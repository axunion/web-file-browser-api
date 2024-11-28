<?php

class DirectoryItem
{
    public string $type;
    public string $name;
    public ?int $size;

    public function __construct(string $type, string $name, ?int $size = null)
    {
        $this->type = $type;
        $this->name = $name;
        $this->size = $size;
    }
}

/**
 * Retrieve the specified directory structure
 *
 * @param string $path
 * @return DirectoryItem[]
 */
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
            $result[] = new DirectoryItem('directory', $item);
        } elseif (is_file($full_path)) {
            $result[] = new DirectoryItem('file', $item, filesize($full_path));
        }
    }

    return $result;
}
