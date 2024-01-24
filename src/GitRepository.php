<?php

namespace Rodziu\Git;

use Rodziu\Git\Pack\Pack;
use Rodziu\Git\Types\Commit;
use Rodziu\Git\Types\GitObject;
use Rodziu\Git\Types\HEAD;
use Rodziu\Git\Types\Tag;

class GitRepository
{
    /**
     * @var string
     */
    private $gitRepoPath = "";
    /**
     * @var array
     */
    private $packs = [];

    /**
     * GitRepository constructor.
     *
     * @param string $gitRepoPath
     */
    public function __construct(string $gitRepoPath)
    {
        $gitRepoPath = rtrim($gitRepoPath, DIRECTORY_SEPARATOR);
        if (basename($gitRepoPath) != '.git' || !file_exists($gitRepoPath)) {
            throw new GitException("$gitRepoPath is not a git repository!");
        }
        $this->gitRepoPath = $gitRepoPath;
        $this->packs = [];
        if (is_dir($gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR.'pack')) {
            foreach (
                new \DirectoryIterator($gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR.'pack')
                as $pack
            ) {
                if ($pack->isFile() && $pack->getExtension() === 'pack') {
                    $this->packs[] = new Pack($pack->getPathname());
                }
            }
        }
    }

    /**
     * Get current HEAD of repository
     *
     * @return HEAD
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

    /**
     * @return array
     */
    protected function getPackedRefs(): array
    {
        $ret = [
            'heads' => [],
            'tags' => []
        ];
        if (file_exists($this->gitRepoPath.DIRECTORY_SEPARATOR.'packed-refs')) {
            $packedRefs = file_get_contents($this->gitRepoPath.DIRECTORY_SEPARATOR.'packed-refs');
            if (preg_match_all('#^([a-z0-9]{40}) refs/(tags|heads)/(.*)$#m', $packedRefs, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $ret[$m[2]][$m[3]] = $m[1];
                }
            }
        }
        return $ret;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        $tags = [];
        $packedRefs = $this->getPackedRefs();
        foreach ($packedRefs['tags'] as $k => $v) {
            $tags[] = new Tag($k, $v);
        }
        $iterator = new \IteratorIterator(new \DirectoryIterator(
            $this->gitRepoPath.DIRECTORY_SEPARATOR.'refs'.DIRECTORY_SEPARATOR.'tags'
        ));
        foreach ($iterator as $i) {
            /** @var $i \DirectoryIterator */
            if (!$i->isDot()) {
                $tags[] = new Tag(
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

    /**
     * @param string $branch
     *
     * @return Commit[]
     */
    public function getHistory(string $branch = 'master'): array
    {
        $queue = [$this->getTip($branch)];
        $merges = $commits = [];
        $masterHashes = [];
        while (!is_null($commitHash = array_shift($queue))) {
            $commit = $this->getCommit($commitHash);
            $parentCount = count($commit->parents);
            if ($parentCount) {
                $queue[] = $commit->parents[0];
                if ($parentCount > 1) {
                    $merges[$commitHash] = array_slice($commit->parents, 1);
                }
            }
            $commits[] = $commit;
            $masterHashes[] = $commitHash;
        }
        $merges = array_reverse($merges);
        foreach ($merges as $commitHash => $mergeHashes) {
            $mergeCommits = $this->getMergeCommits($mergeHashes, $masterHashes);
            foreach ($commits as $k => $commit) {
                if ($commit->commitHash == $commitHash) {
                    $toSplice = [];
                    foreach ($mergeCommits as $mergeCommit) {
                        if (!in_array($mergeCommit->commitHash, $masterHashes)) {
                            $toSplice[] = $mergeCommit;
                            $masterHashes[] = $mergeCommit->commitHash;
                        }
                    }
                    array_splice($commits, $k + 1, 0, $toSplice);
                    break;
                }
            }
        }
        return $commits;
    }

    /**
     * @param string $branch
     *
     * @return string
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

    public function getObject(string $hash): ?GitObject
    {
        $localPath = $this->gitRepoPath
            .DIRECTORY_SEPARATOR.'objects'
            .DIRECTORY_SEPARATOR.substr($hash, 0, 2)
            .DIRECTORY_SEPARATOR.substr($hash, 2);
        $object = GitObject::createFromFile($localPath);
        if ($object === null) {
            /** @var Pack $pack */
            foreach ($this->packs as $pack) {
                $object = $pack->getPackedObject($hash);
                if ($object !== null) {
                    break;
                }
            }
        }
        return $object;
    }

    /**
     * @param string[] $mergeHashes
     * @param string[] $masterHashes
     *
     * @return Commit[]
     */
    protected function getMergeCommits(array $mergeHashes, array $masterHashes): array
    {
        $commits = [];
        $queue = $mergeHashes;
        while (!is_null($commitHash = array_shift($queue))) {
            $commit = $this->getCommit($commitHash);
            foreach ($commit->parents as $p) {
                if (!in_array($p, $masterHashes)) {
                    $queue[] = $p;
                }
            }
            $commits[] = $commit;
        }
        return $commits;
    }

    /**
     * Save git object to filesystem if it doesn't already exist
     */
    public function saveGitObject(GitObject $gitObject): void
    {
        $path = $this->gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR
            .substr($gitObject->getSha1(), 0, 2);
        $objectFile = substr($gitObject->getSha1(), 2);
        if (!file_exists($path.DIRECTORY_SEPARATOR.$objectFile)) {
            @mkdir($path);
            file_put_contents(
                $path.DIRECTORY_SEPARATOR.$objectFile,
                gzcompress((string) $gitObject)
            );
        }
    }
}
