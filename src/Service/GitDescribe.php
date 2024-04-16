<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\Commit;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\Tree;
use Rodziu\Git\Object\TreeBranch;

readonly class GitDescribe
{
    public function __construct(
        private GitRepositoryManager $manager
    ) {
    }

    public function __invoke(
        ?string $commitIsh = null,
        bool $all = false,
        bool $tags = false
    ): string {
        $describedObjectHash = $this->manager->getRefReader()
            ->resolveCommitIsh($commitIsh, true);
        $describedObjectType = $this->manager->getObjectReader()
            ->getObjectType($describedObjectHash);

        if (!in_array($describedObjectType, [GitObject::TYPE_COMMIT, GitObject::TYPE_BLOB], true)) {
            throw new GitException("`$commitIsh` is neither a commit nor blob");
        }

        $index = $this->createDescribeRefIndex($all, $tags);
        $description = $describedObjectType === GitObject::TYPE_COMMIT
            ? $this->describeCommit($describedObjectHash, $index)
            : $this->describeBlob($describedObjectHash, $index);

        if ($description) {
            return $description;
        }

        $type = $all || $tags ? 'tags' : 'annotated tag';
        throw new GitException("No $type can describe `$describedObjectHash`");
    }

    /**
     * @return string[]
     */
    private function createDescribeRefIndex(
        bool $all,
        bool $tags
    ): array {
        $refReader = $this->manager->getRefReader();
        $index = [];

        foreach ($refReader->getRefs($all ? null : 'tags') as $ref) {
            if (
                $all
                || ($tags && $ref->getType() === 'tags')
                || ($ref->getAnnotatedTagTargetHash() !== null)
            ) {
                $key = $ref->getAnnotatedTagTargetHash() ?? $ref->getTargetObjectHash();
                $index[$key] = ($all ? $ref->getType().'/' : '').$ref->getName();
            }
        }

        return $index;
    }

    /**
     * @param string[] $index
     */
    private function describeCommit(
        string $commitHash,
        array $index
    ): ?string {
        $gitLog = $this->manager->getGitLog();

        foreach ($gitLog($commitHash) as $i => $commit) {
            if ($i === 0) {
                $commitHash = $commit->getCommitHash();
            }

            if (array_key_exists($commit->getCommitHash(), $index)) {
                return $this->getDescription(
                    $index[$commit->getCommitHash()],
                    $i,
                    $commitHash
                );
            }
        }

        return null;
    }

    private function getDescription(
        string $refName,
        int $count,
        string $commitHash
    ): string {
        $description = [$refName];

        if ($count > 0) {
            $description[] = $count;
            $description[] = 'g'.substr($commitHash, 0, 7);
        }

        return implode('-', $description);
    }


    /**
     * @param string[] $index
     */
    private function describeBlob(
        string $blobHash,
        array $index
    ): ?string {
        $gitLog = $this->manager->getGitLog();
        $objectReader = $this->manager->getObjectReader();
        $log = array_reverse(iterator_to_array($gitLog()));
        $refName = null;
        $commitsSinceLastRef = 0;

        /** @var Commit $commit */
        foreach ($log as $commit) {
            if (array_key_exists($commit->getCommitHash(), $index)) {
                $refName = $index[$commit->getCommitHash()];
                $commitsSinceLastRef = 0;
            } else {
                $commitsSinceLastRef++;
            }

            $object = $objectReader->getObject($commit->getTree());
            $tree = Tree::fromGitObject($this->manager, $object);
            foreach ($tree->walkRecursive() as [$path, $branch, $object]) {
                /**
                 * @var string $path
                 * @var TreeBranch $branch
                 * @var GitObject $object
                 */
                if ($blobHash !== $object->getHash()) {
                    continue;
                }

                if ($refName === null) {
                    return null;
                }

                $description = $this->getDescription(
                    $refName,
                    $commitsSinceLastRef,
                    $commit->getCommitHash()
                );

                return $description.':'.$path.$branch->getName();
            }
        }

        return null;
    }
}
