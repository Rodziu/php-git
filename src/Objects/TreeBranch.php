<?php

namespace Rodziu\Git\Objects;

readonly class TreeBranch
{
    public function __construct(
        protected string $name,
        protected int $mode,
        protected string $hash
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
