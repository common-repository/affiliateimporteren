<?php
/**
 * @var Status $this
 */
use Dnolbon\AffiliateImporter\Pages\Status;

?>
<div class="affiliate-reports">
    <div class="wrap light-tabs" default-rel="backup_settings">
    </div>
    <div class="tab_content" rel="backup_settings">
        <div class="separator"></div>
        <div class="text_content">
            <h3>Environment</h3>
            <table class="settings_table">
                <tr>
                    <td><label>Home URL</label></td>
                    <td><?php echo home_url(); ?></td>
                </tr>
                <tr>
                    <td><label>Version</label></td>
                    <td>1.0</td>
                </tr>
                <tr>
                    <td><label>WP Version</label></td>
                    <td><?php
                        if (is_multisite()) {
                            echo 'WPMU';
                        } else {
                            echo 'WP';
                        }
                        ?><?php bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><label>Web Server Info</label></td>
                    <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE']); ?></td>
                </tr>
                <tr>
                    <td><label>PHP Version</label></td>
                    <td><?php if (function_exists('phpversion')) {
                            echo esc_html(phpversion());
                        } ?></td>
                </tr>
                <tr>
                    <td><label>WP Memory Limit</label></td>
                    <td><?= $this->checkMemoryLimit() ?></td>
                </tr>
                <tr>
                    <td><label>WP Debug Mode</label></td>
                    <td><?php if (defined('WP_DEBUG') && WP_DEBUG) echo 'YES'; else 'No'; ?></td>
                </tr>
                <tr>
                    <td><label>WP Max Upload Size</label></td>
                    <td><?php echo size_format(wp_max_upload_size()); ?></td>
                </tr>
                <tr>
                    <td><label>PHP Post Max Size</label></td>
                    <td><?php if (function_exists('ini_get')) echo size_format(woocommerce_let_to_num(ini_get('post_max_size'))); ?></td>
                </tr>
                <tr>
                    <td><label>PHP Time Limit</label></td>
                    <td><?php if (function_exists('ini_get')) echo ini_get('max_execution_time'); ?></td>
                </tr>
                <tr>
                    <td><label>WP Remote GET</label></td>
                    <td><?= $this->checkRemoteGet() ?></td>
                </tr>
                <tr>
                    <td><label>SOAP Client</label></td>
                    <td><?= $this->checkSoap() ?></td>
                </tr>
                <tr>
                    <td><label>SimpleXML library</label></td>
                    <td><?= $this->checkSimpleXml() ?></td>
                </tr>
            </table>
        </div>
        <div class="separator"></div>
        <div class="text_content">
            <h3>Plugins</h3>
            <table class="settings_table">
                <tr>
                    <td><label>Installed Plugins</label></td>
                    <td><?= $this->getActivePlugins() ?></td>
                </tr>
            </table>
        </div>
        <div class="separator"></div>
        <div class="text_content">
            <h3>Settings</h3>
            <table class="settings_table">
                <tr>
                    <td><label>Force SSL</label></td>
                    <td><?php echo get_option('woocommerce_force_ssl_checkout') === 'yes' ? 'Yes' : 'No'; ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>