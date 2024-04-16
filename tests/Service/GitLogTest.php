<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\TestsHelper;

class GitLogTest extends TestCase
{
    /**
     * @param string[] $expected
     */
    #[DataProvider('getLogDataProvider')]
    public function testGetLog(?string $commitHash, array $expected): void
    {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $gitLog = new GitLog($manager);

        // When
        $hashes = [];
        foreach ($gitLog($commitHash) as $commit) {
            $hashes[] = $commit->getCommitHash();
        }

        // Then
        $this->assertEquals($expected, $hashes);
    }

    /**
     * @return array{commitHash: string|null, expected: string[]}[]
     */
    public static function getLogDataProvider(): array
    {
        return [
            'HEAD' => [
                'commitHash' => null,
                'expected' => [
                    'cf1e76b98d891e6c5d57d747330abe1e1ed854f6',
                    '890ecb06d3d373489adb661931f1d02b721375fc',
                    'e0d5e0b30030060c2cc8e1e131b80d576ddcfea7',
                    '5cce24046b2cc50eb9d32159d36975433badd456',
                    'a3e6e3bfb5066f12bf2d52a9e5317fbe161d3c06',
                    'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
                    '7f1abf9c92388346c662ae67665ad040e7f88e8b',
                    'b534ef510fc478df9e7c14593b7214abbe2d4e78',
                    'b679aa263412e259e97e7687d6f8286bbac43be6',
                    'bd5785f3aa2e35c60f70e4df8ef97613a43391b4',
                    '8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
                    '5c97392f3736acad2f54b6a6d58a2d50eb9b22b5',
                    '605789f6cf83cfc13911db511faa8c5ae1800261',
                ]
            ],
            'commitHash' => [
                'commitHash' => 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
                'expected' => [
                    'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
                    '7f1abf9c92388346c662ae67665ad040e7f88e8b',
                    'b534ef510fc478df9e7c14593b7214abbe2d4e78',
                    'b679aa263412e259e97e7687d6f8286bbac43be6',
                    'bd5785f3aa2e35c60f70e4df8ef97613a43391b4',
                    '8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
                    '5c97392f3736acad2f54b6a6d58a2d50eb9b22b5',
                    '605789f6cf83cfc13911db511faa8c5ae1800261',
                ]
            ],
            'commitIsh' => [
                'commitHash' => '8dfb1dd0',
                'expected' => [
                    '8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
                    '5c97392f3736acad2f54b6a6d58a2d50eb9b22b5',
                    '605789f6cf83cfc13911db511faa8c5ae1800261',
                ]
            ],
            'branch' => [
                'branch' => 'detachedBranch',
                'expected' => [
                    '0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf',
                    'b679aa263412e259e97e7687d6f8286bbac43be6',
                    'bd5785f3aa2e35c60f70e4df8ef97613a43391b4',
                    '8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
                    '5c97392f3736acad2f54b6a6d58a2d50eb9b22b5',
                    '605789f6cf83cfc13911db511faa8c5ae1800261',
                ]
            ],
        ];
    }
}
