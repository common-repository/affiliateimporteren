<?php
namespace Dnolbon\WooCommerce\Orders;

use Dnolbon\Wordpress\MainClassAbstract;

abstract class OrdersListAbstract
{
    /**
     * @var MainClassAbstract $mainClass
     */
    private $mainClass;

    public function __construct($mainClass)
    {
        $this->setMainClass($mainClass);

        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    /**
     * @return MainClassAbstract
     */
    public function getMainClass()
    {
        return $this->mainClass;
    }

    /**
     * @param MainClassAbstract $mainClass
     */
    public function setMainClass($mainClass)
    {
        $this->mainClass = $mainClass;
    }

    abstract public function assets();

    protected function getPostType()
    {
        global $typenow, $post_type;
        if ($typenow !== null) {
            return $typenow;
        }

        if ($post_type !== null) {
            return $post_type;
        }
        return '';
    }
}
