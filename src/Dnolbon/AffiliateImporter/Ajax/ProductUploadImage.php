<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Tables\ProductAddTable;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductUploadImage extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_upload_image';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = array("state" => "warning", "message" => "file not found");
        try {
            $productId = isset($_POST['upload_product_id']) ? sanitize_text_field($_POST['upload_product_id']) : "";
            $product = ProductFactory::getWithId($productId);

            if ($product->getIsNew() === false) {
                if (!function_exists('wp_handle_upload')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }

                if ($_FILES) {
                    foreach ($_FILES as $file => $array) {
                        if ($_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
                            $result["state"] = "error";
                            $result["message"] = "upload error : " . $_FILES[$file]['error'];
                        }
                        $uploadOverrides = ['test_form' => false];
                        $movefile = wp_handle_upload($array, $uploadOverrides);

                        if ($movefile && !isset($movefile['error'])) {
                            $movefile["url"];
                            $currentPhotos = $product->getCleanField('user_photos');
                            $product->setUserPhotos(($currentPhotos ? $currentPhotos . "," : "") . $movefile["url"]);
                            $product->setUserImage($movefile["url"]);

                            $result["state"] = "ok";
                            $result["message"] = "";
                            $result["goods"] = $product->toArray([]);
                            $result["images_content"] = ProductAddTable::putImageEdit($product, true);
                            $result["cur_image"] = $product->getImage();
                        } else {
                            $result["state"] = "error";
                            $result["message"] = "E1: " . $movefile['error'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $result['state'] = 'error';
            $result['message'] = $e->getMessage();
        }
        echo json_encode($result);
        wp_die();
    }
}
