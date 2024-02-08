<?php

namespace Rodziu\Git\Objects;

use Rodziu\Git\Exception\GitException;

class AnnotatedTag extends Tag
{
    public function __construct(
        string $tag,
        public string $tagHash,
        string $taggedObjectHash,
        public string $type,
        public string $taggerName,
        public string $taggerMail,
        public \DateTimeImmutable $date,
        public string $message
    ) {
        parent::__construct($tag, $taggedObjectHash);
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
            $gitObject->getSha1(),
            $data['object'],
            $data['type'],
            $data['tagger']['name'],
            $data['tagger']['mail'],
            $data['tagger']['date'],
            $data['message']
        );
    }
}
