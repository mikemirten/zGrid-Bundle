<?php

namespace Mrtn\GridBundle\DataProvider;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Criteria;

use Zgrid\SchemaProvider\SchemaProviderInterface;
use Zgrid\Request\RequestInterface;
use Zgrid\DataProvider\DataProviderInterface;
use Zgrid\DataProcessor\DataProcessorInterface;

class SelectableDataProvider implements DataProviderInterface
{
	/**
	 * Doctrine repository
	 *
	 * @var Selectable
	 */
	private $collection;

	/**
	 * Schema provider
	 *
	 * @var SchemaProviderInterface 
	 */
	private $schemaProvider;

	/**
	 * Data processor
	 *
	 * @var DataProcessorInterface
	 */
	private $dataProcessor;

	/**
	 * Constructor
	 * 
	 * @param RepositoryInterface $repository
	 */
	public function __construct(Selectable $collection, SchemaProviderInterface $schemaProvider, DataProcessorInterface $dataProcessor)
	{
		$this->collection     = $collection;
		$this->schemaProvider = $schemaProvider;
		$this->dataProcessor  = $dataProcessor;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSchema()
	{
		return $this->schemaProvider->getSchema();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getData(RequestInterface $request)
	{
		$criteria = new Criteria();
		$criteria->setMaxResults($request->getLimit());
		$criteria->setFirstResult($request->getOffset());

		$this->processOrder($criteria, $request);
		$this->processSearch($criteria, $request);
		$this->processGlobalSearch($criteria, $request);

		$result = $this->collection->matching($criteria);

		return $this->processData($result);
	}

	/**
	 * Process data
	 * 
	 * @param  array | \Traversable $data
	 * @return array
	 */
	protected function processData($data)
	{
		$processed = new \SplDoublyLinkedList();

		foreach ($data as $item) {
			$processed[] = $this->dataProcessor->process($item);
		}

		return $processed;
	}

	/**
	 * Process order
	 * 
	 * @param Criteria         $criteria
	 * @param RequestInterface $request
	 */
	protected function processOrder(Criteria $criteria, RequestInterface $request)
	{
		$criteria->orderBy($request->getOrder());
	}

	/**
	 * Process order
	 * 
	 * @param Criteria         $criteria
	 * @param RequestInterface $request
	 */
	protected function processSearch(Criteria $criteria, RequestInterface $request)
	{
		foreach ($request->getSearch() as $field => $value) {
			$criteria->andWhere(Criteria::expr()->contains($field, $value));
		}
	}

	/**
	 * Process global search
	 * 
	 * @param Criteria         $criteria
	 * @param RequestInterface $request
	 */
	protected function processGlobalSearch(Criteria $criteria, RequestInterface $request)
	{
		$query = $request->getGlobalSearch();

		if ($query === null) {
			return;
		}

		$condition = call_user_func_array([Criteria::expr(), 'orX'], array_map(
			function($name) use($query) {
				return Criteria::expr()->contains($name, $query);
			},
			$this->schemaProvider->getSchema()->getGloballySearchableNames()
		));

		$criteria->andWhere($condition);
	}
}
