<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\Commit;

readonly class GitLog
{
    public function __construct(
        private GitRepositoryManager $manager
    ) {
    }

    /**
     * @return \Generator<Commit>
     */
    public function __invoke(?string $commitIsh = null): \Generator
    {
        $commitHash = $this->manager->getRefReader()
            ->resolveCommitIsh($commitIsh);
        $candidates = [$this->getCommit($commitHash)];
        $visitedHashes = [$commitHash];

        do {
            usort($candidates, function (Commit $a, Commit $b) {
                return $b->getAuthorDate()->getTimestamp() <=> $a->getAuthorDate()->getTimestamp();
            });

            $currentCommit = array_shift($candidates);
            yield $currentCommit;

            foreach ($currentCommit->getParents() as $parentCommitHash) {
                if (in_array($parentCommitHash, $visitedHashes)) {
                    continue;
                }

                $candidates[] = $this->getCommit($parentCommitHash);
                $visitedHashes[] = $parentCommitHash;
            }
        } while (count($candidates) > 0);
    }

    private function getCommit(string $commitHash): Commit
    {
        $object = $this->manager->getObjectReader()
            ->getObject($commitHash);

        return Commit::fromGitObject($object);
    }
}
