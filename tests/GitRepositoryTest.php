<?php

namespace Rodziu\Git;

use PHPUnit\Framework\TestCase;
use Rodziu\Git\Objects\AnnotatedTag;
use Rodziu\Git\Objects\HEAD;
use Rodziu\Git\Objects\Tag;

class GitRepositoryTest extends TestCase
{
    private GitRepository $git;

    public function setUp(): void
    {
        parent::setUp();
        $this->git = new GitRepository(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'.git');
    }

    public function testGetHead(): void
    {
        $this->assertEquals(
            new HEAD('890ecb06d3d373489adb661931f1d02b721375fc', 'master'),
            $this->git->getHead()
        );
    }

    public function testGetBranches(): void
    {
        $branches = $this->git->getBranches();
        sort($branches);
        $this->assertEquals(['branch', 'detachedBranch', 'master', 'someBranch'], $branches
        );
    }

    /**
     * @dataProvider getCommitDataProvider
     */
    public function testGetCommit(string $commitHash, array $expected): void
    {
        $commit = $this->git->getCommit($commitHash);
        $this->assertEquals($expected, get_object_vars($commit));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getCommitDataProvider(): array
    {
        return [
            '7f1abf9c92388346c662ae67665ad040e7f88e8b' => ['7f1abf9c92388346c662ae67665ad040e7f88e8b', [
                'commitHash' => '7f1abf9c92388346c662ae67665ad040e7f88e8b',
                'tree' => '4ac978f076c8654bfd365838bad72608792a287c',
                'parents' => ['b534ef510fc478df9e7c14593b7214abbe2d4e78'],
                'authorName' => 'Mateusz Rohde',
                'authorMail' => 'mateusz.rohde@gmail.com',
                'authorDate' => new \DateTimeImmutable('2017-06-06 13:00:53.000000', new \DateTimeZone('+02:00')),
                'committerName' => 'Mateusz Rohde',
                'committerMail' => 'mateusz.rohde@gmail.com',
                'commitDate' => new \DateTimeImmutable('2017-06-06 13:00:53.000000', new \DateTimeZone('+02:00')),
                'message' => "first commit line\nsecond commit line\nthird commit line"
            ]],
            'b679aa263412e259e97e7687d6f8286bbac43be6' => ['b679aa263412e259e97e7687d6f8286bbac43be6', [
                'commitHash' => 'b679aa263412e259e97e7687d6f8286bbac43be6',
                'tree' => '2edc7d4c18a9b699ea81833b3d5626a29761df20',
                'parents' => ['bd5785f3aa2e35c60f70e4df8ef97613a43391b4', '8dfb1dd06eef93b66d5b42df8ade9662fa41b752'],
                'authorName' => 'Mateusz Rohde',
                'authorMail' => 'mateusz.rohde@gmail.com',
                'authorDate' => new \DateTimeImmutable('2017-06-06 10:56:07.000000', new \DateTimeZone('+02:00')),
                'committerName' => 'Mateusz Rohde',
                'committerMail' => 'mateusz.rohde@gmail.com',
                'commitDate' => new \DateTimeImmutable('2017-06-06 10:56:07.000000', new \DateTimeZone('+02:00')),
                'message' => "Merge branch 'someBranch'"
            ]],
            'b931c2fe4ad701ca4e4839ce5d729bdeb667e681' => ['b931c2fe4ad701ca4e4839ce5d729bdeb667e681', [
                'commitHash' => 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
                'tree' => '500d64db24715c051497d0f85d97b1c2ce4b8a80',
                'parents' => ['7f1abf9c92388346c662ae67665ad040e7f88e8b'],
                'authorName' => 'Mateusz Rohde',
                'authorMail' => 'mateusz.rohde@gmail.com',
                'authorDate' => new \DateTimeImmutable('2017-06-08 14:19:17.000000', new \DateTimeZone('+02:00')),
                'committerName' => 'Mateusz Rohde',
                'committerMail' => 'mateusz.rohde@gmail.com',
                'commitDate' => new \DateTimeImmutable('2017-06-08 14:19:17.000000', new \DateTimeZone('+02:00')),
                'message' => 'branch'
            ]],
            '5cce24046b2cc50eb9d32159d36975433badd456' => ['5cce24046b2cc50eb9d32159d36975433badd456', [
                'commitHash' => '5cce24046b2cc50eb9d32159d36975433badd456',
                'tree' => 'ffb28d71f9ebd4e8a41271f93a3d10e09e682829',
                'parents' => ['a3e6e3bfb5066f12bf2d52a9e5317fbe161d3c06'],
                'authorName' => 'Mateusz Rohde',
                'authorMail' => 'mateusz.rohde@gmail.com',
                'authorDate' => new \DateTimeImmutable('2017-06-09 11:42:28.000000', new \DateTimeZone('+02:00')),
                'committerName' => 'Mateusz Rohde',
                'committerMail' => 'mateusz.rohde@gmail.com',
                'commitDate' => new \DateTimeImmutable('2017-06-09 11:49:15.000000', new \DateTimeZone('+02:00')),
                'message' => 'commit'
            ]],
        ];
    }

    /**
     * @throws \Exception
     */
    public function testGetTags(): void
    {
        $tags = $this->git->getTags();

        $this->assertEquals([
            new AnnotatedTag(
                '2.0.0',
                '991c9a8db826196699a43776e43da3dd09e31dd5',
                '890ecb06d3d373489adb661931f1d02b721375fc',
                'commit',
                'Mateusz Rohde',
                'mateusz.rohde@gmail.com',
                new \DateTimeImmutable('2024-02-06 22:49:40.000000', new \DateTimeZone('+01:00')),
                'Annotated tag description'
            ),
            new Tag('1.0.0', 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'),
            new Tag('0.2.0', 'b679aa263412e259e97e7687d6f8286bbac43be6'),
            new Tag('0.1.0', 'bd5785f3aa2e35c60f70e4df8ef97613a43391b4'),
            new AnnotatedTag(
                'tree',
                'a7538d5b9bf3b4e7c7f3c5c43391852339cefc67',
                '4ac978f076c8654bfd365838bad72608792a287c',
                'tree',
                'Mateusz Rohde',
                'mateusz.rohde@gmail.com',
                new \DateTimeImmutable('2024-02-06 22:59:16.000000', new \DateTimeZone('+01:00')),
                'tree tag'
            ),
        ], $tags);
    }

    /**
     * @dataProvider getLogDataProvider
     */
    public function testGetLog(?string $commitHash, ?string $branch, array $expected): void
    {
        $hashes = [];
        foreach ($this->git->getLog($commitHash, $branch) as $commit) {
            $hashes[] = $commit->commitHash;
        }

        $this->assertEquals($expected, $hashes);
    }

    public static function getLogDataProvider(): array
    {
        return [
            'HEAD' => [
                'commitHash' => null,
                'branch' => null,
                'expected' => [
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
                'branch' => null,
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
            'branch' => [
                'commitHash' => null,
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
