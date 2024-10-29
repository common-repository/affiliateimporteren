<?php
namespace Dnolbon\AffiliateImporterBanggood;

use Dnolbon\AffiliateImporter\Account\AccountAbstract;

class AffiliateImporterBanggoodAccount extends AccountAbstract
{
    /**
     * @return array
     */
    public function getForm()
    {
        return [
            'title' => 'BangGood account setting',
            'fields' => [
                [
                    'field' => 'referalKey',
                    'value' => $this->getAccountDataKeyValue('referalKey'),
                    'title' => 'REFEFAL KEY',
                    'type' => ''
                ],
                [
                    'field' => 'Appid',
                    'value' => $this->getAccountDataKeyValue('Appid'),
                    'title' => 'Appid',
                    'type' => ''
                ],
                [
                    'field' => 'AppSecret',
                    'value' => $this->getAccountDataKeyValue('AppSecret'),
                    'title' => 'AppSecret',
                    'type' => ''
                ]
            ]
        ];
    }


    protected function getName()
    {
        return 'BanggoodAccount';
    }
}
