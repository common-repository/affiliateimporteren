<?php
namespace Dnolbon\AffiliateImporterAmazon;

use Dnolbon\AffiliateImporter\Account\AccountAbstract;

class AffiliateImporterAmazonAccount extends AccountAbstract
{
    /**
     * @return array
     */
    public function getForm()
    {
        return [
            'title' => 'Amazon account setting',
            'fields' => [
                [
                    "field" => "access_key_id",
                    "value" => $this->getAccountDataKeyValue('access_key_id'),
                    "title" => "Access Key Id",
                    "type" => ''
                ],
                [
                    'field' => 'secret_access_key',
                    'value' => $this->getAccountDataKeyValue('secret_access_key'),
                    'title' => 'Secret Access Key',
                    'type' => ''
                ],
                [
                    'field' => 'associate_tag',
                    'value' => $this->getAccountDataKeyValue('associate_tag'),
                    'title' => 'Associate Tag',
                    'type' => ''
                ]
            ]
        ];
    }


    protected function getName()
    {
        return 'AIDN_AliexpressAccount';
    }
}
