<?php
namespace Dnolbon\AffiliateImporterBanggood;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;

class AffiliateImporterBanggood extends AffiliateImporterAbstract
{

    public static function getClassName()
    {
        return 'AffImporterBg';
    }

    public function getClassPrefix()
    {
        return 'bgdn';
    }

    public function getAffiliateName()
    {
        return 'banggood';
    }

    protected function getCurrentVersion()
    {
        return 1;
    }
}
