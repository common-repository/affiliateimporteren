<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\Account\AccountFactory;
use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorFactory;
use Dnolbon\AffiliateImporter\Prices\PriceFormulas;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;
use Dnolbon\Twig\Twig;
use Dnolbon\WooCommerce\Categories\Categories;

class Settings extends PageAbstract
{

    public function render()
    {
        $importer = $mainImporter = AffiliateImporter::getInstance()->getImporter($this->getType());
        $settingsPrefix = $mainImporter->getClassPrefix();

        $this->processActions();

        $activePage = 'schedule';
        Toolbar::parseToolbar($activePage, $this->getType());

        $type = $this->getType();

        $paramsSettings = [];

        if (isset($_REQUEST['module']) && $_REQUEST['module']) {
            $paramsSettings['currentModule'] = sanitize_text_field($_REQUEST['module']);
        } else {
            $paramsSettings['currentModule'] = 'common';
        }

        $account = AccountFactory::getAccount($type);

        $template = Twig::getInstance()->getTwig()->load('settings_account.html');
        $paramsSettings['settingsAffiliate'] = $template->render(
            [
                'form' => $account->getForm(),
                'data' => $account->getAccountData(),
                'prefix' => $importer->getClassPrefix()
            ]
        );

        $configurator = ConfiguratorFactory::getConfigurator($this->getType());
        $params = $configurator->getSettings();
        $params['prefix'] = $importer->getClassPrefix();

        $template = Twig::getInstance()->getTwig()->load('settings_' . $this->getType() . '.html');
        $paramsSettings['settingsAffiliate'] .= $template->render($params);

        $params = [];
        $params['conversionFactor'] = get_option($settingsPrefix . '_currency_conversion_factor', '1');
        $params['defaultType'] = get_option($settingsPrefix . '_default_type', 'simple');
        $params['defaultStatus'] = get_option($settingsPrefix . '_default_status', 'publish');
        $params['removeLink'] = get_option($settingsPrefix . '_remove_link_from_desc', false);
        $params['removeImage'] = get_option($settingsPrefix . '_remove_img_from_desc', false);
        $params['productImagesLimit'] = get_option($settingsPrefix . '_import_product_images_limit');
        $params['minProductQuantity'] = get_option($settingsPrefix . '_min_product_quantity', 5);
        $params['maxProductQuantity'] = get_option($settingsPrefix . '_max_product_quantity', 10);
        $params['prefix'] = $importer->getClassPrefix();

        $template = Twig::getInstance()->getTwig()->load('settings_common.html');
        $paramsSettings['settingsCommon'] = $template->render($params);


        $params = [];
        $params['priceAutoUpdate'] = get_option($settingsPrefix . '_price_auto_update', false);
        $params['regularPriceAutoUpdate'] = get_option($settingsPrefix . '_regular_price_auto_update', false);
        $params['notAvailableStatus'] = get_option($settingsPrefix . '_not_available_product_status', 'trash');
        $params['priceUpdatePeriod'] = get_option($settingsPrefix . '_price_auto_update_period', 'daily');
        $params['updatePerShedule'] = get_option($settingsPrefix . '_update_per_schedule', 20);
        $params['prefix'] = $importer->getClassPrefix();

        $template = Twig::getInstance()->getTwig()->load('settings_shedule.html');
        $paramsSettings['settingsShedule'] = $template->render($params);


        $params = [];
        $params['language'] = get_option($settingsPrefix . '_tr_' . $this->getType() . '_language', 'en');
        $params['prefix'] = $importer->getClassPrefix();

        $template = Twig::getInstance()->getTwig()->load('settings_language.html');
        $paramsSettings['settingsLang'] = $template->render($params);


        $params = [];
        $params['categories'] = Categories::getCategoriesTree();
        $params['formulas'] = PriceFormulas::getList($this->getType());
        $params['prefix'] = $importer->getClassPrefix();

        $template = Twig::getInstance()->getTwig()->load('settings_prices.html');
        $paramsSettings['settingsPrice'] = $template->render($params);
        $paramsSettings['prefix'] = $importer->getClassPrefix();

        $template = Twig::getInstance()->getTwig()->load('settings.html');
        echo $template->render($paramsSettings);
    }

    public function processActions()
    {
        $postData = filter_input_array(INPUT_POST);

        if (isset($postData['affiliate_settings'])) {
            if (isset($postData['options'])) {
                foreach ($postData['options'] as $key => $value) {
                    update_option($key, sanitize_text_field($value));
                }
            }

            if (isset($postData['account'])) {
                $account = AccountFactory::getAccount($this->getType());
                foreach ($postData['account'] as $key => $value) {
                    $account->setAccountDataKeyValue($key, $value);
                }
                $account->saveAccountData();
            }
        }
    }
}
