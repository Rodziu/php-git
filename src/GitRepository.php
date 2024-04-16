<?php

declare(strict_types=1);

namespace Rodziu\Git;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\AnnotatedTag;
use Rodziu\Git\Object\Commit;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\Head;
use Rodziu\Git\Object\Tag;
use Rodziu\Git\Object\Tree;
use Rodziu\Git\Object\TreeBranch;
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
        if ($parseRepositoryName) {
            $repositoryName = GitClone::getRepositoryNameFromUrl($url);

            if ($repositoryName === null) {
                throw new GitException("Could not parse repository name from `$url`");
            }

            $destinationPath = $destinationPath.DIRECTORY_SEPARATOR.$repositoryName;
        }


        $umask = umask(0);

        try {
            $clone = new GitClone($url, $destinationPath);
            $clone->fetchRepositoryInfo();
            $git = new self($clone->getRepositoryPath());
            $head = $git->getHead();
            $clone->fetchObjects($head->getCommitHash());
            if ($checkout) {
                $git->checkout($head->getCommitHash());
            }
            umask($umask);
            return $git;
        } catch (GitException $e) {
            umask($umask);
            throw $e;
        }
    }

    public function getHead(): Head
    {
        return $this->manager->getRefReader()
            ->getHead();
    }

    public function checkout(string $commitIsh = null): void
    {
        $commitHash = $this->manager->getRefReader()
            ->resolveCommitIsh($commitIsh);
        $commit = $this->getCommit($commitHash);
        $treeObject = $this->manager->getObjectReader()
            ->getObject($commit->getTree());
        $tree = Tree::fromGitObject($this->manager, $treeObject);
        $basePath = dirname($this->manager->getRepositoryPath());

        foreach ($tree->walkRecursive() as [$path, $branch, $object]) {
            /**
             * @var string $path
             * @var TreeBranch $branch
             * @var GitObject $object
             */
            $path = $basePath.DIRECTORY_SEPARATOR.$path.$branch->getName();

            if ($branch->getMode() === 0) {
                mkdir($path, 0755);
            } else {
                file_put_contents($path, $object->getData());
                if ($branch->getMode() === 755) {
                    chmod($path, 0755);
                }
            }
        }
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
        $objectReader = $this->manager->getObjectReader();
        $tags = [];

        foreach ($refReader->getRefs('tags') as $ref) {
            if ($ref->getAnnotatedTagTargetHash() !== null) {
                $tags[] = AnnotatedTag::fromGitObject(
                    $objectReader->getObject($ref->getTargetObjectHash())
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

    public function getCommit(string $commitHash): Commit
    {
        $object = $this->manager->getObjectReader()
            ->getObject($commitHash);

        if ($object === null || $object->getType() !== GitObject::TYPE_COMMIT) {
            throw new GitException("Commit $commitHash does not exist!");
        }

        return Commit::fromGitObject($object);
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
