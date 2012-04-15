<?php
namespace Devture\Bundle\LocalizationBundle\Twig\Extension;
use Symfony\Component\HttpFoundation\Request;
use Devture\Bundle\LocalizationBundle\Routing\LocaleAwareUrlGenerator;

class LocaleHelperExtension extends \Twig_Extension {

    private $request;
    private $generator;
    private $currentLocale;
    private $locales;

    public function __construct(Request $request, LocaleAwareUrlGenerator $generator, $currentLocale, array $locales) {
        $this->request = $request;
        $this->generator = $generator;
        $this->currentLocale = $currentLocale;
        $this->locales = $locales;
    }

    public function getName() {
        return 'locale_helper_extension';
    }

    public function getFunctions() {
        return array(
            'get_locale' => new \Twig_Function_Method($this, 'getLocale'),
            'get_locales' => new \Twig_Function_Method($this, 'getLocales'),
            'get_localized_uri' => new \Twig_Function_Method($this, 'getLocalizedUri'),
            'get_translated' => new \Twig_Function_Method($this, 'getTranslated'),
            'path_localized' => new \Twig_Function_Method($this, 'getLocalizedPath'),
            'url_localized' => new \Twig_Function_Method($this, 'getLocalizedUrl'),
        );
    }

    public function getLocale() {
        return $this->currentLocale;
    }

    public function getLocales() {
        return $this->locales;
    }

    public function getLocalizedUri($newLocale) {
        $uri = $this->request->getRequestUri();
        if ($uri === '/') {
            return '/' . $newLocale;
        }
        return str_replace($this->currentLocale, $newLocale, $uri);
    }

    public function getTranslated($object, $attribute) {
        $getter = 'get' . ucfirst($attribute);
        return $object->$getter($this->currentLocale);
    }

    public function getLocalizedPath($endpoint, array $args = array()) {
        return $this->generator->generate($endpoint, $args);
    }

    public function getLocalizedUrl($endpoint, array $args = array()) {
        return $this->generator->generate($endpoint, $args, true);
    }

}

