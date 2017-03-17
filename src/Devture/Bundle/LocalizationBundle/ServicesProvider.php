<?php
namespace Devture\Bundle\LocalizationBundle;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServicesProvider implements \Pimple\ServiceProviderInterface, \Silex\Api\BootableProviderInterface, \Silex\Api\EventListenerProviderInterface {

	private $config;

	public function __construct(array $config) {
		$this->config = array_merge(array(
			'default_locale' => 'en',
			'fallback_locale' => 'en',
			'locales' => array('en' => 'English'),
			'cache_path' => null,
			'auto_reload' => false,
		), $config);
	}

	public function register(\Pimple\Container $container) {
		$config = $this->config;

		//Expose some settings
		$container['default_locale'] = $config['default_locale'];
		$container['fallback_locale'] = $config['fallback_locale'];
		$container['locales'] = $config['locales'];

		$container['devture_localization.url_generator'] = function ($container) {
			return new Routing\LocaleAwareUrlGenerator($container, $container['routes'], $container['request_context']);
		};

		if (!isset($container['url_generator'])) {
			//Technically, we don't need either of those.
			//But registering as `url_generator` directly will fail if one of them overwrites it later.
			//Most projects will include one of them anyway, so this ordering requirement shouldn't be a big deal.
			throw new \LogicException('url_generator missing. Register FrameworkBundle (or the UrlGeneratorServiceProvider) before LocalizationBundle.');
		}

		//Replace the url_generator with our own, to enable transparent locale-aware URL generation
		$container->extend('url_generator', function ($_current, $container) {
			return $container['devture_localization.url_generator'];
		});

		$container['devture_localization.locale_listener'] = function ($container) use ($config) {
			return new EventListener\LocaleListener(
				$config['default_locale'],
				array_keys($config['locales']),
				$container['devture_localization.translator']
			);
		};

		$container['devture_localization.translator'] = function ($container) use ($config) {
			$translator = new Translator(
				'en',
				$container['devture_localization.translator.message_selector'],
				$config['cache_path'],
				$config['auto_reload']
			);
			if (isset($container['fallback_locale'])) {
				$translator->setFallbackLocales(array($container['fallback_locale']));
			}
			$translator->addLoader('json', $container['devture_localization.translator.loader']);
			return $translator;
		};

		//Alias it, so services that rely on $container['translator'] (like devture/form's FormExtension) can work.
		//Silex\Provider\TranslationServiceProvider also exports $container['translator'], so this makes us compatible.
		//
		//Also, if TwigServiceProvider sees that there's a `translator` service, it would automatically
		//register \Symfony\Bridge\Twig\Extension\TranslationExtension.
		$container['translator'] = function ($container) {
			return $container['devture_localization.translator'];
		};

		$container['devture_localization.translator.message_selector'] = function () {
			return new MessageSelector();
		};

		$container['devture_localization.translator.resource_loader'] = function ($container) {
			return new Translation\ResourceLoader($container['devture_localization.translator'], 'json');
		};

		$container['devture_localization.translator.loader'] = function () {
			return new Translation\JsonFileLoader();
		};

		$container['devture_localization.twig.locale_helper_extension'] = function ($container) use ($config) {
			return new Twig\LocaleHelperExtension($container, $config['locales']);
		};
	}

	public function subscribe(\Pimple\Container $container, EventDispatcherInterface $dispatcher) {
		$dispatcher->addSubscriber($container['devture_localization.locale_listener']);
	}

	public function boot(\Silex\Application $app) {
		//Note: \Symfony\Bridge\Twig\Extension\TranslationExtension is automatically registered
		//by TwigServiceProvider, because we have defined a `translator` service above.

		$app['twig']->addExtension($app['devture_localization.twig.locale_helper_extension']);
	}

}
