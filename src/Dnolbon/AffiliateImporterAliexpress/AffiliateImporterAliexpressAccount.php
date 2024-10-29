<?php
namespace Dnolbon\AffiliateImporterAliexpress;

use Dnolbon\AffiliateImporter\Account\AccountAbstract;

class AffiliateImporterAliexpressAccount extends AccountAbstract
{
    /**
     * @return array
     */
    public function getForm()
    {
        return [
            'title' => 'Aliexpress account setting',
            'fields' => [
                [
                    'field' => 'appKey',
                    'value' => $this->getAccountDataKeyValue('appKey'),
                    'title' => 'API KEY',
                    'type' => ''
                ],
                [
                    'field' => 'trackingId',
                    'value' => $this->getAccountDataKeyValue('trackingId'),
                    'title' => 'Tracking Id',
                    'type' => ''
                ]
            ]
        ];
    }


    protected function getName()
    {
        return 'AEIDN_AliexpressAccount';
    }
}
