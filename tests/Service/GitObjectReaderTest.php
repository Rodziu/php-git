<?php
declare(strict_types=1);

namespace Rodziu\Git\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\Object\GitObject;
use Rodziu\Git\TestsHelper;

class GitObjectReaderTest extends TestCase
{
    private GitObjectReader $objectReader;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = TestsHelper::getGitRepositoryManager();
        $this->objectReader = new GitObjectReader($manager);
    }

    #[DataProvider('getObjectTypeDataProvider')]
    public function testGetObjectType(string $hash, int $expectedType): void
    {
        // Given (prepared at setUp)

        // When
        $type = $this->objectReader->getObjectType($hash);

        // Then
        $this->assertEquals($expectedType, $type);
    }

    /**
     * @return array{hash: string, expectedType: int}[]
     */
    public static function getObjectTypeDataProvider(): array
    {
        return [
            'commit' => [
                'hash' => 'e0d5e0b30030060c2cc8e1e131b80d576ddcfea7',
                'expectedType' => GitObject::TYPE_COMMIT
            ],
            'commit-ish' => [
                'hash' => 'e0d5e0b3',
                'expectedType' => GitObject::TYPE_COMMIT
            ],
            'tree' => [
                'hash' => '4ac978f076c8654bfd365838bad72608792a287c',
                'expectedType' => GitObject::TYPE_TREE
            ],
            'blob' => [
                'hash' => '69fe54ed84d7ba9ca428ca7dd1b19cceb2d4a2be',
                'expectedType' => GitObject::TYPE_BLOB
            ],
            'tag' => [
                'hash' => '991c9a8db826196699a43776e43da3dd09e31dd5',
                'expectedType' => GitObject::TYPE_TAG
            ],
        ];
    }

    #[DataProvider('getObjectDataProvider')]
    public function testGetObject(string $hash, string $expectedSha1, string $expectedTypeName): void
    {
        // Given (prepared at setUp)

        // When
        $gitObject = $this->objectReader->getObject($hash);

        // Then
        $this->assertEquals($expectedSha1, $gitObject->getHash());
        $this->assertEquals($expectedTypeName, $gitObject->getTypeName());
    }

    /**
     * @return array{hash: string, expectedSha1: string, expectedTypeName: string}[]
     */
    public static function getObjectDataProvider(): array
    {
        return [
            'object' => [
                'hash' => 'e0d5e0b30030060c2cc8e1e131b80d576ddcfea7',
                'expectedSha1' => 'e0d5e0b30030060c2cc8e1e131b80d576ddcfea7',
                'expectedTypeName' => 'commit'
            ],
            'object commit-ish' => [
                'hash' => 'e0d5e0b3',
                'expectedSha1' => 'e0d5e0b30030060c2cc8e1e131b80d576ddcfea7',
                'expectedTypeName' => 'commit'
            ],
        ];
    }
}
