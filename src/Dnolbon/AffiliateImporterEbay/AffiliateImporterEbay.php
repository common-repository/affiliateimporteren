<?php
namespace Dnolbon\AffiliateImporterEbay;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;

class AffiliateImporterEbay extends AffiliateImporterAbstract
{

    public static function getClassName()
    {
        return 'AffImporterEb';
    }

    public function getClassPrefix()
    {
        return 'ebidn';
    }

    public function getAffiliateName()
    {
        return 'ebay';
    }

    protected function getCurrentVersion()
    {
        return 20;
    }
}
