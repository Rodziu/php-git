<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use Rodziu\Git\Exception\GitException;

readonly class Commit implements \Stringable
{
    /**
     * @param string[] $parents
     */
    public function __construct(
        private string $commitHash,
        private string $tree,
        private array $parents,
        private string $authorName,
        private string $authorMail,
        private \DateTimeImmutable $authorDate,
        private string $committerName,
        private string $committerMail,
        private \DateTimeImmutable $commitDate,
        private string $message,
    ) {
    }

    public function getCommitHash(): string
    {
        return $this->commitHash;
    }

    public function getTree(): string
    {
        return $this->tree;
    }

    /**
     * @return string[]
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getAuthorMail(): string
    {
        return $this->authorMail;
    }

    public function getAuthorDate(): \DateTimeImmutable
    {
        return $this->authorDate;
    }

    public function getCommitterName(): string
    {
        return $this->committerName;
    }

    public function getCommitterMail(): string
    {
        return $this->committerMail;
    }

    public function getCommitDate(): \DateTimeImmutable
    {
        return $this->commitDate;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function __toString(): string
    {
        $data = "tree {$this->tree}";

        foreach ($this->parents as $parent) {
            $data .= "\nparent $parent";
        }

        $getTime = function (\DateTimeImmutable $date) {
            $tzOffset = $date->getOffset() / 36;
            $tzOffset = ($tzOffset > 0 ? '+' : '-')
                .str_pad((string) $tzOffset, 4, '0', STR_PAD_LEFT);
            return "{$date->getTimestamp()} $tzOffset";
        };

        $data .= "\nauthor {$this->authorName} <{$this->authorMail}> {$getTime($this->authorDate)}";
        $data .= "\ncommitter {$this->committerName} <{$this->committerMail}> {$getTime($this->commitDate)}";
        $data .= "\n\n{$this->message}\n";
        return $data;
    }

    public static function fromGitObject(GitObject $gitObject): Commit
    {
        if ($gitObject->getType() !== GitObject::TYPE_COMMIT) {
            throw new GitException(
                "Expected GitObject of type `commit`, `{$gitObject->getTypeName()}` given"
            );
        }

        $data = $gitObject->parseData();
        $parents = [];

        if (array_key_exists('parent', $data) && $data['parent'] !== null) {
            $parents = is_array($data['parent']) ? $data['parent'] : [$data['parent']];
        }

        return new self(
            $gitObject->getHash(),
            $data['tree'],
            $parents,
            $data['author']['name'],
            $data['author']['mail'],
            $data['author']['date'],
            $data['committer']['name'],
            $data['committer']['mail'],
            $data['committer']['date'],
            $data['message']
        );
    }
}
