<?php
namespace Dnolbon\AffiliateImporter;

use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\Wordpress\Db\Db;

class WordpressStats
{
    private $type;

    public function __construct($type)
    {
        $this->type = $type;

        add_action('woocommerce_product_add_to_cart_url', [$this, 'buildLink']);

        add_action('wp', [$this, 'registerHit'], 0);
        add_action('woocommerce_add_to_cart', [$this, 'addToCart'], 1, 3);
    }

    public function buildLink($link)
    {
        $currentId = get_the_ID();
        $importer = AffiliateImporter::getInstance()->getImporter($this->type);
        $externalId = get_post_meta($currentId, 'external_id', true);

        $product = ProductFactory::getWithId($externalId);

        if ($product->getType() === $importer->getAffiliateName()) {
            $url = admin_url('admin-ajax.php');
            $url .= '?action=' . $importer->getClassPrefix() . '_redirect';
            $url .= '&link=' . urlencode($link) . '&id=' . $currentId;
            return $url;
        } else {
            return $link;
        }
    }

    public function registerHit()
    {
        if (!is_admin()) {
            global $post;
            if ($post) {
                $importer = AffiliateImporter::getInstance()->getImporter($this->type);

                $postId = (int)$post->ID;

                if ($postId <= 0) {
                    return false;
                }

                Db::getInstance()->getDb()->insert(
                    $importer->getTableName('stats'),
                    ['date' => date('Y-m-d'), 'product_id' => $postId]
                );
            }
        }
    }

    public function addToCart($cartItemKey = '', $productId = 0, $quantity = 0)
    {

        if (!is_admin()) {
            $postId = $productId;

            if ($postId <= 0) {
                return false;
            }

            $importer = AffiliateImporter::getInstance()->getImporter($this->type);

            Db::getInstance()->getDb()->insert(
                $importer->getTableName('stats'),
                ['date' => date('Y-m-d'), 'product_id' => $postId, 'quantity' => $quantity]
            );

            return true;
        }
    }
}
