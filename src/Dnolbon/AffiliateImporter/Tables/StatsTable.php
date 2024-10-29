<?php
namespace Dnolbon\AffiliateImporter\Tables;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;
use Dnolbon\Wordpress\Table\Table;

class StatsTable extends Table
{
    private $type;

    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @since 3.1.0
     * @access public
     *
     * @return array
     */
    public function getColumns()
    {
        $columns = [
            'image' => 'Thumb',
            'external_id' => 'SKU',
            'title' => 'Title',
            'hits' => 'Hits',
            'orders' => 'Redirected',
            'post_date' => 'Date added'
        ];
        return $columns;
    }

    /**
     * Prepares the list of items for displaying.
     * @uses WP_List_Table::set_pagination_args()
     *
     * @since 3.1.0
     * @access public
     */
    public function prepareItems()
    {
        $current_page = $this->getPagenum();

        $db = Db::getInstance()->getDb();

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $statsTable = $importer->getTableName('stats');
        $goodsArchiveTable = $importer->getTableName('goods_archive');

        $sql = 'SELECT count(*) FROM ' . $goodsArchiveTable . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("' . $this->getType() . '#", ' . $goodsArchiveTable . '.external_id) 
                    where ' . $db->postmeta . '.meta_id is not null ';
        $total = $db->get_var($sql);

        $sql = 'SELECT 
                    ' . $goodsArchiveTable . '.*, 
                    (select count(*) from ' . $statsTable . '
                    where ' . $db->posts . '.ID = ' . $statsTable . '.product_id
                    and quantity = 0) as hits,
                    ifnull((select sum(quantity) from ' . $statsTable . '
                    where ' . $db->posts . '.ID = ' . $statsTable . '.product_id), 0) as orders,
                    ' . $db->posts . '.post_date
                FROM ' . $goodsArchiveTable . ' 
                    left join ' . $db->postmeta . ' on ' . $db->postmeta . '.meta_key = "external_id" 
                    and ' . $db->postmeta . '.meta_value = concat("' . $this->getType() . '#", ' . $goodsArchiveTable . '.external_id)
                    
                    left join ' . $db->posts . ' on ' . $db->posts . '.ID = ' . $db->postmeta . '.post_id
                     
                where ' . $db->postmeta . '.meta_id is not null
                
                order by %s
                    
                limit ' . (($current_page - 1) * 20) . ',20';


        $preparedSql = $db->prepare(
            $sql,
            (isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) . ' ' . sanitize_text_field($_GET['order']) : 'title desc')
        );

        $this->items = $db->get_results($preparedSql);

        $this->setPagination(['total_items' => $total, 'per_page' => 20]);

        $this->initTable();
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getId($item)
    {
        return $this->getType() . '#' . $item->external_id;
    }

    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     *
     * @since 3.1.0
     * @access protected
     *
     * @return array
     */
    protected function getSortableColumns()
    {
        return [
            'external_id' => ['external_id', false],
            'title' => ['title', false],
            'hits' => ['hits', false],
            'orders' => ['orders', false],
            'post_date' => ['post_date', false]
        ];
    }

    protected function columnImage($item)
    {
        return '<img src="' . $item->image . '">';
    }
}
