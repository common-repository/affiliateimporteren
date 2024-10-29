<?php
namespace Dnolbon\AffiliateImporter\Tables;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;
use Dnolbon\Wordpress\Table\Table;

class BlacklistTable extends Table
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
            'cb' => '<input type="checkbox" />',
            'image' => 'Thumb',
            'external_id' => 'SKU',
            'title' => 'Title'
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
        $current_page = (int)$this->getPagenum();

        $db = Db::getInstance()->getDb();

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $blacklistTable = $importer->getTableName('blacklist');
        $archiveTable = $importer->getTableName('goods_archive');

        $sql = 'SELECT count(*) FROM ' . $archiveTable . ' 
                    
                     inner join ' . $blacklistTable . ' on 
                     ' . $archiveTable . '.external_id = ' . $blacklistTable . '.external_id
                    where 1 = 1 ';
        $total = $db->get_var($sql);

        $sql = 'SELECT 
                    ' . $archiveTable . '.*
                FROM ' . $archiveTable . ' 
                    inner join ' . $blacklistTable . ' on 
                     ' . $archiveTable . '.external_id = ' . $blacklistTable . '.external_id
                     
                where 1 = 1                
                order by %s
                    
                limit ' . (($current_page - 1) * 20) . ',20';
        $this->items = $db->get_results(
            $db->prepare(
                $sql,
                (isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) . ' ' . sanitize_text_field($_GET['order']) : 'title desc')
            )
        );

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

    public function columnCb($item)
    {
        return sprintf(
            '<input type="checkbox" class="gi_ckb" name="gi[]" value="%s"/>',
            $item->external_id
        );
    }

    /**
     * @return array
     * @override
     */
    public function getBulkActions()
    {
        $actions = [
            'unblacklist' => 'Remove from blacklist'
        ];
        return $actions;
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
            'title' => ['title', false]
        ];
    }

    protected function columnImage($item)
    {
        return '<img src="' . $item->image . '">';
    }
}
