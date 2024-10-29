<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;
use Dnolbon\Wordpress\Db\Db;

class ExportSettings extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_export_settings';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $db = Db::getInstance()->getDb();

        $filename = str_replace('.csv', '', $_GET['filename'] ? sanitize_text_field($_GET['filename']) : 'settings');

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $options = [];

        /**
         * @var AffiliateImporterAbstract $mainClass
         */
        $mainClass = $this->getMainClass();

        $sql = 'SELECT * FROM ' . $db->prefix . 'options where option_name like "'.$mainClass->getClassPrefix().'%"';

        $dbResult = $db->get_results($sql);
        if ($dbResult) {
            foreach ($dbResult as $row) {
                $options[] = [$row->option_name, $row->option_value];
            }
        }
        $outputBuffer = fopen("php://output", 'w');
        foreach ($options as $val) {
            fputcsv($outputBuffer, $val);
        }
        fclose($outputBuffer);

        wp_die();
    }
}
