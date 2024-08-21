<?php

function get_directory_structure($path): array
{
    if (!is_dir($path)) {
        throw new Exception("Directory not found: $path");
    }

    $items = @scandir($path);

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
                'contents' => get_directory_structure($full_path)
            ];
        } elseif (is_file($full_path)) {
            $result[] = [
                'type' => 'file',
                'name' => $item,
            ];
        } elseif (is_link($full_path)) {
            $result[] = [
                'type' => 'link',
                'name' => $item,
            ];
        }
    }

    return $result;
}
