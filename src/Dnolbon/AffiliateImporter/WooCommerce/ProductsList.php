<?php
namespace Dnolbon\AffiliateImporter\WooCommerce;

use Dnolbon\WooCommerce\Products\ProductsListAbstract;

class ProductsList extends ProductsListAbstract
{
    public function __construct($mainClass)
    {
        $this->addAction(new ProductsListAction($mainClass));

        parent::__construct($mainClass);
    }

    public function assets()
    {
        $plugin_data = get_plugin_data($this->getMainClass()->getMainFile());
        wp_enqueue_style(
            md5($this->getMainClass()->getMainFile()) . '-wc-pl-style',
            plugins_url('assets/css/wc_pl_style.css', $this->getMainClass()->getMainFile()),
            [],
            $plugin_data['Version']
        );
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script(
            md5($this->getMainClass()->getMainFile()) . '-wc-pl-script',
            plugins_url('assets/js/wc_pl_script.js', $this->getMainClass()->getMainFile()),
            [],
            $plugin_data['Version']
        );
    }
}
