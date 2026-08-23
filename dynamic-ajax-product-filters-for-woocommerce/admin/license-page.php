<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the WordPress.org-compliant Pro installation guidance.
 *
 * The directory-hosted free plugin must not download, install, update, or
 * activate executable code from an external server. Pro licensing and updates
 * are handled by the separately installed Pro plugin.
 */
function dapfforwc_render_pro_installation_guidance()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'dynamic-ajax-product-filters-for-woocommerce'));
    }

    $pro_plugin_file = 'dynamic-ajax-product-filters-for-woocommerce-pro/dynamic-ajax-product-filters-for-woocommerce-pro.php';
    $pro_plugin_path = WP_PLUGIN_DIR . '/' . $pro_plugin_file;
    $is_pro_installed = file_exists($pro_plugin_path);
    $is_pro_active = is_plugin_active($pro_plugin_file)
        || (is_multisite() && is_plugin_active_for_network($pro_plugin_file));
    $can_install_plugins = current_user_can('install_plugins');
    $product_url = 'https://plugincy.com/dynamic-ajax-product-filters-for-woocommerce/';
    $account_url = 'https://plugincy.com/my-account/';
    $plugins_url = self_admin_url('plugins.php');
    $upload_url = self_admin_url('plugin-install.php?tab=upload');

    if ($is_pro_active) {
        $status_class = 'is-active';
        $status_icon = 'yes-alt';
        $status_label = esc_html__('Pro plugin active', 'dynamic-ajax-product-filters-for-woocommerce');
        $status_title = esc_html__('Your Pro setup is ready', 'dynamic-ajax-product-filters-for-woocommerce');
        $status_message = esc_html__('The Pro plugin is active. Manage licensing, premium updates, and account actions from the Pro plugin settings.', 'dynamic-ajax-product-filters-for-woocommerce');
    } elseif ($is_pro_installed) {
        $status_class = 'is-warning';
        $status_icon = 'warning';
        $status_label = esc_html__('Action needed', 'dynamic-ajax-product-filters-for-woocommerce');
        $status_title = esc_html__('Pro is installed but inactive', 'dynamic-ajax-product-filters-for-woocommerce');
        $status_message = esc_html__('Activate the Pro plugin from Installed Plugins, then open the Pro settings to complete license management.', 'dynamic-ajax-product-filters-for-woocommerce');
    } else {
        $status_class = 'is-ready';
        $status_icon = 'star-filled';
        $status_label = esc_html__('Upgrade available', 'dynamic-ajax-product-filters-for-woocommerce');
        $status_title = esc_html__('Upgrade to Dynamic AJAX Product Filters Pro', 'dynamic-ajax-product-filters-for-woocommerce');
        $status_message = esc_html__('Unlock advanced WooCommerce filtering controls, premium templates, SEO options, and priority Plugincy support with the separate Pro plugin.', 'dynamic-ajax-product-filters-for-woocommerce');
    }
    ?>
    <div class="dapfforwc-pro-installation">
        <section class="dapfforwc-pro-hero <?php echo esc_attr($status_class); ?>">
            <div class="dapfforwc-pro-hero-content">
                <span class="dapfforwc-pro-eyebrow">
                    <span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
                    <?php esc_html_e('Plugincy Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                </span>
                <h2><?php echo esc_html($status_title); ?></h2>
                <p><?php echo esc_html($status_message); ?></p>
                <div class="dapfforwc-pro-actions">
                    <?php if ($is_pro_active) : ?>
                        <a class="dapfforwc-pro-button is-secondary" href="<?php echo esc_url($plugins_url); ?>">
                            <span class="dashicons dashicons-admin-plugins" aria-hidden="true"></span>
                            <?php esc_html_e('View Installed Plugins', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </a>
                    <?php elseif ($is_pro_installed) : ?>
                        <a class="dapfforwc-pro-button is-primary" href="<?php echo esc_url($plugins_url); ?>">
                            <span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
                            <?php esc_html_e('Open Installed Plugins', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </a>
                    <?php else : ?>
                        <a class="dapfforwc-pro-button is-primary" href="<?php echo esc_url($product_url); ?>" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-cart" aria-hidden="true"></span>
                            <?php esc_html_e('Get Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </a>
                        <a class="dapfforwc-pro-button is-secondary" href="<?php echo esc_url($account_url); ?>" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons dashicons-download" aria-hidden="true"></span>
                            <?php esc_html_e('Download from My Account', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </a>
                        <?php if ($can_install_plugins) : ?>
                            <a class="dapfforwc-pro-button is-ghost" href="<?php echo esc_url($upload_url); ?>">
                                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                                <?php esc_html_e('Upload Pro ZIP', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dapfforwc-pro-status-card">
                <span class="dapfforwc-pro-status-icon">
                    <span class="dashicons dashicons-<?php echo esc_attr($status_icon); ?>" aria-hidden="true"></span>
                </span>
                <span class="dapfforwc-pro-status-label"><?php echo esc_html($status_label); ?></span>
                <strong><?php esc_html_e('Dynamic AJAX Product Filters Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                <p>
                    <?php
                    if ($is_pro_active) {
                        esc_html_e('Premium extension detected and running on this site.', 'dynamic-ajax-product-filters-for-woocommerce');
                    } elseif ($is_pro_installed) {
                        esc_html_e('Premium extension files are present and waiting for activation.', 'dynamic-ajax-product-filters-for-woocommerce');
                    } else {
                        esc_html_e('Install the separate Pro plugin ZIP to add licensing and premium update controls.', 'dynamic-ajax-product-filters-for-woocommerce');
                    }
                    ?>
                </p>
            </div>
        </section>

        <div class="dapfforwc-pro-grid">
            <section class="dapfforwc-pro-panel">
                <div class="dapfforwc-pro-panel-heading">
                    <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                    <h3><?php esc_html_e('Setup Workflow', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h3>
                </div>
                <ol class="dapfforwc-pro-steps">
                    <li>
                        <span>1</span>
                        <div>
                            <strong><?php esc_html_e('Get the Pro ZIP', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                            <p><?php esc_html_e('Purchase or download the Pro package from your Plugincy account.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                        </div>
                    </li>
                    <li>
                        <span>2</span>
                        <div>
                            <strong><?php esc_html_e('Upload through WordPress', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                            <p><?php esc_html_e('Open Plugins > Add Plugin > Upload Plugin, then upload the Pro ZIP.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                        </div>
                    </li>
                    <li>
                        <span>3</span>
                        <div>
                            <strong><?php esc_html_e('Activate and manage license', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                            <p><?php esc_html_e('Activate Pro, then enter and manage your license inside the Pro plugin settings.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                        </div>
                    </li>
                </ol>
                <?php if (!$can_install_plugins) : ?>
                    <div class="dapfforwc-pro-note is-warning">
                        <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                        <p><?php esc_html_e('Ask a site or network administrator with plugin installation permission to upload the Pro ZIP.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="dapfforwc-pro-panel">
                <div class="dapfforwc-pro-panel-heading">
                    <span class="dashicons dashicons-awards" aria-hidden="true"></span>
                    <h3><?php esc_html_e('Premium Benefits', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h3>
                </div>
                <div class="dapfforwc-pro-features">
                    <div>
                        <span class="dashicons dashicons-filter" aria-hidden="true"></span>
                        <strong><?php esc_html_e('Advanced Filters', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                        <p><?php esc_html_e('Extend product discovery with premium filtering controls.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                    </div>
                    <div>
                        <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
                        <strong><?php esc_html_e('SEO Controls', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                        <p><?php esc_html_e('Build richer URL and permalink experiences for filtered pages.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                    </div>
                    <div>
                        <span class="dashicons dashicons-sos" aria-hidden="true"></span>
                        <strong><?php esc_html_e('Priority Support', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                        <p><?php esc_html_e('Get faster help from the Plugincy support team.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                    </div>
                    <div>
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <strong><?php esc_html_e('Premium Updates', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
                        <p><?php esc_html_e('Receive Pro improvements and compatibility updates.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                    </div>
                </div>
            </section>
        </div>

        <div class="dapfforwc-pro-compliance">
            <span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
            <p>
                <?php esc_html_e('For WordPress.org security compliance, this free plugin does not download, install, activate, or update executable Pro packages from an external server. It also does not delete existing Pro license data. Licensing and premium updates are handled by the separately installed Pro plugin.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
            </p>
        </div>
    </div>
    <?php ob_start(); ?>
        .dapfforwc-pro-installation {
            color: #1f2937;
            padding-top: 20px;
        }

        .dapfforwc-pro-hero {
            background: linear-gradient(135deg, #fff7f3 0%, #ffffff 45%, #f4f7ff 100%);
            border: 1px solid #f0e4dd;
            border-radius: 12px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 24px;
            overflow: hidden;
            padding: 28px;
            position: relative;
        }

        .dapfforwc-pro-hero::before {
            background: #ff5a36;
            content: "";
            height: 100%;
            left: 0;
            position: absolute;
            top: 0;
            width: 5px;
        }

        .dapfforwc-pro-eyebrow,
        .dapfforwc-pro-status-label {
            align-items: center;
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            letter-spacing: 0;
            line-height: 1;
            text-transform: uppercase;
        }

        .dapfforwc-pro-eyebrow {
            background: rgba(255, 90, 54, 0.1);
            color: #d93f1f;
            margin-bottom: 12px;
            padding: 8px 10px;
        }

        .dapfforwc-pro-eyebrow .dashicons,
        .dapfforwc-pro-status-label .dashicons {
            font-size: 14px;
            height: 14px;
            width: 14px;
        }

        .dapfforwc-pro-hero h2 {
            color: #111827;
            font-size: 30px;
            font-weight: 800;
            line-height: 1.18;
            margin: 0 0 12px;
            max-width: 720px;
        }

        .dapfforwc-pro-hero p,
        .dapfforwc-pro-panel p,
        .dapfforwc-pro-compliance p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .dapfforwc-pro-hero-content {
            min-width: 0;
        }

        .dapfforwc-pro-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }

        .dapfforwc-pro-button {
            align-items: center;
            border-radius: 8px;
            display: inline-flex;
            font-size: 14px;
            font-weight: 700;
            gap: 8px;
            min-height: 40px;
            padding: 0 16px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .dapfforwc-pro-button:focus {
            box-shadow: 0 0 0 3px rgba(255, 90, 54, 0.2);
            outline: none;
        }

        .dapfforwc-pro-button .dashicons {
            font-size: 16px;
            height: 16px;
            width: 16px;
        }

        .dapfforwc-pro-button.is-primary {
            background: #ff5a36;
            box-shadow: 0 10px 20px rgba(255, 90, 54, 0.22);
            color: #fff;
        }

        .dapfforwc-pro-button.is-primary:hover {
            background: #e64c2a;
            color: #fff;
            transform: translateY(-1px);
        }

        .dapfforwc-pro-button.is-secondary {
            background: #667eea;
            color: #fff;
        }

        .dapfforwc-pro-button.is-secondary:hover {
            background: #5a67d8;
            color: #fff;
            transform: translateY(-1px);
        }

        .dapfforwc-pro-button.is-ghost {
            background: #fff;
            border: 1px solid #d8dee9;
            color: #344054;
        }

        .dapfforwc-pro-button.is-ghost:hover {
            border-color: #ffb29f;
            color: #d93f1f;
            transform: translateY(-1px);
        }

        .dapfforwc-pro-status-card,
        .dapfforwc-pro-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .dapfforwc-pro-status-card {
            align-self: stretch;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 190px;
            padding: 22px;
        }

        .dapfforwc-pro-status-icon {
            align-items: center;
            background: #fff1ec;
            border-radius: 10px;
            color: #ff5a36;
            display: inline-flex;
            height: 42px;
            justify-content: center;
            margin-bottom: 14px;
            width: 42px;
        }

        .dapfforwc-pro-status-icon .dashicons {
            font-size: 22px;
            height: 22px;
            width: 22px;
        }

        .dapfforwc-pro-status-label {
            background: #f8fafc;
            color: #475569;
            margin-bottom: 10px;
            padding: 7px 9px;
            width: fit-content;
        }

        .dapfforwc-pro-status-card strong {
            color: #111827;
            display: block;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .dapfforwc-pro-hero.is-active::before {
            background: #16a34a;
        }

        .dapfforwc-pro-hero.is-active .dapfforwc-pro-eyebrow,
        .dapfforwc-pro-hero.is-active .dapfforwc-pro-status-icon {
            background: #ecfdf3;
            color: #15803d;
        }

        .dapfforwc-pro-hero.is-warning::before {
            background: #f59e0b;
        }

        .dapfforwc-pro-hero.is-warning .dapfforwc-pro-eyebrow,
        .dapfforwc-pro-hero.is-warning .dapfforwc-pro-status-icon {
            background: #fffbeb;
            color: #b45309;
        }

        .dapfforwc-pro-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
            margin-top: 18px;
        }

        .dapfforwc-pro-panel {
            box-shadow: none;
            padding: 22px;
        }

        .dapfforwc-pro-panel-heading {
            align-items: center;
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }

        .dapfforwc-pro-panel-heading .dashicons {
            align-items: center;
            background: #f4f7ff;
            border-radius: 8px;
            color: #667eea;
            display: inline-flex;
            font-size: 18px;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .dapfforwc-pro-panel h3 {
            color: #111827;
            font-size: 18px;
            margin: 0;
        }

        .dapfforwc-pro-steps {
            display: grid;
            gap: 14px;
            margin: 0;
        }

        .dapfforwc-pro-steps li {
            align-items: flex-start;
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 8px;
            display: grid;
            gap: 12px;
            grid-template-columns: 30px minmax(0, 1fr);
            margin: 0;
            padding: 14px;
        }

        .dapfforwc-pro-steps li > span {
            align-items: center;
            background: #ff5a36;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            font-weight: 800;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .dapfforwc-pro-steps strong,
        .dapfforwc-pro-features strong {
            color: #1f2937;
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .dapfforwc-pro-features {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .dapfforwc-pro-features > div {
            background: #fff;
            border: 1px solid #edf2f7;
            border-radius: 8px;
            padding: 16px;
        }

        .dapfforwc-pro-features .dashicons {
            color: #ff5a36;
            font-size: 20px;
            height: 20px;
            margin-bottom: 10px;
            width: 20px;
        }

        .dapfforwc-pro-note,
        .dapfforwc-pro-compliance {
            align-items: flex-start;
            border-radius: 8px;
            display: flex;
            gap: 10px;
        }

        .dapfforwc-pro-note {
            background: #fffbeb;
            border: 1px solid #fde68a;
            margin-top: 14px;
            padding: 12px;
        }

        .dapfforwc-pro-note .dashicons {
            color: #b45309;
            flex: 0 0 auto;
        }

        .dapfforwc-pro-compliance {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            margin-top: 18px;
            padding: 14px 16px;
        }

        .dapfforwc-pro-compliance .dashicons {
            color: #667eea;
            flex: 0 0 auto;
            margin-top: 2px;
        }

        @media (max-width: 960px) {
            .dapfforwc-pro-hero,
            .dapfforwc-pro-grid {
                grid-template-columns: 1fr;
            }

            .dapfforwc-pro-status-card {
                min-height: auto;
            }
        }

        @media (max-width: 640px) {
            .dapfforwc-pro-installation {
                padding-top: 14px;
            }

            .dapfforwc-pro-hero,
            .dapfforwc-pro-panel {
                padding: 18px;
            }

            .dapfforwc-pro-hero h2 {
                font-size: 24px;
            }

            .dapfforwc-pro-actions,
            .dapfforwc-pro-button {
                width: 100%;
            }

            .dapfforwc-pro-button {
                justify-content: center;
            }

            .dapfforwc-pro-features {
                grid-template-columns: 1fr;
            }
        }
    <?php dapfforwc_add_inline_style(ob_get_clean(), 'dapfforwc-admin-style'); ?>
    <?php
}
