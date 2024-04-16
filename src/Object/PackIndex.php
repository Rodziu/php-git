<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use Rodziu\Git\Exception\GitException;

readonly class PackIndex
{
    public function __construct(
        private string $packIndexPath
    ) {
        if (!file_exists($packIndexPath)) {
            throw new GitException("`$packIndexPath` does not exist!");
        }
    }

    public function findObjectOffset(string $hash): ?int
    {
        $hash = pack('H*', $hash);
        $indexFileHandle = fopen($this->packIndexPath, 'rb');
        $magic = fread($indexFileHandle, 4);

        if ($magic != "\xFFtOc") { // version 1
            [$cur, $after] = $this->readFanOut($indexFileHandle, $hash);
            $n = $after - $cur;
            if ($n > 0) {
                fseek($indexFileHandle, 4 * 256 + 24 * $cur);
                for ($i = 0; $i < $n; $i++) {
                    $offset = unpack('Nx', fread($indexFileHandle, 4))['x'];
                    $name = fread($indexFileHandle, 20);
                    if (str_starts_with($name, $hash)) {
                        fclose($indexFileHandle);
                        return $offset;
                    }
                }
            }

            return null;
        }

        $version = unpack('Nx', fread($indexFileHandle, 4))['x'];

        if ($version == 2) {
            [$cur, $after] = $this->readFanOut($indexFileHandle, $hash);

            if ($cur != $after) {
                fseek($indexFileHandle, 8 + 4 * 255);
                $totalObjects = unpack('Nx', fread($indexFileHandle, 4))['x'];
                /* look up sha1 */
                fseek($indexFileHandle, 8 + 4 * 256 + 20 * $cur);
                for ($i = $cur; $i < $after; $i++) {
                    $name = fread($indexFileHandle, 20);
                    if (str_starts_with($name, $hash)) {
                        break;
                    }
                }
                if ($i != $after) {
                    fseek($indexFileHandle, 8 + 4 * 256 + 24 * $totalObjects + 4 * $i);
                    $offset = unpack('Nx', fread($indexFileHandle, 4))['x'];
                    if ($offset & 0x80000000) {
                        throw new GitException('64-bit pack files offsets not implemented');
                    }
                    fclose($indexFileHandle);
                    return $offset;
                }
            }
        } else {
            throw new GitException('unsupported pack index format');
        }

        fclose($indexFileHandle);
        return null;
    }

    /**
     * @param resource $f
     * @return int[]
     */
    private function readFanOut($f, string $hash): array
    {
        if ($hash[0] == "\x00") {
            $cur = 0;
            fseek($f, 8);
        } else {
            fseek($f, 8 + (ord($hash[0]) - 1) * 4);
            $cur = unpack('Nx', fread($f, 4))['x'];
        }
        $after = unpack('Nx', fread($f, 4))['x'];
        return [$cur, $after];
    }
}
