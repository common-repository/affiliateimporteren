<?php
namespace Dnolbon\AffiliateImporterEbay;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorAbstract;
use wpdb;

class AffiliateImporterEbayConfigurator extends ConfiguratorAbstract
{
    public function install()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $charsetСollate = '';
        if (!empty($wpdb->charset)) {
            $charsetСollate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }
        if (!empty($wpdb->collate)) {
            $charsetСollate .= " COLLATE {$wpdb->collate}";
        }

        add_option('ebdn_ebay_custom_id', '', '', 'no');
        add_option('ebdn_ebay_geo_targeting', false, '', 'no');
        add_option('ebdn_ebay_network_id', '9', '', 'no');
        add_option('ebdn_ebay_tracking_id', '', '', 'no');
        add_option('ebdn_ebay_per_page', 20, '', 'no');

        $tableName = AffiliateImporter::getInstance()->getImporter('ebay')->getTableName('sites');

        $sql = "CREATE TABLE $tableName (" .
            "`id` INT(20) UNSIGNED NOT NULL AUTO_INCREMENT," .
            "`language` VARCHAR(255) NULL DEFAULT ''," .
            "`country` VARCHAR(255) NULL DEFAULT ''," .
            "`siteid` VARCHAR(255) NULL DEFAULT ''," .
            "`sitecode` VARCHAR(255) NULL DEFAULT ''," .
            "`sitename` VARCHAR(255) NULL DEFAULT ''," .
            "PRIMARY KEY (`id`) )" .
            " {$charsetСollate} ENGINE=InnoDB;";
        dbDelta($sql);

        $sql = "INSERT INTO `$tableName` (`language`, `country`, `siteid`, `sitecode`, `sitename`) 
                VALUES
                ('en-US', 'US', '0', 'EBAY-US', 'eBay United States'),
                ('de-AT', 'AT', '16', 'EBAY-AT', 'eBay Austria'),
                ('en-AU', 'AU', '15', 'EBAY-AU', 'eBay Australia'),
                ('de-CH', 'CH', '193', 'EBAY-CH', 'eBay Switzerland'),
                ('en-DE', 'DE', '77', 'EBAY-DE', 'eBay Germany'),
                ('en-CA', 'CA', '2', 'EBAY-ENCA', 'eBay Canada (English)'),
                ('en-ES', 'ES', '186', 'EBAY-ES', 'eBay Spain'),
                ('fr-FR', 'FR', '71', 'EBAY-FR', 'eBay France'),
                ('fr-BE', 'BE', '23', 'EBAY-FRBE', 'eBay Belgium(French)'),
                ('fr-CA', 'CA', '210', 'EBAY-FRCA', 'eBay Canada (French)'),
                ('en-GB', 'GB', '3', 'EBAY-GB', 'eBay UK'),
                ('zh-Hant', 'HK', '201', 'EBAY-HK', 'eBay Hong Kong'),
                ('en-IE', 'IE', '205', 'EBAY-IE', 'eBay Ireland'),
                ('en-IN', 'IN', '203', 'EBAY-IN', 'eBay India'),
                ('it-IT', 'IT', '101', 'EBAY-IT', 'eBay Italy'),
                ('en-US', 'US', '100', 'EBAY-MOTOR', 'eBay Motors'),
                ('en-MY', 'MY', '207', 'EBAY-MY', 'eBay Malaysia'),
                ('nl-NL', 'NL', '146', 'EBAY-NL', 'eBay Netherlands'),
                ('nl-BE', 'BE', '123', 'EBAY-NLBE', 'eBay Belgium(Dutch)'),
                ('en-PH', 'PH', '211', 'EBAY-PH', 'eBay Philippines'),
                ('pl-PL', 'PH', '212', 'EBAY-PL', 'eBay Poland'),
                ('en-SG', 'SG', '216', 'EBAY-SG', 'eBay Singapore');";
        dbDelta($sql);
    }

    public function uninstall()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        delete_option('ebdn_ebay_custom_id');
        delete_option('ebdn_ebay_geo_targeting');
        delete_option('ebdn_ebay_network_id');
        delete_option('ebdn_ebay_tracking_id');
        delete_option('ebdn_ebay_per_page');

        $tableName = AffiliateImporter::getInstance()->getImporter('ebay')->getTableName('sites');

        $sql = 'DROP TABLE IF EXISTS ' . $tableName . ';';
        $wpdb->query($sql);
    }

    public function setFilters()
    {
        $this->addFilter(
            'store',
            'store',
            11,
            [
                'type' => 'edit',
                'label' => 'Store name',
                "placeholder" => "Please enter your store name"
            ]
        );

        $this->addFilter("category_id", "category_id", 21, array("type" => "select",
            "label" => "Category",
            "class" => "category_list",
            "data_source" => array($this, 'getCategories')));

        $this->addFilter("free_shipping_only", "free_shipping_only", 31, array("type" => "checkbox",
            "label" => "Free Shipping Only",
            "default" => "yes"));

        $this->addFilter("feedback_score", array("min_feedback", "max_feedback"), 32, array("type" => "edit",
            "label" => "Feedback score",
            "min_feedback" => array("label" => "min", "default" => "0"),
            "max_feedback" => array("label" => " max", "default" => "0")));

        $this->addFilter("available_to", "available_to", 33, array("type" => "select",
            "label" => "Shipment Options",
            "class" => "countries_list",
            "data_source" => array($this, 'getCountries')));

        $this->addFilter("condition", "condition", 34, array("type" => "select",
            "label" => "Condition",
            "class" => "sitecode_list",
            "data_source" => array($this, 'getConditionList')));

        $this->addFilter("sitecode", "sitecode", 35, array("type" => "select",
            "label" => "Site",
            "class" => "sitecode_list",
            "data_source" => array($this, 'getSites')));

        $this->addFilter("listing_type", "listing_type", 36, array("type" => "select",
            "label" => "Listing Type",
            "class" => "sitecode_list",
            "multiple" => true,
            "data_source" => array($this, 'getListingType')));
    }

    public function getCuntries()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $result = array();
        $result[] = array('id' => '', 'name' => ' - ');
        $handle = @fopen($importer->getMainFilePath() . '/data/countries.csv', 'r');
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $cntr = explode(',', $buffer);
                $result[] = array('id' => $cntr[1], 'name' => $cntr[0]);
            }
            if (!feof($handle)) {
                echo "Error: unexpected fgets() fail<br/>";
            }
            fclose($handle);
        }
        return $result;
    }

    public function getConditionList()
    {
        return [
            ["id" => "", "name" => ""],
            ["id" => 1000, "name" => "New"],
            ["id" => 1500, "name" => "New other (see details)"],
            ["id" => 1750, "name" => "New with defects"],
            ["id" => 2000, "name" => "Manufacturer refurbished"],
            ["id" => 2500, "name" => "Seller refurbished"],
            ["id" => 3000, "name" => "Used"],
            ["id" => 4000, "name" => "Very Good"],
            ["id" => 5000, "name" => "Good"],
            ["id" => 6000, "name" => "Acceptable"],
            ["id" => 7000, "name" => "For parts or not working"]
        ];
    }

    public function getSites()
    {
        global $wpdb;
        $result = array();

        $tableName = AffiliateImporter::getInstance()->getImporter('ebay')->getTableName('sites');

        $dbRes = $wpdb->get_results("SELECT * FROM " . $tableName);
        if ($dbRes) {
            foreach ($dbRes as $row) {
                $result[] = AffiliateImporterEbaySiteFactory::getByObject($row);
            }
        }

        return $result;
    }

    public function getListingType()
    {
        return [
            ['id' => 'All', 'name' => 'All'],
            ['id' => 'Auction', 'name' => 'Auction'],
            ['id' => 'AuctionWithBIN', 'name' => 'Auction With Buy It Now'],
            ['id' => 'FixedPrice', 'name' => 'Fixed Price'],
            ['id' => 'Classified', 'name' => 'Classified']
        ];
    }

    protected function getCategories()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $result = [];
        $result[] = ['id' => '', 'name' => ' - '];
        $xml = simplexml_load_file($importer->getMainFilePath() . '/data/ebay_categories.xml');
        foreach ($xml->CategoryArray->Category as $c) {

            if (((string)$c->CategoryLevel === '1') ||
                (get_option('ebdn_ebay_extends_cats', false) && (string)$c->CategoryLevel === '1')
            ) {
                $result[] = [
                    'id' => (string)$c->CategoryID,
                    'name' => (string)$c->CategoryName,
                    'level' => (string)$c->CategoryLevel
                ];
            }
        }

        return $result;
    }

    public function getSettings()
    {
        return [
            'perPage' => get_option('ebdn_ebay_per_page', 20),
            'extendedCats' => get_option('ebdn_ebay_extends_cats', false),
            'customId' => get_option('ebdn_ebay_custom_id'),
            'geoTargeting' => get_option('ebdn_ebay_geo_targeting', false),
            'networdId' => get_option('ebdn_ebay_network_id', '9'),
            'trackingId' => get_option('ebdn_ebay_tracking_id')
        ];
    }
}
