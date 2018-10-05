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

use Rodziu\GenericTypes\GenericArray;

/**
 * Class ArrayOfGitCommit
 * @package Rodziu\Types
 */
class ArrayOfGitCommit extends GenericArray{
	/**
	 * ArrayOfGitCommit constructor.
	 *
	 * @param Commit ...$commits
	 */
	public function __construct(Commit ...$commits){
		parent::__construct();
		$this->values = $commits;
	}
}