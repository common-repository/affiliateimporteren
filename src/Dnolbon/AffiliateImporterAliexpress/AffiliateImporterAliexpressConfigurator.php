<?php
namespace Dnolbon\AffiliateImporterAliexpress;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorAbstract;
use Dnolbon\AffiliateImporter\Products\Product;

class AffiliateImporterAliexpressConfigurator extends ConfiguratorAbstract
{
    public function install()
    {
        add_option('aeidn_ali_per_page', 20, '', 'no');
        add_option('aeidn_ali_links_to_affiliate', true, '', 'no');
        add_option('aeidn_ali_local_currency', '', '', 'no');
    }

    public function uninstall()
    {
        delete_option('aeidn_ali_per_page');
        delete_option('aeidn_ali_links_to_affiliate');
        delete_option('aeidn_ali_local_currency');
    }

    public function setFilters()
    {
        $this->addFilter(
            'category_id',
            'category_id',
            21,
            [
                'type' => 'select',
                'label' => 'Category',
                'class' => 'category_list',
                'style' => 'width:25em;',
                'data_source' => [$this, 'getCategories']
            ]
        );
        $this->addFilter(
            'volume',
            ['volume_from', 'volume_to'],
            32,
            [
                'type' => 'edit',
                'label' => 'Тotal orders (last 30 days)',
                'description' => 'from 1 to 100',
                'volume_from' => ['label' => 'from'],
                'volume_to' => ['label' => ' to']
            ]
        );

        $this->addFilter(
            'feedback_score',
            ['min_feedback', 'max_feedback'],
            33,
            [
                'type' => 'edit',
                'label' => 'Feedback score',
                'min_feedback' => ['label' => 'min', 'default' => '0'],
                'max_feedback' => ['label' => ' max', 'default' => '0']
            ]
        );

        $this->addFilter(
            'high_quality_items',
            'high_quality_items',
            34,
            [
                'type' => 'checkbox',
                'label' => 'High Quality items',
                'default' => 'yes'
            ]
        );
    }

    public function getColumns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'image' => '',
            'info' => 'Information',
            'price' => 'Source Price',
            'userPrice' => 'Posted Price',
            'commission' => 'Commission (8%)',
            'curr' => 'Currency',
            'volume' => 'Тotal orders (last 30 days)',
            'rating' => 'Rating',
            'validTime' => 'Valid time'
        ];
        return $columns;
    }

    /**
     * @param Product $item
     */
    public function columnCommission($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['commission'];
    }

    /**
     * @param Product $item
     */
    public function columnVolume($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['volume'];
    }

    /**
     * @param Product $item
     */
    public function columnRating($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['rating'];
    }

    /**
     * @param Product $item
     */
    public function columnValidTime($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['validTime'];
    }

    protected function getCategories()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $result = json_decode(
            file_get_contents($importer->getMainFilePath() . '/data/aliexpress_categories.json'),
            true
        );
        $result = $result['categories'];
        array_unshift($result, ['id' => '', 'name' => ' - ', 'level' => 1]);
        return $result;
    }

    public function getSettings()
    {
        $settingsPrefix = 'aeidn';

        $localCurrency = get_option($settingsPrefix . '_ali_local_currency', 'usd');
        $convertLinksToAffiliate = get_option($settingsPrefix . '_ali_links_to_affiliate', false);
        $showPerPage = get_option($settingsPrefix . '_ali_per_page', 20);

        return [
            'localCurrency' => $localCurrency,
            'convertLinksToAffiliate' => $convertLinksToAffiliate,
            'showPerPage' => $showPerPage
        ];
    }
}
