<?php

declare(strict_types=1);

namespace Rodziu\Git;

use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\AnnotatedTag;
use Rodziu\Git\Object\Commit;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\Head;
use Rodziu\Git\Object\Tag;
use Rodziu\Git\Object\Tree;
use Rodziu\Git\Service\GitClone;

readonly class GitRepository
{
    private GitRepositoryManager $manager;

    public function __construct(string $repositoryPath)
    {
        $this->manager = new GitRepositoryManager($repositoryPath);
    }

    public static function cloneRepository(
        string $url,
        string $destinationPath,
        bool $parseRepositoryName = true,
        bool $checkout = true
    ): self {
        return GitClone::cloneRepository(
            $url,
            $destinationPath,
            $parseRepositoryName,
            $checkout
        );
    }

    public function fetch(string $remote = 'origin'): void
    {
        $fetch = $this->manager->getGitFetch();
        $fetch($remote);
    }

    public function checkout(string $commitIsh = null): void
    {
        $this->manager->getGitCheckout()
            ->checkout($commitIsh);
    }

    public function getHead(): Head
    {
        return $this->manager->getRefReader()
            ->getHead();
    }

    public function getObject(string $objectPath): ?GitObject
    {
        return $this->manager->getObjectReader()
            ->getObject($objectPath);
    }

    public function getCommit(string $commitHash): Commit
    {
        return $this->manager->getObjectReader()
            ->getCommit($commitHash);
    }

    public function getTree(string $treeHash): Tree
    {
        return $this->manager->getObjectReader()
            ->getTree($treeHash);
    }

    /**
     * @return string[]
     */
    public function getBranches(bool $remote = false): array
    {
        $refReader = $this->manager->getRefReader();
        $branches = [];

        foreach ($refReader->getRefs($remote ? 'remotes' : 'heads') as $ref) {
            $branches[] = $ref->getName();
        }

        return $branches;
    }

    /**
     * @return (Tag|AnnotatedTag)[]
     */
    public function getTags(): array
    {
        $refReader = $this->manager->getRefReader();
        $tags = [];

        foreach ($refReader->getRefs('tags') as $ref) {
            if ($ref->getAnnotatedTagTargetHash() !== null) {
                $tags[] = AnnotatedTag::fromGitObject(
                    $this->getObject($ref->getTargetObjectHash())
                );
            } else {
                $tags[] = new Tag($ref->getName(), $ref->getTargetObjectHash());
            }
        }

        usort($tags, function (Tag $a, Tag $b) {
            return version_compare($b->getName(), $a->getName());
        });

        return $tags;
    }

    /**
     * @return \Generator<Commit>
     */
    public function getLog(?string $commitIsh = null): \Generator
    {
        $gitLog = $this->manager->getGitLog();

        yield from $gitLog($commitIsh);
    }

    public function describe(
        ?string $commitIsh = null,
        bool $all = false,
        bool $tags = false
    ): string {
        $gitDescribe = $this->manager->getGitDescribe();

        return $gitDescribe($commitIsh, $all, $tags);
    }
}
