<?php
namespace Devture\Bundle\FrameworkBundle;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Devture\Component\Form\Twig\FormExtension;
use Devture\Component\Form\Twig\TokenExtension;
use Devture\Component\Form\Token\TemporaryTokenManager;

class ServicesProvider implements ServiceProviderInterface {

	private $config;

	public function __construct(array $config) {
		$config = array_merge(array(
			'token.validity_time' => 7200,
			'token.hash_function' => 'sha256',
		), $config);

		if (!isset($config['token.secret'])) {
			throw new \InvalidArgumentException('The token.secret configuration parameter is required.');
		}

		$this->config = $config;
	}

	public function register(Application $app) {
		$config = $this->config;

		$app->register(new \Silex\Provider\UrlGeneratorServiceProvider());
		$app->register(new \Silex\Provider\ServiceControllerServiceProvider());

		$app['devture_framework.csrf_token_manager'] = $app->share(function () use ($config) {
			return new TemporaryTokenManager($config['token.validity_time'], $config['token.secret'], $config['token.hash_function']);
		});

		$app['devture_framework.twig.token_extension'] = $app->share(function ($app) {
			return new TokenExtension($app['devture_framework.csrf_token_manager']);
		});

		$app['devture_framework.twig.form_extension'] = $app->share(function ($app) {
			return new FormExtension($app);
		});

		$app['devture_framework.twig.request_info_extension'] = $app->share(function ($app) {
			return new Twig\RequestInfoExtension($app);
		});
	}

	public function boot(Application $app) {
		if (!isset($app['twig.loader.filesystem'])) {
			throw new \RuntimeException('Silex\Provider\TwigServiceProvider not registered. Cannot initialize properly.');
		}

		$class = new \ReflectionClass('\Devture\Component\Form\Twig\FormExtension');
		$app['twig.loader.filesystem']->addPath(dirname(dirname($class->getFileName())) . '/Resources/views/');

		$app['twig']->addExtension($app['devture_framework.twig.form_extension']);
		$app['twig']->addExtension($app['devture_framework.twig.token_extension']);
		$app['twig']->addExtension($app['devture_framework.twig.request_info_extension']);
	}

}
