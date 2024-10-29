<?php
namespace Dnolbon\AffiliateImporter\Loader;

class LoaderFactory
{
    /**
     * @param string $type
     * @return LoaderAbstract
     */
    public static function getLoader($type)
    {
        $className = '\Dnolbon\AffiliateImporter' . ucfirst($type) . '\AffiliateImporter' . ucfirst($type) . 'Loader';
        return new $className($type);
    }
}
