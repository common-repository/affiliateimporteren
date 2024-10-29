<?php
namespace Dnolbon\WooCommerce\Orders;

use Dnolbon\Wordpress\MainClassAbstract;

abstract class OrdersColumnActionAbstract
{
    /**
     * @var MainClassAbstract $mainClass
     */
    private $mainClass;

    /**
     * ProductsRowActionAbstract constructor.
     * @param MainClassAbstract $mainClass
     */
    public function __construct($mainClass)
    {
        $this->setMainClass($mainClass);
        add_action(
            'manage_shop_order_posts_custom_column',
            [$this, 'render'],
            $this->getPriority(),
            $this->getAcceptedArgs()
        );
    }

    private function getPriority()
    {
        return 10;
    }

    private function getAcceptedArgs()
    {
        return 1;
    }

    abstract public function render($column);

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
}
