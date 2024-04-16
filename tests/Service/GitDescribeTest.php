<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\TestsHelper;

class GitDescribeTest extends TestCase
{
    #[DataProvider('describeDataProvider')]
    public function testDescribe(
        ?string $commitIsh,
        bool $all,
        bool $tags,
        ?string $expected,
        ?string $expectedError = null
    ): void {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $gitDescribe = new GitDescribe($manager);

        if ($expectedError !== null) {
            $this->expectExceptionMessage($expectedError);
        }

        // When
        $actual = $gitDescribe($commitIsh, $all, $tags);

        // Then
        if ($expected !== null) {
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @return array{hash: string|null, all: boolean, tags: boolean, expected: string|null, expectedError?: string|null}[]
     */
    public static function describeDataProvider(): array
    {
        /** @noinspection SpellCheckingInspection */
        return [
            'describe' => [
                'hash' => null,
                'all' => false,
                'tags' => false,
                'expected' => '2.0.0-1-gcf1e76b'
            ],
            'describe --all' => [
                'hash' => null,
                'all' => true,
                'tags' => false,
                'expected' => 'heads/master'
            ],
            'describe --tags' => [
                'hash' => null,
                'all' => false,
                'tags' => true,
                'expected' => '2.0.0-1-gcf1e76b'
            ],
            'describe 890ecb06' => [
                'hash' => '890ecb06',
                'all' => false,
                'tags' => false,
                'expected' => '2.0.0'
            ],
            'describe --all 890ecb06' => [
                'hash' => '890ecb06',
                'all' => true,
                'tags' => false,
                'expected' => 'tags/2.0.0'
            ],
            'describe --tags 890ecb06' => [
                'hash' => '890ecb06',
                'all' => false,
                'tags' => true,
                'expected' => '2.0.0'
            ],
            'describe --tags detachedBranch' => [
                'hash' => 'detachedBranch',
                'all' => false,
                'tags' => true,
                'expected' => '0.2.0-1-g0f8b7ce'
            ],
            'describe --all detachedBranch' => [
                'hash' => 'detachedBranch',
                'all' => true,
                'tags' => false,
                'expected' => 'heads/detachedBranch'
            ],
            'describe --tags b534ef51' => [
                'hash' => 'b534ef51',
                'all' => false,
                'tags' => true,
                'expected' => '0.2.0-1-gb534ef5'
            ],
            'describe --all b534ef51' => [
                'hash' => 'b534ef51',
                'all' => true,
                'tags' => false,
                'expected' => 'tags/0.2.0-1-gb534ef5'
            ],
            'describe --tags 7f1abf9c' => [
                'hash' => '7f1abf9c',
                'all' => false,
                'tags' => true,
                'expected' => '0.2.0-2-g7f1abf9'
            ],
            'describe --all 7f1abf9c' => [
                'hash' => '7f1abf9c',
                'all' => true,
                'tags' => false,
                'expected' => 'tags/0.2.0-2-g7f1abf9'
            ],
            'describe --tags b931c2fe' => [
                'hash' => 'b931c2fe',
                'all' => false,
                'tags' => true,
                'expected' => '1.0.0'
            ],
            'describe --all b931c2fe' => [
                'hash' => 'b931c2fe',
                'all' => true,
                'tags' => false,
                'expected' => 'tags/1.0.0'
            ],
            'describe --tags e0d5e0b3' => [
                'hash' => 'e0d5e0b3',
                'all' => false,
                'tags' => true,
                'expected' => '1.0.0-3-ge0d5e0b'
            ],
            'describe --all e0d5e0b3' => [
                'hash' => 'e0d5e0b3',
                'all' => true,
                'tags' => false,
                'expected' => 'tags/1.0.0-3-ge0d5e0b'
            ],
            'describe 5c97392f' => [
                'hash' => '5c97392f',
                'all' => false,
                'tags' => false,
                'expected' => null,
                'error' => 'No annotated tag can describe `5c97392f3736acad2f54b6a6d58a2d50eb9b22b5`'
            ],
            'describe --all 5c97392f' => [
                'hash' => '5c97392f',
                'all' => true,
                'tags' => false,
                'expected' => null,
                'error' => 'No tags can describe `5c97392f3736acad2f54b6a6d58a2d50eb9b22b5`'
            ],
            'describe --tags 5c97392f' => [
                'hash' => '5c97392f',
                'all' => false,
                'tags' => true,
                'expected' => null,
                'error' => 'No tags can describe `5c97392f3736acad2f54b6a6d58a2d50eb9b22b5`'
            ],
            'describe detachedBranch' => [
                'hash' => 'detachedBranch',
                'all' => false,
                'tags' => false,
                'expected' => null,
                'error' => 'No annotated tag can describe `0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf`'
            ],
            'describe tree' => [
                'hash' => '4ac978f076c8654bfd365838bad72608792a287c',
                'all' => false,
                'tags' => false,
                'expected' => null,
                'error' => '`4ac978f076c8654bfd365838bad72608792a287c` is neither a commit nor blob'
            ],
            'describe tree commitIsh' => [
                'hash' => '4ac978',
                'all' => false,
                'tags' => false,
                'expected' => null,
                'error' => '`4ac978` is neither a commit nor blob'
            ],
            'describe blob' => [
                'hash' => '69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be',
                'all' => false,
                'tags' => false,
                'expected' => null,
                'error' => 'No annotated tag can describe `69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be`'
            ],
            'describe blob --tags' => [
                'hash' => '69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be',
                'all' => false,
                'tags' => true,
                'expected' => '1.0.0-1-ga3e6e3b:file'
            ],
            'describe blob --all' => [
                'hash' => '69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be',
                'all' => true,
                'tags' => false,
                'expected' => 'tags/1.0.0-1-ga3e6e3b:file'
            ],
            'describe blob --tags sub path' => [
                'hash' => 'e69de29bb2d1d6434b8b29ae775ad8c2e48c5391',
                'all' => false,
                'tags' => true,
                'expected' => '1.0.0-3-ge0d5e0b:test/dir_test/file777'
            ]
        ];
    }
}
