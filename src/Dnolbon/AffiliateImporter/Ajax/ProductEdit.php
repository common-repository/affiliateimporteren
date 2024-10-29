<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductEdit extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_edit_goods';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = array("state" => "ok", "message" => "");
        try {
            $productId = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : "";

            $field = (isset($_POST['field']) ? sanitize_text_field($_POST['field']) : false);
            $value = (isset($_POST['value']) ? sanitize_text_field($_POST['value']) : "");
            $value = stripslashes($value);

            $product = ProductFactory::getWithId($productId);
            $product->setField($field, $value);
            $product->save();
        } catch (\Exception $e) {
            $result['state'] = 'error';
            $result['message'] = $e->getMessage();
        }

        echo json_encode($result);

        wp_die();
    }
}
