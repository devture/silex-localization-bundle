<?php
namespace Devture\Bundle\LocalizationBundle\Translation;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;

class JsonFileLoader extends ArrayLoader implements LoaderInterface {

	public function load($resource, $locale, $domain = 'messages') {
		$contents = file_get_contents($resource);

		$messages = array();
		if ($contents !== '') {
			$messages = json_decode($contents, 1);

			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new \InvalidArgumentException(sprintf(
					'The file "%s" cannot be JSON-decoded: %s.',
					$resource,
					json_last_error_msg()
				));
			}
		}

		if (!is_array($messages)) {
			throw new \InvalidArgumentException(sprintf('The file "%s" must contain a JSON array.', $resource));
		}

		$catalogue = parent::load($messages, $locale, $domain);
		$catalogue->addResource(new FileResource($resource));

		return $catalogue;
	}

}
