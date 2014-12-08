<?php
/*
Plugin Name: Wordpress Active Nav
Version: 1.0
Author: StaticFlow
Author URI: http://staticflow.co.uk

Usage:
wp_nav_menu(array(
    'follow_active' => true,
    'start_level' => 1,
    'max_level' => 2,
));
*/

add_filter('wp_nav_menu_objects', function($items, $args) {
    $active_path = array('0');
    $current_object_id = get_the_ID();
    $cursor = !empty($args->root_menu_id) ? $args->root_menu_id : null;

    $hierarchical_items = array('0' => array());
    $current_level = &$hierarchical_items['0'];
    $parents = array('0');
    foreach ($items as $item) {
        if ($item->menu_item_parent == end($parents)) {
            $current_level[$item->ID] = array();
        } else {
            if (array_key_exists($item->menu_item_parent, $current_level)) {
                $parents[] = $item->menu_item_parent;
            } else {
                $current_level = &$hierarchical_items;
                foreach ($parents as $i => $parent) {
                    if (array_key_exists($item->menu_item_parent, $current_level)) {
                        $parents = array_slice($parents, 0, $i + 1);
                        break;
                    }
                    $current_level = &$current_level[$parent];
                }
            }
            $current_level = &$current_level[$item->menu_item_parent];
            $current_level[$item->ID] = array();
        }

        if ($item->object_id == $current_object_id) {
            $active_path = $parents;
            $active_path[] = (string)$item->ID;
        }
    }

    if (isset($args->follow_active) && $args->follow_active) {
        $cursor = null;
        if (count($active_path) > $args->max_level) {
            $cursor = $active_path[$args->max_level - 1];
        } elseif (count($active_path) < $args->start_level) {
            return array();
        } else {
            $cursor = end($active_path);
        }
    }

    if (!is_null($cursor)) {
        //  walk finding items until all levels are exhausted
        $parents = array($cursor);
        $output = array();
        while (!empty($parents)) {
            $newparents = array();
            foreach ($items as $item) {
                if (in_array($item->menu_item_parent, $parents)) {
                    if ($item->menu_item_parent == $cursor)
                        $item->menu_item_parent = 0;
                    $output[] = $item;
                    $newparents[] = $item->ID;
                }
            }
            $parents = $newparents;
        }

        return $output;
    } else {
        return $items;
    }
}, 10, 2);

add_shortcode('wp-active-nav', function($atts, $content = null) {
    $atts['echo'] = false;
    return wp_nav_menu($atts);
});

