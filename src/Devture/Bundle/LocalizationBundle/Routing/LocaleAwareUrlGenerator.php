<?php
namespace Devture\Bundle\LocalizationBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;

class LocaleAwareUrlGenerator extends UrlGenerator {

	private $container;

	public function __construct(\Pimple $container, RouteCollection $routes, RequestContext $context) {
		$this->container = $container;
		$this->routes = $routes;
		parent::__construct($routes, $context);
	}

	public function generate($name, $parameters = array(), $absolute = false) {
		$route = $this->routes->get($name);
		if ($route instanceof Route) {
			if (strpos($route->getPath(), '{locale}') !== false && !array_key_exists('locale', $parameters)) {
				try {
					$parameters['locale'] = $this->getRequest()->getLocale();
				} catch (\RuntimeException $e) {
					//Not running in a request context.
				}
			}
		}
		return parent::generate($name, $parameters, $absolute);
	}

	/**
	 * @throws \RuntimeException when not in a request context
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	private function getRequest() {
		return $this->container['request'];
	}

}
