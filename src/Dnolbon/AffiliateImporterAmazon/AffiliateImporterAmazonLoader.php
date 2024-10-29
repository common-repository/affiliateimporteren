<?php
namespace Dnolbon\AffiliateImporterAmazon;

use Dnolbon\AffiliateImporter\Account\AccountFactory;
use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Loader\LoaderAbstract;
use Dnolbon\AffiliateImporter\Products\ProductFactory;
use Dnolbon\AffiliateImporter\Utils\Curl;
use Dnolbon\AffiliateImporter\Utils\Utils;

class AffiliateImporterAmazonLoader extends LoaderAbstract
{

    /**
     * @return mixed
     */
    protected function loadDetailRemote()
    {
        $product = $this->getProduct();

        $prms = [
            "Operation" => "ItemLookup",
            "ItemId" => $product->getExternalId(),
            "IdType" => "ASIN",
            "ResponseGroup" => "Images,ItemAttributes,Large,OfferFull,Offers,OfferSummary",
            "Version" => "2015-10-01"
        ];

        $site = isset($params['sitecode']) ? $params['sitecode'] : get_option('aidn_amazon_default_site', 'com');

        $response = $this->sendAmazonRequest($site, $prms);

        if (isset($response['error'])) {
            return ['state' => 'error', 'message' => $response['error']];
        } else {
            $item = $response['Items']['Item'];

            if ($item) {
                $currency_conversion_factor = (float)get_option('aidn_currency_conversion_factor', 1);

                $product = $this->parseAmazonItem($item);

                $product->setUserPrice(round($product->getPrice() * $currency_conversion_factor, 2));

                if ($product->getRegularPrice()) {
                    $product->setUserRegularPrice(round($product->getRegularPrice() * $currency_conversion_factor, 2));
                }

                return [
                    "state" => "ok",
                    "message" => "",
                    "goods" => $product
                ];
            }
        }
        return ['state' => 'error', 'message' => ''];
    }

    public function sendAmazonRequest($site_id, $prms = array())
    {
        $params = is_array($prms) ? $prms : array();

        // The region you are interested in
        $endpoint = "webservices.amazon." . $site_id;
        $uri = "/onca/xml";

        $account = AccountFactory::getAccount('amazon');

        $awsAssociateTag = $account->getAccountData()['associate_tag'];
        $awsAccessKeyId = $account->getAccountData()['access_key_id'];
        $awsSecretKey = $account->getAccountData()['secret_access_key'];

        if (!isset($params['AWSAccessKeyId'])) {
            $params['AWSAccessKeyId'] = $awsAccessKeyId;
        }
        if (!isset($params['AssociateTag'])) {
            $params['AssociateTag'] = $awsAssociateTag;
        }
        if (!isset($params['Service'])) {
            $params['Service'] = "AWSECommerceService";
        }

        // Set current timestamp if not set
        if (!isset($params["Timestamp"])) {
            $params["Timestamp"] = gmdate('Y-m-d\TH:i:s\Z');
        }

        // Sort the parameters by key
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = rawurlencode($key) . "=" . rawurlencode($value);
        }

        // Generate the canonical query
        $canonicalQueryString = implode("&", $pairs);

        // Generate the string to be signed
        $stringToSign = "GET\n" . $endpoint . "\n" . $uri . "\n" . $canonicalQueryString;

        // Generate the signature required by the Product Advertising API
        $signature = base64_encode(hash_hmac("sha256", $stringToSign, $awsSecretKey, true));

        // Generate the signed URL
        $requestUrl = 'http://' . $endpoint . $uri . '?' . $canonicalQueryString;
        $requestUrl .= '&Signature=' . rawurlencode($signature);

        //echo "Signed URL: \"" . $request_url . "\"<br/>";

        $response = Curl::get($requestUrl);
        //echo "<pre>";print_r($response);echo "</pre>";

        if (is_wp_error($response)) {
            return array("error" => "Amazon not response!");
        } else {
            if (wp_remote_retrieve_response_code($response) !== '200') {
                $error = "[" . wp_remote_retrieve_response_code($response) . "]";
                $error .= " " . wp_remote_retrieve_response_message($response);
                return [
                    "error" => $error,
                    "body_message" => wp_remote_retrieve_body($response)
                ];
            } else {
                $body = wp_remote_retrieve_body($response);
                //echo "<pre>";print_r($body);echo "</pre>";
                $response_xml = simplexml_load_string($body);
                //echo "<pre>";print_r($response_xml);echo "</pre>";

                $response_json = json_encode($response_xml);
                $response = json_decode($response_json, true);

                if ($response['Items']['Request']['IsValid'] === 'True') {
                    return $response;
                } else {
                    $error = $response_xml->Items->Request->Errors->Error->Code . ";";
                    $error .= " " . $response_xml->Items->Request->Errors->Error->Message;
                    return array(
                        "error" => $error
                    );
                }
            }
        }
    }

    public function parseAmazonItem($item, $params = array())
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());
        $noImageUrl = plugins_url('assets/img/iconPlaceholder_96x96.gif', $importer->getMainFile());

        $product = ProductFactory::getWithId("amazon" . $item["ASIN"]);
        $product->setImage(isset($item["LargeImage"]["URL"]) ? $item["LargeImage"]["URL"] : $noImageUrl);
        $product->setDetailUrl($item["DetailPageURL"]);
        $product->setTitle($item["ItemAttributes"]["Title"]);
        $product->setSubtitle("#notuse#");
        $product->setKeywords("#notuse#");
        $product->setCategoryId(0);

        if (isset($item["BrowseNodes"]["BrowseNode"]["Name"])) {
            $product->setCategoryName($item["BrowseNodes"]["BrowseNode"]["Name"]);
        } elseif (isset($item["BrowseNodes"]["BrowseNode"][0]["Name"])) {
            $product->setCategoryName($item["BrowseNodes"]["BrowseNode"][0]["Name"]);
        }

        $description = '';
        foreach ($item['ItemAttributes'] as $attr => $value) {
            if ($attr === "Feature") {
                $description .= '<div class="feature"><span>Feature:</span>';
                $description .= '<ul>';
                $value = is_array($value) ? $value : array($value);
                foreach ($value as $v) {
                    $description .= "<li>" . $v . "</li>";
                }
                $description .= '</ul>';
                $description .= '</div>';
            }
        }


        if (isset($item['EditorialReviews']['EditorialReview'])) {
            if (isset($item['EditorialReviews']['EditorialReview'][0])) {
                foreach ($item['EditorialReviews']['EditorialReview'] as $dd) {
                    if ($dd['Source'] === 'Product Description') {
                        $description .= '<div class="product_description">' . $dd['Content'] . '</div>';
                    }
                }
            } elseif (isset($item['EditorialReviews']['EditorialReview']['Content'])) {
                $description .= '<div class="product_description">';
                $description .= $item['EditorialReviews']['EditorialReview']['Content'];
                $description .= '</div>';
            }
        }
        $description = Utils::removeTags($description);

        $product->setDescription($description);

        $attrs = array();
        $attr_exclude = [
            "EANList",
            "Feature",
            "Label",
            "PackageDimensions",
            "PackageQuantity",
            "ProductGroup",
            "ProductTypeName",
            "UPCList",
            "Title"
        ];
        foreach ($item['ItemAttributes'] as $attr => $value) {
            if (!in_array($attr, $attr_exclude, false) && !is_array($value)) {
                $attrs[] = array("name" => $attr, "value" => $value);
            }
        }

        $additionalMeta = $product->getAdditionalMeta();
        if (isset($item["ParentASIN"]) && $item["ParentASIN"]) {
            $additionalMeta['parent_id'] = $item["ParentASIN"];
        }

        $additionalMeta['attribute'] = $attrs ? $attrs : array();

        $tmp_p = "";
        if (isset($item['ImageSets'])) {
            $images = array($item['ImageSets']['ImageSet']);
            if (isset($item['ImageSets']['ImageSet'][0])) {
                $images = $item['ImageSets']['ImageSet'];
            }
            foreach ($images as $img) {
                if ($img["@attributes"]["Category"] === "variant") {
                    $tmp_p .= ($tmp_p ? "," : "") . $img["LargeImage"]["URL"];
                }
            }
        }
        $product->setPhotos($tmp_p);

        $tmpCondition = "";
        $tmpCurr = "";
        $tmpPrice = 0;
        $tmpPercentageSaved = 0;

        $getPriceByCondition = true;


        //echo "<pre>";print_r($item['Offers']);echo "</pre>";
        //echo "<pre>";print_r($item['OfferSummary']);echo "</pre>";
        if (isset($item['Offers']['Offer'])) {
            if ((int)$item['Offers']['TotalOffers'] === 1) {
                $tmpOffers = array($item['Offers']['Offer']);
            } else {
                $tmpOffers = $item['Offers']['Offer'];
            }
            if (isset($params['condition']) && $getPriceByCondition) {
                foreach ($tmpOffers as $offer) {
                    if ($params['condition'] === $offer['OfferAttributes']['Condition']) {
                        if (isset($offer['OfferListing']['SalePrice']['Amount'])) {
                            $tmpCurr = $offer['OfferListing']['SalePrice']['CurrencyCode'];
                            $tmpPrice = (float)$offer['OfferListing']['SalePrice']['Amount'] / 100;
                        } else {
                            $tmpCurr = $offer['OfferListing']['Price']['CurrencyCode'];
                            $tmpPrice = (float)$offer['OfferListing']['Price']['Amount'] / 100;
                        }
                        $tmpCondition = $offer['OfferAttributes']['Condition'];
                    }
                }
            }
            if (!$tmpPrice) {
                $curTmpPercentageSaved = 0;
                foreach ($tmpOffers as $offer) {
                    if (isset($offer['OfferListing']['SalePrice']['Amount'])) {
                        $curTmpCurr = "";
                        if (isset($offer['OfferListing']['SalePrice']['CurrencyCode'])) {
                            $curTmpCurr = $offer['OfferListing']['SalePrice']['CurrencyCode'];
                        }

                        $curTmpPrice = 0;
                        if (isset($offer['OfferListing']['SalePrice']['Amount'])) {
                            $curTmpPrice = (float)$offer['OfferListing']['SalePrice']['Amount'] / 100;
                        }
                    } else {
                        $curTmpCurr = "";
                        if (isset($offer['OfferListing']['Price']['CurrencyCode'])) {
                            $curTmpCurr = $offer['OfferListing']['Price']['CurrencyCode'];
                        }
                        $curTmpPrice = 0;
                        if (isset($offer['OfferListing']['Price']['Amount'])) {
                            $curTmpPrice = (float)$offer['OfferListing']['Price']['Amount'] / 100;
                        }
                        $curTmpPercentageSaved = 0;
                        if (isset($offer['OfferListing']['PercentageSaved'])) {
                            $curTmpPercentageSaved = (int)$offer['OfferListing']['PercentageSaved'];
                        }
                    }

                    if (!$tmpCurr || $tmpPrice < $curTmpPrice) {
                        $tmpCurr = $curTmpCurr;
                        $tmpPrice = $curTmpPrice;
                        $tmpCondition = $offer['OfferAttributes']['Condition'];
                        $tmpPercentageSaved = $curTmpPercentageSaved;
                    }
                }
            }
        } else { // last try... find some price
            $totalKey = 'Total' . $params['condition'];
            if (isset($params['condition']) &&
                isset($item['OfferSummary'][$totalKey]) &&
                (int)$item['OfferSummary'][$totalKey] > 0
            ) {
                $tmpCondition = $params['condition'];

                $tmpCurr = $item['OfferSummary']['Lowest' . $params['condition'] . 'Price']['CurrencyCode'];
                $tmpPrice = (float)$item['OfferSummary']['Lowest' . $params['condition'] . 'Price']['Amount'] / 100;
            }

            if (!$tmpCondition) {
                if (isset($item['OfferSummary']['TotalNew']) &&
                    (int)$item['OfferSummary']['TotalNew'] > 0
                ) {
                    $tmpCondition = "New";
                    $tmpCurr = $item['OfferSummary']['LowestNewPrice']['CurrencyCode'];
                    $tmpPrice = (float)$item['OfferSummary']['LowestNewPrice']['Amount'] / 100;
                } elseif (isset($item['OfferSummary']['TotalUsed']) &&
                    (int)$item['OfferSummary']['TotalUsed'] > 0
                ) {
                    $tmpCondition = "Used";
                    $tmpCurr = $item['OfferSummary']['LowestUsedPrice']['CurrencyCode'];
                    $tmpPrice = (float)$item['OfferSummary']['LowestUsedPrice']['Amount'] / 100;
                } elseif (isset($item['OfferSummary']['TotalCollectible']) &&
                    (int)$item['OfferSummary']['TotalCollectible'] > 0
                ) {
                    $tmpCondition = "Collectible";
                    $tmpCurr = $item['OfferSummary']['LowestCollectiblePrice']['CurrencyCode'];
                    $tmpPrice = (float)$item['OfferSummary']['LowestCollectiblePrice']['Amount'] / 100;
                } elseif (isset($item['OfferSummary']['TotalRefurbished']) &&
                    (int)$item['OfferSummary']['TotalRefurbished'] > 0
                ) {
                    $tmpCondition = "Refurbished";
                    $tmpCurr = $item['OfferSummary']['LowestRefurbishedPrice']['CurrencyCode'];
                    $tmpPrice = (float)$item['OfferSummary']['LowestRefurbishedPrice']['Amount'] / 100;
                }
            }
        }
        $additionalMeta['condition'] = $tmpCondition;


        $product->setSellerUrl("#notuse#");


        $product->setPrice(round(Utils::fixPrice($tmpPrice), 2));
        if ($tmpPercentageSaved) {
            $additionalMeta['original_discount'] = $tmpPercentageSaved;
            $product->setRegularPrice(round($product->getPrice() * 100 / (100 - $tmpPercentageSaved), 2));
        }

        $product->setAdditionalMeta($additionalMeta);
        $product->setCurr($tmpCurr);

        return $product;
    }

    /**
     * @param $filter
     * @param int $page
     * @return mixed
     */
    protected function loadListRemote($filter, $page = 1)
    {
        $MAX_RESULT_ITEMS = 50;
        $per_page = get_option('aidn_amazon_per_page', 10);
        $result = array("total" => 0, "per_page" => $per_page, "items" => array(), "error" => "");
        if ((isset($filter['aidn_productId']) && !empty($filter['aidn_productId'])) ||
            (isset($filter['aidn_query']) && !empty($filter['aidn_query'])) ||
            (isset($filter['category_id']) && (int)$filter['category_id'] !== 0)
        ) {
            $singleProductId = "";
            if (isset($filter['aidn_productId']) && $filter['aidn_productId']) {
                $singleProductId = $filter['aidn_productId'];
            }

            $query = isset($filter['aidn_query']) ? utf8_encode($filter['aidn_query']) : "";

            $site = isset($filter['sitecode']) ? $filter['sitecode'] : get_option('aidn_amazon_default_site', 'com');

            $categoryId = (isset($filter['category_id']) && $filter['category_id']) ? $filter['category_id'] : "";
            $linkCategoryId = 0;
            if (isset($filter['link_category_id']) && (int)$filter['link_category_id']) {
                $linkCategoryId = (int)$filter['link_category_id'];
            }

            $priceFrom = false;
            if (isset($filter['aidn_min_price']) &&
                !empty($filter['aidn_min_price']) &&
                (float)$filter['aidn_min_price'] > 0.009
            ) {
                $priceFrom = sprintf("%01.2f", (float)$filter['aidn_min_price']);
            }

            $priceTo = false;
            if (isset($filter['aidn_max_price']) &&
                !empty($filter['aidn_max_price']) &&
                (float)$filter['aidn_max_price'] > 0.009
            ) {
                $priceTo = sprintf("%01.2f", (float)$filter['aidn_max_price']);
            }

            $condition = (isset($filter['condition']) && $filter['condition']) ? $filter['condition'] : "";
            // <---------------------------

            if ($singleProductId) {
                $group = "Images,ItemAttributes,Large,OfferFull,Offers,OfferSummary";
                $group .= ",VariationImages,Variations,VariationSummary";
                $params = array(
                    "Operation" => "ItemLookup",
                    "ItemId" => $singleProductId,
                    "IdType" => "ASIN",
                    "ResponseGroup" => $group,
                    "Version" => "2015-10-01"
                );
            } else {
                $params = array(
                    "Operation" => "ItemSearch",
                    "SearchIndex" => "All",
                    "ItemPage" => $page,
                    "Keywords" => $query,
                    "ResponseGroup" => "Images,ItemAttributes,Large,OfferFull,Offers,OfferSummary",
                    "Version" => "2015-10-01"
                    //"ResponseGroup" => "Images,ItemAttributes,Offers",
                    //"Sort" => "price"
                );

                if ($priceFrom) {
                    $params['MinimumPrice'] = (int)((float)$priceFrom * 100);
                }
                if ($priceTo) {
                    $params['MaximumPrice'] = (int)((float)$priceTo * 100);
                }
                if ($categoryId) {
                    $params['SearchIndex'] = $categoryId;
                }
                if ($condition) {
                    $params['Condition'] = $condition;
                }
                if ($condition !== "New") {
                    $params['Availability'] = "Available";
                }
            }
            //print_r($params);
            $response = $this->sendAmazonRequest($site, $params);

            if (isset($response['error'])) {
                $result["error"] = $response['error'];
                if (isset($response['body_message']) && $response['body_message']) {
                    $result["error"] .= "<br/>" . $response['body_message'];
                }
            } else {
                //echo "<pre>";print_r($response);echo "</pre>";
                $items = array();
                if (isset($response['Items']['Item']) && $response['Items']['Item']) {
                    $items = $response['Items']['Item'];
                }
                //echo "<pre>";print_r($items);echo "</pre>";
                if ($items) {
                    $totalResults = 1;
                    if (isset($response['Items']['TotalResults'])) {
                        $totalResults = (int)$response['Items']['TotalResults'];
                    }

                    if ($singleProductId || $totalResults === 1) {
                        $items = $items ? array($items) : array();
                    }

                    if ($totalResults === 1) {
                        $totalResults = count($items);
                    }

                    $currencyConversionFactor = (float)get_option('aidn_currency_conversion_factor', 1);
                    foreach ($items as $item) {
                        //echo "<pre>";print_r($item);echo "</pre>";

                        $product = $this->parseAmazonItem($item, ['condition' => $condition]);
                        $product->setLinkCategoryId($linkCategoryId);

                        $product->save();

                        $product->loadUserPrice($currencyConversionFactor);
                        $product->loadUserImage();

                        $result["items"][] = $product;
                        //}
                    }
                    $result["total"] = $totalResults > $MAX_RESULT_ITEMS ? $MAX_RESULT_ITEMS : $totalResults;
                } else {
                    $result["error"] = 'There is no product to display!';
                }
            }
        } else {
            $result["error"] = 'Please enter some search keywords or select item from category list!';
        }
        return $result;
    }
}
