<?php
namespace Devture\Bundle\LocalizationBundle\Translation;

use Symfony\Component\Translation\Translator;

class ResourceLoader {

    private $translator;
    private $format;

    public function __construct(Translator $translator, $format) {
        $this->translator = $translator;
        $this->format = $format;
    }

    public function addResources($path, $loaderName = null) {
        if ($loaderName === null) {
            $loaderName = $this->format;
        }
        $path = rtrim($path, '/');
        foreach (glob($path . '/*.' . $this->format) as $filePath) {
            $parts = explode('/', $filePath);
            list($localeKey, $_extension) = explode('.', array_pop($parts));
            $this->translator->addResource($loaderName, $filePath, $localeKey);
        }
    }

}
