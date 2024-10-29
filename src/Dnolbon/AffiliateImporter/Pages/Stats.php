<?php
namespace Dnolbon\AffiliateImporter\Pages;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Tables\StatsTable;
use Dnolbon\AffiliateImporter\Toolbar\Toolbar;
use Dnolbon\Wordpress\Table\Table;

class Stats extends PageAbstract
{
    /**
     * @var Table $table
     */
    private $table;

    public function render()
    {
        $activePage = 'stats';
        Toolbar::parseToolbar($activePage, $this->getType());

        $this->getTable()->prepareItems();

        $path = AffiliateImporter::getInstance()->getImporter($this->getType())->getMainFilePath();
        include $path . '/layout/stats.php';
    }

    /**
     * @return Table
     */
    public function getTable()
    {
        if ($this->table === null) {
            $this->table = new StatsTable();
            $this->table->setType($this->getType());
        }
        return $this->table;
    }
}
