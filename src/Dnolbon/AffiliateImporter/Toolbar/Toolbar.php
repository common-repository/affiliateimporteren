<?php
namespace Dnolbon\AffiliateImporter\Toolbar;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\Twig\Twig;

class Toolbar
{
    public static function parseToolbar($activePage, $type)
    {
        $importer = AffiliateImporter::getInstance()->getImporter($type);

        $imagePath = plugins_url('assets/img/toolbar', $importer->getMainFile());

        $menu = [
            [
                'name' => 'Dasboard',
                'icon' => 'dashboard',
                'link' => ''
            ],
            [
                'name' => 'Add product',
                'icon' => 'add',
                'link' => 'add'
            ],
            [
                'name' => 'Shedule',
                'icon' => 'schedule',
                'link' => 'schedule'
            ],
            [
                'name' => 'Statistics',
                'icon' => 'stat',
                'link' => 'stats'
            ],
            [
                'name' => 'Settings',
                'icon' => 'settings',
                'link' => 'settings'
            ],
            [
                'name' => 'Backup/Restore',
                'icon' => 'backup',
                'link' => 'backup'
            ],
            [
                'name' => 'Status',
                'icon' => 'status',
                'link' => 'status'
            ],
            [
                'name' => 'Support',
                'icon' => 'support',
                'link' => '-',
                'exteral_link' => 'http://cr1000team.com/support/',
                'class' => 'right'
            ]
        ];

        foreach ($menu as &$menuEl) {
            if (isset($menuEl['exteral_link'])) {
                $fullLink = ($menuEl['exteral_link']);
            } else {
                $page = $importer->getClassPrefix() . ($menuEl['link'] ? '-' . $menuEl['link'] : '');
                $fullLink = (admin_url('admin.php?page=' . $page));
            }

            $linkClass = '';
            if ($activePage === $menuEl['link']) {
                $linkClass = 'active_page';
            }

            $menuEl['full_link'] = $fullLink;
            $menuEl['link_class'] = $linkClass;
        }

        $template = Twig::getInstance()->getTwig()->load('toolbar.html');
        echo $template->render(
            [
                'menu' => $menu,
                'imagePath' => $imagePath,
                'activePage' => $activePage
            ]
        );
    }
}
