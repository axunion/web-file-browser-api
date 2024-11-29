<?php

class DirectoryItem
{
    public string $name;
    public ?int $size;

    /**
     * Constructor for DirectoryItem
     *
     * @param string $name File or directory name
     * @param int|null $size File size in bytes (null for directories)
     */
    public function __construct(string $name, ?int $size = null)
    {
        $this->name = $name;
        $this->size = $size;
    }
}

/**
 * Retrieve the structure of the specified directory
 *
 * @param string $path Path of the directory to scan
 * @return DirectoryItem[] Array of directory or file items
 * @throws Exception If the directory is invalid or cannot be scanned
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
            $result[] = new DirectoryItem($item, null);
        } elseif (is_file($full_path)) {
            $result[] = new DirectoryItem($item, filesize($full_path));
        }
    }

    return $result;
}
