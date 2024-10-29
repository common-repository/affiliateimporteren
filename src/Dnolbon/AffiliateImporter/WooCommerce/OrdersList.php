<?php
namespace Dnolbon\AffiliateImporter\WooCommerce;

use Dnolbon\WooCommerce\Orders\OrdersListAbstract;

class OrdersList extends OrdersListAbstract
{
    public function assets()
    {
        $pluginData = get_plugin_data($this->getMainClass()->getMainFile());
        wp_enqueue_style(
            md5($this->getMainClass()->getMainFile()) . '-wc-ol-style',
            plugins_url('assets/css/wc_ol_style.css', $this->getMainClass()->getMainFile()),
            [],
            $pluginData['Version']
        );
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script(
            md5($this->getMainClass()->getMainFile()) . '-wc-ol-script',
            plugins_url('assets/js/wc_ol_script.js', $this->getMainClass()->getMainFile()),
            [],
            $pluginData['Version']
        );
    }
}
