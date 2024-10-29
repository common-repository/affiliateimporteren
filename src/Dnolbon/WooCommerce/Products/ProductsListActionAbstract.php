<?php
namespace Dnolbon\WooCommerce\Products;

use Dnolbon\Wordpress\MainClassAbstract;

abstract class ProductsListActionAbstract
{
    /**
     * @var MainClassAbstract $mainClass
     */
    private $mainClass;

    protected $wpListTable;

    /**
     * ProductsListActionAbstract constructor.
     * @param MainClassAbstract $mainClass
     */
    public function __construct(MainClassAbstract $mainClass)
    {
        $this->mainClass = $mainClass;
    }

    abstract public function getAction();

    abstract public function getText();

    abstract public function process();

    abstract public function showNotice();

    /**
     * @return mixed
     */
    public function getWpListTable()
    {
        if ($this->wpListTable === null) {
            $this->wpListTable = _get_list_table('WP_Posts_List_Table');
        }
        return $this->wpListTable;
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

    protected function getPostIds()
    {
        $postIds = [];

        if (isset($_REQUEST['post'])) {
            $postIds = array_map('intval', $_REQUEST['post']);
        }
        return $postIds;
    }
}
