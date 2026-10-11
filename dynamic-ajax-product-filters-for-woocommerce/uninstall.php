<?php
/**
 * Runs when the plugin is deleted from the Plugins page. Nothing is removed unless
 * "Delete all plugin data on uninstall" (Advanced settings) is on, and the settings shared
 * with the Pro plugin are kept while Pro is still installed.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/includes/uninstall-data.php';

$dapfforwc_delete_shared = !dapfforwc_uninstall_is_pro_installed();

if (is_multisite() && function_exists('get_sites')) {
    foreach (get_sites(array('fields' => 'ids', 'number' => 0)) as $dapfforwc_site_id) {
        switch_to_blog((int) $dapfforwc_site_id);
        dapfforwc_uninstall_site($dapfforwc_delete_shared);
        restore_current_blog();
    }
} else {
    dapfforwc_uninstall_site($dapfforwc_delete_shared);
}
