<?php
namespace Dnolbon\Wordpress\Menu;

class MenuFactory
{
    /**
     * @param string $pageTitle
     * @param string $capability
     * @param string $menuSlug
     * @param array $params
     * @return Menu
     */
    public static function addMenu($pageTitle, $capability, $menuSlug, $params = [])
    {
        return new Menu(
            $pageTitle,
            $capability,
            $menuSlug,
            $params
        );
    }
}
