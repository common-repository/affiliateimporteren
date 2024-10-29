<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\Wordpress\Ajax\AjaxAbstract;
use Dnolbon\AffiliateImporter\Products\Products;

class ProductUpdate extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_update_goods';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $mainClass = $this->getMainClass();

        $postId = isset($_REQUEST['post_id']) ? sanitize_text_field($_REQUEST['post_id']) : "";

        $externalId = get_post_meta($postId, "external_id", true);
        if ($externalId) {
            list($source) = explode('#', $externalId);

            $result = Products::updatePriceByPostId($source, $postId);
            $result['post_id'] = $postId;
        } else {
            $result = ["state" => "error", "message" => "Product with post id " . $postId . " not found"];
        }

        echo json_encode(apply_filters($mainClass->getClassPrefix().'_after_ajax_update_goods', $result));
        wp_die();
    }
}
