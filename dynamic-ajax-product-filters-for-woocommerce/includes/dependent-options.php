<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight cache rows have counts but no product memberships. Resolve dependent
 * choices from the same product query as the listing instead of returning every term.
 * Results stay request-local: a visitor's selection must never enter the shared cache.
 */
function dapfforwc_get_database_updated_filters(array $all_data, array $context = array()): array
{
    global $dapfforwc_styleoptions;
    $filter = DAPFFORWC_WC_Query_Filter_Enhanced::get_instance();
    $params = isset($context['query_params']) ? $context['query_params'] : $filter->get_filter_params();
    $base = dapfforwc_get_lightweight_updated_filters($all_data);
    $selected = $context['selected'] ?? array();
    $pools = array();
    $resolve = static function (array $pool_params) use (&$pools, $filter, $all_data, $base, $selected): array {
        // Ordering, pagination and operators alone do not narrow the catalogue.
        $active = array_diff_key($pool_params, array_flip(array('orderby', 'order', 'paged', 'per_page', 'cat_operator', 'tag_operator', 'brand_operator', 'terms_operator', 'tax_relation', 'meta_relation')));
        $active = array_filter($active, static function ($value) { return $value !== '' && $value !== array() && $value !== null; });
        if (empty($active)) {
            return $base;
        }
        $key = md5(wp_json_encode($pool_params));
        if (isset($pools[$key])) {
            return $pools[$key];
        }
        $ids = $filter->get_filter_option_product_ids($pool_params);
        $counts = array();
        foreach (array_chunk($ids, 500) as $batch) {
            // Reduce each bounded batch to counts before reading the next one. A broad
            // filter on a large catalogue must not hydrate a full serialized dataset.
            $data = dapfforwc_get_filter_cache_skeleton_data();
            dapfforwc_merge_filter_cache_product_batch($data, $batch);
            $data = dapfforwc_filter_data_for_instock_only(dapfforwc_finalize_filter_cache_data($data));
            foreach (array_keys($base) as $group) {
                if (in_array($group, array('attributes', 'custom_fields'), true)) {
                    foreach ($data[$group] ?? array() as $name => $field) {
                        foreach ($field['terms'] as $term) {
                            $slug = (string) $term['slug'];
                            $counts[$group][$name][$slug] = ($counts[$group][$name][$slug] ?? 0) + count($term['products']);
                        }
                    }
                } else {
                    foreach ($data[$group] ?? array() as $term) {
                        $slug = (string) $term['slug'];
                        $counts[$group][$slug] = ($counts[$group][$slug] ?? 0) + count($term['products']);
                    }
                }
            }
        }
        $keep_terms = static function (array $terms, array $term_counts, array $selection): array {
            $kept = array();
            $selection = array_map('strval', $selection);
            foreach ($terms as $term) {
                $slug = (string) (is_object($term) ? $term->slug : $term['slug']);
                $count = $term_counts[$slug] ?? 0;
                if ($count > 0 || in_array($slug, $selection, true)) {
                    if (is_object($term)) { $term = clone $term; $term->count = $count; }
                    else { $term['count'] = $count; }
                    $kept[] = $term;
                }
            }
            return $kept;
        };
        $resolved = $base;
        // Retain configured terms and order; a selected zero-count option stays removable.
        foreach (array_keys($base) as $group) {
            if (in_array($group, array('attributes', 'custom_fields'), true)) {
                foreach ($base[$group] as $name => $terms) {
                    $resolved[$group][$name] = $keep_terms($terms, $counts[$group][$name] ?? array(), $selected[$group][$name] ?? array());
                }
            } else {
                $resolved[$group] = $keep_terms($base[$group], $counts[$group] ?? array(), $selected[$group] ?? array());
            }
        }
        return $pools[$key] = $resolved;
    };
    $operator = static function ($name, $fallback = 'OR') use ($dapfforwc_styleoptions): string {
        if (strtoupper((string) $fallback) === 'AND') {
            return 'AND';
        }
        return strtoupper((string) ($dapfforwc_styleoptions['operator'][$name] ?? $fallback));
    };
    $groups = array(
        'categories' => array('product-category', 'product-category', $params['cat_operator'] ?? 'OR'),
        'tags' => array('tags', 'tag', $params['tag_operator'] ?? 'OR'),
        'brands' => array('rplurand', 'brands', $params['brand_operator'] ?? 'OR'),
        'authors' => array('rpluthor', 'authors', 'OR'),
        'stock_status' => array('rplutock_status', 'status', 'OR'),
        'sale_status' => array('rpn_sale', 'sale_status', 'OR'),
    );
    $result = $base;
    foreach ($groups as $group => $config) {
        if (empty($all_data[$group])) {
            continue;
        }
        $pool_params = $params;
        if ($operator($config[1], $config[2]) !== 'AND') {
            unset($pool_params[$config[0]]);
        }
        $result[$group] = $resolve($pool_params)[$group];
    }
    foreach (array('attributes' => 'attributes', 'custom_fields' => 'custom_meta') as $group => $param_key) {
        foreach ($all_data[$group] ?? array() as $name => $field) {
            $pool_params = $params;
            if ($operator($name, $params['terms_operator'] ?? 'OR') !== 'AND') {
                unset($pool_params[$param_key][$name]);
                if (empty($pool_params[$param_key])) {
                    unset($pool_params[$param_key]);
                }
            }
            $result[$group][$name] = $resolve($pool_params)[$group][$name] ?? array();
        }
    }
    return $result;
}

/** Match the full cache's ancestor categories, without materializing product lists. */
function dapfforwc_add_lightweight_category_ancestors(array $categories): array
{
    global $wpdb;
    $hierarchy = dapfforwc_get_filter_cache_category_hierarchy();
    $descendants = array();
    foreach (array_keys($categories) as $term_id) {
        $current = (int) $term_id;
        $seen = array();
        while (!empty($hierarchy[$current]['parent']) && empty($seen[$current])) {
            $seen[$current] = true;
            $current = (int) $hierarchy[$current]['parent'];
            if (!isset($hierarchy[$current])) {
                break;
            }
            $descendants[$current][$term_id] = (int) $term_id;
            $descendants[$current][$current] = $current;
        }
    }
    foreach ($descendants as $term_id => $children) {
        $placeholders = implode(',', array_fill(0, count($children), '%d'));
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
               AND tt.taxonomy = 'product_cat' AND tt.term_id IN ($placeholders)",
            array_values($children)
        ));
        $categories[$term_id] = array_merge($hierarchy[$term_id], array(
            'term_id' => $term_id, 'taxonomy' => 'product_cat', 'products' => array(), 'count' => $count,
        ));
    }
    return $categories;
}
