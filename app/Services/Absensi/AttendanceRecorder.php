<?php

namespace App\Services\Absensi;

use App\Models\Absensi\AttendanceDaily;
use App\Models\Absensi\AttendanceEvent;
use App\Models\Absensi\AttendancePeriod;
use App\Models\Absensi\Credential;
use App\Models\Absensi\Device;
use App\Models\Absensi\PeopleRef;
use App\Models\Absensi\SchedulesRef;
use Carbon\Carbon;

/**
 * Alur inti modul Absensi: terima 1 scan/tap mentah, insert ke attendance_events
 * (insert-only, lihat AttendanceEvent), lalu upsert agregat harian & per-periode.
 *
 * Ini implementasi "worker/trigger" yang disebut di ARCHITECTURE.md §2.3 langkah 2 -
 * sengaja dijalankan SYNCHRONOUS di request (bukan queued job), karena kiosk butuh
 * feedback instan ("Hadir - Andi Saputra") begitu tap/scan terjadi. Job queued
 * terpisah (belum dibuat) cuma untuk langkah 3: sync attendance_daily/period ->
 * student_attendance/teacher_attendance di DB UTAMA.
 *
 * PENYEDERHANAAN yang sengaja diambil untuk versi awal ini (bukan keputusan final):
 * - Status "Terlambat" belum dihitung - semua check-in valid ditandai "Hadir".
 *   Penentuan jam terlambat butuh keputusan bisnis (jam masuk per sekolah/kelas)
 *   yang belum ada di schema (schools_ref tidak punya kolom jam masuk sekolah).
 * - Toggle check_in/check_out murni berdasarkan event terakhir hari itu untuk
 *   orang yang sama (belum ada konsep "sesi" eksplisit).
 */
class AttendanceRecorder
{
    /**
     * @return array{success:bool,recognized:bool,duplicate:bool,person_name:?string,event_type:string,recorded_at:string}
     */
    public function record(Device $device, string $method, string $rawValue): array
    {
        $now = Carbon::now();
        $hash = hash('sha256', $rawValue);

        $credential = Credential::where('school_id', $device->school_id)
            ->where('method', $method)
            ->where('credential_hash', $hash)
            ->where('is_active', true)
            ->first();

        $person = null;
        if ($credential) {
            $person = PeopleRef::where('person_id', $credential->person_id)
                ->where('person_type', $credential->person_type)
                ->where('is_active', true)
                ->first();
        }

        $schedule = $this->resolveActiveSchedule($device, $now);
        $isDuplicate = $person ? $this->isDuplicateTap($device, $person, $now) : false;
        $eventType = $person ? $this->resolveEventType($device, $person, $now) : 'unknown';
        $isValid = $person !== null && ! $isDuplicate;

        AttendanceEvent::create([
            'school_id' => $device->school_id,
            'device_id' => $device->id,
            'schedule_id' => $schedule?->schedule_id,
            'person_id' => $person?->person_id,
            'person_type' => $person?->person_type,
            'method' => $method,
            'event_type' => $eventType,
            'is_valid' => $isValid,
            'flagged_reason' => match (true) {
                $isDuplicate => 'duplicate_tap_lt_5s',
                ! $person => 'credential_not_found',
                default => null,
            },
            'recorded_at' => $now,
        ]);

        if ($isValid) {
            $this->upsertDaily($device, $person, $now, $eventType, $method);

            if ($schedule) {
                $this->upsertPeriod($device, $person, $schedule, $now, $eventType, $method);
            }
        }

        return [
            'success' => $isValid,
            'recognized' => $person !== null,
            'duplicate' => $isDuplicate,
            'person_name' => $person?->full_name,
            'event_type' => $eventType,
            'recorded_at' => $now->format('H:i:s'),
        ];
    }

    private function resolveActiveSchedule(Device $device, Carbon $now): ?SchedulesRef
    {
        if (! $device->default_class_id) {
            return null;
        }

        return SchedulesRef::where('school_id', $device->school_id)
            ->where('class_id', $device->default_class_id)
            ->where('day_of_week', $now->isoWeekday())
            ->where('start_time', '<=', $now->format('H:i:s'))
            ->where('end_time', '>=', $now->format('H:i:s'))
            ->where('is_active', true)
            ->first();
    }

    private function isDuplicateTap(Device $device, PeopleRef $person, Carbon $now): bool
    {
        $lastEvent = AttendanceEvent::where('device_id', $device->id)
            ->where('person_id', $person->person_id)
            ->where('person_type', $person->person_type)
            ->orderByDesc('recorded_at')
            ->first();

        return $lastEvent !== null && $lastEvent->recorded_at->diffInSeconds($now) < 5;
    }

    private function resolveEventType(Device $device, PeopleRef $person, Carbon $now): string
    {
        $lastEventToday = AttendanceEvent::where('school_id', $device->school_id)
            ->where('person_id', $person->person_id)
            ->where('person_type', $person->person_type)
            ->whereDate('recorded_at', $now->toDateString())
            ->where('is_valid', true)
            ->orderByDesc('recorded_at')
            ->first();

        if (! $lastEventToday || $lastEventToday->event_type === 'check_out') {
            return 'check_in';
        }

        return 'check_out';
    }

    private function upsertDaily(Device $device, PeopleRef $person, Carbon $now, string $eventType, string $method): void
    {
        $daily = AttendanceDaily::firstOrNew([
            'school_id' => $device->school_id,
            'person_id' => $person->person_id,
            'person_type' => $person->person_type,
            'date' => $now->toDateString(),
        ]);

        if ($eventType === 'check_in' && ! $daily->first_check_in) {
            $daily->first_check_in = $now->format('H:i:s');
            $daily->status = 'Hadir'; // lihat catatan penyederhanaan di docblock kelas
            $daily->primary_method = $method;
        }

        if ($eventType === 'check_out') {
            $daily->last_check_out = $now->format('H:i:s');
        }

        $daily->total_events = ($daily->total_events ?? 0) + 1;
        $daily->updated_at = $now;
        $daily->save();
    }

    private function upsertPeriod(Device $device, PeopleRef $person, SchedulesRef $schedule, Carbon $now, string $eventType, string $method): void
    {
        $period = AttendancePeriod::firstOrNew([
            'school_id' => $device->school_id,
            'schedule_id' => $schedule->schedule_id,
            'person_id' => $person->person_id,
            'person_type' => $person->person_type,
            'date' => $now->toDateString(),
        ]);

        if ($eventType === 'check_in' && ! $period->first_check_in) {
            $period->first_check_in = $now->format('H:i:s');
            $period->status = 'Hadir';
            $period->primary_method = $method;
        }

        if ($eventType === 'check_out') {
            $period->last_check_out = $now->format('H:i:s');
        }

        $period->total_events = ($period->total_events ?? 0) + 1;
        $period->updated_at = $now;
        $period->save();
    }
}
