<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class OrderInfo extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_order_info';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = array("state" => "ok", "data" => "");

        $post_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : false;

        if (!$post_id) {
            $result['state'] = 'error';
            echo json_encode($result);
            wp_die();
        }

        $content = array();

        $order = new \WC_Order($post_id);

        $items = $order->get_items();

        $k = 1;
        //echo "<strong>AffiliateImporterAl info:</strong><br/>";
        foreach ($items as $item) {
            $product_name = $item['name'];
            $product_id = $item['product_id'];

            $product_url = get_post_meta($product_id, 'product_url', true);
            $seller_url = get_post_meta($product_id, 'seller_url', true);

            $tmp = '';

            if ($product_url) {
                $tmp = $k . '). <a title="' . $product_name . '" href="' . $product_url . '" target="_blank" class="link_to_source product_url">Product page</a>';
            }

            if ($seller_url) {
                $tmp .= "<span class='seller_url_block'> | <a href='" . $seller_url . "' target='_blank' class='seller_url'>Seller</a></span>";
            }

            $content[] = $tmp;
            $k++;
        }

        $result['data'] = array('content' => $content, 'id' => $post_id);

        echo json_encode($result);
        wp_die();
    }
}
