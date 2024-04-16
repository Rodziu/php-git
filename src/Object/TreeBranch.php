<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

readonly class TreeBranch
{
    public function __construct(
        private string $name,
        private int $mode,
        private string $hash
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
