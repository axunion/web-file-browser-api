<?php

declare(strict_types=1);

enum ItemType: string
{
    case FILE = 'file';
    case DIRECTORY = 'directory';
}

/**
 * Represents a single file or directory item.
 */
class DirectoryItem
{
    /**
     * @param ItemType $type Type of the item (file or directory)
     * @param string   $name Name of the file or directory
     */
    public function __construct(
        public ItemType $type,
        public string $name
    ) {}
}

/**
 * Scans directories and returns structured listings.
 */
final class DirectoryScanner
{
    /**
     * Scan a directory and return its immediate contents as DirectoryItem objects.
     *
     * @param string $path Absolute or relative path to the target directory.
     * @return DirectoryItem[]
     * @throws DirectoryException If the directory is invalid or not readable.
     */
    public static function scan(string $path): array
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

        /** @var SplFileInfo $info */
        foreach ($iterator as $info) {
            // Skip symlinks completely
            if ($info->isLink()) {
                continue;
            }

            // Skip hidden entries (e.g. .seq_lock, .gitkeep)
            if (str_starts_with($info->getFilename(), '.')) {
                continue;
            }

            if (!$info->isDir() && !$info->isFile()) {
                continue;
            }

            $items[] = new DirectoryItem(
                $info->isDir() ? ItemType::DIRECTORY : ItemType::FILE,
                $info->getFilename()
            );
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
}
