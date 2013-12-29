<?php
namespace Devture\Bundle\LocalizationBundle;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class ServicesProvider implements ServiceProviderInterface {

	private $config;

	public function __construct(array $config) {
		$this->config = array_merge(array(
			'default_locale' => 'en',
			'fallback_locale' => 'en',
			'locales' => array('en' => 'English'),
		), $config);
	}

	public function register(Application $app) {
		$config = $this->config;

		//Expose some settings
		$app['default_locale'] = $config['default_locale'];
		$app['fallback_locale'] = $config['fallback_locale'];
		$app['locales'] = $config['locales'];

		$app['devture_localization.url_generator'] = $app->share(function ($app) {
			return new Routing\LocaleAwareUrlGenerator($app, $app['routes'], $app['request_context']);
		});

		if (!isset($app['url_generator'])) {
			//Technically, we don't need either of those.
			//But registering as `url_generator` directly will fail if one of them overwrites it later.
			//Most projects will include one of them anyway, so this ordering requirement shouldn't be a big deal.
			throw new \LogicException('url_generator missing. Register FrameworkBundle (or the UrlGeneratorServiceProvider) before LocalizationBundle.');
		}

		//Replace the url_generator with our own, to enable transparent locale-aware URL generation
		$app->extend('url_generator', function ($current) use ($app) {
			return $app['devture_localization.url_generator'];
		});

		$app['devture_localization.locale_listener'] = $app->share(function ($app) use ($config) {
			return new EventListener\LocaleListener(
				$config['default_locale'],
				array_keys($config['locales']),
				$app['devture_localization.translator']
			);
		});

		$app['devture_localization.translator'] = $app->share(function ($app) {
			$translator = new Translator('en', $app['devture_localization.translator.message_selector']);
			if (isset($app['fallback_locale'])) {
				$translator->setFallbackLocale($app['fallback_locale']);
			}
			$translator->addLoader('json', $app['devture_localization.translator.loader']);
			return $translator;
		});

		//Alias it, so services that rely on $app['translator'] (like devture/form's FormExtension) can work.
		//Silex\Provider\TranslationServiceProvider also exports $app['translator'], so this makes us compatible.
		$app['translator'] = $app->share(function ($app) {
			return $app['devture_localization.translator'];
		});

		$app['devture_localization.translator.message_selector'] = $app->share(function () {
			return new MessageSelector();
		});

		$app['devture_localization.translator.resource_loader'] = $app->share(function ($app) {
			return new Translation\ResourceLoader($app['devture_localization.translator'], 'json');
		});

		$app['devture_localization.translator.loader'] = $app->share(function () {
			return new Translation\JsonFileLoader();
		});

		$app['devture_localization.twig.translation_extension'] = $app->share(function ($app) {
			return new TranslationExtension($app['devture_localization.translator']);
		});

		$app['devture_localization.twig.locale_helper_extension'] = $app->share(function ($app) use ($config) {
			return new Twig\LocaleHelperExtension($app, $config['locales']);
		});
	}

	public function boot(Application $app) {
		$app['dispatcher']->addSubscriber($app['devture_localization.locale_listener']);

		$app['twig']->addExtension($app['devture_localization.twig.translation_extension']);
		$app['twig']->addExtension($app['devture_localization.twig.locale_helper_extension']);
	}

}
