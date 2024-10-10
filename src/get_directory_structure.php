<?php

function get_directory_structure(string $path): array
{
    if (!is_dir($path)) {
        throw new Exception("Directory not found: $path");
    }

    $items = scandir($path);

    if ($items === false) {
        throw new Exception("Unable to scan directory: $path");
    }

    $result = [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $full_path = $path . DIRECTORY_SEPARATOR . $item;

        if (is_dir($full_path) && !is_link($full_path)) {
            $result[] = [
                'type' => 'directory',
                'name' => $item,
            ];
        } elseif (is_file($full_path)) {
            $result[] = [
                'type' => 'file',
                'name' => $item,
                'size' => filesize($full_path),
            ];
        }
    }

    return $result;
}
