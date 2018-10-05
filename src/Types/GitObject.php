<?php

namespace Rodziu\Git\Types;

use Rodziu\GenericTypes\GenericStructure;
use Rodziu\Git\GitException;

/**
 * Class GitObject
 * @package Rodziu\Git\Types
 */
class GitObject extends GenericStructure{
	public const TYPE_COMMIT = 1;
	public const TYPE_TREE = 2;
	public const TYPE_BLOB = 3;
	public const TYPE_TAG = 4;
	/**
	 * @var int
	 */
	protected $type;
	/**
	 * @var string
	 */
	protected $sha1;
	/**
	 * @var int
	 */
	protected $size;
	/**
	 * @var string
	 */
	protected $data;

	/**
	 * GitObject constructor.
	 *
	 * @param int $type
	 * @param string $data
	 * @param int|null $size
	 * @param string|null $sha1
	 */
	public function __construct(int $type, string $data, int $size = null, string $sha1 = null){
		$this->type = $type;
		$this->data = $data;
		$this->size = $size === null ? strlen($data) : $size;
		$this->sha1 = $sha1 === null ? $this->calcSHA1() : $sha1;
	}

	/**
	 * @return string
	 */
	public function calcSHA1(){
		return hash('sha1', (string)$this);
	}

	/**
	 * @return int
	 */
	public function getType(): int{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getSha1(): string{
		return $this->sha1;
	}

	/**
	 * @return int
	 */
	public function getSize(): int{
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getData(): string{
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function __toString(): string{
		return $this->getTypeName()." $this->size\0$this->data";
	}

	/**
	 * @return string
	 */
	public function getTypeName(){
		switch($this->type){
			case self::TYPE_COMMIT:
				return 'commit';
			case self::TYPE_TREE:
				return 'tree';
			case self::TYPE_BLOB:
				return 'blob';
			case self::TYPE_TAG:
				return 'tag';
			default:
				throw new GitException("Unknown Git object type $this->type");
		}
	}

	/**
	 * @param string $objectFilePath
	 *
	 * @return null|GitObject
	 */
	public static function createFromFile(string $objectFilePath): ?self{
		if(!file_exists($objectFilePath)){
			return null;
		}
		$data = explode("\0", gzuncompress(file_get_contents($objectFilePath)), 2);
		list($typeName, $size) = explode(" ", $data[0]);
		switch($typeName){
			case 'commit':
				$type = self::TYPE_COMMIT;
				break;
			case 'tree':
				$type = self::TYPE_TREE;
				break;
			case 'blob':
				$type = self::TYPE_BLOB;
				break;
			default:
				return null;
		}
		return new self($type, $data[1], $size);
	}
}