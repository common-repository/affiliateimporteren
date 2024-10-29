<?php
namespace Dnolbon\AffiliateImporter\Tables;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\Wordpress\Db\Db;
use Dnolbon\Wordpress\Table\Table;

class SheduleTable extends Table
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
            'title' => 'Title',
            'external_id' => 'SKU',
            'user_schedule_time' => 'Shedule time'
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

        $sql = 'SELECT count(*) FROM ' . $importer->getTableName('goods_archive') . ' 
                    where user_schedule_time is not null and user_schedule_time <> "0000-00-00 00:00:00" ';
        $total = $db->get_var($sql);

        $sql = 'SELECT 
                    ' . $importer->getTableName('goods_archive') . '.*
                FROM ' . $importer->getTableName('goods_archive') . ' 
                     
                where user_schedule_time is not null and user_schedule_time <> "0000-00-00 00:00:00"
                
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
            'unshedule' => 'Remove from shedule'
        ];
        return $actions;
    }

    public function needLoadMoreDetail($item)
    {
        foreach (get_object_vars($item) as $f => $val) {
            if (!is_array($val) && (string)$val === '#needload#') {
                return true;
            }
        }
        return false;
    }

    public function getId($item)
    {
        return $this->getType() . '#' . $item->external_id;
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
            'user_schedule_time' => ['user_schedule_time', false]
        ];
    }

    protected function columnImage($item)
    {
        return '<img src="' . $item->image . '">';
    }

    protected function columnTitle($item)
    {
        $actions = [];

        $product = ProductFactory::getWithId($this->getType() . '#' . $item->external_id);

        $actions['id'] = '<a href="' . $item->detail_url . '" target="_blank" class="link_to_source product_url">Product page</a>' . "<span class='seller_url_block' " . ($item->seller_url ? "" : "style='display:none'") . "> | <a href='" . $item->seller_url . "' target='_blank' class='seller_url'>Seller page</a></span>";
//        $actions['import'] = $goods->post_id ? '<i>Posted</i>' : '<a href="#import_" class="post_import">Post to Woocommerce</a>';
        $actions['load_more_detail'] = $product->isNeedLoad() ? '<a href="#moredetails" class="moredetails">Load more details</a>' : '<i>Details loaded</i>';
        $actions['schedule_import'] = '<input type="text" class="schedule_post_date" style="visibility:hidden;width:0px;padding:0;margin:0;"/><a href="#scheduleimport" onclick="return affiliateShowDatePicker(this)" class="schedule_post_import">Schedule Post</a>';

//        $cat_name = "";
//        foreach ($this->link_categories as $c) {
//            if ($c['term_id'] === $item->link_category_id) {
//                $cat_name = $c['name'];
//                break;
//            }
//        }

        $html = ProductAddTable::putField($product, "title", true, "edit", "Title", "") .
            ProductAddTable::putField($product, 'subtitle', true, "edit", "Subtitle", "subtitle-block") .
            ProductAddTable::putField($product, 'keywords', true, "edit", "Keywords", "subtitle-block") .
            ProductAddTable::putDescriptionEdit($product);

//        $html .= $item->title . '';
        $html .= $this->rowActions($actions);
        return $html;
    }
}
