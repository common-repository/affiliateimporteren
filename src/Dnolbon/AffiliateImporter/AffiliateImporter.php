<?php
namespace Dnolbon\AffiliateImporter;

class AffiliateImporter
{
    /**
     * @var AffiliateImporter $instance
     */
    protected static $instance;
    /**
     * @var AffiliateImporterAbstract[] $importers
     */
    private $importers;

    /**
     * @return AffiliateImporter
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new AffiliateImporter();
        }

        return self::$instance;
    }

    /**
     * @param AffiliateImporterAbstract $object
     */
    public function addImporter($object)
    {
        $this->importers[$object->getAffiliateName()] = $object;
    }

    public function getImporter($type)
    {
        return $this->importers[$type];
    }
}
