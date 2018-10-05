<?php

namespace Rodziu\Git\Types;

use Rodziu\GenericTypes\GenericStructure;

/**
 * Class TreeBranch
 * @package Rodziu\Git\Types
 */
class TreeBranch extends GenericStructure{
	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @var int
	 */
	protected $mode;
	/**
	 * @var GitObject
	 */
	protected $object;

	/**
	 * TreeBranch constructor.
	 *
	 * @param string $name
	 * @param int $mode
	 * @param GitObject $object
	 */
	public function __construct(string $name, int $mode, GitObject $object){
		$this->name = $name;
		$this->mode = $mode;
		$this->object = $object;
	}

	/**
	 * @return string
	 */
	public function getName(): string{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getMode(): int{
		return $this->mode;
	}

	/**
	 * @return GitObject
	 */
	public function getObject(): GitObject{
		return $this->object;
	}
}