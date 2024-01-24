<?php

namespace Rodziu\Git\Pack;

use Rodziu\Git\GitException;

class PackIndex
{
    /**
     * @var string
     */
    protected $packIndexPath;

    public function __construct(string $packIndexPath)
    {
        if (!file_exists($packIndexPath)) {
            throw new GitException("`$packIndexPath` does not exist!");
        }
        $this->packIndexPath = $packIndexPath;
    }

    public function findObjectOffset(string $hash): ?int
    {
        $hash = (string) pack('H40', $hash);
        $index = fopen($this->packIndexPath, 'rb');
        $magic = fread($index, 4);
        if ($magic != "\xFFtOc") { // version 1
            list($cur, $after) = $this->readFanOut($index, $hash, 0);
            $n = $after - $cur;
            if ($n > 0) {
                fseek($index, 4 * 256 + 24 * $cur);
                for ($i = 0; $i < $n; $i++) {
                    $offset = unpack('Nx', fread($index, 4))['x'];
                    $name = fread($index, 20);
                    if ($name === $hash) {
                        /* we found the object */
                        fclose($index);
                        return $offset;
                    }
                }
            }
        } else { // version 2
            $version = unpack('Nx', fread($index, 4))['x'];
            if ($version == 2) {
                list($cur, $after) = $this->readFanOut($index, $hash, 8);
                if ($cur != $after) {
                    fseek($index, 8 + 4 * 255);
                    $totalObjects = unpack('Nx', fread($index, 4))['x'];
                    /* look up sha1 */
                    fseek($index, 8 + 4 * 256 + 20 * $cur);
                    for ($i = $cur; $i < $after; $i++) {
                        $name = fread($index, 20);
                        if ($name === $hash) {
                            break;
                        }
                    }
                    if ($i != $after) {
                        fseek($index, 8 + 4 * 256 + 24 * $totalObjects + 4 * $i);
                        $offset = unpack('Nx', fread($index, 4))['x'];
                        if ($offset & 0x80000000) {
                            throw new GitException('64-bit pack files offsets not implemented');
                        }
                        fclose($index);
                        return $offset;
                    }
                }
            } else {
                throw new GitException('unsupported pack index format');
            }
        }
        fclose($index);
        return null;
    }

    private function readFanOut($f, string $hash, int $offset): array
    {
        if ($hash{0} == "\x00") {
            $cur = 0;
            fseek($f, $offset);
        } else {
            fseek($f, $offset + (ord($hash{0}) - 1) * 4);
            $cur = unpack('Nx', fread($f, 4))['x'];
        }
        $after = unpack('Nx', fread($f, 4))['x'];
        return [$cur, $after];
    }
}
