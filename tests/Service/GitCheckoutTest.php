<?php

declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\TestCase;
use Rodziu\Git\TestsHelper;

class GitCheckoutTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        TestsHelper::restoreTestRepository();
    }

    public function testCheckout(): void
    {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $gitCheckout = new GitCheckout($manager);

        // When
        $gitCheckout->checkout('detachedBranch');

        // Then
        $files = [
            'fileOnBranch',
            'secondFileOnBranch',
            'file2',
            'file',
            'fileOnDetachedBranch',
        ];

        foreach ($files as $file) {
            self::assertFileExists(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.$file);
        }

        $headContents = file_get_contents($manager->resolvePath('HEAD'));
        self::assertEquals('ref: refs/heads/detachedBranch', $headContents);
    }
}
