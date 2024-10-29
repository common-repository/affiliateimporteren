<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;
use Dnolbon\Wordpress\Db\Db;

class Unshedule extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_unshedule';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $db = Db::getInstance()->getDb();
        $id = sanitize_text_field($_POST['id']);

        /**
         * @var AffiliateImporterAbstract $mainClass
         */
        $mainClass = $this->getMainClass();

        $db->update(
            $mainClass->getTableName('goods_archive'),
            ['user_schedule_time' => null],
            ['external_id' => $id]
        );
        $db->update(
            $mainClass->getTableName('goods'),
            ['user_schedule_time' => null],
            ['external_id' => $id]
        );
    }
}

