<?php

/**
 * Plugin Name: Dynamic AJAX Product Filters for WooCommerce
 * Plugin URI:  https://plugincy.com/
 * Description: A WooCommerce plugin to filter products by attributes, categories, and tags using AJAX for seamless user experience.
 * Version:     1.6.4
 * Author:      Plugincy
 * Author URI:  https://plugincy.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dynamic-ajax-product-filters-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define DAY_IN_SECONDS if not already defined
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

define('DAPFFORWC_VERSION', '1.6.4');

define('DAPFFORWC_ENABLE_THIRD_PARTY_HOOKS', true);

if (!defined('DAPFFORWC_FRONTEND_STYLE_HANDLE')) {
    define('DAPFFORWC_FRONTEND_STYLE_HANDLE', 'dapfforwc-filter-style');
}

if (!defined('DAPFFORWC_PLUGIN_BASE_NAME')) {
    define('DAPFFORWC_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
}

if (!defined('DAPFFORWC_FILTER_CACHE_BATCH_HOOK')) {
    define('DAPFFORWC_FILTER_CACHE_BATCH_HOOK', 'dapfforwc_process_filter_cache_batch');
}

if (!defined('DAPFFORWC_FILTER_CACHE_QUEUE_GROUP')) {
    define('DAPFFORWC_FILTER_CACHE_QUEUE_GROUP', 'dapfforwc-filter-cache');
}

if (!defined('DAPFFORWC_FILTER_CACHE_BUILD_STATE_OPTION')) {
    define('DAPFFORWC_FILTER_CACHE_BUILD_STATE_OPTION', 'dapfforwc_filter_cache_build_state');
}

if (!defined('DAPFFORWC_FILTER_CACHE_BUILD_DATA_OPTION')) {
    define('DAPFFORWC_FILTER_CACHE_BUILD_DATA_OPTION', 'dapfforwc_filter_cache_build_data');
}

if (!defined('DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK')) {
    define('DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK', 'dapfforwc_process_product_details_cache_batch');
}

if (!defined('DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_STATE_OPTION')) {
    define('DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_STATE_OPTION', 'dapfforwc_product_details_cache_build_state');
}

if (!defined('DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_DATA_OPTION')) {
    define('DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_DATA_OPTION', 'dapfforwc_product_details_cache_build_data');
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        $needle = (string) $needle;
        if ($needle === '') {
            return true;
        }

        return strpos((string) $haystack, $needle) !== false;
    }
}

// Safe placeholder used for fragment-mode lazy images (keeps layout, no network request)
if (!defined('DAPFFORWC_FRAGMENT_PLACEHOLDER')) {
    define('DAPFFORWC_FRAGMENT_PLACEHOLDER', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Existing option cache global is used across legacy admin template code.
// Global Variables
global $dapfforwc_allowed_tags, $template_options, $dapfforwc_options, $dapfforwc_seo_permalinks_options, $dapfforwc_advance_settings, $dapfforwc_styleoptions, $dapfforwc_use_url_filter, $dapfforwc_auto_detect_pages_filters, $dapfforwc_slug, $dapfforwc_sub_options, $dapfforwc_front_page_slug;

$template_options = get_option('dapfforwc_template_options') ?: [
    'active_template' => 'clean',
    'background_color' => '#ffffffb3',
    'primary_color' => '#432fb8',
    'secondary_color' => '#ff4d4d',
    'border_color' => '#eeeeee',
    'text_color' => '#000000',
];
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$dapfforwc_options = get_option('dapfforwc_options') ?: [
    'show_categories' => "on",
    'show_attributes' => "on",
    'show_tags' => "on",
    'show_custom_taxonomies' => "",
    'show_price_range' => "on",
    'show_rating' => "on",
    'show_search' => "on",
    'show_brand' => "",
    'show_author' => "",
    'show_status' => "",
    'show_onsale' => "",
    'show_dimension' => "",
    'show_sku' => "",
    'show_discount' => "",
    'show_date_filter' => "",
    'use_url_filter' => 'query_string',
    'update_filter_options' => "on",
    'show_loader' => "on",
    'pages' => [],
    'loader_html' => '<div id="loader" style="display:none;"></div>',
    'loader_css' => '#loader { width: 56px; height: 56px; border-radius: 50%; -webkit-mask: radial-gradient(farthest-side,#0000 calc(100% - 9px),#000 0); animation: spinner-zp9dbg 1s infinite linear; } @keyframes spinner-zp9dbg { to { transform: rotate(1turn); } }',
    'use_custom_template' => 0,
    'custom_template_code' => '',
    'product_selector' => '.products',
    'pagination_selector' => '.woocommerce-pagination',
    'filters_word_in_permalinks' => 'filters',
];
$dapfforwc_advance_settings = get_option('dapfforwc_advance_options') ?: [];
$dapfforwc_seo_permalinks_options = get_option('dapfforwc_seo_permalinks_options') ?: [];



if (!function_exists('dapfforwc_get_filter_cache_empty_data')) {
    function dapfforwc_get_filter_cache_empty_data()
    {
        return [
            'attributes' => [],
            'categories' => [],
            'tags' => [],
            'brands' => [],
            'authors' => [],
            'custom_fields' => [],
            'stock_status' => [],
            'sale_status' => [],
        ];
    }
}

if (!function_exists('dapfforwc_get_published_product_count')) {
    function dapfforwc_get_published_product_count()
    {
        static $count = null;

        if ($count !== null) {
            return $count;
        }

        $product_counts = wp_count_posts('product');
        $count = isset($product_counts->publish) ? (int) $product_counts->publish : 0;

        return $count;
    }
}

if (!function_exists('dapfforwc_get_large_catalog_threshold')) {
    function dapfforwc_get_large_catalog_threshold()
    {
        /**
         * Filters the product count where automatic filter cache generation moves to background batches.
         *
         * @param int $threshold Published product count threshold.
         */
        return (int) apply_filters('dapfforwc_large_catalog_product_threshold', 5000);
    }
}

if (!function_exists('dapfforwc_is_large_catalog')) {
    function dapfforwc_is_large_catalog()
    {
        $threshold = dapfforwc_get_large_catalog_threshold();

        return $threshold > 0 && dapfforwc_get_published_product_count() >= $threshold;
    }
}

if (!function_exists('dapfforwc_use_large_catalog_database_mode')) {
    function dapfforwc_use_large_catalog_database_mode()
    {
        /**
         * Filters whether large catalogues should use the database-safe runtime path.
         *
         * When enabled, the plugin does not build or load serialized full-catalog
         * product/term caches. Filter data is read as aggregate rows and product
         * filtering stays in the WooCommerce/WP query layer.
         *
         * @param bool $enabled Whether database-safe mode is enabled.
         * @param int  $count   Published product count.
         */
        return (bool) apply_filters(
            'dapfforwc_use_large_catalog_database_mode',
            dapfforwc_is_large_catalog(),
            dapfforwc_get_published_product_count()
        );
    }
}

if (!function_exists('dapfforwc_is_filter_option_enabled')) {
    function dapfforwc_is_filter_option_enabled($option_key, $options = null)
    {
        global $dapfforwc_options;

        $options = is_array($options) ? $options : (is_array($dapfforwc_options) ? $dapfforwc_options : []);

        return isset($options[$option_key]) && $options[$option_key] === 'on';
    }
}

if (!function_exists('dapfforwc_update_filter_cache_status')) {
    function dapfforwc_update_filter_cache_status($status, $message = '', $extra = [])
    {
        $payload = wp_parse_args(
            is_array($extra) ? $extra : [],
            [
                'status' => sanitize_key($status),
                'message' => sanitize_text_field($message),
                'product_count' => function_exists('dapfforwc_get_published_product_count') ? dapfforwc_get_published_product_count() : 0,
                'updated_at' => time(),
            ]
        );

        update_option('dapfforwc_filter_cache_status', $payload, false);
    }
}

if (!function_exists('dapfforwc_is_filter_cache_build_locked')) {
    function dapfforwc_is_filter_cache_build_locked()
    {
        $lock = get_option('dapfforwc_filter_cache_build_lock', []);
        if (!is_array($lock) || empty($lock['started_at'])) {
            return false;
        }

        $started_at = (int) $lock['started_at'];

        return $started_at > 0 && (time() - $started_at) < (30 * MINUTE_IN_SECONDS);
    }
}

if (!function_exists('dapfforwc_start_filter_cache_build')) {
    function dapfforwc_start_filter_cache_build($manual = false)
    {
        if (!$manual && dapfforwc_is_filter_cache_build_locked()) {
            return false;
        }

        update_option(
            'dapfforwc_filter_cache_build_lock',
            [
                'started_at' => time(),
                'manual' => $manual ? 1 : 0,
            ],
            false
        );

        dapfforwc_update_filter_cache_status(
            'running',
            $manual ? 'Manual filter cache build is running.' : 'Automatic filter cache build is running.',
            ['manual' => $manual ? 1 : 0]
        );

        if (!has_action('shutdown', 'dapfforwc_filter_cache_shutdown_guard')) {
            add_action('shutdown', 'dapfforwc_filter_cache_shutdown_guard');
        }

        return true;
    }
}

if (!function_exists('dapfforwc_finish_filter_cache_build')) {
    function dapfforwc_finish_filter_cache_build($status, $message = '', $extra = [])
    {
        delete_option('dapfforwc_filter_cache_build_lock');
        dapfforwc_update_filter_cache_status($status, $message, $extra);
    }
}

if (!function_exists('dapfforwc_filter_cache_shutdown_guard')) {
    function dapfforwc_filter_cache_shutdown_guard()
    {
        if (!dapfforwc_is_filter_cache_build_locked()) {
            return;
        }

        $error = error_get_last();
        $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

        if (is_array($error) && in_array((int) $error['type'], $fatal_types, true)) {
            dapfforwc_finish_filter_cache_build(
                'failed',
                'Filter cache build stopped before completion. Automatic retry is paused to prevent repeated fatal errors.',
                [
                    'error' => sanitize_text_field($error['message'] ?? ''),
                    'file' => sanitize_text_field($error['file'] ?? ''),
                    'line' => isset($error['line']) ? (int) $error['line'] : 0,
                ]
            );
        }
    }
}

if (!function_exists('dapfforwc_get_filter_cache_build_timeout')) {
    function dapfforwc_get_filter_cache_build_timeout()
    {
        /**
         * Filters how long a background filter-cache build may stay active before a new build can replace it.
         *
         * @param int $timeout Timeout in seconds.
         */
        return (int) apply_filters('dapfforwc_filter_cache_build_timeout', 6 * HOUR_IN_SECONDS);
    }
}

if (!function_exists('dapfforwc_get_filter_cache_stale_after')) {
    function dapfforwc_get_filter_cache_stale_after()
    {
        /**
         * Filters how long a background cache run may show no progress before it needs attention.
         *
         * @param int $stale_after Stale threshold in seconds.
         */
        return max(5 * MINUTE_IN_SECONDS, (int) apply_filters('dapfforwc_filter_cache_stale_after', 30 * MINUTE_IN_SECONDS));
    }
}

if (!function_exists('dapfforwc_get_background_cache_batch_delay')) {
    function dapfforwc_get_background_cache_batch_delay($context = 'filter')
    {
        $context = sanitize_key($context);
        $product_count = function_exists('dapfforwc_get_published_product_count') ? dapfforwc_get_published_product_count() : 0;

        if ($product_count >= 20000) {
            $delay = 15;
        } elseif ($product_count >= 5000) {
            $delay = 10;
        } else {
            $delay = 3;
        }

        /**
         * Filters the delay before the next background cache batch is scheduled.
         *
         * A positive delay prevents Action Scheduler from processing every generated
         * batch inside one web request, which is the main 504 risk on large stores.
         *
         * @param int    $delay         Delay in seconds.
         * @param string $context       Cache context.
         * @param int    $product_count Published product count.
         */
        $delay = (int) apply_filters('dapfforwc_background_cache_batch_delay', $delay, $context, $product_count);

        return max(1, min(300, $delay));
    }
}

if (!function_exists('dapfforwc_get_filter_cache_paused_statuses')) {
    function dapfforwc_get_filter_cache_paused_statuses()
    {
        return ['failed', 'needs_attention', 'paused'];
    }
}

if (!function_exists('dapfforwc_is_filter_cache_rebuild_paused')) {
    function dapfforwc_is_filter_cache_rebuild_paused($state = null)
    {
        $state = is_array($state) ? $state : dapfforwc_get_filter_cache_build_state();
        $status = isset($state['status']) ? sanitize_key($state['status']) : '';

        return in_array($status, dapfforwc_get_filter_cache_paused_statuses(), true);
    }
}

if (!function_exists('dapfforwc_is_background_cache_state_stale')) {
    function dapfforwc_is_background_cache_state_stale($state)
    {
        if (!is_array($state)) {
            return false;
        }

        $status = isset($state['status']) ? sanitize_key($state['status']) : '';
        if (!in_array($status, ['queued', 'running'], true)) {
            return false;
        }

        $updated_at = isset($state['updated_at']) ? (int) $state['updated_at'] : 0;
        if ($updated_at <= 0) {
            return false;
        }

        return (time() - $updated_at) > dapfforwc_get_filter_cache_stale_after();
    }
}

if (!function_exists('dapfforwc_get_filter_cache_build_state')) {
    function dapfforwc_get_filter_cache_build_state()
    {
        $state = get_option(DAPFFORWC_FILTER_CACHE_BUILD_STATE_OPTION, []);

        return is_array($state) ? $state : [];
    }
}

if (!function_exists('dapfforwc_is_filter_cache_background_build_active')) {
    function dapfforwc_is_filter_cache_background_build_active($state = null)
    {
        $state = is_array($state) ? $state : dapfforwc_get_filter_cache_build_state();
        $status = isset($state['status']) ? sanitize_key($state['status']) : '';

        if (!in_array($status, ['queued', 'running'], true)) {
            return false;
        }

        $updated_at = isset($state['updated_at']) ? (int) $state['updated_at'] : 0;
        $started_at = isset($state['started_at']) ? (int) $state['started_at'] : $updated_at;
        $last_seen = max($updated_at, $started_at);

        if ($last_seen <= 0) {
            return false;
        }

        return (time() - $last_seen) < dapfforwc_get_filter_cache_build_timeout();
    }
}

if (!function_exists('dapfforwc_get_cache_batch_args')) {
    function dapfforwc_get_cache_batch_args($build_id)
    {
        $build_id = is_scalar($build_id) ? sanitize_text_field((string) $build_id) : '';

        return $build_id !== '' ? [$build_id] : [];
    }
}

if (!function_exists('dapfforwc_is_plugin_active_for_background_jobs')) {
    function dapfforwc_is_plugin_active_for_background_jobs()
    {
        $plugin_file = defined('DAPFFORWC_PLUGIN_BASE_NAME') ? DAPFFORWC_PLUGIN_BASE_NAME : plugin_basename(__FILE__);

        if (!function_exists('is_plugin_active') || !function_exists('is_plugin_active_for_network')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network($plugin_file)) {
            return true;
        }

        return function_exists('is_plugin_active') && is_plugin_active($plugin_file);
    }
}

if (!function_exists('dapfforwc_schedule_filter_cache_batch')) {
    function dapfforwc_schedule_filter_cache_batch($delay = 0, $build_id = '', $force = false)
    {
        $delay = max(0, (int) $delay);
        $build_id = is_scalar($build_id) ? sanitize_text_field((string) $build_id) : '';

        if ($force && !dapfforwc_is_plugin_active_for_background_jobs()) {
            return false;
        }

        if ($build_id === '') {
            $state = dapfforwc_get_filter_cache_build_state();
            $build_id = isset($state['build_id']) ? sanitize_text_field((string) $state['build_id']) : '';
        }

        $args = dapfforwc_get_cache_batch_args($build_id);

        if (!$force && function_exists('as_next_scheduled_action') && as_next_scheduled_action(DAPFFORWC_FILTER_CACHE_BATCH_HOOK, $args, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP)) {
            return true;
        }

        if (function_exists('as_enqueue_async_action') && $delay === 0) {
            return (bool) as_enqueue_async_action(DAPFFORWC_FILTER_CACHE_BATCH_HOOK, $args, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP);
        }

        if (function_exists('as_schedule_single_action')) {
            return (bool) as_schedule_single_action(time() + $delay, DAPFFORWC_FILTER_CACHE_BATCH_HOOK, $args, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP);
        }

        if ($force || !wp_next_scheduled(DAPFFORWC_FILTER_CACHE_BATCH_HOOK, $args)) {
            return (bool) wp_schedule_single_event(time() + $delay, DAPFFORWC_FILTER_CACHE_BATCH_HOOK, $args);
        }

        return true;
    }
}

if (!function_exists('dapfforwc_schedule_filter_cache_rebuild')) {
    function dapfforwc_schedule_filter_cache_rebuild($reason = 'automatic', $force = false)
    {
        if (function_exists('dapfforwc_use_large_catalog_database_mode') && dapfforwc_use_large_catalog_database_mode()) {
            dapfforwc_update_filter_cache_status(
                'database_mode',
                'Large catalogue detected. Full serialized filter cache rebuild is skipped because database-safe automatic mode is active.',
                [
                    'reason' => sanitize_key($reason),
                    'mode' => 'database_safe',
                ]
            );
            return false;
        }

        $state = dapfforwc_get_filter_cache_build_state();

        if (!$force && dapfforwc_is_filter_cache_rebuild_paused($state)) {
            dapfforwc_update_filter_cache_status(
                'needs_attention',
                'Automatic filter cache rebuild is paused after a failed or stalled background run. Review the cache status in plugin settings before retrying.',
                [
                    'reason' => sanitize_key($reason),
                    'paused' => 1,
                ]
            );
            return false;
        }

        if (!$force && dapfforwc_is_filter_cache_background_build_active($state)) {
            $build_id = isset($state['build_id']) ? sanitize_text_field((string) $state['build_id']) : '';
            dapfforwc_schedule_filter_cache_batch(dapfforwc_get_background_cache_batch_delay('filter'), $build_id);
            return true;
        }

        if ($force) {
            delete_option(DAPFFORWC_FILTER_CACHE_BUILD_DATA_OPTION);
        }

        $product_count = function_exists('dapfforwc_get_published_product_count') ? dapfforwc_get_published_product_count() : 0;
        $build_id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : md5(uniqid('dapfforwc', true));

        $state = [
            'build_id' => $build_id,
            'status' => 'queued',
            'reason' => sanitize_key($reason),
            'last_id' => 0,
            'processed' => 0,
            'product_count' => $product_count,
            'started_at' => time(),
            'updated_at' => time(),
        ];

        update_option(DAPFFORWC_FILTER_CACHE_BUILD_STATE_OPTION, $state, false);
        dapfforwc_update_filter_cache_status(
            'queued',
            'Automatic filter cache rebuild is queued and will run in background batches.',
            [
                'processed' => 0,
                'reason' => sanitize_key($reason),
            ]
        );

        return dapfforwc_schedule_filter_cache_batch(dapfforwc_get_background_cache_batch_delay('filter'), $build_id);
    }
}

if (!function_exists('dapfforwc_get_product_details_cache_build_state')) {
    function dapfforwc_get_product_details_cache_build_state()
    {
        $state = get_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_STATE_OPTION, []);

        return is_array($state) ? $state : [];
    }
}

if (!function_exists('dapfforwc_is_product_details_cache_background_build_active')) {
    function dapfforwc_is_product_details_cache_background_build_active($state = null)
    {
        $state = is_array($state) ? $state : dapfforwc_get_product_details_cache_build_state();
        $status = isset($state['status']) ? sanitize_key($state['status']) : '';

        if (!in_array($status, ['queued', 'running'], true)) {
            return false;
        }

        $updated_at = isset($state['updated_at']) ? (int) $state['updated_at'] : 0;
        $started_at = isset($state['started_at']) ? (int) $state['started_at'] : $updated_at;
        $last_seen = max($updated_at, $started_at);

        if ($last_seen <= 0) {
            return false;
        }

        return (time() - $last_seen) < dapfforwc_get_filter_cache_build_timeout();
    }
}

if (!function_exists('dapfforwc_schedule_product_details_cache_batch')) {
    function dapfforwc_schedule_product_details_cache_batch($delay = 0, $build_id = '', $force = false)
    {
        $delay = max(0, (int) $delay);
        $build_id = is_scalar($build_id) ? sanitize_text_field((string) $build_id) : '';

        if ($force && !dapfforwc_is_plugin_active_for_background_jobs()) {
            return false;
        }

        if ($build_id === '') {
            $state = dapfforwc_get_product_details_cache_build_state();
            $build_id = isset($state['build_id']) ? sanitize_text_field((string) $state['build_id']) : '';
        }

        $args = dapfforwc_get_cache_batch_args($build_id);

        if (!$force && function_exists('as_next_scheduled_action') && as_next_scheduled_action(DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK, $args, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP)) {
            return true;
        }

        if (function_exists('as_enqueue_async_action') && $delay === 0) {
            return (bool) as_enqueue_async_action(DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK, $args, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP);
        }

        if (function_exists('as_schedule_single_action')) {
            return (bool) as_schedule_single_action(time() + $delay, DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK, $args, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP);
        }

        if ($force || !wp_next_scheduled(DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK, $args)) {
            return (bool) wp_schedule_single_event(time() + $delay, DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK, $args);
        }

        return true;
    }
}

if (!function_exists('dapfforwc_schedule_product_details_cache_rebuild')) {
    function dapfforwc_schedule_product_details_cache_rebuild($reason = 'automatic', $force = false)
    {
        if (function_exists('dapfforwc_use_large_catalog_database_mode') && dapfforwc_use_large_catalog_database_mode()) {
            return false;
        }

        $state = dapfforwc_get_product_details_cache_build_state();

        if (!$force && dapfforwc_is_filter_cache_rebuild_paused(dapfforwc_get_filter_cache_build_state())) {
            return false;
        }

        if (!$force && dapfforwc_is_product_details_cache_background_build_active($state)) {
            $build_id = isset($state['build_id']) ? sanitize_text_field((string) $state['build_id']) : '';
            dapfforwc_schedule_product_details_cache_batch(dapfforwc_get_background_cache_batch_delay('product_details'), $build_id);
            return true;
        }

        if ($force) {
            delete_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_DATA_OPTION);
        }

        $product_count = function_exists('dapfforwc_get_published_product_count') ? dapfforwc_get_published_product_count() : 0;
        $build_id = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : md5(uniqid('dapfforwc-product-details', true));
        $state = [
            'build_id' => $build_id,
            'status' => 'queued',
            'reason' => sanitize_key($reason),
            'last_id' => 0,
            'processed' => 0,
            'product_count' => $product_count,
            'started_at' => time(),
            'updated_at' => time(),
        ];

        update_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_STATE_OPTION, $state, false);

        return dapfforwc_schedule_product_details_cache_batch(dapfforwc_get_background_cache_batch_delay('product_details'), $build_id);
    }
}

if (!function_exists('dapfforwc_unschedule_cache_batch_hook')) {
    function dapfforwc_unschedule_cache_batch_hook($hook)
    {
        $hook = sanitize_key($hook);

        if ($hook === '') {
            return;
        }

        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions($hook, null, DAPFFORWC_FILTER_CACHE_QUEUE_GROUP);
        }

        if (function_exists('wp_unschedule_hook')) {
            wp_unschedule_hook($hook);
            return;
        }

        if (function_exists('_get_cron_array')) {
            $cron = _get_cron_array();
            if (is_array($cron)) {
                foreach ($cron as $timestamp => $events) {
                    if (empty($events[$hook]) || !is_array($events[$hook])) {
                        continue;
                    }

                    foreach ($events[$hook] as $event) {
                        $args = isset($event['args']) && is_array($event['args']) ? $event['args'] : [];
                        wp_unschedule_event((int) $timestamp, $hook, $args);
                    }
                }
            }
            return;
        }

        while ($timestamp = wp_next_scheduled($hook)) {
            wp_unschedule_event($timestamp, $hook);
        }
    }
}

if (!function_exists('dapfforwc_unschedule_filter_cache_background_jobs')) {
    function dapfforwc_unschedule_filter_cache_background_jobs()
    {
        dapfforwc_unschedule_cache_batch_hook(DAPFFORWC_FILTER_CACHE_BATCH_HOOK);
        dapfforwc_unschedule_cache_batch_hook(DAPFFORWC_PRODUCT_DETAILS_CACHE_BATCH_HOOK);
    }
}

if (!function_exists('dapfforwc_cancel_filter_cache_background_jobs')) {
    function dapfforwc_cancel_filter_cache_background_jobs($reason = 'cancelled')
    {
        $filter_state = dapfforwc_get_filter_cache_build_state();
        $details_state = dapfforwc_get_product_details_cache_build_state();
        $product_count = 0;

        if (isset($filter_state['product_count'])) {
            $product_count = (int) $filter_state['product_count'];
        } elseif (isset($details_state['product_count'])) {
            $product_count = (int) $details_state['product_count'];
        }

        dapfforwc_unschedule_filter_cache_background_jobs();

        delete_option('dapfforwc_filter_cache_build_lock');
        delete_option(DAPFFORWC_FILTER_CACHE_BUILD_DATA_OPTION);
        delete_option(DAPFFORWC_FILTER_CACHE_BUILD_STATE_OPTION);
        delete_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_DATA_OPTION);
        delete_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_STATE_OPTION);

        update_option(
            'dapfforwc_filter_cache_status',
            [
                'status' => 'cancelled',
                'message' => 'Background filter cache build was cancelled because the plugin was deactivated.',
                'product_count' => $product_count,
                'reason' => sanitize_key($reason),
                'updated_at' => time(),
            ],
            false
        );
    }
}

if (!function_exists('dapfforwc_should_skip_automatic_filter_cache_build')) {
    function dapfforwc_should_skip_automatic_filter_cache_build($manual = false)
    {
        if ($manual) {
            return false;
        }

        if (dapfforwc_is_filter_cache_rebuild_paused()) {
            return true;
        }

        if (dapfforwc_is_filter_cache_build_locked() || dapfforwc_is_filter_cache_background_build_active()) {
            $state = dapfforwc_get_filter_cache_build_state();
            $build_id = isset($state['build_id']) ? sanitize_text_field((string) $state['build_id']) : '';
            dapfforwc_schedule_filter_cache_batch(dapfforwc_get_background_cache_batch_delay('filter'), $build_id);
            return true;
        }

        if (function_exists('dapfforwc_use_large_catalog_database_mode') && dapfforwc_use_large_catalog_database_mode()) {
            dapfforwc_update_filter_cache_status(
                'database_mode',
                'Large catalogue detected. Full serialized filter caches are skipped and filter data is loaded through the database-safe automatic mode.',
                ['mode' => 'database_safe']
            );
            return true;
        }

        if (dapfforwc_is_large_catalog()) {
            dapfforwc_schedule_filter_cache_rebuild('cache_miss');
            return true;
        }

        return false;
    }
}

if (!function_exists('dapfforwc_get_registered_attribute_prefix_options')) {
    function dapfforwc_get_registered_attribute_prefix_options($exclude_attributes = [])
    {
        $exclude_attributes = is_array($exclude_attributes) ? array_map('sanitize_key', $exclude_attributes) : [];
        $attributes = [];

        if (function_exists('wc_get_attribute_taxonomies')) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            if (is_array($attribute_taxonomies)) {
                foreach ($attribute_taxonomies as $attribute) {
                    $attribute_name = isset($attribute->attribute_name) ? sanitize_key($attribute->attribute_name) : '';
                    if ($attribute_name === '' || in_array($attribute_name, $exclude_attributes, true)) {
                        continue;
                    }

                    $attributes[] = (object) [
                        'attribute_name' => $attribute_name,
                        'attribute_label' => isset($attribute->attribute_label) ? $attribute->attribute_label : $attribute_name,
                    ];
                }
            }
        }

        return $attributes;
    }
}

if (!function_exists('dapfforwc_get_seo_permalink_default_options')) {
    function dapfforwc_get_seo_permalink_default_options($advance_options = [])
    {
        $advance_options = is_array($advance_options) ? $advance_options : [];
        $exclude_attributes = isset($advance_options['exclude_attributes']) && $advance_options['exclude_attributes'] !== ''
            ? array_filter(array_map('trim', explode(',', (string) $advance_options['exclude_attributes'])))
            : [];

        $attributes = dapfforwc_get_registered_attribute_prefix_options($exclude_attributes);

        return [
            'use_attribute_type_in_permalinks' => "on",
            'dapfforwc_permalinks_prefix_options' => [
                "product-category" => 'cata',
                'tag' => 'tags',
                'attribute' => !empty($attributes) ? array_reduce($attributes, function ($carry, $attr) {
                    $carry[$attr->attribute_name] = $attr->attribute_name;
                    return $carry;
                }, []) : [
                    'color' => 'color',
                    'size' => 'size',
                    'brand' => 'brand',
                    'material' => 'material',
                    'style' => 'style',
                ],
                'custom' => [],
                'price' => 'price',
                'rating' => 'rating',
                'brand' => 'brand',
                'author' => 'author',
                'stock_status' => 'stockStatus',
                'sale_status' => 'saleStatus',
                'width' => 'width',
                'min_width' => 'min_width',
                'max_width' => 'max_width',
                'length' => 'length',
                'height' => 'height',
                'min_height' => 'min_height',
                'max_height' => 'max_height',
                'weight' => 'weight',
                'min_weight' => 'min_weight',
                'max_weight' => 'max_weight',
                'sku' => 'sku',
                'discount' => 'discount',
                'date_filter' => 'date',
                'pagination' => 'paged',
                'plugincy_search' => 'title',
                'orderby' => 'orderby',
            ],
            'filters_word_in_permalinks' => 'filters',
            'use_filters_word_in_permalinks' => '',
            'use_anchor' => 0,
        ];
    }
}

if (!function_exists('dapfforwc_check_seo_settings')) {
    function dapfforwc_check_seo_settings()
    {
        global $dapfforwc_seo_permalinks_options, $dapfforwc_advance_settings;

        // Define default SEO permalink options
        $default_seo_permalinks_options = dapfforwc_get_seo_permalink_default_options($dapfforwc_advance_settings);

        // Merge with existing options and check for missing keys
        if (!empty($dapfforwc_seo_permalinks_options)) {
            // Check top-level keys
            foreach ($default_seo_permalinks_options as $key => $default_value) {
                if (!isset($dapfforwc_seo_permalinks_options[$key])) {
                    $dapfforwc_seo_permalinks_options[$key] = $default_value;
                } elseif ($key === 'dapfforwc_permalinks_prefix_options' && is_array($default_value)) {
                    // Check nested keys in dapfforwc_permalinks_prefix_options
                    if (!is_array($dapfforwc_seo_permalinks_options[$key])) {
                        $dapfforwc_seo_permalinks_options[$key] = $default_value;
                    } else {
                        foreach ($default_value as $nested_key => $nested_default) {
                            if (!isset($dapfforwc_seo_permalinks_options[$key][$nested_key])) {
                                $dapfforwc_seo_permalinks_options[$key][$nested_key] = $nested_default;
                            }
                        }
                    }
                }
            }
            // Update the option if any keys were missing
            update_option('dapfforwc_seo_permalinks_options', $dapfforwc_seo_permalinks_options);
        } else {
            // If option doesn't exist, use defaults and save
            $dapfforwc_seo_permalinks_options = $default_seo_permalinks_options;
            update_option('dapfforwc_seo_permalinks_options', $dapfforwc_seo_permalinks_options);
        }
    }
}

$dapfforwc_styleoptions = get_option('dapfforwc_style_options') ?: [];

$dapfforwc_use_url_filter = isset($dapfforwc_options['use_url_filter']) ? $dapfforwc_options['use_url_filter'] : false;
$dapfforwc_auto_detect_pages_filters = isset($dapfforwc_options['pages_filter_auto']) ? $dapfforwc_options['pages_filter_auto'] : '';
$dapfforwc_slug = "";

// Get the ID of the front page

$dapfforwc_front_page_id = get_option('page_on_front') ?: null;

// Get the front page object
$dapfforwc_front_page = isset($dapfforwc_front_page_id) ? get_post($dapfforwc_front_page_id) : null;
// Get the slug of the front page
$dapfforwc_front_page_slug = isset($dapfforwc_front_page) ? $dapfforwc_front_page->post_name : "";

if (!function_exists('dapfforwc_get_mobile_breakpoint')) {
    /**
     * Returns the configured mobile breakpoint or the default fallback.
     *
     * @return int
     */
    function dapfforwc_get_mobile_breakpoint()
    {
        global $dapfforwc_advance_settings;

        $breakpoint = isset($dapfforwc_advance_settings['mobile_breakpoint'])
            ? absint($dapfforwc_advance_settings['mobile_breakpoint'])
            : 0;

        return $breakpoint > 0 ? $breakpoint : 768;
    }
}

$dapfforwc_allowed_tags = array(
    'a' => array(
        'href' => array(),
        'title' => array(),
        'class' => array(),
        'target' => array(), // Allow target attribute for links
        'rel' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'strong' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'em' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'li' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'div' => array(
        'class' => array(),
        'id' => array(), // Allow id for divs
        'style' => array(),
    ),
    'img' => array(
        'src' => array(),
        'alt' => array(),
        'class' => array(),
        'width' => array(), // Allow width attribute
        'height' => array(), // Allow height attribute
        'style' => array(),
        'id' => array(),
        'data-src' => array(),
    ),
    'h1' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow h1
    'h2' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'h3' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow h3
    'h4' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow h4
    'h5' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow h5
    'h6' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow h6
    'span' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
        'title' => array(),

    ),
    'p' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
        'title' => array(),
    ),
    'br' => array(
        'style' => array(),
        'class' => array(),
    ), // Allow line breaks
    'blockquote' => array(
        'cite' => array(), // Allow cite attribute for blockquotes
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'table' => array(
        'class' => array(),
        'style' => array(), // Allow inline styles
        'id' => array(),
    ),
    'tr' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'td' => array(
        'class' => array(),
        'colspan' => array(), // Allow colspan attribute
        'rowspan' => array(), // Allow rowspan attribute
        'style' => array(),
        'id' => array(),
    ),
    'th' => array(
        'class' => array(),
        'colspan' => array(),
        'rowspan' => array(),
        'style' => array(),
        'id' => array(),
    ),
    'ul' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow unordered lists
    'ol' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
    ), // Allow ordered lists
    'script' => array(
        'type' => array(),
        'src' => array(),
        'async' => array(),
        'defer' => array(),
        'charset' => array(),
    ), // Be cautious with scripts

    // Style and Meta Tags
    'style' => array(
        'type' => array(),
        'media' => array(),
        'scoped' => array(),
    ),
    'link' => array(
        'rel' => array(),
        'href' => array(),
        'type' => array(),
        'media' => array(),
        'sizes' => array(),
        'hreflang' => array(),
        'crossorigin' => array(),
    ),
    'meta' => array(
        'name' => array(),
        'content' => array(),
        'http-equiv' => array(),
        'charset' => array(),
        'property' => array(), // For Open Graph
    ),
    'title' => array(),
    'base' => array(
        'href' => array(),
        'target' => array(),
    ),

    // Document Structure
    'html' => array(
        'lang' => array(),
        'dir' => array(),
        'class' => array(),
        'style' => array(),
    ),
    'head' => array(),
    'body' => array(
        'class' => array(),
        'id' => array(),
        'style' => array(),
        'onload' => array(),
    ),
    'header' => array(
        'class' => array(),
        'id' => array(),
        'style' => array(),
        'role' => array(),
    ),
    'footer' => array(
        'class' => array(),
        'id' => array(),
        'style' => array(),
        'role' => array(),
    ),
    'nav' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
        'role' => array(),
    ),
    'main' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
        'role' => array(),
    ),
    'section' => array(
        'class' => array(),
        'id' => array(),
        'style' => array(),
        'role' => array(),
    ),
    'article' => array(
        'class' => array(),
        'style' => array(),
        'id' => array(),
        'role' => array(),
    ),
    'aside' => array(
        'class' => array(),
        'id' => array(),
        'style' => array(),
        'role' => array(),
    ),

    // Form Elements
    'form' => array(
        'action' => array(),
        'method' => array(),
        'style' => array(),
        'enctype' => array(),
        'target' => array(),
        'name' => array(),
        'id' => array(),
        'class' => array(),
        'autocomplete' => array(),
        'novalidate' => array(),
        'data-mobile-style' => array(),
        'data-product_show_settings' => array(),
        'data-product_selector' => array(),
        'data-pagination_selector' => array(),
        'data-layout' => array(),
    ),
    'input' => array(
        'type' => array(),
        'name' => array(),
        'value' => array(),
        'style' => array(),
        'placeholder' => array(),
        'id' => array(),
        'class' => array(),
        'required' => array(),
        'disabled' => array(),
        'readonly' => array(),
        'checked' => array(),
        'selected' => array(),
        'multiple' => array(),
        'min' => array(),
        'max' => array(),
        'step' => array(),
        'pattern' => array(),
        'maxlength' => array(),
        'minlength' => array(),
        'size' => array(),
        'autocomplete' => array(),
        'autofocus' => array(),
        'form' => array(),
        'formaction' => array(),
        'formmethod' => array(),
        'formtarget' => array(),
        'formnovalidate' => array(),
        'accept' => array(),
        'alt' => array(),
        'src' => array(),
        'width' => array(),
        'height' => array(),
        'title' => array(),
    ),
    'textarea' => array(
        'name' => array(),
        'id' => array(),
        'class' => array(),
        'placeholder' => array(),
        'rows' => array(),
        'style' => array(),
        'cols' => array(),
        'required' => array(),
        'disabled' => array(),
        'readonly' => array(),
        'maxlength' => array(),
        'minlength' => array(),
        'wrap' => array(),
        'autocomplete' => array(),
        'autofocus' => array(),
        'form' => array(),
    ),
    'select' => array(
        'name' => array(),
        'id' => array(),
        'class' => array(),
        'multiple' => array(),
        'size' => array(),
        'required' => array(),
        'style' => array(),
        'disabled' => array(),
        'autofocus' => array(),
        'form' => array(),
    ),
    'option' => array(
        'value' => array(),
        'selected' => array(),
        'style' => array(),
        'disabled' => array(),
        'label' => array(),
    ),
    'optgroup' => array(
        'label' => array(),
        'style' => array(),
        'disabled' => array(),
    ),
    'button' => array(
        'type' => array(),
        'name' => array(),
        'value' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
        'disabled' => array(),
        'form' => array(),
        'formaction' => array(),
        'formmethod' => array(),
        'formtarget' => array(),
        'formnovalidate' => array(),
        'autofocus' => array(),
    ),
    'label' => array(
        'for' => array(),
        'form' => array(),
        'id' => array(),
        'class' => array(),
        'style' => array(),
    ),
    'fieldset' => array(
        'disabled' => array(),
        'form' => array(),
        'style' => array(),
        'name' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'legend' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'datalist' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'output' => array(
        'for' => array(),
        'form' => array(),
        'name' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'plugrogress' => array(
        'value' => array(),
        'max' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'meter' => array(
        'value' => array(),
        'min' => array(),
        'max' => array(),
        'low' => array(),
        'style' => array(),
        'high' => array(),
        'optimum' => array(),
        'id' => array(),
        'class' => array(),
    ),

    // Media Elements
    'audio' => array(
        'src' => array(),
        'controls' => array(),
        'autoplay' => array(),
        'style' => array(),
        'loop' => array(),
        'muted' => array(),
        'preload' => array(),
        'crossorigin' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'video' => array(
        'src' => array(),
        'controls' => array(),
        'autoplay' => array(),
        'loop' => array(),
        'muted' => array(),
        'preload' => array(),
        'style' => array(),
        'poster' => array(),
        'width' => array(),
        'height' => array(),
        'crossorigin' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'source' => array(
        'src' => array(),
        'style' => array(),
        'type' => array(),
        'media' => array(),
        'sizes' => array(),
        'srcset' => array(),
    ),
    'track' => array(
        'kind' => array(),
        'src' => array(),
        'style' => array(),
        'srclang' => array(),
        'label' => array(),
        'default' => array(),
    ),
    'embed' => array(
        'src' => array(),
        'type' => array(),
        'width' => array(),
        'height' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'object' => array(
        'data' => array(),
        'type' => array(),
        'style' => array(),
        'name' => array(),
        'width' => array(),
        'height' => array(),
        'form' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'param' => array(
        'name' => array(),
        'value' => array(),
        'style' => array(),
    ),
    'iframe' => array(
        'src' => array(),
        'srcdoc' => array(),
        'name' => array(),
        'width' => array(),
        'style' => array(),
        'height' => array(),
        'sandbox' => array(),
        'allow' => array(),
        'allowfullscreen' => array(),
        'loading' => array(),
        'id' => array(),
        'class' => array(),
    ),

    // Interactive Elements
    'details' => array(
        'open' => array(),
        'id' => array(),
        'class' => array(),
        'style' => array(),
    ),
    'summary' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'dialog' => array(
        'open' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),

    // Text Content Elements
    'pre' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'code' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'kbd' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'samp' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'var' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'small' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'sub' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'sup' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'mark' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'del' => array(
        'datetime' => array(),
        'style' => array(),
        'cite' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'ins' => array(
        'datetime' => array(),
        'style' => array(),
        'cite' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'q' => array(
        'cite' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'cite' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'abbr' => array(
        'title' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'dfn' => array(
        'title' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'time' => array(
        'datetime' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'data' => array(
        'value' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'address' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Table Elements (Enhanced)
    'caption' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'thead' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'tbody' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'tfoot' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'colgroup' => array(
        'span' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),
    'col' => array(
        'span' => array(),
        'style' => array(),
        'id' => array(),
        'class' => array(),
    ),

    // Definition Lists
    'dl' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'dt' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'dd' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Ruby Annotations
    'ruby' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'rt' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'rp' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Bidirectional Text
    'bdi' => array(
        'dir' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'bdo' => array(
        'dir' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Web Components
    'template' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'slot' => array(
        'name' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Math and Science
    'math' => array(
        'display' => array(),
        'xmlns' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Canvas and Graphics
    'canvas' => array(
        'width' => array(),
        'height' => array(),
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),

    // Obsolete but sometimes needed
    'center' => array(
        'id' => array(),
        'style' => array(),
        'class' => array(),
    ),
    'font' => array(
        'size' => array(),
        'style' => array(),
        'color' => array(),
        'face' => array(),
        'id' => array(),
        'class' => array(),
    ),

    // SVG Tags
    'svg' => array(
        'xmlns' => array(),
        'viewbox' => array(), // lowercase
        'viewBox' => array(), // camelCase (standard)
        'width' => array(),
        'height' => array(),
        'class' => array(),
        'id' => array(),
        'style' => array(),
        'preserveAspectRatio' => array(),
        'version' => array(),
        'x' => array(),
        'y' => array(),
        'fill' => array(),
    ),
    'g' => array(
        'class' => array(),
        'id' => array(),
        'transform' => array(),
        'style' => array(),
        'fill' => array(),
        'stroke' => array(),
        'opacity' => array(),
    ),
    'path' => array(
        'd' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'stroke-dasharray' => array(),
        'stroke-linecap' => array(),
        'stroke-linejoin' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'circle' => array(
        'cx' => array(),
        'cy' => array(),
        'r' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'ellipse' => array(
        'cx' => array(),
        'cy' => array(),
        'rx' => array(),
        'ry' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'rect' => array(
        'x' => array(),
        'y' => array(),
        'width' => array(),
        'height' => array(),
        'rx' => array(),
        'ry' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'line' => array(
        'x1' => array(),
        'y1' => array(),
        'x2' => array(),
        'y2' => array(),
        'class' => array(),
        'id' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'stroke-dasharray' => array(),
        'stroke-linecap' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'polyline' => array(
        'points' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'stroke-dasharray' => array(),
        'stroke-linecap' => array(),
        'stroke-linejoin' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'polygon' => array(
        'points' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'stroke-width' => array(),
        'stroke-dasharray' => array(),
        'stroke-linecap' => array(),
        'stroke-linejoin' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'text' => array(
        'x' => array(),
        'y' => array(),
        'dx' => array(),
        'dy' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'font-family' => array(),
        'font-size' => array(),
        'font-weight' => array(),
        'text-anchor' => array(),
        'dominant-baseline' => array(),
        'opacity' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'tspan' => array(
        'x' => array(),
        'y' => array(),
        'dx' => array(),
        'dy' => array(),
        'class' => array(),
        'id' => array(),
        'fill' => array(),
        'stroke' => array(),
        'font-family' => array(),
        'font-size' => array(),
        'font-weight' => array(),
        'text-anchor' => array(),
        'dominant-baseline' => array(),
        'opacity' => array(),
        'style' => array(),
    ),
    'use' => array(
        'href' => array(),
        'xlink:href' => array(),
        'x' => array(),
        'y' => array(),
        'width' => array(),
        'height' => array(),
        'class' => array(),
        'id' => array(),
        'transform' => array(),
        'style' => array(),
    ),
    'defs' => array(
        'class' => array(),
        'id' => array(),
        'style' => array(),
    ),
    'symbol' => array(
        'id' => array(),
        'viewBox' => array(),
        'class' => array(),
        'style' => array(),
        'preserveAspectRatio' => array(),
    ),
    'marker' => array(
        'id' => array(),
        'markerWidth' => array(),
        'markerHeight' => array(),
        'refX' => array(),
        'refY' => array(),
        'style' => array(),
        'orient' => array(),
        'markerUnits' => array(),
        'class' => array(),
    ),
    'linearGradient' => array(
        'id' => array(),
        'x1' => array(),
        'y1' => array(),
        'style' => array(),
        'x2' => array(),
        'y2' => array(),
        'gradientUnits' => array(),
        'gradientTransform' => array(),
        'class' => array(),
    ),
    'lineargradient' => array(
        'id' => array(),
        'x1' => array(),
        'y1' => array(),
        'style' => array(),
        'x2' => array(),
        'y2' => array(),
        'gradientUnits' => array(),
        'gradientTransform' => array(),
        'class' => array(),
    ),
    'radialGradient' => array(
        'id' => array(),
        'cx' => array(),
        'cy' => array(),
        'style' => array(),
        'r' => array(),
        'fx' => array(),
        'fy' => array(),
        'gradientUnits' => array(),
        'gradientTransform' => array(),
        'class' => array(),
    ),
    'radialgradient' => array(
        'id' => array(),
        'cx' => array(),
        'cy' => array(),
        'r' => array(),
        'style' => array(),
        'fx' => array(),
        'fy' => array(),
        'gradientUnits' => array(),
        'gradientTransform' => array(),
        'class' => array(),
    ),
    'stop' => array(
        'offset' => array(),
        'stop-color' => array(),
        'stop-opacity' => array(),
        'class' => array(),
        'style' => array(),
    ),
    'clipPath' => array(
        'id' => array(),
        'class' => array(),
        'style' => array(),
        'clipPathUnits' => array(),
    ),
    'mask' => array(
        'id' => array(),
        'class' => array(),
        'style' => array(),
        'maskUnits' => array(),
        'maskContentUnits' => array(),
        'x' => array(),
        'y' => array(),
        'width' => array(),
        'height' => array(),
    ),
    'pattern' => array(
        'id' => array(),
        'x' => array(),
        'y' => array(),
        'width' => array(),
        'style' => array(),
        'height' => array(),
        'patternUnits' => array(),
        'patternContentUnits' => array(),
        'patternTransform' => array(),
        'viewBox' => array(),
        'class' => array(),
    ),
    'filter' => array(
        'id' => array(),
        'x' => array(),
        'y' => array(),
        'style' => array(),
        'width' => array(),
        'height' => array(),
        'filterUnits' => array(),
        'primitiveUnits' => array(),
        'class' => array(),
    ),
    'feGaussianBlur' => array(
        'in' => array(),
        'style' => array(),
        'stdDeviation' => array(),
        'result' => array(),
    ),
    'feOffset' => array(
        'in' => array(),
        'dx' => array(),
        'style' => array(),
        'dy' => array(),
        'result' => array(),
    ),
    'feDropShadow' => array(
        'dx' => array(),
        'dy' => array(),
        'style' => array(),
        'stdDeviation' => array(),
        'flood-color' => array(),
        'flood-opacity' => array(),
    ),
    'image' => array(
        'x' => array(),
        'y' => array(),
        'width' => array(),
        'style' => array(),
        'height' => array(),
        'href' => array(),
        'xlink:href' => array(),
        'preserveAspectRatio' => array(),
        'class' => array(),
        'id' => array(),
        'opacity' => array(),
        'transform' => array(),
    ),
);


// Allow extensive CSS properties for WordPress
add_filter('safe_style_css', function ($styles) {
    return array_merge($styles, array(
        // Layout & Positioning
        'display',
        'visibility',
        'opacity',
        'position',
        'top',
        'right',
        'bottom',
        'left',
        'z-index',
        'float',
        'clear',
        'clip',
        'clip-path',

        // Box Model
        'width',
        'height',
        'max-width',
        'max-height',
        'min-width',
        'min-height',
        'box-sizing',
        'aspect-ratio',

        // Margins & Padding
        'margin',
        'margin-top',
        'margin-right',
        'margin-bottom',
        'margin-left',
        'margin-block',
        'margin-inline',
        'margin-block-start',
        'margin-block-end',
        'margin-inline-start',
        'margin-inline-end',
        'padding',
        'padding-top',
        'padding-right',
        'padding-bottom',
        'padding-left',
        'padding-block',
        'padding-inline',
        'padding-block-start',
        'padding-block-end',
        'padding-inline-start',
        'padding-inline-end',

        // Borders
        'border',
        'border-top',
        'border-right',
        'border-bottom',
        'border-left',
        'border-width',
        'border-top-width',
        'border-right-width',
        'border-bottom-width',
        'border-left-width',
        'border-style',
        'border-top-style',
        'border-right-style',
        'border-bottom-style',
        'border-left-style',
        'border-color',
        'border-top-color',
        'border-right-color',
        'border-bottom-color',
        'border-left-color',
        'border-radius',
        'border-top-left-radius',
        'border-top-right-radius',
        'border-bottom-left-radius',
        'border-bottom-right-radius',
        'border-image',
        'border-image-source',
        'border-image-slice',
        'border-image-width',
        'border-image-outset',
        'border-image-repeat',
        'border-collapse',
        'border-spacing',

        // Background
        'background',
        'background-color',
        'background-image',
        'background-position',
        'background-position-x',
        'background-position-y',
        'background-repeat',
        'background-size',
        'background-attachment',
        'background-origin',
        'background-clip',
        'background-blend-mode',

        // Typography
        'color',
        'font',
        'font-family',
        'font-size',
        'font-weight',
        'font-style',
        'font-variant',
        'font-stretch',
        'font-display',
        'font-feature-settings',
        'font-variation-settings',
        'line-height',
        'letter-spacing',
        'word-spacing',
        'text-align',
        'text-align-last',
        'text-decoration',
        'text-decoration-line',
        'text-decoration-color',
        'text-decoration-style',
        'text-decoration-thickness',
        'text-transform',
        'text-indent',
        'text-shadow',
        'text-overflow',
        'text-rendering',
        'white-space',
        'word-wrap',
        'word-break',
        'overflow-wrap',
        'hyphens',
        'writing-mode',
        'text-orientation',
        'direction',
        'unicode-bidi',

        // List Styles
        'list-style',
        'list-style-type',
        'list-style-position',
        'list-style-image',

        // Table Styles
        'table-layout',
        'caption-side',
        'empty-cells',

        // Positioning & Alignment
        'vertical-align',
        'object-fit',
        'object-position',

        // Overflow & Scrolling
        'overflow',
        'overflow-x',
        'overflow-y',
        'overflow-anchor',
        'overscroll-behavior',
        'overscroll-behavior-x',
        'overscroll-behavior-y',
        'scroll-behavior',
        'scroll-margin',
        'scroll-padding',
        'scroll-snap-type',
        'scroll-snap-align',

        // Flexbox
        'flex',
        'flex-direction',
        'flex-wrap',
        'flex-flow',
        'justify-content',
        'align-items',
        'align-content',
        'align-self',
        'order',
        'flex-grow',
        'flex-shrink',
        'flex-basis',

        // Grid
        'grid',
        'grid-template',
        'grid-template-columns',
        'grid-template-rows',
        'grid-template-areas',
        'grid-auto-columns',
        'grid-auto-rows',
        'grid-auto-flow',
        'grid-column',
        'grid-column-start',
        'grid-column-end',
        'grid-row',
        'grid-row-start',
        'grid-row-end',
        'grid-area',
        'gap',
        'row-gap',
        'column-gap',
        'grid-gap',
        'grid-row-gap',
        'grid-column-gap',
        'justify-items',
        'justify-self',
        'place-items',
        'place-self',
        'place-content',

        // Transforms & Animations
        'transform',
        'transform-origin',
        'transform-style',
        'transform-box',
        'perspective',
        'perspective-origin',
        'backface-visibility',
        'transition',
        'transition-property',
        'transition-duration',
        'transition-timing-function',
        'transition-delay',
        'animation',
        'animation-name',
        'animation-duration',
        'animation-timing-function',
        'animation-delay',
        'animation-iteration-count',
        'animation-direction',
        'animation-fill-mode',
        'animation-play-state',

        // Visual Effects
        'box-shadow',
        'filter',
        'backdrop-filter',
        'mix-blend-mode',
        'isolation',
        'outline',
        'outline-color',
        'outline-style',
        'outline-width',
        'outline-offset',
        'resize',
        'cursor',
        'pointer-events',
        'user-select',
        'touch-action',

        // Print Styles
        'page-break-before',
        'page-break-after',
        'page-break-inside',
        'break-before',
        'break-after',
        'break-inside',

        // Logical Properties (modern CSS)
        'block-size',
        'inline-size',
        'min-block-size',
        'min-inline-size',
        'max-block-size',
        'max-inline-size',
        'inset',
        'inset-block',
        'inset-inline',
        'inset-block-start',
        'inset-block-end',
        'inset-inline-start',
        'inset-inline-end',

        // Container Queries
        'container-type',
        'container-name',
        'container',

        // Content & Generated Content
        'content',
        'quotes',
        'counter-reset',
        'counter-increment',

        // Miscellaneous
        'all',
        'contain',
        'will-change',
        'appearance',
        'caret-color',
        'tab-size',
        'column-count',
        'column-width',
        'column-gap',
        'column-rule',
        'column-rule-color',
        'column-rule-style',
        'column-rule-width',
        'column-span',
        'column-fill',
        'columns',

        // svg style
        'stop-color',
        'stop-opacity',
        'fill'

    ));
});


// Define sub-options
$dapfforwc_sub_options = [
    'checkbox' => [
        'checkbox' => 'Checkbox',
        'radio_check' => 'Radio Check',
        'radio' => 'Radio',
        'square' => 'Square',
        'checkbox_hide' => 'Checkbox Hide',
    ],
    'plugincy_color' => [
        'plugincy_color' => 'Color',
        'color_no_border' => 'Color Without Border',
        'color_circle' => 'Color Circle',
        'color_value' => 'Color With Value',
        'color_swatch_label' => 'Color Swatch Label',
    ],
    'image' => [
        'image' => 'Image',
        'image_no_border' => 'Image Without Border',
        'image_value' => 'Image With Value',
    ],
    'dropdown' => [
        'select' => 'Select',
        'pluginy_select2' => 'Select 2',
        'chip_dropdown' => 'Chip Dropdown',
        'modern_dropdown' => 'Modern Dropdown',
        'checkbox_dropdown' => 'Checkbox Dropdown',
        // 'select2_classic' => 'Select 2 Classic',
    ],
    'icon' => [
        'icon_card' => 'Icon Card',
        'icon_list' => 'Icon List',
        'icon_chip' => 'Icon Chip',
        'icon_badge' => 'Icon Badge',
        'icon_only' => 'Icon Only',
    ],
    'chips' => [
        'chips' => 'Chips',
        'button_chips' => 'Button Chips',
        'button_check' => 'Button Checkbox',
    ],
    'stepper' => [
        'stepper' => 'Stepper',
        'boxed-stepper' => 'Boxed Stepper',
        'compact-stepper' => 'Compact Stepper',
        'pill-stepper' => 'Pill Stepper',
    ],
    'price' => [
        'price' => 'Price',
        'slider' => 'Slider',
        'slider2' => 'Slider2',
        'chart-slider' => 'Chart Slider',
        'price-range-button' => 'Range Button',
        'price-range-card' => 'Range Card',
        'input-price-range' => 'input price range',
    ],
    'rating' => [
        'rating' => 'Rating Star',
        'rating-text' => 'Rating Text',
        'dynamic-rating' => 'Dynamic Rating',
        'rating-slider' => 'Rating Slider',
    ],
    "plugincy_search" => [
        "plugincy_search" => 'Search with Button',
        "icon_search" => 'Search with Icon'
    ],
    "dimension_input" => [
        "dimension_input" => 'Dimension Input',
        'dimension_range' => 'Dimension Range',
        'dimensions-compact-range-card' => 'Compact Range Card',
    ],
    "discount_slider" => [
        "discount_slider" => 'Discount Slider',
    ],
    "simple_input" => [
        "simple_input" => 'Input',
        'sku_search_button' => 'Search Button',
        'sku_icon_search' => 'Icon Search',
    ],
    'date_range' => [
        'date-modern-calendar' => 'Modern Calendar',
        'date-classic-select' => 'Classic Select',
        'date-quick-picks' => 'Quick Picks',
        'date-dual-calendar' => 'Dual Calendar',
        'date-stacked-list' => 'Stacked List',
    ],
];

// Check if WooCommerce is active
add_action('plugins_loaded', 'dapfforwc_check_woocommerce');

function dapfforwc_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'dapfforwc_missing_woocommerce_notice');
    } else {
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'admin/admin-notice.php';
            require_once(plugin_dir_path(__FILE__) . 'includes/get_review.php');
            require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
        }
        require_once plugin_dir_path(__FILE__) . 'includes/filter-template.php';
        require_once plugin_dir_path(__FILE__) . 'admin/license-page.php';
        // require_once plugin_dir_path(__FILE__) . 'includes/class-inject.php';
        require_once plugin_dir_path(__FILE__) . 'admin/elementor-category.php';


        add_action('wp_enqueue_scripts', 'dapfforwc_enqueue_scripts');
        add_action('admin_enqueue_scripts', 'dapfforwc_admin_scripts');
        require_once plugin_dir_path(__FILE__) . 'includes/class-filter-functions.php';

        // add_action('wp_ajax_dapfforwc_filter_products', 'dapfforwc_filter_products');
        // add_action('wp_ajax_nopriv_dapfforwc_filter_products', 'dapfforwc_filter_products');

        register_setting('dapfforwc_options_group', 'dapfforwc_filters', 'sanitize_text_field');

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dapfforwc_add_settings_link');
        require_once plugin_dir_path(__FILE__) . 'includes/common-functions.php';

        // filter error detector
        add_action('admin_bar_menu', 'dapfforwc_add_debug_menu', 100);

        dapfforwc_check_seo_settings();
    }
}

function dapfforwc_missing_woocommerce_notice()
{
    echo '<div class="notice notice-error"><p><strong>' . esc_html__('Filter Plugin', 'dynamic-ajax-product-filters-for-woocommerce') . '</strong> ' . esc_html__('requires WooCommerce to be installed and activated.', 'dynamic-ajax-product-filters-for-woocommerce') . '</p></div>';
}

function dapfforwc_get_asset_version()
{
    return defined('DAPFFORWC_VERSION') ? DAPFFORWC_VERSION : false;
}

function dapfforwc_get_frontend_style_handle()
{
    return defined('DAPFFORWC_FRONTEND_STYLE_HANDLE') ? DAPFFORWC_FRONTEND_STYLE_HANDLE : 'dapfforwc-filter-style';
}

function dapfforwc_get_frontend_style_src()
{
    return plugin_dir_url(__FILE__) . 'assets/css/style.min.css';
}

function dapfforwc_register_frontend_style()
{
    $handle = dapfforwc_get_frontend_style_handle();
    $src = dapfforwc_get_frontend_style_src();
    $styles = wp_styles();

    if (isset($styles->registered[$handle])) {
        $styles->registered[$handle]->src = $src;
        $styles->registered[$handle]->deps = [];
        $styles->registered[$handle]->ver = dapfforwc_get_asset_version();
        $styles->registered[$handle]->args = 'all';
        return;
    }

    wp_register_style($handle, $src, [], dapfforwc_get_asset_version());
}

function dapfforwc_ensure_style_handle($handle)
{
    if ($handle === 'filter-style') {
        $handle = dapfforwc_get_frontend_style_handle();
    }

    if ($handle === dapfforwc_get_frontend_style_handle()) {
        dapfforwc_register_frontend_style();
    } elseif (!wp_style_is($handle, 'registered') && !wp_style_is($handle, 'enqueued') && !wp_style_is($handle, 'done')) {
        wp_register_style($handle, false, [], dapfforwc_get_asset_version());
    }

    if (!wp_style_is($handle, 'enqueued') && !wp_style_is($handle, 'done')) {
        wp_enqueue_style($handle);
    }
}

function dapfforwc_ensure_script_handle($handle)
{
    if (!wp_script_is($handle, 'registered') && !wp_script_is($handle, 'enqueued') && !wp_script_is($handle, 'done')) {
        wp_register_script($handle, false, ['jquery'], dapfforwc_get_asset_version(), true);
    }

    if (!wp_script_is($handle, 'enqueued') && !wp_script_is($handle, 'done')) {
        wp_enqueue_script($handle);
    }
}

function dapfforwc_print_late_inline_style($handle)
{
    $is_fragment_request = function_exists('dapfforwc_is_fragment_request') && dapfforwc_is_fragment_request();
    if (!is_admin() && !wp_doing_ajax() && !$is_fragment_request) {
        wp_print_styles([$handle]);
        return;
    }

    $footer_hook = is_admin() ? 'admin_footer' : 'wp_footer';

    if (did_action($footer_hook)) {
        wp_print_styles([$handle]);
        return;
    }

    add_action(
        $footer_hook,
        static function () use ($handle) {
            wp_print_styles([$handle]);
        },
        9999
    );
}

function dapfforwc_print_late_inline_script($handle)
{
    $footer_hook = is_admin() ? 'admin_footer' : 'wp_footer';

    if (did_action($footer_hook)) {
        wp_print_scripts([$handle]);
        return;
    }

    add_action(
        $footer_hook,
        static function () use ($handle) {
            wp_print_scripts([$handle]);
        },
        9999
    );
}

function dapfforwc_add_inline_style($css, $handle = '')
{
    $css = trim((string) $css);
    if ($css === '') {
        return;
    }

    $handle = $handle !== '' ? $handle : (is_admin() ? 'dapfforwc-admin-menu-style' : dapfforwc_get_frontend_style_handle());
    if ($handle === 'filter-style') {
        $handle = dapfforwc_get_frontend_style_handle();
    }
    dapfforwc_ensure_style_handle($handle);

    if (wp_style_is($handle, 'done')) {
        $late_handle = $handle . '-inline-' . md5($css);
        wp_register_style($late_handle, false, [], dapfforwc_get_asset_version());
        wp_enqueue_style($late_handle);
        wp_add_inline_style($late_handle, $css);
        dapfforwc_print_late_inline_style($late_handle);
        return;
    }

    wp_add_inline_style($handle, $css);
}

function dapfforwc_add_inline_script($script, $handle = '', $position = 'after')
{
    $script = trim((string) $script);
    if ($script === '') {
        return;
    }

    if (strpos($script, '"use strict"') === false && strpos($script, "'use strict'") === false) {
        $script = '"use strict";' . "\n" . $script;
    }

    $handle = $handle !== '' ? $handle : (is_admin() ? 'dapfforwc-admin-menu-script' : 'urlfilter-ajax');
    dapfforwc_ensure_script_handle($handle);

    if (wp_script_is($handle, 'done')) {
        $late_handle = $handle . '-inline-' . md5($script);
        wp_register_script($late_handle, false, ['jquery'], dapfforwc_get_asset_version(), true);
        wp_enqueue_script($late_handle);
        wp_add_inline_script($late_handle, $script, $position);
        dapfforwc_print_late_inline_script($late_handle);
        return;
    }

    wp_add_inline_script($handle, $script, $position);
}

// Enqueue scripts and styles
function dapfforwc_enqueue_scripts()
{
    global $dapfforwc_use_url_filter, $dapfforwc_options, $dapfforwc_seo_permalinks_options, $dapfforwc_slug, $dapfforwc_styleoptions, $dapfforwc_advance_settings, $dapfforwc_front_page_slug;

    $script_handle = 'urlfilter-ajax';
    $script_path = 'assets/js/filter.min.js';
    $mobile_breakpoint = dapfforwc_get_mobile_breakpoint();
    $dapfforwc_advance_settings['mobile_breakpoint'] = $mobile_breakpoint;

    wp_enqueue_script('jquery');
    wp_enqueue_script($script_handle, plugin_dir_url(__FILE__) . $script_path, ['jquery'], DAPFFORWC_VERSION, true);
    wp_script_add_data($script_handle, 'async', true); // Load script asynchronously

    $dapfforwc_frontend_options = [
        'use_url_filter' => $dapfforwc_options['use_url_filter'] ?? $dapfforwc_use_url_filter,
        'update_filter_options' => $dapfforwc_options['update_filter_options'] ?? '',
        'show_loader' => $dapfforwc_options['show_loader'] ?? '',
    ];

    $dapfforwc_frontend_prefix_options = $dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options'] ?? [];
    if (is_array($dapfforwc_frontend_prefix_options)) {
        if (!dapfforwc_is_filter_option_enabled('show_categories', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['product-category']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_tags', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['tag']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_price_range', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['price']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_rating', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['rating']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_brand', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['brand']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_author', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['author']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_status', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['stock_status']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_onsale', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['sale_status']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_attributes', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['attribute']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_custom_fields', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['custom']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_dimension', $dapfforwc_options)) {
            unset(
                $dapfforwc_frontend_prefix_options['width'],
                $dapfforwc_frontend_prefix_options['min_width'],
                $dapfforwc_frontend_prefix_options['max_width'],
                $dapfforwc_frontend_prefix_options['length'],
                $dapfforwc_frontend_prefix_options['min_length'],
                $dapfforwc_frontend_prefix_options['max_length'],
                $dapfforwc_frontend_prefix_options['height'],
                $dapfforwc_frontend_prefix_options['min_height'],
                $dapfforwc_frontend_prefix_options['max_height'],
                $dapfforwc_frontend_prefix_options['weight'],
                $dapfforwc_frontend_prefix_options['min_weight'],
                $dapfforwc_frontend_prefix_options['max_weight']
            );
        }
        if (!dapfforwc_is_filter_option_enabled('show_sku', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['sku']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_discount', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['discount']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_date_filter', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['date_filter']);
        }
        if (!dapfforwc_is_filter_option_enabled('show_search', $dapfforwc_options)) {
            unset($dapfforwc_frontend_prefix_options['plugincy_search']);
        }
    } else {
        $dapfforwc_frontend_prefix_options = [];
    }

    $dapfforwc_frontend_seo_options = [
        'use_attribute_type_in_permalinks' => $dapfforwc_seo_permalinks_options['use_attribute_type_in_permalinks'] ?? '',
        'dapfforwc_permalinks_prefix_options' => $dapfforwc_frontend_prefix_options,
    ];

    $dapfforwc_frontend_styleoptions = [
        'show_in_active_filters' => $dapfforwc_styleoptions['show_in_active_filters'] ?? [],
        'apply_behavior' => $dapfforwc_styleoptions['apply_behavior'] ?? [],
        'show_apply_button' => $dapfforwc_styleoptions['show_apply_button'] ?? [],
        'show_apply_reset_on' => $dapfforwc_styleoptions['show_apply_reset_on'] ?? [],
    ];

    $dapfforwc_frontend_advance_settings = [
        'product_selector' => $dapfforwc_advance_settings['product_selector'] ?? '',
        'pagination_selector' => $dapfforwc_advance_settings['pagination_selector'] ?? '',
        'sorting_selector' => $dapfforwc_advance_settings['sorting_selector'] ?? '',
        'result_count_selector' => $dapfforwc_advance_settings['result_count_selector'] ?? '',
        'advanced_pagination_enabled' => $dapfforwc_advance_settings['advanced_pagination_enabled'] ?? '',
        'advanced_pagination_mode' => $dapfforwc_advance_settings['advanced_pagination_mode'] ?? '',
        'advanced_pagination_prev_selector' => $dapfforwc_advance_settings['advanced_pagination_prev_selector'] ?? '',
        'advanced_pagination_next_selector' => $dapfforwc_advance_settings['advanced_pagination_next_selector'] ?? '',
        'advanced_pagination_load_more_selector' => $dapfforwc_advance_settings['advanced_pagination_load_more_selector'] ?? '',
        'advanced_pagination_infinite_scroll_selector' => $dapfforwc_advance_settings['advanced_pagination_infinite_scroll_selector'] ?? '',
        'no_products_text' => $dapfforwc_advance_settings['no_products_text'] ?? '',
        'select2_placeholder' => $dapfforwc_advance_settings['select2_placeholder'] ?? '',
        'mobile_breakpoint' => $mobile_breakpoint,
    ];

    $dapfforwc_localized_data = array(
        'dapfforwc_options' => $dapfforwc_frontend_options,
        'dapfforwc_seo_permalinks_options' => $dapfforwc_frontend_seo_options,
        'dapfforwc_slug' => $dapfforwc_slug,
        'dapfforwc_styleoptions' => $dapfforwc_frontend_styleoptions,
        'dapfforwc_advance_settings' => $dapfforwc_frontend_advance_settings,
        'dapfforwc_front_page_slug' => $dapfforwc_front_page_slug,
        'mobile_breakpoint' => $mobile_breakpoint,
    );
    wp_localize_script($script_handle, 'dapfforwc_data', $dapfforwc_localized_data);

    wp_localize_script($script_handle, 'dapfforwc_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'shopPageUrl' => esc_url(get_permalink(get_option('woocommerce_shop_page_id'))),
        'isProductArchive' =>  is_shop() || is_product_category() || is_product_tag() || is_product(),
        'currencySymbol' => get_woocommerce_currency_symbol(),
        'isHomePage' => is_front_page()
    ]);

    dapfforwc_register_frontend_style();
    wp_enqueue_style(dapfforwc_get_frontend_style_handle());
    wp_enqueue_style('select2-css', plugin_dir_url(__FILE__) . 'assets/css/select2.min.css', [], DAPFFORWC_VERSION);
    wp_enqueue_script('select2-js', plugin_dir_url(__FILE__) . 'assets/js/select2.min.js', ['jquery'], DAPFFORWC_VERSION, true);
    $css = '';
    // Generate inline css for sidebartop in mobile
    if (isset($dapfforwc_advance_settings["sidebar_on_top"]) && $dapfforwc_advance_settings["sidebar_on_top"] === "on") {
        $css .= "@media (max-width: {$mobile_breakpoint}px) {
                    div#content>div {
                        flex-direction: column !important;
                    }
        }";
    }
    // Override default mobile padding for filter container with dynamic breakpoint
    $css .= "@media (max-width: {$mobile_breakpoint}px) {
        .mobile-filter .rfilterselected,
        .mobile-filter #product-filter {
            padding-left: 10px;
            padding-right: 10px;
        }
    }";
    // Generate CSS for max-height

    $max_height = (is_array($dapfforwc_styleoptions) && isset($dapfforwc_styleoptions["max_height"])) ? $dapfforwc_styleoptions["max_height"] : [];
    foreach ($max_height as $key => $value) {
        // Sanitize the key to create a valid CSS class name
        if (is_numeric($value) && $value > 0) {
            if ($key === "product-category") {
                $key = "category";
            } elseif ($key === "plugincy_rating") {
                $key = "rating";
            }
            $cssClass = strtolower($key); // Replace dashes with underscores
            $css .= ".{$cssClass} .items{\n";
            $css .= "    max-height: {$value}px;\n"; // Set max-height based on value
            $css .= "    overflow-y: auto;\n";
            $css .= "    scrollbar-width: thin;\n";
            $css .= "    transition: max-height 0.3s ease;\n";
            $css .= "}\n";
        }
    }

    // Add the generated CSS as inline style
    wp_add_inline_style(dapfforwc_get_frontend_style_handle(), $css);
    wp_add_inline_script('select2-js', '
        
    ');
}

function dapfforwc_admin_scripts($hook)
{
    // Enqueue global admin menu styles for all admin pages
    wp_enqueue_style(
        'dapfforwc-admin-menu-style',
        plugin_dir_url(__FILE__) . 'assets/css/admin-menu.min.css',
        [],
        DAPFFORWC_VERSION,
        'all'
    );

    wp_enqueue_script('dapfforwc-admin-menu-script', plugin_dir_url(__FILE__) . 'assets/js/admin-menu-script.min.js', [], DAPFFORWC_VERSION, true);
    wp_localize_script('dapfforwc-admin-menu-script', 'dapfforwcAdminAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'review_nonce' => wp_create_nonce('dapfforwc_review_nonce'),
        'deactivation_feedback_nonce' => wp_create_nonce('deactivation_feedback'),
        'dismiss_ny_notice_nonce' => wp_create_nonce('dapfforwc_dismiss_ny_notice'),
        'slug_check_nonce' => wp_create_nonce('dapfforwc_slug_check_notice'),
    ]);

    if ($hook !== 'toplevel_page_dapfforwc-admin') {
        return; // Load additional styles only on the plugin's admin page
    }
    global $dapfforwc_sub_options;
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    $dapfforwc_admin_style_version = filemtime(plugin_dir_path(__FILE__) . 'assets/css/admin-style.min.css');
    $dapfforwc_media_uploader_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/media-uploader.min.js');
    wp_enqueue_style('dapfforwc-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.min.css', [], $dapfforwc_admin_style_version);
    wp_enqueue_code_editor(array('type' => 'text/html'));
    wp_enqueue_script('wp-theme-plugin-editor');
    wp_enqueue_style('wp-codemirror');
    wp_enqueue_script('dapfforwc-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.min.js', [], DAPFFORWC_VERSION, true);
    wp_enqueue_media();
    wp_enqueue_script('dapfforwc-media-uploader', plugin_dir_url(__FILE__) . 'assets/js/media-uploader.min.js', ['jquery'], $dapfforwc_media_uploader_version, true);


    wp_enqueue_style('pluginy-select2-css', plugin_dir_url(__FILE__) . 'assets/css/select2.min.css', [], DAPFFORWC_VERSION);
    wp_enqueue_script('pluginy-select2-js', plugin_dir_url(__FILE__) . 'assets/js/select2.min.js', ['jquery'], DAPFFORWC_VERSION, true);


    $inline_script = 'document.addEventListener("DOMContentLoaded", function () {
    const styleContainer = document.getElementById("style-options-container");
    if (styleContainer && styleContainer.dataset && styleContainer.dataset.dynamic === "true") {
        return;
    }
    const dropdown = document.getElementById("attribute-dropdown");
    const dropdown_main = document.getElementById("main-texonomy-dropdown");
    const dropdown_attr = document.getElementById("child-attr-dropdown");
    const dropdown_custom = document.getElementById("child-custom-dropdown");

    if(dropdown)dropdown.addEventListener("change", function () {
    const selectedAttribute = this.value;
    
    localStorage.setItem("dapfforwc_selected_attribute", JSON.stringify({ "attribute": selectedAttribute }));

    toggleDisplay(".style-options", "none");

    if (selectedAttribute) {
        const selectedOptions = document.getElementById(`options-${selectedAttribute}`);
        if (selectedOptions) {
            selectedOptions.style.display = "block";
        }
    }

    if (selectedAttribute === "price") {
        toggleDisplay(".primary_options label", "none");
        toggleDisplay(".hierarchical", "none");
        toggleDisplay(".primary_options label.price", "block");
        toggleDisplay(".primary_options label.rating", "none");
        toggleDisplay(".setting-item.single-selection", "none");
        toggleDisplay(".setting-item.show-product-count", "none");
    }
    else if (selectedAttribute === "rating") {
        toggleDisplay(".hierarchical", "none");
        toggleDisplay(".primary_options label", "none");
        toggleDisplay(".primary_options label.price", "none");
        toggleDisplay(".primary_options label.rating", "block");
        toggleDisplay(".setting-item.single-selection", "none");
        toggleDisplay(".setting-item.show-product-count", "none");
    } else if(selectedAttribute === "product-category"){
        toggleDisplay(".hierarchical", "block");
        toggleDisplay(".primary_options label", "block");
        toggleDisplay(".primary_options label.price", "none");
        toggleDisplay(".primary_options label.rating", "none");
        toggleDisplay(".setting-item.show-product-count", "block");
        toggleDisplay(".primary_options label.plugincy_color", "none");
        toggleDisplay(".primary_options label.image", "none");
    }else if(selectedAttribute === "tag"){
        toggleDisplay(".hierarchical", "none");
        toggleDisplay(".primary_options label", "block");
        toggleDisplay(".primary_options label.price", "none");
        toggleDisplay(".primary_options label.rating", "none");
        toggleDisplay(".setting-item.show-product-count", "block");
        toggleDisplay(".primary_options label.plugincy_color", "none");
        toggleDisplay(".primary_options label.image", "none");
    }
    else {
        toggleDisplay(".hierarchical", "none");
        toggleDisplay(".primary_options label", "block");
        toggleDisplay(".primary_options label.price", "none");
        toggleDisplay(".primary_options label.rating", "none");
        toggleDisplay(".setting-item.single-selection", "block");
        toggleDisplay(".setting-item.show-product-count", "block");
    }
});


    let savedAttribute = localStorage.getItem("dapfforwc_selected_attribute");
           if (!savedAttribute) {
                savedAttribute = JSON.stringify({ attribute: "product-category" });
                localStorage.setItem("dapfforwc_selected_attribute", savedAttribute);
                
            }
    if (savedAttribute) {
        try {
            const parsed = JSON.parse(savedAttribute);
            if (parsed && parsed.attribute && dropdown) {
                dropdown.value = parsed.attribute;
                dropdown_main.value = parsed.attribute;
                dropdown_attr.value = parsed.attribute;
                dropdown_custom.value = parsed.attribute;
                if (dropdown_attr.value) {
                    dropdown_main.value = "attributes";
                }else if (dropdown_custom.value){
                    dropdown_main.value = "custom_fields";
                }
                // Trigger a change event on the attribute-dropdown
                const event = new Event("change", {
                    bubbles: true
                });

                toggleDisplay(".style-options", "none");
                
                dropdown_main.dispatchEvent(event);
            }
        } catch (e) {}
    }

    if(dropdown){
        const firstAttribute = dropdown.value;
        const firstOptions = document.querySelector(`#options-${firstAttribute}`);
        if (firstOptions) {
            firstOptions.style.display = "block";
        }
    }

    function toggleDisplay(selector, display) {
        document.querySelectorAll(selector).forEach(el => {
            el.style.display = display;
        });
    }

    

    const proOnlySubOptionKeys = new Set(["dynamic-rating", "rating-slider", "input-price-range", "chart-slider", "price-range-button", "price-range-card", "color_circle", "color_value", "color_swatch_label", "image_value", "button_check", "chips", "button_chips", "pill-stepper", "compact-stepper", "boxed-stepper", "stepper", "icon_only", "icon_badge", "icon_chip", "icon_list", "icon_card", "checkbox_dropdown", "modern_dropdown", "chip_dropdown", "dimension_range", "dimensions-compact-range-card", "sku_search_button", "sku_icon_search", "discount_slider", "date-modern-calendar", "date-quick-picks", "date-dual-calendar", "date-stacked-list"]);
    const subOptionImageBaseUrl = ' . wp_json_encode(plugin_dir_url(__FILE__) . 'assets/images/') . ';

    document.querySelectorAll(`.style-options .primary_options input[type="radio"][name^="dapfforwc_style_options"]`).forEach(function (radio) {
        radio.addEventListener("change", function () {
            const selectedType = this.value;
            const attributeName = this.name.match(/\[(.*?)\]/)[1];
            const subOptionsContainer = document.querySelector(`#options-${attributeName} .dynamic-sub-options`);
            if (!subOptionsContainer) {
                return;
            }
   
            document.querySelectorAll(".primary_options label").forEach(label => {
                label.classList.remove("active");
                const checkIcon = label.querySelector(".active");
                if (checkIcon) {
                    checkIcon.style.display = "none"; 
                }
            });
            const selectedLabel = radio.closest("label");
            selectedLabel.classList.add("active");

            const subOptions = ' . (isset($dapfforwc_sub_options) && is_array($dapfforwc_sub_options) ? wp_json_encode($dapfforwc_sub_options) : '[]') . ';

            const currentOptions = subOptions[selectedType] || {};
            subOptionsContainer.innerHTML = "";

            const fragment = document.createDocumentFragment();
            for (const key in currentOptions) {
                const label = document.createElement("label");
                const isProOnly = proOnlySubOptionKeys.has(key);
                label.className = key + (isProOnly ? " pro-only" : "");

                const activeSpan = document.createElement("span");
                activeSpan.className = "active";
                activeSpan.style.display = "none";
                const checkIcon = document.createElement("i");
                checkIcon.className = "fa fa-check";
                activeSpan.appendChild(checkIcon);

                const input = document.createElement("input");
                input.type = "radio";
                input.className = "optionselect";
                input.name = (isProOnly ? "_pro" : "dapfforwc_style_options") + "[" + attributeName + "][sub_option]";
                input.value = key;
                if (isProOnly) {
                    input.disabled = true;
                }

                const image = document.createElement("img");
                image.src = subOptionImageBaseUrl + key + ".png";
                image.alt = currentOptions[key] || key;

                label.appendChild(activeSpan);
                label.appendChild(input);
                label.appendChild(image);
                fragment.appendChild(label);
            }
            subOptionsContainer.appendChild(fragment);

            attachSubOptionListeners();

           if(selectedType==="plugincy_color" || selectedType==="image") {
            document.querySelector(`.advanced-options.${attributeName}`).style.display = "block";
            document.querySelector(`.advanced-options.${attributeName} .plugincy_color`).style.display = "none";
            document.querySelector(`.advanced-options.${attributeName} .image`).style.display = "none";
            document.querySelector(`.advanced-options.${attributeName} .${selectedType}`).style.display = "block";
            document.querySelector(`.setting-item.single-selection`).style.display = "block";

           }else if(selectedType==="dropdown") {
            radio.closest(".style-options").querySelector(`.setting-item.single-selection`).style.display = "none";
            document.querySelectorAll(".advanced-options").forEach(advanceoptions =>{
                advanceoptions.style.display = "none";
            })
            radio.closest(".style-options").querySelector(`.search_settings`).style.display = "none";  
            radio.closest(".style-options").querySelector(`.layout_settings`).style.display = "none";  
           } else {
            radio.closest(".style-options").querySelector(`.setting-item.single-selection`).style.display = "block";
            document.querySelectorAll(".advanced-options").forEach(advanceoptions =>{
                advanceoptions.style.display = "none";
            })
            radio.closest(".style-options").querySelector(`.search_settings`).style.display = "block";  
            radio.closest(".style-options").querySelector(`.layout_settings`).style.display = "flex";  
           }
        });
    });

    function attachSubOptionListeners() {
    const radioButtons = document.querySelectorAll(".optionselect");
    
    
    radioButtons.forEach(radio => {
        radio.addEventListener("change", function() {
            const selectedType = this.value;
            document.querySelectorAll(".dynamic-sub-options label").forEach(label => {
                label.classList.remove("active");
                const checkIcon = label.querySelector(".active");
                if (checkIcon) {
                    checkIcon.style.display = "none";
                }
            });

            const selectedLabel = this.closest("label");
            selectedLabel.classList.add("active");
            const checkIcon = selectedLabel.querySelector(".active");
            if (checkIcon) {
                checkIcon.style.display = "inline"; // Show check icon
            }

            if(selectedType==="icon_search") {
                document.querySelector(`#options-search .optional_settings .btn_text`).style.display = "none";
            }else{
                document.querySelector(`#options-search .optional_settings .btn_text`).style.display = "block";
            }

            // Managing single selection checkbox
            const singleSelectionCheckbox = this.closest(".style-options").querySelector(".setting-item.single-selection input");
            const singleSelectiondiv = this.closest(".style-options").querySelector(".setting-item.single-selection"); 
            if(singleSelectionCheckbox){
                if (this.value === "select" || selectedType==="pluginy_select2") {
                    singleSelectionCheckbox.checked = true;
                    singleSelectiondiv.style.display = "none"; // Show the checkbox
                    
                } else {
                    singleSelectionCheckbox.checked = false; // Uncheck if other options are selected
                    singleSelectiondiv.style.display = "block"; // Hide the checkbox
                }
            }
        });

        const selectedType = radio.value;
        if(selectedType==="icon_search") {
            document.querySelector(`#options-search .optional_settings .btn_text`).style.display = "none";
        } else if(selectedType==="rating-text"){
                document.querySelector(`.optional_settings .additional_txt_rating`).style.display = "block";
        } else{
            document.querySelector(`.optional_settings .additional_txt_rating`).style.display = "none";
            document.querySelector(`#options-search .optional_settings .btn_text`).style.display = "block";
        }

        if(selectedType==="select" || selectedType==="pluginy_select2"){
               document.querySelector(`.single-selection`).style.display = "none";
            }
    });
}

// Call the function to attach listeners
attachSubOptionListeners();

});';
    wp_add_inline_script('dapfforwc-admin-script', $inline_script);
}

// function dapfforwc_filter_products()
// {
//     if (class_exists('dapfforwc_Filter_Functions')) {
//         $filter = new dapfforwc_Filter_Functions();
//         $filter->process_filter();
//     } else {
//         wp_send_json_error('Filter class not found.');
//     }
// }


function dapfforwc_add_settings_link($links)
{
    if (!is_array($links)) {
        $links = [];
    }
    $old_links = $links;
    $links = [];
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=dapfforwc-admin')) . '">' . esc_html__('Settings', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>';
    $support_link = '<a href="' . esc_url("https://www.plugincy.com/support/") . '">' . esc_html__('Support', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>';
    $documentation_link = '<a href="' . esc_url("https://plugincy.com/documentations/dynamic-ajax-product-filters-for-woocommerce/") . '">' . esc_html__('Documentation', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>';
    $our_plugins_link = '<a href="' . esc_url(admin_url('admin.php?page=plugincy-plugins')) . '">' . esc_html__('Our Plugins', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>';
    $get_pro_link = '<a href="https://plugincy.com/dynamic-ajax-product-filters-for-woocommerce/" target="_blank" style="color:#d54e21;font-weight:bold;">' . esc_html__('Get Pro', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>';
    $links[] = $settings_link;
    $links[] = $support_link;
    $links[] = $documentation_link;
    $links[] = $our_plugins_link;
    $links[] = $get_pro_link;
    $links = array_merge($links, $old_links);
    return array_filter($links);
}

function dapfforwc_get_full_slug($post_id)
{
    if (empty($post_id)) {
        return ''; // Return an empty string if $post_id is not defined
    }
    $dapfforwc_slug_parts = [];
    $current_post_id = $post_id;

    while ($current_post_id) {
        $current_post = get_post($current_post_id);

        if (!$current_post) {
            break; // Exit if no post is found
        }

        // Prepend the current slug
        array_unshift($dapfforwc_slug_parts, $current_post->post_name);

        // Get the parent post ID
        $current_post_id = wp_get_post_parent_id($current_post_id);
    }

    return implode('/', $dapfforwc_slug_parts); // Combine slugs with '/'
}


require_once(plugin_dir_path(__FILE__) . 'includes/widget_design_template.php');
require_once(plugin_dir_path(__FILE__) . 'includes/blocks_widget_create.php');

// block editor script
function dapfforwc_enqueue_dynamic_ajax_filter_block_assets()
{
    wp_enqueue_script(
        'dynamic-ajax-filter-block',
        plugins_url('includes/block.min.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'includes/block.min.js'),
        true
    );

    wp_enqueue_style('custom-box-control-styles', plugin_dir_url(__FILE__) . 'assets/css/block-editor.min.css', [], DAPFFORWC_VERSION);
}
add_action('enqueue_block_editor_assets', 'dapfforwc_enqueue_dynamic_ajax_filter_block_assets');






function dapfforwc_add_debug_menu($wp_admin_bar)
{
    if (current_user_can('administrator')) {
        $args = [
            'id'    => 'dapfforwc_debug',
            'title' => '<span class="ab-icon dashicons dashicons-filter"></span><span id="dapfforwc_issue_count"></span> ' . esc_html__('Product Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            'meta'  => [
                'class' => 'dapfforwc-debug-bar',
            ],
        ];
        $wp_admin_bar->add_node($args);

        $wp_admin_bar->add_node([
            'id'     => 'dapfforwc_debug_sub',
            'parent' => 'dapfforwc_debug',
            'title'  => '<span id="dapfforwc_debug_message">' . esc_html__('Checking...', 'dynamic-ajax-product-filters-for-woocommerce') . '</span>',
            'meta'   => [
                'class' => 'ab-sub-wrapper',
            ],
        ]);
    }
}

add_action('wp_footer', 'dapfforwc_check_elements', 100); // Ensure this runs after the DOM is fully loaded

function dapfforwc_check_elements()
{
    global $dapfforwc_advance_settings;
    if (current_user_can('administrator')) {
        ob_start();
        ?>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    var debugMessage = document.getElementById('dapfforwc_debug_message');
                    var issueCount = document.getElementById('dapfforwc_issue_count');
                    if (!document.querySelector('#product-filter')) {
                        debugMessage.innerHTML = '<span style="color: red;">&#10007;</span> <?php echo esc_html__('Filter is not added', 'dynamic-ajax-product-filters-for-woocommerce'); ?>';
                        issueCount.innerHTML = '1';
                        issueCount.style.display = 'block';
                    } else if (!window.getProductSelector) {
                        debugMessage.innerHTML = '<span style="color: red;">&#10007;</span> <?php echo esc_html__('Products are not found. Add product or', 'dynamic-ajax-product-filters-for-woocommerce'); ?> <a href="https://plugincy.com/documentations/dynamic-ajax-product-filters-for-woocommerce/filters-setup/managing-selectors-in-product-filters/#product-selector-configuration" target="_blank" style="display: inline; padding: 0;"><?php echo esc_html__('change selector', 'dynamic-ajax-product-filters-for-woocommerce'); ?></a>';
                        issueCount.innerHTML = '1';
                        issueCount.style.display = 'block';
                        if (!document.getElementById('dapfforwc-popup-notification')) {
                            var popup = document.createElement('div');
                            popup.id = 'dapfforwc-popup-notification';
                            popup.innerHTML = `
                            <div style="
                                    display: flex;
                                    align-items: center;
                                    position: fixed;
                                    top: 123px;
                                    right: 10px;
                                    background: #fff;
                                    color: #222;
                                    border: 1px solid #d54e21;
                                    padding: 18px 28px;
                                    border-radius: 8px;
                                    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.13);
                                    z-index: 99999;
                                    font-size: 16px;
                                    min-width: 340px;
                                    max-width: 100%;
                                    gap: 14px;
                                    transition: opacity 0.3s;
                            ">
                                <span style="
                                    color: #d54e21;
                                    font-size: 26px;
                                    margin-right: 8px;
                                    flex-shrink: 0;
                                ">&#9888;</span>
                                <span style="flex:1;">
                                    <strong style="display:block;font-size:17px;margin-bottom:2px;">Product Selector Not Found</strong>
                                    <span>
                                        The product selector does not match any element on this page.<br>
                                        <a href="https://plugincy.com/documentations/dynamic-ajax-product-filters-for-woocommerce/filters-setup/managing-selectors-in-product-filters/#product-selector-configuration" target="_blank" style="color:#432fb8;text-decoration:underline;font-weight:500;display:inline-block;margin-top:6px;"><?php echo esc_html__('View documentation', 'dynamic-ajax-product-filters-for-woocommerce'); ?></a>
                                    </span>
                                </span>
                                <span style="
                                    margin-left: 12px;
                                    cursor: pointer;
                                    color: #d54e21;
                                    font-weight: bold;
                                    font-size: 22px;
                                    line-height: 1;
                                    transition: color 0.2s;
                                " onclick="this.closest('#dapfforwc-popup-notification').style.display='none'" title="Dismiss">&times;</span>
                            </div>
                        `;
                            document.body.appendChild(popup);
                        }
                    } else if (!document.querySelector('<?php echo esc_js(isset($dapfforwc_advance_settings["pagination_selector"]) && !empty($dapfforwc_advance_settings["pagination_selector"]) ? $dapfforwc_advance_settings["pagination_selector"] : ''); ?>') && !document.querySelector('.plugincy-filter-pagination')) {
                        debugMessage.innerHTML = '<span style="color: red;">&#10007;</span> <?php echo esc_html__('Pagination is not found', 'dynamic-ajax-product-filters-for-woocommerce'); ?> <a href="https://plugincy.com/documentations/dynamic-ajax-product-filters-for-woocommerce/filters-setup/managing-selectors-in-product-filters/#pagination-selector-configuration" target="_blank" style="display: inline; padding: 0;"><?php echo esc_html__('change selector', 'dynamic-ajax-product-filters-for-woocommerce'); ?></a>';
                        issueCount.innerHTML = '1';
                        issueCount.style.display = 'block';
                    } else {
                        debugMessage.innerHTML = '<span style="color: green;">&#10003;</span> <?php echo esc_html__('Filter working fine', 'dynamic-ajax-product-filters-for-woocommerce'); ?>';

                    }
                }, 2000);
            });
        <?php
        dapfforwc_add_inline_script(ob_get_clean(), 'urlfilter-ajax');

        ob_start();
        ?>
            ul#wp-admin-bar-dapfforwc_debug-default {
                padding: 0 !important;
                margin: 0 !important;
            }

            li#wp-admin-bar-dapfforwc_debug_sub {
                display: block !important;
                padding: 10px 5px !important;
                height: max-content;
            }
        <?php
        dapfforwc_add_inline_style(ob_get_clean(), dapfforwc_get_frontend_style_handle());
    }
}



function dapfforwc_register_api_routes()
{
    register_rest_route('dynamic-ajax-product-filters-for-woocommerce/v1', '/attributes/', array(
        'methods' => 'GET',
        'callback' => 'dapfforwc_get_product_attributes',
        // Public access is intentional for block/editor option discovery. The callback only exposes non-sensitive
        // attribute labels/slugs publicly and keeps custom field keys behind an admin/WooCommerce capability check.
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'dapfforwc_register_api_routes');

function dapfforwc_get_product_attributes()
{
    global $dapfforwc_advance_settings, $dapfforwc_options;

    // Fetch WooCommerce attribute taxonomies
    $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
    $all_attributes = isset($all_data['attributes']) ? $all_data['attributes'] : [];
    $exclude_attributes = isset($dapfforwc_advance_settings['exclude_attributes'])
        ? array_filter(array_map('sanitize_key', explode(',', (string) $dapfforwc_advance_settings['exclude_attributes'])))
        : [];
    $exclude_custom_fields = isset($dapfforwc_advance_settings['exclude_custom_fields'])
        ? array_filter(array_map('sanitize_key', explode(',', (string) $dapfforwc_advance_settings['exclude_custom_fields'])))
        : [];
    $custom_fields = isset($all_data['custom_fields']) ? $all_data['custom_fields'] : [];
    $result = [];

    foreach ($all_attributes as $attribute) {
        $attribute_name = isset($attribute['attribute_name']) ? sanitize_key($attribute['attribute_name']) : '';
        if ($attribute_name === '' || in_array($attribute_name, $exclude_attributes, true)) {
            continue;
        }
        $result[] = [
            'name' => isset($attribute['attribute_label']) ? sanitize_text_field($attribute['attribute_label']) : $attribute_name,
            'slug' => $attribute_name,
        ];
    }

    $can_view_custom_fields = dapfforwc_is_filter_option_enabled('show_custom_fields', $dapfforwc_options)
        && (current_user_can('manage_woocommerce') || current_user_can('manage_options'));

    if (!$can_view_custom_fields) {
        return rest_ensure_response($result);
    }

    foreach ($custom_fields as $attribute) {
        $field_name = isset($attribute['name']) ? sanitize_key($attribute['name']) : '';
        if ($field_name === '' || strpos($field_name, '_') === 0 || in_array($field_name, $exclude_custom_fields, true)) {
            continue;
        }
        $result[] = [
            'name' => isset($attribute['label']) ? sanitize_text_field($attribute['label']) : ucwords(str_replace(['_', '-'], ' ', $field_name)),
            'slug' => $field_name,
        ];
    }

    if (empty($result)) {
        return new WP_Error('no_attributes', esc_html__('No product attributes found', 'dynamic-ajax-product-filters-for-woocommerce'), array('status' => 404));
    }

    return rest_ensure_response($result);
}

function dapfforwc_replacement($current_place, $query_params, $site_title, $page_title)
{
    // New approach: Extract attribute-value pairs directly from query_params
    $formatted_pairs = [];

    // Process each query parameter as an attribute-value pair
    foreach ($query_params as $param => $value) {
        // Skip special parameters

        if ($param === 'filters' && $value === '1') {
            continue; // Skip special parameters
        }
        // Process multi-value parameters (comma-separated)
        $values = explode(',', sanitize_text_field(wp_unslash($value)));
        $formatted_values = [];

        if (strpos($current_place, '{attribute_prefix}') !== false) {

            foreach ($values as $val) {
                $formatted_values[] = str_replace('-', ' ', $val);
            }

            // Format as "attribute seperator between {attribute_prefix} & {value} value1, value2"
            if (!empty($formatted_values)) {
                preg_match('/{attribute_prefix}(.*?)\{value\}/', $current_place, $matches);
                $separator = isset($matches[1]) ? $matches[1] : '-';
                $formatted_pairs[] = $param . "{$separator}" . implode(', ', $formatted_values);
            }
        } elseif (strpos($current_place, '{value}') !== false) {
            // Format as "value1, value2"
            if (!empty($values)) {
                $formatted_pairs[] = implode(', ', $values);
            }
        }
    }

    // Combine all formatted pairs
    $formatted_string = implode(', ', $formatted_pairs);

    // Replace placeholders in SEO settings
    $replacements = [
        '{site_title}' => $site_title,
        '{page_title}' => $page_title,
        '{attribute_prefix}' => '', // No longer needed as we format differently
        '{value}' => $formatted_string // Now contains "attribute - value" format
    ];

    return $replacements;
}


function dapfforwc_block_categories($categories, $post)
{
    // Create the new category array
    $new_category = array(
        'slug' => 'plugincy',
        'title' => esc_html__('Plugincy', 'dynamic-ajax-product-filters-for-woocommerce'),
        'icon'  => 'plugincy',
    );

    // Add the new category to the beginning of the categories array
    array_unshift($categories, $new_category);

    return $categories;
}
add_filter('block_categories_all', 'dapfforwc_block_categories', 0, 2);


function dapfforwc_editor_script()
{
    if (wp_script_is('plugincy-custom-editor', 'enqueued')) {
        return;
    }
    wp_enqueue_script(
        'plugincy-custom-editor',
        plugin_dir_url(__FILE__) . 'includes/blocks/editor.js',
        array('wp-blocks', 'wp-element', 'wp-edit-post', 'wp-dom-ready', 'wp-plugins'),
        DAPFFORWC_VERSION,
        true
    );
}
add_action('enqueue_block_editor_assets', 'dapfforwc_editor_script');



if (isset($dapfforwc_advance_settings["remove_outofStock"]) && $dapfforwc_advance_settings["remove_outofStock"] === 'on') {
    // Filter products to exclude out of stock items
    add_filter('woocommerce_product_query_meta_query', function ($meta_query) {
        $meta_query[] = array(
            'key' => '_stock_status',
            'value' => 'instock',
            'compare' => '='
        );
        return $meta_query;
    });

    // Or use the built-in WooCommerce option
    add_filter('pre_option_woocommerce_hide_out_of_stock_items', function () {
        return 'yes';
    });
}










require_once plugin_dir_path(__FILE__) . 'includes/analytics.php';

class dapfforwc_cart_analytics_main
{
    private $analytics;

    public function __construct()
    {
        global $dapfforwc_advance_settings;
        $allow_data_share = isset($dapfforwc_advance_settings["allow_data_share"]) && $dapfforwc_advance_settings["allow_data_share"] === 'on';
        // Initialize analytics with the correct plugin file path
        $this->analytics = new dapfforwc_cart_anaylytics(
            '01',
            'https://plugincy.com/wp-json/product-analytics/v1',
            DAPFFORWC_VERSION,
            'Dynamic AJAX Product Filters for WooCommerce',
            __FILE__ // Pass the main plugin file
        );

        // Plugin hooks
        add_action('init', array($this, 'init'));
        if ($allow_data_share) {
            add_action('admin_init', array($this, 'admin_init'));
        }

        // Handle deactivation feedback AJAX
        add_action('wp_ajax_dapfforwc_send_deactivation_feedback', array($this, 'handle_deactivation_feedback'));
    }

    public function init()
    {
        // Any initialization code
    }

    public function admin_init()
    {
        // Send analytics data on first activation or weekly
        $this->maybe_send_analytics();
    }

    private function maybe_send_analytics()
    {
        $last_sent = get_option('dapfforwc_analytics_last_sent', get_option('onepaquc_analytics_last_sent', 0));
        $week_ago = strtotime('-1 week');

        if ($last_sent < $week_ago) {
            $this->analytics->send_tracking_data();
            update_option('dapfforwc_analytics_last_sent', time(), false);
        }
    }

    public function handle_deactivation_feedback()
    {
        global $dapfforwc_advance_settings;
        if (!isset($dapfforwc_advance_settings["allow_data_share"]) || $dapfforwc_advance_settings["allow_data_share"] !== 'on') {
            wp_send_json_error(esc_html__('Data sharing is disabled.', 'dynamic-ajax-product-filters-for-woocommerce'));
        }

        check_ajax_referer('deactivation_feedback', 'nonce');

        $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
        $this->analytics->send_deactivation_data($reason);

        wp_die();
    }
}

new dapfforwc_cart_analytics_main();

function dapfforwc_sidebar_to_top_inline_scripts()
{
    $mobile_breakpoint = dapfforwc_get_mobile_breakpoint();
    $desktop_breakpoint = $mobile_breakpoint + 1;
    ob_start();
    ?>
        /* Mobile-only Sidebar to Top CSS */
        @media (max-width: <?php echo esc_attr($mobile_breakpoint); ?>px) {
            .sidebar-moved-to-top {
                order: -1 !important;
                -webkit-box-ordinal-group: 0 !important;
                -ms-flex-order: -1 !important;
                width: 100% !important;
                margin-bottom: 20px !important;
                display: block !important;
            }

            /* Make parent container flex if it isn't already - mobile only */
            .sidebar-parent-flex {
                display: flex !important;
                flex-direction: column !important;
            }

            .sidebar-moved-to-top .widget {
                margin-bottom: 15px !important;
            }
        }

        /* Desktop - reset to normal positioning */
        @media (min-width: <?php echo esc_attr($desktop_breakpoint); ?>px) {
            .sidebar-moved-to-top {
                order: initial !important;
                -webkit-box-ordinal-group: initial !important;
                -ms-flex-order: initial !important;
                width: auto !important;
                margin-bottom: initial !important;
            }

            .sidebar-parent-flex {
                display: initial !important;
                flex-direction: initial !important;
            }
        }
    <?php
    dapfforwc_add_inline_style(ob_get_clean(), dapfforwc_get_frontend_style_handle());

    ob_start();
    ?>
        jQuery(document).ready(function($) {

            var mobileBreakpoint = <?php echo (int) $mobile_breakpoint; ?>;
            var $filterForm = $('form#product-filter');
            // Ensure jQuery is properly loaded
            if (typeof $ === 'undefined' || !$) {
                console.warn('jQuery is not properly loaded');
                return;
            }

            if ($filterForm.data("mobile-style") !== "style_1" && $filterForm.data("mobile-style") !== "style_2") {
                return;
            }

            setTimeout(function() {

                // Common sidebar selectors used across WordPress themes
                var sidebarSelectors = [
                    '#sidebar',
                    '.sidebar',
                    '#secondary',
                    '.secondary',
                    '.widget-area',
                    '#primary-sidebar',
                    '.primary-sidebar',
                    '#main-sidebar',
                    '.main-sidebar',
                    '.sidebar-primary',
                    '.sidebar-secondary',
                    '#complementary',
                    '.complementary',
                    '.aside',
                    '#aside',
                    '.sidebar-1',
                    '.sidebar-2',
                    '#sidebar-1',
                    '#sidebar-2'
                ];

                // Store original sidebar data
                var sidebarOriginalData = null;
                var currentlyMovedSidebar = null;
                var resizeTimer = null;

                // Safe element checking function
                function isValidElement($element) {
                    return $element && $element.length > 0 && $element.get(0) && $element.get(0).nodeType === 1;
                }

                // Function to check if form#product-filter is already at the top of .products
                function isFilterFormAlreadyAtTop() {
                    try {
                        var $products = $(window.getProductSelectorString ? window.getProductSelectorString : '.products');

                        if (!isValidElement($filterForm) || !isValidElement($products)) {
                            return false;
                        }

                        // Check if elements are visible and have layout properties
                        if (!$filterForm.is(':visible') || !$products.is(':visible')) {
                            return false;
                        }

                        // Get the position of both elements with null checks
                        var formOffset = $filterForm.offset();
                        var productsOffset = $products.offset();

                        // Check if offset() returned valid objects
                        if (!formOffset || !productsOffset ||
                            typeof formOffset.top === 'undefined' ||
                            typeof productsOffset.top === 'undefined') {
                            return false;
                        }

                        var formTop = formOffset.top;
                        var productsTop = productsOffset.top;

                        // Check if form is already above or at the same level as products
                        // Adding a small tolerance (10px) for any margin/spacing
                        return formTop <= (productsTop + 10);
                    } catch (error) {
                        console.warn('Error checking filter form position:', error);
                        return false;
                    }
                }

                // Function to find and store original sidebar position
                function findAndStoreSidebarData() {
                    try {
                        if (sidebarOriginalData) return true; // Already found and stored

                        var sidebarFound = false;

                        // Look for sidebar containing form#product-filter
                        $.each(sidebarSelectors, function(index, selector) {
                            try {
                                var $sidebar = $(selector);

                                if (isValidElement($sidebar) && !sidebarFound) {
                                    // Check if this sidebar contains the product filter form
                                    if ($sidebar.find('form#product-filter').length > 0) {
                                        sidebarFound = true;
                                        storeSidebarOriginalData($sidebar);
                                        return false; // Break the loop
                                    }
                                }
                            } catch (error) {
                                console.warn('Error checking sidebar selector:', selector, error);
                            }
                        });

                        // If no sidebar with common selectors contains form#product-filter, 
                        // check for any element containing form#product-filter
                        if (!sidebarFound) {
                            var $filterForm = $('form#product-filter');
                            if (isValidElement($filterForm)) {
                                var $possibleSidebar = $filterForm.closest('div, aside, section');
                                if (isValidElement($possibleSidebar)) {
                                    storeSidebarOriginalData($possibleSidebar);
                                    sidebarFound = true;
                                }
                            }
                        }

                        return sidebarFound;
                    } catch (error) {
                        console.warn('Error finding sidebar data:', error);
                        return false;
                    }
                }

                // Store the original sidebar data
                function storeSidebarOriginalData($sidebar) {
                    try {
                        if (!isValidElement($sidebar)) return;

                        var $parent = $sidebar.parent();
                        if (!isValidElement($parent)) return;

                        var $nextSibling = $sidebar.next();
                        var $prevSibling = $sidebar.prev();

                        sidebarOriginalData = {
                            sidebar: $sidebar,
                            parent: $parent,
                            nextSibling: isValidElement($nextSibling) ? $nextSibling : null,
                            prevSibling: isValidElement($prevSibling) ? $prevSibling : null,
                            index: $parent.children().index($sidebar)
                        };
                    } catch (error) {
                        console.warn('Error storing sidebar data:', error);
                    }
                }

                // Function to move sidebar to top (mobile only)
                function moveSidebarToTop() {
                    try {
                        // Check if we're on mobile (<= breakpoint)
                        if ($(window).width() <= mobileBreakpoint) {
                            // Check if form#product-filter is already at the top of .products
                            if (isFilterFormAlreadyAtTop()) {
                                return; // Don't move if already positioned correctly
                            }

                            if (!findAndStoreSidebarData()) return;

                            var $sidebar = sidebarOriginalData.sidebar;
                            if (!isValidElement($sidebar)) return;

                            // Don't move if already moved
                            if ($sidebar.hasClass('sidebar-moved-to-top')) return;

                            // Try to find .products container first as the preferred target
                            var $products = $(window.getProductSelectorString ? window.getProductSelectorString : '.products');
                            var $targetParent = null;

                            if (isValidElement($products)) {
                                // Move sidebar before .products
                                $targetParent = $products.parent();
                                if (isValidElement($targetParent)) {
                                    $targetParent.addClass('sidebar-parent-flex');
                                    $sidebar.addClass('sidebar-moved-to-top');

                                    // Safe DOM manipulation
                                    if ($sidebar.get(0) && $products.get(0)) {
                                        $products.before($sidebar);
                                        currentlyMovedSidebar = $sidebar;
                                    }
                                }
                            } else {
                                // Fallback to original logic if .products not found
                                var $mainContent = findMainContent($sidebar);

                                if (isValidElement($mainContent)) {
                                    $targetParent = $mainContent.parent();
                                    if (isValidElement($targetParent)) {
                                        $targetParent.addClass('sidebar-parent-flex');
                                        $sidebar.addClass('sidebar-moved-to-top');

                                        // Safe DOM manipulation
                                        if ($sidebar.get(0) && $mainContent.get(0)) {
                                            $mainContent.before($sidebar);
                                            currentlyMovedSidebar = $sidebar;
                                        }
                                    }
                                } else {
                                    // Final fallback: move to top of container
                                    var $container = $sidebar.closest('.container, .wrap, .site, #page, #wrapper, .main-container');
                                    if (isValidElement($container)) {
                                        $container.addClass('sidebar-parent-flex');
                                        $sidebar.addClass('sidebar-moved-to-top');

                                        // Safe DOM manipulation
                                        if ($sidebar.get(0) && $container.get(0)) {
                                            $container.prepend($sidebar);
                                            currentlyMovedSidebar = $sidebar;
                                        }
                                    }
                                }
                            }
                        } else {
                            // Desktop - restore original position
                            restoreOriginalPosition();
                        }
                    } catch (error) {
                        console.warn('Error moving sidebar to top:', error);
                    }
                }

                // Function to find main content
                function findMainContent($sidebar) {
                    try {
                        if (!isValidElement($sidebar)) return null;

                        // First, try to find main content as a sibling
                        var $mainContent = $sidebar.siblings().filter(function() {
                            var $this = $(this);
                            return $this.find('article, .post, .entry, .content').length > 0;
                        }).first();

                        // If no main content found as sibling, try common content selectors within the same parent
                        if (!isValidElement($mainContent)) {
                            var contentSelectors = [
                                '#main',
                                '.main',
                                '#content',
                                '.content',
                                '#primary',
                                '.primary',
                                '.site-content',
                                '.entry-content',
                                '.post-content',
                                'main',
                                'article'
                            ];

                            var $parent = $sidebar.parent();
                            if (isValidElement($parent)) {
                                $.each(contentSelectors, function(i, contentSelector) {
                                    var $content = $parent.find(contentSelector).first();
                                    if (isValidElement($content) && !$content.is($sidebar) && !$sidebar.find($content).length) {
                                        $mainContent = $content;
                                        return false; // Break the loop
                                    }
                                });
                            }
                        }

                        return isValidElement($mainContent) ? $mainContent : null;
                    } catch (error) {
                        console.warn('Error finding main content:', error);
                        return null;
                    }
                }

                // Function to restore original position
                function restoreOriginalPosition() {
                    try {
                        if (!sidebarOriginalData || !currentlyMovedSidebar) return;

                        var $sidebar = currentlyMovedSidebar;
                        var originalData = sidebarOriginalData;

                        if (!isValidElement($sidebar)) return;

                        // Remove mobile classes
                        $sidebar.removeClass('sidebar-moved-to-top');

                        // Remove flex class from any parents that might have it
                        $('.sidebar-parent-flex').removeClass('sidebar-parent-flex');

                        // Restore to original position
                        if (originalData.nextSibling && isValidElement(originalData.nextSibling)) {
                            // Insert before the next sibling
                            if ($sidebar.get(0) && originalData.nextSibling.get(0)) {
                                originalData.nextSibling.before($sidebar);
                            }
                        } else if (originalData.prevSibling && isValidElement(originalData.prevSibling)) {
                            // Insert after the previous sibling
                            if ($sidebar.get(0) && originalData.prevSibling.get(0)) {
                                originalData.prevSibling.after($sidebar);
                            }
                        } else if (isValidElement(originalData.parent)) {
                            // Append to original parent if no siblings
                            if ($sidebar.get(0) && originalData.parent.get(0)) {
                                originalData.parent.append($sidebar);
                            }
                        }

                        currentlyMovedSidebar = null;
                    } catch (error) {
                        console.warn('Error restoring original position:', error);
                    }
                }

                // Debounced resize handler
                function handleResize() {
                    try {
                        clearTimeout(resizeTimer);
                        resizeTimer = setTimeout(function() {
                            moveSidebarToTop();
                        }, 150); // Debounce resize events
                    } catch (error) {
                        console.warn('Error handling resize:', error);
                    }
                }

                // Execute on page load with delay to ensure DOM is ready
                setTimeout(function() {
                    try {
                        moveSidebarToTop();
                    } catch (error) {
                        console.warn('Error on initial load:', error);
                    }
                }, 100);

                // Re-execute after AJAX calls (for dynamic content)
                $(document).ajaxComplete(function() {
                    setTimeout(function() {
                        try {
                            moveSidebarToTop();
                        } catch (error) {
                            console.warn('Error after AJAX:', error);
                        }
                    }, 200);
                });

                // Re-execute after window resize (debounced)
                $(window).on('resize', handleResize);
            }, 2000);
        });
    <?php
    dapfforwc_add_inline_script(ob_get_clean(), 'urlfilter-ajax');
}

if (!isset($dapfforwc_advance_settings["sidebar_on_top"]) || (isset($dapfforwc_advance_settings["sidebar_on_top"])  && $dapfforwc_advance_settings["sidebar_on_top"] === 'on')) {
    add_action('template_redirect', function () {
        // Check if WooCommerce is active and functions exist
        if (
            class_exists('WooCommerce') &&
            function_exists('is_shop') &&
            function_exists('is_product_category') &&
            function_exists('is_product_tag')
        ) {

            if (is_shop() || is_product_category() || is_product_tag()) {
                add_action('wp_head', 'dapfforwc_sidebar_to_top_inline_scripts');
            }
        }
    });
}



/**
 * Create widget area for WooCommerce archives
 */
function dapfforwc_register_sidebar()
{
    register_sidebar(array(
        'name' => esc_html__('WooCommerce Archive Content', 'dynamic-ajax-product-filters-for-woocommerce'),
        'id' => 'wc-archive-content',
        'description' => esc_html__('Content displayed after WooCommerce archive titles and before products', 'dynamic-ajax-product-filters-for-woocommerce'),
        'before_widget' => '<div id="%1$s" class="widget %2$s plugincy-archive-widget">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}
add_action('widgets_init', 'dapfforwc_register_sidebar');

/**
 * Display widget content after archive title and before products
 */
function dapfforwc_display_archive_content()
{
    // Only on WooCommerce archive pages
    if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
        return;
    }

    if (is_active_sidebar('wc-archive-content')) {
        echo '<div class="plugincy-archive-content-wrapper">';
        dynamic_sidebar('wc-archive-content');
        echo '</div>';
    }
}

/**
 * Hook into WooCommerce template to display content
 */
function dapfforwc_hook_archive_content()
{
    // Remove default WooCommerce hooks temporarily to insert our content
    remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
    remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

    // Add our custom content
    add_action('woocommerce_archive_description', 'dapfforwc_display_archive_content', 15);

    // Re-add the default WooCommerce hooks after our content
    add_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 20);
    add_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 20);
}
add_action('init', 'dapfforwc_hook_archive_content');

/**
 * Clear plugin-owned cache during install/update lifecycle events.
 *
 * Lifecycle cleanup should remove stale data without starting background rebuilds.
 */
function dapfforwc_clear_plugin_lifecycle_caches($context = 'lifecycle')
{
    if (function_exists('dapfforwc_clear_woocommerce_caches')) {
        dapfforwc_clear_woocommerce_caches($context, false);
    }
}

/**
 * Check whether the completed plugin update belongs to this plugin.
 *
 * @param array $options Upgrader completion options.
 *
 * @return bool
 */
function dapfforwc_is_current_plugin_update($options)
{
    if (!is_array($options)) {
        return false;
    }

    if ('update' !== ($options['action'] ?? '') || 'plugin' !== ($options['type'] ?? '')) {
        return false;
    }

    $plugin_file     = defined('DAPFFORWC_PLUGIN_BASE_NAME') ? DAPFFORWC_PLUGIN_BASE_NAME : plugin_basename(__FILE__);
    $updated_plugins = [];

    if (isset($options['plugins']) && is_array($options['plugins'])) {
        $updated_plugins = $options['plugins'];
    }

    if (isset($options['plugin']) && is_string($options['plugin'])) {
        $updated_plugins[] = $options['plugin'];
    }

    return in_array($plugin_file, $updated_plugins, true);
}

/**
 * Clear stale plugin-owned cache after this plugin is updated.
 *
 * @param WP_Upgrader $upgrader_object Upgrader instance.
 * @param array       $options         Upgrader completion options.
 */
function dapfforwc_clear_plugin_lifecycle_caches_after_update($upgrader_object, $options)
{
    unset($upgrader_object);

    if (!dapfforwc_is_current_plugin_update($options)) {
        return;
    }

    dapfforwc_clear_plugin_lifecycle_caches('plugin_update');
}
add_action('upgrader_process_complete', 'dapfforwc_clear_plugin_lifecycle_caches_after_update', 10, 2);

/**
 * Plugin activation hook
 */
function dapfforwc_activate()
{
    dapfforwc_clear_plugin_lifecycle_caches('plugin_activation');

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'dapfforwc_activate');

/**
 * Plugin deactivation hook
 */
function dapfforwc_deactivate()
{
    if (function_exists('dapfforwc_cancel_filter_cache_background_jobs')) {
        dapfforwc_cancel_filter_cache_background_jobs('deactivation');
    }

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'dapfforwc_deactivate');


// Handle AJAX template activation
function dapfforwc_ajax_activate_template()
{
    check_ajax_referer('dapfforwc_template_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(esc_html__('You do not have permission to manage templates.', 'dynamic-ajax-product-filters-for-woocommerce'));
    }

    $template_id = isset($_POST['template_id']) ? sanitize_text_field(wp_unslash($_POST['template_id'])) : '';

    if (empty($template_id)) {
        wp_send_json_error(esc_html__('Invalid template ID.', 'dynamic-ajax-product-filters-for-woocommerce'));
    }

    // Validate template ID
    $valid_templates = ['clean', 'shadow', 'modern', 'basic', 'basic_bordered'];
    if (!in_array($template_id, $valid_templates, true)) {
        wp_send_json_error(esc_html__('Invalid template selected.', 'dynamic-ajax-product-filters-for-woocommerce'));
    }

    // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Existing option cache global is used across legacy admin template code.
    global $template_options;
    $template_options['active_template'] = $template_id;

    $updated = update_option('dapfforwc_template_options', $template_options);
    // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

    if ($updated !== false) {
        // Get template name for success message
        $template_names = [
            'clean'  => esc_html__('Minimal Template', 'dynamic-ajax-product-filters-for-woocommerce'),
            'shadow' => esc_html__('Elevated Template', 'dynamic-ajax-product-filters-for-woocommerce'),
            'modern' => esc_html__('Modern Template', 'dynamic-ajax-product-filters-for-woocommerce'),
            'basic' => esc_html__('Basic Template', 'dynamic-ajax-product-filters-for-woocommerce'),
            'basic_bordered' => esc_html__('Basic Bordered Template', 'dynamic-ajax-product-filters-for-woocommerce'),
        ];

        wp_send_json_success(sprintf(
            /* translators: %s: Template name. */
            esc_html__('%s activated successfully!', 'dynamic-ajax-product-filters-for-woocommerce'),
            $template_names[$template_id]
        ));
    } else {
        wp_send_json_error(esc_html__('Failed to save template settings.', 'dynamic-ajax-product-filters-for-woocommerce'));
    }
}
add_action('wp_ajax_dapfforwc_activate_template', 'dapfforwc_ajax_activate_template');


/**
 * Register the widget
 */
function dapfforwc_register_widget()
{
    register_widget('dapfforwc_Widget_filters');
}
add_action('widgets_init', 'dapfforwc_register_widget');

/**
 * Plugincy Widget Filter Class
 */
class dapfforwc_Widget_filters extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'plugincy_widget',
            esc_html__('Dynamic Ajax Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            array(
                'description' => esc_html__('Display content after WooCommerce archive titles', 'dynamic-ajax-product-filters-for-woocommerce'),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance)
    {
        global $dapfforwc_allowed_tags;
        // Only display on WooCommerce archive pages
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }

        $content = '[plugincy_filters]';

        echo wp_kses($args['before_widget'], $dapfforwc_allowed_tags);

        // Process shortcodes and display content
        echo do_shortcode(wpautop($content));

        echo wp_kses($args['after_widget'], $dapfforwc_allowed_tags);
    }

    /**
     * Back-end widget form
     */
    public function form($instance)
    {
        $content = '[plugincy_filters]';
    ?>
        <p> <?php echo esc_attr($content); ?></p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['content'] = '[plugincy_filters]';

        return $instance;
    }
}




// Register the widget for [plugincy_filters_single name="selector_here"]

function dapfforwc_register_single_filter_widget()
{
    register_widget('dapfforwc_Widget_single_filter');
}
add_action('widgets_init', 'dapfforwc_register_single_filter_widget');

// dapfforwc_Widget_single_filter
class dapfforwc_Widget_single_filter extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'plugincy_widget_single',
            esc_html__('Dynamic Ajax Single Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            array(
                'description' => esc_html__('Display a single filter for WooCommerce products', 'dynamic-ajax-product-filters-for-woocommerce'),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance)
    {
        global $dapfforwc_allowed_tags;
        // Only display on WooCommerce archive pages
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }

        $selector = !empty($instance['selector']) ? $instance['selector'] : 'selector_here';
        $content = '[plugincy_filters_single name="' . esc_attr($selector) . '"]';

        echo wp_kses($args['before_widget'], $dapfforwc_allowed_tags);
        echo do_shortcode(wpautop($content));
        echo wp_kses($args['after_widget'], $dapfforwc_allowed_tags);
    }

    /**
     * Back-end widget form
     */
    public function form($instance)
    {
        $selector = !empty($instance['selector']) ? $instance['selector'] : '';
    ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('selector')); ?>">
                <?php esc_html_e('Selector Name:', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('selector')); ?>" name="<?php echo esc_attr($this->get_field_name('selector')); ?>" type="text" value="<?php echo esc_attr($selector); ?>" placeholder="selector_here" />
        </p>
        <p>
            <small><?php esc_html_e('Enter the selector name for the single filter shortcode.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></small>
        </p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['selector'] = !empty($new_instance['selector']) ? sanitize_text_field($new_instance['selector']) : '';
        return $instance;
    }
}

// Register the widget for [plugincy_filters_selected]

function dapfforwc_register_selected_filter_widget()
{
    register_widget('dapfforwc_Widget_selected_filter');
}
add_action('widgets_init', 'dapfforwc_register_selected_filter_widget');
// dapfforwc_Widget_selected_filter
class dapfforwc_Widget_selected_filter extends WP_Widget
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(
            'plugincy_widget_selected',
            esc_html__('Dynamic Ajax Selected Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            array(
                'description' => esc_html__('Display a selected filter for WooCommerce products', 'dynamic-ajax-product-filters-for-woocommerce'),
                'customize_selective_refresh' => true,
            )
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance)
    {
        global $dapfforwc_allowed_tags;
        // Only display on WooCommerce archive pages
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }
        $content = '[plugincy_filters_selected]';

        echo wp_kses($args['before_widget'], $dapfforwc_allowed_tags);
        // No dynamic content required, just output shortcode
        echo do_shortcode($content);
        echo wp_kses($args['after_widget'], $dapfforwc_allowed_tags);
    }

    /**
     * Back-end widget form
     */
    public function form($instance)
    {
        $content = '[plugincy_filters_selected]';
    ?>
        <p> <?php echo esc_attr($content); ?></p>
<?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance)
    {
        $instance = array();

        return $instance;
    }
}


add_action('widgets_init', function () {
    if (function_exists('wp_use_widgets_block_editor') && wp_use_widgets_block_editor()) {
        unregister_widget('dapfforwc_Widget_filters');
        unregister_widget('dapfforwc_Widget_single_filter');
        unregister_widget('dapfforwc_Widget_selected_filter');
    }
});


// Enqueue your script in WordPress
add_action('wp_enqueue_scripts', function () {
    wp_add_inline_script('jquery-core', "
        (function() {
            function isDebugMode() {
                return new URLSearchParams(window.location.search).get('plugincydebug') === 'true';
            }
            
            window.plugincydebugLog = function() {
                if (isDebugMode() && console && console.log) {
                    console.log.apply(console, arguments);
                }
            };
        })();
    ");
});

// Function to clear all cache files
function dapfforwc_clear_woocommerce_caches($context = null, $schedule_rebuild = true)
{
    if (function_exists('dapfforwc_unschedule_filter_cache_background_jobs')) {
        dapfforwc_unschedule_filter_cache_background_jobs();
    }

    $transients = array(
        'dapfforwc_attributes_cache_v8',
        'dapfforwc_attributes_cache_v7',
        'dapfforwc_attributes_cache_v6',
        'dapfforwc_attributes_cache_v5',
        'dapfforwc_attributes_cache_v4',
        'dapfforwc_attributes_cache_v3',
        'dapfforwc_attributes_cache_v2',
        'dapfforwc_attributes_cache_v1',
        'dapfforwc_filter_options_light_cache_v1',
        'dapfforwc_product_details_cache_v8',
        'dapfforwc_product_details_cache_v7',
        'dapfforwc_product_details_cache_v6',
        'dapfforwc_product_details_cache_v5',
        'dapfforwc_product_details_cache_v4',
        'dapfforwc_product_details_cache_v3',
        'dapfforwc_product_details_cache_v2',
        'dapfforwc_product_details_cache_v1',
        'dapfforwc_min_max_prices_cache_v1',
    );

    foreach ($transients as $transient) {
        delete_transient($transient);
    }

    $cache_files = [
        plugin_dir_path(__FILE__) . 'includes/woocommerce_attributes_cache.json',
        plugin_dir_path(__FILE__) . 'includes/woocommerce_product_details.json',
        plugin_dir_path(__FILE__) . 'includes/min_max_prices_cache.json',
    ];

    foreach ($cache_files as $cache_file) {
        if (file_exists($cache_file)) {
            wp_delete_file($cache_file);
        }
    }

    delete_option('dapfforwc_filter_cache_build_lock');
    delete_option(DAPFFORWC_FILTER_CACHE_BUILD_DATA_OPTION);
    delete_option(DAPFFORWC_FILTER_CACHE_BUILD_STATE_OPTION);
    delete_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_DATA_OPTION);
    delete_option(DAPFFORWC_PRODUCT_DETAILS_CACHE_BUILD_STATE_OPTION);

    if (function_exists('dapfforwc_update_filter_cache_status')) {
        dapfforwc_update_filter_cache_status('cleared', 'Filter cache was cleared.');
    }

    if ($schedule_rebuild && function_exists('dapfforwc_use_large_catalog_database_mode') && dapfforwc_use_large_catalog_database_mode()) {
        if (function_exists('dapfforwc_update_filter_cache_status')) {
            dapfforwc_update_filter_cache_status(
                'database_mode',
                'Large catalogue detected. The plugin is using database-safe automatic filter data and will not build full-catalog serialized caches.',
                ['mode' => 'database_safe']
            );
        }

        return;
    }

    if ($schedule_rebuild && function_exists('dapfforwc_schedule_filter_cache_rebuild')) {
        dapfforwc_schedule_filter_cache_rebuild('cache_clear', true);
        if (function_exists('dapfforwc_schedule_product_details_cache_rebuild')) {
            dapfforwc_schedule_product_details_cache_rebuild('cache_clear', true);
        }
    }
}



/**
 * Ultra-light fragment mode for gm-product-filter AJAX.
 * Goal: Emit only the needed HTML markup, nothing else.
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Fragment mode is read-only output trimming and requires a valid gm-product-filter-nonce before the fragment flag is honored.
if (!function_exists('dapfforwc_is_fragment_request')) {
    function dapfforwc_is_fragment_request(): bool
    {
        static $cached_result = null;
        if ($cached_result !== null) {
            return $cached_result;
        }

        if (empty($_GET['gm-product-filter-nonce'])) {
            return $cached_result = false;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['gm-product-filter-nonce']));
        if (!wp_verify_nonce($nonce, 'gm-product-filter-action')) {
            return $cached_result = false;
        }

        $has_fragment_flag = isset($_GET['gm_pf_fragment']) && sanitize_text_field(wp_unslash($_GET['gm_pf_fragment'])) === '1';
        if (!$has_fragment_flag) {
            return $cached_result = false;
        }

        $is_ajax_header = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REQUESTED_WITH']))) === 'xmlhttprequest';

        return $cached_result = ($is_ajax_header || wp_doing_ajax());
    }
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

/** ------------------------------------------------------------------------
 * HEAD/SCRIPT/CSS suppression (works even if theme hard-codes tags)
 * --------------------------------------------------------------------- */
add_action('init', function () {
    if (!dapfforwc_is_fragment_request()) return;

    // Stop resource hints (dns-prefetch, preconnect, font/style preloads)
    remove_action('wp_head', 'wp_resource_hints', 2);
    add_filter('wp_resource_hints', static function () {
        return [];
    }, 999);

    // Block core printers (prevents many default tags)
    remove_action('wp_head', 'wp_print_styles', 8);
    remove_action('wp_head', 'wp_print_head_scripts', 9);
    remove_action('wp_footer', 'wp_print_footer_scripts', 20);

    // Block block-theme "Global Styles" and SVG filters
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

    // Trim misc head noise
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);

    // Emojis + oEmbed + REST links
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
}, 1);

/**
 * Dequeue/deregister anything that still gets enqueued.
 * Also neuter tag writers in case something slips through late.
 */
add_action('wp_enqueue_scripts', function () {
    if (!dapfforwc_is_fragment_request()) return;

    global $wp_styles, $wp_scripts;

    if ($wp_styles && !empty($wp_styles->queue)) {
        foreach ((array) $wp_styles->queue as $h) {
            wp_dequeue_style($h);
            wp_deregister_style($h);
        }
    }
    if ($wp_scripts && !empty($wp_scripts->queue)) {
        foreach ((array) $wp_scripts->queue as $h) {
            wp_dequeue_script($h);
            wp_deregister_script($h);
        }
    }

    // Known inline handles (WP 5.9+)
    wp_dequeue_style('global-styles');
    wp_deregister_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
    wp_deregister_style('classic-theme-styles');

    add_filter('style_loader_tag',  '__return_empty_string', 9999);
    add_filter('script_loader_tag', '__return_empty_string', 9999, 3);
}, 9999);

/**
 * Output buffer (final guard) — strips hard-coded tags in header.php.
 * Only runs in fragment mode; safe for all themes.
 */
add_action('init', function () {
    if (!dapfforwc_is_fragment_request()) return;

    if (!empty($GLOBALS['dapfforwc_fragment_buffer_started'])) {
        return;
    }

    $GLOBALS['dapfforwc_fragment_buffer_level'] = ob_get_level();
    $GLOBALS['dapfforwc_fragment_buffer_started'] = true;
    ob_start('dapfforwc_cleanup_fragment_output');
}, 0);

add_action('template_redirect', function () {
    if (!dapfforwc_is_fragment_request()) return;

    // Remove heavy footer actions (trackers, etc.)
    remove_all_actions('wp_footer');
}, 0);

if (!function_exists('dapfforwc_cleanup_fragment_output')) {
    function dapfforwc_fragment_dom_cleanup($html)
    {
        if (!class_exists('DOMDocument')) {
            return null;
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        $loaded = $document->loadHTML(
            $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $queries = [
            '//head',
            '//script',
            '//noscript',
            '//link',
            '//meta',
            '//header',
            '//footer',
            '//*[@id="wpadminbar"]',
            '//*[@id="wpadminbar-mobile"]',
            '//*[contains(concat(" ", normalize-space(@class), " "), " no-results ")]//script',
        ];

        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if (!$nodes) {
                continue;
            }

            for ($index = $nodes->length - 1; $index >= 0; $index--) {
                $node = $nodes->item($index);
                if ($node && $node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body) {
            return null;
        }

        $cleaned = '';
        foreach ($body->childNodes as $child) {
            $cleaned .= $document->saveHTML($child);
        }

        return trim($cleaned);
    }

    function dapfforwc_cleanup_fragment_output($html)
    {
        $cleaned = dapfforwc_fragment_dom_cleanup($html);
        if (is_string($cleaned) && $cleaned !== '') {
            return $cleaned;
        }

        $html = preg_replace('#<link[^>]+rel=["\']stylesheet["\'][^>]*>#i', '', $html);
        $html = preg_replace('#<link[^>]+rel=["\']preload["\'][^>]+as=["\'](?:style|font)["\'][^>]*>#i', '', $html);
        $html = preg_replace('#<!DOCTYPE[^>]*>#i', '', $html);
        $html = preg_replace('#<head\b[^>]*>.*?</head>#is', '', $html);
        $html = preg_replace('#<header\b[^>]*>.*?</header>#is', '', $html);
        $html = preg_replace('#<footer\b[^>]*>.*?</footer>#is', '', $html);
        $html = preg_replace('#<' . 'script\b[^>]*>.*?</' . 'script>#is', '', $html);
        $html = preg_replace('#<body\b[^>]*>#i', '', $html);
        $html = preg_replace('#</body>#i', '', $html);
        $html = preg_replace('#<html\b[^>]*>#i', '', $html);
        $html = preg_replace('#</html>#i', '', $html);
        return $html;
    }
}

if (!function_exists('dapfforwc_fragment_shutdown_cleanup')) {
    function dapfforwc_fragment_shutdown_cleanup()
    {
        if (!dapfforwc_is_fragment_request()) return;

        $start_level = isset($GLOBALS['dapfforwc_fragment_buffer_level']) ? intval($GLOBALS['dapfforwc_fragment_buffer_level']) : null;
        if ($start_level === null) {
            return;
        }

        while (ob_get_level() > $start_level) {
            $buffer = ob_get_clean();

            if (ob_get_level() === $start_level) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional cleaned WooCommerce/theme HTML fragment response.
                echo dapfforwc_cleanup_fragment_output($buffer);
                break;
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intermediate output buffer content is preserved until the final fragment cleanup pass.
            echo $buffer;
        }

        unset($GLOBALS['dapfforwc_fragment_buffer_level']);
        unset($GLOBALS['dapfforwc_fragment_buffer_started']);
    }
}
add_action('shutdown', 'dapfforwc_fragment_shutdown_cleanup', PHP_INT_MAX);

/** ------------------------------------------------------------------------
 * WooCommerce & Query trimming
 * --------------------------------------------------------------------- */
add_filter('woocommerce_enable_cart_session', function ($enabled) {
    return dapfforwc_is_fragment_request() ? false : $enabled;
}, 10, 1);

add_filter('woocommerce_use_cart_session', function ($use) {
    return dapfforwc_is_fragment_request() ? false : $use;
}, 10, 1);

add_filter('woocommerce_cart_hash', function ($hash) {
    return dapfforwc_is_fragment_request() ? '' : $hash;
}, 10, 1);


/** ------------------------------------------------------------------------
 * Headers & cosmetics
 * --------------------------------------------------------------------- */
add_action('init', function () {
    if (!dapfforwc_is_fragment_request()) return;
    add_filter('show_admin_bar', '__return_false', 999);
});

add_action('send_headers', function () {
    if (!dapfforwc_is_fragment_request()) return;
    header('X-GM-PF-Fragment: 1');   // debug flag
    header('Connection: keep-alive');
    header('Cache-Control: no-store, max-age=0'); // personalized, keep off
});


add_filter('redirect_canonical', function ($redirect_url, $requested_url) {
    $requested_query = wp_parse_url($requested_url, PHP_URL_QUERY);
    $requested_args = array();

    if (is_string($requested_query) && '' !== $requested_query) {
        parse_str($requested_query, $requested_args);
    }

    $paged = isset($requested_args['paged']) ? absint($requested_args['paged']) : 0;

    if ($paged > 0) {
        // Prevent redirect for paged URLs like /products/?paged=2
        return false;
    }
    return $redirect_url;
}, 10, 2);




/**
 * Modify search query according to configured search behavior.
 */
function dapfforwc_search_by_title_only($search, $wp_query)
{
    global $wpdb, $dapfforwc_styleoptions;

    // Only apply if our custom flag is set
    if (!$wp_query->get('dapfforwc_search_post_title')) {
        return $search;
    }

    $q = $wp_query->query_vars;
    $search_terms = isset($q['search_terms']) ? (array)$q['search_terms'] : array();
    $full_match_enabled = isset($dapfforwc_styleoptions['enable_full_match']['search']) && $dapfforwc_styleoptions['enable_full_match']['search'] === 'yes';
    if ($full_match_enabled && !empty($q['s'])) {
        $search_terms = array(sanitize_text_field($q['s']));
    }

    if (empty($search_terms)) {
        return $search;
    }

    $search_behavior = array('title');

    $n = $full_match_enabled || !empty($q['exact']) ? '' : '%';
    $search_clauses = array();

    foreach ($search_terms as $term) {
        $term_like = esc_sql($wpdb->esc_like($term));
        $like_expression = "{$n}{$term_like}{$n}";
        $term_clauses = array();

        if (in_array('title', $search_behavior, true)) {
            $term_clauses[] = "{$wpdb->posts}.post_title LIKE '{$like_expression}'";
        }

        if (!empty($term_clauses)) {
            $search_clauses[] = '(' . implode(' OR ', $term_clauses) . ')';
        }
    }

    if (empty($search_clauses)) {
        return $search;
    }

    $search = ' AND (' . implode(' AND ', $search_clauses) . ') ';
    if (!is_user_logged_in()) {
        $search .= " AND ({$wpdb->posts}.post_password = '') ";
    }

    return $search;
}



add_filter('plugin_row_meta', 'dapfforwc_plugin_row_meta', 10, 2);

/**
 * Show row meta on the plugin screen.
 *
 * @param mixed $links Plugin Row Meta.
 * @param mixed $file  Plugin Base file.
 *
 * @return array
 */
function dapfforwc_plugin_row_meta($links, $file)
{
    if (DAPFFORWC_PLUGIN_BASE_NAME !== $file) {
        return $links;
    }

    $docs_url = 'https://plugincy.com/documentations/dynamic-ajax-product-filters-for-woocommerce/';

    $community_support_url = 'https://wordpress.org/support/plugin/dynamic-ajax-product-filters-for-woocommerce/';

    $support_url = 'https://www.plugincy.com/support/';

    $row_meta = array(
        'docs'    => '<a href="' . esc_url($docs_url) . '" aria-label="' . esc_attr__('View documentation', 'dynamic-ajax-product-filters-for-woocommerce') . '">' . esc_html__('Docs', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>',
        'support' => '<a href="' . esc_url($support_url) . '" aria-label="' . esc_attr__('Support', 'dynamic-ajax-product-filters-for-woocommerce') . '">' . esc_html__('Support', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>',
        'community_support' => '<a href="' . esc_url($community_support_url) . '" aria-label="' . esc_attr__('Visit community forums', 'dynamic-ajax-product-filters-for-woocommerce') . '">' . esc_html__('Community support', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>',
    );

    return array_merge($links, $row_meta);
}
