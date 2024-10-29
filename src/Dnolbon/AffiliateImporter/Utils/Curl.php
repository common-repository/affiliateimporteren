<?php
namespace Dnolbon\AffiliateImporter\Utils;

class Curl
{
    public static function get($url, $args = [])
    {
        $defArgs = [
            'headers' => ['Accept-Encoding' => ''],
            'timeout' => 30,
            'user-agent' => 'Toolkit/1.7.3',
            'sslverify' => false
        ];

        if (!is_array($args)) {
            $args = [];
        }

        foreach ($defArgs as $key => $val) {
            if (!isset($args[$key])) {
                $args[$key] = $val;
            }
        }

        return wp_remote_get($url, $args);
    }
}
