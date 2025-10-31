<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/bootstrap.php';

validateMethod(['GET']);

try {
    $rawPath = getInput(INPUT_GET, 'path', '');
    $target = resolvePathWithTrash($rawPath);

    if (!is_dir($target) || !is_readable($target)) {
        throw new RuntimeException('Specified path is not a readable directory.');
    }

    $items = DirectoryScanner::scan($target);
    $list = array_map(fn($item) => [
        'type' => $item->type->value,
        'name' => $item->name,
    ], $items);

    sendSuccess(['list' => $list]);
} catch (Throwable $e) {
    handleError($e);
}
