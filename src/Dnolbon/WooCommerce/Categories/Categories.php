<?php
namespace Dnolbon\WooCommerce\Categories;

class Categories
{
    public static function getCategoriesTree()
    {
        $categories = get_terms('product_cat', array('hide_empty' => 0, 'hierarchical' => true));
        $categories = json_decode(json_encode($categories), true);
        $categories = self::buildCategoriesTree($categories, 0);
        return $categories;
    }

    private static function buildCategoriesTree($allCategories, $parentCategories, $level = 1)
    {
        $result = [];
        foreach ($allCategories as $category) {
            if ($category['parent'] === $parentCategories) {
                $category['level'] = $level;
                $result[] = $category;
                $child_cats = self::buildCategoriesTree($allCategories, $category['term_id'], $level + 1);
                if ($child_cats) {
                    $result = array_merge($result, $child_cats);
                }
            }
        }
        return $result;
    }
}
