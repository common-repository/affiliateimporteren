<?php
namespace Dnolbon\Wordpress\Shortcode;

interface ShortcodeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param array $params
     * @return mixed
     */
    public function render($params);
}
