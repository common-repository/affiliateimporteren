<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;
use Dnolbon\Wordpress\Db\Db;

class Dashboard extends PageAbstract
{
    public function render()
    {
        $activePage = '';

        Toolbar::parseToolbar($activePage, $this->getType());

        $path = AffiliateImporter::getInstance()->getImporter($this->getType())->getMainFilePath();
        $type = $this->getType();
        include $path . '/layout/main.php';
    }

    public function getTotalNumberProducts()
    {
        $db = Db::getInstance()->getDb();

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $goodsArchiveTable = $importer->getTableName('goods_archive');

        $sql = 'SELECT count(*) FROM ' . $goodsArchiveTable . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = 
                        concat("' . $this->getType() . '#", ' . $goodsArchiveTable . '.external_id) 
                    where ' . $db->postmeta . '.meta_id is not null ';
        return $db->get_var($sql);
    }

    public function getTotals()
    {
        $db = Db::getInstance()->getDb();

        $stats = $this->getStats();

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        
        $statsTable = $importer->getTableName('stats');
        $goodsArchiveTable = $importer->getTableName('goods_archive');
        
        $sql = 'SELECT 
                     
                    sum((select count(*) from ' . $statsTable . '
                    where ' . $db->posts . '.ID = ' . $statsTable . '.product_id
                    and quantity = 0
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ') > date(now()))) as hits,
                    sum(ifnull((select sum(quantity) from ' . $statsTable . '
                    where ' . $db->posts . '.ID = ' . $statsTable . '.product_id
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ') > date(now())), 0)) as orders
                FROM ' . $goodsArchiveTable . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = 
                            concat("' . $this->getType() . '#", ' . $goodsArchiveTable . '.external_id)
                    
                    left join ' . $db->posts . ' on ' . $db->posts . '.ID = ' . $db->postmeta . '.post_id
                     
                where ' . $db->postmeta . '.meta_id is not null
                ';
        return $db->get_results($sql);
    }

    public function getStats()
    {
        return isset($_GET['stats']) ? sanitize_text_field($_GET['stats']) : '1 day';
    }

    public function getProductsTop()
    {
        $db = Db::getInstance()->getDb();

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        $statsTable = $importer->getTableName('stats');
        $goodsArchiveTable = $importer->getTableName('goods_archive');

        $limit = $this->getLimit();
        $stats = $this->getStats();
        $sql = 'SELECT 
                    ' . $goodsArchiveTable . '.*, 
                    (select count(*) from ' . $statsTable . '
                    where ' . $db->posts . '.ID = ' . $statsTable . '.product_id
                    and quantity = 0
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ')  > date(now())) as hits,
                    ifnull((select sum(quantity) from ' . $statsTable . '
                    where ' . $db->posts . '.ID = ' . $statsTable . '.product_id
                    and DATE_ADD(`date`, INTERVAL +' . $stats . ')  > date(now())), 0) as orders
                FROM ' . $goodsArchiveTable . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = 
                            concat("' . $this->getType() . '#", ' . $goodsArchiveTable . '.external_id)
                    
                    left join ' . $db->posts . ' on ' . $db->posts . '.ID = ' . $db->postmeta . '.post_id
                     
                where ' . $db->postmeta . '.meta_id is not null
                
                order by hits desc
                    
                limit 0,' . $limit . '';
        return $db->get_results($sql);
    }

    public function getLimit()
    {
        return (int)(isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
    }
}
