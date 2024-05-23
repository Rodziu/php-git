<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;
use Rodziu\Git\Object\AnnotatedTag;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\Object\GitRef;
use Rodziu\Git\Object\Head;

readonly class GitRefReader
{
    public function __construct(
        private GitRepositoryManager $manager
    ) {
    }

    public function getHead(): Head
    {
        $headPath = $this->manager->resolvePath('HEAD');

        if (!file_exists($headPath)) {
            throw new GitException("Head file does not exist at $headPath!");
        }

        $head = trim(file_get_contents($headPath));

        if (strlen($head) == 40) {
            return new Head($head);
        } elseif (!preg_match('#^ref:\s+(.*)$#su', $head, $match)) {
            throw new GitException("Could not match ref: in $headPath!");
        }

        $branch = preg_replace('#^.*/#', '', $match[1]);

        return new Head(
            trim(file_get_contents(
                $this->manager->resolvePath(trim($match[1]))
            )),
            $branch
        );
    }

    /**
     * @return \Generator<GitRef>
     */
    public function getRefs(?string $type = null, string ...$anotherType): \Generator
    {
        $types = ['remotes', 'heads', 'tags'];

        if ($type !== null) {
            array_unshift($anotherType, $type);
            $types = $anotherType;
        }

        $objectReader = $this->manager->getObjectReader();

        foreach ($types as $currentType) {
            /** @var \RecursiveDirectoryIterator $iterator */
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->manager->resolvePath('refs', $currentType),
                    \FilesystemIterator::SKIP_DOTS
                )
            );

            $iterator->rewind();
            while ($iterator->valid()) {
                $objectHash = trim(file_get_contents($iterator->getPathname()));
                $annotatedTagTargetHash = null;

                if ($currentType === 'tags' && $objectReader->getObjectType($objectHash) === GitObject::TYPE_TAG) {
                    $annotatedTag = AnnotatedTag::fromGitObject($objectReader->getObject($objectHash));
                    $annotatedTagTargetHash = $annotatedTag->getTaggedObjectHash();
                }

                yield new GitRef(
                    $currentType,
                    $iterator->getSubPathName(),
                    $objectHash,
                    $annotatedTagTargetHash
                );
                $iterator->next();
            }
        }

        yield from $this->getPackedRefs(...$types);
    }

    /**
     * @return \Generator<GitRef>
     */
    private function getPackedRefs(string ...$type): \Generator
    {
        $packedRefsPath = $this->manager->resolvePath('packed-refs');

        if (
            file_exists($packedRefsPath)
            && ($fileHandle = fopen($packedRefsPath, 'r')) !== false
        ) {
            try {
                while (($line = fgets($fileHandle)) !== false) {
                    $line = explode(' ', trim($line), 2);

                    if (count($line) !== 2) {
                        continue;
                    }

                    [$targetObjectHash, $referencePath] = $line;

                    if (
                        strlen($targetObjectHash) !== 40
                        || !str_starts_with($referencePath, 'refs/')
                    ) {
                        continue;
                    }

                    $ref = explode('/', $referencePath, 3);

                    if (count($ref) !== 3) {
                        continue;
                    }

                    [, $currentType, $name] = $ref;

                    if (count($type) > 0 && !in_array($currentType, $type)) {
                        continue;
                    }

                    $annotatedTagTargetHash = null;

                    if ($currentType === 'tags') {
                        $currentPosition = ftell($fileHandle);
                        $nextLine = fgets($fileHandle);

                        if ($nextLine !== false && str_starts_with($nextLine, '^')) {
                            $annotatedTagTargetHash = trim(ltrim($nextLine, '^'));
                        } else {
                            fseek($fileHandle, $currentPosition);
                        }
                    }

                    yield new GitRef(
                        $currentType,
                        $name,
                        $targetObjectHash,
                        $annotatedTagTargetHash
                    );
                }
            } finally {
                fclose($fileHandle);
            }
        }
    }

    public function resolveCommitIshToHash(?string $commitIsh = null): string
    {
        $ref = $this->resolveCommitIsh($commitIsh);

        if ($ref instanceof Head) {
            return $ref->getCommitHash();
        } elseif ($ref instanceof GitRef) {
            return $ref->getTargetObjectHash();
        }

        return $ref;
    }

    public function resolveCommitIsh(?string $commitIsh = null): Head|GitRef|string
    {
        if ($commitIsh === null) {
            return $this->getHead();
        }

        if (strlen($commitIsh) === 40) {
            return $commitIsh;
        }

        $refObject = $this->getRefObject($commitIsh);

        if ($refObject !== null) {
            return $refObject;
        }

        $object = $this->manager->getObjectReader()
            ->getObject($commitIsh);

        if ($object === null) {
            throw new GitException("No object matches `$commitIsh`!");
        }

        return $object->getHash();
    }

    public function getRefObject(string $referenceName): ?GitRef
    {
        foreach ($this->getRefs() as $ref) {
            if ($ref->getName() === $referenceName) {
                return $ref;
            }
        }

        return null;
    }

    /**
     * @param GitRef[] $refs
     */
    public function storeRefs(array $refs): void
    {
        $packedRefs = [];

        foreach ($this->getPackedRefs() as $ref) {
            $packedRefs[(string) $ref] = $ref;
        }

        foreach ($refs as $ref) {
            $path = $this->manager->resolvePath('refs', $ref->getType(), $ref->getName());

            if (file_exists($path)) {
                file_put_contents($path, $ref->getTargetObjectHash());
                continue;
            }

            $packedRefs[(string) $ref] = $ref;
        }

        ksort($packedRefs);

        $packedRefs = array_map(function (GitRef $ref) {
            $result = $ref->getTargetObjectHash().' '.$ref;

            if ($ref->getAnnotatedTagTargetHash()) {
                $result .= PHP_EOL.'^'.$ref->getAnnotatedTagTargetHash();
            }

            return $result;
        }, $packedRefs);

        $packedRefsContents = '# pack-refs with: peeled fully-peeled sorted '.PHP_EOL
            .implode(PHP_EOL, $packedRefs).PHP_EOL;

        file_put_contents($this->manager->resolvePath('packed-refs'), $packedRefsContents);
    }
}
