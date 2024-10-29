<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\AffiliateImporter\Prices\PriceFormulaFactory;
use Dnolbon\AffiliateImporter\Prices\PriceFormulas;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class PriceFormulaDel extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_price_formula_del';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        /**
         * @var AffiliateImporterAbstract $mainClass
         */
        $mainClass = $this->getMainClass();

        $result = ['state' => 'ok'];

        if (isset($_POST['id'])) {
            $formula = PriceFormulaFactory::getById(sanitize_text_field($_POST['id']), $mainClass->getAffiliateName());
            $formula->delete();

            PriceFormulas::recalcPos($mainClass->getAffiliateName());
        }

        echo json_encode($result);
        wp_die();
    }
}

