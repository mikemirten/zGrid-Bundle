<?php

namespace Mrtn\GridBundle\Builder;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Annotations\Reader;

use Zgrid\SchemaProvider\SchemaProviderInterface;
use Zgrid\Request\SimpleRequest;
use Zgrid\Grid\Grid;

use Mrtn\GridBundle\DataProvider\SelectableDataProvider;
use Mrtn\GridBundle\DataProcessor\PropertyAccessDataProcessor;
use Mrtn\GridBundle\SchemaProvider\EntityAnnotationSchemaProvider;
use Mrtn\GridBundle\Request\HttpRequest;

class Builder
{
	/**
	 * Annotation reader
	 *
	 * @var Reader 
	 */
	protected $annotationReader;
	
	/**
	 * Property accessor
	 *
	 * @var PropertyAccessorInterface 
	 */
	protected $propertyAccess;
	
	/**
	 * Constructor
	 * 
	 * @param Reader                    $annotationReader
	 * @param PropertyAccessorInterface $propertyAccess
	 */
	public function __construct(Reader $annotationReader, PropertyAccessorInterface $propertyAccess)
	{
		$this->annotationReader = $annotationReader;
		$this->propertyAccess   = $propertyAccess;
	}
	
	/**
	 * Create GRID by selectable
	 * 
	 * @param Selectable              $selectable
	 * @param SchemaProviderInterface $schemaProvider
	 * @param HttpFoundationRequest   $httpRequest
	 */
	public function createBySelectable(Selectable $selectable, HttpFoundationRequest $httpRequest = null, SchemaProviderInterface $schemaProvider = null)
	{
		if ($schemaProvider === null && $selectable instanceof ObjectRepository) {
			$schemaProvider = new EntityAnnotationSchemaProvider($selectable, $this->annotationReader);
		}
		
		if ($schemaProvider === null) {
			throw new \LogicException('If selectable is not instance of "ObjectRepository", schema must be provided');
		}
		
		$dataProcessor = new PropertyAccessDataProcessor($this->propertyAccess, $schemaProvider);
		$dataProvider  = new SelectableDataProvider($selectable, $schemaProvider, $dataProcessor);
		
		$request = ($httpRequest === null)
			? new SimpleRequest()
			: new HttpRequest($httpRequest);
		
		return new Grid($dataProvider, $request);
	}
}