<?php

declare(strict_types=1);

namespace Object;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rodziu\Git\Object\PackIndex;
use Rodziu\Git\TestsHelper;

class PackIndexTest extends TestCase
{
    #[DataProvider('findObjectOffsetDataProvider')]
    public function testFindObjectOffset(string $hash, ?int $expected): void
    {
        // Given
        $packIndex = new PackIndex(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'pack.idx');

        // When
        $offset = $packIndex->findObjectOffset($hash);

        // Then
        self::assertSame($expected, $offset);
    }

    /**
     * @return array{string, ?int}[]
     */
    public static function findObjectOffsetDataProvider(): array
    {
        return [
            ['da91da46c59db6fb346635270f59d84aa6917d90', 40249],
            ['da91da46', 40249],
            ['da9', null],
        ];
    }
}
