<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly \Illuminate\Support\Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows->map(fn ($r) => [
            $r['nama'],
            $r['counts']['Hadir'],
            $r['counts']['Terlambat'],
            $r['counts']['Sakit'],
            $r['counts']['Izin'],
            $r['counts']['Alpa'],
            $r['total_recorded'],
            $r['tidak_tercatat'],
            $r['percentage'] . '%',
        ]);
    }

    public function headings(): array
    {
        return ['Nama', 'Hadir', 'Terlambat', 'Sakit', 'Izin', 'Alpa', 'Total Tercatat', 'Tidak Tercatat', '% Kehadiran'];
    }

    public function title(): string
    {
        return 'Rekap Absensi';
    }
}