<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\AffiliateImporter\Loader\LoaderFactory;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Tables\ProductAddTable;
use Dnolbon\WooCommerce\WooCommerce;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductImport extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_import_goods';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = ["state" => "ok", "message" => ""];

        try {
            $productId = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : "";

            $editFields = isset($_POST['edit_fields']) ? sanitize_text_field($_POST['edit_fields']) : "";
            if ($editFields) {
                $editFields = explode(",", $editFields);
            }

            $product = ProductFactory::getWithId($productId);
            if ($product->getIsNew() === false) {
                if ($product->isNeedLoad()) {
                    $loader = LoaderFactory::getLoader($product->getType());
                    $loader->loadDetail($product);
                }
                $product->setUserScheduleTime(null);
                $product->save();

                if ($product->getPostId() === null) {
                    $result = WooCommerce::addPost(
                        $product,
                        ['import_status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish']
                    );
                }

                $descriptionContent = ProductAddTable::putDescriptionEdit(true);
                $result = [
                    "state" => "ok",
                    "goods" => $product->toArray($editFields),
                    "images_content" => ProductAddTable::putImageEdit($product, true),
                    "description_content" => $descriptionContent
                ];
            } else {
                $result['state'] = 'error';
                $result['message'] = "Product " . sanitize_text_field($_POST['id']) . " not find.";
            }
        } catch (\Exception $e) {
            $result['state'] = 'error';
            $result['message'] = $e->getMessage();
        }

        echo json_encode($result);

        wp_die();
    }
}
