<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;
use Dnolbon\Wordpress\Db\Db;

class WoocommerceRedirect extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_redirect';
    }

    public function onlyForAdmin()
    {
        return false;
    }

    public function process()
    {
        /**
         * @var AffiliateImporterAbstract $mainClass
         */
        $mainClass = $this->getMainClass();

        $link = sanitize_text_field(urldecode($_GET['link']));
        $id = sanitize_text_field($_GET['id']);
        if (!is_admin()) {
            Db::getInstance()->getDb()->insert(
                $mainClass->getTableName('stats'),
                ['date' => date('Y-m-d'), 'product_id' => $id, 'quantity' => 1]
            );
        }
        $link = str_replace('&#038;', '&', $link);

        header('Location: ' . $link . '');
        exit();
    }
}
