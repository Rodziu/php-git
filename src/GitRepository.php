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

namespace Rodziu\Git;

use Rodziu\GenericTypes\ArrayOfString;
use Rodziu\Git\Pack\Pack;
use Rodziu\Git\Types\ArrayOfGitCommit;
use Rodziu\Git\Types\ArrayOfGitTag;
use Rodziu\Git\Types\Commit;
use Rodziu\Git\Types\GitObject;
use Rodziu\Git\Types\HEAD;
use Rodziu\Git\Types\Tag;

/**
 * Class GitRepository
 * @package Rodziu\Git
 */
class GitRepository{
	/**
	 * @var string
	 */
	private $gitRepoPath = "";
	/**
	 * @var array
	 */
	private $packs = [];

	/**
	 * GitRepository constructor.
	 *
	 * @param string $gitRepoPath
	 */
	public function __construct(string $gitRepoPath){
		$gitRepoPath = rtrim($gitRepoPath, DIRECTORY_SEPARATOR);
		if(basename($gitRepoPath) != '.git' || !file_exists($gitRepoPath)){
			throw new GitException("$gitRepoPath is not a git repository!");
		}
		$this->gitRepoPath = $gitRepoPath;
		$this->packs = [];
		if(is_dir($gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR.'pack')){
			foreach(
				new \DirectoryIterator($gitRepoPath.DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR.'pack')
				as $pack
			){
				if($pack->isFile() && $pack->getExtension() === 'pack'){
					$this->packs[] = new Pack($pack->getPathname());
				}
			}
		}
	}

	/**
	 * Get current HEAD of repository
	 *
	 * @return HEAD
	 * @throws GitException
	 */
	public function getHead(): HEAD{
		$headPath = $this->gitRepoPath.DIRECTORY_SEPARATOR.'HEAD';
		if(!file_exists($headPath)){
			throw new GitException("Head file does not exist at $headPath!");
		}
		$head = trim(file_get_contents($headPath));
		if(strlen($head) == 40){
			return new HEAD($head);
		}else if(!preg_match('#^ref:\s+(.*)$#su', $head, $match)){
			throw new GitException("Could not match ref: in $headPath!");
		}
		$branch = preg_replace('#^.*/#', '', $match[1]);
		return new HEAD(
			trim(file_get_contents(
				$this->gitRepoPath.DIRECTORY_SEPARATOR.trim($match[1])
			)),
			$branch
		);
	}

	/**
	 * @return ArrayOfString
	 */
	public function getBranches(): ArrayOfString{
		$ret = new ArrayOfString();
		$packedRefs = $this->getPackedRefs();
		foreach($packedRefs['heads'] as $v){
			$ret[] = $v;
		}
		$iterator = new \IteratorIterator(new \DirectoryIterator(
			$this->gitRepoPath.DIRECTORY_SEPARATOR.'refs'.DIRECTORY_SEPARATOR.'heads'
		));
		foreach($iterator as $i){
			if(!$i->isDot()){
				$ret[] = $i->getFileName();
			}
		}
		return $ret;
	}

	/**
	 * @return array
	 */
	protected function getPackedRefs(): array{
		$ret = [
			'heads' => [],
			'tags'  => []
		];
		if(file_exists($this->gitRepoPath.DIRECTORY_SEPARATOR.'packed-refs')){
			$packedRefs = file_get_contents($this->gitRepoPath.DIRECTORY_SEPARATOR.'packed-refs');
			if(preg_match_all('#^([a-z0-9]{40}) refs/(tags|heads)/(.*)$#m', $packedRefs, $matches, PREG_SET_ORDER)){
				foreach($matches as $m){
					$ret[$m[2]][$m[3]] = $m[1];
				}
			}
		}
		return $ret;
	}

	/**
	 * @return ArrayOfGitTag
	 */
	public function getTags(): ArrayOfGitTag{
		$tags = [];
		$packedRefs = $this->getPackedRefs();
		foreach($packedRefs['tags'] as $k => $v){
			$tags[] = new Tag($k, $v);
		}
		$iterator = new \IteratorIterator(new \DirectoryIterator(
			$this->gitRepoPath.DIRECTORY_SEPARATOR.'refs'.DIRECTORY_SEPARATOR.'tags'
		));
		foreach($iterator as $i){
			/** @var $i \DirectoryIterator */
			if(!$i->isDot()){
				$tags[] = new Tag(
					$i->getFilename(),
					trim(file_get_contents($i->getPathname()))
				);
			}
		}
		usort($tags, function(Tag $a, Tag $b){
			return version_compare($b->tag, $a->tag);
		});
		return new ArrayOfGitTag(...$tags);
	}

	/**
	 * @param string $branch
	 *
	 * @return ArrayOfGitCommit
	 */
	public function getHistory(string $branch = 'master'): ArrayOfGitCommit{
		$queue = [$this->getTip($branch)];
		$merges = $commits = [];
		$masterHashes = [];
		while(!is_null($commitHash = array_shift($queue))){
			$commit = $this->getCommit($commitHash);
			$parentCount = count($commit->parents);
			if($parentCount){
				$queue[] = $commit->parents[0];
				if($parentCount > 1){
					$merges[$commitHash] = array_slice($commit->parents->toArray(), 1);
				}
			}
			$commits[] = $commit;
			$masterHashes[] = $commitHash;
		}
		$merges = array_reverse($merges);
		foreach($merges as $commitHash => $mergeHashes){
			$mergeCommits = $this->getMergeCommits(new ArrayOfString(...$mergeHashes), $masterHashes);
			foreach($commits as $k => $commit){
				if($commit->commitHash == $commitHash){
					$toSplice = [];
					foreach($mergeCommits as $mergeCommit){
						if(!in_array($mergeCommit->commitHash, $masterHashes)){
							$toSplice[] = $mergeCommit;
							$masterHashes[] = $mergeCommit->commitHash;
						}
					}
					array_splice($commits, $k + 1, 0, $toSplice);
					break;
				}
			}
		}
		return new ArrayOfGitCommit(...$commits);
	}

	/**
	 * @param string $branch
	 *
	 * @return string
	 * @throws GitException
	 */
	public function getTip(string $branch = 'master'): string{
		$headPath = $this->gitRepoPath
			.DIRECTORY_SEPARATOR.'refs'
			.DIRECTORY_SEPARATOR.'heads'
			.DIRECTORY_SEPARATOR.$branch;
		if(!file_exists($headPath)){
			throw new GitException("No such branch $branch!");
		}
		return trim(file_get_contents($headPath));
	}

	/**
	 * @param string $commitHash
	 *
	 * @return Commit
	 * @throws GitException
	 */
	public function getCommit(string $commitHash): Commit{
		$object = $this->getObject($commitHash);
		if($object === null || $object->getType() !== GitObject::TYPE_COMMIT){
			throw new GitException("Commit $commitHash does not exist!");
		}
		return Commit::fromGitObject($object);
	}

	/**
	 * @param string $hash
	 *
	 * @return null|GitObject
	 */
	public function getObject(string $hash): ?GitObject{
		$localPath = $this->gitRepoPath
			.DIRECTORY_SEPARATOR.'objects'
			.DIRECTORY_SEPARATOR.substr($hash, 0, 2)
			.DIRECTORY_SEPARATOR.substr($hash, 2);
		$object = GitObject::createFromFile($localPath);
		if($object === null){
			/** @var Pack $pack */
			foreach($this->packs as $pack){
				$object = $pack->getPackedObject($hash);
				if($object !== null){
					break;
				}
			}
		}
		return $object;
	}

	/**
	 * @param ArrayOfString $mergeHashes
	 * @param array $masterHashes
	 *
	 * @return ArrayOfGitCommit
	 */
	protected function getMergeCommits(ArrayOfString $mergeHashes, array $masterHashes): ArrayOfGitCommit{
		$commits = [];
		$queue = $mergeHashes->toArray();
		while(!is_null($commitHash = array_shift($queue))){
			$commit = $this->getCommit($commitHash);
			foreach($commit->parents as $p){
				if(!in_array($p, $masterHashes)){
					$queue[] = $p;
				}
			}
			$commits[] = $commit;
		}
		return new ArrayOfGitCommit(...$commits);
	}
}