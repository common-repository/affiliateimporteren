<?php
namespace Dnolbon\AffiliateImporter\Account;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;

abstract class AccountAbstract
{
    private $accountData;

    private $id = 0;

    private $type;

    /**
     * AccountAbstract constructor.
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->getAccountData();
    }

    /**
     * @return mixed
     */
    public function getAccountData()
    {
        if ($this->accountData === null) {
            $sql = 'SELECT * FROM ' . $this->getImporter()->getTableName('account');
            $sql .= " WHERE name='" . $this->getName() . "'";
            $result = Db::getInstance()->getDb()->get_row($sql);
            if ($result) {
                $this->accountData = unserialize($result->data);
                $this->id = (int)$result->id;
            } else {
                $this->accountData = [];
            }
        }
        return $this->accountData;
    }

    protected function getImporter()
    {
        return AffiliateImporter::getInstance()->getImporter($this->getType());
    }

    /**
     * @return mixed
     */
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

    abstract protected function getName();

    abstract public function getForm();

    public function getAccountDataKeyValue($key)
    {
        if (array_key_exists($key, $this->getAccountData())) {
            return $this->getAccountData()[$key];
        }
        return '';
    }

    public function setAccountDataKeyValue($key, $value)
    {
        $this->accountData[$key] = $value;
    }

    public function saveAccountData()
    {
        if ($this->id > 0) {
            Db::getInstance()->getDb()->update(
                $this->getImporter()->getTableName('account'),
                [
                    'data' => serialize($this->accountData)
                ],
                [
                    'id' => $this->id
                ]
            );
        } else {
            Db::getInstance()->getDb()->insert(
                $this->getImporter()->getTableName('account'),
                [
                    'data' => serialize($this->accountData),
                    'name' => $this->getName()
                ]
            );
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
