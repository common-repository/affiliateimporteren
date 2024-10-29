<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\Loader\LoaderFactory;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Tables\ProductAddTable;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductLoadDetails extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_load_details';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = array("state" => "ok", "message" => "", "goods" => array(), "images_content" => "");
        try {
            $productId = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : "";
            $product = ProductFactory::getWithId($productId);

            $loader = LoaderFactory::getLoader($product->getType());
            $result = $loader->loadDetail($product);

            $editFields = isset($_POST['edit_fields']) ? sanitize_text_field($_POST['edit_fields']) : "";
            if ($editFields) {
                $editFields = explode(",", $editFields);
            }

            if ($result['state'] === 'ok') {
                $descriptionContent = ProductAddTable::putDescriptionEdit(true);
                $product->setDescription('#hidden#');
                $result = [
                    "state" => "ok",
                    "goods" => $product->toArray($editFields),
                    "images_content" => ProductAddTable::putImageEdit($product, true),
                    "description_content" => $descriptionContent
                ];
            }
        } catch (\Exception $e) {
            $result['state'] = 'error';
            $result['message'] = $e->getMessage();
        }
        echo json_encode($result);
        wp_die();
    }
}
