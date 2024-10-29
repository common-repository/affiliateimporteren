<?php
namespace Dnolbon\Wordpress\Db;

class Db
{
    /**
     * @var Db $instance
     */
    protected static $instance;

    /**
     * @return Db
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Db();
        }

        return self::$instance;
    }

    /**
     * @return \wpdb
     */
    public function getDb()
    {
        global $wpdb;
        return $wpdb;
    }
}
