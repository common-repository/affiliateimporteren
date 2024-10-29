<?php
namespace Dnolbon\AffiliateImporter\Prices;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;

class PriceFormulas
{
    /**
     * @param $type
     * @return PriceFormula[]
     */
    public static function getList($type)
    {
        $db = Db::getInstance()->getDb();

        $formulas = [];

        $importer = AffiliateImporter::getInstance()->getImporter($type);
        $results = $db->get_results("SELECT * FROM " . $importer->getTableName('price_formula') . " ORDER BY pos");

        if ($results) {
            foreach ($results as $row) {
                $formulas[] = PriceFormulaFactory::getById($row->id, $type);
            }
        }
        return $formulas;
    }

    /**
     * @param $type
     */
    public static function recalcPos($type)
    {
        $importer = AffiliateImporter::getInstance()->getImporter($type);

        $db = Db::getInstance()->getDb();

        $sql = 'UPDATE ' . $importer->getTableName('price_formula') . ' dest,';
        $sql .= ' (SELECT @r:=@r+1 as new_pos, z.id from( ';
        $sql .= ' select id from ' . $importer->getTableName('price_formula') . ' order by pos) z,';
        $sql .= ' (select @r:=0)y) src SET dest.pos = src.new_pos where dest.id=src.id;';

        $db->query($sql);
    }
}
