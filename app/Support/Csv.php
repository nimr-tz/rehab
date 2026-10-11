<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV downloads for staff, safe to open in Excel. */
class Csv
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // A byte order mark, so Excel reads names with accents correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, array_map(self::cell(...), $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Text that a spreadsheet would run as a formula (=, +, -, @, tab or
     * carriage return first) is prefixed with an apostrophe, so it stays text.
     */
    public static function cell(mixed $value): string
    {
        $value = match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Yes' : 'No',
            default => (string) $value,
        };

        return preg_match('/^[=+\-@\t\r]/', $value) && ! is_numeric($value) ? "'".$value : $value;
    }
}
