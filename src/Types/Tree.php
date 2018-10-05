<?php

namespace Rodziu\Git\Types;

use Rodziu\Git\GitRepository;

/**
 * Class Tree
 * @package Rodziu\Git\Types
 */
class Tree implements \IteratorAggregate{
	/**
	 * @var GitObject
	 */
	protected $gitObject;
	/**
	 * @var GitRepository
	 */
	protected $gitRepo;

	/**
	 * Tree constructor.
	 *
	 * @param GitObject $gitObject
	 * @param GitRepository $gitRepo
	 */
	public function __construct(GitObject $gitObject, GitRepository $gitRepo){
		$this->gitObject = $gitObject;
		$this->gitRepo = $gitRepo;
	}

	/**
	 * Retrieve an external iterator
	 * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return \Generator An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator(): \Generator{
		$pointer = 0;
		$stack = $mode = '';
		$data = $this->gitObject->getData();
		while(isset($data[$pointer])){
			$char = $data[$pointer];
			if($char === ' '){
				$mode = str_pad($stack, 6, '0', STR_PAD_LEFT);
				$stack = '';
			}else if($char === "\0"){
				$hash = unpack('H40', substr($data, ++$pointer, 20))[1];
				yield new TreeBranch(
					$stack, (int)substr($mode, 3), $this->gitRepo->getObject($hash)
				);
				$pointer += 20;
				$stack = '';
				continue;
			}else{
				$stack .= $char;
			}
			$pointer++;
		}
	}

	/**
	 * @param string|null $parentPath
	 *
	 * @return \Generator
	 */
	public function walkRecursive(string $parentPath = DIRECTORY_SEPARATOR): \Generator{
		/** @var TreeBranch $branch */
		foreach($this as $branch){
			if($branch->getObject()->getType() === GitObject::TYPE_TREE){
				yield $parentPath => $branch;
				yield from (new Tree($branch->getObject(), $this->gitRepo))
					->walkRecursive($parentPath.$branch->getName().DIRECTORY_SEPARATOR);
			}else{ // blob
				yield $parentPath => $branch;
			}
		}
	}
}