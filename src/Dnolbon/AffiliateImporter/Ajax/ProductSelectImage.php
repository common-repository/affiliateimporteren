<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductSelectImage extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_select_image';
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

            $product = ProductFactory::getWithId($productId);
            $product->setField('user_image', isset($_POST['image']) ? sanitize_text_field($_POST['image']) : "");
            $product->save();
        } catch (\Exception $e) {
            $result['state'] = 'error';
            $result['message'] = $e->getMessage();
        }

        echo json_encode($result);

        wp_die();
    }
}
