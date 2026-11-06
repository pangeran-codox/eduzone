<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Baca dan tulis file Excel untuk fitur impor. Memakai PhpSpreadsheet (dependensi maatwebsite/excel) langsung agar:
 * - nilai sel dibaca MENTAH (tanggal = nomor seri, angka = angka), sehingga normalisasi ada di BaseImporter;
 * - nomor baris Excel yang dilaporkan ke TU akurat (baris kosong tetap dihitung).
 * Semua sel yang ditulis berupa teks eksplisit: NISN/NIP/kata sandi berawalan 0 tidak boleh berubah jadi angka.
 */
class ImportSpreadsheet
{
    public const MAX_ROWS = 1000;

    /**
     * @return array{rows: array<int, array<string, mixed>>, missing: list<string>, unknown: list<string>}
     *         rows: nomor baris Excel => nilai per kunci kolom (baris kosong dilewati)
     */
    public static function read(string $absolutePath, BaseImporter $importer): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        try {
            $spreadsheet = $reader->load($absolutePath);
        } catch (\Throwable $e) {
            throw new RuntimeException('File tidak dapat dibaca sebagai .xlsx. Pastikan memakai template dari menu Impor Data.');
        }

        $sheet = $spreadsheet->getSheetByName('Data') ?? $spreadsheet->getSheet(0);
        $raw = $sheet->toArray(null, true, false, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($raw === [] || ! isset($raw[0])) {
            throw new RuntimeException('File kosong.');
        }

        $known = array_keys($importer->columns());
        $columnIndex = [];
        $unknown = [];

        foreach ($raw[0] as $index => $header) {
            $key = self::normalizeHeader($header);

            if ($key === '') {
                continue;
            }

            if (in_array($key, $known, true)) {
                $columnIndex[$index] = $key;
            } else {
                $unknown[] = (string) $header;
            }
        }

        $missing = array_values(array_diff($importer->requiredKeys(), array_values($columnIndex)));

        $rows = [];
        $count = count($raw);

        for ($i = 1; $i < $count; $i++) {
            $row = [];
            $filled = false;

            foreach ($columnIndex as $index => $key) {
                $value = $raw[$i][$index] ?? null;

                if (is_string($value)) {
                    $value = trim($value);
                    $value = $value === '' ? null : $value;
                }

                if ($value !== null) {
                    $filled = true;
                }

                $row[$key] = $value;
            }

            if (! $filled) {
                continue;
            }

            $rows[$i + 1] = $row;

            if (count($rows) > self::MAX_ROWS) {
                throw new RuntimeException('Maksimal ' . self::MAX_ROWS . ' baris data per unggahan. Pecah file menjadi beberapa bagian.');
            }
        }

        return ['rows' => $rows, 'missing' => $missing, 'unknown' => $unknown];
    }

    public static function normalizeHeader(mixed $header): string
    {
        $h = mb_strtolower(trim((string) ($header ?? '')));
        $h = str_replace('*', '', $h);
        $h = preg_replace('/[^a-z0-9]+/', '_', $h) ?? '';

        return trim($h, '_');
    }

    /** Buat file template sementara dan kembalikan path-nya (hapus setelah dikirim). */
    public static function template(BaseImporter $importer, string $schoolId): string
    {
        $importer->configure($schoolId);

        $columns = $importer->columns();
        $keys = array_keys($columns);
        $lists = $importer->lists($schoolId);
        $lastRow = self::MAX_ROWS + 1;

        $spreadsheet = new Spreadsheet();
        $data = $spreadsheet->getActiveSheet();
        $data->setTitle('Data');

        // Lembar Daftar (nilai pilihan dinamis, mis. kelas aktif). Dibuat dulu agar rujukan dropdown valid.
        $listRanges = [];
        if ($lists !== []) {
            $listSheet = $spreadsheet->createSheet();
            $listSheet->setTitle('Daftar');
            $col = 1;

            foreach ($lists as $name => $values) {
                $letter = Coordinate::stringFromColumnIndex($col);
                $listSheet->setCellValueExplicit("{$letter}1", $name, DataType::TYPE_STRING);
                $listSheet->getColumnDimension($letter)->setWidth(24);

                foreach (array_values($values) as $i => $value) {
                    $listSheet->setCellValueExplicit($letter . ($i + 2), (string) $value, DataType::TYPE_STRING);
                }

                $listRanges[$name] = "Daftar!\${$letter}\$2:\${$letter}\$" . max(2, count($values) + 1);
                $col++;
            }
        }

        foreach ($keys as $i => $key) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $meta = $columns[$key];

            $data->setCellValueExplicit("{$letter}1", $key, DataType::TYPE_STRING);
            $data->getColumnDimension($letter)->setWidth($meta['width'] ?? 16);
            $data->getStyle("{$letter}2:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

            $header = $data->getStyle("{$letter}1");
            $header->getFont()->setBold(true)->getColor()->setARGB($meta['required'] ? 'FFFFFFFF' : 'FF1B3A34');
            $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($meta['required'] ? 'FF1B3A34' : 'FFE8E4D8');

            $formula = null;
            if (! empty($meta['options'])) {
                $formula = '"' . implode(',', $meta['options']) . '"';
            } elseif (! empty($meta['list']) && isset($listRanges[$meta['list']])) {
                $formula = '=' . $listRanges[$meta['list']];
            }

            if ($formula !== null) {
                for ($r = 2; $r <= $lastRow; $r++) {
                    $validation = $data->getCell("{$letter}{$r}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Nilai tidak valid');
                    $validation->setError('Pilih salah satu nilai dari daftar.');
                    $validation->setFormula1($formula);
                }
            }
        }

        $data->freezePane('A2');

        // Lembar Petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->getColumnDimension('A')->setWidth(22);
        $guide->getColumnDimension('B')->setWidth(10);
        $guide->getColumnDimension('C')->setWidth(70);
        $guide->getColumnDimension('D')->setWidth(28);

        $lines = [
            ['Template impor ' . $importer->label() . ' EduZone'],
            [$importer->description()],
            [''],
            ['Cara memakai'],
            ["1. Isi lembar 'Data' mulai baris 2. Jangan mengubah, memindah, atau menghapus baris judul (baris 1)."],
            ['2. Kolom berjudul hijau tua wajib diisi. Kolom lain boleh dikosongkan.'],
            ['3. Seluruh kolom sudah berformat Teks supaya angka 0 di depan (NISN, telepon) tidak hilang. Jangan mengubah formatnya.'],
            ['4. Tanggal ditulis DD/MM/YYYY atau YYYY-MM-DD.'],
            ['5. Maksimal ' . self::MAX_ROWS . ' baris data per unggahan.'],
            ["6. Unggah di menu Impor Data. Sistem memeriksa dulu tanpa menyimpan apa pun, lalu menampilkan hasilnya untuk Anda setujui."],
            [''],
            ['Kolom', 'Wajib', 'Keterangan', 'Contoh'],
        ];

        foreach ($keys as $key) {
            $lines[] = [$key, $columns[$key]['required'] ? 'ya' : 'tidak', $columns[$key]['hint'], $columns[$key]['example']];
        }

        self::writeRows($guide, $lines);
        $guide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $guide->getStyle('A4')->getFont()->setBold(true);
        $guide->getStyle('A12:D12')->getFont()->setBold(true);

        // Urutan lembar: Data, Petunjuk, Daftar (Daftar dibuat lebih dulu agar rujukan dropdown valid).
        if ($spreadsheet->sheetNameExists('Daftar')) {
            $spreadsheet->setIndexByName('Petunjuk', 1);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return self::save($spreadsheet, 'tpl_');
    }

    /**
     * Laporan galat hasil validasi.
     *
     * @param list<array{line: int, column: string, message: string}> $errors
     */
    public static function errorReport(array $errors, bool $truncated): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Galat');

        $rows = [['Baris Excel', 'Kolom', 'Masalah']];
        foreach ($errors as $error) {
            $rows[] = [(string) $error['line'], $error['column'], $error['message']];
        }
        if ($truncated) {
            $rows[] = ['', '', 'Daftar dipotong. Perbaiki galat di atas, unggah ulang, lalu periksa lagi untuk melihat sisanya.'];
        }

        self::writeRows($sheet, $rows);
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(90);
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        return self::save($spreadsheet, 'err_');
    }

    /** Ubah CSV akun sementara (peran,nama,kelas,username,password) menjadi file .xlsx di $destination. */
    public static function credentialsReport(string $csvPath, string $destination): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Akun');

        $rows = [['Peran', 'Nama', 'Kelas', 'Username', 'Kata sandi awal']];
        $handle = fopen($csvPath, 'r');

        if ($handle !== false) {
            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null] || $line === []) {
                    continue;
                }
                $rows[] = array_map(fn ($v) => (string) $v, $line);
            }
            fclose($handle);
        }

        self::writeRows($sheet, $rows);
        foreach (['A' => 10, 'B' => 30, 'C' => 18, 'D' => 22, 'E' => 18] as $letter => $width) {
            $sheet->getColumnDimension($letter)->setWidth($width);
        }
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        (new Xlsx($spreadsheet))->save($destination);
    }

    /** @param list<list<string>> $rows */
    private static function writeRows($sheet, array $rows): void
    {
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($c + 1) . ($r + 1),
                    (string) $value,
                    DataType::TYPE_STRING
                );
            }
        }
    }

    private static function save(Spreadsheet $spreadsheet, string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara.');
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
