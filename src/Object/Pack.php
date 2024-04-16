<?php

declare(strict_types=1);

namespace Rodziu\Git\Object;

use Rodziu\Git\Exception\GitException;

/**
 * @implements \IteratorAggregate<int, GitObject>
 */
class Pack implements \IteratorAggregate
{
    private readonly ?PackIndex $packIndex;
    /**
     * @var null|resource
     */
    private $packFileHandle = null;
    /**
     * @var array{magic?: string, version?: int, objectCount?: int}
     */
    private array $packHeader = [];

    public function __construct(
        private readonly string $packFilePath
    ) {
        if (!file_exists($packFilePath)) {
            throw new GitException("`$packFilePath` does not exist!");
        }

        $indexPath = preg_replace('#\.pack$#', ".idx", $packFilePath);

        try {
            $this->packIndex = new PackIndex($indexPath);
        } catch (GitException) {
            $this->packIndex = null;
        }
    }

    public function getChecksum(): string
    {
        $this->open();
        fseek($this->packFileHandle, -20, SEEK_END);
        return unpack('H*', fread($this->packFileHandle, 20))[1];
    }

    /**
     * Open a pack file and read its header
     */
    private function open(): void
    {
        if ($this->packFileHandle !== null) {
            return;
        }

        $this->packFileHandle = fopen($this->packFilePath, 'rb');
        $magic = fread($this->packFileHandle, 4);
        $version = unpack('Nx', fread($this->packFileHandle, 4))['x'];
        $objectCount = unpack('Nx', fread($this->packFileHandle, 4))['x'];

        if ($magic !== 'PACK' || $version !== 2) {
            throw new GitException('unsupported pack format');
        }

        $this->packHeader = [
            'magic' => $magic,
            'version' => $version,
            'objectCount' => $objectCount
        ];
    }

    public function getIterator(): \Generator
    {
        $this->open();

        try {
            fseek($this->packFileHandle, 12);
            $index = [];
            $cnt = 0;
            $objectOffset = ftell($this->packFileHandle);

            do {
                $object = $this->unpackObject($objectOffset, $index);
                yield $object;
                $index[$object->getHash()] = $objectOffset;
                $objectOffset = ftell($this->packFileHandle);
                ++$cnt;
            } while ($cnt < $this->packHeader['objectCount']);
        } finally {
            $this->close();
        }
    }

    /**
     * @param array<string, int>|null $runtimeIndex
     */
    public function unpackObject(
        int $offset,
        array $runtimeIndex = null
    ): GitObject {
        $this->open();
        fseek($this->packFileHandle, $offset);
        // decode object type and size
        $c = fgetc($this->packFileHandle);
        $bin = str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        $type = bindec(substr($bin, 1, 3));
        $size = substr($bin, 4);
        while ($bin[0] == '1') {
            $c = fgetc($this->packFileHandle);
            $bin = str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
            $size = substr($bin, 1).$size;
        }
        $size = bindec($size);
        $refObject = null;

        if ($type === GitObject::TYPE_OFS_DELTA) {
            $deltaOffset = -1;
            do {
                $deltaOffset++;
                $c = ord(fgetc($this->packFileHandle));
                $deltaOffset = ($deltaOffset << 7) + ($c & 0x7F);
            } while ($c & 0x80);
            $baseOffset = $offset - $deltaOffset;
            $dataOffset = ftell($this->packFileHandle);
            $refObject = $this->unpackObject($baseOffset);
            // set internal file pointer back to last position
            fseek($this->packFileHandle, $dataOffset);
        } elseif ($type === GitObject::TYPE_REF_DELTA) {
            $dd = fread($this->packFileHandle, 20);
            $refSHA = unpack('H*', $dd)[1];
            $dataOffset = ftell($this->packFileHandle);
            if ($runtimeIndex !== null) {
                $refObject = $this->unpackObject($runtimeIndex[$refSHA], $runtimeIndex);
            } else {
                $refObject = $this->getPackedObject($refSHA);
            }
            // set internal file pointer back to last position
            fseek($this->packFileHandle, $dataOffset);
        }

        // decode object contents
        $inflateContext = inflate_init(ZLIB_ENCODING_DEFLATE);
        $data = '';

        do {
            $char = fgetc($this->packFileHandle);
            $data .= inflate_add($inflateContext, $char);
        } while ($char !== false && inflate_get_status($inflateContext) === ZLIB_OK);

        if ($refObject) {
            return new GitObject(
                $refObject->getType(),
                data: $this->applyDelta($data, $refObject->getData())
            );
        }

        return new GitObject($type, $size, $data);
    }

    public function getPackedObject(string $hash): ?GitObject
    {
        if ($this->packIndex) {
            $offset = $this->packIndex->findObjectOffset($hash);

            if ($offset === null) {
                return null;
            }

            return $this->unpackObject($offset);
        }

        foreach ($this as $gitObject) {
            if ($gitObject->getHash() === $hash) {
                return $gitObject;
            }
        }

        return null;
    }

    /**
     * Apply delta to Git object
     */
    private function applyDelta(string $delta, string $base): string
    {
        $pos = 0;
        $getCode = function () use (&$pos, $delta) {
            return ord($delta[$pos++]);
        };
        $getBin = function ($number) {
            return str_pad(decbin($number), 8, '0', STR_PAD_LEFT);
        };
        $gitVarInt = function ($str, &$pos = 0) {
            $r = 0;
            $c = 0x80;
            for ($i = 0; $c & 0x80; $i += 7) {
                $c = ord($str[$pos++]);
                $r |= (($c & 0x7F) << $i);
            }
            return $r;
        };

        $gitVarInt($delta, $pos); // base size
        $gitVarInt($delta, $pos); // result size
        $result = '';

        while (isset($delta[$pos])) {
            $opCode = $getCode();
            $opCodeBin = $getBin($opCode);

            if ($opCodeBin[0]) {
                // copy
                $offset = $length = '';
                for ($i = 7; $i > 1; $i--) {
                    $cur = $getBin($opCodeBin[$i] ? $getCode() : 0);
                    if ($i > 3) { // offset bytes
                        $offset = $cur.$offset;
                    } else { // length bytes
                        $length = $cur.$length;
                    }
                }
                $offset = bindec($offset);
                $length = bindec($length);
                if ($length === 0) {
                    $length = 65536;
                }
                $result .= substr($base, $offset, $length);
            } else {
                $result .= substr($delta, $pos, $opCode);
                $pos += $opCode;
            }
        }
        return $result;
    }

    public function __destruct()
    {
        if ($this->packFileHandle !== null) {
            $this->close();
        }
    }

    private function close(): void
    {
        @fclose($this->packFileHandle);
        $this->packFileHandle = null;
    }
}
