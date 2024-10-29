<?php
namespace Dnolbon\WooCommerce\Products;

use Dnolbon\Wordpress\MainClassAbstract;

abstract class ProductsListAbstract
{
    protected $wpListTable;
    /**
     * @var MainClassAbstract $mainClass
     */
    private $mainClass;

    /**
     * @var ProductsListActionAbstract[] listActions
     */
    private $listActions = [];

    public function __construct($mainClass)
    {
        $this->setMainClass($mainClass);

        add_action('admin_footer-edit.php', [$this, 'loadScripts']);
        add_action('load-edit.php', [$this, 'processActions']);
        add_action('admin_notices', [$this, 'showNotices']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

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

    abstract public function assets();

    /**
     * @param ProductsListActionAbstract $action
     */
    public function addAction($action)
    {
        $this->listActions[] = $action;
    }

    public function showNotices()
    {
        global $post_type, $pagenow;

        if ($pagenow === 'edit.php' && $post_type === 'product') {
            foreach ($this->listActions as $listAction) {
                $listAction->showNotice();
            }
        }
    }

    public function loadScripts()
    {
        if ($this->getPostType() === 'product') {
            foreach ($this->listActions as $listAction) {
                $text = $listAction->getText();
                $action = $listAction->getAction();
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function () {
                        jQuery('<option>').val('<?php echo $action;?>').text('<?php _e($text)?>').appendTo("select[name='action']");
                        jQuery('<option>').val('<?php echo $action;?>').text('<?php _e($text)?>').appendTo("select[name='action2']");
                    });
                </script>
                <?php
            }
        }
    }

    protected function getPostType()
    {
        global $typenow, $post_type;
        if ($typenow !== null) {
            return $typenow;
        }

        if ($post_type !== null) {
            return $post_type;
        }
        return '';
    }

    public function processActions()
    {
        if ($this->getPostType() === 'product') {
            foreach ($this->listActions as $listAction) {
                if ($listAction->getAction() === $this->getCurrentAction()) {
                    $listAction->process();
                }
            }
        }
    }

    protected function getCurrentAction()
    {
        return $this->getWpListTable()->current_action();
    }

    /**
     * @return mixed
     */
    public function getWpListTable()
    {
        if ($this->wpListTable === null) {
            $this->wpListTable = _get_list_table('WP_Posts_List_Table');
        }
        return $this->wpListTable;
    }
}
