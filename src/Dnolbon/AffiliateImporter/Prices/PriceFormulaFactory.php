<?php
namespace Dnolbon\AffiliateImporter\Prices;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;

class PriceFormulaFactory
{
    public static function getById($id, $type)
    {
        $importer = AffiliateImporter::getInstance()->getImporter($type);

        $db = Db::getInstance()->getDb();

        $priceFormula = null;

        $result = $db->get_row($db->prepare(
            'SELECT * FROM ' . $importer->getTableName('price_formula') . ' WHERE id=%s',
            $id
        ));

        if ($result) {
            $priceFormula = new PriceFormula();
            $priceFormula->setId($result->id);
            $priceFormula->setPos($result->pos);

            $fData = unserialize($result->formula);

            $priceFormula->setType($fData['type']);
            $priceFormula->setCategory($fData['category']);
            $priceFormula->setCategoryName($fData['category_name']);
            $priceFormula->setMinPrice($fData['min_price']);
            $priceFormula->setMaxPrice($fData['max_price']);
            $priceFormula->setSign($fData['sign']);
            $priceFormula->setValue($fData['value']);
            $priceFormula->setDiscount1($fData['discount1']);
            $priceFormula->setDiscount2($fData['discount2']);
            $priceFormula->setTypeName($fData['type_name']);
        }
        return $priceFormula;
    }
}
