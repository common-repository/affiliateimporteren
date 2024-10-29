<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Tables\SheduleTable;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;
use Dnolbon\Wordpress\Table\Table;

class Shedule extends PageAbstract
{
    /**
     * @var Table $table
     */
    private $table;

    public function render()
    {
        $activePage = 'schedule';
        Toolbar::parseToolbar($activePage, $this->getType());

        $this->getTable()->prepareItems();

        $path = AffiliateImporter::getInstance()->getImporter($this->getType())->getMainFilePath();
        include $path . '/layout/shedule.php';
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        if ($this->table === null) {
            $this->table = new SheduleTable();
            $this->table->setType($this->getType());
        }
        return $this->table;
    }
}
