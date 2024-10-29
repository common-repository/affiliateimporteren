<?php
namespace Dnolbon\AffiliateImporter\Tables;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorFactory;
use Dnolbon\AffiliateImporter\Loader\LoaderFactory;
use Dnolbon\AffiliateImporter\Products\Product;
use Dnolbon\Wordpress\Db\Db;
use Dnolbon\Wordpress\Table\Table;

class ProductAddTable extends Table
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
            'image' => '',
            'info' => 'Information',
            'shipToLocations' => 'Ship to',
            'condition' => 'Condition',
            'price' => 'Source Price',
            'userPrice' => 'Posted Price',
            'ship' => 'Shipment Charges',
            'curr' => 'Currency'
        ];

        $configurator = ConfiguratorFactory::getConfigurator($this->getType());
        $configuratorColumns = $configurator->getColumns();
        if ($configuratorColumns) {
            $columns = $configuratorColumns;
        }
        return $columns;
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
     * @param Product $item
     * @return string
     */
    public function getId($item)
    {
        return $this->getType() . '#' . $item->getExternalId();
    }

    /**
     * Prepares the list of items for displaying.
     *
     * @since 3.1.0
     * @access public
     */
    public function prepareItems()
    {
        $_SERVER['REQUEST_URI'] = remove_query_arg(array('reset'), $_SERVER['REQUEST_URI']);

        $loader = LoaderFactory::getLoader($this->getType());
        $filter = filter_input_array(INPUT_GET);
        $current_page = $this->getPagenum();

        $data = $loader->loadList($filter, $current_page);

        $this->setPagination(array('total_items' => (int)$data['total'], 'per_page' => (int)$data['per_page']));
        $this->items = $data['items'];


        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        $db = Db::getInstance()->getDb();
        $db->query('delete from ' . $importer->getTableName('goods_archive') . ' where external_id in (
                    select external_id from ' . $importer->getTableName('goods') . '
                    )');
        $db->query('insert into ' . $importer->getTableName('goods_archive') . '
                    select * from ' . $importer->getTableName('goods') . '
                    ');

        $this->initTable();
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnCb($item)
    {
        return sprintf(
            '<input type="checkbox" class="gi_ckb" name="gi[]" ' . ($item->getPostId() ? ' disabled' : '') . ' value="%s"/>',
            $item->getExternalId()
        );
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnImage($item)
    {
        return self::putImageEdit($item);
    }

    /**
     * @param Product $product
     * @param bool $contentOnly
     * @return string
     */
    public static function putImageEdit($product, $contentOnly = false)
    {
        $out = '';
        if (!$contentOnly) {
            $out .= sprintf('<a href="#TB_inline?width=320&height=450&inlineId=select-image-dlg-%1$s" class="thickbox select_image"><img src="%2$s"/></a>', $product->getFullId('-'), $product->getImage());
            $out .= '<a href="#TB_inline?width=320&height=150&inlineId=upload_image_dlg" class="thickbox upload_image">[upload image]</a>';
            $out .= '<div id="select-image-dlg-' . $product->getFullId('-') . '" style="display:none;">';
        }
        if ($product->getPhotos() === "#needload#") {
            $out .= '<h3><font style="color:red;">Photos not load yet! Click "load more details"</font></h3>';
        }
        $out .= '<h3>Click on an image to select it</h3>';
        $out .= '<input type="hidden" class="item_id" value="' . $product->getFullId() . '"/>';
        $curImage = $product->getUserImage();

        $photos = $product->getAllPhotos();
        foreach ($photos as $photo) {
            $out .= sprintf(
                '<div class="select_image"><img class="' . ($curImage === $photo ? "sel" : "") . '" src="%1$s"/></div>',
                $photo
            );
        }

        if (!$contentOnly) {
            $out .= '</div>';
        }
        return $out;
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnInfo($item)
    {
        $actions = array();
        $actions['id'] = '<a href="' . $item->getDetailUrl() . '" target="_blank" class="link_to_source product_url">Product page</a>';
        $actions['id'] .= "<span class='seller_url_block' " . ($item->getSellerUrl() ? "" : "style='display:none'") . ">";
        $actions['id'] .= " | <a href='" . $item->getSellerUrl() . "' target='_blank' class='seller_url'>Seller page</a></span>";

        $actions['load_more_detail'] = $item->isNeedLoad() ? '<a href="#moredetails" onclick="return affiliateLoadMoreDetails(this)" class="moredetails">Load more details</a>' : '<i>Details loaded</i>';
        $actions['import'] = $item->getPostId() ? '<i>Posted</i>' : '<a href="#import_" onclick="return affiliatePostImport(this)" class="post_import">Post to Woocommerce</a>';
        if (!$item->getPostId()) {
            $actions['schedule_import'] = $item->getUserScheduleTime() ? ("<i>Will be post on " . date("m/d/Y H:i", strtotime($item->getUserScheduleTime()))) . "</i>" : '<input type="text" class="schedule_post_date" style="visibility:hidden;width:0px;padding:0;margin:0;"/><a href="#scheduleimport" onclick="return affiliateShowDatePicker(this)" class="schedule_post_import">Schedule Post</a>';
        }

        $catName = "";
//        foreach ($this->linkСategories as $c) {
//            if ($c['term_id'] === $item->link_category_id) {
//                $catName = $c['name'];
//                break;
//            }
//        }

        $resultData = self::putField($item, "title", true, "edit", "Title", "") .
            self::putField($item, 'subtitle', true, "edit", "Subtitle", "subtitle-block") .
            self::putField($item, 'keywords', true, "edit", "Keywords", "subtitle-block") .
            self::putDescriptionEdit($item) .
            ($catName ? "<div>Link to category: $catName</div>" : "") .
            $this->rowActions($actions);

        return $resultData;
    }

    /**
     * @param Product $product
     * @param $field
     * @param $edit
     * @param string $edit_text
     * @param string $lable_text
     * @param string $block_class
     * @return string
     */
    public static function putField($product, $field, $edit, $edit_text = "edit", $lable_text = "", $block_class = "")
    {
        $value = $product->getField($field);

        $loaded = $value !== "#needload#";

        $out = '';
        if ($value !== "#notuse#") {
            $out .= '<div class="block_field ' . $block_class . ($edit ? ' edit' : '') . '">';
            $out .= '<input type="hidden" class="field_code" value="' . $field . '"/>';
            if ($lable_text) {
                $out .= '<label class="field_label">' . $lable_text . ': </label>';
            }
            $out .= '<span class="field_text">' . ($loaded ? $value : '<font style="color:red;">Need to load more details</font>') . '</span>';
            if ($edit) {
                $out .= '<input type="text" class="field_edit" value="" style="width:100%;display:none"/>';
                $out .= '<input type="button" class="save_btn button" onclick="affiliateProductEdit(this)" value="Save" style="display:none"/> ';
                $out .= '<input type="button" class="cancel_btn button" onclick="affiliateProductCancel(this)" value="Cancel" style="display:none"/>';
                $out .= ' <a href="#edit" onclick="return affiliateEdit(this)" class="edit_btn" ' . ($loaded ? '' : 'style="display:none;"') . '>[' . $edit_text . ']</a>';
            }
            $out .= '</div>';
        }

        return $out;
    }

    public static function putDescriptionEdit($contentOnly = false)
    {
        $out = '';
        if (!$contentOnly) {
            $out .= 'Description: <a href="#TB_inline?width=800&height=600&inlineId=edit_desc_dlg" onclick="return affiliateDescriptionEditor(this)" class="thickbox edit_desc_action">[edit description]</a>';
        }

        return $out;
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnShipToLocations($item)
    {
        return $item->getCleanAditionalField('ship_to_locations');
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnCondition($item)
    {
        return $item->getCleanAditionalField('condition');
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnPrice($item)
    {
        return self::putField($item, 'price', false);
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnUserPrice($item)
    {
        return self::putField($item, 'user_price', false);
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnShip($item)
    {
        return $item->getCleanAditionalField('ship');
    }

    /**
     * @param Product $item
     * @return string
     */
    public function columnCurr($item)
    {
        return self::putField($item, 'curr', false);
    }

    /**
     * @return array
     * @override
     */
    public function getBulkActions()
    {
        $actions = array(
            'import' => 'Post to Woocommerce (publish)',
            'import_draft' => 'Post to Woocommerce (draft)',
            'blacklist' => 'Blacklist'
        );
        return $actions;
    }

    public function columnOther($item, $columnName)
    {
        $configurator = ConfiguratorFactory::getConfigurator($this->getType());
        if (method_exists($configurator, 'column' . ucfirst($columnName))) {
            return $configurator->{'column' . ucfirst($columnName)}($item);
        } else {
            return '';
        }
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
        $sortableColumns = [];
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        return apply_filters($importer->getClassPrefix() . '_get_dashboard_sortable_columns', $sortableColumns);
    }
}
