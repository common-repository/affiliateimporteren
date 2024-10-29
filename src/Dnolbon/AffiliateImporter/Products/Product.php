<?php
namespace Dnolbon\AffiliateImporter\Products;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Prices\PriceFormula;
use Dnolbon\AffiliateImporter\Prices\PriceFormulas;
use Dnolbon\AffiliateImporter\Utils\Utils;
use Dnolbon\Wordpress\Db\Db;

class Product
{
    /**
     * @var string $type
     */
    private $type;
    /**
     * @var string $id
     */
    private $externalId;
    /**
     * @var string $variationId
     */
    private $variationId;
    /**
     * @var string $image
     */
    private $image;
    /**
     * @var string $detailUrl
     */
    private $detailUrl;
    /**
     * @var string $sellerUrl
     */
    private $sellerUrl;
    /**
     * @var string $photos
     */
    private $photos;
    /**
     * @var string $title
     */
    private $title;
    /**
     * @var string $subtitle
     */
    private $subtitle;
    /**
     * @var string $description
     */
    private $description;
    /**
     * @var string $keywords
     */
    private $keywords;
    /**
     * @var string $price
     */
    private $price;
    /**
     * @var string $regularPrice
     */
    private $regularPrice;
    /**
     * @var string $currency
     */
    private $currency;
    /**
     * @var int $categoryId
     */
    private $categoryId;
    /**
     * @var string $categoryName
     */
    private $categoryName;
    /**
     * @var int $linkCategoryId
     */
    private $linkCategoryId;
    /**
     * @var string $additionalMeta
     */
    private $additionalMeta;
    /**
     * @var string $userImage
     */
    private $userImage;
    /**
     * @var string $userPhotos
     */
    private $userPhotos;
    /**
     * @var string $userTitle
     */
    private $userTitle;
    /**
     * @var string $userSubTitle
     */
    private $userSubTitle;
    /**
     * @var string $userDescription
     */
    private $userDescription;
    /**
     * @var string $userKeywords
     */
    private $userKeywords;
    /**
     * @var string $userPrice
     */
    private $userPrice;
    /**
     * @var string $userRegularPrice
     */
    private $userRegularPrice;
    /**
     * @var string $userScheduleTime
     */
    private $userScheduleTime;

    /**
     * @var bool $available
     */
    private $available = true;

    /**
     * @var bool $isNew
     */
    private $isNew = true;

    /**
     * Product constructor.
     * @param string $externalId
     * @param string $type
     * @param string $variationId
     */
    public function __construct($externalId, $type, $variationId)
    {
        $this->externalId = $externalId;
        $this->type = $type;
        $this->variationId = $variationId;
    }

    public function loadLocalData()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $sql = "SELECT * FROM " . $importer->getTableName('goods');
        $sql .= " WHERE type=%s and external_id=%s and variation_id=%s";

        $dbResult = Db::getInstance()->getDb()->get_row(
            Db::getInstance()->getDb()->prepare(
                $sql,
                $this->getType(),
                $this->getExternalId(),
                $this->getVariationId()
            )
        );

        if ($dbResult) {
            foreach ($dbResult as $key => $value) {
                $this->setField($key, $value);
            }
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    }

    /**
     * @return string
     */
    public function getVariationId()
    {
        return $this->variationId;
    }

    /**
     * @param string $variationId
     */
    public function setVariationId($variationId)
    {
        $this->variationId = $variationId;
    }

    public function setField($field, $value)
    {
        $fixedKey = '';
        $fieldArray = explode('_', $field);
        foreach ($fieldArray as $key) {
            $fixedKey .= ucfirst($key);
        }

        if (Utils::isSerialized($value)) {
            $value = unserialize($value);
        }
        $method = 'set' . $fixedKey;
        $this->$method($value);
    }

    /**
     * @param string $currency
     */
    public function setCurr($currency)
    {
        $this->currency = $currency;
    }

    public function toArray($fields)
    {
        $output = [];
        $output['type'] = $this->getType();
        $output['id'] = $this->getExternalId();
        $output['variation_id'] = $this->getVariationId();

        foreach ($fields as $field) {
            $output[$field] = $this->getField($field);
        }
        return $output;
    }

    public function getField($field)
    {
        $fixedKey = '';
        $fieldArray = explode('_', $field);
        foreach ($fieldArray as $key) {
            $fixedKey .= ucfirst($key);
        }

        $method = 'get' . $fixedKey;
        return $this->$method();
    }

    /**
     * @return mixed
     */
    public function getIsNew()
    {
        return $this->isNew;
    }

    /**
     * @param mixed $isNew
     */
    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
    }

    public function isNeedLoad()
    {
        foreach (get_object_vars($this) as $val) {
            if (!is_array($val) && (string)$val === '#needload#') {
                return true;
            }
        }
        return false;
    }

    public function getPostId()
    {
        $db = Db::getInstance()->getDb();
        $sql = 'SELECT post_id FROM ' . $db->postmeta . ' WHERE meta_key=\'external_id\' AND meta_value=\'%s\' LIMIT 1';
        $postId = $db->get_var($db->prepare($sql, $this->getFullId()));
        return $postId;
    }

    public function getFullId($delimeter = '#')
    {
        $fullId = $this->getType() . $delimeter . $this->getExternalId();
        $variationId = $this->getVariationId();
        if ($variationId && $variationId !== '-') {
            $fullId .= $delimeter . $variationId;
        }
        return $fullId;
    }

    public function getCategoryLink()
    {
        return $this->getLinkCategoryId() ? (int)$this->getLinkCategoryId() : $this->getCleanField('category_name');
    }

    /**
     * @return int
     */
    public function getLinkCategoryId()
    {
        return $this->linkCategoryId;
    }

    /**
     * @param int $linkCategoryId
     */
    public function setLinkCategoryId($linkCategoryId)
    {
        $this->linkCategoryId = $linkCategoryId;
    }

    public function getCleanField($field)
    {
        $result = $this->getField($field);
        if ($result && !is_array($result)) {
            $result = str_replace(['#empty#', '#notuse#', '#needload#'], '', $result);
        }
        return $result;
    }

    public function getCleanAditionalField($field)
    {
        $additional = $this->getAdditionalMeta();
        if (Utils::isSerialized($additional)) {
            $additional = unserialize($additional);
        }

        $result = isset($additional[$field]) ? $additional[$field] : '';
        if ($result && !is_array($result)) {
            $result = str_replace(['#empty#', '#notuse#', '#needload#'], '', $result);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getAdditionalMeta()
    {
        return $this->additionalMeta;
    }

    /**
     * @param string $additionalMeta
     */
    public function setAdditionalMeta($additionalMeta)
    {
        $this->additionalMeta = $additionalMeta;
    }

    public function getAllPhotos()
    {
        $photos = array($this->getImage());
        if ($this->getPhotos() && $this->getPhotos() !== "#needload#") {
            $photos = array_merge($photos, explode(",", $this->getPhotos()));
        }
        if ($this->getUserPhotos()) {
            $photos = array_merge($photos, explode(",", $this->getUserPhotos()));
        }
        $photos = array_unique($photos);

        return $photos;
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * @param string $photos
     */
    public function setPhotos($photos)
    {
        $this->photos = $photos;
    }

    /**
     * @return string
     */
    public function getUserPhotos()
    {
        return $this->userPhotos;
    }

    /**
     * @param string $userPhotos
     */
    public function setUserPhotos($userPhotos)
    {
        $this->userPhotos = $userPhotos;
    }

    /**
     * @param bool $single
     * @return PriceFormula[]
     */
    public function getFormulas($single = true)
    {
        $result = [];
        $formulas = PriceFormulas::getList($this->getType());

        foreach ($formulas as $formula) {
            $check = true;

            if ($formula->getMinPrice() && (float)$formula->getMinPrice() >= (float)$this->getUserPrice()) {
                $check = false;
            }

            if ($formula->getMaxPrice() && (float)$formula->getMaxPrice() <= (float)$this->getUserPrice()) {
                $check = false;
            }

            if ($formula->getCategory() && (int)$formula->getCategory() !== (int)$this->getLinkCategoryId()) {
                $check = false;
            }

            if ($check) {
                $result[] = $formula;

                if ($single) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getUserPrice()
    {
        return $this->userPrice;
    }

    /**
     * @param string $userPrice
     */
    public function setUserPrice($userPrice)
    {
        $this->userPrice = $userPrice;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @param bool $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    public function loadUserImage()
    {
        if (trim((string)$this->getUserImage()) === '') {
            $this->setUserImage($this->getImage());
            $this->save();
        }
    }

    /**
     * @return string
     */
    public function getUserImage()
    {
        return $this->userImage;
    }

    /**
     * @param string $userImage
     */
    public function setUserImage($userImage)
    {
        $this->userImage = $userImage;
    }

    public function save()
    {
        $userDescription = trim($this->getUserDescription()) === '' ? '#empty#' : trim($this->getUserDescription());
        $userRegularPrice = trim($this->getUserRegularPrice()) === '' ? '#empty#' : trim($this->getUserRegularPrice());

        $data = [
            'type' => $this->getType(),
            'external_id' => $this->getExternalId(),
            'variation_id' => $this->getVariationId(),
            'image' => $this->getImage(),
            'detail_url' => $this->getDetailUrl(),
            'seller_url' => $this->getSellerUrl(),
            'photos' => $this->getPhotos(),
            'title' => $this->getTitle(),
            'subtitle' => $this->getSubtitle(),
            'description' => $this->getDescription(),
            'keywords' => $this->getKeywords(),
            'regular_price' => $this->getRegularPrice(),
            'price' => $this->getPrice(),
            'curr' => $this->getCurr(),
            'category_id' => $this->getCategoryId(),
            'category_name' => $this->getCategoryName(),
            'link_category_id' => $this->getLinkCategoryId(),
            'additional_meta' => serialize($this->getAdditionalMeta()),
            'user_image' => trim($this->getUserImage()) === '' ? '#empty#' : trim($this->getUserImage()),
            'user_photos' => trim($this->getUserPhotos()) === '' ? '#empty#' : trim($this->getUserPhotos()),
            'user_title' => trim($this->getUserTitle()) === '' ? '#empty#' : trim($this->getUserTitle()),
            'user_subtitle' => trim($this->getUserSubTitle()) === '' ? '#empty#' : trim($this->getUserSubTitle()),
            'user_description' => $userDescription,
            'user_keywords' => trim($this->getUserKeywords()) === '' ? '#empty#' : trim($this->getUserKeywords()),
            'user_schedule_time' => trim($this->getUserScheduleTime()),
            'user_price' => $this->getUserPrice(),
            'user_regular_price' => $userRegularPrice
        ];

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        if ($this->isNew) {
            Db::getInstance()->getDb()->insert(
                $importer->getTableName('goods'),
                $data
            );
            $this->isNew = false;
        } else {
            Db::getInstance()->getDb()->update(
                $importer->getTableName('goods'),
                $data,
                [
                    'type' => $this->getType(),
                    'external_id' => $this->getExternalId(),
                    'variation_id' => $this->getVariationId()
                ]
            );
        }
    }

    /**
     * @return string
     */
    public function getUserDescription()
    {
        return $this->userDescription;
    }

    /**
     * @param string $userDescription
     */
    public function setUserDescription($userDescription)
    {
        $this->userDescription = $userDescription;
    }

    /**
     * @return string
     */
    public function getUserRegularPrice()
    {
        return $this->userRegularPrice;
    }

    /**
     * @param string $userRegularPrice
     */
    public function setUserRegularPrice($userRegularPrice)
    {
        $this->userRegularPrice = $userRegularPrice;
    }

    /**
     * @return string
     */
    public function getDetailUrl()
    {
        return $this->detailUrl;
    }

    /**
     * @param string $detailUrl
     */
    public function setDetailUrl($detailUrl)
    {
        $this->detailUrl = $detailUrl;
    }

    /**
     * @return string
     */
    public function getSellerUrl()
    {
        return $this->sellerUrl;
    }

    /**
     * @param string $sellerUrl
     */
    public function setSellerUrl($sellerUrl)
    {
        $this->sellerUrl = $sellerUrl;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getRegularPrice()
    {
        return $this->regularPrice;
    }

    /**
     * @param string $regularPrice
     */
    public function setRegularPrice($regularPrice)
    {
        $this->regularPrice = $regularPrice;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getCurr()
    {
        return $this->currency;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @param string $categoryName
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * @return string
     */
    public function getUserTitle()
    {
        return $this->userTitle;
    }

    /**
     * @param string $userTitle
     */
    public function setUserTitle($userTitle)
    {
        $this->userTitle = $userTitle;
    }

    /**
     * @return string
     */
    public function getUserSubTitle()
    {
        return $this->userSubTitle;
    }

    /**
     * @param string $userSubTitle
     */
    public function setUserSubTitle($userSubTitle)
    {
        $this->userSubTitle = $userSubTitle;
    }

    /**
     * @return string
     */
    public function getUserKeywords()
    {
        return $this->userKeywords;
    }

    /**
     * @param string $userKeywords
     */
    public function setUserKeywords($userKeywords)
    {
        $this->userKeywords = $userKeywords;
    }

    /**
     * @return string
     */
    public function getUserScheduleTime()
    {
        return $this->userScheduleTime;
    }

    /**
     * @param string $userScheduleTime
     */
    public function setUserScheduleTime($userScheduleTime)
    {
        $this->userScheduleTime = $userScheduleTime;
    }

    public function loadUserPrice($currencyConversionFactor)
    {
        if (trim((string)$this->getUserPrice()) === '') {
            $this->setUserPrice(round($this->getPrice() * $currencyConversionFactor, 2));
            $this->setUserRegularPrice(
                round($this->getRegularPrice() * $currencyConversionFactor, 2)
            );
            $this->save();
        }
    }
}
