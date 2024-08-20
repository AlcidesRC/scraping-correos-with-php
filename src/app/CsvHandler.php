<?php

declare(strict_types=1);

namespace App;

use Exception;
use Generator;
use RuntimeException;

final class CsvHandler
{
    private const DELIMITER = ';';

    public static function read(string $filename): Generator
    {
        $file = fopen($filename, 'r');

        if ($file === false) {
            throw new RuntimeException('File cannot be opened.');
        }

        while (($row = fgetcsv($file, 0, self::DELIMITER)) !== false) {
            yield $row;
        }

        fclose($file);
    }

    /**
     * @param array<int, mixed> $data
     * @param array<int|string, bool|float|int|string|null>|null $headers
     */
    public static function write(string $filename, array $data, ?array $headers = []): bool
    {
        $rows = static function (array $data): Generator {
            foreach ($data as $row) {
                yield $row;
            }
        };

        try {
            $file = fopen($filename, 'w+');

            if ($file === false) {
                throw new RuntimeException('File cannot be created.');
            }

            if (is_array($headers) && count($headers)) {
                fputcsv($file, $headers, self::DELIMITER);
            }

            foreach ($rows($data) as $row) {
                fputcsv($file, $row, self::DELIMITER);
            }

            fclose($file);

            $status = true;
        } catch (RuntimeException $e) {
            $status = false;
        }

        return $status;
    }
}
