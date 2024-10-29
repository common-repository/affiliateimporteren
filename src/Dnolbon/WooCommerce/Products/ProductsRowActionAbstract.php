<?php
namespace Dnolbon\WooCommerce\Products;

use Dnolbon\Wordpress\MainClassAbstract;

abstract class ProductsRowActionAbstract
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
        add_filter('post_row_actions', [$this, 'render'], $this->getPriority(), $this->getAcceptedArgs());
    }

    private function getPriority()
    {
        return 10;
    }

    private function getAcceptedArgs()
    {
        return 2;
    }

    abstract public function render($actions, $post);

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
