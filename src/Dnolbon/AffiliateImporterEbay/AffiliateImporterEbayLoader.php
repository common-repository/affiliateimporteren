<?php
namespace Dnolbon\AffiliateImporterEbay;

use Dnolbon\AffiliateImporter\Account\AccountFactory;
use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorFactory;
use Dnolbon\AffiliateImporter\Loader\LoaderAbstract;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Utils\Curl;
use Dnolbon\AffiliateImporter\Utils\Utils;

class AffiliateImporterEbayLoader extends LoaderAbstract
{
    /**
     * @return mixed
     */
    protected function loadDetailRemote()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        $noImageUrl = plugins_url('assets/img/iconPlaceholder_96x96.gif', $importer->getMainFile());

        $product = $this->getProduct();
        $additionalMeta = $product->getAdditionalMeta();

        $siteId = 0;
        $account = AccountFactory::getAccount($this->getType());

        if (isset($params['site_code'])) {
            /**
             * @var AffiliateImporterEbayConfigurator $configurator
             */
            $configurator = ConfiguratorFactory::getConfigurator('ebay');

            $sitesList = $configurator->getSites();
            foreach ($sitesList as $s) {
                if ($s['id'] === $params["site_code"]) {
                    $siteId = $s['code'];
                    break;
                }
            }
        }

        $initLoad = isset($params["init_load"]) ? $params["init_load"] : false;

        $apiUrl = "http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=XML";
        $apiUrl .= "&appid=" . $account->getAccountData()['appId'] .
            "&siteid=" . $siteId .
            //"&version=515".
            "&version=889" .
            "&ItemID=" . $product->getExternalId();
        $apiUrl .= "&IncludeSelector=ItemSpecifics,Description,Details,Variations,StoreInfo";

        if ($initLoad) {
            if (get_option('ebdn_ebay_custom_id')) {
                $apiUrl .= "&trackingid=" . get_option('ebdn_ebay_custom_id');
            }
            if (get_option('ebdn_ebay_network_id')) {
                $apiUrl .= "&trackingpartnercode=" . get_option('ebdn_ebay_network_id');
            }
            if (get_option('ebdn_ebay_tracking_id')) {
                $apiUrl .= "&affiliateuserid=" . get_option('ebdn_ebay_tracking_id');
            }
        }

        //echo $api_url;

        $tmpResponse = Curl::get($apiUrl);
        if (is_wp_error($tmpResponse)) {
            $result = ["state" => "error", "message" => "eBay api not response!"];
        } else {
            $body = wp_remote_retrieve_body($tmpResponse);

            $detailXml = simplexml_load_string($body);

            if (!isset($detailXml->Errors)) {
                if ($initLoad) {

                    $currencyConversionFactor = (float)str_replace(
                        ",",
                        ".",
                        (string)get_option('ebdn_currency_conversion_factor', 1)
                    );

                    $product->setLinkCategoryId(isset($params["link_category_id"]) ? $params["link_category_id"] : 0);
                    if ($detailXml->Item->GalleryURL) {
                        $product->setImage((string)$detailXml->Item->GalleryURL);
                    } else {
                        $product->setImage($noImageUrl);
                    }
                    $product->setDetailUrl($detailXml->Item->ViewItemURLForNaturalSearch);

                    if (isset($detailXml->Item->Storefront->StoreURL)) {
                        $product->setSellerUrl($detailXml->Item->Storefront->StoreURL);
                    } elseif (isset($detailXml->Item->Seller->UserID)) {
                        $product->setSellerUrl("http://www.ebay.com/usr/" . $detailXml->Item->Seller->UserID);
                    }

                    $product->setTitle((string)$detailXml->Item->Title);
                    $product->setSubtitle((string)$detailXml->Item->Subtitle);
                    $product->setCategoryId((string)$detailXml->Item->PrimaryCategoryID);
                    $product->setCategoryName((string)$detailXml->Item->PrimaryCategoryName);


                    if (trim($product->getKeywords()) === '') {
                        $product->setKeywords("#needload#");
                    }

                    if (trim($product->getDescription()) === '') {
                        $product->setDescription("#needload#");
                    }

                    if (trim($product->getPhotos()) === '') {
                        $product->setPhotos("#needload#");
                    }

                    if (isset($params["site_code"])) {
                        $additionalMeta['filters'] = ['site_code' => $params["site_code"]];
                    }

                    $additionalMeta['condition'] = "";
                    $additionalMeta['ship'] = "0.00";

                    $additionalMeta['ship_to_locations'] = "";
                    foreach ($detailXml->Item->ShipToLocations as $sl) {
                        if (strlen($additionalMeta['ship_to_locations']) > 0) {
                            $additionalMeta['ship_to_locations'] .= ", " . $sl;
                        } else {
                            $additionalMeta['ship_to_locations'] .= "" . $sl;
                        }
                    }

                    if (get_option('ebdn_ebay_using_woocommerce_currency', false) &&
                        get_woocommerce_currency() === trim((string)$detailXml->Item->CurrentPrice['currencyID'])
                    ) {
                        $product->setPrice(round(Utils::fixPrice($detailXml->Item->CurrentPrice), 2));
                        $product->setCurr(trim((string)$detailXml->Item->CurrentPrice['currencyID']));
                    } else {
                        $product->setPrice(round(Utils::fixPrice($detailXml->Item->ConvertedCurrentPrice), 2));
                        $product->setCurr(trim((string)$detailXml->Item->ConvertedCurrentPrice['currencyID']));
                    }
                    $product->save();

                    $product->loadUserPrice($currencyConversionFactor);
                    $product->loadUserImage();
                }

                $description = $detailXml->Item->Description;
                $description = $this->clearHtml($description);
                $description = Utils::removeTags($description);
                $product->setDescription($description);

                $attrList = array();
                if (isset($detailXml->Item->ItemSpecifics)) {
                    foreach ($detailXml->Item->ItemSpecifics->NameValueList as $attr) {
                        $value = "";
                        foreach ($attr->Value as $v) {
                            $value .= ($value === '' ? "" : ", ") . $v;
                        }
                        $attrList[] = array("name" => (string)$attr->Name, "value" => $value);
                    }
                }

                $additional_meta['attribute'] = $attrList ? $attrList : array();

                if (isset($detailXml->Item->Quantity)) {
                    $quantity = (int)$detailXml->Item->Quantity;
                    if (isset($detailXml->Item->QuantitySold) && (int)$detailXml->Item->QuantitySold) {
                        $quantity -= (int)$detailXml->Item->QuantitySold;
                    }
                    $additional_meta['quantity'] = $quantity;
                }

                $tmpP = "";
                $newPreview = "";
                foreach ($detailXml->Item->PictureURL as $imgUrl) {
                    $imgUrl = preg_replace('/\$\_(\d+)\.JPG/i', '$_10.JPG', $imgUrl);
                    if (!$newPreview) {
                        $newPreview = (string)$imgUrl;
                    }
                    $tmpP .= ($tmpP ? "," : "") . $imgUrl;
                }
                $product->setPhotos($tmpP);

                if ($product->getDetailUrl()) {
                    try {
                        $page_meta = get_meta_tags($product->getDetailUrl());
                        $product->setKeywords((isset($page_meta["keywords"]) ? $page_meta["keywords"] : ""));
                    } catch (\Exception $e) {

                    }
                }
                $product->setAdditionalMeta($additionalMeta);
                $product->save();

                if ($newPreview && (trim((string)$product->getUserImage()) === '' ||
                        trim((string)$product->getUserImage()) === trim((string)$product->getImage()))
                ) {
                    if ($product->getImage() === $noImageUrl) {
                        $product->setImage($newPreview);
                    }
                    $product->save();
                }

                $result = [
                    "state" => "ok",
                    "message" => "",
                    "goods" => apply_filters('ebdn_modify_goods_data', $product, $detailXml, "ebay_load_detail")
                ];
            } else {
                $result = [
                    "state" => "error",
                    'message' => 'Error: ' . $detailXml->Errors->ErrorCode . ". " . $detailXml->Errors->LongMessage,
                    "goods" => $product
                ];
            }
        }

        return $result;
    }

    private function clearHtml($inHtml)
    {
        if (!$inHtml) {
            return "";
        }

        $html = $inHtml;
        $html = preg_replace('/<span class="ebay"[^>]*?>.*?<\/span>/si', '', $html);
        $html = preg_replace("/<\/?h[1-9]{1}[^>]*\>/i", "", $html);
        $html = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);
        $html = preg_replace('/(<[^>]+) class=".*?"/i', '$1', $html);
        $html = preg_replace('/(<[^>]+) width=".*?"/i', '$1', $html);
        $html = preg_replace('/(<[^>]+) height=".*?"/i', '$1', $html);
        $html = preg_replace('/(<[^>]+) alt=".*?"/i', '$1', $html);

        $html = force_balance_tags($html);
        return $html;
    }

    /**
     * @param $filter
     * @param int $page
     * @return mixed
     */
    protected function loadListRemote($filter, $page = 1)
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        $noImageUrl = plugins_url('assets/img/iconPlaceholder_96x96.gif', $importer->getMainFile());

        $perPage = get_option('ebdn_ebay_per_page', 20);

        if ((isset($filter['ebdn_productId']) && !empty($filter['ebdn_productId'])) ||
            (isset($filter['ebdn_query']) && !empty($filter['ebdn_query'])) ||
            (isset($filter['store']) && !empty($filter['store'])) ||
            (isset($filter['category_id']) && (int)$filter['category_id'] !== 0)
        ) {
            $endpoint = 'http://svcs.ebay.com/services/search/FindingService/v1';
            $responseEncoding = 'XML';

            $productId = isset($filter['ebdn_productId']) ? $filter['ebdn_productId'] : "";

            $safeQuery = isset($filter['ebdn_query']) ? urlencode(utf8_encode($filter['ebdn_query'])) : "";
            $site = isset($filter['sitecode']) ? $filter['sitecode'] : "EBAY-US";

            $priceMin = 0;
            if (isset($filter['ebdn_min_price']) && (float)$filter['ebdn_min_price'] > 0.009) {
                $priceMin = (float)$filter['ebdn_min_price'];
            }

            $priceMax = 0;
            if (isset($filter['ebdn_max_price']) && (float)$filter['ebdn_max_price'] > 0.009) {
                $priceMax = (float)$filter['ebdn_max_price'];
            }

            $feedbackMin = 0;
            if (isset($filter['min_feedback']) && (int)$filter['min_feedback'] > 0) {
                $feedbackMin = (int)$filter['min_feedback'];
            }

            $feedbackMax = 0;
            if (isset($filter['max_feedback']) && (int)$filter['max_feedback'] > 0) {
                $feedbackMax = (int)$filter['max_feedback'];
            }

            if ($feedbackMax < $feedbackMin) {
                $feedbackMax = 0;
            }

            $availableTo = (isset($filter['available_to']) && $filter['available_to']) ? $filter['available_to'] : "";

            $condition = (isset($filter['condition']) && $filter['condition']) ? $filter['condition'] : "";

            $freeShippingOnly = (isset($filter['free_shipping_only']) && $filter['free_shipping_only']);

            $categoryId = 0;
            if (isset($filter['category_id']) && (int)$filter['category_id']) {
                $categoryId = (int)$filter['category_id'];
            }

            $linkCategoryId = 0;
            if (isset($filter['link_category_id']) && (int)$filter['link_category_id']) {
                $linkCategoryId = (int)$filter['link_category_id'];
            }

            $storeName = "";
            if (isset($filter['store'])) {
                $storeName = $filter['store'];
            }

            $listingType = [];
            if (isset($filter['listing_type']) && is_array($filter['listing_type'])) {
                $listingType = $filter['listing_type'];
            }

            $pagenum = ((int)$page) ? $page : 1;

            if ($productId) {
                $tmpRes = $this->loadDetail(
                    ProductFactory::getWithId("ebay#$productId"),
                    ["init_load" => true, "link_category_id" => $linkCategoryId, "site_code" => $site]
                );
                if ($tmpRes['state'] === 'ok') {
                    $result["total"] = 1;
                    $result["items"] = array($tmpRes['goods']);
                } else {
                    $result["error"] = $tmpRes['message'];
                }
            } else {
                $account = AccountFactory::getAccount($this->getType());

                $apicall = "$endpoint?OPERATION-NAME=" . ($storeName ? "findItemsIneBayStores" : "findItemsAdvanced")
                    . "&SERVICE-VERSION=1.12.0"
                    . "&GLOBAL-ID=$site"
                    . "&SECURITY-APPNAME=" . $account->getAccountData()['appID']
                    . "&RESPONSE-DATA-FORMAT=$responseEncoding"
                    . ($safeQuery ? "&keywords=" . $safeQuery : "")
                    . ($storeName ? "&storeName=" . $storeName : "")
                    . "&paginationInput.entriesPerPage=$perPage"
                    . "&paginationInput.pageNumber=$pagenum"
                    . "&sortOrder=BestMatch"
                    . "&outputSelector(0)=SellerInfo"
                    . "&outputSelector(1)=StoreInfo"
                    . "&descriptionSearch(1)=true"
                    . ($categoryId ? "&categoryId=" . $categoryId : "");


                if (get_option('ebdn_ebay_custom_id')) {
                    $apicall .= "&affiliate.customId=" . get_option('ebdn_ebay_custom_id');
                }
                if (get_option('ebdn_ebay_geo_targeting', false)) {
                    $apicall .= "&affiliate.geoTargeting=true";
                }
                if (get_option('ebdn_ebay_network_id')) {
                    $apicall .= "&affiliate.networkId=" . get_option('ebdn_ebay_network_id');
                }
                if (get_option('ebdn_ebay_tracking_id')) {
                    $apicall .= "&affiliate.trackingId=" . get_option('ebdn_ebay_tracking_id');
                }


                $filter_index = 0;

                $apicall .= "&itemFilter($filter_index).name=HideDuplicateItems&itemFilter($filter_index).value=true";
                $filter_index++;

                if ($feedbackMin) {
                    $apicall .= "&itemFilter($filter_index).name=FeedbackScoreMin";
                    $apicall .= "&itemFilter($filter_index).value=$feedbackMin";
                    $filter_index++;
                }
                if ($feedbackMax) {
                    $apicall .= "&itemFilter($filter_index).name=FeedbackScoreMax";
                    $apicall .= "&itemFilter($filter_index).value=$feedbackMax";
                    $filter_index++;
                }
                if ($priceMin) {
                    $apicall .= "&itemFilter($filter_index).name=MinPrice&itemFilter($filter_index).value=$priceMin";
                    $filter_index++;
                }
                if ($priceMax) {
                    $apicall .= "&itemFilter($filter_index).name=MaxPrice&itemFilter($filter_index).value=$priceMax";
                    $filter_index++;
                }

                if ($availableTo) {
                    $apicall .= "&itemFilter($filter_index).name=AvailableTo";
                    $apicall .= "&itemFilter($filter_index).value=$availableTo";
                    $filter_index++;
                }

                if ($condition) {
                    $apicall .= "&itemFilter($filter_index).name=Condition&itemFilter($filter_index).value=$condition";
                    $filter_index++;
                }

                /* show only USD
                if (true) {
                    $apicall.="&itemFilter($filter_index).name=Currency&itemFilter($filter_index).value=USD";
                    $filter_index++;
                }*/

                if ($freeShippingOnly) {
                    $apicall .= "&itemFilter($filter_index).name=FreeShippingOnly&itemFilter($filter_index).value=true";
                    $filter_index++;
                }

                if ($listingType) {
                    $apicall .= "&itemFilter($filter_index).name=ListingType";
                    foreach ($listingType as $i => $listingValue) {
                        $apicall .= "&itemFilter($filter_index).value($i)=" . $listingValue;
                    }
                } else {
                    $apicall .= "&itemFilter($filter_index).name=ListingType";
                    $apicall .= "&itemFilter($filter_index).value=FixedPrice";
                }

                if (isset($_GET['orderby'])) {
                    switch ($_GET['orderby']) {
                        case 'price':
                            if ($_GET['order'] === 'asc') {
                                $apicall .= "&sortOrder=PricePlusShippingLowest";
                            } elseif ($_GET['order'] === 'desc') {
                                $apicall .= "&sortOrder=CurrentPriceHighest";
                                //$apicall.="&sortOrder=PricePlusShippingHighest";
                            }
                            break;
                        default:
                            break;
                    }
                }

                //echo $apicall;

                $tmpResponse = Curl::get($apicall);
                if (is_wp_error($tmpResponse)) {
                    $result["error"] = 'eBay api not response!';
                } else {
                    $body = wp_remote_retrieve_body($tmpResponse);
                    $resp = simplexml_load_string($body);

                    if (isset($resp->errorMessage->error)) {
                        $result["error"] = "Error code: " . (string)$resp->errorMessage->error->errorId . ".";
                        $result["error"] .= (string)$resp->errorMessage->error->message;
                    } else {
                        if ($resp && $resp->paginationOutput->totalEntries > 0) {
                            if ((int)$resp->paginationOutput->totalEntries > ($perPage * 100)) {
                                $result["total"] = ($perPage * 100);
                            } else {
                                $result["total"] = (int)$resp->paginationOutput->totalEntries;
                            }

                            $currencyConversionFactor = (float)str_replace(
                                ",",
                                ".",
                                (string)get_option('ebdn_currency_conversion_factor', 1)
                            );
                            foreach ($resp->searchResult->item as $item) {
                                //echo "<pre>";print_r($item);echo "</pre>";

                                $product = ProductFactory::getWithId("ebay#" . $item->itemId);
                                $product->setDetailUrl(
                                    str_replace("item=0", "item=" . $item->itemId, $item->viewItemURL)
                                );
                                $product->setLinkCategoryId($linkCategoryId);
                                $product->setImage($item->galleryURL ? (string)$item->galleryURL : $noImageUrl);


                                if (isset($item->storeInfo->storeURL)) {
                                    $product->setSellerUrl((string)$item->storeInfo->storeURL);
                                } elseif (isset($item->sellerInfo->sellerUserName)) {
                                    $product->setSellerUrl(
                                        "http://www.ebay.com/usr/" . $item->sellerInfo->sellerUserName
                                    );
                                }

                                $product->setTitle((string)$item->title);
                                $product->setSubtitle((string)$item->subtitle);

                                $product->setCategoryId((string)$item->primaryCategory->categoryId);
                                $product->setCategoryName((string)$item->primaryCategory->categoryName);

                                if (trim($product->getKeywords()) === '') {
                                    $product->setKeywords("#needload#");
                                }

                                if (trim($product->getDescription()) === '') {
                                    $product->setDescription("#needload#");
                                }

                                if (trim($product->getPhotos()) === '') {
                                    $product->setPhotos("#needload#");
                                }

                                $additionalMeta = $product->getAdditionalMeta();
                                $additionalMeta['filters'] = array('site_code' => $site);

                                $additionalMeta['condition'] = (string)$item->condition->conditionDisplayName;
                                $additionalMeta['ship'] = Utils::fixPrice($item->shippingInfo->shippingServiceCost);
                                $additionalMeta['ship_to_locations'] = "";
                                foreach ($item->shippingInfo->shipToLocations as $sl) {
                                    if (strlen($additionalMeta['ship_to_locations']) > 0) {
                                        $additionalMeta['ship_to_locations'] .= ", " . $sl;
                                    } else {
                                        $additionalMeta['ship_to_locations'] .= $sl;
                                    }
                                }

                                $price = round(Utils::fixPrice($item->sellingStatus->convertedCurrentPrice), 2);
                                $product->setPrice($price);


                                $currencyId = trim((string)$item->sellingStatus->currentPrice['currencyId']);
                                if (get_option('ebdn_ebay_using_woocommerce_currency', false) &&
                                    get_woocommerce_currency() === $currencyId
                                ) {
                                    $product->setPrice(round(Utils::fixPrice($item->sellingStatus->currentPrice), 2));
                                    $product->setCurr(trim((string)$item->sellingStatus->currentPrice['currencyId']));
                                } else {
                                    $roundedPrice = round(Utils::fixPrice(
                                        $item->sellingStatus->convertedCurrentPrice
                                    ), 2);
                                    $product->setPrice($roundedPrice);
                                    $product->setCurr($currencyId);
                                }
                                $product->setAdditionalMeta($additionalMeta);
                                $product->save();

                                if (trim((string)$product->getUserPrice()) === '') {
                                    $product->setUserPrice(round($product->getPrice() * $currencyConversionFactor, 2));
                                    $product->save();
                                }

                                if (trim((string)$product->getUserImage()) === '') {
                                    $product->setUserImage($product->getImage());
                                }

                                $result["items"][] = $product;
                            }
                        }
                    }
                }
            }
        } else {
            $error = 'Please enter some search keywords or input specific prodcutId';
            $error .= ' or specifc store name or select item from category list!';
            $result["error"] = $error;
        }
    }
}
