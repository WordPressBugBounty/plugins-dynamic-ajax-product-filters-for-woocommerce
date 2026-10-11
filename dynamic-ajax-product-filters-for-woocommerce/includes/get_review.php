<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_head', 'dapfforwc_add_review_popup');

function dapfforwc_add_review_popup()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $install_time = get_option('dapfforwc_install_time');
    // Read-only routing: which admin page decides between the popup and the notice. Nothing is changed from
    // the request, so no nonce is needed (the review buttons post to nonce-checked AJAX actions).
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($current_page === 'dapfforwc-admin') {
        
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
                <div id="review-popup" role="dialog" aria-modal="true" aria-labelledby="dapfforwc-review-title" tabindex="-1" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); box-sizing:border-box; width:calc(100% - 32px); max-width:520px; background:#fff; border:1px solid #ccc; padding:20px 44px 20px 20px; z-index:1000;">
                    <button type="button" id="dapfforwc-review-close" aria-label="<?php esc_attr_e('Close', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" style="position:absolute; top:6px; right:6px; width:32px; height:32px; padding:0; border:none; background:none; color:#50575e; font-size:24px; line-height:32px; cursor:pointer;"><span aria-hidden="true">&times;</span></button>
                    <h2 id="dapfforwc-review-title" style="margin-top:0;"><?php esc_html_e('We Value Your Feedback!', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h2>
                    <p><?php
                        /* translators: %s: plugin name. */
                        printf(esc_html__('If you enjoy using %s, please take a moment to leave us a review.', 'dynamic-ajax-product-filters-for-woocommerce'), '<b>' . esc_html__('Dynamic AJAX Product Filters for WooCommerce', 'dynamic-ajax-product-filters-for-woocommerce') . '</b>');
                    ?></p>
                    <a href="https://wordpress.org/support/plugin/dynamic-ajax-product-filters-for-woocommerce/reviews/" target="_blank" style="display:inline-block; padding:10px 15px; background:#432fb8; color:#fff; text-decoration:none; border-radius:5px;"><?php esc_html_e('Leave a Review', 'dynamic-ajax-product-filters-for-woocommerce'); ?></a>
                    <button type="button" id="dapfforwc-review-remind" style="margin-top:10px; background:#f00; color:#fff; border:none; padding:10px; cursor:pointer;"><?php esc_html_e('Remind Me Later', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
                    <button type="button" id="dapfforwc-review-done" style="margin-top:10px; background:#ccc; color:#000; border:none; padding:10px; cursor:pointer;"><?php esc_html_e('Already Done', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
                </div>

                <div id="dapfforwc-review-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0, 0, 0, 0.5); z-index:999;"></div>

                <?php ob_start(); ?>
                    (function() {
                    function dapfforwcGetAdminAjaxSetting(key) {
                        return window.dapfforwcAdminAjax && window.dapfforwcAdminAjax[key] ? window.dapfforwcAdminAjax[key] : '';
                    }
                    function dapfforwcSendReviewAction(action) {
                        const reviewAjaxUrl = dapfforwcGetAdminAjaxSetting('ajax_url') || window.ajaxurl || '';
                        if (!reviewAjaxUrl) {
                            return;
                        }
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', reviewAjaxUrl, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.send('action=' + action + '&nonce=' + encodeURIComponent(dapfforwcGetAdminAjaxSetting('review_nonce')));
                    }
                    document.addEventListener('DOMContentLoaded', function() {
                        const popup = document.getElementById('review-popup');
                        const overlay = document.getElementById('dapfforwc-review-overlay');
                        if (!popup || !overlay) {
                            return;
                        }
                        let opener = null;
                        const isOpen = function() {
                            return popup.style.display === 'block';
                        };

                        // The popup is a modal dialog: it takes focus when it appears and gives it back when it closes.
                        const hidePopup = function() {
                            overlay.style.display = 'none';
                            popup.style.display = 'none';
                            if (opener && document.contains(opener) && typeof opener.focus === 'function') {
                                opener.focus();
                            }
                        };

                        // Every way out of the popup except "Already Done" (the X button, Escape, a click outside it and
                        // "Remind Me Later") hides it and asks again in 3 days.
                        const remindLater = function() {
                            if (!isOpen()) {
                                return;
                            }
                            hidePopup();
                            dapfforwcSendReviewAction('dapfforwc_remind_me_later');
                        };

                        setTimeout(function() {
                            // Never interrupt someone who is typing in the settings: it asks again on a later visit.
                            const active = document.activeElement;
                            if (active && active !== document.body && (active.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName))) {
                                return;
                            }
                            opener = active;
                            overlay.style.display = 'block';
                            popup.style.display = 'block';
                            popup.focus();
                        }, 3000); // Show after 3 seconds

                        // Escape works like "Remind Me Later"; Tab stays inside the popup.
                        document.addEventListener('keydown', function(e) {
                            if (!isOpen()) {
                                return;
                            }
                            if (e.key === 'Escape') {
                                e.preventDefault();
                                remindLater();
                                return;
                            }
                            if (e.key === 'Tab') {
                                const focusable = popup.querySelectorAll('a[href], button');
                                const first = focusable[0];
                                const last = focusable[focusable.length - 1];
                                if (e.shiftKey && (document.activeElement === first || document.activeElement === popup || !popup.contains(document.activeElement))) {
                                    e.preventDefault();
                                    last.focus();
                                } else if (!e.shiftKey && (document.activeElement === last || !popup.contains(document.activeElement))) {
                                    e.preventDefault();
                                    first.focus();
                                }
                            }
                        });

                        overlay.addEventListener('click', remindLater);
                        document.getElementById('dapfforwc-review-close').addEventListener('click', remindLater);
                        document.getElementById('dapfforwc-review-remind').addEventListener('click', remindLater);

                        document.getElementById('dapfforwc-review-done').addEventListener('click', function() {
                            hidePopup();
                            dapfforwcSendReviewAction('dapfforwc_review_already_done');
                        });
                    });
                    })();
                <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-menu-script'); ?>
    <?php
            }
        }
    } elseif (dapfforwc_is_review_notice_screen($current_page)) {
        $already_done = get_option('dapfforwc_review_already_done');
        $remind_me_later = get_option('dapfforwc_remind_me_later');

        // Check if the reminder is set for later
        if ($remind_me_later && (time() < $remind_me_later)) {
            return; // Don't show if user selected "Remind Me Later"
        }

        // Show the notice if not already done; the 10 minutes count from the first visit of the plugin page.
        if (!$already_done && $install_time && time() - $install_time >= 600) {
            add_action('admin_notices', 'dapfforwc_show_admin_notice');
        }
    }
}

/**
 * The review notice is limited to the Dashboard and the plugin's own admin pages (never every admin screen).
 *
 * @param string $current_page Sanitized ?page= value of the current admin request.
 * @return bool
 */
function dapfforwc_is_review_notice_screen($current_page)
{
    if (in_array($current_page, array('dapfforwc-admin', 'plugincy-plugins'), true)) {
        return true;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    return $screen && isset($screen->id) && 'dashboard' === $screen->id;
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
        <p><?php
                        /* translators: %s: plugin name. */
                        printf(esc_html__('If you enjoy using %s, please take a moment to leave us a review.', 'dynamic-ajax-product-filters-for-woocommerce'), '<b>' . esc_html__('Dynamic AJAX Product Filters for WooCommerce', 'dynamic-ajax-product-filters-for-woocommerce') . '</b>');
                    ?></p>
        <p><a href="https://wordpress.org/support/plugin/dynamic-ajax-product-filters-for-woocommerce/reviews/" target="_blank" class="button-primary"><?php esc_html_e('Leave a Review', 'dynamic-ajax-product-filters-for-woocommerce'); ?></a>
            <button id="close-notice" class="button-secondary"><?php esc_html_e('Remind Me Later', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
            <button id="already-done-notice" class="button-secondary"><?php esc_html_e('Already Done', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
        </p>
    </div>
    <?php ob_start(); ?>
        document.addEventListener('DOMContentLoaded', function() {
            const sendReviewAction = function(action) {
                const settings = window.dapfforwcAdminAjax || {};
                const reviewAjaxUrl = settings.ajax_url || window.ajaxurl || '';
                if (!reviewAjaxUrl) {
                    return;
                }
                const xhr = new XMLHttpRequest();
                xhr.open('POST', reviewAjaxUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('action=' + action + '&nonce=' + encodeURIComponent(settings.review_nonce || ''));
            };

            document.getElementById('close-notice').addEventListener('click', function() {
                document.getElementById('admin-review-notice').style.display = 'none';
                sendReviewAction('dapfforwc_remind_me_later');
            });

            document.getElementById('already-done-notice').addEventListener('click', function() {
                document.getElementById('admin-review-notice').style.display = 'none';
                sendReviewAction('dapfforwc_review_already_done');
            });

            // The core dismiss (X) button counts as "Remind Me Later", so the notice does not come back on the next page load.
            document.addEventListener('click', function(event) {
                if (event.target && event.target.closest && event.target.closest('#admin-review-notice .notice-dismiss')) {
                    sendReviewAction('dapfforwc_remind_me_later');
                }
            });
        });
    <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-menu-script'); ?>
<?php
}
