<?php
namespace Dnolbon\Wordpress;

interface MainClassInterface
{
    /**
     * Check version for database upgrade
     */
    public function checkVersion();

    /**
     * Database install or upgrade script
     */
    public function install();

    /**
     * Database uninstall script
     */
    public function uninstall();

    /**
     * Admin menu registration
     */
    public function registerMenu();

    public function getClassPrefix();

    public static function getClassName();
}
