<?php
namespace Dnolbon\Wordpress;

abstract class MainClassAbstract implements MainClassInterface
{
    protected $mainFile;

    protected $mainFilePath;

    protected $pluginName;

    public function __construct($mainFile, $pluginName)
    {
        $this->mainFile = $mainFile;
        $this->pluginName = $pluginName;

        if (is_admin()) {
            $this->actionsForAdmin();
        } else {
            $this->actionsForUser();
        }
        $this->actionsForAll();

        register_activation_hook($this->mainFile, [$this, 'activationHook']);
        register_deactivation_hook($this->mainFile, [$this, 'deactivationHook']);

        add_action('admin_init', [$this, 'onAdminInit']);
        add_action('admin_enqueue_scripts', [$this, 'registerAssets']);
        add_filter('plugin_action_links_' . $this->getPluginName(), [$this, 'registerActionLinks']);

        add_action('admin_menu', [$this, 'registerMenu']);
    }

    abstract protected function actionsForAdmin();

    abstract protected function actionsForUser();

    abstract protected function actionsForAll();

    abstract public function activationHook();

    abstract public function deactivationHook();

    abstract public function onAdminInit();

    abstract public function registerAssets();

    abstract public function registerActionLinks($links);

    /**
     * @return mixed
     */
    public function getMainFile()
    {
        return $this->mainFile;
    }

    public function getMainFilePath()
    {
        if ($this->mainFilePath === null) {
            $this->mainFilePath = plugin_dir_path($this->mainFile);
        }
        return $this->mainFilePath;
    }

    /**
     * @return mixed
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * @param mixed $pluginName
     */
    public function setPluginName($pluginName)
    {
        $this->pluginName = $pluginName;
    }

}
