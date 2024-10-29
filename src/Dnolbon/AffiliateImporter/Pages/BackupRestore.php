<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;
use Dnolbon\Twig\Twig;

class BackupRestore extends PageAbstract
{
    public function render()
    {
        $this->loadFile();

        $activePage = 'backup';
        Toolbar::parseToolbar($activePage, $this->getType());

        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $template = Twig::getInstance()->getTwig()->load('backuprestore.html');
        echo $template->render(
            [
                'prefix' => $importer->getClassPrefix()
            ]
        );
    }

    public function loadFile()
    {
        foreach ($_FILES as $file) {
            $csv = array_map('str_getcsv', file($file['tmp_name']));
            foreach ($csv as $line) {
                update_option($line[0], $line[1]);
            }
        }
    }
}
