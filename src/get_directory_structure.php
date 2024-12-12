<?php

enum ItemType: string
{
    case FILE = 'file';
    case DIRECTORY = 'directory';
}

class DirectoryException extends Exception {}

class DirectoryItem
{
    public ItemType $type;
    public string $name;
    public ?int $size;

    /**
     * Constructor for DirectoryItem
     *
     * @param ItemType $type Type of the item (file or directory)
     * @param string $name File or directory name
     * @param int|null $size File size in bytes (null for directories)
     * @throws InvalidArgumentException If invalid size is provided for the item type
     */
    public function __construct(ItemType $type, string $name, ?int $size = null)
    {
        if ($type === ItemType::FILE && $size === null) {
            throw new InvalidArgumentException("File size must be specified for files.");
        }

        if ($type === ItemType::DIRECTORY && $size !== null) {
            throw new InvalidArgumentException("Size for directories must be null.");
        }

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
 * @throws DirectoryException If the directory is invalid or cannot be scanned
 */
function get_directory_structure(string $path): array
{
    if (!is_dir($path)) {
        throw new DirectoryException("Directory not found: $path");
    }

    $items = scandir($path);

    if ($items === false) {
        throw new DirectoryException("Unable to scan directory: $path");
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

    usort(
        $result,
        fn(DirectoryItem $a, DirectoryItem $b) =>
        $a->type === $b->type ? strcmp($a->name, $b->name) : ($a->type === ItemType::DIRECTORY ? -1 : 1)
    );

    return $result;
}
