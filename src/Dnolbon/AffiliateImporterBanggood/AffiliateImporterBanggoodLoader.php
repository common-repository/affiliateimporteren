<?php
namespace Dnolbon\AffiliateImporterBanggood;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Loader\LoaderAbstract;
use Dnolbon\AffiliateImporter\Products\Product;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Utils\Curl;
use Dnolbon\AffiliateImporter\Utils\Utils;
use Dnolbon\Wordpress\Db\Db;

class AffiliateImporterBanggoodLoader extends LoaderAbstract
{
    /**
     * @return mixed
     */
    protected function loadDetailRemote()
    {
        return [
            "state" => "error",
            'message' => '',
            "goods" => $this->getProduct()
        ];
    }

    /**
     * @param $filter
     * @param int $page
     * @return mixed
     */
    protected function loadListRemote($filter, $page = 1)
    {
        return ['items' => [], 'total' => 0, 'per_page' => 20];
    }
}
