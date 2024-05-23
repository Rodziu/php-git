<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;
use Rodziu\Git\TestsHelper;

class GitFetchTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        TestsHelper::restoreTestRepository();
    }

    public function testFetch(): void
    {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $gitFetch = new GitFetch($manager);
        $refMasterPath = $manager->resolvePath('refs', 'remotes', 'origin', 'master');
        file_put_contents(
            $refMasterPath,
            '890ecb06d3d373489adb661931f1d02b721375fc'
        );

        // When
        $gitFetch();

        // Then
        $hash = file_get_contents($refMasterPath);
        self::assertEquals('cf1e76b98d891e6c5d57d747330abe1e1ed854f6', $hash);
    }

    /**
     * @param array{head: Head, refs: GitRef[]} $repositoryInfo
     * @param array{string[], string[]} $expected
     */
    #[DataProvider('getHavesAndWantsDataProvider')]
    public function testGetHavesAndWants(array $repositoryInfo, array $expected): void
    {
        // Given
        $manager = TestsHelper::getGitRepositoryManager();
        $gitFetch = new GitFetch($manager);

        // When
        $actual = $gitFetch->getHavesAndWants($repositoryInfo);

        // Then
        self::assertEquals($expected, $actual);
    }

    /**
     * @return array{repositoryInfo: array{head: Head, refs: GitRef[]}, expected: array{string[], string[]}}[]
     */
    public static function getHavesAndWantsDataProvider(): array
    {
        return [
            [
                'repositoryInfo' => [
                    'head' => new Head(
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f6',
                        'origin/master'
                    ),
                    'refs' => [
                        new GitRef(
                            'remotes',
                            'origin/master',
                            'cf1e76b98d891e6c5d57d747330abe1e1ed854f6'
                        ),
                        new GitRef(
                            'tags',
                            '0.1.0',
                            'bd5785f3aa2e35c60f70e4df8ef97613a43391b4'
                        ),
                        new GitRef(
                            'tags',
                            '0.2.0',
                            'b679aa263412e259e97e7687d6f8286bbac43be6'
                        ),
                        new GitRef(
                            'tags',
                            '1.0.0',
                            'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'
                        ),
                        new GitRef(
                            'tags',
                            '2.0.0',
                            '991c9a8db826196699a43776e43da3dd09e31dd5',
                            '890ecb06d3d373489adb661931f1d02b721375fc'
                        ),
                        new GitRef(
                            'tags',
                            'tree',
                            'a7538d5b9bf3b4e7c7f3c5c43391852339cefc67',
                            '4ac978f076c8654bfd365838bad72608792a287c'
                        ),
                    ],
                ],
                'expected' => [
                    [
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f6',
                        '8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
                        '0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf'
                    ],
                    [],
                ],
            ],
            [
                'repositoryInfo' => [
                    'head' => new Head(
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f7',
                        'origin/master'
                    ),
                    'refs' => [
                        new GitRef(
                            'remotes',
                            'origin/master',
                            'cf1e76b98d891e6c5d57d747330abe1e1ed854f7'
                        ),
                        new GitRef(
                            'tags',
                            '0.1.0',
                            'bd5785f3aa2e35c60f70e4df8ef97613a43391b4'
                        ),
                        new GitRef(
                            'tags',
                            '0.2.0',
                            'b679aa263412e259e97e7687d6f8286bbac43be6'
                        ),
                        new GitRef(
                            'tags',
                            '1.0.0',
                            'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'
                        ),
                        new GitRef(
                            'tags',
                            '2.0.0',
                            '991c9a8db826196699a43776e43da3dd09e31dd5',
                            '890ecb06d3d373489adb661931f1d02b721375fc'
                        ),
                        new GitRef(
                            'tags',
                            'tree',
                            'a7538d5b9bf3b4e7c7f3c5c43391852339cefc67',
                            '4ac978f076c8654bfd365838bad72608792a287c'
                        ),
                        new GitRef(
                            'tags',
                            'newTag',
                            'a7538d5b9bf3b4e7c7f3c5c43391852339cefc68',
                            '4ac978f076c8654bfd365838bad72608792a287c'
                        ),
                    ],
                ],
                'expected' => [
                    [
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f6',
                        '8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
                        '0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf'
                    ],
                    [
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f7',
                        'a7538d5b9bf3b4e7c7f3c5c43391852339cefc68'
                    ],
                ],
            ],
        ];
    }
}
