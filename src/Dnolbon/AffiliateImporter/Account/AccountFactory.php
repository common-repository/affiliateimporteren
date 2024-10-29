<?php
namespace Dnolbon\AffiliateImporter\Account;

class AccountFactory
{
    /**
     * @param string $type
     * @return AccountAbstract
     */
    public static function getAccount($type)
    {
        $className = '\Dnolbon\AffiliateImporter' . ucfirst($type) . '\AffiliateImporter' . ucfirst($type) . 'Account';
        return new $className($type);
    }
}
