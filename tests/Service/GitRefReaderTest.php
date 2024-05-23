<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;
use Rodziu\Git\TestsHelper;

class GitRefReaderTest extends TestCase
{
    private GitRepositoryManager $manager;
    private GitRefReader $refReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = TestsHelper::getGitRepositoryManager();
        $this->refReader = new GitRefReader($this->manager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        TestsHelper::restoreTestRepository();
    }

    public function testGetHead(): void
    {
        // Given (prepared at setUp)

        // When
        $head = $this->refReader->getHead();

        // Then
        $expected = new Head('cf1e76b98d891e6c5d57d747330abe1e1ed854f6', 'master');
        $this->assertEquals($expected, $head);
    }

    /**
     * @param GitRef[] $expected
     */
    #[DataProvider('getRefsDataProvider')]
    public function testGetRefs(?string $type, array $expected): void
    {
        // Given (prepared at setUp)

        // When
        $actual = iterator_to_array($this->refReader->getRefs($type), false);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array{string, GitRef[]}[]
     */
    public static function getRefsDataProvider(): array
    {
        $remotes = [
            new GitRef('remotes', 'origin/master', 'cf1e76b98d891e6c5d57d747330abe1e1ed854f6'),
            new GitRef('remotes', 'origin/someBranch', '8dfb1dd06eef93b66d5b42df8ade9662fa41b752'),
            new GitRef('remotes', 'origin/detachedBranch', '0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf'),
        ];
        $heads = [
            new GitRef('heads', 'master', 'cf1e76b98d891e6c5d57d747330abe1e1ed854f6'),
            new GitRef('heads', 'someBranch', '8dfb1dd06eef93b66d5b42df8ade9662fa41b752'),
            new GitRef('heads', 'branch', 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'),
            new GitRef('heads', 'detachedBranch', '0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf'),
        ];
        $tags = [
            new GitRef('tags', '2.0.0', '991c9a8db826196699a43776e43da3dd09e31dd5', '890ecb06d3d373489adb661931f1d02b721375fc'),
            new GitRef('tags', '0.1.0', 'bd5785f3aa2e35c60f70e4df8ef97613a43391b4'),
            new GitRef('tags', '0.2.0', 'b679aa263412e259e97e7687d6f8286bbac43be6'),
            new GitRef('tags', '1.0.0', 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'),
            new GitRef('tags', 'tree', 'a7538d5b9bf3b4e7c7f3c5c43391852339cefc67', '4ac978f076c8654bfd365838bad72608792a287c'),
        ];

        return [
            'remotes' => ['remotes', $remotes],
            'heads' => ['heads', $heads],
            'tags' => ['tags', $tags],
            'all' => [null, array_merge($remotes, $heads, $tags)]
        ];
    }

    #[DataProvider('getRefObjectHashDataProvider')]
    public function testGetRefObjectHash(string $ref, string $expected): void
    {
        // Given (prepared at setUp)

        // When
        $actual = $this->refReader->getRefObject($ref)->getTargetObjectHash();

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return string[][]
     */
    public static function getRefObjectHashDataProvider(): array
    {
        return [
            ['detachedBranch', '0f8b7ceb8e9c28627c997b3fa18eaeb614f35fdf'],
            ['master', 'cf1e76b98d891e6c5d57d747330abe1e1ed854f6'],
            ['origin/someBranch', '8dfb1dd06eef93b66d5b42df8ade9662fa41b752'],
            ['2.0.0', '991c9a8db826196699a43776e43da3dd09e31dd5'],
            ['1.0.0', 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'],
        ];
    }

    /**
     * @param GitRef[] $refs
     * @param array<string, string> $expectedFiles
     */
    #[DataProvider('storeRefsDataProvider')]
    public function testStoreRefs(array $refs, array $expectedFiles): void
    {
        // Given (prepared at setUp)

        // When
        $this->refReader->storeRefs($refs);

        // Then
        foreach ($expectedFiles as $path => $contents) {
            $path = $this->manager->resolvePath($path);
            self::assertEquals($contents, file_get_contents($path));
        }
    }

    /**
     * @return array{refs: GitRef[], expectedFiles: array<string, string>}[]
     */
    public static function storeRefsDataProvider(): array
    {
        return [
            [
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
                'expectedFiles' => [
                    'packed-refs' => <<<EOF
# pack-refs with: peeled fully-peeled sorted 
bd5785f3aa2e35c60f70e4df8ef97613a43391b4 refs/tags/0.1.0
b679aa263412e259e97e7687d6f8286bbac43be6 refs/tags/0.2.0
b931c2fe4ad701ca4e4839ce5d729bdeb667e681 refs/tags/1.0.0
a7538d5b9bf3b4e7c7f3c5c43391852339cefc67 refs/tags/tree
^4ac978f076c8654bfd365838bad72608792a287c

EOF,
                ]
            ],
            [
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
                    new GitRef(
                        'remotes',
                        'origin/test',
                        'cf1e76b98d891e6c5d57d747330abe1e1ed854f7'
                    ),
                    new GitRef(
                        'tags',
                        'new-tag',
                        'a7538d5b9bf3b4e7c7f3c5c43391852339cefc68'
                    ),
                ],
                'expectedFiles' => [
                    'packed-refs' => <<<EOF
# pack-refs with: peeled fully-peeled sorted 
cf1e76b98d891e6c5d57d747330abe1e1ed854f7 refs/remotes/origin/test
bd5785f3aa2e35c60f70e4df8ef97613a43391b4 refs/tags/0.1.0
b679aa263412e259e97e7687d6f8286bbac43be6 refs/tags/0.2.0
b931c2fe4ad701ca4e4839ce5d729bdeb667e681 refs/tags/1.0.0
a7538d5b9bf3b4e7c7f3c5c43391852339cefc68 refs/tags/new-tag
a7538d5b9bf3b4e7c7f3c5c43391852339cefc67 refs/tags/tree
^4ac978f076c8654bfd365838bad72608792a287c

EOF,
                ]
            ]
        ];
    }
}
