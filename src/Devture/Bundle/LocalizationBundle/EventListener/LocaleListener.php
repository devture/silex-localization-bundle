<?php
namespace Devture\Bundle\LocalizationBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
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

		//Marking it for our own tracking purposes (see below).
		$request->attributes->set('LocalizationBundle_determined_locale', $locale);
	}

	public function onKernelException(GetResponseForExceptionEvent $event) {
		//This route gets called for 2 reasons:
		//--
		//1) 404 errors that never reached the controller (URL-matching level),
		//in which case onKernelRequest() above never gets called and doesn't determine
		//the locale.
		//--
		//2) 404 or other errors tha happen within the request's processing (controller, etc.),
		//for which, we've already determined a locale.
		//--
		//Let's handle both cases.
		if ($event->getRequest()->attributes->has('LocalizationBundle_determined_locale')) {
			//Already determined, don't run fallback logic.
			return;
		}

		//We have reached an exception before onKernelRequest().
		//This is likely a 404 error (can't match any route).
		//Let's execute some fallback logic.
		$event->getRequest()->setDefaultLocale($this->defaultLocale);
		$this->translator->setLocale($this->defaultLocale);
		$event->getRequest()->attributes->set('LocalizationBundle_determined_locale', $this->defaultLocale);
	}

	static public function getSubscribedEvents() {
		return array(
			KernelEvents::REQUEST => array(array('onKernelRequest')),
			KernelEvents::EXCEPTION => array(array('onKernelException')),
		);
	}
}
