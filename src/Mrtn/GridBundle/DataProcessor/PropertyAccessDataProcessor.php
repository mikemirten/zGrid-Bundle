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
	 * @var \Zgrid\Schema\Field[]
	 */
	private $fields;

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
		$fields = $this->getFields();

		$row = new Row();

		foreach ($this->schemaProvider->getSchema()->getMetadataProperties() as $name) {
			$metaValue = $this->accessor->getValue($source, $name);
			
			$row->setMetadataProperty($name, $metaValue);
		}
		
		foreach ($fields as $property => $field) {
			$value = $this->accessor->getValue($source, $property);

			$row->appendCell(new Cell($value, $field));
		}

		return $row;
	}

	/**
	 * Get properties
	 * 
	 * @return \Zgrid\Schema\Field[]
	 */
	protected function getFields()
	{
		if ($this->fields === null) {
			$this->fields = [];

			foreach ($this->schemaProvider->getSchema()->getFields() as $field) {
				$property = $field->getProperty() ?: $field->getName();

				$this->fields[$property] = $field;
			}
		}
		
		return $this->fields;
	}
}
