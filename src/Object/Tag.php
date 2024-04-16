<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

readonly class Tag implements \Stringable
{
    public function __construct(
        protected string $name,
        protected string $taggedObjectHash
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTaggedObjectHash(): string
    {
        return $this->taggedObjectHash;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
