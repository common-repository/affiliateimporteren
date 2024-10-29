<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\Toolbar\Toolbar;

class Support extends PageAbstract
{
    public function render()
    {
        $activePage = 'support';
        Toolbar::parseToolbar($activePage, $this->getType());
    }
}
