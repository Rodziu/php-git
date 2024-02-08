<?php

namespace Rodziu\Git\Objects;

class Tag
{
    public function __construct(
        public string $tag,
        public string $taggedObjectHash
    ) {
    }
}
