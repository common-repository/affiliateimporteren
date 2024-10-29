<?php
namespace Dnolbon\AffiliateImporter\WooCommerce;

use Dnolbon\WooCommerce\Orders\OrdersColumnActionAbstract;

class OrdersInfoColumn extends OrdersColumnActionAbstract
{
    public function render($column)
    {
        if ($column === 'order_title') {
            global $post;

            $actions = array();

            if ($column === 'order_title') {
                $actions = array_merge($actions, [
                    $this->getMainClass()->getClassPrefix() . '_order_info' => sprintf(
                        '<a class="%1$s-order-info" id="%1$s-%2$d" href="/">%3$s</a>',
                        $this->getMainClass()->getClassPrefix(),
                        $post->ID,
                        $this->getMainClass()->getClassName() . ' Info'
                    )
                ]);

            }

            if (count($actions) > 0) {
                echo implode($actions, ' | ');
            }
        }
    }
}
