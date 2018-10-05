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
 * Class Tag
 * @package Rodziu\Git\Types
 */
class Tag extends GenericStructure{
	/**
	 * @var string
	 */
	public $tag = "";
	/**
	 * @var string
	 */
	public $commit = "";

	/**
	 * Tag constructor.
	 *
	 * @param string $tag
	 * @param string $commit
	 */
	public function __construct(string $tag, string $commit){
		$this->tag = $tag;
		$this->commit = $commit;
	}
}