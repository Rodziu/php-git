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

/**
 * Class TestsHelper
 * @package Rodziu
 */
abstract class TestsHelper{
	const GIT_TEST_PATH = __DIR__.DIRECTORY_SEPARATOR.'gitTest';

	/**
	 */
	public static function createZip(){
		$zip = new \ZipArchive();
		$zip->open(self::GIT_TEST_PATH.'.zip', \ZipArchive::CREATE);
		$path = self::GIT_TEST_PATH;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
		);
		foreach($iterator as $i){
			/** @var \SplFileInfo $i */
			$zip->addFile($i->getPathname(), str_replace(self::GIT_TEST_PATH, '', $i->getPathname()));
		}
		$zip->close();
	}

	/**
	 */
	public static function unpackZip(){
		if(!is_dir(self::GIT_TEST_PATH)){
			$zip = new \ZipArchive();
			if($zip->open(self::GIT_TEST_PATH.'.zip')){
				@mkdir(self::GIT_TEST_PATH);
				$zip->extractTo(self::GIT_TEST_PATH);
				$zip->close();
			}
		}
	}
}
TestsHelper::unpackZip();
require dirname(__DIR__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';