<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\Wordpress\Ajax\AjaxAbstract;

class ProductDescriptionEditor extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix() . '_description_editor';
    }

    public function onlyForAdmin()
    {
        return true;
    }

    public function process()
    {
        $productId = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : "";

        $product = ProductFactory::getWithId($productId);

        if ($product->getPhotos() === '#needload#') {
            echo '<h3><font style="color:red;">Description not load yet! Click "load more details"</font></h3>';
        } else {
            wp_editor(
                $product->getDescription(),
                $product->getFullId('-'),
                ['media_buttons' => false]
            );
            echo '<input type="hidden" class="item_id" value="' . $product->getFullId() . '"/>';
            echo '<input type="hidden" class="editor_id" value="' . $product->getFullId('-') . '"/>';
            echo '<input type="button" class="save_description button" onclick="affiliateSaveDescription(this)" value="Save description"/>';

            _WP_Editors::enqueue_scripts();
            wp_enqueue_script('jquery-ui-dialog');
            print_footer_scripts();
            _WP_Editors::editor_js();
        }

        wp_die();
    }
}
