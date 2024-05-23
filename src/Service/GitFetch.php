<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;
use Rodziu\Git\Util\FileSystemUtil;

readonly class GitFetch
{
    public function __construct(
        private GitRepositoryManager $manager,
    ) {
    }

    /**
     * @return array{head: Head, refs: GitRef[]}
     */
    public function __invoke(string $remote = 'origin'): array
    {
        $client = $this->manager->getUploadPackClient();
        $repositoryInfo = $client->fetchRepositoryInfo($remote);
        [$haves, $wants] = $this->getHavesAndWants($repositoryInfo);

        if (count($wants) === 0) {
            return $repositoryInfo;
        }

        $client->fetchObjects($haves, $wants);
        $this->updateRefs($repositoryInfo, $remote);

        return $repositoryInfo;
    }

    /**
     * @param array{head: Head, refs: GitRef[]} $repositoryInfo
     * @return array{string[], string[]}
     */
    public function getHavesAndWants(array $repositoryInfo): array
    {
        $refReader = $this->manager->getRefReader();
        $currentRefsIndex = [];
        $haves = [];
        $wants = [];

        foreach ($refReader->getRefs('remotes', 'tags') as $ref) {
            if ($ref->getType() === 'remotes') {
                $currentRefsIndex[(string) $ref] = $ref->getTargetObjectHash();
                $haves[] = $ref->getTargetObjectHash();
            } elseif ($ref->getAnnotatedTagTargetHash() !== null) {
                $currentRefsIndex[(string) $ref] = $ref->getTargetObjectHash();
            }
        }


        foreach ($repositoryInfo['refs'] as $ref) {
            $fqn = (string) $ref;

            if (
                (
                    $ref->getType() === 'remotes'
                    || $ref->getAnnotatedTagTargetHash() !== null
                )
                && (
                    !array_key_exists($fqn, $currentRefsIndex)
                    || $currentRefsIndex[$fqn] !== $ref->getTargetObjectHash()
                )
            ) {
                $wants[] = $ref->getTargetObjectHash();
            }
        }

        return [
            $haves,
            $wants,
        ];
    }

    /**
     * @param array{head: Head, refs: GitRef[]} $repositoryInfo
     */
    public function updateRefs(array $repositoryInfo, string $remote): void
    {
        $headPath = $this->manager->resolvePath('refs', 'remotes', $remote, 'HEAD');
        FileSystemUtil::mkdirIfNotExists(dirname($headPath));
        file_put_contents($headPath, $repositoryInfo['head']->getCommitHash().PHP_EOL);

        $this->manager->getRefReader()
            ->storeRefs($repositoryInfo['refs']);
    }
}
