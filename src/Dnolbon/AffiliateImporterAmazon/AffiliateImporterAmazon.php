<?php
namespace Dnolbon\AffiliateImporterAmazon;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;

class AffiliateImporterAmazon extends AffiliateImporterAbstract
{

    public static function getClassName()
    {
        return 'AffImporterAm';
    }

    public function getClassPrefix()
    {
        return 'aidn';
    }

    public function getAffiliateName()
    {
        return 'amazon';
    }

    protected function getCurrentVersion()
    {
        return 20;
    }
}
