<?php
namespace Dnolbon\Wordpress\Menu;

class Menu
{
    /**
     * @var string $pageTitle
     */
    private $pageTitle;
    /**
     * @var string $menuTitle
     */
    private $menuTitle;
    /**
     * @var string $capability
     */
    private $capability;
    /**
     * @var string $menuSlug
     */
    private $menuSlug;
    /**
     * @var callable $function
     */
    private $function;
    /**
     * @var string $icon
     */
    private $icon = '';
    /**
     * @var int $position
     */
    private $position;
    /**
     * @var bool $isChild
     */
    private $isChild = false;
    /**
     * @var Menu[] $childs
     */
    private $childs = [];
    /**
     * @var Menu $parent
     */
    private $parent;

    /**
     * WordpressMenu constructor.
     * @param string $pageTitle
     * @param string $capability
     * @param string $menuSlug
     * @param array $params
     */
    public function __construct($pageTitle, $capability, $menuSlug, $params = [])
    {
        $this->pageTitle = $pageTitle;
        $this->menuTitle = $pageTitle;
        $this->capability = $capability;
        $this->menuSlug = $menuSlug;

        $this->function = array_key_exists('function', $params) ? $params['function'] : '';
        if (array_key_exists('icon', $params)) {
            $mainDir = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
            $this->icon = plugin_dir_url($mainDir) . '/assets/img/' . $params['icon'];
        }
        $this->position = array_key_exists('position', $params) ? $params['position'] : null;
    }

    /**
     * @return array
     */
    public function getChilds()
    {
        return $this->childs;
    }

    /**
     * @param array $childs
     */
    public function setChilds($childs)
    {
        $this->childs = $childs;
    }

    public function show()
    {
        if ($this->isChild) {
            add_submenu_page(
                $this->getParent()->getMenuSlug(),
                $this->getParent()->getPageTitle() . ' ' . $this->getPageTitle(),
                $this->getMenuTitle(),
                $this->getCapability(),
                $this->getParent()->getMenuSlug() . '-' . $this->getMenuSlug(),
                $this->getFunction()
            );
        } else {
            add_menu_page(
                $this->getPageTitle(),
                $this->getMenuTitle(),
                $this->getCapability(),
                $this->getMenuSlug(),
                $this->getFunction(),
                $this->getIcon(),
                $this->getPosition()
            );
        }
        foreach ($this->childs as $child) {
            $child->show();
        }
    }

    /**
     * @return string
     */
    public function getMenuSlug()
    {
        return $this->menuSlug;
    }

    /**
     * @param string $menuSlug
     */
    public function setMenuSlug($menuSlug)
    {
        $this->menuSlug = $menuSlug;
    }

    /**
     * @return Menu
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Menu $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
     * @param string $pageTitle
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * @return string
     */
    public function getMenuTitle()
    {
        return $this->menuTitle;
    }

    /**
     * @param string $menuTitle
     */
    public function setMenuTitle($menuTitle)
    {
        $this->menuTitle = $menuTitle;
    }

    /**
     * @return string
     */
    public function getCapability()
    {
        return $this->capability;
    }

    /**
     * @param string $capability
     */
    public function setCapability($capability)
    {
        $this->capability = $capability;
    }

    /**
     * @return callable
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @param callable $function
     */
    public function setFunction($function)
    {
        $this->function = $function;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return boolean
     */
    public function isIsChild()
    {
        return $this->isChild;
    }

    /**
     * @param boolean $isChild
     */
    public function setIsChild($isChild)
    {
        $this->isChild = $isChild;
    }

    /**
     * @param Menu $menu
     */
    public function addChild($menu)
    {
        $menu->setIsChild(true);
        $menu->setParent($this);
        $this->childs[] = $menu;
    }
}
