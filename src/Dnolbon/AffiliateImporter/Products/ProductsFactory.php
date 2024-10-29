<?php
namespace Dnolbon\AffiliateImporter\Products;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;

class ProductsFactory
{
    public static function getList($page, $perPage, $type, $filter = "")
    {
        $importer = AffiliateImporter::getInstance()->getImporter($type);

        $db = Db::getInstance()->getDb();

        $result = array();

        $where = ' WHERE 1 ';
        $where .= $filter;

        $perPage = (int)$perPage;

        $blacklistTable = $importer->getTableName('blacklist');
        $goodsTable = $importer->getTableName('goods');

        $dbRes = $db->get_results(
            '
            SELECT 
                ' . $goodsTable . '.* 
            FROM ' . $goodsTable . '
            left join ' . $blacklistTable . ' on 
                ' . $goodsTable . '.external_id =
                    ' . $blacklistTable . '.external_id ' .
            $where . ' 
            AND ' . $blacklistTable . '.id is null 
            LIMIT ' . (($page - 1) * $perPage) . ', ' . $perPage
        );
        if ($dbRes) {
            foreach ($dbRes as $row) {
                $result[] = ProductFactory::getWithId($row->external_id);
            }
        }
        return ['total' => count($result), 'page' => $page, 'per_page' => $perPage, 'items' => $result];
    }
}
