<?php
/**
 * What uninstall.php removes. Loaded only by uninstall.php (and the regression tests); defines functions, no side effects.
 *
 * The main settings (dapfforwc_options, _advance_options, _style_options, _template_options, _seo_permalinks_options),
 * the dapfforwc_* cache transients and the analytics cron event are shared with the Pro plugin, so they are only
 * removed when Pro is not installed. Free's own data (background cache state, review and analytics bookkeeping,
 * cache batch jobs) is always removed when the setting is on.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('dapfforwc_uninstall_is_pro_installed')) {
    function dapfforwc_uninstall_is_pro_installed()
    {
        return file_exists(WP_PLUGIN_DIR . '/dynamic-ajax-product-filters-for-woocommerce-pro/dynamic-ajax-product-filters-for-woocommerce-pro.php');
    }
}

if (!function_exists('dapfforwc_uninstall_is_enabled')) {
    /**
     * "Delete all plugin data on uninstall" (Advanced settings, shared with Pro). Off unless explicitly 'on'.
     */
    function dapfforwc_uninstall_is_enabled()
    {
        $advance = get_option('dapfforwc_advance_options');

        return is_array($advance) && isset($advance['delete_data_on_uninstall']) && 'on' === $advance['delete_data_on_uninstall'];
    }
}

if (!function_exists('dapfforwc_uninstall_data_map')) {
    /**
     * @param bool $delete_shared Also remove the data shared with the Pro plugin.
     * @return array{options: string[], cron_hooks: string[], transient_prefix: string, action_group: string}
     */
    function dapfforwc_uninstall_data_map($delete_shared)
    {
        $map = array(
            'options' => array(
                'dapfforwc_filter_cache_build_lock',
                'dapfforwc_filter_cache_build_data',
                'dapfforwc_filter_cache_build_state',
                'dapfforwc_product_details_cache_build_data',
                'dapfforwc_product_details_cache_build_state',
                'dapfforwc_filter_cache_status',
                'dapfforwc_install_time',
                'dapfforwc_remind_me_later',
                'dapfforwc_review_already_done',
                'dapfforwc_filters',
                'dapfforwc_analytics_last_sent',
            ),
            'cron_hooks' => array(
                'dapfforwc_process_filter_cache_batch',
                'dapfforwc_process_product_details_cache_batch',
            ),
            'transient_prefix' => '',
            'action_group' => 'dapfforwc-filter-cache',
        );

        if ($delete_shared) {
            $map['options'] = array_merge($map['options'], array(
                'dapfforwc_options',
                'dapfforwc_advance_options',
                'dapfforwc_style_options',
                'dapfforwc_template_options',
                'dapfforwc_seo_permalinks_options',
                'dapfforwc_style_options_attribute_id_migrated',
                'dapfforwc_style_options_term_id_migrated',
            ));
            $map['cron_hooks'][] = 'send_plugin_analytics_01';
            $map['transient_prefix'] = 'dapfforwc_';
        }

        return $map;
    }
}

if (!function_exists('dapfforwc_uninstall_site')) {
    /**
     * Remove the plugin's data from the current site, but only when the site enabled the setting.
     *
     * @param bool $delete_shared Also remove the data shared with the Pro plugin.
     * @return bool Whether anything was removed.
     */
    function dapfforwc_uninstall_site($delete_shared)
    {
        if (!dapfforwc_uninstall_is_enabled()) {
            return false;
        }

        global $wpdb;
        $map = dapfforwc_uninstall_data_map($delete_shared);

        // Background cache batches: Action Scheduler (WooCommerce) and the WP-Cron fallback.
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('', array(), $map['action_group']);
        }
        if (class_exists('ActionScheduler') && function_exists('as_get_scheduled_actions')) {
            $action_ids = as_get_scheduled_actions(array('group' => $map['action_group'], 'status' => '', 'per_page' => -1), 'ids');
            $store = ActionScheduler::store();
            foreach ((array) $action_ids as $action_id) {
                try {
                    $store->delete_action($action_id);
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        foreach ($map['cron_hooks'] as $hook) {
            wp_unschedule_hook($hook);
        }

        foreach ($map['options'] as $option) {
            delete_option($option);
        }

        if ($map['transient_prefix'] !== '') {
            $like_value = $wpdb->esc_like('_transient_' . $map['transient_prefix']) . '%';
            $like_timeout = $wpdb->esc_like('_transient_timeout_' . $map['transient_prefix']) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time uninstall clean-up of the plugin's transients (names carry a dynamic suffix).
            $names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like_value, $like_timeout));
            foreach ((array) $names as $name) {
                if (strpos($name, '_transient_timeout_') === 0) {
                    continue;
                }
                delete_transient(substr($name, strlen('_transient_')));
            }
            foreach ((array) $names as $name) {
                if (strpos($name, '_transient_timeout_') === 0) {
                    delete_option($name);
                }
            }
        }

        return true;
    }
}
