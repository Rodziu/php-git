<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\Commit;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\Pack;
use Rodziu\Git\Object\Tree;

readonly class GitObjectReader
{
    public function __construct(
        private GitRepositoryManager $manager
    ) {
    }

    public function getObjectType(string $hash): ?int
    {
        $localPath = $this->getLocalObjectPath($hash);

        if ($localPath && file_exists($localPath)) {
            return GitObject::getObjectTypeFromFile($localPath);
        }

        foreach ($this->getPacks() as $pack) {
            $object = $pack->getPackedObject($hash);

            if ($object !== null) {
                return $object->getType();
            }
        }

        return null;
    }

    private function getLocalObjectPath(string $hash): ?string
    {
        $localPath = $this->manager->resolvePath(
            'objects',
            substr($hash, 0, 2)
        );
        $hash = substr($hash, 2);

        if (!is_dir($localPath)) {
            return null;
        }

        if (strlen($hash) === 40) {
            $localPath .= DIRECTORY_SEPARATOR.$hash;
        } else {
            $possiblePaths = glob($localPath.DIRECTORY_SEPARATOR.$hash.'*');

            if ($possiblePaths !== false && count($possiblePaths) === 1) {
                $localPath = $possiblePaths[0];
            }
        }

        return $localPath;
    }

    /**
     * @return Pack[]
     */
    private function getPacks(): array
    {
        if (($packs = $this->manager->getPacks()) !== null) {
            return $packs;
        }

        $packs = [];
        $packsDirectory = $this->manager->resolvePath('objects', 'pack');

        if (is_dir($packsDirectory)) {
            /** @var \SplFileInfo $packFile */
            foreach (new \DirectoryIterator($packsDirectory) as $packFile) {
                if ($packFile->isFile() && $packFile->getExtension() === 'pack') {
                    $packs[] = new Pack($packFile->getPathname());
                }
            }
        }

        $this->manager->setPacks($packs);

        return $packs;
    }

    public function getObject(string $hash): ?GitObject
    {
        $object = $this->getLocalObject($hash);

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

    private function getLocalObject(string $hash): ?GitObject
    {
        $localPath = $this->getLocalObjectPath($hash);

        return $localPath ? GitObject::createFromFile($localPath) : null;
    }

    public function getCommit(string $commitHash): Commit
    {
        $object = $this->getObject($commitHash);

        if ($object === null || $object->getType() !== GitObject::TYPE_COMMIT) {
            throw new GitException("Commit $commitHash does not exist!");
        }

        return Commit::fromGitObject($object);
    }

    public function getTree(string $treeHash): Tree
    {
        $treeObject = $this->getObject($treeHash);

        if ($treeObject === null || $treeObject->getType() !== GitObject::TYPE_TREE) {
            throw new GitException("Tree $treeHash does not exist!");
        }

        return Tree::fromGitObject($this->manager, $treeObject);
    }
}
