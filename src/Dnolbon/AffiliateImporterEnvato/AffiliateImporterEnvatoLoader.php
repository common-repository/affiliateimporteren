<?php
namespace Dnolbon\AffiliateImporterEnvato;

use Dnolbon\AffiliateImporter\Account\AccountFactory;
use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Loader\LoaderAbstract;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Utils\Utils;

class AffiliateImporterEnvatoLoader extends LoaderAbstract
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
        $result = ['items' => [], 'total' => 0, 'per_page' => 20];

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        $noImageUrl = plugins_url('assets/img/iconPlaceholder_96x96.gif', $importer->getMainFile());

        $account = AccountFactory::getAccount($this->getType());

        if (isset($filter['endn_query']) &&
            isset($filter['category_id']) &&
            (isset($filter['link_category_id']) && (int)$filter['link_category_id'] !== 0)
        ) {
            list($site, $category) = explode(':', $filter['category_id']);

            $url = 'https://api.envato.com/v1/discovery/search/search/item?';
            $url .= 'term=' . $filter['endn_query'] . '&site=' . $site;
            $url .= '&category=' . $category;
            $url .= '&page=' . $page . '&page_size=20';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $headers = [
                'Authorization: Bearer ' . $account->getAccountDataKeyValue('secretKey')
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $serverOutput = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $linkCategoryId = 0;
            if (isset($filter['link_category_id']) && (int)$filter['link_category_id']) {
                $linkCategoryId = (int)$filter['link_category_id'];
            }

            $currencyConversionFactor = (float)str_replace(
                ",",
                ".",
                (string)get_option('envato_currency_conversion_factor', 1)
            );
            $result['total'] = $serverOutput['total_hits'];

            foreach ($serverOutput['matches'] as $item) {
                $additional = [];
                $additional['site'] = $item['site'];
                $additional['classification'] = $item['classification'];
                $additional['sales'] = $item['number_of_sales'];
                $additional['author_username'] = $item['author_username'];
                $additional['rating'] = $item['rating']['rating'];

                $product = ProductFactory::getWithId("envato#" . $item['id']);
                $product->setDetailUrl($item['url'] . '?ref=' . $account->getAccountDataKeyValue('userName'));
                $product->setLinkCategoryId($linkCategoryId);
                $product->setAdditionalMeta($additional);
                $product->setUserPrice('');

                if (isset($item['previews'])) {
                    $previews = $item['previews'];
                    $product->setImage(current(current($previews)));
                    $photos = [];
                    foreach ($previews as $preview) {
                        foreach ($preview as $previewUrl) {
                            $photos[] = $previewUrl;
                        }
                    }
                    $product->setPhotos(implode(',', $photos));
                } else {
                    $product->setImage($noImageUrl);
                }
                $product->setTitle($item['name']);
                $product->setSubtitle('#notuse#');
                $product->setCategoryId($item['site']);
                $product->setCategoryName($item['classification']);
                $product->setDescription($item['description_html']);

                if (trim($product->getKeywords()) === '') {
                    $product->setKeywords("#needload#");
                }
                $product->setSellerUrl($item['author_url']);

                $price = round(Utils::fixPrice($item['price_cents'] / 100), 2);
                $product->setPrice($price);
                $product->save();

                $product->loadUserPrice($currencyConversionFactor);
                $product->loadUserImage();

                $result['items'][] = $product;
            }
        }

        return $result;
    }
}
