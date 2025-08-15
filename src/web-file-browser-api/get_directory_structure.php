<?php

declare(strict_types=1);

enum ItemType: string
{
    case FILE = 'file';
    case DIRECTORY = 'directory';
}

class DirectoryException extends \RuntimeException {}

/**
 * Represents a single file or directory item.
 */
class DirectoryItem
{
    public ItemType $type;
    public string $name;

    /**
     * @param ItemType $type Type of the item (file or directory)
     * @param string   $name Name of the file or directory
     */
    public function __construct(ItemType $type, string $name)
    {
        $this->type = $type;
        $this->name = $name;
    }
}

/**
 * Scan a directory and return its immediate contents as DirectoryItem objects.
 *
 * @param string $path Absolute or relative path to the target directory.
 * @param bool   $recursive Ignored; function remains non-recursive for compatibility.
 * @return DirectoryItem[]
 * @throws DirectoryException If the directory is invalid or not readable.
 */
function getDirectoryStructure(string $path, bool $recursive = false): array
{
    if (!is_dir($path) || !is_readable($path)) {
        throw new DirectoryException("Directory not accessible: {$path}");
    }

    try {
        $iterator = new FilesystemIterator(
            $path,
            FilesystemIterator::SKIP_DOTS
        );
    } catch (UnexpectedValueException $e) {
        throw new DirectoryException(
            "Failed to open directory: {$path}",
            0,
            $e
        );
    }

    $items = [];

    foreach ($iterator as $info) {
        // Skip symlinks completely
        if ($info->isLink()) {
            continue;
        }

        if ($info->isDir()) {
            $items[] = new DirectoryItem(
                ItemType::DIRECTORY,
                $info->getFilename()
            );
        } elseif ($info->isFile()) {
            $items[] = new DirectoryItem(
                ItemType::FILE,
                $info->getFilename()
            );
        }
    }

    // Sort: directories first, then files; natural, case-insensitive order
    usort($items, function (DirectoryItem $a, DirectoryItem $b): int {
        if ($a->type !== $b->type) {
            return $a->type === ItemType::DIRECTORY ? -1 : 1;
        }
        return strnatcasecmp($a->name, $b->name);
    });

    return $items;
}
