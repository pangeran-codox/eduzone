<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu QR Absensi</title>
    <style>
        * { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; }
        body { margin: 0; padding: 20px; background: #fff; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button {
            padding: 8px 16px; background: #1B3A34; color: #fff;
            border: none; border-radius: 6px; cursor: pointer; font-size: 13px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .card {
            border: 1px dashed #999;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            page-break-inside: avoid;
        }
        .card svg { width: 140px; height: 140px; }
        .card .nama { font-weight: 600; font-size: 13px; margin-top: 8px; }
        .card .hint { font-size: 10px; color: #888; margin-top: 2px; }
        @media print {
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Cetak</button>
    </div>

    <div class="grid">
        @foreach($batch as $item)
            <div class="card">
                {!! $item['qr_svg'] !!}
                <div class="nama">{{ $item['nama'] }}</div>
                <div class="hint">Kartu Absensi EduZone</div>
            </div>
        @endforeach
    </div>
</body>
</html>