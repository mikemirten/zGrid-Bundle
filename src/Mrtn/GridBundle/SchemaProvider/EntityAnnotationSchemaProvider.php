<?php

namespace Mrtn\GridBundle\SchemaProvider;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Annotations\Reader;

use Mrtn\GridBundle\Annotation\Grid    as GridAnnotation;
use Mrtn\GridBundle\Annotation\Column  as ColumnAnnotation;
use Mrtn\GridBundle\Annotation\Exclude as ExcludeAnnotation;

use Zgrid\Schema\Schema;
use Zgrid\Schema\Field;

use Zgrid\SchemaProvider\SchemaProviderInterface;

class EntityAnnotationSchemaProvider implements SchemaProviderInterface
{
	/**
	 * Doctrine repository
	 *
	 * @var ObjectRepository
	 */
	private $repository;

	/**
	 * Amnnotation reader
	 *
	 * @var Reader
	 */
	private $reader;

	/**
	 * Reflection
	 *
	 * @var \ReflectionClass
	 */
	private $reflection;

	/**
	 * Schema
	 *
	 * @var Schema
	 */
	private $schema;

	/**
	 * Constructor
	 * 
	 * @param RepositoryInterface $repository
	 */
	public function __construct(ObjectRepository $repository, Reader $reader)
	{
		$this->repository = $repository;
		$this->reader     = $reader;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSchema()
	{
		if ($this->schema === null) {
			$this->schema = new Schema();

			$this->processFields();
			$this->processVirtualFields();
		}

		return $this->schema;
	}

	/**
	 * Get fields
	 */
	protected function processFields()
	{
		$gridAnnotation = $this->getGridAnnotation();

		if ($gridAnnotation === null || $gridAnnotation->strategy === GridAnnotation::STRATEGY_EXCLUDE) {
			$this->addFieldsByExcludeStrategy();
			return;
		}

		$this->addFieldsByIncludeStrategy();
	}

	/**
	 * Add virtual fields provided by methods
	 * Independent of strategy
	 */
	protected function processVirtualFields()
	{
		$methods = $this->getClassReflection()->getMethods();
		
		foreach ($methods as $method) {
			if ($method->isStatic() || ! $method->isPublic()) {
				continue;
			}
			
			if (! preg_match('~^(?:is|get)([a-z0-9_]+)~i', $method->getName(), $matches)) {
				continue;
			}
			
			$annotation = $this->reader->getMethodAnnotation($method, ColumnAnnotation::class);
			
			if ($annotation === null) {
				continue;
			}
			
			$field = new Field(lcfirst($matches[1]));
			$this->applyAnnotation($field, $annotation);

			$this->schema->addField($field);
		}
	}
	
	/**
	 * Add fields by properties with "Field" annotation
	 * "Exclude" annotation will be ignored
	 */
	protected function addFieldsByIncludeStrategy()
	{
		$properties  = $this->getClassReflection()->getProperties();
		$annotations = $this->getColumnAnnotations();

		foreach ($properties as $property) {
			$name = $property->getName();

			if (! isset($annotations[$name])) {
				continue;
			}

			$field = new Field($name);
			$this->applyAnnotation($field, $annotations[$name]);

			$this->schema->addField($field);
		}
	}

	/**
	 * Add fields by properties without "Exclude" annotation
	 */
	protected function addFieldsByExcludeStrategy()
	{
		$properties = $this->getClassReflection()->getProperties();

		$excludedAnnotations = $this->getExcludeAnnotations();
		$columnsAnnotations  = $this->getColumnAnnotations();

		foreach ($properties as $property) {
			if ($property->isStatic()) {
				continue;
			}
			
			$name = $property->getName();

			if (isset($excludedAnnotations[$name])) {
				continue;
			}

			$field = new Field($name);

			if (isset($columnsAnnotations[$name])) {
				$this->applyAnnotation($field, $columnsAnnotations[$name]);
			}

			$this->schema->addField($field);
		}
	}

	/**
	 * Apply annotation to field
	 * 
	 * @param Field            $field
	 * @param ColumnAnnotation $annotation
	 */
	protected function applyAnnotation(Field $field, ColumnAnnotation $annotation)
	{
		$field->setTitle($annotation->title);
		$field->setWidth($annotation->width);
		$field->setProperty($annotation->property);
		$field->setOrderable($annotation->orderable);
		$field->setOrderBy($annotation->orderBy);
		$field->setSearchable($annotation->searchable);
		$field->setSearchBy($annotation->searchBy);
		$field->setGloballySearchable($annotation->globalSearch);
	}

	/**
	 * Get GRID annotation
	 * 
	 * @return GridAnnotation | null
	 */
	protected function getGridAnnotation()
	{
		$reflection = $this->getClassReflection();

		return $this->reader->getClassAnnotation($reflection, GridAnnotation::class);
	}

	/**
	 * Get fields annotations
	 * 
	 * @return ExcludeAnnotation[]
	 */
	protected function getColumnAnnotations()
	{
		return $this->readPropertyAnnotations(ColumnAnnotation::class);
	}

	/**
	 * Get exclude annotations
	 * 
	 * @return ExcludeAnnotation[]
	 */
	protected function getExcludeAnnotations()
	{
		return $this->readPropertyAnnotations(ExcludeAnnotation::class);
	}

	/**
	 * Get fields annotations
	 * 
	 * @param  string $annotationName
	 * @return array
	 */
	protected function readPropertyAnnotations($annotationName)
	{
		$reflection  = $this->getClassReflection();
		$annotations = [];

		foreach ($reflection->getProperties() as $property) {
			$annotation = $this->reader->getPropertyAnnotation($property, $annotationName);

			if ($annotation !== null) {
				$annotations[$property->getName()] = $annotation;
			}
		}

		return $annotations;
	}
	
	/**
	 * Get reflection of entity class
	 * 
	 * @return \ReflectionClass
	 */
	protected function getClassReflection()
	{
		if ($this->reflection === null) {
			$class = $this->repository->getClassName();

			$this->reflection = new \ReflectionClass($class);
		}

		return $this->reflection;
	}
}
