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
	 * @var string
	 */
	protected $hash;

	/**
	 * TreeBranch constructor.
	 *
	 * @param string $name
	 * @param int $mode
	 * @param string $hash
	 */
	public function __construct(string $name, int $mode, string $hash){
		$this->name = $name;
		$this->mode = $mode;
		$this->hash = $hash;
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
	 * @return string
	 */
	public function getHash(): string{
		return $this->hash;
	}
}