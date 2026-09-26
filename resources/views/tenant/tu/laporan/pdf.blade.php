<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { margin-bottom: 2px; }
        p.sub { color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>{{ $schoolName }}</h2>
    <p class="sub">Rekap Absensi {{ $personType === 'teacher' ? 'Guru' : 'Siswa' }} — {{ $startDate }} s/d {{ $endDate }}</p>

    <table>
        <thead>
            <tr>
                <th>Nama</th><th>Hadir</th><th>Terlambat</th><th>Sakit</th><th>Izin</th><th>Alpa</th><th>Tidak Tercatat</th><th>% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['nama'] }}</td>
                    <td>{{ $r['counts']['Hadir'] }}</td>
                    <td>{{ $r['counts']['Terlambat'] }}</td>
                    <td>{{ $r['counts']['Sakit'] }}</td>
                    <td>{{ $r['counts']['Izin'] }}</td>
                    <td>{{ $r['counts']['Alpa'] }}</td>
                    <td>{{ $r['tidak_tercatat'] }}</td>
                    <td>{{ $r['percentage'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>