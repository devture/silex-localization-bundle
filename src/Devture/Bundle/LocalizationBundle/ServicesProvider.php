<?php
namespace Devture\Bundle\LocalizationBundle;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Devture\Bundle\LocalizationBundle\Translation\JsonFileLoader;
use Devture\Bundle\LocalizationBundle\Twig\Extension\LocaleHelperExtension;

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
            $translator = new Translator(isset($app['locale']) ? $app['locale'] : 'en', $app['translator.message_selector']);
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

        $app['twig']->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($app['translator']));

        $app->before(function () use ($app, $config) {
            $app['locale'] = $config['default_locale'];

            $isAcceptableLocale = true;
            if ($locale = $app['request']->attributes->get('locale')) {
                if (!in_array($locale, array_keys($config['locales']))) {
                    $isAcceptableLocale = false;
                } else {
                    $app['locale'] = $locale;
                }
            }

            $app['translator']->setLocale($app['locale']);
            $app['twig']->addExtension(new LocaleHelperExtension($app['request'], $app['url_generator_localized'], $app['locale'], $config['locales']));

            if (!$isAcceptableLocale) {
                return $app->abort(404);
            }
        });
    }

}
