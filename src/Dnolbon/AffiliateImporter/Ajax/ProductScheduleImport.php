<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductScheduleImport extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_schedule_import_goods';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = ['state' => 'ok', 'message' => ''];
        try {
            $timeStr = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : "";
            $time = $timeStr ? date("Y-m-d H:i:s", strtotime($timeStr)) : "";

            $productId = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : "";
            $product = ProductFactory::getWithId($productId);
            if ($product->getIsNew() === false && $time) {
                $result['message'] = sanitize_text_field($_POST['id']) . " loaded " . $time;
                $result['time'] = date("m/d/Y H:i", strtotime($time));
                $product->setUserScheduleTime($time);
                $product->save();
            } else {
                $result['message'] = sanitize_text_field($_POST['id']) . " not loaded " . $time;
            }
        } catch (\Exception $e) {
            $result['state'] = 'error';
            $result['message'] = $e->getMessage();
        }

        echo json_encode($result);

        wp_die();
    }
}
