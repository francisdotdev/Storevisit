<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Escape dangerous formula injection characters.
     */
    public static function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        // If cell starts with formula trigger characters (=, +, -, @, \t, \r)
        if (preg_match('/^[=\+\-@\t\r]/', $str)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Stream a CSV response with formula protection and UTF-8 BOM.
     *
     * @param string $filename
     * @param array $headers
     * @param iterable $rows A generator or array of row arrays
     * @return StreamedResponse
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // Write Header
            fputcsv($handle, array_map([self::class, 'sanitizeCell'], $headers));

            // Write Rows
            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'sanitizeCell'], (array) $row));
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}

