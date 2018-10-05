<?php
/**
 *    _______ __     _    __               _
 *   / ____(_) /_   | |  / /__  __________(_)___  ____
 *  / / __/ / __/   | | / / _ \/ ___/ ___/ / __ \/ __ \
 * / /_/ / / /_     | |/ /  __/ /  (__  ) / /_/ / / / /
 * \____/_/\__/     |___/\___/_/  /____/_/\____/_/ /_/
 *
 * @author Rodziu <mateusz.rohde@gmail.com>
 * @copyright Copyright (c) 2017.
 */

namespace Rodziu\Git\Types;

use Rodziu\GenericTypes\ArrayOfString;
use Rodziu\GenericTypes\GenericStructure;
use Rodziu\Git\GitException;

/**
 * Class Commit
 * @package Rodziu\Git\Types
 */
class Commit extends GenericStructure{
	/**
	 * @var string
	 */
	public $commitHash = "";
	/**
	 * @var string
	 */
	public $tree = "";
	/**
	 * @var ArrayOfString
	 */
	public $parents;
	/**
	 * @var string
	 */
	public $authorName = "";
	/**
	 * @var string
	 */
	public $authorMail = "";
	/**
	 * @var \DateTimeImmutable
	 */
	public $date;
	/**
	 * @var string
	 */
	public $committerName = "";
	/**
	 * @var string
	 */
	public $committerMail = "";
	/**
	 * @var string
	 */
	public $message = "";

	/**
	 * Commit constructor.
	 *
	 * @param string $commitHash
	 * @param string $tree
	 * @param ArrayOfString $parents
	 * @param \DateTimeImmutable $date
	 * @param string $authorName
	 * @param string $authorMail
	 * @param string $committerName
	 * @param string $committerMail
	 * @param string $message
	 */
	public function __construct(
		string $commitHash, string $tree, ArrayOfString $parents, \DateTimeImmutable $date, string $authorName, string $authorMail,
		string $committerName, string $committerMail, string $message
	){
		$this->commitHash = $commitHash;
		$this->tree = $tree;
		$this->parents = $parents;
		$this->date = $date;
		$this->authorName = $authorName;
		$this->authorMail = $authorMail;
		$this->committerName = $committerName;
		$this->committerMail = $committerMail;
		$this->message = $message;
	}

	/**
	 * @param GitObject $gitObject
	 *
	 * @return \Rodziu\GenericTypes\GenericTypeInterface|Commit
	 */
	public static function fromGitObject(GitObject $gitObject){
		if($gitObject->getType() !== GitObject::TYPE_COMMIT){
			throw new GitException(
				"Expected GitObject of type `commit`, `{$gitObject->getTypeName()}` given"
			);
		}
		$commit = explode("\n", $gitObject->getData());
		$array = [
			'commitHash' => $gitObject->getSha1(), 'parents' => new ArrayOfString(...[]), 'message' => []
		];
		foreach($commit as $line){
			if(preg_match('#^(tree|parent|author|committer)\s(.*)$#', $line, $match)){
				switch($match[1]){
					case 'tree':
						$array['tree'] = $match[2];
						break;
					case 'parent':
						$array['parents'][] = $match[2];
						break;
					case 'author':
					case 'committer':
						if(preg_match(/** @lang text */
							"#(?<name>.+)\s<(?<mail>[^>]+)>\s(?<timestamp>\d+)\s(?<offset>[+-]\d{4})#",
							$match[2],
							$m
						)){
							try{
								$array['date'] = (new \DateTimeImmutable(
									'',
									new \DateTimeZone($m['offset'])
								))->setTimestamp($m['timestamp']);
							}catch(\Exception $e){
								throw new \LogicException($e);
							}
							$array[$match[1].'Name'] = $m['name'];
							$array[$match[1].'Mail'] = $m['mail'];
						}
						break;
				}
			}else if(!empty($line)){
				$array['message'][] = $line;
			}
		}
		$array['message'] = implode("\n", $array['message']);
		return self::fromArray($array);
	}

	/**
	 * @param string $commitHash
	 * @param string $commit
	 *
	 * @return Commit
	 */
	public static function fromCommitString(string $commitHash, string $commit): self{
		$commit = explode("\n", $commit);
		$array = ['commitHash' => $commitHash, 'parents' => new ArrayOfString(...[]), 'message' => []];
		foreach($commit as $line){
			if(preg_match('#^(tree|parent|author|committer)\s(.*)$#', $line, $match)){
				switch($match[1]){
					case 'tree':
						$array['tree'] = $match[2];
						break;
					case 'parent':
						$array['parents'][] = $match[2];
						break;
					case 'author':
					case 'committer':
						if(preg_match(/** @lang text */
							"#(?<name>.+)\s<(?<mail>[^>]+)>\s(?<timestamp>\d+)\s(?<offset>[+-]\d{4})#",
							$match[2],
							$m
						)){
							try{
								$array['date'] = (new \DateTimeImmutable(
									'',
									new \DateTimeZone($m['offset'])
								))->setTimestamp($m['timestamp']);
							}catch(\Exception $e){
								throw new \LogicException($e);
							}
							$array[$match[1].'Name'] = $m['name'];
							$array[$match[1].'Mail'] = $m['mail'];
						}
						break;
				}
			}else if(!empty($line)){
				$array['message'][] = $line;
			}
		}
		$array['message'] = implode("\n", $array['message']);
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return self::fromArray($array);
	}
}