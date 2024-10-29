<?php
namespace Dnolbon\AffiliateImporter\Loader;

use Dnolbon\AffiliateImporter\Account\AccountAbstract;
use Dnolbon\AffiliateImporter\Account\AccountFactory;
use Dnolbon\AffiliateImporter\Products\Product;

abstract class LoaderAbstract
{
    /**
     * @var Product $product
     */
    private $product;

    private $type;

    /**
     * @var AccountAbstract $account
     */
    private $account;

    /**
     * LoaderAbstract constructor.
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    public function loadDetail($product, $params = [])
    {
        $this->setProduct($product);

        $result = $this->loadDetailRemote();

        return $result;
    }

    /**
     * @return mixed
     */
    abstract protected function loadDetailRemote();

    public function loadList($filter, $page = 1)
    {
        $result = $this->loadListRemote($filter, $page);
        /**
         * @var Product $product
         */
        foreach ($result['items'] as $key => $product) {
            // update user price by formula
            $formulas = $product->getFormulas();
            if ($formulas) {
                $product->setUserPrice(
                    sprintf(
                        '%01.2f',
                        $formulas[0]->apply($product->getUserPrice())
                    )
                );
                $formulas[0]->calcRegularPrice($product);
                $product->setUserRegularPrice(sprintf('%01.2f', $product->getRegularPrice()));
                $product->save();
            }
        }

        return $result;
    }

    /**
     * @param $filter
     * @param int $page
     * @return mixed
     */
    abstract protected function loadListRemote($filter, $page = 1);

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct($product)
    {
        $this->product =& $product;
    }

    /**
     * @return AccountAbstract
     */
    public function getAccount()
    {
        if ($this->account === null) {
            $this->account = AccountFactory::getAccount($this->getType());
        }
        return $this->account;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
