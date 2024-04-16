<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use PHPUnit\Framework\TestCase;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\TestsHelper;

class TreeTest extends TestCase
{
    private GitRepositoryManager $manager;
    private GitObject $gitObject;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = TestsHelper::getGitRepositoryManager();
        /** @noinspection SpellCheckingInspection */
        $this->gitObject = GitObject::createFromFile(
            implode(DIRECTORY_SEPARATOR, [
                TestsHelper::GIT_TEST_PATH,
                '.git',
                'objects',
                '80',
                'c89636379e7432af7a1d185dcab15a207ec69d'
            ])
        );
    }

    public function testFromGitObject(): void
    {
        // Given (prepared at setUp)

        // When
        $tree = Tree::fromGitObject($this->manager, $this->gitObject);

        // Then
        $ret = [];
        foreach ($tree as $branch) {
            $ret[] = [
                $branch->getMode(), $branch->getHash(),
                $branch->getName()
            ];
        }

        self::assertSame([
            [644, '2b50af3d214b6b49735c4df3d54adee265f43f51', '.git-changelog'],
            [644, '69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be', 'file'],
            [644, 'f3c77b12c6c1a59b79d4a86b6e05ea57b1e45d84', 'file2'],
            [644, 'd033d51983536e2d623b34c246afdd1a6e11d09f', 'file3'],
            [644, 'c367783755ca8487c9de574d189a43de5b606b06', 'fileOnBranch'],
            [644, 'f48a363a2b2aa305c172f64f1f675ff7a4920a6c', 'pack.idx'],
            [644, '2591f0ea09dd1ab5183a6a4ff7b7545b7941bfaf', 'pack.pack'],
            [644, '6bf5a82c80e5a02a43d74a90ca0ceed372b29f94', 'secondFileOnBranch'],
            [000, 'cd5ed3bff843b3cf2a91fc50c90c365dcdc2a0ca', 'test'],
        ], $ret);
    }

    public function testWalkRecursive(): void
    {
        // Given
        $tree = Tree::fromGitObject($this->manager, $this->gitObject);

        // When
        $ret = [];
        /**
         * @var string $path
         * @var TreeBranch $branch
         * @var GitObject $gitObject
         */
        foreach ($tree->walkRecursive() as list($path, $branch, $gitObject)) {
            $ret[] = [
                $branch->getMode(), $gitObject->getTypeName(), $branch->getHash(),
                $branch->getName(), $path
            ];
        }

        // Then
        self::assertSame([
            [644, 'blob', '2b50af3d214b6b49735c4df3d54adee265f43f51', '.git-changelog', ''],
            [644, 'blob', '69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be', 'file', ''],
            [644, 'blob', 'f3c77b12c6c1a59b79d4a86b6e05ea57b1e45d84', 'file2', ''],
            [644, 'blob', 'd033d51983536e2d623b34c246afdd1a6e11d09f', 'file3', ''],
            [644, 'blob', 'c367783755ca8487c9de574d189a43de5b606b06', 'fileOnBranch', ''],
            [644, 'blob', 'f48a363a2b2aa305c172f64f1f675ff7a4920a6c', 'pack.idx', ''],
            [644, 'blob', '2591f0ea09dd1ab5183a6a4ff7b7545b7941bfaf', 'pack.pack', ''],
            [644, 'blob', '6bf5a82c80e5a02a43d74a90ca0ceed372b29f94', 'secondFileOnBranch', ''],
            [000, 'tree', 'cd5ed3bff843b3cf2a91fc50c90c365dcdc2a0ca', 'test', ''],
            [000, 'tree', '0a63e8cfb619885847bb115d2238d8d2a8f16df2', 'dir_test', 'test/'],
            [755, 'blob', 'e69de29bb2d1d6434b8b29ae775ad8c2e48c5391', 'file777', 'test/dir_test/'],
            [644, 'blob', 'e69de29bb2d1d6434b8b29ae775ad8c2e48c5391', 'file_test', 'test/'],
        ], $ret);
    }
}
