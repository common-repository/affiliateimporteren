<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\AffiliateImporter\Prices\PriceFormula;
use Dnolbon\AffiliateImporter\Prices\PriceFormulas;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class PriceFormulaAdd extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_price_formula_add';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $result = ["state" => "ok"];

        /**
         * @var AffiliateImporterAbstract $mainClass
         */
        $mainClass = $this->getMainClass();

        $formulas = PriceFormulas::getList($mainClass->getAffiliateName());

        $formula = new PriceFormula();
        $formula->setType($mainClass->getAffiliateName());
        $formula->setPos(count($formulas) + 1);

        if (isset($_POST['type_name'])) {
            $formula->setTypeName(sanitize_text_field($_POST['type_name']));
        }
        if (isset($_POST['category'])) {
            $formula->setCategory((int)$_POST['category']);
        }
        if (isset($_POST['category_name'])) {
            $formula->setCategoryName(sanitize_text_field($_POST['category_name']));
        }
        if (isset($_POST['min_price'])) {
            $formula->setMinPrice((float)$_POST['min_price']);
        }
        if (isset($_POST['max_price'])) {
            $formula->setMaxPrice((float)$_POST['max_price']);
        }
        if (isset($_POST['sign'])) {
            $formula->setSign(sanitize_text_field($_POST['sign']));
        }
        if (isset($_POST['value'])) {
            $formula->setValue((int)$_POST['value']);
        }
        if (isset($_POST['discount1'])) {
            $formula->setDiscount1(sanitize_text_field($_POST['discount1']));
        }
        if (isset($_POST['discount2'])) {
            $formula->setDiscount2(sanitize_text_field($_POST['discount2']));
        }
        $formula->save();

        $result['formula'] = $formula->toArray();
        echo json_encode($result);
        wp_die();
    }
}
