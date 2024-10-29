<?php
namespace Dnolbon\AffiliateImporter\Products;

class ProductFactory
{
    public static function getWithId($id)
    {
        list($type, $externalId, $variationId) = explode('#', $id . '#-');

        $product = new Product($externalId, $type, $variationId);
        $product->loadLocalData();
        return $product;
    }
}
