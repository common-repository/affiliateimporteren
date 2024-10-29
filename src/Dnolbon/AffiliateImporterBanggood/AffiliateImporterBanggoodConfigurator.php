<?php
namespace Dnolbon\AffiliateImporterBanggood;

use Dnolbon\AffiliateImporter\Account\AccountFactory;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorAbstract;

class AffiliateImporterBanggoodConfigurator extends ConfiguratorAbstract
{
    public function install()
    {

    }

    public function uninstall()
    {

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
    }

    public function getSettings()
    {
        return [];
    }

    protected function getCategories()
    {
        $account = AccountFactory::getAccount($this->getType());
//        $result = ['id' => '', 'name' => ' - ', 'level' => 1];
        $banggoodAPI = new BanggoodAPI(
            $account->getAccountDataKeyValue('Appid'),
            $account->getAccountDataKeyValue('AppSecret')
        );
        $params = array('page' => 1);
        $banggoodAPI->setParams($params);
        $result = $banggoodAPI->getCategoryList();

        return $result;
    }
}
