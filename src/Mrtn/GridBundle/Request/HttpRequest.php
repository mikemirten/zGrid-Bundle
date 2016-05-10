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
	public function getPage()
	{
		return $this->request->get('page', self::DEFAULT_PAGE);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOrder()
	{
		if ($this->order === null) {
			$this->order = array_map('strtolower', $this->request->get('order', []));
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
			$this->search = $this->request->get('search', []);
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
		return $this->request->get('globalSearch');
	}
}
