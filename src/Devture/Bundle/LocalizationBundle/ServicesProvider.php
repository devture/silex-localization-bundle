<?php
namespace Devture\Bundle\LocalizationBundle;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Devture\Bundle\LocalizationBundle\Twig\Extension\LocaleHelperExtension;

class ServicesProvider implements ServiceProviderInterface {

    private $config;
    private $basePath;

    public function __construct(array $config, $basePath) {
        $this->config = $config;
        $this->basePath = $basePath;
    }

    public function register(Application $app) {
        $config = $this->config;

        //Expose some settings
        $app['default_locale'] = $config['default_locale'];
        $app['locales'] = $config['locales'];

        $app['url_generator_localized'] = $app->share(function () use ($app) {
            return new \Devture\Bundle\LocalizationBundle\Routing\LocaleAwareUrlGenerator($app, $app['routes'], $app['request_context']);
        });

        $app->register(new \Silex\Provider\TranslationServiceProvider(), array(
                'locale_fallback' => $config['fallback_locale'],));

        //Load translation messages from JSON files (see the 'translator.messages' service definiton)
        $app['translator.loader'] = new \Devture\Bundle\LocalizationBundle\Translation\JsonFileLoader();

        $messagesMap = array();
        foreach ($config['locales'] as $key => $data) {
            $messagesMap[$key] = $this->basePath . '/locales/' . $key . '.json';
        }
        $app['translator.messages'] = $messagesMap;

        $app['twig']->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($app['translator']));

        $app->before(function () use ($app, $config) {
            $app['locale'] = $config['default_locale'];

            $isInvalidLocale = false;
            if ($locale = $app['request']->get('locale')) {
                if (!in_array($locale, array_keys($config['locales']))) {
                    $isInvalidLocale = true;
                } else {
                    $app['locale'] = $locale;
                }
            }

            $app['translator']->setLocale($app['locale']);
            $app['twig']->addExtension(new LocaleHelperExtension($app['request'], $app['url_generator_localized'], $app['locale'], $config['locales']));

            if ($isInvalidLocale) {
                return $app->abort(404);
            }
        });
    }

}
