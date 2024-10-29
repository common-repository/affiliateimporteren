<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductInfo extends AjaxAbstract
{
    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_product_info';
    }

    public function process()
    {
        /**
         * @var AffiliateImporterAbstract $mainClass
         */
        $mainClass = $this->getMainClass();

        $result = ["state" => "ok", "data" => ""];

        $postId = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : false;

        if (!$postId) {
            $result['state'] = 'error';
            echo json_encode($result);
            wp_die();
        }

        $externalId = get_post_meta($postId, "external_id", true);

        $timeValue = get_post_meta($postId, 'price_last_update', true);

        $timeValue = $timeValue ? date("Y-m-d H:i:s", $timeValue) : 'not updated';

        $productUrl = get_post_meta($postId, 'product_url', true);
        $sellerUrl = get_post_meta($postId, 'seller_url', true);

        $content = array();

        list($source, $externalId) = explode('#', $externalId);

        $content[] = "Source: <span class='" . $mainClass->getClassPrefix() . "_value'>" . $source . "</span>";
        $content[] = "Product url: <a target='_blank' href='" . $productUrl . "'>here</a>";

        if ($sellerUrl) {
            $content[] = "Seller url: <a target='_blank' href='" . $sellerUrl . "'>here</a>";
        }

        $content[] = "External ID: <span class='" . $mainClass->getClassPrefix() . "_value'>" . $externalId . "</span>";
        $content[] = "Last auto-update: <span class='" . $mainClass->getClassPrefix() . "_value'>" . $timeValue . "</span>";

        $content = apply_filters(
            $mainClass->getClassPrefix() . '_ajax_product_info',
            $content,
            $postId,
            $externalId,
            $source
        );
        $result['data'] = array('content' => $content, 'id' => $postId);

        echo json_encode($result);
        wp_die();
    }

    public function onlyForAdmin()
    {
        return true;
    }
}
