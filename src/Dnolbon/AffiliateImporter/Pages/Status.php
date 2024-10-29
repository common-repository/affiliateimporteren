<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;

class Status extends PageAbstract
{
    public function render()
    {
        $activePage = 'status';
        Toolbar::parseToolbar($activePage, $this->getType());

        $path = AffiliateImporter::getInstance()->getImporter($this->getType())->getMainFilePath();

        include $path . '/layout/status.php';
    }

    public function checkMemoryLimit()
    {
        $memory = $this->let_to_num(WP_MEMORY_LIMIT);
        $html = array();
        if ($memory < 127108864) {
            $html[] = '<div class="wwcAliAff-message wwcAliAff-error">' . sprintf('%s - We recommend setting memory to at least 128MB. See: <a href="%s">Increassing memory allocated to PHP</a>', size_format($memory), 'http://codex.wordpress.org/Editing_wp-config.php#Increassing_memory_allocated_to_PHP') . '</div>';
        } else {
            $html[] = '<div class="wwcAliAff-message wwcAliAff-success">' . size_format($memory) . '</div>';
        }

        return implode("\n", $html);
    }

    private function let_to_num($size)
    {
        $l = substr($size, -1);
        $ret = substr($size, 0, -1);
        switch (strtoupper($l)) {
            case 'P':
                $ret *= 1024;
            case 'T':
                $ret *= 1024;
            case 'G':
                $ret *= 1024;
            case 'M':
                $ret *= 1024;
            case 'K':
                $ret *= 1024;
                break;
        }
        return $ret;
    }

    public function checkRemoteGet()
    {
        // WP Remote Get Check
        $params = array(
            'sslverify' => false,
            'timeout' => 20,
            'body' => isset($request) ? $request : array()
        );

        $response = wp_remote_get('http://gw.api.alibaba.com/', $params);

        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300) {
            $msg = 'wp_remote_get() was successful - Webservices Alibaba is working.';
            $status = true;
        } elseif (is_wp_error($response)) {
            $msg = 'wp_remote_get() failed. Webservices Alibaba won\'t work with your server. Contact your hosting provider. Error:' . ' ' . $response->get_error_message();
            $status = false;
        } else {
            $msg = 'wp_remote_get() failed. Webservices Alibaba may not work with your server.';
            $status = false;
        }

        return ($status === true ? '<div class="wwcAliAff-message wwcAliAff-success">' : '<div class="wwcAliAff-message wwcAliAff-error">') . $msg . '</div>';
    }

    public function checkSoap()
    {
        $status = false;
        $msg = '';

        if (extension_loaded('soap') /*class_exists( 'SoapClient' )*/) {
            $msg = 'Your server has the SOAP Client class enabled.';
            $status = true;
        } else {
            $msg = sprintf('Your server does not have the <a href="%s">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected.', 'http://php.net/manual/en/class.soapclient.php');
            $status = false;
        }

        return ($status == true ? '<div class="wwcAliAff-message wwcAliAff-success">' : '<div class="wwcAliAff-message wwcAliAff-error">') . $msg . '</div>';
    }

    public function checkSimpleXml()
    {
        $status = false;
        $msg = '';

        if (function_exists('simplexml_load_string')) {
            $msg = 'Your server has the SimpleXML library enabled.';
            $status = true;
        } else {
            $msg = sprintf('Your server does not have the <a href="%s">SimpleXML</a> library enabled - some gateway plugins which use SimpleXML library may not work as expected.', 'http://php.net/manual/en/book.simplexml.php');
            $status = false;
        }

        return ($status == true ? '<div class="wwcAliAff-message wwcAliAff-success">' : '<div class="wwcAliAff-message wwcAliAff-error">') . $msg . '</div>';
    }

    public function getActivePlugins()
    {
        $active_plugins = (array)get_option('active_plugins', array());

        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }

        $wc_plugins = array();

        foreach ($active_plugins as $plugin) {

            $plugin_data = @get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $dirname = dirname($plugin);
            $version_string = '';

            if (!empty($plugin_data['Name'])) {


                if (false === ($version_data = get_transient($plugin . '_version_data'))) {
                    $changelog = wp_remote_get('http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $dirname . '/changelog.txt');
                    $cl_lines = explode("\n", wp_remote_retrieve_body($changelog));
                    if (!empty($cl_lines)) {
                        foreach ($cl_lines as $line_num => $cl_line) {
                            if (preg_match('/^[0-9]/', $cl_line)) {

                                $date = str_replace('.', '-', trim(substr($cl_line, 0, strpos($cl_line, '-'))));
                                $version = preg_replace('~[^0-9,.]~', '', stristr($cl_line, "version"));
                                $update = trim(str_replace("*", "", $cl_lines[$line_num + 1]));
                                $version_data = array('date' => $date, 'version' => $version, 'update' => $update, 'changelog' => $changelog);
                                set_transient($plugin . '_version_data', $version_data, 60 * 60 * 12);
                                break;
                            }
                        }
                    }
                }

                if (!empty($version_data['version']) && version_compare($version_data['version'], $plugin_data['Version'], '!=')) {
                    $version_string = ' &ndash; <strong style="color:red;">' . $version_data['version'] . ' ' . 'is available' . '</strong>';
                }


                $wc_plugins[] = $plugin_data['Name'] . ' ' . 'by' . ' ' . $plugin_data['Author'] . ' ' . 'version' . ' ' . $plugin_data['Version'] . $version_string;

            }
        }

        return implode(', <br/>', $wc_plugins);
    }

}
