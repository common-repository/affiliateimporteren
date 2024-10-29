<?php
namespace Dnolbon\AffiliateImporterAmazon;

use Dnolbon\AffiliateImporter\Configurator\ConfiguratorAbstract;

class AffiliateImporterAmazonConfigurator extends ConfiguratorAbstract
{
    public function install()
    {
        add_option('aidn_amazon_default_site', 'com', '', 'no');
        add_option('aidn_amazon_per_page', 10, '', 'no');
    }

    public function uninstall()
    {
        delete_option('aidn_amazon_default_site');
        delete_option('aidn_amazon_per_page');
    }

    public function setFilters()
    {
        $this->addFilter("category_id", "category_id", 21, array("type" => "select",
            "label" => "Category",
            "class" => "category_list",
            "data_source" => array($this, 'getCategories')));

        $this->addFilter("condition", "condition", 34, array("type" => "select",
            "label" => "Condition",
            "class" => "sitecode_list",
            "data_source" => array($this, 'getConditionList')));

        $this->addFilter("sitecode", "sitecode", 35, array("type" => "select",
                "label" => "Site",
                "class" => "sitecode_list",
                "data_source" => array($this, 'getSites'))
        );
    }

    protected function getCategories()
    {
        return array(
            array("id" => "", "name" => " - "),
            array("id" => "All", "name" => "All"),
            array("id" => "Appliances", "name" => "Appliances"),
            array("id" => "ArtsAndCrafts", "name" => "ArtsAndCrafts"),
            array("id" => "Automotive", "name" => "Automotive"),
            array("id" => "Baby", "name" => "Baby"),
            array("id" => "Beauty", "name" => "Beauty"),
            array("id" => "Blended", "name" => "Blended"),
            array("id" => "Books", "name" => "Books"),
            array("id" => "Collectibles", "name" => "Collectibles"),
            array("id" => "Electronics", "name" => "Electronics"),
            array("id" => "Fashion", "name" => "Fashion"),
            array("id" => "FashionBaby", "name" => "FashionBaby"),
            array("id" => "FashionBoys", "name" => "FashionBoys"),
            array("id" => "FashionGirls", "name" => "FashionGirls"),
            array("id" => "FashionMen", "name" => "FashionMen"),
            array("id" => "FashionWomen", "name" => "FashionWomen"),
            array("id" => "GiftCards", "name" => "GiftCards"),
            array("id" => "Grocery", "name" => "Grocery"),
            array("id" => "HealthPersonalCare", "name" => "HealthPersonalCare"),
            array("id" => "HomeGarden", "name" => "HomeGarden"),
            array("id" => "Industrial", "name" => "Industrial"),
            array("id" => "KindleStore", "name" => "KindleStore"),
            array("id" => "LawnAndGarden", "name" => "LawnAndGarden"),
            array("id" => "Luggage", "name" => "Luggage"),
            array("id" => "MP3Downloads", "name" => "MP3Downloads"),
            array("id" => "Magazines", "name" => "Magazines"),
            array("id" => "Merchants", "name" => "Merchants"),
            array("id" => "MobileApps", "name" => "MobileApps"),
            array("id" => "Movies", "name" => "Movies"),
            array("id" => "Music", "name" => "Music"),
            array("id" => "MusicalInstruments", "name" => "MusicalInstruments"),
            array("id" => "OfficeProducts", "name" => "OfficeProducts"),
            array("id" => "PCHardware", "name" => "PCHardware"),
            array("id" => "PetSupplies", "name" => "PetSupplies"),
            array("id" => "Software", "name" => "Software"),
            array("id" => "SportingGoods", "name" => "SportingGoods"),
            array("id" => "Tools", "name" => "Tools"),
            array("id" => "Toys", "name" => "Toys"),
            array("id" => "UnboxVideo", "name" => "UnboxVideo"),
            array("id" => "VideoGames", "name" => "VideoGames"),
            array("id" => "Wine", "name" => "Wine"),
            array("id" => "Wireless", "name" => "Wireless"),
        );
    }

    protected function getSites()
    {
        return array(
            array("id" => "com", "name" => "com"),
            array("id" => "de", "name" => "de"),
            array("id" => "co.uk", "name" => "co.uk"),
            array("id" => "ca", "name" => "ca"),
            array("id" => "fr", "name" => "fr"),
            array("id" => "co.jp", "name" => "co.jp"),
            array("id" => "it", "name" => "it"),
            array("id" => "cn", "name" => "cn"),
            array("id" => "es", "name" => "es"),
            array("id" => "in", "name" => "in")
        );
    }

    protected function getConditionList()
    {
        return array(
            array("id" => "", "name" => ""),
            array("id" => "New", "name" => "New"),
            array("id" => "Used", "name" => "Used"),
            array("id" => "Collectible", "name" => "Collectible"),
            array("id" => "Refurbished", "name" => "Refurbished"),
            array("id" => "All", "name" => "All"),
        );
    }

    public function getSettings()
    {
        return [
            'defaultSite' => get_option('aidn_amazon_default_site')
        ];
    }
}
