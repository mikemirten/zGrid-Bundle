<?php

namespace Mrtn\GridBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class Grid
{
	const STRATEGY_INCLUDE = 'include';
	const STRATEGY_EXCLUDE = 'exclude';

	/**
	 * Strategy
	 *
	 * @var string
	 */
	public $strategy = self::STRATEGY_EXCLUDE;
}
