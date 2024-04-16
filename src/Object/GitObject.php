<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use Rodziu\Git\Exception\GitException;

class GitObject implements \Stringable
{
    // https://git-scm.com/docs/pack-format#_object_types
    public const TYPE_COMMIT = 1;
    public const TYPE_TREE = 2;
    public const TYPE_BLOB = 3;
    public const TYPE_TAG = 4;
    public const TYPE_OFS_DELTA = 6;
    public const TYPE_REF_DELTA = 7;


    public function __construct(
        private readonly int $type,
        private ?int $size = null,
        private ?string $data = null,
        private ?string $hash = null,
        private readonly ?string $filePath = null
    ) {
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getSize(): int
    {
        if ($this->size === null) {
            $this->size = strlen($this->getData());
        }

        return $this->size;
    }

    public function getData(): string
    {
        if (
            $this->data === null
            && $this->filePath !== null
        ) {
            foreach (self::iterateFileContents($this->filePath) as $key => $value) {
                if ($key === 'data') {
                    $this->data = $value;
                    break;
                }
            }
        }

        return $this->data;
    }

    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = $this->calcSHA1();
        }

        return $this->hash;
    }

    public function calcSHA1(): string
    {
        return hash('sha1', (string) $this);
    }

    public function __toString(): string
    {
        return "{$this->getTypeName()} {$this->getSize()}\0{$this->getData()}";
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

    public static function createFromFile(string $objectFilePath): ?self
    {
        if (!file_exists($objectFilePath)) {
            return null;
        }

        $type = null;

        foreach (self::iterateFileContents($objectFilePath) as $key => $value) {
            if ($key === 'type') {
                $type = self::typeNameToType($value);
            } elseif ($key === 'size') {
                return new GitObject($type, $value, filePath: $objectFilePath);
            }
        }

        return null;
    }

    public static function iterateFileContents(string $objectFilePath): \Generator
    {
        $fileHandle = fopen($objectFilePath, 'r');

        if ($fileHandle) {
            try {
                $inflateContext = inflate_init(ZLIB_ENCODING_DEFLATE);
                $type = $size = null;
                $stack = '';

                do {
                    $char = fgetc($fileHandle);
                    $stack .= inflate_add($inflateContext, $char);

                    if ($type === null && str_contains($stack, ' ')) {
                        [$type, $stack] = explode(' ', $stack, 2);
                        yield 'type' => $type;
                    } elseif ($type !== null && $size === null && str_contains($stack, "\0")) {
                        [$size, $stack] = explode("\0", $stack, 2);
                        yield 'size' => (int) $size;
                    }
                } while ($char !== false && inflate_get_status($inflateContext) === ZLIB_OK);

                yield 'data' => $stack;
            } finally {
                fclose($fileHandle);
            }
        }
    }

    public static function getObjectTypeFromFile(string $objectFilePath): ?int
    {
        foreach (self::iterateFileContents($objectFilePath) as $key => $value) {
            if ($key === 'type') {
                return self::typeNameToType($value);
            }
        }

        return null;
    }

    public static function typeNameToType(string $typeName): int
    {
        return match ($typeName) {
            'commit' => self::TYPE_COMMIT,
            'tree' => self::TYPE_TREE,
            'blob' => self::TYPE_BLOB,
            'tag' => self::TYPE_TAG,
            default => throw new GitException("Unknown Git object type `$typeName`"),
        };
    }

    /**
     * @return array<string, array{name: string, mail:string, date: \DateTimeImmutable}|string>
     */
    public function parseData(): array
    {
        $lines = explode("\n", $this->getData());
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
                    'date' => $this->getDate((int) $match['timestamp'], $match['offset'])
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

    private function getDate(int $timestamp, string $offset): \DateTimeImmutable
    {
        try {
            return (new \DateTimeImmutable(
                '',
                new \DateTimeZone($offset)
            ))
                ->setTimestamp($timestamp);
        } catch (\Exception $e) {
            throw new GitException(
                'Cannot instantiate date object!',
                0,
                $e
            );
        }
    }
}
