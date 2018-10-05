<?php

namespace Rodziu\Git;

use Rodziu\Git\Pack\Pack;
use Rodziu\Git\Types\Tree;
use Rodziu\Git\Types\TreeBranch;
use WidzialniPL\Curl;

/**
 * Class GitClone
 * @package Rodziu\Git
 */
class GitClone{
	/**
	 * @var string
	 */
	protected $url;
	/**
	 * @var string
	 */
	protected $destination;

	/**
	 * GitClone constructor.
	 *
	 * @param string $url
	 * @param string $destination
	 */
	protected function __construct(string $url, string $destination){
		if(preg_match('#/([^/]+).git$#', $url, $match)){
			$repoName = $match[1];
		}else{
			throw new GitException("Wrong url format!");
		}
		$destination = $destination.DIRECTORY_SEPARATOR.$repoName;
		if(is_dir($destination)){
			throw new GitException("`$destination` already exists!");
		}
		$this->url = $url;
		$this->destination = $destination;
		mkdir($destination, 0755, true);
		mkdir($destination.DIRECTORY_SEPARATOR.'.git', 0755, true);
	}

	/**
	 */
	protected function getRepositoryInfo(): string{
		$c = new Curl();
		$c->init("{$this->url}/info/refs?service=git-upload-pack", true, [
			CURLOPT_USERAGENT  => 'git/2.10.5',
			CURLOPT_HTTPHEADER => ['Content-Type: application/x-git-upload-pack-request']
		]);
		$data = $c->exec();
		if($data[1]['http_code'] === 200){
			$response = explode("\n", $data[0]);
			$lines = count($response);
			$gitPath = $this->destination.DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR;
			if(preg_match('#symref=HEAD:([^ ]+)#', $response[0], $match)){
				$head = $match[1];
				file_put_contents(
					$gitPath.'HEAD',
					"ref: $head\n"
				);
			}else{
				throw new GitException("Failed to match HEAD");
			}
			for($i = 1; $i < $lines; $i++){
				$line = explode(" ", substr($response[$i], 4));
				if(count($line) === 2){
					$dir = $gitPath.dirname($line[1]);
					if(!is_dir($dir)){
						mkdir($dir, 0755, true);
					}
					file_put_contents($gitPath.$line[1], $line[0].PHP_EOL);
					if($line[1] == $head){
						$head = $line[0];
					}
				}
			}
			return $head;
		}else{
			throw new GitException(
				"Could not get repository info from `{$this->url}`"
			);
		}
	}

	/**
	 * @param string $head
	 */
	protected function fetchObjects(string $head){
		$c = new Curl();
		$c->init("{$this->url}/git-upload-pack", true, [
			CURLOPT_USERAGENT  => 'git/2.10.5',
			CURLOPT_HTTPHEADER => ['Content-Type: application/x-git-upload-pack-request']
		]);
		$c->setPost("0032want $head\n00000032have 0000000000000000000000000000000000000000\n0009done\n");
		$data = $c->exec();
		if($data[1]['http_code'] === 200){
			mkdir(
				$dir = implode(DIRECTORY_SEPARATOR, [
					$this->destination, '.git', 'objects'
				]),
				0755, true
			);
			mkdir(
				$dir .= DIRECTORY_SEPARATOR.'pack',
				0755, true
			);
			file_put_contents(
				$dir.DIRECTORY_SEPARATOR.'pack-hash.pack',
				explode("\n", $data[0], 2)[1]
			);
			$hash = (new Pack($dir.DIRECTORY_SEPARATOR.'pack-hash.pack'))->getChecksum();
			rename(
				$dir.DIRECTORY_SEPARATOR.'pack-hash.pack',
				$dir.DIRECTORY_SEPARATOR."pack-$hash.pack"
			);
		}else{
			throw new GitException(
				"Could not fetch objects from `{$this->url}`"
			);
		}
	}

	/**
	 * @param string $repositoryPath
	 * @param string $head
	 */
	protected function checkout(string $repositoryPath, string $head){
		$git = new GitRepository($repositoryPath);
		$tree = new Tree(
			$git->getObject($git->getCommit($head)->tree), $git
		);
		/** @var TreeBranch $branch */
		foreach($tree->walkRecursive() as $path => $branch){
			$path = $this->destination.DIRECTORY_SEPARATOR.$path.$branch->getName();
			if($branch->getMode() === 0){
				mkdir($path, 0755);
			}else{
				file_put_contents($path, $branch->getObject()->getData());
				if($branch->getMode() === 755){
					chmod($path, 0755);
				}
			}
		}
	}

	/**
	 * @param string $url
	 * @param string $destination
	 */
	public static function cloneRepository(string $url, string $destination){
		$umask = umask(0);
		try{
			$clone = new self($url, $destination);
			$head = $clone->getRepositoryInfo();
			$clone->fetchObjects($head);
			$clone->checkout($clone->destination.DIRECTORY_SEPARATOR.'.git', $head);
		}catch(GitException $e){
			throw $e;
		}finally{
			umask($umask);
		}
	}
}