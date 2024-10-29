<?php
namespace Dnolbon\AffiliateImporterEbay;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Wordpress\Db\Db;

class AffiliateImporterEbaySite
{
    public $id = 0;
    public $language = "";
    public $country = "";
    public $siteid = "";
    public $sitecode = "";
    public $sitename = "";

    public function save()
    {
        $data = [
            'language' => $this->getLanguage(),
            'country' => $this->getCountry(),
            'siteid' => $this->getSiteid(),
            'sitecode' => $this->getSitecode(),
            'sitename' => $this->getSitename()
        ];

        $db = Db::getInstance()->getDb();

        $tableName = AffiliateImporter::getInstance()->getImporter('ebay')->getTableName('sites');

        if ($this->getId() > 0) {
            $db->update(
                $tableName,
                $data,
                ['id' => $this->getId()]
            );
        } else {
            $db->insert(
                $tableName,
                $data
            );
            $this->setId($db->insert_id);
        }
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getSiteid()
    {
        return $this->siteid;
    }

    /**
     * @param string $siteid
     */
    public function setSiteid($siteid)
    {
        $this->siteid = $siteid;
    }

    /**
     * @return string
     */
    public function getSitecode()
    {
        return $this->sitecode;
    }

    /**
     * @param string $sitecode
     */
    public function setSitecode($sitecode)
    {
        $this->sitecode = $sitecode;
    }

    /**
     * @return string
     */
    public function getSitename()
    {
        return $this->sitename;
    }

    /**
     * @param string $sitename
     */
    public function setSitename($sitename)
    {
        $this->sitename = $sitename;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
