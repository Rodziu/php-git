<?php

namespace Rodziu\Git;

abstract class TestsHelper
{
    const GIT_TEST_PATH = __DIR__.DIRECTORY_SEPARATOR.'gitTest';

    public static function createZip(): void
    {
        $zip = new \ZipArchive();
        $zip->open(self::GIT_TEST_PATH.'.zip', \ZipArchive::CREATE);
        $path = self::GIT_TEST_PATH;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $i) {
            /** @var \SplFileInfo $i */
            $zip->addFile($i->getPathname(), str_replace(self::GIT_TEST_PATH, '', $i->getPathname()));
        }
        $zip->close();
    }

    public static function unpackZip(): void
    {
        if (!is_dir(self::GIT_TEST_PATH)) {
            $zip = new \ZipArchive();
            if ($zip->open(self::GIT_TEST_PATH.'.zip')) {
                @mkdir(self::GIT_TEST_PATH);
                $zip->extractTo(self::GIT_TEST_PATH);
                $zip->close();
            }
        }
    }
}

TestsHelper::unpackZip();
require dirname(__DIR__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
