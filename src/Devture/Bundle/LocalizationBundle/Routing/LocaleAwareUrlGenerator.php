<?php
namespace Devture\Bundle\LocalizationBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;

class LocaleAwareUrlGenerator extends UrlGenerator {

    private $container;

    public function __construct(\Pimple $container, RouteCollection $routes, RequestContext $context) {
        $this->container = $container;
        parent::__construct($routes, $context);
    }

    public function generate($name, $parameters = array(), $absolute = false) {
        $parameters['locale'] = $this->container['request']->getLocale();
        return parent::generate($name, $parameters, $absolute);
    }

}
