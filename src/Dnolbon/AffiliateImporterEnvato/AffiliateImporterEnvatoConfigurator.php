<?php
namespace Dnolbon\AffiliateImporterEnvato;

use Dnolbon\AffiliateImporter\AffiliateImporter;
use Dnolbon\AffiliateImporter\Configurator\ConfiguratorAbstract;
use Dnolbon\AffiliateImporter\Products\Product;

class AffiliateImporterEnvatoConfigurator extends ConfiguratorAbstract
{
    public function install()
    {

    }

    public function uninstall()
    {

    }

    public function setFilters()
    {
//        $this->addFilter(
//            'site',
//            'site',
//            31,
//            [
//                'type' => 'select',
//                'label' => 'Site',
//                'class' => 'category_list',
//                'style' => 'width:25em;',
//                'data_source' => [$this, 'getSites']
//            ]
//        );
        $this->addFilter(
            'category_id',
            'category_id',
            32,
            [
                'type' => 'select',
                'label' => 'Category',
                'class' => 'category_list',
                'style' => 'width:25em;',
                'data_source' => [$this, 'getCategories']
            ]
        );
        $this->addFilter(
            'tags',
            'tags',
            33,
            [
                'type' => 'edit',
                'label' => 'Tags',
                'class' => '',
                'style' => ''
            ]
        );
        $this->addFilter(
            'platform',
            'platform',
            34,
            [
                'type' => 'edit',
                'label' => 'Platform',
                'class' => '',
                'style' => ''
            ]
        );
        $this->addFilter(
            'compatible_with',
            'compatible_with',
            35,
            [
                'type' => 'edit',
                'label' => 'Compatible with',
                'class' => '',
                'style' => ''
            ]
        );

        $this->addFilter(
            'rating',
            ['rating_min', 'rating_max'],
            36,
            [
                'type' => 'edit',
                'label' => 'Rating',
                'rating_min' => ['label' => 'min', 'default' => '0'],
                'rating_max' => ['label' => ' max', 'default' => '0']
            ]
        );

        $this->addFilter(
            'price',
            ['price_min', 'price_max'],
            37,
            [
                'type' => 'edit',
                'label' => 'Rating',
                'price_min' => ['label' => 'min', 'default' => '0'],
                'price_max' => ['label' => ' max', 'default' => '0']
            ]
        );
    }

    public function getSettings()
    {
        return [];
    }

    protected function getCategories()
    {
        $importer = AffiliateImporter::getInstance()->getImporter($this->getType());

        $catogories = [['id' => '', 'name' => ' - ', 'level' => 1]];
        foreach ($this->getSites() as $site) {
            $catogories[] = $site;
            $jsonFile = $importer->getMainFilePath() . '/data/envato/' . $site['id'] . '.json';
            $jsonData = json_decode(file_get_contents($jsonFile), true);
            foreach ($jsonData['categories'] as $cat) {
                $catogories[] = [
                    'id' => $site['id'] . ':' . $cat['path'],
                    'name' => $cat['name'],
                    'level' => 2 + (substr_count($cat['path'], '/'))
                ];
            }
        }
        return $catogories;
    }

    protected function getSites()
    {
        return [
            [
                'id' => 'themeforest.net',
                'name' => 'themeforest.net',
                'level' => 1
            ],
            [
                'id' => 'codecanyon.net',
                'name' => 'codecanyon.net',
                'level' => 1
            ],
            [
                'id' => 'videohive.net',
                'name' => 'videohive.net',
                'level' => 1
            ],
            [
                'id' => 'audiojungle.net',
                'name' => 'audiojungle.net',
                'level' => 1
            ],
            [
                'id' => 'graphicriver.net',
                'name' => 'graphicriver.net',
                'level' => 1
            ],
            [
                'id' => '3docean.net',
                'name' => '3docean.net',
                'level' => 1
            ]
        ];
    }

    public function getColumns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'image' => '',
            'info' => 'Information',
            'price' => 'Source Price',
            'userPrice' => 'Posted Price',
            'site' => 'Site',
            'classification' => 'Classification',
            'sales' => 'Sales',
            'author' => 'Author',
            'rating' => 'Rating'
        ];
        return $columns;
    }

    /**
     * @param Product $item
     */
    public function columnSite($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['site'];
    }
    /**
     * @param Product $item
     */
    public function columnClassification($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['classification'];
    }
    /**
     * @param Product $item
     */
    public function columnSales($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['sales'];
    }
    /**
     * @param Product $item
     */
    public function columnAuthor($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['author_username'];
    }
    /**
     * @param Product $item
     */
    public function columnRating($item)
    {
        $additionalMeta = ($item->getAdditionalMeta());
        return $additionalMeta['rating'];
    }
}
