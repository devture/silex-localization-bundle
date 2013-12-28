<?php
namespace Devture\Bundle\FrameworkBundle\Twig;

use Symfony\Component\HttpFoundation\Request;

class RequestInfoExtension extends \Twig_Extension {

	private $container;

	public function __construct(\Pimple $container) {
		$this->container = $container;
	}

	public function getName() {
		return 'devture_framework_request_info_extension';
	}

	public function getFunctions() {
		return array(
			'is_route' => new \Twig_Function_Method($this, 'isRoute'),
			'is_route_prefix' => new \Twig_Function_Method($this, 'isRoutePrefix'),
		);
	}

	public function isRoute($name) {
		return ($this->getRequest()->attributes->get('_route') === $name);
	}

	public function isRoutePrefix($prefix) {
		return (strpos($this->getRequest()->attributes->get('_route'), $prefix) === 0);
	}

	/**
	 * @return Request
	 * @throws \RuntimeException when not in a request context
	 */
	private function getRequest() {
		return $this->container['request'];
	}

}
