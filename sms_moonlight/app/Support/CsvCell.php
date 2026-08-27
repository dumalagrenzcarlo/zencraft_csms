<?php

declare(strict_types=1);

namespace App\Support;

final class CsvCell
{
    /**
     * Prevent spreadsheet applications from interpreting user-controlled
     * values as formulas when a CSV export is opened.
     *
     * @param  list<mixed>  $cells
     * @return list<mixed>
     */
    public static function row(array $cells): array
    {
        return array_map(static function (mixed $value): mixed {
            if (! is_string($value)) {
                return $value;
            }

            return preg_match('/^[\s\x{FEFF}]*[=+\-@]/u', $value) === 1
                ? "'".$value
                : $value;
        }, $cells);
    }
}
