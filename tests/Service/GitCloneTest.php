<?php

declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\TestCase;
use Rodziu\Git\TestsHelper;

class GitCloneTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        TestsHelper::restoreTestRepository();
    }

    public function testCloneRepository(): void
    {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $remoteConfig = $manager->getConfig()
            ->getSectionVariables('remote', 'origin');
        $url = $remoteConfig['url'];

        // When
        $destination = TestsHelper::GIT_TEST_PATH;
        GitClone::cloneRepository(
            $url,
            $destination,
        );

        // Then
        $destination .= DIRECTORY_SEPARATOR.'git-test';
        self::assertDirectoryExists($destination);

        $dirs = [
            'test/dir_test',
            'test',
            '.git/objects/pack',
            '.git/objects',
            '.git/refs/tags',
            '.git/refs/remotes',
            '.git/refs/heads',
            '.git/refs',
            '.git',
        ];
        $files = [
            'pack.pack',
            '.git-changelog',
            'pack.idx',
            'file3',
            'fileOnBranch',
            'secondFileOnBranch',
            'file2',
            'file',
            'test/dir_test/file777',
            'test/file_test',
            '.git/config',
            '.git/objects/pack/pack-60a8fe114ec270c0922f96aa7a082f611a12fca1.pack',
            '.git/packed-refs',
            '.git/HEAD',
        ];

        foreach ($dirs as $dir) {
            self::assertDirectoryExists($destination.DIRECTORY_SEPARATOR.$dir);
        }

        foreach ($files as $file) {
            self::assertFileExists($destination.DIRECTORY_SEPARATOR.$file);
        }
    }
}
