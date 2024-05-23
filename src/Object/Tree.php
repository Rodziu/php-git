<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use Rodziu\Git\Exception\GitException;
use Rodziu\Git\Manager\GitRepositoryManager;

/**
 * @template-implements  \IteratorAggregate<int, TreeBranch>
 * @template-implements  \ArrayAccess<int, TreeBranch>
 */
class Tree implements \Countable, \ArrayAccess, \IteratorAggregate
{
    /**
     * @var TreeBranch[]
     */
    private array $values;

    public function __construct(
        private readonly GitRepositoryManager $manager,
        TreeBranch ...$branches
    ) {
        $this->values = $branches;
    }

    public static function fromGitObject(GitRepositoryManager $manager, GitObject $gitObject): self
    {
        if ($gitObject->getType() !== GitObject::TYPE_TREE) {
            throw new GitException(
                "Expected GitObject of type `tree`, `{$gitObject->getTypeName()}` given"
            );
        }

        $tree = new self($manager);
        $pointer = 0;
        $stack = $mode = '';
        $data = $gitObject->getData();

        while (isset($data[$pointer])) {
            $char = $data[$pointer];

            if ($char === ' ') {
                $mode = str_pad($stack, 6, '0', STR_PAD_LEFT);
                $stack = '';
            } elseif ($char === "\0") {
                $hash = unpack('H40', substr($data, ++$pointer, 20))[1];
                $tree->values[] = new TreeBranch(
                    $stack,
                    (int) substr($mode, 3),
                    $hash
                );
                $pointer += 20;
                $stack = '';
                continue;
            } else {
                $stack .= $char;
            }

            $pointer++;
        }

        return $tree;
    }

    /**
     * @return \Generator<array{string, TreeBranch, GitObject}>
     */
    public function walkRecursive(string $parentPath = ''): \Generator
    {
        foreach ($this as $branch) {
            $object = $this->manager->getObjectReader()->getObject($branch->getHash());
            yield [$parentPath, $branch, $object];

            if ($object->getType() === GitObject::TYPE_TREE) {
                yield from (Tree::fromGitObject($this->manager, $object))
                    ->walkRecursive(
                        $parentPath.$branch->getName().DIRECTORY_SEPARATOR
                    );
            }
        }
    }

    /**
     * @return \ArrayIterator<int, TreeBranch>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof TreeBranch)) {
            $type = gettype($value);

            /* @phpstan-ignore-next-line */
            if ($type === 'object') {
                $type = get_class($value);
            }

            throw new \TypeError(sprintf(
                'Expected value of `TreeBranch` type, `%s` given',
                $type
            ));
        }

        if (null === $offset) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
    }

    public function offsetGet($offset): TreeBranch
    {
        return $this->values[$offset];
    }

    public function findFileByPath(string $path): ?TreeBranch
    {
        $path = ltrim($path, '/');
        $path = explode('/', $path);
        $tree = $this;

        do {
            $lookFor = array_shift($path);
            $found = false;

            foreach ($tree as $branch) {
                if ($branch->getName() === $lookFor) {
                    if (count($path) === 0) {
                        return $branch;
                    }

                    $object = $this->manager->getObjectReader()->getObject($branch->getHash());
                    $tree = Tree::fromGitObject($this->manager, $object);
                    $found = true;
                    break;
                }
            }
        } while (count($path) > 0 && $found);

        return null;
    }
}
