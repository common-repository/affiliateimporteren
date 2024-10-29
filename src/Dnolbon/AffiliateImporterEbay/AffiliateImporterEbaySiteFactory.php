<?php
namespace Dnolbon\AffiliateImporterEbay;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;

class AffiliateImporterEbaySiteFactory
{
    public static function getById($id)
    {
        $db = Db::getInstance()->getDb();

        $importer = AffiliateImporter::getInstance()->getImporter('ebay');

        $tableName = $importer->getTableName('sites');

        $dbRes = $db->get_row("SELECT * FROM " . $tableName . " where id = '" . addslashes($id) . "' ");

        return self::getByObject($dbRes);
    }

    public static function getByObject($dbRes)
    {
        $result = new AffiliateImporterEbaySite();
        $result->setId($dbRes->id);
        $result->setCountry($dbRes->country);
        $result->setLanguage($dbRes->language);
        $result->setSiteid($dbRes->siteid);
        $result->setSitecode($dbRes->sitecode);
        $result->setSitename($dbRes->sitename);
        return $result;
    }
}
