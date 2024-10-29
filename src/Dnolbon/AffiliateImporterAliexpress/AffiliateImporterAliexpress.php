<?php
namespace Dnolbon\AffiliateImporterAliexpress;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;

class AffiliateImporterAliexpress extends AffiliateImporterAbstract
{

    public static function getClassName()
    {
        return 'AffImporterAl';
    }

    public function getClassPrefix()
    {
        return 'aeidn';
    }

    public function getAffiliateName()
    {
        return 'aliexpress';
    }

    protected function getCurrentVersion()
    {
        return 20;
    }
}
