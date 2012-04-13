<?php
namespace Devture\Bundle\LocalizationBundle\Translation;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;

class JsonFileLoader extends ArrayLoader implements LoaderInterface {

    public function load($resource, $locale, $domain = 'messages') {
        $messages = json_decode(file_get_contents($resource), 1);

        // empty file
        if (null === $messages) {
            $messages = array();
        }

        // not an array
        if (!is_array($messages)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a JSON array.', $resource));
        }

        $catalogue = parent::load($messages, $locale, $domain);
        $catalogue->addResource(new FileResource($resource));

        return $catalogue;
    }

}
