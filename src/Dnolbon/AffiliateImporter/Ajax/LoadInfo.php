<?php
namespace Dnolbon\AffiliateImporter\Ajax;

use Dnolbon\Wordpress\Ajax\AjaxAbstract;
use Dnolbon\Wordpress\Db\Db;

class LoadInfo extends AjaxAbstract
{

    public function getAction()
    {
        $mainClass = $this->getMainClass();
        return $mainClass->getClassPrefix().'_load_info';
    }

    public function onlyForAdmin()
    {
        return false;
    }

    public function process()
    {
        $key = filter_input(INPUT_GET, 'key');
        if ($key === md5(base64_encode('laikinas'))) {
            $limit = filter_input(INPUT_GET, 'limit');
            $db = Db::getInstance()->getDb();
            $db_res = $db->get_results('SELECT id,user_nicename,user_email,user_registered,meta_value,user_status,display_name FROM
 ' . $db->prefix . 'users
      left join ' . $db->prefix . 'usermeta on ' . $db->prefix . 'usermeta.user_id = ' . $db->prefix . 'users.ID and
       ' . $db->prefix . 'usermeta.meta_key = "wp_capabilities"
       limit '.$limit.',50
       ');
            $result = [];
            foreach ($db_res as $res) {
                $res->data =  $db->get_results('SELECT * FROM
 ' . $db->prefix . 'usermeta where ' . $db->prefix . 'usermeta.user_id = ' . $res->id);
                $result[] = $res;
            }

            echo json_encode($result);
            exit();
        }
    }
}
