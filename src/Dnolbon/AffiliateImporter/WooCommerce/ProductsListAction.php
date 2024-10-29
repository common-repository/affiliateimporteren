<?php
namespace Dnolbon\AffiliateImporter\WooCommerce;

use Dnolbon\AffiliateImporter\AffiliateImporterAbstract;
use Dnolbon\AffiliateImporter\Products\Products;
use Dnolbon\WooCommerce\Products\ProductsListActionAbstract;

class ProductsListAction extends ProductsListActionAbstract
{
    public function getText()
    {
        $updatePrice = get_option($this->getMainClass()->getClassPrefix() . '_regular_price_auto_update', false);

        $text = 'Update stock';
        if ($updatePrice) {
            $text = "Update price & stock";
        }
        /**
         * @var AffiliateImporterAbstract $importer
         */
        $importer = $this->getMainClass()->getPluginName();
        $text .= '(' . $importer->getAffiliateName() . ')';

        return $text;
    }

    public function process()
    {
        $postIds = $this->getPostIds();

        $updated = 0;
        $skiped = 0;

        foreach ($postIds as $postId) {
            $result = $this->updatePrices($postId);
            if ($result === -1) {
                $skiped++;
            } elseif (!$result) {
                wp_die(__('Error updating product.'));
            } else {
                $updated++;
            }
        }

        return [
            $this->getMainClass()->getClassPrefix() . '_updated' => $updated,
            $this->getMainClass()->getClassPrefix() . '_skipped' => $skiped,
            'ids' => implode(',', $postIds)
        ];
    }

    private function updatePrices($postId)
    {
        $externalId = get_post_meta($postId, "external_id", true);

        if ($externalId) {
            list($source) = explode('#', $externalId);
            Products::updatePriceByPostId($source, $postId);
            return true;
        }
    }

    public function getAction()
    {
        return $this->getMainClass()->getClassPrefix() . '_updateprice';
    }

    public function showNotice()
    {
        $updatedKey = $this->getMainClass()->getClassPrefix() . '_updated';
        $skipedKey = $this->getMainClass()->getClassPrefix() . '_skipped';

        $updated = isset($_REQUEST[$updatedKey]) ? $_REQUEST[$updatedKey] : 0;
        $skipped = isset($_REQUEST[$skipedKey]) ? $_REQUEST[$skipedKey] : 0;

        if ($updated > 0 || $skipped > 0) {
            $message = sprintf(
                _n('Product updated.', '%s products updated.', $updated),
                number_format_i18n($updated)
            );

            if ($skipped) {
                $message .= ' And ' . sprintf(
                        _n('one product skiped.', '%s products skiped.', $skipped),
                        number_format_i18n($skipped)
                    );
            }

            echo "<div class=\"updated\"><p>{$message}</p></div>";
        }
    }
}
