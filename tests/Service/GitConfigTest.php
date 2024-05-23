<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;


use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\TestsHelper;

class GitConfigTest extends TestCase
{
    /**
     * @param array<string, string> $expected
     */
    #[DataProvider('getSectionVariablesDataProvider')]
    public function testGetSectionVariables(string $section, ?string $name, array $expected): void
    {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $gitConfig = new GitConfig($manager);

        // When
        $variables = $gitConfig->getSectionVariables($section, $name);

        // Then
        self::assertEquals($expected, $variables);
    }

    /**
     * @return array{section: string, name: ?string, expected: array<string, string>}[]
     */
    public static function getSectionVariablesDataProvider(): array
    {
        return [
            [
                'section' => 'core',
                'name' => null,
                'expected' => [
                    'repositoryformatversion' => '0',
                    'filemode' => 'true',
                    'bare' => 'false',
                    'logallrefupdates' => 'true',
                ]
            ],
            [
                'section' => 'remote',
                'name' => 'origin',
                'expected' => [
                    'url' => 'ssh://git@github.com/Rodziu/git-test',
                    'fetch' => '+refs/heads/*:refs/remotes/origin/*',
                ]
            ],
            [
                'section' => 'branch',
                'name' => 'master',
                'expected' => [
                    'remote' => 'origin',
                    'merge' => 'refs/heads/master',
                ]
            ],
            [
                'section' => 'unknown',
                'name' => null,
                'expected' => [],
            ],
        ];
    }
}
