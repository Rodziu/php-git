<?php

namespace Rodziu\Git;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Objects\AnnotatedTag;
use Rodziu\Git\Objects\Commit;
use Rodziu\Git\Objects\GitObject;
use Rodziu\Git\Objects\HEAD;
use Rodziu\Git\Objects\Tag;
use Rodziu\Git\Pack\Pack;

class GitRepository
{
    /**
     * @var Pack[]
     */
    private ?array $packs = null;

    /**
     * GitRepository constructor.
     *
     * @param string $gitRepoPath
     */
    public function __construct(
        private string $gitRepoPath
    ) {
        $gitRepoPath = rtrim($gitRepoPath, DIRECTORY_SEPARATOR);

        if (basename($gitRepoPath) != '.git' || !file_exists($gitRepoPath)) {
            throw new GitException("$gitRepoPath is not a git repository!");
        }

        $this->gitRepoPath = $gitRepoPath;
    }

    /**
     * Get current HEAD of repository
     *
     * @throws GitException
     */
    public function getHead(): HEAD
    {
        $headPath = $this->gitRepoPath.DIRECTORY_SEPARATOR.'HEAD';

        if (!file_exists($headPath)) {
            throw new GitException("Head file does not exist at $headPath!");
        }

        $head = trim(file_get_contents($headPath));

        if (strlen($head) == 40) {
            return new HEAD($head);
        } else if (!preg_match('#^ref:\s+(.*)$#su', $head, $match)) {
            throw new GitException("Could not match ref: in $headPath!");
        }

        $branch = preg_replace('#^.*/#', '', $match[1]);

        return new HEAD(
            trim(file_get_contents(
                $this->gitRepoPath.DIRECTORY_SEPARATOR.trim($match[1])
            )),
            $branch
        );
    }

    /**
     * @return string[]
     */
    public function getBranches(): array
    {
        $ret = [];
        $packedRefs = $this->getPackedRefs();

        foreach ($packedRefs['heads'] as $v) {
            $ret[] = $v;
        }

        $iterator = new \IteratorIterator(new \DirectoryIterator(
            $this->gitRepoPath.DIRECTORY_SEPARATOR.'refs'.DIRECTORY_SEPARATOR.'heads'
        ));
        foreach ($iterator as $i) {
            if (!$i->isDot()) {
                $ret[] = $i->getFileName();
            }
        }

        return $ret;
    }

    protected function getPackedRefs(): array
    {
        $ret = [
            'heads' => [],
            'tags' => []
        ];
        $packedRefsPath = $this->gitRepoPath.DIRECTORY_SEPARATOR.'packed-refs';

        if (file_exists($packedRefsPath)) {
            $packedRefs = file_get_contents($packedRefsPath);

            if (preg_match_all('#^([a-z0-9]{40}) refs/(tags|heads)/(.*)$#m', $packedRefs, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $ret[$m[2]][$m[3]] = $m[1];
                }
            }
        }

        return $ret;
    }

    /**
     * @return (Tag|AnnotatedTag)[]
     */
    public function getTags(): array
    {
        $tags = [];
        $packedRefs = $this->getPackedRefs();

        foreach ($packedRefs['tags'] as $tagName => $taggedObjectHash) {
            $tags[] = $this->getTagObject($tagName, $taggedObjectHash);
        }

        $iterator = new \IteratorIterator(new \DirectoryIterator(
            $this->gitRepoPath.DIRECTORY_SEPARATOR.'refs'.DIRECTORY_SEPARATOR.'tags'
        ));
        foreach ($iterator as $i) {
            /** @var $i \DirectoryIterator */
            if (!$i->isDot()) {
                $tags[] = $this->getTagObject(
                    $i->getFilename(),
                    trim(file_get_contents($i->getPathname()))
                );
            }
        }

        usort($tags, function (Tag $a, Tag $b) {
            return version_compare($b->tag, $a->tag);
        });

        return $tags;
    }

    private function getTagObject(string $tagName, string $taggedObjectHash): Tag|AnnotatedTag
    {
        $taggedObject = $this->getObject($taggedObjectHash);

        if ($taggedObject->getType() !== GitObject::TYPE_TAG) {
            return new Tag($tagName, $taggedObjectHash);
        }

        return AnnotatedTag::fromGitObject($taggedObject);
    }

    public function getObject(string $hash): ?GitObject
    {
        $localPath = $this->gitRepoPath
            .DIRECTORY_SEPARATOR.'objects'
            .DIRECTORY_SEPARATOR.substr($hash, 0, 2)
            .DIRECTORY_SEPARATOR.substr($hash, 2);
        $object = GitObject::createFromFile($localPath);

        if ($object !== null) {
            return $object;
        }

        foreach ($this->getPacks() as $pack) {
            $object = $pack->getPackedObject($hash);

            if ($object !== null) {
                return $object;
            }
        }

        return null;
    }

    /**
     * @return \Generator<Commit>
     */
    public function getLog(
        ?string $commitHash = null,
        string $branch = null
    ): \Generator {
        if ($commitHash === null && $branch === null) {
            $commitHash = $this->getHead()->commitHash;
        } else if ($commitHash === null) {
            $commitHash = $this->getTip($branch);
        }

        $candidates = [$this->getCommit($commitHash)];
        $visitedHashes = [$commitHash];

        do {
            usort($candidates, function (Commit $a, Commit $b) {
                return $b->authorDate->getTimestamp() <=> $a->authorDate->getTimestamp();
            });

            $currentCommit = array_shift($candidates);
            yield $currentCommit;

            foreach ($currentCommit->parents as $parentCommitHash) {
                if (in_array($parentCommitHash, $visitedHashes)) {
                    continue;
                }

                $candidates[] = $this->getCommit($parentCommitHash);
                $visitedHashes[] = $parentCommitHash;
            }
        } while (count($candidates) > 0);
    }

    /**
     * @throws GitException
     */
    public function getTip(string $branch = 'master'): string
    {
        $headPath = $this->gitRepoPath
            .DIRECTORY_SEPARATOR.'refs'
            .DIRECTORY_SEPARATOR.'heads'
            .DIRECTORY_SEPARATOR.$branch;

        if (!file_exists($headPath)) {
            throw new GitException("No such branch $branch!");
        }

        return trim(file_get_contents($headPath));
    }

    /**
     * @throws GitException
     */
    public function getCommit(string $commitHash): Commit
    {
        $object = $this->getObject($commitHash);

        if ($object === null || $object->getType() !== GitObject::TYPE_COMMIT) {
            throw new GitException("Commit $commitHash does not exist!");
        }

        return Commit::fromGitObject($object);
    }

    /**
     * @return Pack[]
     */
    protected function getPacks(): array
    {
        if ($this->packs !== null) {
            return $this->packs;
        }

        $this->packs = [];
        $packsDirectory = $this->gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR.'pack';

        if (is_dir($packsDirectory)) {
            foreach (new \DirectoryIterator($packsDirectory) as $pack) {
                if ($pack->isFile() && $pack->getExtension() === 'pack') {
                    $this->packs[] = new Pack($pack->getPathname());
                }
            }
        }

        return $this->packs;
    }

    /**
     * Save git object to filesystem if it doesn't already exist
     */
    public function saveGitObject(GitObject $gitObject): void
    {
        $path = $this->gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR
            .substr($gitObject->getSha1(), 0, 2);
        $objectFile = substr($gitObject->getSha1(), 2);

        if (file_exists($path.DIRECTORY_SEPARATOR.$objectFile)) {
            return;
        }

        @mkdir($path);
        file_put_contents(
            $path.DIRECTORY_SEPARATOR.$objectFile,
            gzcompress((string) $gitObject)
        );
    }
}
