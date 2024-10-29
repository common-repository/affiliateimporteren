<?php
namespace Dnolbon\Twig;

use Twig_Environment;
use Twig_Loader_Filesystem;

class Twig
{
    /**
     * @var Twig $instance
     */
    protected static $instance;

    /**
     * @var Twig_Loader_Filesystem $loader
     */
    protected $loader;

    /**
     * @var string $templatePath
     */
    protected $templatePath;

    /**
     * @var Twig_Environment $twig
     */
    protected $twig;

    /**
     * @return Twig
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Twig();
        }

        return self::$instance;
    }

    /**
     * @return Twig_Environment
     */
    public function getTwig()
    {
        if ($this->twig === null) {
            $this->twig = new Twig_Environment($this->getLoader());
        }
        return $this->twig;
    }

    /**
     * @return Twig_Loader_Filesystem
     */
    public function getLoader()
    {
        if ($this->loader === null) {
            $this->loader = new Twig_Loader_Filesystem($this->getTemplatePath());
        }
        return $this->loader;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getTemplatePath()
    {
        if ($this->templatePath === null) {
            throw new \Exception('Templates path not defined');
        }
        return $this->templatePath;
    }

    /**
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath)
    {
        $this->templatePath = $templatePath;
    }
}
