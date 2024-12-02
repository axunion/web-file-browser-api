<?php

enum ItemType: string
{
    case FILE = 'file';
    case DIRECTORY = 'directory';
}

class DirectoryItem
{
    public ItemType $type;
    public string $name;
    public ?int $size;

    /**
     * Constructor for DirectoryItem
     *
     * @param string $name File or directory name
     * @param int|null $size File size in bytes (null for directories)
     * @param ItemType $type Type of the item (file or directory)
     */
    public function __construct(ItemType $type, string $name, ?int $size)
    {
        $this->type = $type;
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
            $result[] = new DirectoryItem(ItemType::DIRECTORY, $item, null);
        } elseif (is_file($full_path)) {
            $result[] = new DirectoryItem(ItemType::FILE, $item, filesize($full_path));
        }
    }

    usort($result, function (DirectoryItem $a, DirectoryItem $b) {
        if ($a->type === ItemType::DIRECTORY && $b->type === ItemType::FILE) {
            return -1;
        } elseif ($a->type === ItemType::FILE && $b->type === ItemType::DIRECTORY) {
            return 1;
        }

        return strcmp($a->name, $b->name);
    });

    return $result;
}
