<?php

namespace Rodziu\Git\Types;

use Rodziu\GenericTypes\GenericArray;
use Rodziu\Git\GitRepository;

/**
 * Class Tree
 * @package Rodziu\Git\Types
 */
class Tree extends GenericArray{
	/**
	 * Tree constructor.
	 *
	 * @param TreeBranch ...$branches
	 */
	public function __construct(TreeBranch ...$branches){
		parent::__construct();
		$this->values = $branches;
	}

	/**
	 * @param GitObject $gitObject
	 *
	 * @return Tree
	 */
	public static function fromGitObject(GitObject $gitObject): self{
		$tree = new self();
		$pointer = 0;
		$stack = $mode = '';
		$data = $gitObject->getData();
		while(isset($data[$pointer])){
			$char = $data[$pointer];
			if($char === ' '){
				$mode = str_pad($stack, 6, '0', STR_PAD_LEFT);
				$stack = '';
			}else if($char === "\0"){
				$hash = unpack('H40', substr($data, ++$pointer, 20))[1];
				$tree->values[] = new TreeBranch(
					$stack, (int)substr($mode, 3), $hash
				);
				$pointer += 20;
				$stack = '';
				continue;
			}else{
				$stack .= $char;
			}
			$pointer++;
		}
		return $tree;
	}

	/**
	 * @param GitRepository $gitRepository
	 * @param string|null $parentPath
	 *
	 * @return \Generator
	 */
	public function walkRecursive(GitRepository $gitRepository, string $parentPath = DIRECTORY_SEPARATOR): \Generator{
		/** @var TreeBranch $branch */
		foreach($this as $branch){
			$object = $gitRepository->getObject($branch->getHash());
			if($object->getType() === GitObject::TYPE_TREE){
				yield [$parentPath, $branch, $object];
				yield from (Tree::fromGitObject($object))
					->walkRecursive(
						$gitRepository, $parentPath.$branch->getName().DIRECTORY_SEPARATOR
					);
			}else{ // blob
				yield [$parentPath, $branch, $object];
			}
		}
	}
}