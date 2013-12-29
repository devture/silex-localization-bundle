# Silex Localization Bundle

A [Silex](http://silex.sensiolabs.org/) bundle that provides localization support.

It's like the Silex TranslationServiceProvider, but:

 - loads translations from JSON files
 - extracts `{locale}` values from route paths
 - makes the standard `$app['url_generator']` locale-aware: path()/url() calls maintain the {locale} value
 - activates the [TranslationExtension](https://github.com/symfony/TwigBridge/blob/2.4/Extension/TranslationExtension.php) from the [Twig](http://twig.sensiolabs.org/) bridge
 - provides some additional Twig helper functions
