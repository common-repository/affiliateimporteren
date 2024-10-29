<?php
namespace Dnolbon\AffiliateImporter\WooCommerce;

use Dnolbon\WooCommerce\Products\ProductsRowActionAbstract;

class ProductsRowAction extends ProductsRowActionAbstract
{
    public function render($actions, $post)
    {
        if ($post->post_type === 'product') {
            $externalId = get_post_meta($post->ID, 'external_id', true);

            if ($externalId) {
                $actions = array_merge(
                    $actions,
                    [
                        $this->getMainClass()->getClassPrefix() . '_product_info' => sprintf(
                            '<a class="%1$s-product-info" id="%1$s-%2$d" href="/">%3$s</a>',
                            $this->getMainClass()->getClassPrefix(),
                            $post->ID,
                            $this->getMainClass()->getClassName() . ' Info'
                        )
                    ]
                );
                return $actions;
            } else {
                return $actions;
            }
        }
        return [];
    }
}
