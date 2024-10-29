<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorFactory;
use Dnolbon\AffiliateImporter\Products\Products;
use Dnolbon\AffiliateImporter\Tables\BlacklistTable;
use Dnolbon\AffiliateImporter\Tables\ProductAddTable;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;
use Dnolbon\Twig\Twig;
use Dnolbon\WooCommerce\Categories\Categories;

class ProductAdd extends PageAbstract
{
    private $blackListTable;

    private $productAddTable;

    public function render()
    {
        $configurator = ConfiguratorFactory::getConfigurator($this->getType());
        $configurator->setFilters();

        $activePage = 'add';
        Toolbar::parseToolbar($activePage, $this->getType());

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $errors = array_merge(
            get_settings_errors($importer->getClassPrefix() . '_dashboard_error'),
            get_settings_errors($importer->getClassPrefix() . '_goods_list')
        );
        settings_errors($importer->getClassPrefix() . '_dashboard_error');
        settings_errors($importer->getClassPrefix() . '_goods_list');

        if ((int)filter_input(INPUT_GET, 'is_results') === 1 && count($errors) === 0) {
            $defaultPage = 'results';
        } else {
            $defaultPage = 'filter_settings';
        }

        $resultFilter = filter_input_array(INPUT_GET);

        add_thickbox();
        $template = Twig::getInstance()->getTwig()->load('product_add.html');
        echo $template->render([
            'defaultPage' => $defaultPage,
            'type' => $this->getType(),
            'blacklist' => $this->getBlackListTable(),
            'result' => $this->getProductAddTable(),
            'filters' => $configurator->getFilters(),
            'page' => filter_input(INPUT_GET, 'page'),
            'categories' => Categories::getCategoriesTree(),
            'resultFilter' => $resultFilter,
            'nonce' => wp_create_nonce('upload_thumb'),
            'prefix' => $importer->getClassPrefix()
        ]);

        add_screen_option('layout_columns', ['default' => 2]);
    }

    /**
     * @return mixed
     */
    public function getBlackListTable()
    {
        if ($this->blackListTable === null) {
            $this->blackListTable = new BlacklistTable();
            $this->blackListTable->setType($this->getType());
            $this->blackListTable->prepareItems();
        }
        return $this->blackListTable;
    }

    /**
     * @return mixed
     */
    public function getProductAddTable()
    {
        if ($this->productAddTable === null) {
            $this->productAddTable = new ProductAddTable();
            $this->productAddTable->setType($this->getType());
            $this->productAddTable->prepareItems();
        }
        return $this->productAddTable;
    }

    protected function processActions()
    {
        if (filter_input(INPUT_GET, 'reset')) {
            Products::clearList($this->getType());
        }
    }
}
