<?php
//a function to read .env variables that are needed for connection
function load_env(string $filePath): void
{
    if (!file_exists($filePath)) {
        return;
    }

    $rows = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($rows as $row) {
        $row = trim($row);

        //skip comments
        if ($row === '' || str_starts_with($row, '#')) {
            continue;
        }

        //to separate key and value
        $parts = explode('=', $row, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key   = trim($parts[0]);
        $value = trim($parts[1]);

        //remove "" if any
        $value = trim($value, "\"'");

        $_ENV[$key] = $value;
    }
}
