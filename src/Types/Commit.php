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

use Rodziu\DateTimeLocalized\DateTimeImmutable;
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
	 * @var DateTimeImmutable
	 */
	public $authorDate;
	/**
	 * @var string
	 */
	public $committerName = "";
	/**
	 * @var string
	 */
	public $committerMail = "";
	/**
	 * @var DateTimeImmutable
	 */
	public $commitDate;
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
	 * @param string $authorName
	 * @param string $authorMail
	 * @param DateTimeImmutable $authorDate
	 * @param string $committerName
	 * @param string $committerMail
	 * @param DateTimeImmutable $commitDate
	 * @param string $message
	 */
	public function __construct(
		string $commitHash, string $tree, ArrayOfString $parents,
		string $authorName, string $authorMail, DateTimeImmutable $authorDate,
		string $committerName, string $committerMail, DateTimeImmutable $commitDate,
		string $message
	){
		$this->commitHash = $commitHash;
		$this->tree = $tree;
		$this->parents = $parents;
		$this->authorName = $authorName;
		$this->authorMail = $authorMail;
		$this->authorDate = $authorDate;
		$this->committerName = $committerName;
		$this->committerMail = $committerMail;
		$this->commitDate = $commitDate;
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
		return self::fromCommitString($gitObject->getSha1(), $gitObject->getData());
	}

	/**
	 * @param string $commitHash
	 * @param string $commit
	 *
	 * @return Commit
	 */
	public static function fromCommitString(string $commitHash, string $commit): self{
		$commit = explode("\n", $commit);
		$array = ['commitHash' => $commitHash, 'parents' => new ArrayOfString(), 'message' => []];
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
								$array[$match[1] === 'author' ? 'authorDate' : 'commitDate'] = (new DateTimeImmutable(
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

	/**
	 * @return string
	 */
	public function __toString(): string{
		$data = "tree {$this->tree}";
		foreach($this->parents as $parent){
			$data .= "\nparent $parent";
		}
		$getTime = function(DateTimeImmutable $date){
			$tzOffset = $date->getOffset() / 36;
			$tzOffset = ($tzOffset > 0 ? '+' : '-')
				.str_pad($tzOffset, 4, '0', STR_PAD_LEFT);
			return "{$date->getTimestamp()} $tzOffset";
		};
		$data .= "\nauthor {$this->authorName} <{$this->authorMail}> {$getTime($this->authorDate)}";
		$data .= "\ncommitter {$this->committerName} <{$this->committerMail}> {$getTime($this->commitDate)}";
		$data .= "\n\n{$this->message}\n";
		return $data;
	}
}