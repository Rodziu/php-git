<?php
declare(strict_types=1);

namespace Rodziu\Git\Util;

abstract class FileSystemUtil
{

    public static function mkdirIfNotExists(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0755, true);
    }

    /**
     * @param string[] $ignoredItems
     */
    public static function cleanDir(string $path, array $ignoredItems): void
    {
        $iterator = new \DirectoryIterator($path);
        /** @var \DirectoryIterator $file */
        foreach ($iterator as $file) {
            if (
                $file->isDot()
                || in_array($file->getFilename(), $ignoredItems)
            ) {
                continue;
            }

            if ($file->isFile()) {
                unlink($file->getRealPath());
            } else {
                FileSystemUtil::rmdirRecursive($file->getRealPath());
            }
        }
    }

    public static function rmdirRecursive(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($path);
    }
}
