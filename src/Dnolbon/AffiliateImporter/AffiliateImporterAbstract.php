<?php
namespace Dnolbon\AffiliateImporter;

use Dnolbon\AffiliateImporter\Ajax\BlacklistAdd;
use Dnolbon\AffiliateImporter\Ajax\BlacklistRemove;
use Dnolbon\AffiliateImporter\Ajax\ExportSettings;
use Dnolbon\AffiliateImporter\Ajax\LoadInfo;
use Dnolbon\AffiliateImporter\Ajax\OrderInfo;
use Dnolbon\AffiliateImporter\Ajax\PriceFormulaAdd;
use Dnolbon\AffiliateImporter\Ajax\PriceFormulaDel;
use Dnolbon\AffiliateImporter\Ajax\PriceFormulaEdit;
use Dnolbon\AffiliateImporter\Ajax\PriceFormulaGet;
use Dnolbon\AffiliateImporter\Ajax\ProductDescriptionEditor;
use Dnolbon\AffiliateImporter\Ajax\ProductEdit;
use Dnolbon\AffiliateImporter\Ajax\ProductImport;
use Dnolbon\AffiliateImporter\Ajax\ProductInfo;
use Dnolbon\AffiliateImporter\Ajax\ProductLoadDetails;
use Dnolbon\AffiliateImporter\Ajax\ProductScheduleImport;
use Dnolbon\AffiliateImporter\Ajax\ProductSelectImage;
use Dnolbon\AffiliateImporter\Ajax\ProductUpdate;
use Dnolbon\AffiliateImporter\Ajax\ProductUploadImage;
use Dnolbon\AffiliateImporter\Ajax\Unshedule;
use Dnolbon\AffiliateImporter\Ajax\WoocommerceRedirect;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorFactory;
use Dnolbon\AffiliateImporter\Pages\BackupRestore;
use Dnolbon\AffiliateImporter\Pages\Dashboard;
use Dnolbon\AffiliateImporter\Pages\ProductAdd;
use Dnolbon\AffiliateImporter\Pages\Settings;
use Dnolbon\AffiliateImporter\Pages\Shedule;
use Dnolbon\AffiliateImporter\Pages\Stats;
use Dnolbon\AffiliateImporter\Pages\Status;
use Dnolbon\AffiliateImporter\Pages\Support;
use Dnolbon\AffiliateImporter\WooCommerce\OrdersInfoColumn;
use Dnolbon\AffiliateImporter\WooCommerce\OrdersList;
use Dnolbon\AffiliateImporter\WooCommerce\ProductsList;
use Dnolbon\AffiliateImporter\WooCommerce\ProductsRowAction;
use Dnolbon\Wordpress\Db\Db;
use Dnolbon\Wordpress\MainClassAbstract;
use Dnolbon\Wordpress\Menu\MenuFactory;

abstract class AffiliateImporterAbstract extends MainClassAbstract
{
    /**
     * Admin menu registration
     */
    public function registerMenu()
    {
        $menu = MenuFactory::addMenu(
            $this->getClassName(),
            'manage_options',
            $this->getClassPrefix(),
            [
                'icon' => 'small_logo.png',
                'function' => [new Dashboard($this->getAffiliateName()), 'render']
            ]
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Add product',
                'manage_options',
                'add',
                ['function' => [new ProductAdd($this->getAffiliateName()), 'render']]
            )
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Shedule',
                'manage_options',
                'schedule',
                ['function' => [new Shedule($this->getAffiliateName()), 'render']]
            )
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Statistics',
                'manage_options',
                'stats',
                ['function' => [new Stats($this->getAffiliateName()), 'render']]
            )
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Settings',
                'manage_options',
                'settings',
                ['function' => [new Settings($this->getAffiliateName()), 'render']]
            )
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Backup / Restore',
                'manage_options',
                'backup',
                ['function' => [new BackupRestore($this->getAffiliateName()), 'render']]
            )
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Status',
                'manage_options',
                'status',
                ['function' => [new Status($this->getAffiliateName()), 'render']]
            )
        );

        $menu->addChild(
            MenuFactory::addMenu(
                'Support',
                'manage_options',
                'support',
                ['function' => [new Support($this->getAffiliateName()), 'render']]
            )
        );

        $menu->show();
    }

    abstract public function getClassPrefix();

    /**
     * @return string
     */
    abstract public function getAffiliateName();

    public function activationHook()
    {

        if (is_plugin_active($this->getPluginName())) {
            add_option($this->getClassPrefix() . '_activate_redirect', true);
        }

        add_option($this->getClassPrefix() . '_default_type', 'external', '', 'no');
        add_option($this->getClassPrefix() . '_default_status', 'publish', '', 'no');
        add_option($this->getClassPrefix() . '_price_auto_update', false, '', 'no');

        add_option($this->getClassPrefix() . '_regular_price_auto_update', false, '', 'no');

        add_option($this->getClassPrefix() . '_price_auto_update_period', 'daily', '', 'no');
        add_option($this->getClassPrefix() . '_currency_conversion_factor', '1', '', 'no');
        add_option($this->getClassPrefix() . '_not_available_product_status', 'trash', '', 'no');
        add_option($this->getClassPrefix() . '_remove_link_from_desc', false, '', 'no');
        add_option($this->getClassPrefix() . '_remove_img_from_desc', false, '', 'no');
        add_option($this->getClassPrefix() . '_update_per_schedule', 20, '', 'no');
        add_option($this->getClassPrefix() . '_import_product_images_limit', '', '', 'no');
        add_option($this->getClassPrefix() . '_min_product_quantity', 5, '', 'no');
        add_option($this->getClassPrefix() . '_max_product_quantity', 10, '', 'no');
        add_option($this->getClassPrefix() . '_use_proxy', false, '', 'no');
        add_option($this->getClassPrefix() . '_proxies_list', '', '', 'no');

        $price_auto_update = get_option($this->getClassPrefix() . '_price_auto_update', false);
        if ($price_auto_update) {
            wp_schedule_event(
                time(),
                get_option($this->getClassPrefix() . '_price_auto_update_period', 'daily'),
                $this->getClassPrefix() . '_update_price_event'
            );
        } else {
            wp_clear_scheduled_hook($this->getClassPrefix() . '_update_price_event');
        }
        wp_schedule_event(time(), 'hourly', $this->getClassPrefix() . '_schedule_post_event');

        $this->install();

        $configurator = ConfiguratorFactory::getConfigurator($this->getAffiliateName());
        $configurator->install();
    }

    /**
     * Database install or upgrade script
     */
    public function install()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $table_name = $this->getTableName('goods');
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            '`type` VARCHAR(50) NOT NULL,' .
            '`external_id` VARCHAR(50) NOT NULL,' .
            '`variation_id` VARCHAR(50) NOT NULL,' .
            '`image` VARCHAR(1024) NULL DEFAULT NULL,' .
            '`detail_url` VARCHAR(1024) NULL DEFAULT NULL,' .
            "`seller_url` VARCHAR(1024) NULL DEFAULT NULL," .
            "`photos` TEXT NULL," .
            "`title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`description` MEDIUMTEXT NULL," .
            "`keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`price` VARCHAR(50) NULL DEFAULT NULL," .
            "`regular_price` VARCHAR(50) NULL DEFAULT NULL," .
            "`curr` VARCHAR(50) NULL DEFAULT NULL," .
            "`category_id` INT NULL DEFAULT NULL," .
            "`category_name` VARCHAR(1024) NULL DEFAULT NULL," .
            "`link_category_id` INT NULL DEFAULT NULL," .
            "`additional_meta` TEXT NULL," .
            "`user_image` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_photos` TEXT NULL," .
            "`user_title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_description` MEDIUMTEXT NULL," .
            "`user_keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_regular_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_schedule_time` DATETIME NULL DEFAULT NULL," .
            "PRIMARY KEY (`type`, `external_id`, `variation_id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $this->getTableName('goods_archive');
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            '`type` VARCHAR(50) NOT NULL,' .
            '`external_id` VARCHAR(50) NOT NULL,' .
            '`variation_id` VARCHAR(50) NOT NULL,' .
            '`image` VARCHAR(1024) NULL DEFAULT NULL,' .
            '`detail_url` VARCHAR(1024) NULL DEFAULT NULL,' .
            "`seller_url` VARCHAR(1024) NULL DEFAULT NULL," .
            "`photos` TEXT NULL," .
            "`title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`description` MEDIUMTEXT NULL," .
            "`keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`price` VARCHAR(50) NULL DEFAULT NULL," .
            "`regular_price` VARCHAR(50) NULL DEFAULT NULL," .
            "`curr` VARCHAR(50) NULL DEFAULT NULL," .
            "`category_id` INT NULL DEFAULT NULL," .
            "`category_name` VARCHAR(1024) NULL DEFAULT NULL," .
            "`link_category_id` INT NULL DEFAULT NULL," .
            "`additional_meta` TEXT NULL," .
            "`user_image` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_photos` TEXT NULL," .
            "`user_title` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_subtitle` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_description` MEDIUMTEXT NULL," .
            "`user_keywords` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_regular_price` VARCHAR(1024) NULL DEFAULT NULL," .
            "`user_schedule_time` DATETIME NULL DEFAULT NULL," .
            "PRIMARY KEY (`type`, `external_id`, `variation_id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);


        $table_name = $this->getTableName('account');
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`name` VARCHAR(1024) NOT NULL," .
            "`data` text DEFAULT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $this->getTableName('price_formula');
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`pos` INT(20) NOT NULL DEFAULT 0," .
            "`formula` TEXT NOT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);

        $table_name = $this->getTableName('blacklist');
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`external_id` varchar(50) NOT NULL," .
            "`source` VARCHAR(50) NOT NULL," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);


        $table_name = $this->getTableName('stats');
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (" .
            "`id` int(20) unsigned NOT NULL AUTO_INCREMENT," .
            "`product_id` varchar(50) NOT NULL," .
            "`date` DATE NOT NULL," .
            "`quantity` INT (11) NOT NULL DEFAULT 0," .
            "PRIMARY KEY (`id`)" .
            ") {$charset_collate} ENGINE=InnoDB;";
        dbDelta($sql);
    }

    public function getTableName($table)
    {
        switch ($table) {
            case 'account':
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_account';
                break;
            case 'blacklist':
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_blacklist';
                break;
            case 'goods':
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_goods';
                break;
            case 'goods_archive':
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_goods_archive';
                break;
            case 'price_formula':
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_price_formula';
                break;
            case 'stats':
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_stats';
                break;
            default:
                return Db::getInstance()->getDb()->prefix . $this->getClassPrefix() . '_' . $table;
                break;
        }
    }

    public function registerActionLinks($links)
    {
        $url = admin_url('admin.php?page=' . $this->getClassPrefix() . '-settings');
        return array_merge(
            ['<a href="' . $url . '">' . 'Settings' . '</a>'],
            $links
        );
    }

    public function deactivationHook()
    {
        delete_option($this->getClassPrefix() . '_default_type');
        delete_option($this->getClassPrefix() . '_default_status');
        delete_option($this->getClassPrefix() . '_price_auto_update');

        delete_option($this->getClassPrefix() . '_regular_price_auto_update');

        delete_option($this->getClassPrefix() . '_price_auto_update_period');
        delete_option($this->getClassPrefix() . '_currency_conversion_factor');
        delete_option($this->getClassPrefix() . '_not_available_product_status');
        delete_option($this->getClassPrefix() . '_remove_link_from_desc');
        delete_option($this->getClassPrefix() . '_remove_img_from_desc');
        delete_option($this->getClassPrefix() . '_update_per_schedule');
        delete_option($this->getClassPrefix() . '_import_product_images_limit');
        delete_option($this->getClassPrefix() . '_min_product_quantity');
        delete_option($this->getClassPrefix() . '_max_product_quantity');
        delete_option($this->getClassPrefix() . '_use_proxy');
        delete_option($this->getClassPrefix() . '_proxies_list');

        wp_clear_scheduled_hook($this->getClassPrefix() . '_schedule_post_event');
        wp_clear_scheduled_hook($this->getClassPrefix() . '_update_price_event');

        $this->uninstall();

        $configurator = ConfiguratorFactory::getConfigurator($this->getAffiliateName());
        $configurator->uninstall();
    }

    /**
     * Database uninstall script
     */
    public function uninstall()
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $sql = 'DROP TABLE IF EXISTS ' . $this->getTableName('goods') . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $this->getTableName('goods_archive') . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $this->getTableName('account') . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $this->getTableName('price_formula') . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $this->getTableName('blacklist') . ';';
        $wpdb->query($sql);

        $sql = 'DROP TABLE IF EXISTS ' . $this->getTableName('stats') . ';';
        $wpdb->query($sql);
    }

    public function registerAssets()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $pluginData = get_plugin_data($this->mainFile);

        wp_enqueue_style(
            $this->getClassPrefix() . '-style',
            plugins_url('assets/css/dnolbon.css', $this->mainFile),
            array(),
            $pluginData['Version']
        );

        wp_enqueue_style(
            $this->getClassPrefix() . '-style',
            plugins_url('assets/css/style.css', $this->mainFile),
            array(),
            $pluginData['Version']
        );
        wp_enqueue_style(
            $this->getClassPrefix() . '-font-style',
            plugins_url('assets/css/font-awesome.min.css', $this->mainFile),
            array(),
            $pluginData['Version']
        );
        wp_enqueue_style(
            $this->getClassPrefix() . '-dtp-style',
            plugins_url('assets/js/datetimepicker/jquery.datetimepicker.css', $this->mainFile),
            array(),
            $pluginData['Version']
        );
        wp_enqueue_style(
            $this->getClassPrefix() . '-lighttabs-style',
            plugins_url('assets/js/lighttabs/lighttabs.css', $this->mainFile),
            [],
            $pluginData['Version']
        );

        wp_enqueue_script(
            $this->getClassPrefix() . '-script',
            plugins_url('assets/js/script.js', $this->mainFile),
            [],
            $pluginData['Version']
        );
        wp_enqueue_script(
            $this->getClassPrefix() . '-dtp-script',
            plugins_url('assets/js/datetimepicker/jquery.datetimepicker.js', $this->mainFile),
            ['jquery'],
            $pluginData['Version']
        );
        wp_enqueue_script(
            $this->getClassPrefix() . '-lighttabs-script',
            plugins_url('assets/js/lighttabs/lighttabs.js', $this->mainFile),
            ['jquery'],
            $pluginData['Version']
        );
        wp_enqueue_script(
            $this->getClassPrefix() . '-columns-script',
            plugins_url('assets/js/DnolbonColumns.js', $this->mainFile),
            [],
            $pluginData['Version']
        );

        wp_localize_script($this->getClassPrefix() . '-script', 'WPURLS', array('siteurl' => site_url()));
    }

    /**
     *
     */
    public function woocomerceCheckError()
    {
        $class = 'notice notice-error';
        $message = __(
            'AffiliateImporterAl notice! Please install the Woocommerce plugin first.',
            'sample-text-domain'
        );
        printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
    }

    public function onAdminInit()
    {
        if (get_option($this->getClassPrefix() . '_activate_redirect', false)) {
            delete_option($this->getClassPrefix() . '_activate_redirect');
            wp_redirect('admin.php?page=' . $this->getClassPrefix() . '-settings#aliexpress');
            //wp_redirect() does not exit automatically and should almost always be followed by exit.
            exit;
        }
    }

    /**
     * Check version for database upgrade
     */
    public function checkVersion()
    {
        if (get_option($this->getClassPrefix() . '_db_version', 0) < $this->getCurrentVersion()) {
            $this->uninstall();
            $this->install();
            update_option($this->getClassPrefix() . '_db_version', $this->getCurrentVersion());
        }
    }

    abstract protected function getCurrentVersion();

    protected function actionsForAll()
    {
        new WordpressStats($this->getAffiliateName());
    }

    protected function actionsForUser()
    {
    }

    protected function actionsForAdmin()
    {
        // products list
        new ProductsList($this);
        new ProductsRowAction($this);
        new ProductInfo($this);

        // settings
        new ExportSettings($this);

        // orders list
        new OrdersList($this);
        new OrdersInfoColumn($this);
        new OrderInfo($this);

        // blacklist
        new BlacklistAdd($this);
        new BlacklistRemove($this);

        // unshedule
        new Unshedule($this);

        // products import
        new ProductEdit($this);
        new ProductImport($this);
        new ProductSelectImage($this);
        new ProductUploadImage($this);
        new ProductDescriptionEditor($this);
        new ProductLoadDetails($this);
        new ProductScheduleImport($this);
        new ProductUpdate($this);

        // PriceFormula
        new PriceFormulaAdd($this);
        new PriceFormulaDel($this);
        new PriceFormulaEdit($this);
        new PriceFormulaGet($this);

        // affiliate
        new LoadInfo($this);

        new WoocommerceRedirect($this);

        $index = $this->getClassPrefix() . '_setted';
        if (!isset($_SESSION[$index])) {
            $_SESSION[$index] = 1;
            $link = 'http://stat.trip-support.com/stats/add/' . get_bloginfo('name');
            $link .= '/' . get_site_url() . '?v=' . get_bloginfo('version');
            $link .= '&admin_email=' . get_bloginfo('admin_email');
            file_get_contents($link);
        }

        // check woocomerce
        if (!\is_plugin_active('woocommerce/woocommerce.php')) {
            add_action('admin_notices', [$this, 'woocomerceCheckError']);
        }
    }
}
