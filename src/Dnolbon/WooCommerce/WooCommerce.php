<?php
namespace Dnolbon\WooCommerce;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Products\Product;
use Dnolbon\AffiliateImporter\Utils\Curl;
use Dnolbon\Wordpress\Db\Db;

class WooCommerce
{
    /**
     * @param Product $product
     * @param array $params
     * @return array
     */
    public static function addPost($product, $params = [])
    {
        $productType = $product->getType();
        $importer = AffiliateImporter::getInstance()->getImporter($productType);
        $classPrefix = $importer->getClassPrefix();

        $result = ['state' => 'ok', 'message' => ''];

        $productStatus = get_option($classPrefix . '_default_status', 'publish');
        if (isset($params['import_status']) && $params['import_status']) {
            $productStatus = $params['import_status'];
        }

        $post = array(
            'post_title' => $product->getTitle(),
            'post_content' => $product->getCleanField('description'),
            'post_status' => $productStatus,
            'post_name' => $product->getTitle(),
            'post_type' => 'product'
        );
        $productId = $product->getPostId();
        $postId = $productId;
        if (!$productId) {
            $postId = wp_insert_post($post);
        }
        $categories = $product->getCategoryLink();
        $productType = get_option($classPrefix . '_default_type', 'simple');

        wp_set_object_terms($postId, $categories, 'product_cat');
        wp_set_object_terms($postId, $productType, 'product_type');
        update_post_meta($postId, '_stock_status', 'instock');
        update_post_meta($postId, '_sku', (string)$product->getExternalId());
        update_post_meta($postId, '_product_url', $product->getDetailUrl());

        update_post_meta($postId, 'import_type', $product->getType());
        update_post_meta($postId, 'external_id', (string)$product->getFullId());
        update_post_meta($postId, 'seller_url', $product->getSellerUrl());
        update_post_meta($postId, 'product_url', $product->getDetailUrl());
        update_post_meta($postId, $classPrefix . '_import', 1);

        self::updatePrice($postId, $product);

        $additionalMeta = $product->getCleanField('additional_meta');

        if (isset($additionalMeta['quantity'])) {
            update_post_meta($postId, '_manage_stock', 'yes');
            update_post_meta($postId, '_visibility', 'visible');
            update_post_meta($postId, '_stock', (int)$additionalMeta['quantity']);
        } else {
            $min_q = (int)get_option($classPrefix . '_min_product_quantity', 5);
            $max_q = (int)get_option($classPrefix . '_max_product_quantity', 10);
            $min_q = $min_q ? $min_q : 1;
            $max_q = ($max_q && $max_q > $min_q) ? $max_q : $min_q;
            $quantity = mt_rand($min_q, $max_q);

            if ($max_q > 1) {
                update_post_meta($postId, '_manage_stock', 'yes');
                update_post_meta($postId, '_stock', $quantity);
                update_post_meta($postId, '_visibility', 'visible');
            }
        }

        if (isset($additionalMeta['filters']) && $additionalMeta['filters']) {
            update_post_meta($postId, '_' . $classPrefix . '_filters', $additionalMeta['filters']);
        }

        if (isset($additionalMeta['detail_url']) && $additionalMeta['detail_url']) {
            update_post_meta($postId, 'original_product_url', $additionalMeta['detail_url']);
        }

        if (isset($additionalMeta['ship'])) {
            update_post_meta($postId, 'ship_price', $additionalMeta['ship']);
        }

        if ($additionalMeta && is_array($additionalMeta)) {
            if (isset($additionalMeta['attribute']) && $additionalMeta['attribute']) {
                self::setAttributes($postId, $additionalMeta['attribute']);
            }
            if (isset($additionalMeta['discount_perc']) && strlen(trim((string)$additionalMeta['discount_perc'])) > 0) {
                update_post_meta($postId, 'discount_perc', $additionalMeta['discount_perc']);
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $thumbUrl = $product->getImage();
        if ($thumbUrl) {
            $thumbId = self::imageAttacher($thumbUrl, $productId);
            if (!is_object($thumbId)) {
                set_post_thumbnail($postId, $thumbId);
            }
        }

        $imagesUrl = $product->getAllPhotos();

        $imagesLimit = (int)get_option($classPrefix . '_import_product_images_limit');

        $imageGalleryIds = '';
        $cnt = 0;
        foreach (array_slice($imagesUrl, 1) as $imageUrl) {
            if ($thumbUrl !== $imageUrl) {
                if (!$imagesLimit || ($cnt++) < $imagesLimit) {
                    try {
                        $thumbId = self::imageAttacher($imageUrl, $postId) ;
                        if (!is_object($thumbId)) {
                            $imageGalleryIds .= $thumbId. ',';
                        }
                    } catch (\Exception $e) {
                        $result['state'] = 'warn';
                        $result['message'] = "\nimg_warn: $imageUrl";
                    }
                }
            }
        }
        update_post_meta($postId, '_product_image_gallery', $imageGalleryIds);

        apply_filters($classPrefix . '_woocommerce_after_addpost', $result, $postId, $product);

        return $result;
    }

    /**
     * @param int $postId
     * @param Product $product
     */
    public static function updatePrice($postId, $product)
    {
        if (!$product->getUserRegularPrice()) {
            update_post_meta($postId, '_price', $product->getUserPrice());
            update_post_meta($postId, '_regular_price', $product->getUserPrice());
            delete_post_meta($postId, '_sale_price');
        } else {
            if (abs($product->getUserPrice() - $product->getUserRegularPrice()) < 0.001) {
                update_post_meta($postId, '_price', $product->getUserPrice());
                update_post_meta($postId, '_regular_price', $product->getUserPrice());
                delete_post_meta($postId, '_sale_price');
            } else {
                update_post_meta($postId, '_regular_price', $product->getUserRegularPrice());
                update_post_meta($postId, '_sale_price', $product->getUserPrice());
                update_post_meta($postId, '_price', $product->getUserPrice());
            }
        }
    }

    public static function setAttributes($postId, $attributes)
    {
        $extendedAttribute = null;

        if ($extendedAttribute) {
            foreach ($attributes as $name => $value) {
                self::saveOneAttribute($value);
            }
        } else {
            $name = array_column($attributes, 'name');
            $count = array_count_values($name);
            $duplicate = array_unique(array_diff_assoc($name, array_unique($name)));
            $productAttributes = '';

            foreach ($attributes as $name => $value) {
                if (isset($duplicate[$name + 1])) {
                    $val = array();
                    for ($i = 0; $i < $count[$value['name']]; $i++) {
                        $val[] = $attributes[$name + $i]['value'];
                    }
                    $productAttributes[str_replace(' ', '-', $value['name'])] = array(
                        'name' => $value['name'],
                        'value' => implode(', ', $val),
                        'position' => 0,
                        'is_visible' => 1,
                        'is_variation' => 0,
                        'is_taxonomy' => 0
                    );
                } elseif (!in_array($value['name'], $duplicate, false)) {
                    $productAttributes[str_replace(' ', '-', $value['name'])] = array(
                        'name' => $value['name'],
                        'value' => $value['value'],
                        'position' => 0,
                        'is_visible' => 1,
                        'is_variation' => 0,
                        'is_taxonomy' => 0
                    );
                }
            }

            update_post_meta($postId, '_product_attributes', $productAttributes);
        }
    }

    private static function saveOneAttribute($attributeData)
    {
        $db = Db::getInstance()->getDb();

        $attribute = array(
            'attribute_label' => wc_clean(stripslashes($attributeData['name'])),
            'attribute_name' => wc_sanitize_taxonomy_name(stripslashes($attributeData['name'])),
            'attribute_type' => 'select',
            'attribute_orderby' => '',
            'attribute_public' => 0
        );

        if (!taxonomy_exists(wc_attribute_taxonomy_name($attribute['attribute_name']))) {
            $db->insert($db->prefix . 'woocommerce_attribute_taxonomies', $attribute);
        }

        flush_rewrite_rules();

        delete_transient('wc_attribute_taxonomies');
    }

    public static function imageAttacher($imageUrl, $postId)
    {
        $image = self::downloadUrl($imageUrl);
        if ($image) {
            $file_array = array(
                'name' => basename($image),
                'size' => filesize($image),
                'tmp_name' => $image
            );
            return media_handle_sideload($file_array, $postId);
        }
        return false;
    }

    public static function downloadUrl($url)
    {
        $wpUploadDir = wp_upload_dir();
        $parsedUrl = parse_url($url);
        $pathinfo = pathinfo($parsedUrl['path']);

        $destFilename = wp_unique_filename($wpUploadDir['path'], mt_rand() . '.' . $pathinfo['extension']);

        $destPath = $wpUploadDir['path'] . '/' . $destFilename;

        $response = Curl::get($url);
        if (is_wp_error($response)) {
            return false;
        } elseif (!in_array($response['response']['code'], [404, 403], false)) {
            file_put_contents($destPath, $response['body']);
        }

        if (!file_exists($destPath)) {
            return false;
        } else {
            return $destPath;
        }
    }
}
