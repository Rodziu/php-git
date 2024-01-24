<?php

namespace Rodziu\Git\Types;

class Tag
{
    /**
     * @var string
     */
    public $tag = "";
    /**
     * @var string
     */
    public $commit = "";

    public function __construct(string $tag, string $commit)
    {
        $this->tag = $tag;
        $this->commit = $commit;
    }
}
