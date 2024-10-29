<?php
namespace Dnolbon\AffiliateImporterAliexpress;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Loader\LoaderAbstract;
use Dnolbon\AffiliateImporter\Products\Product;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Utils\Curl;
use Dnolbon\AffiliateImporter\Utils\Utils;
use Dnolbon\Wordpress\Db\Db;

class AffiliateImporterAliexpressLoader extends LoaderAbstract
{
    /**
     * @return mixed
     */
    protected function loadDetailRemote()
    {
        $additionalMeta = [];
        $product = $this->getProduct();

        $localCurrency = strtoupper(get_option('aeidn_ali_local_currency', ''));
        if ($localCurrency) {
            $currencyConversionFactor = 1;
        } else {
            $currencyConversionFactor = (float)get_option('aeidn_currency_conversion_factor', 1);
        }

        $appKey = $this->getAccount()->getAccountDataKeyValue('appKey');

        $requestUrl = 'http://gw.api.alibaba.com/openapi/param2/2/';
        $requestUrl .= 'portals.open/api.getPromotionProductDetail/' . $appKey;
        $requestUrl .= '?fields=productId,productTitle,productUrl,imageUrl,originalPrice,salePrice,';
        $requestUrl .= 'discount,evaluateScore,commission,commissionRate,30daysCommission,volume,packageType,';
        $requestUrl .= 'lotNum,validTime,storeName,storeUrl,localPrice';
        $requestUrl .= '&productId=' . $product->getExternalId();
        $requestUrl .= '&language=' . get_option('aeidn_tr_aliexpress_language', 'en');

        if ($localCurrency) {
            $requestUrl .= "&localCurrency=$localCurrency";
        }

        $request = Curl::get($requestUrl);

        if (is_wp_error($request)) {
            return ['state' => 'error', 'message' => 'alibaba . com not response!'];
        }

        $data = json_decode($request['body'], true);

        if (isset($data['errorCode']) && (int)$data['errorCode'] === 20010000) {
            if (isset($data['result']['productId']) &&
                (int)$data['result']['productId'] === (int)$product->getExternalId()
            ) {
                $localPrice = $localCurrency ?
                    Utils::fixPrice($data['result']['localPrice']) :
                    Utils::fixPrice($data['result']['salePrice']);

                $salePrice = Utils::fixPrice($data['result']['salePrice']);
                $usdCourse = round($localPrice / $salePrice, 2);

                $product->setPrice(round($localPrice, 2));

                $originalPrice = Utils::fixPrice($data['result']['originalPrice']);

                $product->setRegularPrice(round($originalPrice * $usdCourse, 2));
                $product->setUserPrice(round($product->getPrice() * $currencyConversionFactor, 2));
                $product->setUserRegularPrice(round($product->getRegularPrice() * $currencyConversionFactor, 2));

                $commissionRate = 8;

                $additionalMeta['commission'] = round($localPrice * ($commissionRate / 100), 2);
                $additionalMeta['regular_price'] = round(
                    Utils::fixPrice($data['result']['originalPrice']) * $currencyConversionFactor,
                    2
                );
                $additionalMeta['sale_price'] = round(
                    Utils::fixPrice($data['result']['salePrice']) * $currencyConversionFactor,
                    2
                );
                $additionalMeta['detail_url'] = $data['result']["productUrl"];
                $product->setAdditionalMeta($additionalMeta);

                $product->setDetailUrl($data['result']["productUrl"]);
                $product->setSellerUrl(isset($data['result']["storeUrl"]) ? $data['result']["storeUrl"] : "");
                $product->setImage($data['result']['imageUrl']);

                $product->save();

                return [
                    "state" => "ok",
                    "message" => ""
                ];
            } else {
                return ["state" => "ok", "message" => ""];
            }
        } elseif (isset($data['errorCode']) &&
            (int)$data['errorCode'] === 20010000 &&
            isset($data['result']['productId'])
        ) {
            return array('state' => 'error', 'message' => 'System Error');
        } elseif (isset($data['errorCode']) &&
            ((int)$data['errorCode'] === 20130000 || (int)$data['errorCode'] === 20030100)
        ) {
            return array('state' => 'error', 'message' => 'Input parameter Product ID is error');
        } elseif (isset($data['error_code']) && (int)$data['error_code'] === 400) {
            return array('state' => 'error', 'message' => "{$data['error_message']}");
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030000) {
            return array('state' => 'error', 'message' => 'Required parameters');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030010) {
            return array('state' => 'error', 'message' => 'Keyword input parameter error');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030020) {
            return array('state' => 'error', 'message' => 'Category ID input parameter error or formatting errors');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030030) {
            return array('state' => 'error', 'message' => 'Commission rate input parameter error or formatting errors');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030040) {
            return array('state' => 'error', 'message' => 'Unit input parameter error or formatting errors');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030050) {
            return [
                'state' => 'error',
                'message' => '30 days promotion amount input parameter error or formatting errors'
            ];
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030060) {
            return array('state' => 'error', 'message' => 'Tracking ID input parameter error or limited length');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20030070) {
            return array('state' => 'error', 'message' => 'Unauthorized transfer request');
        } elseif (isset($data['errorCode']) && (int)$data['errorCode'] === 20020000) {
            return array('state' => 'error', 'message' => 'System Error');
        } else {
            return array('state' => 'error', 'message' => 'Unknown Error');
        }
    }

    /**
     * @param $filter
     * @param int $page
     * @return mixed
     */
    protected function loadListRemote($filter, $page = 1)
    {
        $db = Db::getInstance()->getDb();

        $perPage = get_option('aeidn_ali_per_page', 20);
        $result = ['total' => 0, 'per_page' => $perPage, 'items' => [], 'error' => ''];

        if (isset($filter['link_category_id']) && (int)$filter['link_category_id']) {
            $linkCategoryId = (int)$filter['link_category_id'];
        } else {
            $linkCategoryId = 0;
        }

        if ($linkCategoryId &&
            ((isset($filter['aeidn_productId']) && !empty($filter['aeidn_productId'])) ||
                (isset($filter['aeidn_query']) && !empty($filter['aeidn_query'])) ||
                (isset($filter['category_id']) && (int)$filter['category_id'] !== 0)
            )
        ) {
            if (isset($filter['aeidn_productId']) && $filter['aeidn_productId']) {
                $singleProductId = $filter['aeidn_productId'];
            } else {
                $singleProductId = '';
            }

            $query = isset($filter['aeidn_query']) ? urlencode(utf8_encode($filter['aeidn_query'])) : '';
            $categoryId = (isset($filter['category_id']) && $filter['category_id']) ? $filter['category_id'] : '';
            if (isset($filter['link_category_id']) && (int)$filter['link_category_id']) {
                $linkCategoryId = (int)$filter['link_category_id'];
            } else {
                $linkCategoryId = 0;
            }

            if (isset($filter['aeidn_min_price']) &&
                !empty($filter['aeidn_min_price']) &&
                (float)$filter['aeidn_min_price'] > 0.009
            ) {
                $priceFrom = "&originalPriceFrom={$filter['aeidn_min_price']}";
            } else {
                $priceFrom = '';
            }
            if (isset($filter['aeidn_max_price']) &&
                !empty($filter['aeidn_max_price']) &&
                (float)$filter['aeidn_max_price'] > 0.009
            ) {
                $priceTo = "&originalPriceTo={$filter['aeidn_max_price']}";
            } else {
                $priceTo = '';
            }

            if (isset($filter['commission_rate_from']) &&
                !empty($filter['commission_rate_from']) &&
                (float)$filter['commission_rate_from'] > 0.009
            ) {
                $commissionRateFrom = "&commissionRateFrom={$filter['commission_rate_from']}";
            } else {
                $commissionRateFrom = '';
            }
            if (isset($filter['commission_rate_to']) &&
                !empty($filter['commission_rate_to']) &&
                (float)$filter['commission_rate_to'] > 0.009
            ) {
                $commissionRateTo = "&commissionRateTo={$filter['commission_rate_to']}";
            } else {
                $commissionRateTo = '';
            }

            if (isset($filter['volume_from']) && !empty($filter['volume_from']) && (int)$filter['volume_from'] > 0) {
                $volumeFrom = "&volumeFrom={$filter['volume_from']}";
            } else {
                $volumeFrom = '';
            }
            if (isset($filter['volume_to']) && !empty($filter['volume_to']) && (int)$filter['volume_to'] > 0) {
                $volumeTo = "&volumeTo={$filter['volume_to']}";
            } else {
                $volumeTo = '';
            }

            $highQualityItems = isset($filter['high_quality_items']) ? '&highQualityItems=true' : '';

            if (isset($filter['min_feedback']) && (int)$filter['min_feedback'] > 0) {
                $feedbackMin = (int)$filter['min_feedback'];
            } else {
                $feedbackMin = 0;
            }
            if (isset($filter['max_feedback']) && (int)$filter['max_feedback'] > 0) {
                $feedbackMax = (int)$filter['max_feedback'];
            } else {
                $feedbackMax = 0;
            }
            if ($feedbackMax < $feedbackMin) {
                $feedbackMax = 0;
            }

            $startCredit = $feedbackMin ? "&startCreditScore={$feedbackMin}" : '';
            $endCredit = $feedbackMax ? "&endCreditScore={$feedbackMax}" : '';

            $localCurrency = strtoupper(get_option('aeidn_ali_local_currency', ''));
            if ($localCurrency) {
                $localCurrencyReq = "&localCurrency=$localCurrency";
                $currencyConversionFactor = 1;
            } else {
                $localCurrencyReq = '';
                $currencyConversionFactor = (float)get_option('aeidn_currency_conversion_factor', 1);
            }

            $requestSort = '';
            if (isset($filter['orderby'])) {
                $requestSort = '&sort=';
                switch ($filter['orderby']) {
                    case 'price':
                        if ($filter['order'] === 'asc') {
                            $requestSort .= 'orignalPriceUp';
                        } elseif ($filter['order'] === 'desc') {
                            $requestSort .= 'orignalPriceDown';
                        }

                        break;
                    case 'validTime':
                        if ($filter['order'] === 'asc') {
                            $requestSort .= 'validTimeUp';
                        } elseif ($filter['order'] === 'desc') {
                            $requestSort .= 'validTimeDown';
                        }
                        break;
                    default:
                        $requestSort = '';
                }
            }
            // <---------------------------

            $appKey = $this->getAccount()->getAccountDataKeyValue('appKey');

            if ($singleProductId) {
                // search by product id
                $requestUrl = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/";
                $requestUrl .= "api.getPromotionProductDetail/" . $appKey;
                $requestParam = '?fields=productId,productTitle,productUrl,imageUrl,originalPrice,salePrice,discount';
                $requestParam .= ',evaluateScore,commission,commissionRate,30daysCommission,volume,packageType';
                $requestParam .= ',lotNum,validTime,storeName,storeUrl,localPrice';
                $requestParam .= "&productId=$singleProductId";
                $requestParam .= $localCurrencyReq;
                $requestSort = '';
            } else {
                // search by query and params
                $requestUrl = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/";
                $requestUrl .= "api.listPromotionProduct/" . $appKey;
                $requestParam = '?fields=totalResults,productId,productTitle,productUrl,imageUrl,originalPrice,';
                $requestParam .= 'salePrice,discount,evaluateScore,commission,commissionRate,30daysCommission,volume';
                $requestParam .= ',packageType,lotNum,validTime,localPrice';
                $requestParam .= "&categoryId={$categoryId}&pageNo={$page}&keywords={$query}&pageSize={$perPage}";
                $requestParam .= '&language=' . get_option('aeidn_tr_aliexpress_language', 'en');
                $requestParam .= $localCurrencyReq . $commissionRateFrom . $commissionRateTo . $volumeFrom;
                $requestParam .= $volumeTo . $priceFrom . $priceTo . $startCredit . $endCredit . $highQualityItems;
            }

            $fullRequestUrl = $requestUrl . $requestParam . $requestSort;

            $request = Curl::get($fullRequestUrl);
            //echo $full_request_url."<br/>";
            //echo "<pre>";print_r($request);echo "</pre>";
            //$result["call"] = $request_url . $request_param . $request_sort;

            $errorCode = '';

            $items = [];
            if (is_wp_error($request)) {
                $result['error'] = 'alibaba.com not response!';
            } else {
                $items = json_decode($request['body'], true);
                $errorCode = isset($items['errorCode']) ? $items['errorCode'] : '';
                //echo "<pre>";print_r($request);echo "</pre>";
            }

            if ($singleProductId && isset($items['result']) && $items['result']) {
                $items['result'] = array('products' => array($items['result']));
            }

            //echo "<pre>";print_r($items);echo "</pre>";

            if ($errorCode === 20010000 &&
                isset($items['result']['products']) &&
                !empty($items['result']['products'])
            ) {
                $data = $items['result']['products'];

                foreach ($data as $item) {
                    //echo "<pre>";print_r($item);echo "</pre>";

                    $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

                    $count = $db->get_var("SELECT count(*) FROM " . $importer->getTableName('blacklist') . " 
                        WHERE external_id='" . $item['productId'] . "'");
                    if ($count > 0) {
                        continue;
                    }

                    $additionalMeta = [];

                    $externalId = 'aliexpress#' . $item['productId'];

                    $noImageUrl = plugins_url('assets/img/iconPlaceholder_96x96.gif', $importer->getMainFile());

                    $product = ProductFactory::getWithId($externalId);
                    $product->setLinkCategoryId($linkCategoryId);
                    $product->setImage(isset($item['imageUrl']) ? $item['imageUrl'] : $noImageUrl);
                    $product->setDetailUrl($item['productUrl']);
                    $product->setTitle(strip_tags($item['productTitle']));
                    $product->setSubtitle('#notuse#');
                    $product->setCategoryId(0);

                    $additionalMeta['detail_url'] = $item['productUrl'];

                    $additionalMeta['validTime'] = $item['validTime'];


                    if (trim($product->getCategoryName()) === '') {
                        $product->setCategoryName('#needload#');
                    }

                    if (trim($product->getKeywords()) === '') {
                        $product->setKeywords('#needload#');
                    }

                    if (trim($product->getDescription()) === '') {
                        $product->setDescription('#needload#');
                    }

                    if (trim($product->getPhotos()) === '') {
                        $product->setPhotos('#needload#');
                    }

                    $additionalMeta['discount'] = $item['discount'];

                    //	$additionalMeta['condition'] = "New";

                    if ($localCurrency) {
                        $localPrice = Utils::fixPrice($item['localPrice']);
                    } else {
                        $localPrice = Utils::fixPrice($item['salePrice']);
                    }
                    $salePrice = Utils::fixPrice($item['salePrice']);
                    $usdCourse = round($localPrice / $salePrice, 2);

                    $product->setPrice(round($localPrice, 2));

                    $originalPrice = Utils::fixPrice($item['originalPrice']);
                    $product->setRegularPrice(round($originalPrice * $usdCourse, 2));

                    $additionalMeta['original_discount'] = 100 - round($salePrice * 100 / $originalPrice);

                    //course
                    //$additionalMeta['ship'] = '8%';
                    $commission_rate = 8;
                    $additionalMeta['commission'] = round($localPrice * ($commission_rate / 100), 2);

                    $additionalMeta['volume'] = $item['volume'];
                    $additionalMeta['rating'] = $item['evaluateScore'];

                    /* this is for one addon -----> */
                    $fixedRegularPrice = Utils::fixPrice($item['originalPrice']) * $currencyConversionFactor;
                    $additionalMeta['regular_price'] = round($fixedRegularPrice, 2);

                    $fixedSalePrice = Utils::fixPrice($item['salePrice']) * $currencyConversionFactor;
                    $additionalMeta['sale_price'] = round($fixedSalePrice, 2);
                    /* <--------------------------- */
                    if ($localCurrency) {
                        $product->setCurr(strtoupper($localCurrency));
                    } else {
                        if ($currencyConversionFactor > 1) {
                            $product->setCurr(strtoupper("CUSTOM (*$currencyConversionFactor)"));
                        } else {
                            $product->setCurr(strtoupper('USD (Default)'));
                        }
                    }

                    $product->setAdditionalMeta($additionalMeta);

                    $product->save();

                    $product->loadUserPrice($currencyConversionFactor);
                    $product->loadUserImage();

                    $result['items'][] = $product;
                }

                $result['items'] = $this->getAffiliateGoods($result['items']);

                if (isset($items['result']['totalResults'])) {
                    if ((int)$items['result']['totalResults'] > 10240) {
                        $result['total'] = 10240;
                    } else {
                        $result['total'] = $items['result']['totalResults'];
                    }
                }
            }
            if ((int)$errorCode === 20010000 && empty($items['result']['products'])) {
                $result['error'] = 'There is no product to display!';
            } elseif ((int)$errorCode === 400) {
                $result['error'] = $items['error_message'];
            } elseif ((int)$errorCode === 20030000) {
                $result['error'] = 'Required parameters';
            } elseif ((int)$errorCode === 20030010) {
                $result['error'] = 'Keyword input parameter error';
            } elseif ((int)$errorCode === 20030020) {
                $result['error'] = 'Category ID input parameter error or formatting errors';
            } elseif ((int)$errorCode === 20030030) {
                $result['error'] = 'Commission rate input parameter error or formatting errors';
            } elseif ((int)$errorCode === 20030040) {
                $result['error'] = 'Unit input parameter error or formatting errors';
            } elseif ((int)$errorCode === 20030050) {
                $result['error'] = '30 days promotion amount input parameter error or formatting errors';
            } elseif ((int)$errorCode === 20030060) {
                $result['error'] = 'Tracking ID input parameter error or limited length';
            } elseif ((int)$errorCode === 20030070) {
                $result['error'] = 'Unauthorized transfer request';
            } elseif ((int)$errorCode === 20020000) {
                $result['error'] = 'System Error';
            } elseif ((int)$errorCode === 20030100) {
                $result['error'] = 'Error! Input parameter Product ID';
            }
        } else {
            if ((isset($filter['aeidn_productId']) &&
                    !empty($filter['aeidn_productId'])) ||
                (isset($filter['aeidn_query']) && !empty($filter['aeidn_query'])) ||
                (isset($filter['category_id']) && (int)$filter['category_id'] !== 0)
            ) {
                $result["error"] = 'Please set "Link to category" field before searching';
            } else {
                $result["error"] = 'Please enter keywords, product ID, or select an item category from the list.';
            }
        }

        return $result;
    }

    private function getAffiliateGoods($result)
    {
        $urls = '';
        /**
         * @var Product $product
         */
        foreach ($result as $product) {
            $urls .= ($urls ? "," : "") . $product->getDetailUrl();
        }

        $appKey = $this->getAccount()->getAccountDataKeyValue('appKey');
        $trackingId = $this->getAccount()->getAccountDataKeyValue('trackingId');

        $requestUrl = "http://gw.api.alibaba.com/openapi/param2/2/portals.open/api.getPromotionLinks/";
        $requestUrl .= $appKey . "?fields=&trackingId=" . $trackingId . "&urls={$urls}";

        $request = Curl::get($requestUrl);
        if (!is_wp_error($request)) {
            $data = json_decode($request['body'], true);
            if (isset($data['errorCode']) && (int)$data['errorCode'] === 20010000) {
                /**
                 * @var Product $product
                 */
                foreach ($result as $key => $product) {
                    $newPromoUrl = "";
                    foreach ($data['result']['promotionUrls'] as $pu) {
                        if ($pu['url'] === $result[$key]->getDetailUrl()) {
                            $newPromoUrl = $pu['promotionUrl'];
                            break;
                        }
                    }
                    if ($newPromoUrl) {
                        $product->setDetailUrl($newPromoUrl);
                    }
                }
            }
        }

        return $result;
    }
}
