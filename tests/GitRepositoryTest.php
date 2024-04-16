<?php

declare(strict_types=1);

namespace Rodziu\Git;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\Object\AnnotatedTag;
use Rodziu\Git\Object\Commit;
use Rodziu\Git\Object\Tag;

class GitRepositoryTest extends TestCase
{
    private GitRepository $git;

    public function setUp(): void
    {
        parent::setUp();
        $this->git = new GitRepository(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'.git');
    }

    public function testGetBranches(): void
    {
        // Given (prepared at setUp)

        // When
        $branches = $this->git->getBranches();

        // Then
        sort($branches);
        $this->assertEquals(['branch', 'detachedBranch', 'master', 'someBranch'], $branches);
    }

    public function testGetRemoteBranches(): void
    {
        // Given (prepared at setUp)

        // When
        $branches = $this->git->getBranches(true);

        // Then
        sort($branches);
        $this->assertEquals(['origin/detachedBranch', 'origin/master', 'origin/someBranch'], $branches);
    }

    /**
     * @throws \Exception
     */
    public function testGetTags(): void
    {
        // Given (prepared at setUp)

        // When
        $tags = $this->git->getTags();

        // Then
        $expected = [
            new AnnotatedTag(
                '2.0.0',
                '890ecb06d3d373489adb661931f1d02b721375fc',
                '991c9a8db826196699a43776e43da3dd09e31dd5',
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
                '4ac978f076c8654bfd365838bad72608792a287c',
                'a7538d5b9bf3b4e7c7f3c5c43391852339cefc67',
                'tree',
                'Mateusz Rohde',
                'mateusz.rohde@gmail.com',
                new \DateTimeImmutable('2024-02-06 22:59:16.000000', new \DateTimeZone('+01:00')),
                'tree tag'
            ),
        ];

        $this->assertEquals($expected, $tags);
    }

    #[DataProvider('getCommitDataProvider')]
    public function testGetCommit(string $commitHash, Commit $expected): void
    {
        // Given (prepared at setUp)

        // When
        $commit = $this->git->getCommit($commitHash);

        // Then
        $this->assertEquals($expected, $commit);
    }

    /**
     * @return array{0:string, 1: Commit}[]
     * @throws \Exception
     */
    public static function getCommitDataProvider(): array
    {
        return [
            '7f1abf9c92388346c662ae67665ad040e7f88e8b' => [
                '7f1abf9c92388346c662ae67665ad040e7f88e8b',
                new Commit(
                    '7f1abf9c92388346c662ae67665ad040e7f88e8b',
                    '4ac978f076c8654bfd365838bad72608792a287c',
                    ['b534ef510fc478df9e7c14593b7214abbe2d4e78'],
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-06 13:00:53.000000', new \DateTimeZone('+02:00')),
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-06 13:00:53.000000', new \DateTimeZone('+02:00')),
                    "first commit line\nsecond commit line\nthird commit line"
                ),
            ],
            'b679aa263412e259e97e7687d6f8286bbac43be6' => [
                'b679aa263412e259e97e7687d6f8286bbac43be6',
                new Commit(
                    'b679aa263412e259e97e7687d6f8286bbac43be6',
                    '2edc7d4c18a9b699ea81833b3d5626a29761df20',
                    ['bd5785f3aa2e35c60f70e4df8ef97613a43391b4', '8dfb1dd06eef93b66d5b42df8ade9662fa41b752'],
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-06 10:56:07.000000', new \DateTimeZone('+02:00')),
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-06 10:56:07.000000', new \DateTimeZone('+02:00')),
                    "Merge branch 'someBranch'"
                )
            ],
            'b931c2fe4ad701ca4e4839ce5d729bdeb667e681' => [
                'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
                new Commit(
                    'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
                    '500d64db24715c051497d0f85d97b1c2ce4b8a80',
                    ['7f1abf9c92388346c662ae67665ad040e7f88e8b'],
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-08 14:19:17.000000', new \DateTimeZone('+02:00')),
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-08 14:19:17.000000', new \DateTimeZone('+02:00')),
                    'branch'
                )
            ],
            '5cce24046b2cc50eb9d32159d36975433badd456' => [
                '5cce24046b2cc50eb9d32159d36975433badd456',
                new Commit(
                    '5cce24046b2cc50eb9d32159d36975433badd456',
                    'ffb28d71f9ebd4e8a41271f93a3d10e09e682829',
                    ['a3e6e3bfb5066f12bf2d52a9e5317fbe161d3c06'],
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-09 11:42:28.000000', new \DateTimeZone('+02:00')),
                    'Mateusz Rohde',
                    'mateusz.rohde@gmail.com',
                    new \DateTimeImmutable('2017-06-09 11:49:15.000000', new \DateTimeZone('+02:00')),
                    'commit'
                )
            ],
        ];
    }
}
