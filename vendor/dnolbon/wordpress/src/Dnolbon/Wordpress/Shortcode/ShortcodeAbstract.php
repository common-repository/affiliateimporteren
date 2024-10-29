<?php
namespace Dnolbon\Wordpress\Shortcode;

abstract class ShortcodeAbstract implements ShortcodeInterface
{
    public function __construct()
    {
        add_shortcode($this->getName(), [$this, 'render']);
    }
}
