<?php

declare(strict_types=1);

enum ItemType: string
{
    case FILE = 'file';
    case DIRECTORY = 'directory';
}

class DirectoryException extends Exception {}

/**
 * Represents a single file or directory item.
 */
class DirectoryItem
{
    /** @var ItemType Type of this item */
    public ItemType $type;

    /** @var string Name of the file or directory */
    public string $name;

    /** @var int|null Size in bytes for files, null for directories */
    public ?int $size;

    /**
     * @param ItemType   $type Type of the item (file or directory)
     * @param string     $name File or directory name
     * @param int|null   $size File size in bytes (must be null for directories)
     * @throws InvalidArgumentException
     */
    public function __construct(ItemType $type, string $name, ?int $size = null)
    {
        if ($type === ItemType::FILE && $size === null) {
            throw new InvalidArgumentException('File size must be specified for files.');
        }

        if ($type === ItemType::DIRECTORY && $size !== null) {
            throw new InvalidArgumentException('Size for directories must be null.');
        }

        $this->type = $type;
        $this->name = $name;
        $this->size = $size;
    }
}

/**
 * Scan a directory and return its immediate contents as DirectoryItem objects.
 *
 * @param string $path Absolute or relative path to the target directory.
 * @param bool   $recursive Whether to scan subdirectories recursively.
 * @return DirectoryItem[]
 * @throws DirectoryException If the directory is invalid or not readable.
 */
function getDirectoryStructure(string $path, bool $recursive = false): array
{
    if (!is_dir($path) || !is_readable($path)) {
        throw new DirectoryException("Directory not accessible: {$path}");
    }

    $entries = scandir($path);

    if ($entries === false) {
        throw new DirectoryException("Failed to scan directory: {$path}");
    }

    $result = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $fullPath = $path . DIRECTORY_SEPARATOR . $entry;

        if (is_dir($fullPath) && !is_link($fullPath)) {
            $item = new DirectoryItem(ItemType::DIRECTORY, $entry, null);
            $result[] = $item;

            if ($recursive) {
                $subItems = getDirectoryStructure($fullPath, true);
                $result = array_merge($result, $subItems);
            }
        } elseif (is_file($fullPath)) {
            $size = @filesize($fullPath);
            $result[] = new DirectoryItem(ItemType::FILE, $entry, $size === false ? 0 : $size);
        }
    }

    usort($result, function (DirectoryItem $a, DirectoryItem $b): int {
        if ($a->type !== $b->type) {
            return $a->type === ItemType::DIRECTORY ? -1 : 1;
        }

        return strcmp($a->name, $b->name);
    });

    return $result;
}
