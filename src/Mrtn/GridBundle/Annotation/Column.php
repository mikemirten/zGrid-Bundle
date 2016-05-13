<?php

namespace Mrtn\GridBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class Column
{
	/**
	 * Title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type;
	
	/**
	 * Width
	 *
	 * @var int
	 */
	public $width;

	/**
	 * Property name
	 *
	 * @var string
	 */
	public $property;

	/**
	 * Orderable
	 *
	 * @var boolean
	 */
	public $orderable = false;

	/**
	 * Order by properties
	 *
	 * @var array
	 */
	public $orderBy = [];
	
	/**
	 * Searchable
	 *
	 * @var boolean
	 */
	public $searchable = false;
	
	/**
	 * Search by properties
	 *
	 * @var array
	 */
	public $searchBy = [];

	/**
	 * Involved in global search
	 *
	 * @var boolean
	 */
	public $globalSearch = false;
	
	/**
	 * Priority
	 *
	 * @var int
	 */
	public $priority;
}
