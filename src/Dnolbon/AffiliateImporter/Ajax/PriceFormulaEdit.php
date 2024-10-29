<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\AffiliateImporter\Prices\PriceFormulaFactory;
use Dnolbon\AffiliateImporter\Prices\PriceFormulas;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class PriceFormulaEdit extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_price_formula_edit';
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

        $result = ["state" => "ok"];

        if (!isset($_POST['id'])) {
            echo json_encode(array("state" => "error", "message" => "Uncknown price id"));
            wp_die();
        }

        $formula = PriceFormulaFactory::getById(sanitize_text_field($_POST['id']), $mainClass->getAffiliateName());

        if (!$formula) {
            echo json_encode(
                [
                    "state" => "error",
                    "message" => "Price formula(" . sanitize_text_field($_POST['id']) . ") not found"
                ]
            );
            wp_die();
        }

        if (isset($_POST['pos'])) {
            $formula->setPos((int)$_POST['pos']);
        }
        if (isset($_POST['type'])) {
            $formula->setType(sanitize_text_field($_POST['type']));
        }
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

        $formulas = PriceFormulas::getList($mainClass->getAffiliateName());
        foreach ($formulas as $otherFormula) {
            if ((int)$formula->getId() !== (int)$otherFormula->getId() &&
                (int)$otherFormula->getPos() >= (int)$formula->getPos()
            ) {
                $newPos = $otherFormula->getPos() + 1;
                $otherFormula->setPos($newPos);
                $otherFormula->save();
            }
        }

        PriceFormulas::recalcPos($mainClass->getAffiliateName());

        $result['formula'] = $formula->toArray();
        echo json_encode($result);
        wp_die();
    }
}

