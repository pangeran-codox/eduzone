<?php

namespace App\Support;

final class MailText
{
    /**
     * Netralkan Markdown dan spasi aneh pada teks buatan pengguna yang masuk ke email.
     * Email notifikasi Laravel diproses sebagai Markdown, jadi nama sekolah seperti
     * "[klik](https://situs-jahat)" akan tampil sebagai tautan aktif tanpa ini.
     */
    public static function plain(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        // Backslash-escape semua tanda baca ASCII (valid di CommonMark).
        return preg_replace('/([!-\/:-@\[-`{-~])/', '\\\\$1', $text) ?? '';
    }
}
