<?php
namespace Dnolbon\AffiliateImporter\Configurator;

use Dnolbon\AffiliateImporter\AffiliateImporter;

abstract class ConfiguratorAbstract
{
    private $filterConfig = array();

    private $type;

    /**
     * ConfiguratorAbstract constructor.
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->initFilters();
    }

    final private function initFilters()
    {
        $importer = $this->getImporter();

        $this->addFilter(
            $importer->getClassPrefix() . '_productId',
            $importer->getClassPrefix() . '_productId',
            10,
            [
                'type' => 'edit',
                'label' => 'ProductId',
                'dop_row' => 'OR configure search filter',
                'placeholder' => 'Please enter your productId'
            ]
        );
        $this->addFilter(
            $importer->getClassPrefix() . '_query',
            $importer->getClassPrefix() . '_query',
            20,
            [
                'type' => 'edit',
                'label' => 'Keywords',
                'placeholder' => 'Please enter your Keywords'
            ]
        );
        $this->addFilter(
            'price',
            [$importer->getClassPrefix() . '_min_price', $importer->getClassPrefix() . '_max_price'],
            30,
            [
                'type' => 'edit',
                'label' => 'Price',
                $importer->getClassPrefix() . '_min_price' => ['label' => "from $", 'default' => '0.00'],
                $importer->getClassPrefix() . '_max_price' => ['label' => " to $", 'default' => '0.00']
            ]
        );
    }

    protected function getImporter()
    {
        return AffiliateImporter::getInstance()->getImporter($this->getType());
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    final public function addFilter($id, $name, $order = 1000, $config = [])
    {
        $this->filterConfig[$id] = [
            'id' => $id,
            'name' => $name,
            'config' => $config,
            'order' => $order
        ];
    }

    public function getColumns() {
        return [];
    }

    abstract public function install();

    abstract public function uninstall();

    abstract public function setFilters();

    final public function getFilters()
    {
        $result = [];
        foreach ($this->filterConfig as $id => $filter) {
            $result[$id] = $filter;
            if (isset($filter['config']['data_source']) && $filter['config']['data_source']) {
                if (is_array($filter['config']['data_source'])) {
                    $objectName = $filter['config']['data_source'][0];
                    $objectFunc = $filter['config']['data_source'][1];

                    $result[$id]['config']['data_source'] = $objectName->$objectFunc();
                } else {
                    $result[$id]['config']['data_source'] = ${$filter['config']['data_source']}();
                }
            }
        }

        uasort($result, function ($a, $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        return $result;
    }

    abstract public function getSettings();
}
