<?php
namespace Dnolbon\AffiliateImporter\Products;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Loader\LoaderFactory;
use Dnolbon\WooCommerce\WooCommerce;
use Dnolbon\Wordpress\Db\Db;

class Products
{
    public static function clearList($type, $deleteSheduledPost = false)
    {
        $db = Db::getInstance()->getDb();

        $importer = AffiliateImporter::getInstance()->getImporter($type);

        if ($deleteSheduledPost) {
            $db->query('TRUNCATE ' . $importer->getTableName('goods'));
        } else {
            $sql = 'DELETE FROM ' . $importer->getTableName('goods');
            $sql .= ' WHERE NULLIF(NULLIF(user_schedule_time, "0000-00-00 00:00:00"), "") IS NULL';
            $db->query($sql);
        }
    }

    public static function updatePriceByPostId($type, $productId = false)
    {
        $importer = AffiliateImporter::getInstance()->getImporter($type);
        $classPrefix = $importer->getClassPrefix();

        $result = ['state' => 'ok', 'message' => ''];

        if (!get_option($classPrefix . '_price_auto_update', false)) {
            return false;
        }
        $updatePrice = get_option($classPrefix . '_regular_price_auto_update', false);

        try {
            if ($productId) {
                $postsByTime = [$productId];
            } else {
                $postsByTime = self::getSortedProducts(
                    'price_last_update',
                    get_option($classPrefix . '_update_per_schedule', 20),
                    $type
                );
            }

            $productStatus = get_option($classPrefix . '_not_available_product_status', 'trash');

            foreach ($postsByTime as $postId) {
                $externalId = get_post_meta($postId, 'external_id', true);
                if ($externalId) {
                    $product = ProductFactory::getWithId($externalId);
                    $loader = LoaderFactory::getLoader($product->getType());

                    if ($loader) {
                        $filters = get_post_meta($postId, '_' . $classPrefix . '_filters', true);

                        $result = $loader->loadDetail(
                            $product,
                            array_merge(
                                ['wc_product_id' => $postId],
                                is_array($filters) ? $filters : array()
                            )
                        );

                        if ($result['state'] === 'ok') {

                            // check availability
                            if (!$product->isAvailable()) {
                                if ($productStatus === 'trash') {
                                    wp_trash_post($postId);
                                } elseif ($productStatus === 'outofstock') {
                                    update_post_meta($postId, '_manage_stock', 'yes');
                                    update_post_meta($postId, '_stock_status', 'outofstock');
                                    update_post_meta($postId, '_stock', 0);
                                }
                            } else {
                                wp_untrash_post($postId);
                                $additionalMeta = unserialize($product->getAdditionalMeta());

                                if (isset($additionalMeta['quantity'])) {
                                    update_post_meta($postId, '_manage_stock', 'yes');
                                    update_post_meta($postId, '_visibility', 'visible');
                                    update_post_meta($postId, '_stock', (int)$additionalMeta['quantity']);
                                } else {
                                    $minQ = (int)get_option($classPrefix . '_min_product_quantity', 5);
                                    $maxQ = (int)get_option($classPrefix . '_max_product_quantity', 10);
                                    $minQ = $minQ ? $minQ : 1;
                                    $maxQ = $maxQ ? $maxQ : $minQ;
                                    $quantity = mt_rand($minQ, $maxQ);

                                    update_post_meta($postId, '_stock', $quantity);
                                    update_post_meta($postId, '_manage_stock', 'yes');
                                    update_post_meta($postId, '_stock_status', 'instock');
                                }

                                if ($updatePrice) {
                                    if ($postId) {
                                        WooCommerce::updatePrice($postId, $product);
                                    }
                                }
                            }
                            $result = apply_filters(
                                $classPrefix . '_woocommerce_update_price',
                                $result,
                                $postId,
                                $product
                            );
                        }

                        update_post_meta($postId, 'price_last_update', time());
                    }
                }
            }
        } catch (\Exception $e) {
            $result = ['state' => 'error', 'message' => $e->getMessage()];
        }
        return $result;
    }

    public static function getSortedProducts($sortType, $idsCount, $type)
    {
        $result = [];

        $ids0 = get_posts([
            'post_type' => 'product',
            'fields' => 'ids',
            'numberposts' => $idsCount,
            'meta_query' => [
                [
                    'key' => 'import_type',
                    'value' => [$type],
                    'compare' => 'IN'
                ],
                [
                    'key' => $sortType,
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        foreach ($ids0 as $id) {
            $result[] = $id;
        }

        if (($idsCount - count($result)) > 0) {
            $res = get_posts(array(
                'post_type' => 'product',
                'fields' => 'ids',
                'numberposts' => $idsCount - count($result),
                'meta_query' => array(
                    array(
                        'key' => 'import_type',
                        'value' => ['type'],
                        'compare' => 'IN'
                    )
                ),
                'order' => 'ASC',
                'orderby' => 'meta_value',
                'meta_key' => $sortType,
                //allow hooks
                'suppress_filters' => false
            ));

            foreach ($res as $id) {
                $result[] = $id;
            }
        }
        return $result;
    }
}
