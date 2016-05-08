<?php

namespace Mrtn\GridBundle\DataProcessor;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Zgrid\SchemaProvider\SchemaProviderInterface;
use Zgrid\DataProcessor\DataProcessorInterface;

use Zgrid\Grid\Row;
use Zgrid\Grid\Cell;

class PropertyAccessDataProcessor implements DataProcessorInterface
{
	/**
	 * Property accessor
	 *
	 * @var PropertyAccessorInterface
	 */
	private $accessor;

	/**
	 * Schema provider
	 *
	 * @var SchemaProviderInterface 
	 */
	private $schemaProvider;

	/**
	 * Properties list
	 *
	 * @var array
	 */
	private $propertiesList;

	/**
	 * Constructor
	 * 
	 * @param PropertyAccessorInterface $accessor
	 * @param SchemaProviderInterface   $schemaProvider
	 */
	public function __construct(PropertyAccessorInterface $accessor, SchemaProviderInterface $schemaProvider)
	{
		$this->accessor       = $accessor;
		$this->schemaProvider = $schemaProvider;
	}

	/**
	 * {@inheritdoc}
	 */
	public function process($source)
	{
		$this->initializeProperties();

		$row = new Row();

		foreach ($this->propertiesList as $property) {
			$value = $this->accessor->getValue($source, $property);

			$row->appendCell(new Cell($value));
		}

		return $row;
	}

	/**
	 * Initialize properties
	 */
	protected function initializeProperties()
	{
		if ($this->propertiesList !== null) {
			return;
		}

		$this->propertiesList = new \SplDoublyLinkedList();

		foreach ($this->schemaProvider->getSchema()->getFields() as $field) {
			$property = $field->getProperty() ?: $field->getName();

			$this->propertiesList->push($property);
		}
	}
}
