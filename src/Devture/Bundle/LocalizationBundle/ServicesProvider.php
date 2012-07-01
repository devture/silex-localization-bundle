<?php
namespace Devture\Bundle\LocalizationBundle;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Devture\Bundle\LocalizationBundle\Translation\JsonFileLoader;
use Devture\Bundle\LocalizationBundle\Twig\Extension\LocaleHelperExtension;
use Devture\Bundle\LocalizationBundle\EventListener\LocaleListener;

class ServicesProvider implements ServiceProviderInterface {

    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function register(Application $app) {
        $config = $this->config;

        //Expose some settings
        $app['default_locale'] = $config['default_locale'];
        $app['fallback_locale'] = $config['fallback_locale'];
        $app['locales'] = $config['locales'];

        $app['url_generator_localized'] = $app->share(function () use ($app) {
            return new \Devture\Bundle\LocalizationBundle\Routing\LocaleAwareUrlGenerator($app, $app['routes'], $app['request_context']);
        });

        $app['translator'] = $app->share(function () use ($app) {
            $translator = new Translator('en', $app['translator.message_selector']);
            if (isset($app['fallback_locale'])) {
                $translator->setFallbackLocale($app['fallback_locale']);
            }
            $translator->addLoader('json', $app['localization.translator.loader']);
            return $translator;
        });

        $app['translator.message_selector'] = $app->share(function () {
            return new MessageSelector();
        });

        $app['localization.translator.resource_loader'] = $app->share(function () use ($app) {
            return new \Devture\Bundle\LocalizationBundle\Translation\ResourceLoader($app['translator'], 'json');
        });

        $app['localization.translator.loader'] = $app->share(function () {
            return new JsonFileLoader();
        });
    }

    public function boot(Application $app) {
        $config = $this->config;

        $app['dispatcher']->addSubscriber(new LocaleListener($config['default_locale'], array_keys($config['locales']), $app['translator']));

        $app['twig']->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($app['translator']));
        $app['twig']->addExtension(new LocaleHelperExtension($app, $config['locales']));
    }

}
