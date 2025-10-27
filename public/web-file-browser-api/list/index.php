<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/web-file-browser-api/RequestHandler.php';
require_once __DIR__ . '/../../../src/web-file-browser-api/DirectoryScanner.php';

final class ListHandler extends RequestHandler
{
    protected array $allowedMethods = ['GET'];

    protected function process(): void
    {
        $rawPath = $this->getInput(INPUT_GET, 'path', '');
        $target = $this->resolvePathWithTrash($rawPath);

        if (!is_dir($target) || !is_readable($target)) {
            throw new RuntimeException('Specified path is not a readable directory.');
        }

        $items = DirectoryScanner::scan($target);
        $list = array_map(fn($item) => [
            'type' => $item->type->value,
            'name' => $item->name,
        ], $items);

        $this->sendSuccess(['list' => $list]);
    }
}

(new ListHandler())->handle();
