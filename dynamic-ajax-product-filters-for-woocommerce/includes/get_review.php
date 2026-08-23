<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_head', 'dapfforwc_add_review_popup');

function dapfforwc_add_review_popup()
{
    $install_time = get_option('dapfforwc_install_time');
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dapfforwc_tab_nonce')) {
        return; // Exit if nonce is not valid
    }
    if (isset($_GET['page']) && $_GET['page'] === 'dapfforwc-admin') {
        
        if (!$install_time) {
            update_option('dapfforwc_install_time', time());
            $install_time = time();
        }

        // Check if 10 minutes have passed since installation
        if (time() - $install_time >= 600) {
            $already_done = get_option('dapfforwc_review_already_done');
            $remind_me_later = get_option('dapfforwc_remind_me_later');

            // Check if the reminder is set for later
            if ($remind_me_later && (time() < $remind_me_later)) {
                return; // Don't show if user selected "Remind Me Later"
            }

            // Show the popup if not already done
            if (!$already_done) {
?>
                <div id="review-popup" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; border:1px solid #ccc; padding:20px; z-index:1000;">
                    <h2>We Value Your Feedback!</h2>
                    <p>If you enjoy using <b>Dynamic AJAX Product Filters for WooCommerce</b>, please take a moment to leave us a review.</p>
                    <a href="https://wordpress.org/support/plugin/dynamic-ajax-product-filters-for-woocommerce/reviews/" target="_blank" style="display:inline-block; padding:10px 15px; background:#432fb8; color:#fff; text-decoration:none; border-radius:5px;">Leave a Review</a>
                    <button id="close-popup" style="margin-top:10px; background:#f00; color:#fff; border:none; padding:10px; cursor:pointer;">Remind Me Later</button>
                    <button id="already-done" style="margin-top:10px; background:#ccc; color:#000; border:none; padding:10px; cursor:pointer;">Already Done</button>
                </div>

                <div id="overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.5); z-index:999;"></div>

                <?php ob_start(); ?>
                    function dapfforwcGetAdminAjaxSetting(key) {
                        return window.dapfforwcAdminAjax && window.dapfforwcAdminAjax[key] ? window.dapfforwcAdminAjax[key] : '';
                    }
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(function() {
                            document.getElementById('overlay').style.display = 'block';
                            document.getElementById('review-popup').style.display = 'block';
                        }, 3000); // Show after 3 seconds

                        document.getElementById('close-popup').addEventListener('click', function() {
                            document.getElementById('overlay').style.display = 'none';
                            document.getElementById('review-popup').style.display = 'none';
                            // Send AJAX request to set remind me later
                            const xhr = new XMLHttpRequest();
                            const reviewAjaxUrl = dapfforwcGetAdminAjaxSetting('ajax_url') || window.ajaxurl || '';
                            if (!reviewAjaxUrl) {
                                return;
                            }
                            xhr.open('POST', reviewAjaxUrl, true);
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.send('action=dapfforwc_remind_me_later&nonce=' + encodeURIComponent(dapfforwcGetAdminAjaxSetting('review_nonce')));
                        });

                        document.getElementById('already-done').addEventListener('click', function() {
                            document.getElementById('overlay').style.display = 'none';
                            document.getElementById('review-popup').style.display = 'none';
                            // Send AJAX request to set already done
                            const xhr = new XMLHttpRequest();
                            const reviewAjaxUrl = dapfforwcGetAdminAjaxSetting('ajax_url') || window.ajaxurl || '';
                            if (!reviewAjaxUrl) {
                                return;
                            }
                            xhr.open('POST', reviewAjaxUrl, true);
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.send('action=dapfforwc_review_already_done&nonce=' + encodeURIComponent(dapfforwcGetAdminAjaxSetting('review_nonce')));
                        });
                    });
                <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-menu-script'); ?>
    <?php
            }
        }
    } else {
        $already_done = get_option('dapfforwc_review_already_done');
        $remind_me_later = get_option('dapfforwc_remind_me_later');

        // Check if the reminder is set for later
        if ($remind_me_later && (time() < $remind_me_later)) {
            return; // Don't show if user selected "Remind Me Later"
        }

        // Show the popup if not already done
        if (!$already_done && time() - $install_time >= 600) {
            add_action('admin_notices', 'dapfforwc_show_admin_notice');
        }
    }
}

add_action('wp_ajax_dapfforwc_remind_me_later', 'dapfforwc_remind_me_later');
function dapfforwc_remind_me_later()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }
    check_ajax_referer('dapfforwc_review_nonce', 'nonce');

    // Set remind me later for 3 days
    update_option('dapfforwc_remind_me_later', time() + (3 * 24 * 60 * 60)); // 3 days
    wp_send_json_success();
}

add_action('wp_ajax_dapfforwc_review_already_done', 'dapfforwc_review_already_done');
function dapfforwc_review_already_done()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('forbidden', 403);
    }
    check_ajax_referer('dapfforwc_review_nonce', 'nonce');

    // Set already done flag
    update_option('dapfforwc_review_already_done', true);
    wp_send_json_success();
}

function dapfforwc_show_admin_notice()
{
    ?>
    <div class="notice notice-info is-dismissible" id="admin-review-notice">
        <p>If you enjoy using <b>Dynamic AJAX Product Filters for WooCommerce</b>, please take a moment to leave us a review.</p>
        <p><a href="https://wordpress.org/support/plugin/dynamic-ajax-product-filters-for-woocommerce/reviews/" target="_blank" class="button-primary">Leave a Review</a>
            <button id="close-notice" class="button-secondary">Remind Me Later</button>
            <button id="already-done-notice" class="button-secondary">Already Done</button>
        </p>
    </div>
    <?php ob_start(); ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('close-notice').addEventListener('click', function() {
                document.getElementById('admin-review-notice').style.display = 'none';
            });

            document.getElementById('already-done-notice').addEventListener('click', function() {
                document.getElementById('admin-review-notice').style.display = 'none';
                // Optional: Similar to the popup, you could update an option here.
            });
        });
    <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-menu-script'); ?>
<?php
}
