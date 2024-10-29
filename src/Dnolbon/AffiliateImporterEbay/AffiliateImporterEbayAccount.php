<?php
namespace Dnolbon\AffiliateImporterEbay;

use Dnolbon\AffiliateImporter\Account\AccountAbstract;

class AffiliateImporterEbayAccount extends AccountAbstract
{
    /**
     * @return array
     */
    public function getForm()
    {
        return [
            'title' => 'Ebay account setting',
            'fields' => [
                [
                    "field" => "devID",
                    "value" => $this->getAccountDataKeyValue('devID'),
                    "title" => "DevID",
                    "type" => ""
                ],
                [
                    "field" => "appID",
                    "value" => $this->getAccountDataKeyValue('appID'),
                    "title" => "AppID",
                    "type" => ""
                ],
                [
                    "field" => "certID",
                    "value" => $this->getAccountDataKeyValue('certID'),
                    "title" => "CertID",
                    "type" => ""
                ],
                [
                    "field" => "userID",
                    "value" => $this->getAccountDataKeyValue('userID'),
                    "title" => "UserID",
                    "type" => ""
                ],
                [
                    "field" => "requestToken",
                    "value" => $this->getAccountDataKeyValue('requestToken'),
                    "title" => "RequestToken",
                    "type" => ""
                ]
            ]
        ];
    }


    protected function getName()
    {
        return 'EBDN_AliexpressAccount';
    }
}
