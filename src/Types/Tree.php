<?php

namespace Rodziu\Git\Types;

use Rodziu\Git\GitRepository;

class Tree implements \Countable, \ArrayAccess, \IteratorAggregate
{
    /**
     * @var TreeBranch[]
     */
    private $values;

    public function __construct(TreeBranch ...$branches)
    {
        $this->values = $branches;
    }

    public static function fromGitObject(GitObject $gitObject): self
    {
        $tree = new self();
        $pointer = 0;
        $stack = $mode = '';
        $data = $gitObject->getData();
        while (isset($data[$pointer])) {
            $char = $data[$pointer];
            if ($char === ' ') {
                $mode = str_pad($stack, 6, '0', STR_PAD_LEFT);
                $stack = '';
            } else if ($char === "\0") {
                $hash = unpack('H40', substr($data, ++$pointer, 20))[1];
                $tree->values[] = new TreeBranch(
                    $stack, (int) substr($mode, 3), $hash
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

    public function walkRecursive(GitRepository $gitRepository, string $parentPath = DIRECTORY_SEPARATOR): \Generator
    {
        /** @var TreeBranch $branch */
        foreach ($this as $branch) {
            $object = $gitRepository->getObject($branch->getHash());
            yield [$parentPath, $branch, $object];

            if ($object->getType() === GitObject::TYPE_TREE) {
                yield from (Tree::fromGitObject($object))
                    ->walkRecursive(
                        $gitRepository, $parentPath.$branch->getName().DIRECTORY_SEPARATOR
                    );
            }
        }
    }

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
}
