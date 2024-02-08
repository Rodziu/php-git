<?php

namespace Rodziu\Git\Objects;

class HEAD
{
    public function __construct(
        public string $commitHash,
        public ?string $branch = null
    ) {
    }
}
