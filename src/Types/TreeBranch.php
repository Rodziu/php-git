<?php

namespace Rodziu\Git\Types;

class TreeBranch
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var int
     */
    protected $mode;
    /**
     * @var string
     */
    protected $hash;

    public function __construct(string $name, int $mode, string $hash)
    {
        $this->name = $name;
        $this->mode = $mode;
        $this->hash = $hash;
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
