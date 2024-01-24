<?php

namespace Rodziu\Git\Pack;

use PHPUnit\Framework\TestCase;
use Rodziu\Git\TestsHelper;

class PackIndexTest extends TestCase
{
    public function testFindObjectOffset(): void
    {
        $packIndex = new PackIndex(TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'pack.idx');
        self::assertSame(
            40249, $packIndex->findObjectOffset('da91da46c59db6fb346635270f59d84aa6917d90')
        );
    }
}
