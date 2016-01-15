<?php
namespace Devture\Bundle\LocalizationBundle\Twig;

class LocaleHelperExtension extends \Twig_Extension {

	private $container;
	private $locales;

	public function __construct(\Pimple $container, array $locales) {
		$this->container = $container;
		$this->locales = $locales;
	}

	public function getName() {
		return 'devture_localization_locale_helper_extension';
	}

	public function getFunctions() {
		return array(
			'get_locale' => new \Twig_Function_Method($this, 'getLocale'),
			'get_locales' => new \Twig_Function_Method($this, 'getLocales'),
			'get_localized_uri' => new \Twig_Function_Method($this, 'getLocalizedUri'),
			'get_translated' => new \Twig_Function_Method($this, 'getTranslated'),
			'get_translated_or_first' => new \Twig_Function_Method($this, 'getTranslatedOrFirst'),
		);
	}

	public function getLocale() {
		return $this->getRequest()->getLocale();
	}

	public function getLocales() {
		return $this->locales;
	}

	/**
	 * Generates a modification of the current URL, when switched to the new locale, under these assumptions:
	 *  - the {locale} attribute is always the first part of the path: /{locale}/news/view
	 *  - / is the same as /{locale}
	 *
	 * @param string $newLocale
	 * @return string
	 */
	public function getLocalizedUri($newLocale) {
		$uri = $this->getRequest()->getRequestUri();
		if ($uri === '/') {
			return '/' . $newLocale;
		}
		return preg_replace("/\/" . preg_quote($this->getLocale()) . "(\/|$)/", "/" . preg_quote($newLocale) . "$1", $uri);
	}

	/**
	 * Calls the getter method for $attribute, passing the current locale key as an argument to it.
	 * If that's empty, it uses the fallback value (if provided).
	 *
	 * `get_translated(entity, 'something')` is equivalent to
	 * 1. `$entity->getSomething($currentLocaleKey)`
	 * 2. `$fallbackValue`
	 *
	 * @param object $object
	 * @param string $attribute
	 * @param mixed $fallbackValue
	 * @throws \InvalidArgumentException when the getter method does not exist
	 * @return mixed
	 */
	public function getTranslated($object, $attribute, $fallbackValue = null) {
		$getter = 'get' . ucfirst($attribute);
		if (!method_exists($object, $getter)) {
			throw new \InvalidArgumentException('Trying to get translated attribute via missing getter method ' . get_class($object) . '::' . $getter);
		}

		$value = $object->$getter($this->getLocale());
		if ($value === null || $value === '') {
			return $fallbackValue;
		}

		return $value;
	}

	/**
	 * Calls the getter method for $attribute, passing the current locale key as an argument to it.
	 * If that's empty, it tries to call the "first" getter.
	 * If that's empty, it uses the fallback value (if provided).
	 *
	 * `get_translated_or_first(entity, 'something')` is equivalent to:
	 * 1. `$entity->getSomething($currentLocaleKey)`
	 * 2. `$entity->getSomethingFirst()`
	 * 3. `$fallbackValue`
	 *
	 * @param object $object
	 * @param string $attribute
	 * @param mixed $fallbackValue
	 * @throws \InvalidArgumentException when the getter method does not exist
	 * @return mixed
	 */
	public function getTranslatedOrFirst($object, $attribute, $fallbackValue = null) {
		$getterFirst = 'get' . ucfirst($attribute) . 'First';
		if (!method_exists($object, $getterFirstj)) {
			throw new \InvalidArgumentException('Trying to get translated attribute via missing first getter method ' . get_class($object) . '::' . $getterFirst);
		}

		$value = $this->getTranslated($object, $attribute, $object->$getterFirst());
		if ($value === null || $value === '') {
			return $fallbackValue;
		}

		return $value;
	}

	/**
	 * @throws \RuntimeException when not in a request context
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	private function getRequest() {
		return $this->container['request'];
	}

}

