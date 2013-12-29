<?php
namespace Devture\Bundle\LocalizationBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Listens for HTTP requests, extracts the {locale} attribute from them
 * and makes it the effective request and translator locale.
 */
class LocaleListener implements EventSubscriberInterface {

	private $defaultLocale;
	private $allowedLocales;
	private $translator;

	public function __construct($defaultLocale = 'en', array $allowedLocales, TranslatorInterface $translator) {
		$this->defaultLocale = $defaultLocale;
		$this->allowedLocales = $allowedLocales;
		$this->translator = $translator;
	}

	public function onKernelRequest(GetResponseEvent $event) {
		$request = $event->getRequest();

		$locale = $this->defaultLocale;

		$isAcceptableLocale = true;
		if ($requestLocale = $request->attributes->get('locale')) {
			if (!in_array($requestLocale, $this->allowedLocales)) {
				$isAcceptableLocale = false;
			} else {
				$locale = $requestLocale;
			}
		}

		if (!$isAcceptableLocale) {
			throw new HttpException(404, 'Bad locale.', null, array());
		}

		$request->setLocale($locale);
		$this->translator->setLocale($locale);
	}

	static public function getSubscribedEvents() {
		return array(
			KernelEvents::REQUEST => array(array('onKernelRequest')),
		);
	}
}
