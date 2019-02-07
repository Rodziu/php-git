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

use PHPUnit\Framework\TestCase;
use Rodziu\DateTimeLocalized\DateTimeImmutable;
use Rodziu\GenericTypes\ArrayOfString;
use Rodziu\Git\Types\HEAD;
use Rodziu\Git\Types\Tag;

/**
 * Class GitRepositoryTest
 * @package Rodziu\Git
 */
class GitRepositoryTest extends TestCase{
	/**
	 * @var string
	 */
	private $repoPath = "";
	/**
	 * @var GitRepository
	 */
	private $git;

	/**
	 */
	public function setUp(){
		parent::setUp();
		$this->repoPath = TestsHelper::GIT_TEST_PATH.DIRECTORY_SEPARATOR.'.git';
		$this->git = new GitRepository($this->repoPath);
	}

	/**
	 */
	public function testGetHead(){
		/** @noinspection SpellCheckingInspection */
		$this->assertEquals(
			new HEAD('890ecb06d3d373489adb661931f1d02b721375fc', 'master'),
			$this->git->getHead()
		);
	}

	/**
	 */
	public function testGetBranches(){
		$branches = $this->git->getBranches();
		/** @noinspection SpellCheckingInspection */
		$this->assertEquals(
			new ArrayOfString('master', 'branch', 'someBranch', 'detachedBranch'), $branches
		);
	}

	/**
	 * @dataProvider getCommitDataProvider
	 *
	 * @param string $commitHash
	 * @param array $expected
	 */
	public function testGetCommit(string $commitHash, array $expected){
		$commit = $this->git->getCommit($commitHash);
		$this->assertEquals($expected, $commit->toArray(false));
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getCommitDataProvider(): array{
		/** @noinspection SpellCheckingInspection */
		return [
			'7f1abf9c92388346c662ae67665ad040e7f88e8b' => ['7f1abf9c92388346c662ae67665ad040e7f88e8b', [
				'commitHash'    => '7f1abf9c92388346c662ae67665ad040e7f88e8b',
				'tree'          => '4ac978f076c8654bfd365838bad72608792a287c',
				'parents'       => new ArrayOfString('b534ef510fc478df9e7c14593b7214abbe2d4e78'),
				'authorName'    => 'Mateusz Rohde',
				'authorMail'    => 'mateusz.rohde@gmail.com',
				'authorDate'    => new DateTimeImmutable('2017-06-06 13:00:53.000000', new \DateTimeZone('+02:00')),
				'committerName' => 'Mateusz Rohde',
				'committerMail' => 'mateusz.rohde@gmail.com',
				'commitDate'    => new DateTimeImmutable('2017-06-06 13:00:53.000000', new \DateTimeZone('+02:00')),
				'message'       => "first commit line\nsecond commit line\nthird commit line"
			]],
			'b679aa263412e259e97e7687d6f8286bbac43be6' => ['b679aa263412e259e97e7687d6f8286bbac43be6', [
				'commitHash'    => 'b679aa263412e259e97e7687d6f8286bbac43be6',
				'tree'          => '2edc7d4c18a9b699ea81833b3d5626a29761df20',
				'parents'       => new ArrayOfString('bd5785f3aa2e35c60f70e4df8ef97613a43391b4', '8dfb1dd06eef93b66d5b42df8ade9662fa41b752'),
				'authorName'    => 'Mateusz Rohde',
				'authorMail'    => 'mateusz.rohde@gmail.com',
				'authorDate'    => new DateTimeImmutable('2017-06-06 10:56:07.000000', new \DateTimeZone('+02:00')),
				'committerName' => 'Mateusz Rohde',
				'committerMail' => 'mateusz.rohde@gmail.com',
				'commitDate'    => new DateTimeImmutable('2017-06-06 10:56:07.000000', new \DateTimeZone('+02:00')),
				'message'       => "Merge branch 'someBranch'"
			]],
			'b931c2fe4ad701ca4e4839ce5d729bdeb667e681' => ['b931c2fe4ad701ca4e4839ce5d729bdeb667e681', [
				'commitHash'    => 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
				'tree'          => '500d64db24715c051497d0f85d97b1c2ce4b8a80',
				'parents'       => new ArrayOfString('7f1abf9c92388346c662ae67665ad040e7f88e8b'),
				'authorName'    => 'Mateusz Rohde',
				'authorMail'    => 'mateusz.rohde@gmail.com',
				'authorDate'    => new DateTimeImmutable('2017-06-08 14:19:17.000000', new \DateTimeZone('+02:00')),
				'committerName' => 'Mateusz Rohde',
				'committerMail' => 'mateusz.rohde@gmail.com',
				'commitDate'    => new DateTimeImmutable('2017-06-08 14:19:17.000000', new \DateTimeZone('+02:00')),
				'message'       => 'branch'
			]],
			'5cce24046b2cc50eb9d32159d36975433badd456' => ['5cce24046b2cc50eb9d32159d36975433badd456', [
				'commitHash'    => '5cce24046b2cc50eb9d32159d36975433badd456',
				'tree'          => 'ffb28d71f9ebd4e8a41271f93a3d10e09e682829',
				'parents'       => new ArrayOfString('a3e6e3bfb5066f12bf2d52a9e5317fbe161d3c06'),
				'authorName'    => 'Mateusz Rohde',
				'authorMail'    => 'mateusz.rohde@gmail.com',
				'authorDate'    => new DateTimeImmutable('2017-06-09 11:42:28.000000', new \DateTimeZone('+02:00')),
				'committerName' => 'Mateusz Rohde',
				'committerMail' => 'mateusz.rohde@gmail.com',
				'commitDate'    => new DateTimeImmutable('2017-06-09 11:49:15.000000', new \DateTimeZone('+02:00')),
				'message'       => 'commit'
			]],
		];
	}

	/**
	 */
	public function testGetTags(){
		$tags = $this->git->getTags();
		/** @noinspection SpellCheckingInspection */
		$this->assertEquals([
			new Tag('1.0.0', 'b931c2fe4ad701ca4e4839ce5d729bdeb667e681'),
			new Tag('0.2.0', 'b679aa263412e259e97e7687d6f8286bbac43be6'),
			new Tag('0.1.0', 'bd5785f3aa2e35c60f70e4df8ef97613a43391b4')
		], $tags->toArray(false));
	}

	/**
	 */
	public function testGetHistory(){
		$ret = $this->git->getHistory();
		$hashes = [];
		foreach($ret as $r){
			$hashes[] = $r->commitHash;
		}
		/** @noinspection SpellCheckingInspection */
		$this->assertEquals(
			[
				'890ecb06d3d373489adb661931f1d02b721375fc',
				'e0d5e0b30030060c2cc8e1e131b80d576ddcfea7',
				'5cce24046b2cc50eb9d32159d36975433badd456',
				'a3e6e3bfb5066f12bf2d52a9e5317fbe161d3c06',
				'b931c2fe4ad701ca4e4839ce5d729bdeb667e681',
				'7f1abf9c92388346c662ae67665ad040e7f88e8b',
				'b534ef510fc478df9e7c14593b7214abbe2d4e78',
				'b679aa263412e259e97e7687d6f8286bbac43be6',
				'8dfb1dd06eef93b66d5b42df8ade9662fa41b752',
				'5c97392f3736acad2f54b6a6d58a2d50eb9b22b5',
				'bd5785f3aa2e35c60f70e4df8ef97613a43391b4',
				'605789f6cf83cfc13911db511faa8c5ae1800261',
			],
			$hashes
		);
	}
}
