<?php

namespace Rodziu\Git\Objects;

use Rodziu\Git\Exception\GitException;

class Commit
{
    public function __construct(
        public string $commitHash,
        public string $tree,
        public array $parents,
        public string $authorName,
        public string $authorMail,
        public \DateTimeImmutable $authorDate,
        public string $committerName,
        public string $committerMail,
        public \DateTimeImmutable $commitDate,
        public string $message,
    ) {
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

        if (null !== $data['parent']) {
            $parents = is_array($data['parent']) ? $data['parent'] : [$data['parent']];
        }

        return new self(
            $gitObject->getSha1(),
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

    public static function fromCommitString(string $commitHash, string $commitData): self
    {

        $commit = new self($commitHash);
        $commitLines = explode("\n", $commitData);
        $message = [];

        foreach ($commitLines as $line) {
            if (preg_match('#^(tree|parent|author|committer)\s(.*)$#', $line, $match)) {
                [, $type, $content] = $match;
                switch ($type) {
                    case 'tree':
                        $commit->tree = $content;
                        break;
                    case 'parent':
                        $commit->parents[] = $content;
                        break;
                    case 'author':
                    case 'committer':
                        if (preg_match(
                            "#(?<name>.+)\s<(?<mail>[^>]+)>\s(?<timestamp>\d+)\s(?<offset>[+-]\d{4})#",
                            $content,
                            $m
                        )) {
                            try {
                                $date = (new \DateTimeImmutable(
                                    '',
                                    new \DateTimeZone($m['offset'])
                                ))
                                    ->setTimestamp($m['timestamp']);
                            } catch (\Exception $e) {
                                throw new GitException(
                                    'Cannot instantiate date object for commit!',
                                    0,
                                    $e
                                );
                            }

                            if ($type === 'author') {
                                $commit->authorDate = $date;
                                $commit->authorName = $m['name'];
                                $commit->authorMail = $m['mail'];
                            } else {
                                $commit->commitDate = $date;
                                $commit->committerName = $m['name'];
                                $commit->committerMail = $m['mail'];
                            }
                        }
                        break;
                }
            } else if (!empty($line)) {
                $message[] = $line;
            }
        }

        $commit->message = implode("\n", $message);

        return $commit;
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
                .str_pad($tzOffset, 4, '0', STR_PAD_LEFT);
            return "{$date->getTimestamp()} $tzOffset";
        };

        $data .= "\nauthor {$this->authorName} <{$this->authorMail}> {$getTime($this->authorDate)}";
        $data .= "\ncommitter {$this->committerName} <{$this->committerMail}> {$getTime($this->commitDate)}";
        $data .= "\n\n{$this->message}\n";
        return $data;
    }
}
