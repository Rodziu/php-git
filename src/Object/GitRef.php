<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

readonly class GitRef implements \Stringable
{
    public function __construct(
        private string $type,
        private string $name,
        private ?string $targetObjectHash = null,
        private ?string $annotatedTagTargetHash = null
    ) {

    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTargetObjectHash(): ?string
    {
        return $this->targetObjectHash;
    }

    public function getAnnotatedTagTargetHash(): ?string
    {
        return $this->annotatedTagTargetHash;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
