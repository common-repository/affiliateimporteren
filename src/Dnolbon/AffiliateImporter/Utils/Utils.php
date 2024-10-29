<?php
namespace Dnolbon\AffiliateImporter\Utils;

class Utils
{
    public static function removeTags($html, $tags = array())
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_use_internal_errors(false);
        $dom->formatOutput = true;

        if (!$tags) {
            $tags = array('script', 'head', 'meta', 'style', 'map', 'noscript', 'object');

            if (get_option('aidn_remove_img_from_desc', false)) {
                $tags[] = 'img';
            }
            if (get_option('aidn_remove_link_from_desc', false)) {
                $tags[] = 'a';
            }
        }
        foreach ($tags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            for ($i = $elements->length; --$i >= 0;) {
                $e = $elements->item($i);
                if ($tag === 'a') {
                    while ($e->hasChildNodes()) {
                        $child = $e->removeChild($e->firstChild);
                        $e->parentNode->insertBefore($child, $e);
                    }
                    $e->parentNode->removeChild($e);
                } else {
                    $e->parentNode->removeChild($e);
                }
            }
        }

        return preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML());
    }

    public static function isSerialized($str)
    {
        return ($str === serialize(false) || @unserialize($str) !== false);
    }

    public static function fixPrice($price) {
        return ($price !== null && $price) ? sprintf('%01.2f', str_replace(['US $', 'RUB', '$', 'GPB', 'BRL', 'CAD', 'AUD', 'EUR', 'INR', 'UAH', 'JPY', 'MXN', 'IDR', 'TRY', 'SEK', '.00'], '', $price)) : "0.00";
    }
}
