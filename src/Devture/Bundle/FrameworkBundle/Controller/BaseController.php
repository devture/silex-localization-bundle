<?php
namespace Devture\Bundle\FrameworkBundle\Controller;

abstract class BaseController {

	private $app;
	private $namespace;

	public function __construct(\Silex\Application $app, $namespace) {
		$this->app = $app;
		$this->namespace = $namespace;
	}

	public function get($serviceId) {
		return $this->app[$serviceId];
	}

	public function getNs($serviceId) {
		return $this->get($this->namespace . '.' . $serviceId);
	}

	public function renderView($viewIdentifier, array $data = array()) {
		$data['namespace'] = $this->namespace;
		return $this->app['twig']->render($viewIdentifier, $data);
	}

	public function generateUrl($path, array $params = array(), $absolute = false) {
		return $this->getUrlGenerator()->generate($path, $params, $absolute);
	}

	public function generateUrlNs($path, array $params = array(), $absolute = false) {
		return $this->generateUrl($this->namespace . '.' . $path, $params, $absolute);
	}

	public function redirect($url, $status = 302) {
		return $this->app->redirect($url, $status);
	}

	public function json($data = array(), $status = 200, $headers = array()) {
		return $this->app->json($data, $status, $headers);
	}

	public function abort($statusCode, $message = '', array $headers = array()) {
		return $this->app->abort($statusCode, $message, $headers);
	}

	/**
	 * @return \Symfony\Component\Routing\Generator\UrlGeneratorInterface
	 */
	private function getUrlGenerator() {
		return $this->get('url_generator');
	}

}
