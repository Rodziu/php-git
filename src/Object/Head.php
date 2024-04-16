<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

readonly class Head
{
    public function __construct(
        private string $commitHash,
        private ?string $branch = null
    ) {
    }

    public function getCommitHash(): string
    {
        return $this->commitHash;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }
}
