<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolve the term ordering used for a product attribute.
 *
 * DAPF-specific choices take precedence. When DAPF is left at "default",
 * inherit WooCommerce custom ordering so cached term insertion order cannot
 * override the order configured on the WooCommerce attribute screen.
 *
 * @param string $attribute_name     Product attribute slug, with or without the pa_ prefix.
 * @param string $configured_orderby DAPF order_by value.
 * @return string
 */
function dapfforwc_get_effective_attribute_orderby($attribute_name, $configured_orderby = 'default')
{
    if ($configured_orderby !== 'default') {
        return $configured_orderby;
    }

    if (!function_exists('wc_get_attribute_taxonomies')) {
        return 'default';
    }

    static $woocommerce_orderby = null;

    if ($woocommerce_orderby === null) {
        $woocommerce_orderby = [];

        foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
            $name = isset($attribute->attribute_name) ? sanitize_key((string) $attribute->attribute_name) : '';
            if ($name === '') {
                continue;
            }

            $woocommerce_orderby[$name] = isset($attribute->attribute_orderby)
                ? sanitize_key((string) $attribute->attribute_orderby)
                : '';
        }
    }

    $attribute_name = preg_replace('/^pa_/', '', sanitize_key((string) $attribute_name));

    return isset($woocommerce_orderby[$attribute_name]) && $woocommerce_orderby[$attribute_name] === 'menu_order'
        ? 'menu_order'
        : 'default';
}

/**
 * Sort product attribute terms using the DAPF choice or WooCommerce fallback.
 *
 * @param array  $terms              Attribute terms.
 * @param string $attribute_name     Product attribute slug.
 * @param string $configured_orderby DAPF order_by value.
 * @param string $order_direction    Sort direction.
 * @return array
 */
function dapfforwc_sort_attribute_terms($terms, $attribute_name, $configured_orderby = 'default', $order_direction = 'asc')
{
    $effective_orderby = dapfforwc_get_effective_attribute_orderby($attribute_name, $configured_orderby);

    if ($effective_orderby === 'default' || empty($terms) || !function_exists('dapfforwc_sort_terms')) {
        return $terms;
    }

    return dapfforwc_sort_terms($terms, $effective_orderby, $order_direction);
}

/**
 * Clear DAPF caches once after WooCommerce finishes a drag-and-drop term reorder.
 *
 * WooCommerce fires woocommerce_after_set_term_order for multiple terms during
 * one request, so defer and debounce the existing cache clear until shutdown.
 *
 * @param WP_Term $term     Reordered term.
 * @param int     $index    New term position.
 * @param string  $taxonomy Term taxonomy.
 * @return void
 */
function dapfforwc_schedule_cache_clear_after_term_order($term, $index, $taxonomy)
{
    static $scheduled = false;

    if (
        $scheduled
        || !is_string($taxonomy)
        || !function_exists('dapfforwc_is_woocommerce_taxonomy')
        || !dapfforwc_is_woocommerce_taxonomy($taxonomy)
    ) {
        return;
    }

    $scheduled = true;
    add_action('shutdown', 'dapfforwc_clear_caches_after_term_order');
}
add_action('woocommerce_after_set_term_order', 'dapfforwc_schedule_cache_clear_after_term_order', 10, 3);

/**
 * Run the deferred term-order cache clear.
 *
 * @return void
 */
function dapfforwc_clear_caches_after_term_order()
{
    if (function_exists('dapfforwc_clear_woocommerce_caches')) {
        dapfforwc_clear_woocommerce_caches('term_order');
    }
}

/**
 * Exchange rate of the shopper's currency against the store currency while a currency switcher shows prices in
 * another currency (CURCY - Multi Currency for WooCommerce, WOOCS - Currency Switcher for WooCommerce), else 1.
 *
 * Product prices (_price, the cached product data) are in the store currency, while the price slider and price
 * filter URLs use the currency the shopper sees.
 *
 * @return float Amount in the shopper's currency for 1 in the store currency.
 */
function dapfforwc_get_price_filter_currency_rate() {
    $rate = 1.0;
    if (function_exists('wmc_get_price')) {
        // CURCY: converts a store-currency amount into the current currency.
        $rate = (float) wmc_get_price(1);
    } elseif (isset($GLOBALS['WOOCS']) && is_object($GLOBALS['WOOCS']) && method_exists($GLOBALS['WOOCS'], 'get_currencies')) {
        $woocs_currencies = (array) $GLOBALS['WOOCS']->get_currencies();
        $woocs_current = isset($GLOBALS['WOOCS']->current_currency) ? (string) $GLOBALS['WOOCS']->current_currency : '';
        if (isset($woocs_currencies[$woocs_current]['rate'])) {
            $rate = (float) $woocs_currencies[$woocs_current]['rate'];
        }
    }

    /**
     * Filters the exchange rate used for the price filter (shopper's currency per 1 store currency). Return the
     * rate for other currency switchers; 1 keeps prices in the store currency.
     *
     * @param float $rate Detected rate.
     */
    $rate = (float) apply_filters('dapfforwc_price_filter_currency_rate', $rate);
    return $rate > 0 ? $rate : 1.0;
}

/**
 * Converts a price filter amount from the shopper's currency into the store currency.
 *
 * @param float|int|string $amount Amount from the price slider or a price filter URL.
 * @return float
 */
function dapfforwc_price_filter_to_store_currency($amount) {
    $rate = dapfforwc_get_price_filter_currency_rate();
    return $rate === 1.0 ? (float) $amount : (float) $amount / $rate;
}

function dapfforwc_get_min_max_price($products, $products_id = []) {
    $target_product_ids = array_fill_keys(array_map('intval', (array) $products_id), true);
    $min_price = null;
    $max_price = null;

    foreach ((array) $products as $product) {
        if (!is_array($product) || empty($product['ID']) || !isset($target_product_ids[(int) $product['ID']])) {
            continue;
        }

        $raw_min_price = $product['price'] ?? '';
        if ($raw_min_price === '' || !is_numeric($raw_min_price)) {
            continue;
        }

        $raw_max_price = $product['max_price'] ?? $raw_min_price;
        $min_product_price = (float) $raw_min_price;
        $max_product_price = is_numeric($raw_max_price) ? (float) $raw_max_price : $min_product_price;

        if (is_null($min_price) || $min_product_price < $min_price) {
            $min_price = $min_product_price;
        }

        if (is_null($max_price) || $max_product_price > $max_price) {
            $max_price = $max_product_price;
        }
    }

    // The slider shows the shopper's currency (currency switchers).
    $currency_rate = dapfforwc_get_price_filter_currency_rate();

    if (!is_null($min_price)) {
        $min_price = floor($min_price * $currency_rate); // always take lower integer
    }

    if (!is_null($max_price)) {
        $max_price = ceil($max_price * $currency_rate); // always take upper integer
    }

    return array('min' => $min_price, 'max' => $max_price);
}

function dapfforwc_format_dimension_bound_value($value)
{
    if ($value === null || !is_numeric($value)) {
        return '';
    }

    $normalized = number_format((float) $value, 6, '.', '');
    $normalized = rtrim(rtrim($normalized, '0'), '.');

    if ($normalized === '' || $normalized === '-0') {
        return '0';
    }

    return $normalized;
}

function dapfforwc_dimension_row_matches_bound_filters(array $dimension_row, array $dimension_filters)
{
    foreach ($dimension_filters as $dimension => $range) {
        $value = $dimension_row[$dimension] ?? '';

        if ($value === '' || !is_numeric($value)) {
            return false;
        }

        $value = (float) $value;

        if (isset($range['min']) && $range['min'] !== null && $value < (float) $range['min']) {
            return false;
        }

        if (isset($range['max']) && $range['max'] !== null && $value > (float) $range['max']) {
            return false;
        }
    }

    return true;
}

if (!function_exists('dapfforwc_get_request_uri_query_params')) {
    /**
     * Query-string parameters of the current request URI (not the hydrated $_GET).
     * Parsed directly instead of rebuilding "http://host/uri" first.
     */
    function dapfforwc_get_request_uri_query_params()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only the query part is parsed; callers sanitize the values they use.
        $query = (string) wp_parse_url($request_uri, PHP_URL_QUERY);

        $query_params = array();
        if ($query !== '') {
            parse_str($query, $query_params);
        }

        return $query_params;
    }
}

if (!function_exists('dapfforwc_get_on_sale_product_ids')) {
    /**
     * Product IDs on sale from WooCommerce's cached lookup: honours scheduled sales and lists the parents of on-sale
     * variations, so no product has to be loaded to know whether it is on sale. The on-sale filter and the on-sale
     * option data both use it, so counts and results agree.
     */
    function dapfforwc_get_on_sale_product_ids(): array
    {
        $ids = function_exists('wc_get_product_ids_on_sale') ? (array) wc_get_product_ids_on_sale() : array();

        return array_values(array_unique(array_filter(array_map('absint', $ids))));
    }
}

if (!function_exists('dapfforwc_get_dimension_filter_unit')) {
    /**
     * Unit of a dimension filter: product dimensions are stored in the store's WooCommerce units
     * (Settings > Products > General).
     */
    function dapfforwc_get_dimension_filter_unit(string $dimension): string
    {
        $is_weight = $dimension === 'weight';
        $unit = sanitize_text_field((string) get_option($is_weight ? 'woocommerce_weight_unit' : 'woocommerce_dimension_unit', $is_weight ? 'kg' : 'cm'));

        return $unit !== '' ? $unit : ($is_weight ? 'kg' : 'cm');
    }
}

if (!function_exists('dapfforwc_get_dimension_filter_label')) {
    /**
     * Label of a dimension filter. A saved custom label is kept; a saved label equal to the old hard-coded
     * cm/kg default (which the settings form used to pre-fill and save) is treated as the default.
     */
    function dapfforwc_get_dimension_filter_label(string $dimension, $saved_label = ''): string
    {
        $legacy_defaults = array(
            'length' => 'Length (cm):',
            'width' => 'Width (cm):',
            'height' => 'Height (cm):',
            'weight' => 'Weight (kg):',
        );
        $saved_label = is_scalar($saved_label) ? trim((string) $saved_label) : '';
        if ($saved_label !== '' && $saved_label !== ($legacy_defaults[$dimension] ?? '')) {
            return $saved_label;
        }

        $unit = dapfforwc_get_dimension_filter_unit($dimension);
        switch ($dimension) {
            case 'length':
                /* translators: %s: dimension unit set in WooCommerce, e.g. cm or in. */
                return sprintf(__('Length (%s):', 'dynamic-ajax-product-filters-for-woocommerce'), $unit);
            case 'width':
                /* translators: %s: dimension unit set in WooCommerce, e.g. cm or in. */
                return sprintf(__('Width (%s):', 'dynamic-ajax-product-filters-for-woocommerce'), $unit);
            case 'height':
                /* translators: %s: dimension unit set in WooCommerce, e.g. cm or in. */
                return sprintf(__('Height (%s):', 'dynamic-ajax-product-filters-for-woocommerce'), $unit);
            case 'weight':
                /* translators: %s: weight unit set in WooCommerce, e.g. kg or lbs. */
                return sprintf(__('Weight (%s):', 'dynamic-ajax-product-filters-for-woocommerce'), $unit);
        }

        return ucfirst($dimension) . ':';
    }
}

function dapfforwc_get_dimension_filter_bounds($products, $products_id = [], $active_dimension_filters = [])
{
    $dimensions = array('length', 'width', 'height', 'weight');
    $bounds = array();

    foreach ($dimensions as $dimension) {
        $bounds[$dimension] = array('min' => '', 'max' => '');
    }

    if (empty($products) || !is_array($products)) {
        return $bounds;
    }

    $product_lookup = array();
    foreach ($products as $product) {
        if (!is_array($product) || empty($product['ID'])) {
            continue;
        }

        $product_lookup[(int) $product['ID']] = $product;
    }

    if (empty($product_lookup)) {
        return $bounds;
    }

    $target_product_ids = !empty($products_id)
        ? array_values(array_unique(array_filter(array_map('intval', (array) $products_id))))
        : array_keys($product_lookup);

    foreach ($dimensions as $target_dimension) {
        $other_dimension_filters = $active_dimension_filters;
        unset($other_dimension_filters[$target_dimension]);

        $min_value = null;
        $max_value = null;

        foreach ($target_product_ids as $product_id) {
            if (!isset($product_lookup[$product_id])) {
                continue;
            }

            $product = $product_lookup[$product_id];
            $dimension_rows = array();

            if (!empty($product['dimension_rows']) && is_array($product['dimension_rows'])) {
                foreach ($product['dimension_rows'] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $dimension_rows[] = array(
                        'length' => dapfforwc_format_dimension_bound_value($row['length'] ?? ''),
                        'width' => dapfforwc_format_dimension_bound_value($row['width'] ?? ''),
                        'height' => dapfforwc_format_dimension_bound_value($row['height'] ?? ''),
                        'weight' => dapfforwc_format_dimension_bound_value($row['weight'] ?? ''),
                    );
                }
            }

            if (empty($dimension_rows)) {
                $dimension_rows[] = array(
                    'length' => dapfforwc_format_dimension_bound_value($product['length'] ?? ''),
                    'width' => dapfforwc_format_dimension_bound_value($product['width'] ?? ''),
                    'height' => dapfforwc_format_dimension_bound_value($product['height'] ?? ''),
                    'weight' => dapfforwc_format_dimension_bound_value($product['weight'] ?? ''),
                );
            }

            $seen_rows = array();
            foreach ($dimension_rows as $dimension_row) {
                $row_key = md5(wp_json_encode($dimension_row));
                if (isset($seen_rows[$row_key])) {
                    continue;
                }

                $seen_rows[$row_key] = true;

                if (!dapfforwc_dimension_row_matches_bound_filters($dimension_row, $other_dimension_filters)) {
                    continue;
                }

                $value = $dimension_row[$target_dimension] ?? '';
                if ($value === '' || !is_numeric($value)) {
                    continue;
                }

                $value = (float) $value;

                if ($min_value === null || $value < $min_value) {
                    $min_value = $value;
                }

                if ($max_value === null || $value > $max_value) {
                    $max_value = $value;
                }
            }
        }

        $bounds[$target_dimension] = array(
            'min' => dapfforwc_format_dimension_bound_value($min_value),
            'max' => dapfforwc_format_dimension_bound_value($max_value),
        );
    }

    return $bounds;
}


function dapfforwc_getFilteredProductIds($array) {
    // Collect all non-empty filter arrays. Re-index (string keys would be unpacked as named arguments on PHP 8)
    // and cast each one to an array (a scalar would make array_intersect() throw a TypeError).
    $filters = array_values(array_map(function ($filter) {
        return (array) $filter;
    }, array_filter((array) $array, function ($filter) {
        return !empty($filter);
    })));

    // Return empty array if no filters are applied
    if (empty($filters)) {
        return [];
    }

    // If only one filter is applied, return its values
    if (count($filters) === 1) {
        return array_values($filters[0]);
    }

    // Intersect all non-empty filters to get common product IDs
    return array_values(array_intersect(...$filters));
}

if (!function_exists('dapfforwc_get_style_option_attribute_groups')) {
    function dapfforwc_get_style_option_attribute_groups(): array
    {
        return [
            'show_in_active_filters',
            'widget_title',
            'placeholder',
            'btntext',
            'show_apply_button',
            'applybtntext',
            'apply_behavior',
            'show_reset_button',
            'show_apply_reset_on',
            'max_height',
            'css_class',
            'operator',
            'terms',
            'include_exclude',
            'enable_terms_search',
            'terms_search_texts',
            'terms_search_position',
            'layout',
            'num_columns',
            'enable_tooltip',
            'tooltip_text',
            'enable_widget_title_icon',
            'enable_auto_suggestion',
            'search_behavior',
            'enable_full_match',
        ];
    }
}

if (!function_exists('dapfforwc_get_attribute_style_lookup_keys')) {
    function dapfforwc_get_attribute_style_lookup_keys($attribute): array
    {
        $keys = [];
        $attribute_id = 0;
        $attribute_slug = '';

        if (is_numeric($attribute)) {
            $attribute_id = absint($attribute);
            if ($attribute_id > 0 && function_exists('wc_get_attribute')) {
                $attribute_object = wc_get_attribute($attribute_id);
                if ($attribute_object && !is_wp_error($attribute_object)) {
                    $attribute_slug = sanitize_key((string) $attribute_object->slug);
                }
            }
        } elseif (is_scalar($attribute)) {
            $attribute_slug = sanitize_key((string) $attribute);
            if (strpos($attribute_slug, 'pa_') === 0) {
                $attribute_slug = substr($attribute_slug, 3);
            }

            if ($attribute_slug !== '' && function_exists('wc_attribute_taxonomy_id_by_name')) {
                $attribute_id = absint(wc_attribute_taxonomy_id_by_name($attribute_slug));
            }
        }

        if ($attribute_id > 0) {
            $keys[] = (string) $attribute_id;
        }

        if ($attribute_slug !== '' && !in_array($attribute_slug, $keys, true)) {
            $keys[] = $attribute_slug;
        }

        return $keys;
    }
}

if (!function_exists('dapfforwc_get_attribute_style_storage_key')) {
    function dapfforwc_get_attribute_style_storage_key($attribute): string
    {
        $keys = dapfforwc_get_attribute_style_lookup_keys($attribute);

        return $keys[0] ?? '';
    }
}

if (!function_exists('dapfforwc_prepare_style_options_for_runtime')) {
    function dapfforwc_prepare_style_options_for_runtime($style_options): array
    {
        if (!is_array($style_options) || !function_exists('wc_get_attribute_taxonomies')) {
            return is_array($style_options) ? $style_options : [];
        }

        foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
            $attribute_id = absint($attribute->attribute_id ?? 0);
            $attribute_slug = sanitize_key((string) ($attribute->attribute_name ?? ''));
            if ($attribute_id <= 0 || $attribute_slug === '') {
                continue;
            }

            if (array_key_exists($attribute_id, $style_options)) {
                $style_options[$attribute_slug] = $style_options[$attribute_id];
            }

            foreach (dapfforwc_get_style_option_attribute_groups() as $group) {
                if (
                    isset($style_options[$group])
                    && is_array($style_options[$group])
                    && array_key_exists($attribute_id, $style_options[$group])
                ) {
                    $style_options[$group][$attribute_slug] = $style_options[$group][$attribute_id];
                }
            }
        }

        return $style_options;
    }
}

if (!function_exists('dapfforwc_get_style_options')) {
    function dapfforwc_get_style_options(): array
    {
        return dapfforwc_prepare_style_options_for_runtime(get_option('dapfforwc_style_options', []));
    }
}

if (!function_exists('dapfforwc_normalize_style_option_attribute_keys')) {
    function dapfforwc_normalize_style_option_attribute_keys(array $style_options, array $legacy_attribute_ids = []): array
    {
        $attribute_ids = [];
        if (function_exists('wc_get_attribute_taxonomies')) {
            foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
                $attribute_id = absint($attribute->attribute_id ?? 0);
                $attribute_slug = sanitize_key((string) ($attribute->attribute_name ?? ''));
                if ($attribute_id > 0 && $attribute_slug !== '') {
                    $attribute_ids[$attribute_slug] = $attribute_id;
                }
            }
        }

        foreach ($legacy_attribute_ids as $attribute_slug => $attribute_id) {
            $attribute_slug = sanitize_key((string) $attribute_slug);
            $attribute_id = absint($attribute_id);
            if ($attribute_slug !== '' && $attribute_id > 0) {
                $attribute_ids[$attribute_slug] = $attribute_id;
            }
        }

        foreach ($attribute_ids as $attribute_slug => $attribute_id) {
            if (array_key_exists($attribute_slug, $style_options)) {
                if (
                    array_key_exists($attribute_id, $style_options)
                    && is_array($style_options[$attribute_slug])
                    && is_array($style_options[$attribute_id])
                ) {
                    $style_options[$attribute_id] = array_replace_recursive(
                        $style_options[$attribute_slug],
                        $style_options[$attribute_id]
                    );
                } elseif (!array_key_exists($attribute_id, $style_options)) {
                    $style_options[$attribute_id] = $style_options[$attribute_slug];
                }
                unset($style_options[$attribute_slug]);
            }

            foreach (dapfforwc_get_style_option_attribute_groups() as $group) {
                if (!isset($style_options[$group]) || !is_array($style_options[$group])) {
                    continue;
                }

                if (array_key_exists($attribute_slug, $style_options[$group])) {
                    if (!array_key_exists($attribute_id, $style_options[$group])) {
                        $style_options[$group][$attribute_id] = $style_options[$group][$attribute_slug];
                    }
                    unset($style_options[$group][$attribute_slug]);
                }
            }
        }

        return $style_options;
    }
}

if (!function_exists('dapfforwc_migrate_style_options_on_attribute_update')) {
    function dapfforwc_migrate_style_options_on_attribute_update($attribute_id, $attribute_data, $old_slug)
    {
        $style_options = get_option('dapfforwc_style_options', []);
        if (!is_array($style_options)) {
            return;
        }

        if (function_exists('dapfforwc_normalize_style_option_term_keys')) {
            $style_options = dapfforwc_normalize_style_option_term_keys($style_options);
        }

        $legacy_attribute_ids = [
            sanitize_key((string) $old_slug) => absint($attribute_id),
        ];
        if (is_array($attribute_data) && !empty($attribute_data['attribute_name'])) {
            $legacy_attribute_ids[sanitize_key((string) $attribute_data['attribute_name'])] = absint($attribute_id);
        }

        $normalized = dapfforwc_normalize_style_option_attribute_keys($style_options, $legacy_attribute_ids);
        if ($normalized !== $style_options) {
            update_option('dapfforwc_style_options', $normalized);
        }

        $GLOBALS['dapfforwc_styleoptions'] = dapfforwc_prepare_style_options_for_runtime($normalized);
    }
}
add_action('woocommerce_attribute_updated', 'dapfforwc_migrate_style_options_on_attribute_update', 5, 3);

if (isset($GLOBALS['dapfforwc_styleoptions'])) {
    $GLOBALS['dapfforwc_styleoptions'] = dapfforwc_prepare_style_options_for_runtime(
        $GLOBALS['dapfforwc_styleoptions']
    );
}

if (!function_exists('dapfforwc_refresh_text_option_globals')) {
    /**
     * The option globals are read when the plugin loads, before WPML / Polylang know the visitor's language.
     * Read the stored options again once it is known, so the texts listed in wpml-config.xml show translated.
     */
    function dapfforwc_refresh_text_option_globals(): void
    {
        $language = apply_filters('wpml_current_language', null); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML / Polylang hook.
        if (!is_scalar($language) || (string) $language === '') {
            return;
        }

        global $dapfforwc_styleoptions, $dapfforwc_advance_settings;

        $style_options = get_option('dapfforwc_style_options');
        if (is_array($style_options)) {
            $dapfforwc_styleoptions = dapfforwc_prepare_style_options_for_runtime(
                array_replace_recursive(is_array($dapfforwc_styleoptions) ? $dapfforwc_styleoptions : [], $style_options)
            );
        }

        $advance_options = get_option('dapfforwc_advance_options');
        if (is_array($advance_options)) {
            $dapfforwc_advance_settings = array_replace_recursive(is_array($dapfforwc_advance_settings) ? $dapfforwc_advance_settings : [], $advance_options);
        }
    }
}
add_action('wp', 'dapfforwc_refresh_text_option_globals', 5);
// admin-ajax requests never fire 'wp': refresh there too, so AJAX output is translated.
add_action('admin_init', function () {
    if (wp_doing_ajax()) {
        dapfforwc_refresh_text_option_globals();
    }
}, 5);

if (!function_exists('dapfforwc_get_style_term_taxonomy')) {
    function dapfforwc_get_style_term_taxonomy(string $attribute): string
    {
        if (is_numeric($attribute)) {
            $attribute_keys = dapfforwc_get_attribute_style_lookup_keys($attribute);
            $attribute = $attribute_keys[1] ?? '';
        }

        $attribute = sanitize_key($attribute);
        if ($attribute === '') {
            return '';
        }

        if ($attribute === 'product-category') {
            return taxonomy_exists('product_cat') ? 'product_cat' : '';
        }

        if ($attribute === 'tag') {
            return taxonomy_exists('product_tag') ? 'product_tag' : '';
        }

        if ($attribute === 'brands') {
            return taxonomy_exists('product_brand') ? 'product_brand' : '';
        }

        // An unprefixed key is a WooCommerce attribute first (color => pa_color). A same-named taxonomy is only used
        // when it belongs to products, so another plugin's "color" taxonomy is never picked.
        $attribute_taxonomy = strpos($attribute, 'pa_') === 0 ? $attribute : 'pa_' . sanitize_title($attribute);
        if (taxonomy_exists($attribute_taxonomy)) {
            return $attribute_taxonomy;
        }

        return (taxonomy_exists($attribute) && is_object_in_taxonomy('product', $attribute)) ? $attribute : '';
    }
}

if (!function_exists('dapfforwc_get_term_item_field')) {
    function dapfforwc_get_term_item_field($term, string $field, $default = '')
    {
        if (is_array($term) && array_key_exists($field, $term)) {
            return $term[$field];
        }

        if (is_object($term) && isset($term->{$field})) {
            return $term->{$field};
        }

        return $default;
    }
}

if (!function_exists('dapfforwc_add_term_style_option_key')) {
    function dapfforwc_add_term_style_option_key(array &$keys, $key)
    {
        if (!is_scalar($key)) {
            return;
        }

        $key = rawurldecode(trim((string) $key));
        if ($key === '' || in_array($key, $keys, true)) {
            return;
        }

        $keys[] = $key;
    }
}

if (!function_exists('dapfforwc_get_term_style_storage_key')) {
    function dapfforwc_get_term_style_storage_key($term): string
    {
        $term_id = absint(dapfforwc_get_term_item_field($term, 'term_id', 0));
        if ($term_id > 0) {
            return (string) $term_id;
        }

        $slug = dapfforwc_get_term_item_field($term, 'slug', '');
        if (is_scalar($slug) && trim((string) $slug) !== '') {
            return sanitize_title((string) $slug);
        }

        if (is_scalar($term)) {
            $term = trim((string) $term);
            return is_numeric($term) ? (string) absint($term) : sanitize_title($term);
        }

        return '';
    }
}

if (!function_exists('dapfforwc_get_term_style_lookup_keys')) {
    function dapfforwc_get_term_style_lookup_keys($term, string $attribute = ''): array
    {
        $keys = array();
        $term_id = 0;
        $slug = '';
        $source_slug = '';
        $taxonomy = '';

        if (is_scalar($term)) {
            $raw_term = trim((string) $term);
            if (is_numeric($raw_term)) {
                $term_id = absint($raw_term);
            } else {
                $slug = $raw_term;
            }
        } else {
            $term_id = absint(dapfforwc_get_term_item_field($term, 'term_id', 0));
            $slug = (string) dapfforwc_get_term_item_field($term, 'slug', '');
            $source_slug = (string) dapfforwc_get_term_item_field($term, 'source_slug', '');
            $taxonomy = sanitize_key((string) dapfforwc_get_term_item_field($term, 'taxonomy', ''));
        }

        if ($taxonomy === '') {
            $taxonomy = dapfforwc_get_style_term_taxonomy($attribute);
        }

        // Saved values are term IDs (older settings used slugs), so a numeric slug key ("38") may only be used
        // when no other term of this taxonomy has that ID; otherwise it would match that other term too.
        $slug_key_is_safe = static function ($slug_key, $owner_term_id) use ($taxonomy): bool {
            $slug_key = rawurldecode(trim((string) $slug_key));
            if ($slug_key === '' || $taxonomy === '' || !preg_match('/^[0-9]+$/', $slug_key)) {
                return true;
            }

            $id_term = get_term((int) $slug_key, $taxonomy);
            return !$id_term || is_wp_error($id_term) || (int) $id_term->term_id === (int) $owner_term_id;
        };

        if ($term_id > 0) {
            dapfforwc_add_term_style_option_key($keys, $term_id);
        }

        $resolved_term = null;
        if ($taxonomy !== '') {
            if ($term_id > 0) {
                $resolved_term = get_term($term_id, $taxonomy);
            }

            if ((!$resolved_term || is_wp_error($resolved_term)) && $slug !== '') {
                $resolved_term = get_term_by('slug', $slug, $taxonomy);
            }

            if ((!$resolved_term || is_wp_error($resolved_term)) && $source_slug !== '') {
                $resolved_term = get_term_by('slug', $source_slug, $taxonomy);
            }

            if ($resolved_term && !is_wp_error($resolved_term)) {
                dapfforwc_add_term_style_option_key($keys, $resolved_term->term_id);
                if ($slug_key_is_safe($resolved_term->slug, $resolved_term->term_id)) {
                    dapfforwc_add_term_style_option_key($keys, $resolved_term->slug);
                    dapfforwc_add_term_style_option_key($keys, sanitize_title($resolved_term->slug));
                }
            }
        }

        $owner_term_id = ($resolved_term && !is_wp_error($resolved_term)) ? (int) $resolved_term->term_id : $term_id;
        foreach (array($slug, $source_slug) as $slug_key) {
            if ($slug_key_is_safe($slug_key, $owner_term_id)) {
                dapfforwc_add_term_style_option_key($keys, $slug_key);
                dapfforwc_add_term_style_option_key($keys, sanitize_title($slug_key));
            }
        }

        return $keys;
    }
}

if (!function_exists('dapfforwc_get_term_style_option_value')) {
    function dapfforwc_get_term_style_option_value($style_options, string $attribute, string $group, $term, $default = '')
    {
        if (!is_array($style_options)) {
            return $default;
        }

        $attribute_options = null;
        foreach (dapfforwc_get_attribute_style_lookup_keys($attribute) as $attribute_key) {
            if (isset($style_options[$attribute_key]) && is_array($style_options[$attribute_key])) {
                $attribute_options = $style_options[$attribute_key];
                break;
            }
        }

        if (!is_array($attribute_options)) {
            return $default;
        }

        if (!isset($attribute_options[$group]) || !is_array($attribute_options[$group])) {
            return $default;
        }

        foreach (dapfforwc_get_term_style_lookup_keys($term, $attribute) as $key) {
            if (array_key_exists($key, $attribute_options[$group])) {
                return $attribute_options[$group][$key];
            }
        }

        return $default;
    }
}

if (!function_exists('dapfforwc_term_matches_stored_filter_value')) {
    function dapfforwc_term_matches_stored_filter_value($term, $stored_values, string $attribute = ''): bool
    {
        if (!is_array($stored_values)) {
            $stored_values = array($stored_values);
        }

        $stored_values = array_map('strval', $stored_values);
        foreach (dapfforwc_get_term_style_lookup_keys($term, $attribute) as $key) {
            if (in_array((string) $key, $stored_values, true)) {
                return true;
            }
        }

        return false;
    }
}

function dapfforwc_get_wc_brand_image_by_slug($brand_slug) {
    // Get the term object for the brand slug
    $term = get_term_by('slug', $brand_slug, 'product_brand');

    // Check if the term exists
    if ($term) {
        // Get the thumbnail ID
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);

        // Get the image URL
        $image_url = wp_get_attachment_url($thumbnail_id);
        if ($image_url && is_numeric($thumbnail_id)) {
            // The filter renders this URL as an attachment again; remember the ID to skip the reverse lookup.
            wp_cache_set('attachment_id_' . md5($image_url), (int) $thumbnail_id, 'dapfforwc', HOUR_IN_SECONDS);
        }

        // Return the image URL
        return $image_url;
    }
    return false;
}

/**
 * attachment_url_to_postid() runs an uncached query on every call; cache the result per image URL.
 */
function dapfforwc_get_image_attachment_id($image_url) {
    $image_url = (string) $image_url;
    if ($image_url === '') {
        return 0;
    }

    $cache_key = 'attachment_id_' . md5($image_url);
    $attachment_id = wp_cache_get($cache_key, 'dapfforwc');
    if ($attachment_id === false) {
        $attachment_id = (int) attachment_url_to_postid($image_url);
        wp_cache_set($cache_key, $attachment_id, 'dapfforwc', HOUR_IN_SECONDS);
    }

    return (int) $attachment_id;
}
