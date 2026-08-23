<?php
/**
 * Plugin Analytics Integration Class
 * 
 * This class handles both tracking and deactivation analytics
 * for your WordPress plugin using the Product Analytics Pro API.
 */

 if (!defined('ABSPATH')) {
    exit;
}

class dapfforwc_cart_anaylytics
{

    private $product_id;
    private $analytics_api_url;
    private $plugin_version;
    private $plugin_name;
    private $plugin_file;

    public function __construct($product_id, $analytics_api_url, $plugin_version, $plugin_name, $plugin_file = null)
    {
        $this->product_id = $product_id;
        $this->analytics_api_url = rtrim($analytics_api_url, '/');
        $this->plugin_version = $plugin_version;
        $this->plugin_name = $plugin_name;
        $this->plugin_file = $plugin_file;

        global $dapfforwc_advance_settings;
        $allow_data_share = isset($dapfforwc_advance_settings["allow_data_share"]) && $dapfforwc_advance_settings["allow_data_share"] === 'on';

        // Hook into plugin activation/deactivation using the correct file path
        if ($this->plugin_file && $allow_data_share) {
            register_activation_hook($this->plugin_file, array($this, 'on_plugin_activation'));
            register_deactivation_hook($this->plugin_file, array($this, 'on_plugin_deactivation'));
        }
        if ($allow_data_share) {
            // Send tracking data periodically (weekly)
            add_action('wp_loaded', array($this, 'schedule_tracking'));
            add_action('send_plugin_analytics_' . $this->product_id, array($this, 'send_tracking_data'));
        }

        // Add deactivation feedback form only when data sharing is allowed
        if ($allow_data_share) {
            add_action('admin_footer', array($this, 'add_deactivation_feedback_form'));
        }
    }

    /**
     * Called when plugin is activated
     */
    public function on_plugin_activation()
    {
        // Send initial tracking data
        $this->send_tracking_data();

        // Schedule weekly tracking
        if (!wp_next_scheduled('send_plugin_analytics_' . $this->product_id)) {
            wp_schedule_event(time(), 'weekly', 'send_plugin_analytics_' . $this->product_id);
        }
    }

    /**
     * Called when plugin is deactivated
     */
    public function on_plugin_deactivation()
    {
        // Clear scheduled event
        wp_clear_scheduled_hook('send_plugin_analytics_' . $this->product_id);

        // Note: Deactivation reason will be sent via AJAX from the feedback form
    }

    /**
     * Schedule tracking if not already scheduled
     */
    public function schedule_tracking()
    {
        if (!wp_next_scheduled('send_plugin_analytics_' . $this->product_id)) {
            wp_schedule_event(time(), 'weekly', 'send_plugin_analytics_' . $this->product_id);
        }
    }

    /**
     * Send tracking data to analytics API
     */
    public function send_tracking_data()
    {
        $data = $this->collect_site_data();

        $response = wp_remote_post($this->analytics_api_url . '/track/' . $this->product_id, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($data),
            'timeout' => 5,
            'redirection' => 0,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        return true;
    }

    /**
     * Send deactivation data to analytics API
     */
    public function send_deactivation_data($reason = '')
    {
        $data = array(
            'site_url' => home_url(),
            'reason' => $reason,
        );

        $response = wp_remote_post($this->analytics_api_url . '/deactivate/' . $this->product_id, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($data),
            'timeout' => 5,
            'redirection' => 0,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }

    /**
     * Collect comprehensive site data
     */
    private function collect_site_data()
    {
        global $wpdb;

        return array(
            'site_url' => home_url(),
            'multisite' => is_multisite(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'server_software' => sanitize_text_field(wp_unslash(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown')),
            'mysql_version' => $wpdb->db_version(),
            'location' => $this->get_site_location(),
            'plugin_version' => $this->plugin_version,
            'other_plugins' => $this->get_other_plugins(),
            'active_theme' => get_option('stylesheet'),
            'using_pro' => "0",
        );
    }

    /**
     * Get site location based on timezone
     */
    private function get_site_location()
    {
        $timezone = get_option('timezone_string');
        if (empty($timezone)) {
            return 'Unknown';
        }

        // Extract country/region from timezone
        $parts = explode('/', $timezone);
        return isset($parts[0]) ? $parts[0] : 'Unknown';
    }

    /**
     * Get list of other active plugins
     */
    private function get_other_plugins()
    {
        $active_plugins = get_option('active_plugins', array());
        $plugins = array();

        foreach ($active_plugins as $plugin_path) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
            if (!empty($plugin_data['Name']) && $plugin_data['Name'] !== $this->plugin_name) {
                $plugins[] = array(
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                );
            }
        }

        return $plugins;
    }

    /**
     * Add deactivation feedback form
     */
    public function add_deactivation_feedback_form()
    {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins') {
            // Get the correct plugin basename
            $plugin_file = $this->plugin_file;
            $plugin_basename = plugin_basename($plugin_file);
            $plugin_slug = dirname($plugin_basename);
?>
            <div id="dapfforwc-plugin-deactivation-feedback" style="display:none;">
                <div class="feedback-overlay">
                    <div class="feedback-modal">
                        <div class="modal-header">
                            <div style="display: flex; gap:10px;align-items: center;">
                                <div class="plugincy_icon" style=" line-height: 1; "><svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="24" height="24" viewBox="0 0 24 24">
                                        <path d="M0 0h24v24H0z" fill="#941F74" />
                                        <path d="m8.087 5.761 0.594 -0.006h0.64l0.66 -0.003h1.381q1.056 0 2.114 -0.009h1.341l0.639 -0.006 0.597 0.003h0.523c0.753 0.108 1.252 0.444 1.758 1.002 0.357 0.889 0.312 1.763 0.309 2.712l0.003 0.631v1.32q0 1.008 0.009 2.016v1.284l0.006 0.608c-0.011 1.141 -0.083 1.82 -0.849 2.686 -0.572 0.407 -1.044 0.417 -1.734 0.399l-0.507 -0.011 -0.384 -0.013v-0.563l0.438 -0.063 0.57 -0.09 0.567 -0.086c0.549 -0.098 0.549 -0.098 0.862 -0.699 0.057 -0.44 0.057 -0.44 0.048 -0.933l0.003 -0.575 -0.003 -0.619 0.002 -0.636 -0.002 -1.333q-0.002 -1.02 0.003 -2.045l-0.002 -1.294 0.003 -0.619 -0.005 -0.575 -0.002 -0.506c-0.019 -0.48 -0.019 -0.48 -0.422 -0.99 -0.538 -0.18 -0.894 -0.214 -1.455 -0.223l-0.547 -0.009q-1.237 -0.013 -2.477 -0.019a141 141 0 0 1 -1.306 -0.015 157.5 157.5 0 0 0 -1.884 -0.017l-0.59 -0.013c-0.837 0.003 -1.352 0.028 -2.083 0.456 -0.515 0.879 -0.477 1.659 -0.464 2.661l-0.002 0.608q0 0.634 0.007 1.269 0.007 0.969 0 1.941l0.006 1.234 -0.003 0.585c0.013 0.836 0.026 1.344 0.461 2.073 0.482 0.42 0.75 0.476 1.383 0.5l0.507 0.027 0.384 0.006v0.563c-1.857 0.117 -1.857 0.117 -2.637 -0.329 -0.597 -0.693 -0.784 -1.203 -0.794 -2.117l-0.007 -0.569 -0.002 -0.61 -0.005 -0.631 -0.003 -1.323q-0.003 -1.01 -0.015 -2.021l-0.003 -1.287 -0.007 -0.609c0.011 -1.145 0.072 -1.83 0.847 -2.692 0.618 -0.413 0.806 -0.423 1.524 -0.426" fill="#F4E5EF" />
                                        <path d="M10.875 14.25c0.599 -0.035 0.599 -0.035 1.125 0l0.375 0.75h4.5v0.563h-4.5l-0.375 0.75h-1.313l-0.375 -0.75H7.5V15l0.596 -0.021 0.776 -0.038 0.393 -0.013c0.754 -0.028 0.754 -0.028 1.383 -0.404z" fill="#FBF8FA" />
                                        <path d="M13.875 11.063c0.679 -0.035 0.679 -0.035 1.313 0l0.375 0.75h1.313v0.563h-1.313l-0.375 0.75c-0.633 0.036 -0.633 0.036 -1.313 0l-0.375 -0.563a10.5 10.5 0 0 0 -1.349 -0.096l-0.402 -0.011 -1.272 -0.022 -0.862 -0.019Q8.558 12.39 7.5 12.375v-0.563l0.544 -0.007q0.999 -0.017 1.998 -0.041l0.865 -0.017q0.621 -0.011 1.244 -0.027l0.392 -0.005c0.404 -0.005 0.404 -0.005 0.957 -0.091z" fill="#FCF8FA" />
                                        <path d="M9.738 7.817c0.575 0.058 0.575 0.058 0.806 0.338 0.507 0.435 1.059 0.373 1.698 0.38l0.402 0.011q0.633 0.015 1.266 0.022l0.859 0.019q1.053 0.024 2.106 0.039v0.563l-0.541 0.007q-0.996 0.017 -1.99 0.041l-0.861 0.017q-0.618 0.011 -1.239 0.027l-0.389 0.005c-0.716 0.015 -0.716 0.015 -1.309 0.375l-0.232 0.279c-0.563 0.071 -0.563 0.071 -1.125 0q-0.286 -0.276 -0.563 -0.563c-0.584 -0.137 -0.584 -0.137 -1.125 -0.188v-0.563l0.633 -0.164c0.684 -0.211 0.918 -0.572 1.605 -0.645" fill="#FCFAFC" />
                                        <path d="M9.375 8.438q0.471 0.08 0.938 0.188v0.563l-0.938 0.188c-0.105 -0.363 -0.105 -0.363 -0.188 -0.75z" fill="#9A4289" />
                                        <path d="m11.063 14.813 0.75 0.188 -0.188 0.75 -0.75 -0.188z" fill="#95297A" />
                                        <path d="m14.25 11.625 0.75 0.188 -0.188 0.75 -0.75 -0.188z" fill="#A52679" />
                                        <path d="M12.75 17.813h1.125v0.563H12.75z" fill="#EEDBE8" />
                                    </svg></div>
                                <h3>Quick Feedback</h3>
                            </div>
                            <button type="button" class="close-button" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>If you have a moment, please share why you are deactivating <?php echo esc_html($this->plugin_name); ?>:</p>
                            <form id="dapfforwc-deactivation-feedback-form">
                                <div class="feedback-options">
                                    <label class="feedback-option">
                                        <input type="radio" name="reason" value="temporary">
                                        <span class="radio-button"></span>
                                        It's a temporary deactivation.
                                    </label>
                                    <label class="feedback-option">
                                        <input type="radio" name="reason" value="not-working">
                                        <span class="radio-button"></span>
                                        The plugin isn't working properly.
                                    </label>
                                    <label class="feedback-option">
                                        <input type="radio" name="reason" value="better-plugin">
                                        <span class="radio-button"></span>
                                        I found a better alternative plugin.
                                    </label>
                                    <label class="feedback-option">
                                        <input type="radio" name="reason" value="missing-feature">
                                        <span class="radio-button"></span>
                                        It's missing a specific feature.
                                    </label>
                                    <label class="feedback-option">
                                        <input type="radio" name="reason" value="other">
                                        <span class="radio-button"></span>
                                        Other
                                    </label>
                                </div>
                                <div class="other-reason-container" style="display:none;">
                                    <textarea name="other_reason" placeholder="Please tell us more..." rows="3"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Submit & Deactivate</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php ob_start(); ?>
                .feedback-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 999999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .feedback-modal {
                    background: #ffffff;
                    border-radius: 8px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }

                .modal-header {
                    padding: 24px 24px 8px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }

                .modal-header h3 {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: #1a1a1a;
                }

                .close-button {
                    background: none;
                    border: none;
                    font-size: 20px;
                    color: #666;
                    cursor: pointer;
                    padding: 4px;
                    border-radius: 4px;
                    transition: background-color 0.2s ease;
                }

                .close-button:hover {
                    background: #f5f5f5;
                }

                .modal-body {
                    padding: 16px 24px 24px;
                }

                .modal-body p {
                    margin: 0 0 20px;
                    color: #555;
                    font-size: 14px;
                    line-height: 1.5;
                }

                .feedback-options {
                    margin-bottom: 16px;
                }

                .feedback-option {
                    display: flex;
                    align-items: center;
                    margin: 0 0 12px;
                    padding: 0;
                    cursor: pointer;
                    font-size: 14px;
                    color: #333;
                    line-height: 1.4;
                }

                .feedback-option:hover {
                    color: #432fb8;
                }

                .feedback-option input[type="radio"] {
                    position: absolute;
                    opacity: 0;
                    cursor: pointer;
                    height: 0;
                    width: 0;
                }

                .radio-button {
                    height: 16px;
                    width: 16px;
                    background: #ffffff;
                    border: 2px solid #ddd;
                    border-radius: 50%;
                    margin-right: 12px;
                    flex-shrink: 0;
                    position: relative;
                    transition: all 0.2s ease;
                }

                .feedback-option input[type="radio"]:checked+.radio-button {
                    border-color: #432fb8;
                    background: #432fb8;
                }

                .feedback-option input[type="radio"]:checked+.radio-button:after {
                    content: "";
                    position: absolute;
                    display: block;
                    left: 50%;
                    top: 50%;
                    transform: translate(-50%, -50%);
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background: white;
                }

                .other-reason-container {
                    margin-top: 16px;
                    animation: slideDown 0.3s ease-out;
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        max-height: 0;
                        transform: translateY(-10px);
                    }

                    to {
                        opacity: 1;
                        max-height: 100px;
                        transform: translateY(0);
                    }
                }

                .other-reason-container textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    resize: vertical;
                    font-family: inherit;
                    font-size: 14px;
                    line-height: 1.4;
                    transition: border-color 0.2s ease;
                    box-sizing: border-box;
                }

                .other-reason-container textarea:focus {
                    outline: none;
                    border-color: #432fb8;
                    box-shadow: 0 0 0 1px #432fb8;
                }

                .modal-footer {
                    display: flex;
                    justify-content: flex-end;
                    margin-top: 20px;
                    padding-top: 16px;
                    border-top: 1px solid #eee;
                }

                .btn {
                    padding: 8px 16px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 120px;
                }

                .btn-primary {
                    background: #432fb8;
                    color: white;
                }

                .btn-primary:hover {
                    background: #005a87;
                }

                /* Responsive design */
                @media (max-width: 640px) {
                    .feedback-modal {
                        margin: 20px;
                        width: calc(100% - 40px);
                    }

                    .modal-header {
                        padding: 20px 20px 8px;
                    }

                    .modal-body {
                        padding: 16px 20px 20px;
                    }

                    .btn {
                        width: 100%;
                    }
                }
            <?php dapfforwc_add_inline_style(ob_get_clean(), 'dapfforwc-admin-menu-style'); ?>

            <?php ob_start(); ?>
                jQuery(document).ready(function($) {
                    var pluginBasename = '<?php echo esc_js($plugin_basename); ?>';
                    var pluginSlug = '<?php echo esc_js($plugin_slug); ?>';
                    var deactivateUrl = '';

                    // Multiple selectors to catch the deactivation link
                    var selectors = [
                        'tr[data-slug="' + pluginSlug + '"] .deactivate a',
                        'tr[data-plugin="' + pluginBasename + '"] .deactivate a',
                        '.wp-list-table.plugins tr[data-slug="' + pluginSlug + '"] .row-actions .deactivate a'
                    ];

                    // Try each selector
                    selectors.forEach(function(selector) {
                        $(selector).on('click', function(e) {
                            e.preventDefault();
                            deactivateUrl = $(this).attr('href');
                            $('#dapfforwc-plugin-deactivation-feedback').show();
                        });
                    });

                    // Fallback: Find deactivation link by searching for plugin basename in the URL
                    $('a[href*="action=deactivate"]').each(function() {
                        var href = $(this).attr('href');
                        if (href.indexOf(encodeURIComponent(pluginBasename)) > -1) {
                            $(this).on('click', function(e) {
                                e.preventDefault();
                                deactivateUrl = $(this).attr('href');
                                $('#dapfforwc-plugin-deactivation-feedback').show();
                            });
                        }
                    });

                    // Handle feedback form submission
                    $('#dapfforwc-deactivation-feedback-form').on('submit', function(e) {
                        e.preventDefault();

                        var dapfforwcAdminAjaxUrl = (window.dapfforwcAdminAjax && window.dapfforwcAdminAjax.ajax_url) ? window.dapfforwcAdminAjax.ajax_url : (window.ajaxurl || '');
                        var dapfforwcDeactivationNonce = (window.dapfforwcAdminAjax && window.dapfforwcAdminAjax.deactivation_feedback_nonce) ? window.dapfforwcAdminAjax.deactivation_feedback_nonce : '';
                        if (!dapfforwcAdminAjaxUrl) {
                            window.location.href = deactivateUrl;
                            return;
                        }
                        var reason = $('input[name="reason"]:checked').val();
                        var otherReason = $('textarea[name="other_reason"]').val();

                        if (reason === 'other' && otherReason) {
                            reason = otherReason;
                        }

                        $(this).find("button.btn.btn-primary").text("Deactivating...");

                        // Send deactivation data
                        $.ajax({
                            url: dapfforwcAdminAjaxUrl,
                            type: 'POST',
                            data: {
                                action: 'dapfforwc_send_deactivation_feedback',
                                reason: reason || 'no-reason-provided',
                                nonce: dapfforwcDeactivationNonce
                            },
                            success: function(response) {
                                // Wait a moment to ensure the request completed
                                setTimeout(function() {
                                    window.location.href = deactivateUrl;
                                }, 500);
                            },
                            error: function(xhr, status, error) {
                                console.error('Feedback send failed:', status, error);
                                console.error('Response:', xhr.responseText);

                                // Even if feedback fails, proceed with deactivation
                                setTimeout(function() {
                                    window.location.href = deactivateUrl;
                                }, 500);
                            }
                        });
                    });

                    // Handle other reason text area
                    $(document).on('change', 'input[name="reason"]', function() {
                        if ($(this).val() === 'other') {
                            $('.other-reason-container').slideDown(300);
                        } else {
                            $('.other-reason-container').slideUp(300);
                        }
                    });

                    // Handle close button
                    $(document).on('click', '.close-button', function() {
                        $('#dapfforwc-plugin-deactivation-feedback').hide();
                    });

                    // Handle overlay click to close
                    $(document).on('click', '.feedback-overlay', function(e) {
                        if (e.target === this) {
                            $('#dapfforwc-plugin-deactivation-feedback').hide();
                        }
                    });

                    // Handle escape key
                    $(document).on('keyup', function(e) {
                        if (e.keyCode === 27) { // ESC key
                            $('#dapfforwc-plugin-deactivation-feedback').hide();
                        }
                    });
                });
            <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-menu-script'); ?>
<?php
        }
    }
}
