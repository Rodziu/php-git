<?php

namespace Rodziu\Git\Types;

class HEAD
{
    /**
     * @var string
     */
    public $commitHash = "";
    /**
     * @var string
     */
    public $branch = null;

    public function __construct(string $commitHash, string $branch = null)
    {
        $this->commitHash = $commitHash;
        $this->branch = $branch;
    }
}
