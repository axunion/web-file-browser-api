<?php

function get_directory_structure($path)
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

        $fullPath = $path . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath) && !is_link($fullPath)) {
            $result[] = [
                'type' => 'directory',
                'name' => $item,
                'contents' => get_directory_structure($fullPath)
            ];
        } elseif (is_file($fullPath)) {
            $result[] = [
                'type' => 'file',
                'name' => $item,
            ];
        } elseif (is_link($fullPath)) {
            $result[] = [
                'type' => 'link',
                'name' => $item,
            ];
        }
    }

    return $result;
}
