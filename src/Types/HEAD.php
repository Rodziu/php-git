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

use Rodziu\GenericTypes\GenericStructure;

/**
 * Class HEAD
 * @package Rodziu\Git\Types
 */
class HEAD extends GenericStructure{
	/**
	 * @var string
	 */
	public $commitHash = "";
	/**
	 * @var string
	 */
	public $branch = null;

	/**
	 * HEAD constructor.
	 *
	 * @param string $commitHash
	 * @param string|null $branch
	 */
	public function __construct(string $commitHash, string $branch = null){
		$this->commitHash = $commitHash;
		$this->branch = $branch;
	}
}