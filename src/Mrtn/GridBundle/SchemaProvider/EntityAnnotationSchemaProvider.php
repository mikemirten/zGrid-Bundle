<?php

namespace Mrtn\GridBundle\SchemaProvider;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Annotations\Reader;


use Mrtn\GridBundle\Annotation\Grid     as GridAnnotation;
use Mrtn\GridBundle\Annotation\Column   as ColumnAnnotation;
use Mrtn\GridBundle\Annotation\Exclude  as ExcludeAnnotation;
use Mrtn\GridBundle\Annotation\Metadata as MetadataAnnotation;

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
	 * Doctrine registry
	 *
	 * @var ManagerRegistry
	 */
	private $registry;

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
	 * Entity metadata
	 * 
	 * @var \Doctrine\ORM\Mapping\ClassMetadata
	 */
	private $metadata;

	/**
	 * Constructor
	 * 
	 * @param RepositoryInterface $repository
	 */
	public function __construct(ObjectRepository $repository, Reader $reader, ManagerRegistry $registry)
	{
		$this->repository = $repository;
		$this->reader     = $reader;
		$this->registry   = $registry;
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
			$this->processMetadataFields();
		}
		
		return $this->schema;
	}
	
	/**
	 * Process metadata fields
	 */
	protected function processMetadataFields()
	{
		$annotations = $this->readPropertyAnnotations(MetadataAnnotation::class);
		
		foreach ($annotations as $property => $annotation) {
			$this->schema->addMetadataProperty($annotation->name ?: $property);
		}
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
			
			$metaAnnotation = $this->reader->getMethodAnnotation($method, MetadataAnnotation::class);
			
			if ($metaAnnotation !== null) {
				$this->schema->addMetadataProperty($metaAnnotation->name ?: lcfirst($matches[1]));
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
		$annotations = $this->readPropertyAnnotations(ColumnAnnotation::class);

		foreach ($properties as $property) {
			$name = $property->getName();

			if (! isset($annotations[$name])) {
				continue;
			}

			$field = new Field($name);
			$this->applyAnnotation($field, $annotations[$name]);
			$this->resolveType($field);
			
			$this->schema->addField($field);
		}
	}

	/**
	 * Add fields by properties without "Exclude" annotation
	 */
	protected function addFieldsByExcludeStrategy()
	{
		$properties = $this->getClassReflection()->getProperties();

		$excludedAnnotations = $this->readPropertyAnnotations(ExcludeAnnotation::class);
		$columnsAnnotations  = $this->readPropertyAnnotations(ColumnAnnotation::class);

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
			
			$this->resolveType($field);
			
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
		$field->setType($annotation->type);
		$field->setWidth($annotation->width);
		$field->setProperty($annotation->property);
		$field->setOrderable($annotation->orderable);
		$field->setOrderBy($annotation->orderBy);
		$field->setSearchable($annotation->searchable);
		$field->setSearchBy($annotation->searchBy);
		$field->setGloballySearchable($annotation->globalSearch);
		$field->setPriority($annotation->priority);
	}
	
	/**
	 * Resolve type of field
	 * 
	 * @param  Field $field
	 */
	protected function resolveType(Field $field)
	{
		if ($field->getType() !== null) {
			return;
		}
		
		$type = $this->getMetadata()
			->getTypeOfField($field->getName());
		
		$field->setType($type);
	}
	
	/**
	 * Get entity metadata
	 * 
	 * @return \Doctrine\ORM\Mapping\ClassMetadata
	 */
	protected function getMetadata()
	{
		if ($this->metadata === null) {
			$class = $this->repository->getClassName();
			
			$this->metadata = $this->registry
				->getManagerForClass($class)
				->getClassMetadata($class);
		}
		
		return $this->metadata;
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
