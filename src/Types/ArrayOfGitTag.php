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
 * Class ArrayOfGitTag
 * @package Rodziu\Types
 */
class ArrayOfGitTag extends GenericArray{
	/**
	 * ArrayOfGitTag constructor.
	 *
	 * @param Tag ...$gitTags
	 */
	public function __construct(Tag ...$gitTags){
		parent::__construct();
		$this->values = $gitTags;
	}
}