<?php

namespace Rodziu\Git\Objects;

use Rodziu\Git\Exception\GitException;

class GitObject
{
    // https://git-scm.com/docs/pack-format#_object_types
    public const TYPE_COMMIT = 1;
    public const TYPE_TREE = 2;
    public const TYPE_BLOB = 3;
    public const TYPE_TAG = 4;
    public const TYPE_OFS_DELTA = 6;
    public const TYPE_REF_DELTA = 7;

    protected readonly int $size;
    protected readonly string $sha1;

    public function __construct(
        protected readonly int $type,
        protected readonly string $data,
        int $size = null,
        string $sha1 = null
    ) {
        $this->size = $size === null ? strlen($this->data) : $size;
        $this->sha1 = $sha1 === null ? $this->calcSHA1() : $sha1;
    }

    public function calcSHA1(): string
    {
        return hash('sha1', (string) $this);
    }

    public function __toString(): string
    {
        return $this->getTypeName()." $this->size\0$this->data";
    }

    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_COMMIT => 'commit',
            self::TYPE_TREE => 'tree',
            self::TYPE_BLOB => 'blob',
            self::TYPE_TAG => 'tag',
            default => throw new GitException("Unknown Git object type $this->type"),
        };
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getSha1(): string
    {
        return $this->sha1;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public static function createFromFile(string $objectFilePath): ?self
    {
        if (!file_exists($objectFilePath)) {
            return null;
        }
        $data = explode("\0", gzuncompress(file_get_contents($objectFilePath)), 2);
        [$typeName, $size] = explode(" ", $data[0]);

        switch ($typeName) {
            case 'commit':
                $type = self::TYPE_COMMIT;
                break;
            case 'tree':
                $type = self::TYPE_TREE;
                break;
            case 'blob':
                $type = self::TYPE_BLOB;
                break;
            case 'tag':
                $type = self::TYPE_TAG;
                break;
            default:
                return null;
        }

        $sha1 = basename(dirname($objectFilePath)).basename($objectFilePath);

        return new self($type, $data[1], $size, strlen($sha1) === 40 ? $sha1 : null);
    }

    public function parseData(): array
    {
        $lines = explode("\n", $this->data);
        $metadataEnd = false;
        $parsed = [];
        $messageLines = [];

        foreach ($lines as $line) {
            if ($line === '') {
                $metadataEnd = true;
                continue;
            }

            if ($metadataEnd) {
                $messageLines[] = $line;
                continue;
            }

            [$type, $content] = explode(' ', $line, 2);

            if (preg_match(
                "#(?<name>.+)\s<(?<mail>[^>]+)>\s(?<timestamp>\d+)\s(?<offset>[+-]\d{4})#",
                $content,
                $match
            )) {
                $content = [
                    'name' => $match['name'],
                    'mail' => $match['mail'],
                    'date' => $this->getDate($match['timestamp'], $match['offset'])
                ];
            }

            if (array_key_exists($type, $parsed)) {
                if (!is_array($parsed[$type])) {
                    $parsed[$type] = [$parsed[$type]];
                }

                $parsed[$type][] = $content;
            } else {
                $parsed[$type] = $content;
            }
        }

        $parsed['message'] = implode("\n", $messageLines);

        return $parsed;
    }

    private function getDate(string $timestamp, string $offset): \DateTimeImmutable
    {
        try {
            return (new \DateTimeImmutable(
                '',
                new \DateTimeZone($offset)
            ))
                ->setTimestamp($timestamp);
        } catch (\Exception $e) {
            throw new GitException(
                'Cannot instantiate date object for commit!',
                0,
                $e
            );
        }
    }
}
