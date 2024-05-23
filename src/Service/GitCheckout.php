<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;
use Rodziu\Git\Object\Tree;
use Rodziu\Git\Object\TreeBranch;
use Rodziu\Git\Util\FileSystemUtil;

readonly class GitCheckout
{
    public function __construct(
        private GitRepositoryManager $manager
    ) {
    }

    public function checkout(string $commitIsh = null): void
    {
        $basePath = dirname($this->manager->getRepositoryPath());
        FileSystemUtil::cleanDir($basePath, ['.git']);

        $ref = $this->manager->getRefReader()
            ->resolveCommitIsh($commitIsh);

        if ($ref instanceof Head) {
            $commitHash = $ref->getCommitHash();
        } elseif ($ref instanceof GitRef) {
            $commitHash = $ref->getTargetObjectHash();
        } else {
            $commitHash = $ref;
        }

        $objectReader = $this->manager->getObjectReader();
        $commit = $objectReader->getCommit($commitHash);
        $tree = $objectReader->getTree($commit->getTree());

        $this->checkoutTree($tree, $basePath);

        if (!($ref instanceof Head)) {
            $this->updateHead($ref);
        }
    }

    private function checkoutTree(Tree $tree, string $basePath): void
    {
        $umask = umask(0);

        foreach ($tree->walkRecursive() as [$path, $branch, $object]) {
            /**
             * @var string $path
             * @var TreeBranch $branch
             * @var GitObject $object
             */
            $path = $basePath.DIRECTORY_SEPARATOR.$path.$branch->getName();

            if ($branch->getMode() === 0) {
                FileSystemUtil::mkdirIfNotExists($path);
            } else {
                file_put_contents($path, $object->getData());
                if ($branch->getMode() === 755) {
                    chmod($path, 0755);
                }
            }
        }

        umask($umask);
    }

    private function updateHead(GitRef|string $ref): void
    {
        if (is_string($ref)) {
            $contents = $ref;
        } elseif ($ref->getType() === 'tags') {
            $contents = $ref->getTargetObjectHash();
        } elseif ($ref->getType() === 'heads') {
            $contents = "ref: $ref";
        } else {
            $ref = $this->checkoutRemoteBranch($ref);
            $contents = "ref: $ref";
        }

        file_put_contents(
            $this->manager->resolvePath('HEAD'),
            $contents
        );
    }

    private function checkoutRemoteBranch(GitRef $remoteBranch): GitRef
    {
        if ($remoteBranch->getType() !== 'remotes') {
            throw new GitException("Ref `{$remoteBranch}` is not a remote branch");
        }

        $branchName = preg_replace('#.*/#', '', $remoteBranch->getName());
        $localBranch = new GitRef(
            'heads',
            $branchName,
            $remoteBranch->getTargetObjectHash()
        );
        $refs = [];
        $exists = false;

        foreach ($this->manager->getRefReader()->getRefs() as $currentRef) {
            if ($currentRef->getType() === 'heads' && $currentRef->getName() === $branchName) {
                $currentRef = $localBranch;
                $exists = true;
            }

            $refs[] = $currentRef;
        }

        if (!$exists) {
            $refs[] = $localBranch;
        }

        $this->manager->getRefReader()->storeRefs($refs);

        return $localBranch;
    }
}
