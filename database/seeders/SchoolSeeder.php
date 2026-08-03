<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    private array $periods = [
        ['07:00:00', '08:30:00'],
        ['08:30:00', '10:00:00'],
        ['10:15:00', '11:45:00'],
        ['12:30:00', '14:00:00'],
    ];

    private array $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    private array $subjectPool = [
        'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Fisika', 'Kimia',
        'Biologi', 'Sejarah', 'Geografi', 'Ekonomi', 'Sosiologi',
        'PPKn', 'Pendidikan Jasmani', 'Seni Budaya', 'Pendidikan Agama',
    ];

    public function run(): void
    {
        $start = microtime(true);

        $school = $this->seedSchool();
        $schoolId = $school['id'];
        $suffix   = $school['suffix'];

        $this->command?->line("🏫 School: {$school['name']} ({$suffix})");

        // ── 1. Users peran staf (kepsek, kurikulum, tu, kesiswaan, bk, toolman) ─
        $staffUsers = $this->seedStaffUsers($schoolId, $suffix);

        // ── 2. Akademik: Majors + Classes + Teachers + Students + Schedules ──
        $academic = $this->seedAcademicCore($schoolId, $suffix);

        // ── 3. Data Staff detail (bukan cuma user, tapi record staff lengkap) ─
        $staffRecords = $this->seedStaffRecords($schoolId, $suffix, $staffUsers);

        // ── 4. Homeroom Assignments (wali kelas <-> kelas) ────────────────────
        $this->seedHomeroomAssignments($schoolId, $academic['classes'], $academic['homeroomTeachers']);

        // ── 5. Grade Configs (1 per kelas) ───────────────────────────────────
        $this->seedGradeConfigs($schoolId, $academic['classes'], $staffUsers['kepsek']);

        // ── 6. Exam + Exam Questions ────────────────────────────────────────
        $this->seedExams($schoolId, $suffix, $academic);

        // ── 7. Announcements ────────────────────────────────────────────────
        $this->seedAnnouncements($schoolId, $staffUsers);

        // ── 8. Inventory Lab ────────────────────────────────────────────────
        $this->seedInventory($schoolId);

        // ── 9. Lab Bookings + Lab Visits ────────────────────────────────────
        $this->seedLabBookings($schoolId, $academic, $staffUsers);

        // ── 10. Student Attendance (10 hari terakhir, 50 siswa random) ─────
        $this->seedStudentAttendance($schoolId, $academic['students'], $academic['classes'], $staffUsers['wakel']);

        // ── 11. Teacher Attendance (10 hari, semua guru) ────────────────────
        $this->seedTeacherAttendance($schoolId, $academic['teachers'], $staffUsers['tu']);

        // ── 12. Student Grades (20 siswa x 10 mapel) ────────────────────────
        $this->seedStudentGrades($schoolId, $academic);

        // ── 13. Student Sikap (50 siswa) ────────────────────────────────────
        $this->seedStudentSikap($schoolId, $academic['students'], $academic['classes']);

        // ── 14. Student Achievements (10 prestasi) ──────────────────────────
        $this->seedStudentAchievements($schoolId, $academic['students']);

        // ── 15. Student Records (catatan kesiswaan, 15 record) ─────────────
        $this->seedStudentRecords($schoolId, $academic['students'], $staffUsers['kesiswaan']);

        // ── 16. Counseling Sessions (sesi BK, 12 sesi) ─────────────────────
        $this->seedCounselingSessions($schoolId, $academic['students'], $staffRecords['bk']);

        // ── 17. Teaching Attendance + Student Subject Attendance ────────────
        $this->seedTeachingAttendance($schoolId, $academic);

        // ── 18. Lesson Journals (30 jurnal mengajar) ────────────────────────
        $this->seedLessonJournals($schoolId, $academic);

        // ── 19. Keuangan: Kategori + Transaksi Pemasukan & Pengeluaran ─────
        $keuangan = $this->seedKeuangan($schoolId, $staffUsers);

        // ── 20. Dana BOS + Pengajuan Anggaran + Realisasi BOS ───────────────
        $this->seedBos($schoolId, $staffUsers, $keuangan);

        // ── 21. Audit Keuangan & Activity Logs ──────────────────────────────
        $this->seedAuditAndActivity($schoolId, $staffUsers);

        // ── 22. Toolman Reports ─────────────────────────────────────────────
        $this->seedToolmanReports($schoolId, $staffRecords['toolman']);

        // ── 23. Subscriptions history ───────────────────────────────────────
        $this->seedSubscriptions($schoolId);

        $elapsed = number_format(microtime(true) - $start, 2);
        $this->command?->newLine();
        $this->command?->info("✅ Selesai dalam {$elapsed}s. Rincian:");
        $this->command?->table(['Modul', 'Jumlah'], [
            ['Users (staf + guru + siswa)', number_format(count($staffUsers) + count($academic['teacherUserIds']) + count($academic['studentUserIds']))],
            ['Majors / Kelas / Siswa / Guru', number_format(count($academic['majorIds'])) . ' / ' . number_format(count($academic['classes'])) . ' / ' . number_format(count($academic['students'])) . ' / ' . number_format(count($academic['teachers']))],
            ['Schedules', number_format(count($academic['scheduleIds']))],
            ['Transaksi (masuk / keluar)', number_format($keuangan['total_pemasukan']) . ' / ' . number_format($keuangan['total_pengeluaran'])],
            ['Total tabel disentuh', '23 modul'],
        ]);
    }

    // =====================================================================
    // 0. SCHOOL UTAMA
    // =====================================================================
    private function seedSchool(): array
    {
        $schoolId = Str::uuid()->toString();

        DB::table('schools')->insert([
            'id'                     => $schoolId,
            'name'                   => 'SMA Negeri 1 Surya Nusantara',
            'slug'                   => 'sman1-surya-nusantara',
            'npsn'                   => '20104567',
            'nss'                    => '3010789012',
            'level'                  => 'SMA',
            'status'                 => 'Negeri',
            'accreditation'          => 'A',
            'address'                => 'Jl. Pendidikan Raya No. 123',
            'village'                => 'Surajaya',
            'district'               => 'Kecamatan Cibeunying',
            'city'                   => 'Kota Bandung',
            'province'               => 'Jawa Barat',
            'postal_code'            => '40123',
            'phone'                  => '(022) 555-0123',
            'email'                  => 'info@sman1-surya.sch.id',
            'website'                => 'https://sman1-surya.sch.id',
            'logo'                   => null,
            'principal_name'         => 'Drs. H. Ahmad Solihin, M.Pd.',
            'principal_nip'          => null, // akan di-encrypt via cast (jika dipakai, disini null saja - langsung via model lebih aman)
            'vision'                 => 'Terwujudnya lulusan yang beriman, berakhlak mulia, unggul dalam prestasi, dan peduli lingkungan.',
            'mission'                => "1. Menyelenggarakan pembelajaran berkualitas berbasis teknologi.\n2. Mengembangkan potensi akademik dan non-akademik siswa.\n3. Menumbuhkan karakter religius dan nasionalis.\n4. Membangun kemitraan dengan masyarakat dan industri.",
            'motto'                  => 'Cerdas, Santun, Berprestasi',
            'bank_name'              => 'Bank Negara Indonesia (BNI)',
            'bank_account_number'    => null,
            'bank_account_name'      => null,
            'subscription_plan'      => 'pro',
            'subscription_until'     => now()->addYears(2),
            'max_users'              => 1000,
            'is_active'              => true,
            'onboarded_at'           => now()->subMonths(6),
            'latitude'               => -6.9147440,
            'longitude'              => 107.6098100,
            'geofence_radius_meters' => 200,
            'created_at'             => now()->subMonths(8),
            'updated_at'             => now(),
        ]);

        return [
            'id'     => $schoolId,
            'name'   => 'SMA Negeri 1 Surya Nusantara',
            'suffix' => 'surya',
        ];
    }

    // =====================================================================
    // 1. USER STAF (tidak termasuk guru & siswa, ini bagian kepegawaian TU/dll)
    // =====================================================================
    private function seedStaffUsers(string $schoolId, string $suffix): array
    {
        $rows = [
            'kepsek'    => ['kepsek',    "kepsek_{$suffix}",    "kepsek@{$suffix}.sch.id",    1],
            'kurikulum' => ['kurikulum', "kurikulum_{$suffix}", "kurikulum@{$suffix}.sch.id", 2],
            'tu'        => ['tu',        "tu_{$suffix}",        "tu@{$suffix}.sch.id",        3],
            'guru'      => ['guru_mapel',"guru_{$suffix}",      "guru@{$suffix}.sch.id",      4],
            'wakel'     => ['wali_kelas',"wakel_{$suffix}",     "wakel@{$suffix}.sch.id",     5],
            'kesiswaan' => ['kesiswaan', "kesiswaan_{$suffix}", "kesiswaan@{$suffix}.sch.id", 6],
            'bk'        => ['bk',        "bk_{$suffix}",        "bk@{$suffix}.sch.id",        7],
            'toolman'   => ['toolman',   "toolman_{$suffix}",   "toolman@{$suffix}.sch.id",   8],
            'siswa'     => ['siswa',     "siswa_{$suffix}",     "siswa@{$suffix}.sch.id",     9],
        ];

        $ids = [];
        foreach ($rows as $key => [$role, $username, $email]) {
            $id = Str::uuid()->toString();
            DB::table('users')->insert([
                'id'         => $id,
                'school_id'  => $schoolId,
                'role'       => $role,
                'username'   => $username,
                'email'      => $email,
                'password'   => bcrypt('password123'),
                'is_active'  => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now(),
            ]);
            $ids[$key] = $id;
        }

        return $ids;
    }

    // =====================================================================
    // 2. AKADEMIK CORE (Majors, Classes, Teachers, Students, Schedules)
    // =====================================================================
    private function seedAcademicCore(string $schoolId, string $suffix): array
    {
        $faker = fake('id_ID');
        $grades = ['X', 'XI', 'XII'];
        $rombelLetters = ['A', 'B'];
        $studentsPerClass = 25;

        $majorNames = [
            'IPA' => 'Ilmu Pengetahuan Alam',
            'IPS' => 'Ilmu Pengetahuan Sosial',
        ];

        // Majors
        $majorIds = [];
        foreach ($majorNames as $abbr => $name) {
            $majorId = Str::uuid()->toString();
            $majorIds[$abbr] = $majorId;
            DB::table('majors')->insert([
                'id'           => $majorId,
                'school_id'    => $schoolId,
                'name'         => $name,
                'abbreviation' => $abbr,
                'description'  => "Program {$name} dengan kurikulum Merdeka, fokus pembelajaran berbasis projek dan literasi sains/sosial.",
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Classes (3 grade x 2 major x 2 rombel = 12 kelas)
        $classes = [];
        foreach ($grades as $grade) {
            foreach ($majorIds as $abbr => $majorId) {
                foreach ($rombelLetters as $rombel) {
                    $classId = Str::uuid()->toString();
                    $classes[] = [
                        'id'          => $classId,
                        'grade'       => $grade,
                        'major_id'    => $majorId,
                        'major_abbr'  => $abbr,
                        'class_group' => $rombel,
                        'nama_kelas'  => "{$grade} {$abbr} {$rombel}",
                    ];
                    DB::table('classes')->insert([
                        'id'            => $classId,
                        'school_id'     => $schoolId,
                        'grade'         => $grade,
                        'major_id'      => $majorId,
                        'class_group'   => $rombel,
                        'academic_year' => '2026/2027',
                        'nama_kelas'    => "{$grade} {$abbr} {$rombel}",
                        'kapasitas'     => $studentsPerClass + 6,
                        'is_active'     => true,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }

        // Teachers (wali kelas 12 orang + guru mapel 8 tambahan = 20 guru)
        $teacherCountTotal = count($classes) + 8;
        $teachers = [];
        $teacherUserIds = [];
        $homeroomTeachers = []; // indexnya sama dengan classes
        $subjectTeacherMap = [];

        for ($i = 0; $i < $teacherCountTotal; $i++) {
            $teacherUserId = Str::uuid()->toString();
            $teacherId     = Str::uuid()->toString();
            $fullName      = $faker->name();
            $subject       = $this->subjectPool[$i % count($this->subjectPool)];
            $gender        = $faker->randomElement(['L', 'P']);
            $isHomeroom    = $i < count($classes);

            $email = "guru.{$suffix}.{$i}@sman1-surya.sch.id";

            DB::table('users')->insert([
                'id'         => $teacherUserId,
                'school_id'  => $schoolId,
                'role'       => $isHomeroom ? 'wali_kelas' : 'guru_mapel',
                'username'   => "guru{$i}_{$suffix}",
                'email'      => $email,
                'password'   => bcrypt('password123'),
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('teachers')->insert([
                'id'                => $teacherId,
                'school_id'         => $schoolId,
                'user_id'           => $teacherUserId,
                'nip_hash'          => hash('sha256', "19750101200{$i}01001"),
                'nuptk_hash'        => hash('sha256', "NUPTK-{$suffix}-{$i}"),
                'full_name'         => $fullName,
                'email'             => $email,
                'gender'            => $gender,
                'last_education'    => $faker->randomElement(['S1', 'S2']),
                'education_major'   => "Pendidikan {$subject}",
                'employment_status' => $faker->randomElement(['PNS', 'PPPK', 'Honorer', 'GTY']),
                'joined_date'       => $faker->dateTimeBetween('-15 years', '-2 years')->format('Y-m-d'),
                'major_id'          => null,
                'is_homeroom'       => $isHomeroom,
                'photo'             => null,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $teacherData = ['id' => $teacherId, 'user_id' => $teacherUserId, 'subject' => $subject, 'name' => $fullName];
            $teachers[] = $teacherData;
            $teacherUserIds[] = $teacherUserId;
            $subjectTeacherMap[$subject][] = $teacherId;

            if ($isHomeroom) {
                $homeroomTeachers[] = $teacherId;
            }
        }

        // Students (12 kelas x 25 = 300 siswa)
        $students = [];
        $studentUserIds = [];
        $studentRows = [];
        $userRows = [];
        $globalIdx = 0;

        foreach ($classes as $classIndex => $class) {
            for ($s = 0; $s < $studentsPerClass; $s++) {
                $globalIdx++;
                $studentUserId = Str::uuid()->toString();
                $studentId     = Str::uuid()->toString();
                $fullName      = $faker->name();
                $gender        = $faker->randomElement(['L', 'P']);
                $nisnRand      = str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
                $nisRand       = "2026-{$classIndex}-{$s}";
                $email         = "siswa.{$suffix}.{$globalIdx}@sman1-surya.sch.id";

                $userRows[] = [
                    'id'         => $studentUserId,
                    'school_id'  => $schoolId,
                    'role'       => 'siswa',
                    'username'   => "siswa{$globalIdx}_{$suffix}",
                    'email'      => $email,
                    'password'   => bcrypt('password123'),
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $studentRows[] = [
                    'id'           => $studentId,
                    'school_id'    => $schoolId,
                    'user_id'      => $studentUserId,
                    'nis_hash'     => hash('sha256', $nisRand),
                    'nisn_hash'    => hash('sha256', $nisnRand),
                    'full_name'    => $fullName,
                    'email'        => $email,
                    'gender'       => $gender,
                    'grade'        => $class['grade'],
                    'major_id'     => $class['major_id'],
                    'class_group'  => $class['class_group'],
                    'class_id'     => $class['id'],
                    'joined_date'  => $faker->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d'),
                    'status'       => 'aktif',
                    'photo'        => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

                $students[] = ['id' => $studentId, 'class_id' => $class['id'], 'grade' => $class['grade'], 'major_id' => $class['major_id'], 'name' => $fullName];
                $studentUserIds[] = $studentUserId;
            }
        }

        foreach (array_chunk($userRows, 500) as $chunk)    DB::table('users')->insert($chunk);
        foreach (array_chunk($studentRows, 500) as $chunk) DB::table('students')->insert($chunk);

        // Schedules (12 kelas x 5 hari x 4 periode = 240 schedule)
        $scheduleRows = [];
        $scheduleIds  = [];
        foreach ($classes as $classIndex => $class) {
            $slotIndex = 0;
            foreach ($this->days as $day) {
                foreach ($this->periods as [$start, $end]) {
                    $subject   = $this->subjectPool[$slotIndex % count($this->subjectPool)];
                    $teacherId = $subjectTeacherMap[$subject][$slotIndex % count($subjectTeacherMap[$subject])] ?? $teachers[0]['id'];
                    $scheduleId = Str::uuid()->toString();

                    $scheduleRows[] = [
                        'id'          => $scheduleId,
                        'school_id'   => $schoolId,
                        'teacher_id'  => $teacherId,
                        'subject'     => $subject,
                        'grade'       => $class['grade'],
                        'major'       => $class['major_abbr'],
                        'class_group' => $class['class_group'],
                        'class_id'    => $class['id'],
                        'day'         => $day,
                        'start_time'  => $start,
                        'end_time'    => $end,
                        'room'        => "R. Kelas {$class['nama_kelas']}",
                        'is_active'   => true,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                    $scheduleIds[] = $scheduleId;
                    $slotIndex++;
                }
            }
        }
        foreach (array_chunk($scheduleRows, 500) as $chunk) DB::table('schedules')->insert($chunk);

        return compact('majorIds', 'classes', 'teachers', 'teacherUserIds', 'homeroomTeachers', 'students', 'studentUserIds', 'scheduleIds', 'subjectTeacherMap');
    }

    // =====================================================================
    // 3. STAFF RECORDS (TU, kesiswaan, BK, toolman, kurikulum, kepsek)
    // =====================================================================
    private function seedStaffRecords(string $schoolId, string $suffix, array $staffUsers): array
    {
        $faker = fake('id_ID');
        $map = [
            'kepsek'    => ['Kepala Sekolah',              $staffUsers['kepsek']],
            'kurikulum' => ['Wakil Kepala Sekolah Bid. Kurikulum', $staffUsers['kurikulum']],
            'tu'        => ['Kepala Tata Usaha',           $staffUsers['tu']],
            'kesiswaan' => ['Wakil Kepala Sekolah Bid. Kesiswaan', $staffUsers['kesiswaan']],
            'bk'        => ['Guru Bimbingan Konseling',    $staffUsers['bk']],
            'toolman'   => ['Laboran / Toolman',           $staffUsers['toolman']],
        ];

        $ids = [];
        $i = 0;
        foreach ($map as $key => [$position, $userId]) {
            $id = Str::uuid()->toString();
            DB::table('staff')->insert([
                'id'          => $id,
                'school_id'   => $schoolId,
                'user_id'     => $userId,
                'full_name'   => $faker->name(),
                'nip_hash'    => hash('sha256', "1970010120100{$i}100"),
                'email'       => "staf.{$key}@sman1-surya.sch.id",
                'gender'      => $faker->randomElement(['L', 'P']),
                'position'    => $position,
                'joined_date' => $faker->dateTimeBetween('-18 years', '-4 years')->format('Y-m-d'),
                'photo'       => null,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $ids[$key] = $id;
            $i++;
        }
        return $ids;
    }

    // =====================================================================
    // 4. HOMEROOM ASSIGNMENTS
    // =====================================================================
    private function seedHomeroomAssignments(string $schoolId, array $classes, array $homeroomTeachers): void
    {
        $rows = [];
        foreach ($classes as $i => $class) {
            $rows[] = [
                'id'            => Str::uuid()->toString(),
                'school_id'     => $schoolId,
                'teacher_id'    => $homeroomTeachers[$i] ?? null,
                'class_id'      => $class['id'],
                'academic_year' => '2026/2027',
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        DB::table('homeroom_assignments')->insert($rows);
    }

    // =====================================================================
    // 5. GRADE CONFIGS
    // =====================================================================
    private function seedGradeConfigs(string $schoolId, array $classes, string $finalizedBy): void
    {
        $rows = [];
        foreach ($classes as $class) {
            $rows[] = [
                'id'               => Str::uuid()->toString(),
                'school_id'        => $schoolId,
                'class_id'         => $class['id'],
                'academic_year'    => '2026/2027',
                'semester'         => 'Ganjil',
                'kurikulum'        => 'Merdeka',
                'kkm'              => 70,
                'is_finalized'     => false,
                'finalized_by'     => null,
                'finalized_at'     => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }
        DB::table('grade_configs')->insert($rows);
    }

    // =====================================================================
    // 6. EXAM + QUESTIONS
    // =====================================================================
    private function seedExams(string $schoolId, string $suffix, array $academic): void
    {
        $faker = fake('id_ID');
        $exams = [];

        for ($i = 0; $i < 5; $i++) {
            $class   = $academic['classes'][$i % count($academic['classes'])];
            $teacher = $academic['teachers'][$i % count($academic['teachers'])];
            $subject = $this->subjectPool[$i % count($this->subjectPool)];
            $examId  = Str::uuid()->toString();

            DB::table('exams')->insert([
                'id'             => $examId,
                'school_id'      => $schoolId,
                'name'           => "Ujian Tengah Semester {$subject} - {$class['nama_kelas']}",
                'subject'        => $subject,
                'grade'          => $class['grade'],
                'major'          => $class['major_abbr'],
                'class_id'       => $class['id'],
                'date'           => now()->addDays($i + 2)->toDateString(),
                'start_time'     => '08:00:00',
                'end_time'       => '10:00:00',
                'supervisor_id'  => $teacher['id'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $exams[] = ['id' => $examId, 'teacher_id' => $teacher['id']];
        }

        $qRows = [];
        foreach ($exams as $exam) {
            for ($q = 0; $q < 4; $q++) {
                $qRows[] = [
                    'id'            => Str::uuid()->toString(),
                    'school_id'     => $schoolId,
                    'exam_id'       => $exam['id'],
                    'teacher_id'    => $exam['teacher_id'],
                    'question'      => $faker->sentence(rand(8, 18)) . '?',
                    'option_a'      => $faker->sentence(4),
                    'option_b'      => $faker->sentence(4),
                    'option_c'      => $faker->sentence(4),
                    'option_d'      => $faker->sentence(4),
                    'correct_answer'=> ['A','B','C','D'][$q % 4],
                    'created_at'    => now(),
                ];
            }
        }
        DB::table('exam_questions')->insert($qRows);
    }

    // =====================================================================
    // 7. ANNOUNCEMENTS
    // =====================================================================
    private function seedAnnouncements(string $schoolId, array $staffUsers): void
    {
        $rows = [
            ['Pengumuman Tahun Ajaran Baru 2026/2027',   'Seluruh siswa diharapkan melakukan daftar ulang pada tanggal 15 Juli 2026. Pembagian kelas dilaksanakan setelah rapat wali kelas.', 'all',        true,  $staffUsers['kepsek']],
            ['Jadwal Pembagian Raport Semester Ganjil', 'Raport akan dibagikan pada hari Sabtu, 20 Desember 2026, pukul 08.00 WIB di aula sekolah. Orang tua/wali diwajibkan hadir.', 'all',        true,  $staffUsers['kepsek']],
            ['Libur Hari Raya Idul Adha',               'Sekolah libur tanggal 17-18 Juni 2026. Pembelajaran daring untuk kelas XII akan diumumkan melalui grup kelas.',         'all',        true,  $staffUsers['kurikulum']],
            ['Pendaftaran OSIS Periode 2026/2027',      'Pendaftaran calon pengurus OSIS dibuka sampai 30 September. Pengumuman seleksi melalui website sekolah.',             'siswa',      true,  $staffUsers['kesiswaan']],
            ['Workshop Digital Marketing untuk Siswa',  'Kegiatan workshop untuk kelas XI IPS pada hari Rabu, 14.00-16.00 WIB di Ruang Multimedia. Dihadiri 30 siswa terpilih.', 'guru,siswa', false, $staffUsers['kurikulum']],
        ];

        $dbRows = [];
        foreach ($rows as [$title, $content, $visibility, $important, $createdBy]) {
            $dbRows[] = [
                'id'           => Str::uuid()->toString(),
                'school_id'    => $schoolId,
                'title'        => $title,
                'content'      => $content,
                'created_by'   => $createdBy,
                'visibility'   => $visibility,
                'is_important' => $important,
                'published_at' => now()->subDays(rand(1, 20))->toDateString(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        DB::table('announcements')->insert($dbRows);
    }

    // =====================================================================
    // 8. INVENTORY
    // =====================================================================
    private function seedInventory(string $schoolId): void
    {
        $items = [
            ['Laptop Pembelajaran',         30, 'Baik',         'Lab Komputer',     'Laptop Core i5 8GB untuk praktik TIK'],
            ['Proyektor LCD',                6, 'Baik',         'Ruang Kelas',      'Proyektor Epson EB-X41 untuk pembelajaran'],
            ['Mikroskop Cahaya',            25, 'Baik',         'Lab IPA - Biologi','Mikroskop monokuler 40x-400x'],
            ['Tabung Reaksi',              300, 'Baik',         'Lab IPA - Kimia',  'Tabung reaksi kaca 20 mL'],
            ['Papan Tulis Whiteboard',      12, 'Baik',         'Ruang Kelas',      'Whiteboard 120x240 cm lengkap spidol'],
            ['Meja Siswa',                 300, 'Rusak Ringan', 'Ruang Kelas',      'Meja kayu 60x40 cm, beberapa goresan ringan'],
            ['Kursi Siswa',                310, 'Baik',         'Ruang Kelas',      'Kursi besi jok busa standar'],
            ['Rak Buku',                    15, 'Baik',         'Perpustakaan',     'Rak besi 5 tingkat untuk koleksi buku'],
            ['Bola Basket',                 25, 'Baik',         'Gudang Olahraga',  'Bola basket standar size 7'],
            ['Bola Sepak',                  20, 'Rusak Ringan', 'Gudang Olahraga',  'Bola sepak standar, beberapa butuh angin'],
            ['Printer Laser',                5, 'Baik',         'Tata Usaha',       'Printer HP LaserJet untuk dokumen sekolah'],
            ['Scanner Dokumen',              2, 'Baik',         'Tata Usaha',       'Scanner flatbed warna Epson'],
            ['Speaker Bluetooth',           10, 'Baik',         'Multimedia',       'Speaker aktif Bluetooth untuk kegiatan acara'],
            ['Alat Peraga Matematika',       8, 'Baik',         'Gudang Alat Peraga','Kompas, penggaris busur, bangun ruang'],
            ['Kamera Dokumentasi',           2, 'Baik',         'Humas',            'Mirrorless kamera untuk dokumentasi kegiatan sekolah'],
        ];

        $rows = [];
        foreach ($items as [$name, $qty, $cond, $loc, $desc]) {
            $rows[] = [
                'id'         => Str::uuid()->toString(),
                'school_id'  => $schoolId,
                'item_name'  => $name,
                'quantity'   => $qty,
                'condition'  => $cond,
                'location'   => $loc,
                'description'=> $desc,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('inventory')->insert($rows);
    }

    // =====================================================================
    // 9. LAB BOOKINGS + VISITS
    // =====================================================================
    private function seedLabBookings(string $schoolId, array $academic, array $staffUsers): void
    {
        $labs = ['Lab IPA - Biologi', 'Lab IPA - Kimia', 'Lab Komputer', 'Ruang Multimedia'];
        $bookingRows = [];
        $visitRows   = [];

        for ($i = 0; $i < 7; $i++) {
            $teacher  = $academic['teachers'][$i % count($academic['teachers'])];
            $bookingId = Str::uuid()->toString();
            $date      = now()->addDays($i + 1)->toDateString();

            $bookingRows[] = [
                'id'          => $bookingId,
                'school_id'   => $schoolId,
                'teacher_id'  => $teacher['id'],
                'date'        => $date,
                'start_time'  => $this->periods[$i % 4][0],
                'end_time'    => $this->periods[$i % 4][1],
                'lab_name'    => $labs[$i % count($labs)],
                'purpose'     => 'Praktik mata pelajaran terkait dengan demonstrasi alat dan diskusi kelompok.',
                'status'      => ['Menunggu', 'Disetujui', 'Disetujui', 'Ditolak'][($i + 1) % 4],
                'reviewed_by' => $staffUsers['toolman'],
                'reviewed_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            if ($i % 4 !== 3) { // jika tidak ditolak, buat visit
                $visitRows[] = [
                    'id'             => Str::uuid()->toString(),
                    'school_id'      => $schoolId,
                    'lab_booking_id' => $bookingId,
                    'student_count'  => 20 + ($i * 2),
                    'activity'       => 'Pelaksanaan praktik sesuai jadwal booking. Semua alat diperiksa kondisinya sebelum dan sesudah pemakaian.',
                    'created_at'     => now(),
                ];
            }
        }
        DB::table('lab_bookings')->insert($bookingRows);
        if (! empty($visitRows)) DB::table('lab_visits')->insert($visitRows);
    }

    // =====================================================================
    // 10. STUDENT ATTENDANCE (10 hari terakhir, 50 siswa random)
    // =====================================================================
    private function seedStudentAttendance(string $schoolId, array $students, array $classes, string $wakelUserId): void
    {
        $rows = [];
        $picked = collect($students)->random(min(50, count($students)));
        $statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Sakit', 'Izin', 'Alpa'];

        for ($dayOffset = 1; $dayOffset <= 10; $dayOffset++) {
            $date = now()->subWeekdays($dayOffset)->toDateString();

            foreach ($picked as $student) {
                $status = $statuses[array_rand($statuses)];
                $checkIn = $checkOut = null;
                if ($status === 'Hadir') {
                    $checkIn  = sprintf('%02d:%02d:00', 6, rand(45, 59));
                    $checkOut = sprintf('%02d:%02d:00', 14, rand(0, 30));
                }

                $rows[] = [
                    'id'          => Str::uuid()->toString(),
                    'school_id'   => $schoolId,
                    'student_id'  => $student['id'],
                    'date'        => $date,
                    'check_in'    => $checkIn,
                    'check_out'   => $checkOut,
                    'status'      => $status,
                    'notes'       => $status === 'Sakit'  ? 'Sakit demam, surat keterangan dokter dilampirkan.' :
                                     ($status === 'Izin' ? 'Izin menghadiri acara keluarga di luar kota.' : null),
                    'recorded_by' => $wakelUserId,
                    'created_at'  => now(),
                ];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('student_attendance')->insert($chunk);
    }

    // =====================================================================
    // 11. TEACHER ATTENDANCE (10 hari, 15 guru random)
    // =====================================================================
    private function seedTeacherAttendance(string $schoolId, array $teachers, string $tuUserId): void
    {
        $rows = [];
        $picked = collect($teachers)->random(min(15, count($teachers)));
        $statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Sakit', 'Izin'];

        for ($dayOffset = 1; $dayOffset <= 10; $dayOffset++) {
            $date = now()->subWeekdays($dayOffset)->toDateString();

            foreach ($picked as $teacher) {
                $status = $statuses[array_rand($statuses)];
                $rows[] = [
                    'id'          => Str::uuid()->toString(),
                    'school_id'   => $schoolId,
                    'teacher_id'  => $teacher['id'],
                    'date'        => $date,
                    'check_in'    => $status === 'Hadir' ? sprintf('%02d:%02d:00', 6, rand(30, 55)) : null,
                    'check_out'   => $status === 'Hadir' ? sprintf('%02d:%02d:00', 15, rand(10, 45)) : null,
                    'status'      => $status,
                    'notes'       => $status === 'Izin' ? 'Izin menghadiri rapat dinas pendidikan kabupaten.' : null,
                    'recorded_by' => $tuUserId,
                    'created_at'  => now(),
                ];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('teacher_attendance')->insert($chunk);
    }

    // =====================================================================
    // 12. STUDENT GRADES (25 siswa x 6 mapel = 150)
    // =====================================================================
    private function seedStudentGrades(string $schoolId, array $academic): void
    {
        $rows = [];
        $picked = collect($academic['students'])->random(min(25, count($academic['students'])));
        $subjects = array_slice($this->subjectPool, 0, 6);
        $predikatMap = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C'];

        foreach ($picked as $student) {
            foreach ($subjects as $subject) {
                $teacherId = $academic['subjectTeacherMap'][$subject][0] ?? $academic['teachers'][0]['id'];
                $nilaiHarian = rand(65, 95);
                $nilaiTugas  = rand(60, 98);
                $nilaiAkhir  = (int) round(($nilaiHarian + $nilaiTugas + rand(60, 100)) / 3);
                $predikat    = $nilaiAkhir >= 90 ? 'A'  :
                               ($nilaiAkhir >= 85 ? 'A-' :
                               ($nilaiAkhir >= 80 ? 'B+' :
                               ($nilaiAkhir >= 75 ? 'B'  :
                               ($nilaiAkhir >= 70 ? 'B-' :
                               ($nilaiAkhir >= 65 ? 'C+' : 'C')))));

                $rows[] = [
                    'id'             => Str::uuid()->toString(),
                    'school_id'      => $schoolId,
                    'student_id'     => $student['id'],
                    'class_id'       => $student['class_id'],
                    'teacher_id'     => $teacherId,
                    'subject'        => $subject,
                    'academic_year'  => '2026/2027',
                    'semester'       => 'Ganjil',
                    'nilai_harian'   => $nilaiHarian,
                    'nilai_tugas'    => $nilaiTugas,
                    'nilai_akhir'    => $nilaiAkhir,
                    'predikat'       => $predikat,
                    'catatan'        => $nilaiAkhir < 72 ? 'Perlu remedial pada minggu depan. Diwajibkan mengikuti bimbingan.' : 'Sangat baik, terus tingkatkan prestasi.',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) DB::table('student_grades')->insert($chunk);
    }

    // =====================================================================
    // 13. STUDENT SIKAP (50 siswa)
    // =====================================================================
    private function seedStudentSikap(string $schoolId, array $students, array $classes): void
    {
        $rows = [];
        $picked = collect($students)->random(min(50, count($students)));
        $skor = ['SB', 'B', 'B', 'B', 'C'];
        $ekskul = ['Pramuka', 'PMR', 'Rohis', 'Futsal', 'Basket', 'Teater', 'KIR', 'Paduan Suara', 'English Club', 'Tari Saman'];

        foreach ($picked as $student) {
            $rows[] = [
                'id'                       => Str::uuid()->toString(),
                'school_id'                => $schoolId,
                'student_id'               => $student['id'],
                'class_id'                 => $student['class_id'],
                'academic_year'            => '2026/2027',
                'semester'                 => 'Ganjil',
                'sikap_spiritual'          => $skor[array_rand($skor)],
                'sikap_sosial'             => $skor[array_rand($skor)],
                'catatan_sikap'            => 'Ananda menunjukkan sikap disiplin dan tanggung jawab yang baik terhadap diri sendiri dan lingkungan sekolah.',
                'ekskul'                   => $ekskul[array_rand($ekskul)],
                'catatan_wakel'            => 'Perlu ditingkatkan keberanian bertanya di kelas. Ananda aktif dalam kegiatan ekskul.',
                'ketidakhadiran_sakit'     => rand(0, 3),
                'ketidakhadiran_izin'      => rand(0, 2),
                'ketidakhadiran_alpa'      => rand(0, 4),
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }
        DB::table('student_sikap')->insert($rows);
    }

    // =====================================================================
    // 14. STUDENT ACHIEVEMENTS (10 prestasi)
    // =====================================================================
    private function seedStudentAchievements(string $schoolId, array $students): void
    {
        $rows = [];
        $picked = collect($students)->random(min(10, count($students)));
        $prestasiList = [
            ['Juara 1 Lomba Cerdas Cermat Olimpiade Sains', 'Kabupaten', 2026],
            ['Juara 2 Futsal Antar SMA Se-Kota',          'Kota',      2026],
            ['Peringkat 3 OSN Matematika',                'Provinsi',  2026],
            ['Finalis Lomba Debat Bahasa Inggris',        'Nasional',  2026],
            ['Juara 1 Karya Tulis Ilmiah Remaja',         'Provinsi',  2026],
            ['Medali Perak Kejuaraan Karate Antar Pelajar','Kabupaten', 2026],
            ['Juara Harapan Paduan Suara Gita Bahana',    'Kota',      2026],
            ['The Best Debater Kompetisi Pidato',         'Provinsi',  2025],
            ['Pemenang Design Grafis Pelajar',            'Kota',      2026],
            ['Juara 2 Tenis Meja O2SN',                   'Kabupaten', 2025],
        ];

        foreach ($picked->values() as $i => $student) {
            [$title, $level, $year] = $prestasiList[$i];
            $rows[] = [
                'id'          => Str::uuid()->toString(),
                'school_id'   => $schoolId,
                'student_id'  => $student['id'],
                'title'       => $title,
                'level'       => $level,
                'year'        => $year,
                'description' => 'Prestasi diraih atas perjuangan ananda dan bimbingan guru pembimbing. Mengharumkan nama sekolah.',
                'created_at'  => now(),
            ];
        }
        DB::table('student_achievements')->insert($rows);
    }

    // =====================================================================
    // 15. STUDENT RECORDS (15 catatan kesiswaan)
    // =====================================================================
    private function seedStudentRecords(string $schoolId, array $students, string $kesiswaanUserId): void
    {
        $rows = [];
        $picked = collect($students)->random(min(15, count($students)));
        $catatan = [
            'Terlambat masuk sekolah sebanyak 3 kali. Sudah ditegur dan diberi surat peringatan 1.',
            'Tidak mengikuti upacara bendera tanpa keterangan. Bersedia mengikuti bimbingan konseling.',
            'Membantu petugas kebersihan membersihkan lingkungan sekolah secara sukarela.',
            'Terdapat laporan siswa kurang konsentrasi belajar. Sudah dipanggil orang tua untuk musyawarah.',
            'Melanggar ketentuan seragam sekolah. Sudah diberi teguran lisan.',
            'Berprestasi di kegiatan lomba luar sekolah. Diberi piagam penghargaan dalam upacara bendera.',
            'Terlibat perdebatan kurang sehat dengan teman. Sudah didamaikan kedua belah pihak.',
            'Melunasi tunggakan SPP bulan lalu. Keluarga menyampaikan terima kasih atas kebijakan sekolah.',
            'Absen tanpa keterangan selama 2 hari. Orang tua dikonfirmasi, anak sedang sakit demam.',
            'Berhasil meraih ranking 3 paralel kelas. Diberi hadiah buku tulis untuk memotivasi.',
            'Terlibat kegiatan Pramuka Raimuna Nasional. Kegiatan sangat positif untuk pembentukan karakter.',
            'Tidak mengumpulkan tugas matematika sebanyak 2 kali. Sudah dikonsultasikan dengan guru mapel.',
            'Meraih nilai ulangan tertinggi paralel untuk mata pelajaran Fisika. Diberi apresiasi di kelas.',
            'Mendatangi perpustakaan 20x dalam 1 bulan. Diberi predikat pembaca terbaik bulan ini.',
            'Mengikuti kegiatan kerja bakti membersihkan selokan lingkungan sekolah bersama warga.',
        ];

        foreach ($picked->values() as $i => $student) {
            $rows[] = [
                'id'          => Str::uuid()->toString(),
                'school_id'   => $schoolId,
                'student_id'  => $student['id'],
                'activity'    => 'Catatan Kesiswaan',
                'date'        => now()->subDays($i + 1)->toDateString(),
                'description' => $catatan[$i],
                'created_by'  => $kesiswaanUserId,
                'created_at'  => now(),
            ];
        }
        DB::table('student_records')->insert($rows);
    }

    // =====================================================================
    // 16. COUNSELING SESSIONS (10 sesi BK)
    // =====================================================================
    private function seedCounselingSessions(string $schoolId, array $students, string $bkStaffId): void
    {
        $rows = [];
        $picked = collect($students)->random(min(10, count($students)));
        $topics = [
            'Kesulitan memahami materi matematika kelas XI. Rencana: jadwal bimbingan privat 2x seminggu.',
            'Masalah pertemanan di kelas. Sudah dimediasi dan kedua pihak sepakat berdamai.',
            'Kecenderungan kecemasan menghadapi ujian tengah semester. Teknik relaksasi diajarkan.',
            'Konsultasi rencana studi lanjut ke Perguruan Tinggi Negeri favorit.',
            'Kesulitan berkomunikasi dengan orang tua. Sudah dipertemukan untuk wawancara bersama.',
            'Kesulitan manajemen waktu antara kegiatan akademik dan ekskul.',
            'Konsultasi pemilihan jurusan yang sesuai minat dan bakat untuk kelas XII.',
            'Kasus kecanduan game online. Jadwal konseling rutin 1x seminggu ditambah pendampingan.',
            'Kurang percaya diri saat presentasi. Latihan public speaking 1x per minggu.',
            'Masalah ekonomi keluarga yang menyebabkan kesulitan membayar SPP. Diarahkan ke program keringanan.',
        ];

        foreach ($picked->values() as $i => $student) {
            $rows[] = [
                'id'         => Str::uuid()->toString(),
                'school_id'  => $schoolId,
                'student_id' => $student['id'],
                'staff_id'   => $bkStaffId,
                'date'       => now()->subDays(rand(2, 30))->toDateString(),
                'topic'      => explode('.', $topics[$i])[0],
                'result'     => $topics[$i],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('counseling_sessions')->insert($rows);
    }

    // =====================================================================
    // 17. TEACHING ATTENDANCE + STUDENT SUBJECT ATTENDANCE
    // =====================================================================
    private function seedTeachingAttendance(string $schoolId, array $academic): void
    {
        $teachingRows = [];
        $subjectAttRows = [];
        $schedulesSlice = array_slice($academic['scheduleIds'], 0, 15);
        $statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Izin'];

        foreach ($schedulesSlice as $idx => $scheduleId) {
            // Cari schedule dari DB via query quick - tapi kita sudah punya data di academic['schedulesRow']? Tidak, cuma scheduleIds.
            // Alih-alih query, kita pick teacher random + jadwal waktu dari periods.
            $teacher = $academic['teachers'][$idx % count($academic['teachers'])];
            $date    = now()->subWeekdays(($idx % 5) + 1)->toDateString();
            $teachingId = Str::uuid()->toString();
            $status = $statuses[$idx % count($statuses)];

            $teachingRows[] = [
                'id'           => $teachingId,
                'school_id'    => $schoolId,
                'schedule_id'  => $scheduleId,
                'teacher_id'   => $teacher['id'],
                'date'         => $date,
                'start_time'   => $this->periods[$idx % 4][0],
                'end_time'     => $this->periods[$idx % 4][1],
                'topic'        => 'Pembahasan materi pokok bab terbaru dengan latihan soal dan tanya jawab interaktif.',
                'notes'        => $status === 'Izin' ? 'Guru berhalangan, diganti oleh guru piket dengan tugas mandiri.' : null,
                'created_at'   => now(),
            ];

            if ($status === 'Hadir') {
                $randomStudents = collect($academic['students'])->random(15);
                foreach ($randomStudents as $stu) {
                    $stuStatus = $statuses[(array_rand($statuses))];
                    $subjectAttRows[] = [
                        'id'                    => Str::uuid()->toString(),
                        'school_id'             => $schoolId,
                        'teaching_attendance_id'=> $teachingId,
                        'student_id'            => $stu['id'],
                        'schedule_id'           => $scheduleId,
                        'date'                  => $date,
                        'status'                => $stuStatus,
                        'notes'                 => $stuStatus === 'Izin' ? 'Izin istirahat di UKS karena sakit kepala.' : null,
                        'created_at'            => now(),
                    ];
                }
            }
        }
        DB::table('teaching_attendance')->insert($teachingRows);
        foreach (array_chunk($subjectAttRows, 500) as $chunk) DB::table('student_subject_attendance')->insert($chunk);
    }

    // =====================================================================
    // 18. LESSON JOURNALS (30 jurnal)
    // =====================================================================
    private function seedLessonJournals(string $schoolId, array $academic): void
    {
        $rows = [];
        for ($i = 0; $i < 30; $i++) {
            $teacher = $academic['teachers'][$i % count($academic['teachers'])];
            $subject = $this->subjectPool[$i % count($this->subjectPool)];
            $class   = $academic['classes'][$i % count($academic['classes'])];
            $scheduleId = $academic['scheduleIds'][$i % count($academic['scheduleIds'])];

            $rows[] = [
                'id'           => Str::uuid()->toString(),
                'school_id'    => $schoolId,
                'teacher_id'   => $teacher['id'],
                'schedule_id'  => $scheduleId,
                'date'         => now()->subWeekdays(($i % 8) + 1)->toDateString(),
                'subject'      => $subject,
                'grade'        => $class['grade'],
                'class_id'     => $class['id'],
                'topic'        => 'Pembahasan ' . $subject . ': topik inti minggu ini dengan pendalaman soal HOTS dan pengayaan.',
                'notes'        => 'Mayoritas siswa aktif bertanya. Tugas individu dikumpulkan 95%. Beberapa siswa perlu bimbingan tambahan di luar jam pelajaran.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        DB::table('lesson_journals')->insert($rows);
    }

    // =====================================================================
    // 19. KEUANGAN
    // =====================================================================
    private function seedKeuangan(string $schoolId, array $staffUsers): array
    {
        // Kategori Pemasukan
        $katMasukIds = [];
        $katMasuk = [
            ['SPP Siswa Bulanan',      'Pemasukan rutin dari iuran SPP seluruh siswa setiap bulan.'],
            ['Dana BOS Reguler',       'Dana Bantuan Operasional Sekolah dari pemerintah pusat.'],
            ['Donasi / CSR',           'Donasi dari alumni, dunia usaha, dan masyarakat sekitar.'],
            ['Komite / Infaq',         'Iuran sukarela orang tua siswa melalui komite sekolah.'],
            ['Kegiatan Wirausaha',     'Hasil penjualan kantin, koperasi sekolah, dan usaha produksi siswa.'],
        ];
        foreach ($katMasuk as $i => [$nama, $desc]) {
            DB::table('kategori_pemasukan')->insert([
                'school_id'    => $schoolId,
                'nama_kategori'=> $nama,
                'deskripsi'    => $desc,
                'is_active'    => true,
                'created_at'   => now(),
            ]);
            $katMasukIds[] = DB::getPdo()->lastInsertId();
        }

        // Kategori Pengeluaran
        $katKeluarIds = [];
        $katKeluar = [
            ['Guru & Tenaga Kependidikan', 'Honorer, tunjangan, dan gaji tidak tetap staf.'],
            ['Operasional Harian',        'ATK, listrik, air, telepon, internet, kebersihan.'],
            ['Pemeliharaan Sarana',       'Perbaikan gedung, bangunan, perabotan, dan fasilitas umum.'],
            ['Kegiatan Pembelajaran',     'Modul, alat peraga, cetak soal, konsumsi rapat, lomba.'],
            ['Pengembangan Kualitas',     'Diklat guru, workshop, seminar, studi banding.'],
        ];
        foreach ($katKeluar as $i => [$nama, $desc]) {
            DB::table('kategori_pengeluaran')->insert([
                'school_id'    => $schoolId,
                'nama_kategori'=> $nama,
                'deskripsi'    => $desc,
                'is_active'    => true,
                'created_at'   => now(),
            ]);
            $katKeluarIds[] = DB::getPdo()->lastInsertId();
        }

        // Transaksi Pemasukan (12 transaksi)
        $pemasukanRows = [];
        for ($i = 0; $i < 12; $i++) {
            $bulan  = 1 + ($i % 6);
            $tahun  = now()->year;
            $jumlah = match ($i % 5) {
                0 => rand(75000000, 95000000),  // SPP
                1 => rand(120000000, 180000000),// BOS
                2 => rand(5000000, 25000000),   // Donasi
                3 => rand(10000000, 35000000),  // Komite
                default => rand(2000000, 15000000), // Wirausaha
            };
            $katIdx = $i % count($katMasukIds);
            $pemasukanRows[] = [
                'school_id'         => $schoolId,
                'no_transaksi'      => "IN/SM1S/{$tahun}/" . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'tanggal_transaksi' => "{$tahun}-" . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) . '-15',
                'id_kategori'       => $katMasukIds[$katIdx],
                'keterangan'        => match($katIdx) {
                    0 => "SPP Bulan " . ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][$bulan-1] . " TP. 2026/2027",
                    1 => "Dana BOS Reguler Tahapan {$bulan} TA 2026",
                    2 => "Donasi CSR Perusahaan Mitra",
                    3 => "Infaq Komite Periode {$bulan}",
                    4 => "Koperasi Sekolah - Laba Bulanan",
                    default => "Pemasukan Lainnya",
                },
                'sumber'            => ['Transfer Bank', 'Tunai', 'Tunai', 'Transfer Bank', 'Transfer Bank'][$katIdx],
                'jumlah'            => $jumlah,
                'metode_pembayaran' => ($katIdx === 0 || $katIdx === 1 || $katIdx === 4) ? 'Transfer' : 'Tunai',
                'no_bukti'          => "BUKT-IN-{$tahun}-{$i}",
                'file_bukti'        => null,
                'tahun_ajaran'      => '2026/2027',
                'semester'          => $bulan <= 6 ? 'Genap' : 'Ganjil',
                'status'            => 'Verified',
                'created_by'        => $staffUsers['tu'],
                'verified_by'       => $staffUsers['kepsek'],
                'verified_at'       => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }
        DB::table('transaksi_pemasukan')->insert($pemasukanRows);
        $idPemasukanAwal = DB::getPdo()->lastInsertId();

        // Transaksi Pengeluaran (18 transaksi)
        $pengeluaranRows = [];
        for ($i = 0; $i < 18; $i++) {
            $bulan = 1 + ($i % 8);
            $tahun = now()->year;
            $katIdx = $i % count($katKeluarIds);
            $jumlah = match($katIdx) {
                0 => rand(40000000, 65000000),   // Gaji
                1 => rand(3000000, 15000000),    // Operasional
                2 => rand(2000000, 25000000),    // Sarana
                3 => rand(1000000, 10000000),    // Pembelajaran
                default => rand(1500000, 8000000), // Kualitas
            };
            $fromBos = ($i % 3 === 0 && $katIdx !== 0); // beberapa item pakai BOS
            $pengeluaranRows[] = [
                'school_id'         => $schoolId,
                'no_transaksi'      => "OUT/SM1S/{$tahun}/" . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'tanggal_transaksi' => "{$tahun}-" . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) . '-' . (10 + ($i % 18)),
                'id_kategori'       => $katKeluarIds[$katIdx],
                'keterangan'        => match($katIdx) {
                    0 => 'Honor staf honorer bulan ' . ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus'][$bulan-1],
                    1 => 'Pembelian ATK, perlengkapan kebersihan, dan konsumsi rapat guru.',
                    2 => 'Perbaikan genting bocor ruang kelas XII dan pengecatan ulang dinding koridor.',
                    3 => 'Cetak modul belajar, soal ulangan, dan perlengkapan praktik siswa.',
                    default => 'Biaya workshop MGMP dan diklat sertifikasi guru mapel.',
                },
                'tujuan'            => ['PT. Abadi Makmur', 'PDAM Kota', 'PLN Cabang', 'Toko Alat Tulis', 'Panitia MGMP'][$i % 5],
                'jumlah'            => $jumlah,
                'metode_pembayaran' => ($jumlah > 5000000) ? 'Transfer' : 'Tunai',
                'no_bukti'          => "BUKT-OUT-{$tahun}-{$i}",
                'file_bukti'        => null,
                'tahun_ajaran'      => '2026/2027',
                'semester'          => $bulan <= 6 ? 'Genap' : 'Ganjil',
                'is_from_bos'       => $fromBos,
                'status'            => 'Paid',
                'created_by'        => $staffUsers['tu'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }
        DB::table('transaksi_pengeluaran')->insert($pengeluaranRows);
        $idPengeluaranAwal = DB::getPdo()->lastInsertId();

        return [
            'kat_masuk_ids'   => $katMasukIds,
            'kat_keluar_ids'  => $katKeluarIds,
            'id_pemasukan_awal'  => (int) $idPemasukanAwal,
            'id_pengeluaran_awal'=> (int) $idPengeluaranAwal,
            'total_pemasukan'   => count($pemasukanRows),
            'total_pengeluaran' => count($pengeluaranRows),
        ];
    }

    // =====================================================================
    // 20. BOS, PENGAJUAN ANGGARAN, REALISASI
    // =====================================================================
    private function seedBos(string $schoolId, array $staffUsers, array $keuangan): void
    {
        // 2 record Dana BOS
        $idBosList = [];
        for ($i = 0; $i < 2; $i++) {
            DB::table('dana_bos')->insert([
                'school_id'       => $schoolId,
                'tahun_ajaran'    => '2026/2027',
                'semester'        => $i === 0 ? 'Ganjil' : 'Genap',
                'triwulan'        => (string) ($i + 1),
                'jumlah_diterima' => $i === 0 ? 180500000 : 178750000,
                'tanggal_terima'  => $i === 0 ? '2026-08-05' : '2027-02-10',
                'keterangan'      => "Pencairan BOS Reguler Tahun Ajaran 2026/2027 Tahap " . ($i + 1) . " sesuai SK Dinas Pendidikan.",
                'created_at'      => now(),
            ]);
            $idBosList[] = DB::getPdo()->lastInsertId();
        }

        // 3 pengajuan anggaran
        for ($i = 0; $i < 3; $i++) {
            DB::table('pengajuan_anggaran')->insert([
                'school_id'             => $schoolId,
                'judul'                 => ['Pengadaan Laptop Lab Komputer', 'Perbaikan Atap Gedung A', 'Biaya Study Tour ke Jakarta'][ $i ],
                'kategori_pengeluaran'  => $keuangan['kat_keluar_ids'][ $i % count($keuangan['kat_keluar_ids']) ],
                'jumlah_diajukan'       => [85000000, 45000000, 32000000][$i],
                'tanggal_pengajuan'     => now()->subDays(15 + $i * 3)->toDateString(),
                'keperluan'             => ['Pengadaan 15 unit laptop baru untuk praktik siswa kelas XI RPL.',
                                            'Perbaikan 120 m2 genting bocor dan pengecatan plafon gedung A.',
                                            'Biaya transportasi, konsumsi, tiket wisata edukasi 120 siswa kelas XII.'
                                            ][$i],
                'status'                => ['Approved', 'Approved', 'Pending'][$i],
                'catatan_reviewer'      => $i === 2 ? 'Perlu perhitungan ulang biaya akomodasi. Akan direview minggu depan.' : 'Disetujui sesuai RAPBS tahun berjalan.',
                'reviewed_by'           => $staffUsers['kepsek'],
                'reviewed_at'           => now()->subDays(5 + $i * 2),
                'created_by'            => $staffUsers['tu'],
                'created_at'            => now(),
            ]);
        }

        // Realisasi: hubungkan 1 record BOS ke 2 pengeluaran is_from_bos
        $pengeluaranBosIds = collect(range($keuangan['id_pengeluaran_awal'] - (count($keuangan['kat_keluar_ids']) - 1), $keuangan['id_pengeluaran_awal']))
            ->take(2)->values();
        // catatan: lastInsertId bersifat per koneksi; karena batch insert maka ini berurutan. Kita gunakan pengeluaran dengan indeks terbaru.
        // Karena query cukup rumit, kita ambil 2 pengeluaran pertama via query sederhana.
        $pengeluaranBosIds = DB::table('transaksi_pengeluaran')
            ->where('school_id', $schoolId)
            ->where('is_from_bos', true)
            ->orderBy('id_pengeluaran')
            ->limit(2)
            ->pluck('id_pengeluaran')
            ->all();
        $idBosPertama = DB::table('dana_bos')->where('school_id', $schoolId)->orderBy('id_bos')->limit(1)->value('id_bos');

        $realisasiRows = [];
        foreach ($pengeluaranBosIds as $idPengeluaran) {
            $realisasiRows[] = [
                'id'             => Str::uuid()->toString(),
                'school_id'      => $schoolId,
                'id_bos'         => $idBosPertama,
                'id_pengeluaran' => $idPengeluaran,
                'created_at'     => now(),
            ];
        }
        if (! empty($realisasiRows)) DB::table('realisasi_bos')->insert($realisasiRows);
    }

    // =====================================================================
    // 21. AUDIT KEUANGAN & ACTIVITY LOGS
    // =====================================================================
    private function seedAuditAndActivity(string $schoolId, array $staffUsers): void
    {
        // Audit Keuangan (10 sample)
        $auditRows = [];
        for ($i = 0; $i < 10; $i++) {
            $action = ['CREATE', 'UPDATE', 'APPROVE'][ $i % 3 ];
            $tabel  = ['transaksi_pemasukan', 'transaksi_pengeluaran', 'dana_bos', 'pengajuan_anggaran'][ $i % 4 ];
            $auditRows[] = [
                'id'         => Str::uuid()->toString(),
                'school_id'  => $schoolId,
                'tabel'      => $tabel,
                'id_record'  => rand(1, 20),
                'aksi'       => $action,
                'data_lama'  => $action !== 'CREATE' ? json_encode(['jumlah_sebelumnya' => rand(1000000, 5000000), 'status_sebelumnya' => 'Pending']) : null,
                'data_baru'  => json_encode(['jumlah' => rand(2000000, 15000000), 'status' => $action === 'APPROVE' ? 'Verified' : 'Pending']),
                'user_id'    => [$staffUsers['tu'], $staffUsers['kepsek']][$i % 2],
                'ip_address' => '10.20.30.' . (2 + $i),
                'created_at' => now()->subDays($i + 1),
            ];
        }
        DB::table('audit_keuangan')->insert($auditRows);

        // Activity Logs (20 sample)
        $actRows = [];
        $activities = [
            ['Login Sistem',               'Berhasil login melalui halaman portal sekolah.'],
            ['Tambah Data Siswa',          'Menambahkan data siswa baru melalui panel TU.'],
            ['Update Data Guru',           'Memperbarui riwayat pendidikan dan NIP guru terkait.'],
            ['Proses SPP Siswa',           'Mencatat pemasukan SPP siswa kelas XI bulan berjalan.'],
            ['Cetak Laporan Keuangan',     'Mencetak laporan realisasi anggaran bulan ini untuk dievaluasi.'],
            ['Tetapkan Jadwal Pelajaran',  'Menyusun dan menyimpan jadwal pelajaran semester Ganjil.'],
            ['Input Nilai Siswa',          'Menginput nilai ulangan harian Matematika kelas X.'],
            ['Approve Pengajuan Anggaran', 'Menyetujui pengajuan biaya perbaikan gedung A.'],
            ['Buat Pengumuman',            'Membuat pengumuman tentang libur hari raya nasional.'],
            ['Booking Laboratorium',       'Guru Biologi membooking Lab IPA untuk praktik fotosintesis.'],
            ['Rekap Kehadiran',            'Merekap kehadiran guru dan staf minggu ini.'],
            ['Jurnal Mengajar',            'Mengisi jurnal mengajar Bahasa Indonesia di kelas XII IPA 1.'],
            ['Catatan Kesiswaan',          'Menambahkan catatan kesiswaan terlambat pada siswa terkait.'],
            ['Sesi BK',                    'Mencatat hasil sesi konseling dengan siswa kelas XII IPS.'],
            ['Update Inventaris',          'Pengecekan inventaris laptop Lab Komputer, 30 unit kondisi baik.'],
            ['Laporan Toolman',            'Membuat laporan inventaris alat praktik Fisika mingguan.'],
            ['Kirim Notifikasi',           'Mengirim notifikasi ke seluruh wali kelas tentang Rapat Orang Tua.'],
            ['Ujian Tengah Semester',      'Menerbitkan jadwal dan soal UTS untuk seluruh tingkatan.'],
            ['Rapor Semester',             'Finalisasi nilai rapor semester Ganjil dan pembagian raport.'],
            ['Kelulusan Wisuda',           'Persiapan rapat pleno kelulusan kelas XII angkatan 2026/2027.'],
        ];
        $allUserIds = array_values($staffUsers);
        for ($i = 0; $i < 20; $i++) {
            [$activity, $desc] = $activities[$i];
            $actRows[] = [
                'id'          => Str::uuid()->toString(),
                'school_id'   => $schoolId,
                'user_id'     => $allUserIds[$i % count($allUserIds)],
                'activity'    => $activity,
                'description' => $desc,
                'ip_address'  => '10.20.30.' . (10 + $i),
                'user_agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/128.0',
                'created_at'  => now()->subMinutes($i * 150),
            ];
        }
        DB::table('activity_logs')->insert($actRows);
    }

    // =====================================================================
    // 22. TOOLMAN REPORTS
    // =====================================================================
    private function seedToolmanReports(string $schoolId, string $toolmanStaffId): void
    {
        $rows = [];
        $reports = [
            'Pengecekan seluruh alat praktik Fisika di Lab IPA. Terdapat 1 multimeter rusak, dicadangkan untuk perbaikan.',
            'Penyusunan inventaris Lab Komputer: 30 laptop, 1 server, 2 printer. Semua dalam kondisi baik.',
            'Perawatan rutin mikroskop di Lab Biologi. 5 lensa okuler dibersihkan, 2 lampu LED diganti.',
            'Pengisian bahan praktik Kimia: HCl, NaOH, CuSO4, dan aquades. Semua tersedia sesuai daftar kebutuhan.',
        ];
        for ($i = 0; $i < count($reports); $i++) {
            $rows[] = [
                'id'         => Str::uuid()->toString(),
                'school_id'  => $schoolId,
                'staff_id'   => $toolmanStaffId,
                'date'       => now()->subDays(($i + 1) * 5)->toDateString(),
                'content'    => $reports[$i],
                'created_at' => now(),
            ];
        }
        DB::table('toolman_reports')->insert($rows);
    }

    // =====================================================================
    // 23. SUBSCRIPTIONS HISTORY
    // =====================================================================
    private function seedSubscriptions(string $schoolId): void
    {
        $rows = [
            ['trial', now()->subMonths(8)->toDateString(), now()->subMonths(6)->toDateString(),       0,       null,            'expired',   'Masa percobaan saat onboarding.'],
            ['basic', now()->subMonths(6)->toDateString(), now()->subMonths(2)->toDateString(), 4500000, 'INV-EDZ-20260125', 'expired',   'Aktivasi paket Basic 6 bulan setelah masa trial.'],
            ['pro',   now()->subMonths(2)->toDateString(), now()->addYears(2)->toDateString(), 25000000,'INV-EDZ-20260601', 'active',    'Upgrade ke paket PRO tahunan dengan kuota 1000 user.'],
        ];

        $dbRows = [];
        foreach ($rows as [$plan, $start, $expire, $amount, $invoice, $status, $note]) {
            $dbRows[] = [
                'id'         => Str::uuid()->toString(),
                'school_id'  => $schoolId,
                'plan'       => $plan,
                'started_at' => $start,
                'expired_at' => $expire,
                'amount'     => $amount,
                'invoice_no' => $invoice,
                'status'     => $status,
                'note'       => $note,
                'created_at' => now(),
            ];
        }
        DB::table('subscriptions')->insert($dbRows);
    }
}
