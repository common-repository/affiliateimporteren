<?php
namespace Dnolbon\AffiliateImporterEnvato;

use Dnolbon\AffiliateImporter\Account\AccountAbstract;

class AffiliateImporterEnvatoAccount extends AccountAbstract
{
    /**
     * @return array
     */
    public function getForm()
    {
        return [
            'title' => 'Envato user name',
            'fields' => [
                [
                    'field' => 'userName',
                    'value' => $this->getAccountDataKeyValue('userName'),
                    'title' => 'USER NAME',
                    'type' => ''
                ],
                [
                    'field' => 'secretKey',
                    'value' => $this->getAccountDataKeyValue('secretKey'),
                    'title' => 'Secret key',
                    'type' => ''
                ]
            ]
        ];
    }


    protected function getName()
    {
        return 'EnvatoAccount';
    }
}
