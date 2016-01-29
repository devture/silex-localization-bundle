<?php
namespace Devture\Bundle\LocalizationBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LocaleAwareUrlGenerator extends UrlGenerator {

	private $container;

	public function __construct(\Pimple $container, RouteCollection $routes, RequestContext $context) {
		$this->container = $container;
		$this->routes = $routes;
		parent::__construct($routes, $context);
	}

	/**
	 * {@inheritDoc}
	 * @param $name string
	 * @param $parameters array
	 * @param $referenceType boolean|integer
	 * @see \Symfony\Component\Routing\Generator\UrlGenerator::generate()
	 */
	public function generate($name, $parameters = array(), $referenceType = false) {
		//Since Symfony 3 dropped compatibility for booleans
		//and completely swapped the logic, let's provide our own compatibility layer.
		//We only want to touch booleans. Let proper integer-constant calls to go through.
		if ($referenceType === true) {
			$referenceType = UrlGeneratorInterface::ABSOLUTE_URL;
		} else if ($referenceType === false) {
			$referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
		}

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

		return parent::generate($name, $parameters, $referenceType);
	}

	/**
	 * @throws \RuntimeException when not in a request context
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	private function getRequest() {
		return $this->container['request'];
	}

}
