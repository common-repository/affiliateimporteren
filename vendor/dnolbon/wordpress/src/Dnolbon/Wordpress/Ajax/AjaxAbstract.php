<?php
namespace Dnolbon\Wordpress\Ajax;

use Dnolbon\Wordpress\MainClassAbstract;

abstract class AjaxAbstract
{
    /**
     * @var MainClassAbstract $mainClass
     */
    private $mainClass;

    public function __construct($mainClass)
    {
        $this->setMainClass($mainClass);

        add_action('wp_ajax_' . $this->getAction(), [$this, 'process']);
        if ($this->onlyForAdmin() === true) {
            add_action('wp_ajax_nopriv_' . $this->getAction(), [$this, 'process']);
        }
    }

    abstract public function getAction();

    abstract public function onlyForAdmin();

    abstract public function process();

    /**
     * @return MainClassAbstract
     */
    public function getMainClass()
    {
        return $this->mainClass;
    }

    /**
     * @param MainClassAbstract $mainClass
     */
    public function setMainClass($mainClass)
    {
        $this->mainClass = $mainClass;
    }
}
