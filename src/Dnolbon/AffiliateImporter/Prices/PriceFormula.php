<?php
namespace Dnolbon\AffiliateImporter\Prices;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Products\Product;
use Dnolbon\Wordpress\Db\Db;

class PriceFormula
{
    private $id = 0;
    private $type = "";
    private $pos = 0;
    private $category = 0;
    private $categoryName = "";
    private $minPrice = 0;
    private $maxPrice = 0;
    private $sign = "=";
    private $value = 1;
    private $discount1 = "";
    private $discount2 = "";
    private $typeName;

    public function apply($price)
    {
        $result = $price;
        if ($this->getSign() === '=') {
            $result = $this->getValue();
        } elseif ($this->getSign() === '*') {
            $result *= $this->getValue();
        } elseif ($this->getSign() === '+') {
            $result += $this->getValue();
        }
        return round($result, 2);
    }

    /**
     * @return string
     */
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * @param string $sign
     */
    public function setSign($sign)
    {
        $this->sign = $sign;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function delete()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $db = Db::getInstance()->getDb();
        $db->delete(
            $importer->getTableName('price_formula'),
            ['id' => $this->getId()]
        );
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function save()
    {
        $db = Db::getInstance()->getDb();

        $dataToSet = [
            'pos' => $this->getPos(),
            'formula' => serialize(
                [
                    'type' => $this->getType(),
                    'category' => $this->getCategory(),
                    'category_name' => $this->getCategoryName(),
                    'min_price' => $this->getMinPrice(),
                    'max_price' => $this->getMaxPrice(),
                    'sign' => $this->getSign(),
                    'value' => $this->getValue(),
                    'discount1' => $this->getDiscount1(),
                    'discount2' => $this->getDiscount2(),
                    'type_name' => $this->getTypeName()
                ]
            )
        ];

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        if ($this->getId() > 0) {
            $db->update(
                $importer->getTableName('price_formula'),
                $dataToSet,
                ['id' => $this->getId()]
            );
        } else {
            $db->insert(
                $importer->getTableName('price_formula'),
                $dataToSet
            );
            $this->id = $db->insert_id;
        }
    }

    /**
     * @return int
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * @param int $pos
     */
    public function setPos($pos)
    {
        $this->pos = $pos;
    }

    /**
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param int $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
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
     * @return int
     */
    public function getMinPrice()
    {
        return $this->minPrice;
    }

    /**
     * @param int $minPrice
     */
    public function setMinPrice($minPrice)
    {
        $this->minPrice = $minPrice;
    }

    /**
     * @return int
     */
    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    /**
     * @param int $maxPrice
     */
    public function setMaxPrice($maxPrice)
    {
        $this->maxPrice = $maxPrice;
    }

    /**
     * @return string
     */
    public function getDiscount1()
    {
        return $this->discount1;
    }

    /**
     * @param string $discount1
     */
    public function setDiscount1($discount1)
    {
        $this->discount1 = $discount1;
    }

    /**
     * @return string
     */
    public function getDiscount2()
    {
        return $this->discount2;
    }

    /**
     * @param string $discount2
     */
    public function setDiscount2($discount2)
    {
        $this->discount2 = $discount2;
    }

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param mixed $typeName
     */
    public function setTypeName($typeName)
    {
        $this->typeName = $typeName;
    }

    public function toArray()
    {
        return [
            'pos' => $this->getPos(),
            'type' => $this->getType(),
            'category' => $this->getCategory(),
            'category_name' => $this->getCategoryName(),
            'min_price' => $this->getMinPrice(),
            'max_price' => $this->getMaxPrice(),
            'sign' => $this->getSign(),
            'value' => $this->getValue(),
            'discount1' => $this->getDiscount1(),
            'discount2' => $this->getDiscount2(),
            'type_name' => $this->getTypeName(),
            'id' => $this->getId()

        ];
    }

    public function isDiscount1and2()
    {
        return (strlen(trim((string)$this->getDiscount1())) > 0 &&
            strlen(trim((string)$this->getDiscount2())) > 0);
    }

    /**
     * @param Product $product
     * @return mixed
     */
    public function calcRegularPrice(&$product)
    {
        $discount = 0;

        $discountPerc = $product->getCleanAditionalField('discount_perc');
        if ($discountPerc || strlen(trim((string)$discountPerc)) > 0) {
            $discount = (int)$discountPerc;
        } else {
            $additionalMeta = unserialize($product->getAdditionalMeta());

            if (isset($additionalMeta['original_discount']) &&
                strlen(trim((string)$additionalMeta['original_discount'])) > 0
            ) {
                $discount = (int)$additionalMeta['original_discount'];
            }
            if (strlen(trim((string)$this->discount1)) > 0 && strlen(trim((string)$this->discount2)) > 0) {
                if ((int)$this->discount1 > (int)$this->discount2) {
                    $discount = mt_rand((int)$this->discount2, (int)$this->discount1);
                } else {
                    $discount = mt_rand((int)$this->discount1, (int)$this->discount2);
                }
            } elseif (trim((string)$this->discount1) !== '' || trim((string)$this->discount2) !== '') {
                $discount = strlen(trim((string)$this->discount1)) > 0 ? (int)$this->discount1 : (int)$this->discount2;
            }
        }

        $product->setUserRegularPrice(
            round(($product->getUserPrice() * 100) / (100 - $discount), 2)
        );

        return $product;
    }
}
