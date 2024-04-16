<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use Rodziu\Git\Exception\GitException;

readonly class AnnotatedTag extends Tag
{
    public function __construct(
        string $tag,
        string $taggedObjectHash,
        private string $tagHash,
        private string $type,
        private string $taggerName,
        private string $taggerMail,
        private \DateTimeImmutable $date,
        private string $message
    ) {
        parent::__construct($tag, $taggedObjectHash);
    }

    public function getTagHash(): string
    {
        return $this->tagHash;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTaggerName(): string
    {
        return $this->taggerName;
    }

    public function getTaggerMail(): string
    {
        return $this->taggerMail;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public static function fromGitObject(GitObject $gitObject): AnnotatedTag
    {
        if ($gitObject->getType() !== GitObject::TYPE_TAG) {
            throw new GitException(
                "Expected GitObject of type `tag`, `{$gitObject->getTypeName()}` given"
            );
        }

        $data = $gitObject->parseData();

        return new self(
            $data['tag'],
            $data['object'],
            $gitObject->getHash(),
            $data['type'],
            $data['tagger']['name'],
            $data['tagger']['mail'],
            $data['tagger']['date'],
            $data['message']
        );
    }
}
