<?php
namespace Dnolbon\AffiliateImporterEnvato;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;

class AffiliateImporterEnvato extends AffiliateImporterAbstract
{

    public static function getClassName()
    {
        return 'AffImporterEn';
    }

    public function getClassPrefix()
    {
        return 'endn';
    }

    public function getAffiliateName()
    {
        return 'envato';
    }

    protected function getCurrentVersion()
    {
        return 1;
    }
}
