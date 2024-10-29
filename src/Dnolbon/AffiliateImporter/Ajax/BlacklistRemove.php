<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;
use Dnolbon\Wordpress\Db\Db;

class BlacklistRemove extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_unblacklist';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $db = Db::getInstance()->getDb();
        $id = sanitize_text_field($_POST['id']);

        list($source, $externalId) = explode('#', $id);

        $importer = AffiliateImporter::getInstance()->getImporter($source);
        $db->delete($importer->getTableName('blacklist'), ['external_id' => $externalId]);
    }
}

