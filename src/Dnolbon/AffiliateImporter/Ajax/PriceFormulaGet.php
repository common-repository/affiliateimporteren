<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\AffiliateImporter\Prices\PriceFormulaFactory;
use Dnolbon\WooCommerce\Categories\Categories;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class PriceFormulaGet extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_price_formula_get';
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

        if (!isset($_POST['id'])) {
            echo json_encode(array("state" => "error", "message" => "Uncknown price id"));
            wp_die();
        }

        $formula = PriceFormulaFactory::getById(sanitize_text_field($_POST['id']), $mainClass->getAffiliateName());

        if (!$formula) {
            echo json_encode(
                [
                    'state' => "error",
                    'message' => "Price formula(" . sanitize_text_field($_POST['id']) . ") not found"
                ]
            );
            wp_die();
        }
        $categoriesTreeArr = [];
        $categories_tree = Categories::getCategoriesTree();

        foreach ($categories_tree as $c) {
            $categoriesTreeArr[] = ["id" => $c['term_id'], "name" => $c['name'], "level" => $c['level']];
        }

        $signListArr = [
            ["id" => "=", "name" => " = "],
            ["id" => "+", "name" => " + "],
            ["id" => "*", "name" => " * "]
        ];

        $discountListArr = [
            ["id" => "", "name" => "source %"],
            ["id" => "0", "name" => "0%"],
            ["id" => "5", "name" => "5%"],
            ["id" => "10", "name" => "10%"],
            ["id" => "15", "name" => "15%"],
            ["id" => "20", "name" => "20%"],
            ["id" => "25", "name" => "25%"],
            ["id" => "30", "name" => "30%"],
            ["id" => "35", "name" => "35%"],
            ["id" => "40", "name" => "40%"],
            ["id" => "45", "name" => "45%"],
            ["id" => "50", "name" => "50%"],
            ["id" => "55", "name" => "55%"],
            ["id" => "60", "name" => "60%"],
            ["id" => "65", "name" => "65%"],
            ["id" => "70", "name" => "70%"],
            ["id" => "75", "name" => "75%"],
            ["id" => "80", "name" => "80%"],
            ["id" => "85", "name" => "85%"],
            ["id" => "90", "name" => "90%"],
            ["id" => "95", "name" => "95%"]
        ];

        echo json_encode(
            [
                "state" => "ok",
                "formula" => $formula->toArray(),
                "categories_tree" => $categoriesTreeArr,
                "sign_list" => $signListArr,
                "discount_list" => $discountListArr
            ]
        );

        wp_die();
    }
}
