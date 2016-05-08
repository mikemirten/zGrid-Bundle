<?php

namespace Mrtn\GridBundle\Request;

use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

use Zgrid\Request\RequestInterface;

class HttpRequest implements RequestInterface
{
	/**
	 * Request
	 *
	 * @var HttpFoundationRequest 
	 */
	protected $request;

	/**
	 * Resolved order parameters
	 *
	 * @var array
	 */
	private $order;

	/**
	 * Resolved search parameters
	 *
	 * @var array
	 */
	private $search;

	/**
	 * Resolved global search
	 *
	 * @var array
	 */
	private $globalSearch;

	/**
	 * Constructor
	 * 
	 * @param HttpFoundationRequest $request
	 */
	public function __construct(HttpFoundationRequest $request)
	{
		$this->request = $request;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLimit()
	{
		return $this->request->get('limit', self::DEFAULT_LIMIT);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOffset()
	{
		return $this->request->get('offset', self::DEFAULT_OFFSET);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOrder()
	{
		if ($this->order === null) {
			$this->order = array_map('strtolower', $this->fetchDefinition('order'));
		}

		return $this->order;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOrderFor($name)
	{
		$order = $this->getOrder();

		if (isset($order[$name])) {
			return $order[$name];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSearch()
	{
		if ($this->search === null) {
			$this->search = $this->fetchDefinition('search');
		}

		return $this->search;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSearchFor($name)
	{
		$search = $this->getSearch();

		if (isset($search[$name])) {
			return $search[$name];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGlobalSearch()
	{
		if ($this->globalSearch === null) {
			$this->globalSearch = $this->fetchDefinition('globalSearch');
		}

		if (isset($this->globalSearch['query'])) {
			return $this->globalSearch['query'];
		}
	}

	/**
	 * Fetch definition from request by name
	 * 
	 * Source:
	 * "key1:value1, key2:value2"
	 * 
	 * Result:
	 * [
	 *     key1 => value1,
	 *     key2 => value2
	 * ]
	 * 
	 * @param  string $name
	 * @return array
	 */
	protected function fetchDefinition($name)
	{
		$source = $this->request->get($name);

		if (empty($source)) {
			return [];
		}

		$definition = [];

		foreach (explode(',', $source) as $element) {
			list ($key, $value) = explode(':', $element);

			$definition[trim($key)] = trim($value);
		}

		return $definition;
	}
}
