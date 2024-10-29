<?php
namespace Dnolbon\AffiliateImporter\Configurator;

class ConfiguratorFactory
{
    /**
     * @param string $type
     * @return ConfiguratorAbstract
     */
    public static function getConfigurator($type)
    {
        $className = '\Dnolbon\AffiliateImporter' . ucfirst($type);
        $className .= '\AffiliateImporter' . ucfirst($type) . 'Configurator';
        return new $className($type);
    }
}
