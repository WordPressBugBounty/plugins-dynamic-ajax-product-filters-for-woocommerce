<?php
// form_template_tab.php

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This admin template uses local template variables; plugin functions, options, and hooks remain prefixed.

global $template_options, $dapfforwc_allowed_tags;
?>

<div class="dapfforwc-template-container">
    <div class="dapfforwc-template-header">
        <h2><?php echo esc_html__('Form Templates', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h2>
        <p class="description"><?php echo esc_html__('Choose a template design and customize colors to match your store\'s branding.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
    </div>

    <!-- Loading overlay -->
    <div class="dapfforwc-loading-overlay" style="display: none;">
        <div class="dapfforwc-spinner"></div>
        <p>Activating template...</p>
    </div>

    <div class="dapfforwc-templates-grid">
        <?php
        // Define available templates with enhanced SVG previews
        $templates = [
            [
                'id' => 'clean',
                'name' => esc_html__('Minimal Template', 'dynamic-ajax-product-filters-for-woocommerce'),
                'type' => 'free',
                'image' => '<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg">
            <rect width="200" height="150" fill="#fff" stroke="#e2e8f0"/>
            <text x="20" y="30" font-family="Arial" font-size="12" font-weight="600" fill="#2d3748">Category</text>
            <line x1="20" y1="38" x2="180" y2="38" stroke="#e2e8f0"/>
            <rect x="20" y="50" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="38" y="59" font-family="Arial" font-size="11" fill="#4a5568">Laptop</text>
            <rect x="20" y="70" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="38" y="79" font-family="Arial" font-size="11" fill="#4a5568">MacBook</text>
            <rect x="20" y="90" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="38" y="99" font-family="Arial" font-size="11" fill="#4a5568">Desktop</text>
            <rect x="20" y="110" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="38" y="119" font-family="Arial" font-size="11" fill="#4a5568">Linux</text>
        </svg>',
                'description' => esc_html__('Simple, No border, no shadow.', 'dynamic-ajax-product-filters-for-woocommerce'),
                'features' => ['No box shadow', 'No borders', 'Lightweight']
            ],
            [
                'id' => 'shadow',
                'name' => esc_html__('Elevated Template', 'dynamic-ajax-product-filters-for-woocommerce'),
                'type' => 'free',
                'image' => '<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <filter id="ds"><feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000" flood-opacity="0.15"/></filter>
            </defs>
            <rect x="14" y="12" width="172" height="126" rx="8" fill="#fff" stroke="#e2e8f0" filter="url(#ds)"/>
            <text x="30" y="42" font-family="Arial" font-size="12" font-weight="600" fill="#2d3748">Category</text>
            <line x1="30" y1="48" x2="170" y2="48" stroke="#e2e8f0"/>
            <rect x="30" y="62" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="48" y="71" font-family="Arial" font-size="11" fill="#4a5568">Laptop</text>
            <rect x="30" y="82" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="48" y="91" font-family="Arial" font-size="11" fill="#4a5568">MacBook</text>
            <rect x="30" y="102" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="48" y="111" font-family="Arial" font-size="11" fill="#4a5568">Desktop</text>
            <rect x="30" y="122" width="10" height="10" fill="none" stroke="#cbd5e0"/>
            <text x="48" y="131" font-family="Arial" font-size="11" fill="#4a5568">Linux</text>
        </svg>',
                'description' => esc_html__('Card UI with subtle box shadow (matches provided reference).', 'dynamic-ajax-product-filters-for-woocommerce'),
                'features' => ['Box shadow card', 'Soft border', 'Compact spacing']
            ],
            [
                'id' => 'modern',
                'name' => esc_html__('Modern Template', 'dynamic-ajax-product-filters-for-woocommerce'),
                'type' => 'pro',
                'image' => '<svg width="200" height="150" viewBox="-14 -14 302 372" xmlns="http://www.w3.org/2000/svg"><defs><filter id="softShadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="#000" flood-opacity=".12"/></filter></defs><g filter="url(#softShadow)"><rect x="12" y="12" width="276" height="280" rx="10" fill="#fff"/></g><path d="M10 25v260" stroke="#0f7abf" stroke-width="3" stroke-linecap="round"/><text x="36" y="45" fill="#333" style="font:600 16px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial">Categories</text><path style="stroke:#0f7abf;stroke-width:3;stroke-linecap:round" d="M36 60h226"/><text style="font:500 14px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#2b2b2b" x="36" y="95">Surgical Gastro</text><g transform="translate(246 86)"><circle fill="#0f7abf" r="14"/><text style="font:700 12px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#fff;text-anchor:middle;dominant-baseline:central">9</text></g><path style="stroke:#e8e8e8;stroke-width:1" d="M60 110v40"/><text style="font:500 13px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#4a4a4a" x="76" y="136">Camera Box</text><g transform="translate(246 129)"><circle fill="#0f7abf" r="14"/><text style="font:700 12px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#fff;text-anchor:middle;dominant-baseline:central">0</text></g><path style="stroke:#e8e8e8;stroke-width:1" d="M36 160h226"/><text style="font:500 14px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#2b2b2b" x="36" y="190">Urology</text><g transform="translate(246 181)"><circle fill="#0f7abf" r="14"/><text style="font:700 12px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#fff;text-anchor:middle;dominant-baseline:central">0</text></g><path style="stroke:#e8e8e8;stroke-width:1" d="M60 206v64"/><rect x="64" y="214" width="190" height="26" rx="4" style="fill:#f4f9fe"/><text style="font:500 13px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#0f7abf" x="76" y="232">Olympus</text><g transform="translate(246 227)"><circle fill="#0f7abf" r="14"/><text style="font:700 12px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#fff;text-anchor:middle;dominant-baseline:central">0</text></g><text style="font:500 13px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#4a4a4a" x="76" y="266">Stor 2 Box</text><g transform="translate(246 259)"><circle fill="#0f7abf" r="14"/><text style="font:700 12px system-ui,-apple-system,Segoe UI,Roboto,&quot;Helvetica Neue&quot;,Arial;fill:#fff;text-anchor:middle;dominant-baseline:central">0</text></g></svg>',
                'description' => esc_html__('A clean and contemporary card design with a balanced mix of shadow and borders. Perfect for modern eCommerce layouts.', 'dynamic-ajax-product-filters-for-woocommerce'),
                'features' => ['Subtle card shadow for depth', 'Soft borders for clarity', 'Compact spacing for neat alignment']
            ],
            [
                'id' => 'basic',
                'name' => esc_html__('Classic Template', 'dynamic-ajax-product-filters-for-woocommerce'),
                'type' => 'pro',
                'image' => '<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg"><path fill="#fff" stroke="#e2e8f0" d="M0 0h200v150H0z"/><text x="20" y="30" font-family="Arial" font-size="12" font-weight="600" fill="#2d3748">Category</text><path fill="none" stroke="#cbd5e0" d="M20 50h10v10H20z"/><text x="38" y="59" font-family="Arial" font-size="11" fill="#4a5568">Laptop</text><text x="100" y="59" font-family="Arial" font-size="10" fill="#a0aec0">(12)</text><path fill="none" stroke="#cbd5e0" d="M20 70h10v10H20z"/><text x="38" y="79" font-family="Arial" font-size="11" fill="#4a5568">MacBook</text><text x="100" y="79" font-family="Arial" font-size="10" fill="#a0aec0">(5)</text><path fill="none" stroke="#cbd5e0" d="M20 90h10v10H20z"/><text x="38" y="99" font-family="Arial" font-size="11" fill="#4a5568">Desktop</text><text x="100" y="99" font-family="Arial" font-size="10" fill="#a0aec0">(8)</text><path fill="none" stroke="#cbd5e0" d="M20 110h10v10H20z"/><text x="38" y="119" font-family="Arial" font-size="11" fill="#4a5568">Linux</text><text x="100" y="119" font-family="Arial" font-size="10" fill="#a0aec0">(3)</text></svg>',
                'description' => esc_html__('A timeless and versatile filter style with soft shadows and structured spacing. Works with any theme.', 'dynamic-ajax-product-filters-for-woocommerce'),
                'features' => ['Box shadow card for subtle emphasis', 'Soft border separation', 'Compact and space-efficient layout']
            ],
            [
                'id' => 'basic_bordered',
                'name' => esc_html__('Bordered Template', 'dynamic-ajax-product-filters-for-woocommerce'),
                'type' => 'pro',
                'image' => '<svg width="220" height="170" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#fff"/><text x="16" y="24" font-family="Arial" font-size="13" font-weight="700" fill="#2d3748" letter-spacing=".3">FILTRÉ PAR</text><path stroke="#e2e8f0" stroke-width="3" stroke-linecap="round" d="M16 32h32"/><text x="16" y="64" font-family="Arial" font-size="13" fill="#2d3748">Bleu</text><text x="204" y="64" font-family="Arial" font-size="12" fill="#a0aec0" text-anchor="end">(12)</text><path stroke="#edf2f7" d="M16 76h188"/><text x="16" y="96" font-family="Arial" font-size="13" fill="#2d3748">Gris</text><text x="204" y="96" font-family="Arial" font-size="12" fill="#a0aec0" text-anchor="end">(33)</text><path stroke="#edf2f7" d="M16 108h188"/><text x="16" y="128" font-family="Arial" font-size="13" fill="#2d3748">Marron</text><text x="204" y="128" font-family="Arial" font-size="12" fill="#a0aec0" text-anchor="end">(13)</text><path stroke="#edf2f7" d="M16 140h188"/><text x="16" y="160" font-family="Arial" font-size="13" fill="#2d3748">Noisette</text><text x="204" y="160" font-family="Arial" font-size="12" fill="#a0aec0" text-anchor="end">(12)</text></svg>',
                'description' => esc_html__('A sharper design with clear borders and neat spacing, ideal for structured product categories.', 'dynamic-ajax-product-filters-for-woocommerce'),
                'features' => ['Defined soft borders for separation', 'Subtle box shadow for balance', 'Compact spacing for organized display']
            ]
        ];

        foreach ($templates as $template) {
            $is_active = ($template['id'] === $template_options['active_template']);
            $template_type = isset($template['type']) ? $template['type'] : 'free';
        ?>
            <div class="dapfforwc-template-card <?php echo $is_active ? 'active' : ''; ?>" data-template-id="<?php echo esc_attr($template['id']); ?>">
                <div class="dapfforwc-template-image">
                    <?php echo wp_kses($template['image'], $dapfforwc_allowed_tags); ?>
                    <div class="dapfforwc-template-status">
                        <?php if ($is_active && $template_type === 'free') : ?>
                            <span class="active-badge"><?php esc_html_e('Active', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                        <?php else : ?>
                            <span class="inactive-badge"><?php esc_html_e('Inactive', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dapfforwc-template-type">
                        <?php if ($template_type === 'free') : ?>
                            <span class="free-badge"><?php esc_html_e('Free', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                        <?php else : ?>
                            <span class="paid-badge"><?php esc_html_e('Premium', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dapfforwc-template-overlay">
                        <button class="preview-template-btn" data-template="<?php echo esc_attr($template['id']); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Quick Preview', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </button>
                    </div>
                </div>
                <div class="dapfforwc-template-content">
                    <h3><?php echo esc_html($template['name']); ?></h3>
                    <p><?php echo esc_html($template['description']); ?></p>

                    <?php if (!empty($template['features'])) : ?>
                        <ul class="template-features">
                            <?php foreach ($template['features'] as $feature) : ?>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div class="dapfforwc-template-actions">
                        <?php if (!$is_active && $template_type === 'free') : ?>
                            <button class="button button-primary activate-template" data-template="<?php echo esc_attr($template['id']); ?>">
                                <span class="button-text"><?php esc_html_e('Activate', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                                <span class="button-spinner" style="display: none;">
                                    <span class="spinner is-active"></span>
                                </span>
                            </button>
                        <?php elseif($is_active && $template_type === 'free') : ?>
                            <span class="current-template">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Activated', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                            </span>
                        <?php else: ?>
                            <span class="button" style="border: 1px solid #ff5a36;color:#ff5a36">
                                <?php esc_html_e('Pro Feature Only', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                            </span>
                        <?php endif; ?>
                        <button class="button preview-template" data-template="<?php echo esc_attr($template['id']); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Preview', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
        <!-- Coming soon card -->
        <div class="dapfforwc-template-card coming-soon" aria-hidden="true">
            <div class="dapfforwc-template-image" style="background:#f8f9fa;">
                <div style="text-align:center;">
                    <svg width="200" height="120" xmlns="http://www.w3.org/2000/svg">
                        <rect x="10" y="20" width="180" height="90" rx="10" fill="#fff" stroke="#e2e8f0" stroke-dasharray="6,6" />
                        <text x="100" y="70" text-anchor="middle" font-family="Arial" font-size="12" fill="#a0aec0">
                            <?php echo esc_html__('More templates coming soon', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                        </text>
                    </svg>
                </div>
            </div>
            <div class="dapfforwc-template-content">
                <h3><?php echo esc_html__('More coming soon', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h3>
                <p><?php echo esc_html__('We’re crafting additional styles and layouts. Stay tuned!', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
            </div>
        </div>
    </div>

    <div class="dapfforwc-color-settings">
        <h2><?php esc_html_e('Color Settings', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('dapfforwc_template_options_group'); ?>
            <input type="hidden" name="dapfforwc_template_options[active_template]" value="<?php echo esc_attr($template_options['active_template']); ?>">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Background Color', 'dynamic-ajax-product-filters-for-woocommerce'); ?></th>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="text"
                                name="dapfforwc_template_options[background_color]"
                                value="<?php echo esc_attr(isset($template_options['background_color']) ? $template_options['background_color'] : '#ffffffb3'); ?>"
                                class="dapfforwc-color-picker"
                                id="dapfforwc-bg-color"
                                data-alpha="true"
                                data-default-color="#ffffffb3"
                                style="width:160px;">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Primary Color', 'dynamic-ajax-product-filters-for-woocommerce'); ?></th>
                    <td>
                        <input type="text"
                            name="dapfforwc_template_options[primary_color]"
                            value="<?php echo esc_attr(isset($template_options['primary_color']) ? $template_options['primary_color'] : '#432fb8'); ?>"
                            class="dapfforwc-color-picker"
                            data-alpha="true"
                            data-default-color="#432fb8"
                            style="width:160px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Secondary Color', 'dynamic-ajax-product-filters-for-woocommerce'); ?></th>
                    <td>
                        <input type="text"
                            name="dapfforwc_template_options[secondary_color]"
                            value="<?php echo esc_attr(isset($template_options['secondary_color']) ? $template_options['secondary_color'] : '#ff4d4d'); ?>"
                            class="dapfforwc-color-picker"
                            data-alpha="true"
                            data-default-color="#ff4d4d"
                            style="width:160px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Border Color', 'dynamic-ajax-product-filters-for-woocommerce'); ?></th>
                    <td>
                        <input type="text"
                            name="dapfforwc_template_options[border_color]"
                            value="<?php echo esc_attr(isset($template_options['border_color']) ? $template_options['border_color'] : '#eeeeee'); ?>"
                            class="dapfforwc-color-picker"
                            data-alpha="true"
                            data-default-color="#eeeeee"
                            style="width:160px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'); ?></th>
                    <td>
                        <input type="text"
                            name="dapfforwc_template_options[text_color]"
                            value="<?php echo esc_attr(isset($template_options['text_color']) ? $template_options['text_color'] : '#000000'); ?>"
                            class="dapfforwc-color-picker"
                            data-alpha="true"
                            data-default-color="#000000"
                            style="width:160px;">
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php ob_start(); ?>
            jQuery(document).ready(function($) {
                // Initialize color pickers with alpha support
                $('.dapfforwc-color-picker').each(function() {
                    var $input = $(this);
                    var defaultColor = $input.data('default-color');

                    $input.wpColorPicker({
                        defaultColor: defaultColor,
                        change: function(event, ui) {
                            // Update value when color changes
                            $input.val(ui.color.toString());
                        },
                        clear: function() {
                            // Reset to default color
                            $input.val(defaultColor);
                        },
                        palettes: true
                    });
                });
            });
        <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>
    </div>

</div>

<!-- Preview Modal -->
<div id="dapfforwc-preview-modal" class="dapfforwc-modal" style="display: none;">
    <div class="dapfforwc-modal-backdrop"></div>
    <div class="dapfforwc-modal-content">
        <div class="dapfforwc-modal-header">
            <h3><?php esc_html_e('Template Preview', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h3>
            <button class="dapfforwc-modal-close">&times;</button>
        </div>
        <div class="dapfforwc-modal-body">
            <div class="preview-container">
                <div class="preview-description">
                    <h4 id="preview-template-name"></h4>
                    <p id="preview-template-description"></p>
                    <div class="preview-features" id="preview-features"></div>
                </div>
                <div class="preview-demo">
                    <div class="demo-filter-widget" id="demo-widget">
                        <!-- Dynamic content will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
        <div class="dapfforwc-modal-footer">
            <button class="button button-primary" id="activate-from-preview"><?php esc_html_e('Activate This Template', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
            <button class="button" id="close-preview"><?php esc_html_e('Close Preview', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
    .dapfforwc-template-container {
        padding: 20px 0;
        position: relative;
    }

    .dapfforwc-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        z-index: 10000;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .dapfforwc-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 10px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .dapfforwc-templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        padding: 30px 0;
    }

    .dapfforwc-template-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        position: relative;
    }

    .dapfforwc-template-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
    }

    .dapfforwc-template-card.active {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    .dapfforwc-template-image {
        position: relative;
        height: 180px;
        overflow: hidden;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dapfforwc-template-image svg {
        transition: transform 0.3s ease;
    }

    .dapfforwc-template-card:hover .dapfforwc-template-image svg {
        transform: scale(1.05);
    }

    .dapfforwc-template-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .dapfforwc-template-card:hover .dapfforwc-template-overlay {
        opacity: 1;
    }

    .preview-template-btn {
        background: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        color: #333;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }

    .preview-template-btn:hover {
        background: #667eea;
        color: #fff;
        transform: scale(1.05);
    }

    .dapfforwc-template-status {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 2;
    }

    .active-badge,
    .inactive-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .active-badge {
        background: #667eea;
        color: #fff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .inactive-badge {
        background: rgba(255, 255, 255, 0.9);
        color: #718096;
        border: 1px solid #e2e8f0;
    }

    .dapfforwc-template-type {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 2;
    }

    .free-badge,
    .paid-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .free-badge {
        background: #48bb78;
        color: #fff;
        box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
    }

    .paid-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }


    .dapfforwc-template-content {
        padding: 25px;
    }

    .dapfforwc-template-content h3 {
        margin: 0 0 10px;
        font-size: 18px;
        color: #2d3748;
        font-weight: 600;
    }

    .dapfforwc-template-content p {
        margin: 0 0 15px;
        color: #718096;
        font-size: 14px;
        line-height: 1.6;
    }

    .template-features {
        margin: 15px 0;
        padding: 0;
        list-style: none;
    }

    .template-features li {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 5px;
        font-size: 13px;
        color: #4a5568;
    }

    .template-features .dashicons {
        color: #667eea;
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    .dapfforwc-template-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .dapfforwc-template-actions .button {
        flex: 1;
        text-align: center;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .current-template {
        flex: 1;
        padding: 8px 12px;
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        border-radius: 6px;
        color: #276749;
        text-align: center;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .current-template .dashicons {
        color: #48bb78;
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    /* Modal Styles */
    .dapfforwc-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dapfforwc-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .dapfforwc-modal-content {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow: hidden;
        position: relative;
        z-index: 1;
    }

    .dapfforwc-modal-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dapfforwc-modal-header h3 {
        margin: 0;
        font-size: 20px;
        color: #2d3748;
    }

    .dapfforwc-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #718096;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .dapfforwc-modal-close:hover {
        background: #f7fafc;
        color: #2d3748;
    }

    .dapfforwc-modal-body {
        padding: 25px;
        max-height: 60vh;
        overflow-y: auto;
    }

    .preview-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        align-items: start;
    }

    .preview-description h4 {
        margin: 0 0 10px;
        font-size: 18px;
        color: #2d3748;
    }

    .preview-description p {
        margin: 0 0 20px;
        color: #718096;
        line-height: 1.6;
    }

    .preview-features {
        background: #f7fafc;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }

    .preview-features h5 {
        margin: 0 0 10px;
        font-size: 14px;
        color: #2d3748;
        font-weight: 600;
    }

    .preview-features ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .preview-features li {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 13px;
        color: #4a5568;
    }

    .preview-features .dashicons {
        color: #667eea;
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    .demo-filter-widget {
        background: #f8f9fa;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        min-height: 300px;
    }

    .dapfforwc-modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #f7fafc;
    }

    .dapfforwc-color-settings {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .dapfforwc-color-settings h2 {
        margin-top: 0;
        padding-bottom: 15px;
        border-bottom: 1px solid #e2e8f0;
        color: #2d3748;
    }

    .dapfforwc-color-picker {
        width: 60px;
        height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        cursor: pointer;
    }

    /* Loading states */
    .activate-template.loading .button-text {
        opacity: 0;
    }

    .activate-template.loading .button-spinner {
        display: block !important;
    }

    .activate-template.loading {
        pointer-events: none;
    }

    /* Success animation */
    @keyframes successPulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }

    .dapfforwc-template-card.just-activated {
        animation: successPulse 0.6s ease-in-out;
    }

    .dapfforwc-template-card.coming-soon .dapfforwc-template-image {
        opacity: .85;
    }

    .dapfforwc-template-card.coming-soon .dapfforwc-template-actions {
        display: none;
    }


    /* Responsive */
    @media (max-width: 768px) {
        .dapfforwc-templates-grid {
            grid-template-columns: 1fr;
        }

        .preview-container {
            grid-template-columns: 1fr;
        }

        .dapfforwc-modal-content {
            width: 95%;
            margin: 10px;
        }
    }
<?php dapfforwc_add_inline_style(ob_get_clean(), 'dapfforwc-admin-style'); ?>

<?php ob_start(); ?>
/* global jQuery, ajaxurl, wp */
(function ($) {
  'use strict';

  /**
   * CONFIG
   * Prefer localizing this from PHP:
   * wp_localize_script( 'your-handle', 'dapfforwcConfig', [
   *   'templates'      => $templates,
   *   'activeTemplate' => $template_options['active_template'],
   *   'nonce'          => wp_create_nonce('dapfforwc_template_nonce'),
   *   'i18n'           => [ 'activate' => __( 'Activate', 'dynamic-ajax-product-filters-for-woocommerce' ), ... ]
   * ] );
   */
  const CONFIG = (function () {
    const i18nFallback = {
      activate:        <?php echo wp_json_encode( __( 'Activate', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      activated:       <?php echo wp_json_encode( __( 'Activated', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      active:          <?php echo wp_json_encode( __( 'Active', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      inactive:        <?php echo wp_json_encode( __( 'Inactive', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      proOnly:         <?php echo wp_json_encode( __( 'Pro Features Only', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      activateThis:    <?php echo wp_json_encode( __( 'Activate This Template', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      keyFeatures:     <?php echo wp_json_encode( __( 'Key Features:', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      errorActivate:   <?php echo wp_json_encode( __( 'Error activating template. Please try again.', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      networkError:    <?php echo wp_json_encode( __( 'Network error. Please try again.', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      category:        <?php echo wp_json_encode( __( 'Category', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      categories:      <?php echo wp_json_encode( __( 'Categories', 'dynamic-ajax-product-filters-for-woocommerce' ) ); ?>,
      dismissNotice:   'Dismiss this notice.'
    };

    // If you localized via wp_localize_script, use that first:
    const cfg = window.dapfforwcConfig || {};

    return {
      templates:      cfg.templates || <?php echo wp_json_encode( $templates ); ?>,
      activeTemplate: cfg.activeTemplate || <?php echo wp_json_encode( (string) ($template_options['active_template'] ?? '') ); ?>,
      nonce:          cfg.nonce || <?php echo wp_json_encode( wp_create_nonce('dapfforwc_template_nonce') ); ?>,
      i18n:           Object.assign(i18nFallback, cfg.i18n || {})
    };
  })();

  // ---------- Helpers ----------
  const $body    = $(document.body);
  const selectors = {
    card:              '.dapfforwc-template-card',
    activateBtn:       '.activate-template',
    currentBadge:      '.current-template',
    statusBadgeActive: '.active-badge',
    statusBadgeInact:  '.inactive-badge',
    loadingOverlay:    '.dapfforwc-loading-overlay',
    hiddenActiveInput: 'input[name="dapfforwc_template_options[active_template]"]',
    previewBtn:        '.preview-template, .preview-template-btn',
    modal:             '#dapfforwc-preview-modal',
    modalClose:        '.dapfforwc-modal-close, #close-preview, .dapfforwc-modal-backdrop',
    modalContent:      '.dapfforwc-modal-content',
    previewName:       '#preview-template-name',
    previewDesc:       '#preview-template-description',
    previewFeatures:   '#preview-features',
    previewActivate:   '#activate-from-preview',
    demoWidget:        '#demo-widget'
  };

  const ui = {
    showOverlay() { $(selectors.loadingOverlay).fadeIn(150); },
    hideOverlay() { $(selectors.loadingOverlay).fadeOut(150); },

    notify(type, message) {
      const $n = $(`
        <div class="notice notice-${type} is-dismissible dapfforwc-notification"
             style="position:fixed;top:32px;right:20px;z-index:100001;max-width:350px;animation:slideInRight .3s ease;">
          <p></p>
          <button type="button" class="notice-dismiss"><span class="screen-reader-text">${CONFIG.i18n.dismissNotice}</span></button>
        </div>
      `);
      $n.find('p').text(message);
      $body.append($n);

      // Auto-dismiss
      setTimeout(() => $n.fadeOut(200, () => $n.remove()), 4000);

      // Manual dismiss
      $n.on('click', '.notice-dismiss', function () {
        $(this).closest('.dapfforwc-notification').fadeOut(200, function () { $(this).remove(); });
      });
    },

    setButtonLoading($btn, loading) {
      if (loading) {
        $btn.addClass('loading').prop('disabled', true);
      } else {
        $btn.removeClass('loading').prop('disabled', false);
      }
    }
  };

  // ---------- Data ----------
  function getTemplateById(id) {
    return CONFIG.templates.find(t => String(t.id) === String(id)) || null;
  }

  // ---------- Activation Flow ----------
  function activateTemplate(templateId, $triggerBtn) {
    ui.showOverlay();
    if ($triggerBtn) ui.setButtonLoading($triggerBtn, true);

    return $.ajax({
      url: ajaxurl,
      type: 'POST',
      dataType: 'json',
      data: {
        action:      'dapfforwc_activate_template',
        template_id: templateId,
        nonce:       CONFIG.nonce
      }
    }).done(function (response) {
      if (response && response.success) {
        updateActiveTemplateUI(templateId);
        ui.notify('success', response.data || CONFIG.i18n.activated);
        // Optional micro-animation:
        const $card = $(`${selectors.card}[data-template-id="${templateId}"]`);
        $card.addClass('just-activated');
        setTimeout(() => $card.removeClass('just-activated'), 600);
      } else {
        ui.notify('error', (response && response.data) || CONFIG.i18n.errorActivate);
      }
    }).fail(function () {
      ui.notify('error', CONFIG.i18n.networkError);
    }).always(function () {
      if ($triggerBtn) ui.setButtonLoading($triggerBtn, false);
      ui.hideOverlay();
    });
  }

  function updateActiveTemplateUI(templateId) {
    // 1) Clear states
    const $cards = $(selectors.card);
    $cards.removeClass('active');

    // Update badges
    $cards.find(selectors.statusBadgeActive)
      .removeClass('active-badge')
      .addClass('inactive-badge')
      .text(CONFIG.i18n.inactive);

    // Replace any "current" static badges with actionable buttons
    $cards.find(selectors.currentBadge).each(function () {
      const $badge = $(this);
      // find host card to fetch its template-id
      const $hostCard = $badge.closest(selectors.card);
      const hostId = $hostCard.data('template-id');
      $badge.replaceWith(
        `<button class="button button-primary activate-template" data-template="${hostId}">
           <span class="button-text">${CONFIG.i18n.activate}</span>
           <span class="button-spinner" style="display:none;"><span class="spinner is-active"></span></span>
         </button>`
      );
    });

    // 2) Set the chosen one active
    const $activeCard = $(`${selectors.card}[data-template-id="${templateId}"]`);
    $activeCard.addClass('active');
    $activeCard.find(selectors.statusBadgeInact)
      .removeClass('inactive-badge')
      .addClass('active-badge')
      .text(CONFIG.i18n.active);

    // Swap button → static “Activated” pill
    $activeCard.find(selectors.activateBtn).replaceWith(
      `<span class="current-template">
         <span class="dashicons dashicons-yes-alt"></span> ${CONFIG.i18n.activated}
       </span>`
    );

    // 3) Persist in hidden field
    $(selectors.hiddenActiveInput).val(templateId);

    // 4) Update our in-memory value
    CONFIG.activeTemplate = String(templateId);
  }

  // ---------- Preview Modal ----------
  function openPreviewModal(template) {
    $(selectors.previewName).text(template.name || '');
    $(selectors.previewDesc).text(template.description || '');

    // Build features list
    const feats = Array.isArray(template.features) ? template.features : [];
    const featHtml = [
      `<h5>${CONFIG.i18n.keyFeatures}</h5>`,
      '<ul>',
      ...feats.map(f => `<li><span class="dashicons dashicons-yes-alt"></span> ${String(f)}</li>`),
      '</ul>'
    ].join('');
    $(selectors.previewFeatures).html(featHtml);

    // Build demo widget
    buildDemoWidget(template);

    // Configure activate button
    const $act = $(selectors.previewActivate).data('template', template.id);
    const isActive = String(template.id) === String(CONFIG.activeTemplate);
    const isFree   = String(template.type || 'free') === 'free';

    if (isActive && isFree) {
      $act.html(`<span class="dashicons dashicons-yes-alt"></span> ${CONFIG.i18n.activated}`)
          .prop('disabled', true)
          .removeClass('button-primary')
          .addClass('button-secondary');
    } else if (!isFree) {
      $act.text(CONFIG.i18n.proOnly)
          .prop('disabled', true)
          .removeClass('button-primary')
          .addClass('button-secondary');
    } else {
      $act.text(CONFIG.i18n.activateThis)
          .prop('disabled', false)
          .removeClass('button-secondary')
          .addClass('button-primary');
    }

    $(selectors.modal).fadeIn(200);
    $body.addClass('modal-open');
  }

  function closePreviewModal() {
    $(selectors.modal).fadeOut(200);
    $body.removeClass('modal-open');
  }

  // ---------- Demo Widget (purely illustrative) ----------
  function buildDemoWidget(template) {
    const catLabel  = CONFIG.i18n.category;
    const catsLabel = CONFIG.i18n.categories;

    let demoHtml = '';
    switch (String(template.id)) {
      case 'clean':
        demoHtml = `
          <div style="border:1px solid #e2e8f0;border-radius:8px;background:#fff;">
            <div style="padding:14px;border-bottom:1px solid #e2e8f0;">
              <h4 style="margin:0;font-size:14px;font-weight:600;color:#2d3748;">${catLabel}</h4>
            </div>
            <div style="padding:14px;">
              ${['Laptop','MacBook','Desktop','Linux','Tablet'].map(l => `
                <label style="display:flex;align-items:center;gap:8px;margin:8px 0;cursor:pointer;">
                  <input type="checkbox" style="accent-color:#667eea;">
                  <span style="font-size:13px;color:#4a5568;">${l}</span>
                </label>
              `).join('')}
            </div>
          </div>`;
        break;

      case 'shadow':
        demoHtml = `
          <div style="border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.08);">
            <div style="padding:16px;border-bottom:1px solid #e2e8f0;">
              <h4 style="margin:0;font-size:14px;font-weight:600;color:#2d3748;">${catLabel}</h4>
            </div>
            <div style="padding:16px;">
              ${['Laptop','MacBook','Desktop','Linux','Tablet'].map(l => `
                <label style="display:flex;align-items:center;gap:8px;margin:10px 0;cursor:pointer;">
                  <input type="checkbox" style="accent-color:#667eea;">
                  <span style="font-size:14px;color:#2d3748;">${l}</span>
                </label>
              `).join('')}
            </div>
          </div>`;
        break;

      case 'modern':
        demoHtml = `
          <div style="border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.08);width:240px;overflow:hidden;">
            <div style="padding:16px;border-bottom:1px solid #e2e8f0;">
              <h4 style="margin:0;font-size:14px;font-weight:600;color:#2d3748;">${catsLabel}</h4>
            </div>
            <div style="padding:12px 16px;">
              ${[
                {label:'Surgical Gastro', count:9, sub:[{label:'Camera Box',count:0}]},
                {label:'Urology', count:0, sub:[{label:'Olympus',count:0},{label:'Stor 2 Box',count:0}]}
              ].map(cat => `
                <div style="margin:10px 0;">
                  <div style="display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:600;color:#2d3748;margin-bottom:6px;">
                    <span>${cat.label}</span>
                    <span style="background:#3182ce;color:#fff;font-size:12px;font-weight:700;border-radius:9999px;min-width:22px;height:22px;display:flex;align-items:center;justify-content:center;">${cat.count}</span>
                  </div>
                  ${cat.sub.map(sub => `
                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#4a5568;margin:0 0 0 12px;padding:4px 0 4px 6px;border-left:1px solid #dbdbdb;cursor:pointer;"
                         onmouseover="this.style.background='#ebf8ff';this.style.color='#3182ce';this.style.borderLeft='3px solid #3182ce';"
                         onmouseout="this.style.background='';this.style.color='#4a5568';this.style.borderLeft='1px solid #dbdbdb';">
                      <span>${sub.label}</span>
                      <span style="background:#3182ce;color:#fff;font-size:12px;font-weight:700;border-radius:9999px;min-width:22px;height:22px;display:flex;align-items:center;justify-content:center;">${sub.count}</span>
                    </div>
                  `).join('')}
                </div>
              `).join('')}
            </div>
          </div>`;
        break;

      case 'basic':
        demoHtml = `
          <div style="border:1px solid #e2e8f0;border-radius:8px;background:#fff;">
            <div style="padding:14px 14px 0;">
              <h4 style="margin:0;font-size:14px;font-weight:600;color:#2d3748;">${catLabel}</h4>
            </div>
            <div style="padding:14px;">
              ${['Laptop','MacBook','Desktop','Linux','Tablet'].map(l => `
                <label style="display:flex;align-items:center;gap:8px;margin:8px 0;cursor:pointer;width:100%;">
                  <input type="checkbox" style="accent-color:#667eea;">
                  <span style="display:flex;justify-content:space-between;width:100%;">
                    <span style="font-size:13px;color:#4a5568;">${l}</span>
                    <span>(10)</span>
                  </span>
                </label>
              `).join('')}
            </div>
          </div>`;
        break;

      case 'basic_bordered': {
        const items = [
          { label: 'Laptop',  count: 12 },
          { label: 'MacBook', count: 33 },
          { label: 'Desktop', count: 13 },
          { label: 'Linux',   count: 12 },
          { label: 'Tablet',  count: 14 }
        ];
        demoHtml = `
          <div style="border:1px solid #e2e8f0;border-radius:8px;background:#fff;">
            <div style="padding:14px 14px 0;">
              <h4 style="margin:0;font-size:14px;font-weight:600;color:#2d3748;">${catLabel}</h4>
              <div style="height:3px;background-color:rgba(0,0,0,0.1);width:30px;margin:.5em 0 0;"></div>
            </div>
            <div style="padding:8px 14px 14px;">
              ${items.map((it, i) => `
                <label style="display:block;margin:12px 0 0;cursor:pointer;${i ? 'border-top:1px solid #edf2f7;padding-top:12px;' : ''}">
                  <span style="display:flex;justify-content:space-between;align-items:center;gap:8px;width:100%;">
                    <span style="font-size:13px;color:#2d3748;">${it.label}</span>
                    <span style="font-size:12px;color:#a0aec0;">(${it.count})</span>
                  </span>
                </label>
              `).join('')}
            </div>
          </div>`;
        break;
      }

      default:
        demoHtml = '<div style="padding:16px;color:#666;">No preview available.</div>';
    }

    $(selectors.demoWidget).html(demoHtml);
  }

  // ---------- Events (delegated) ----------
  $(document)
    // Activate from card button
    .on('click', selectors.activateBtn, function () {
      const $btn = $(this);
      const templateId = $btn.data('template');
      if (!templateId) return;
      activateTemplate(templateId, $btn);
    })

    // Open preview (from card or elsewhere)
    .on('click', selectors.previewBtn, function () {
      const templateId = $(this).data('template');
      const template   = getTemplateById(templateId);
      if (template) openPreviewModal(template);
    })

    // Activate from preview footer
    .on('click', selectors.previewActivate, function () {
      const templateId = $(this).data('template');
      if (!templateId) return;
      closePreviewModal();
      // Find the corresponding card button if present (nice UX), else pass null
      const $cardBtn = $(`${selectors.activateBtn}[data-template="${templateId}"]`).first();
      activateTemplate(templateId, $cardBtn.length ? $cardBtn : null);
    })

    // Modal closing
    .on('click', selectors.modalClose, function () {
      closePreviewModal();
    })

    // Prevent modal backdrop click from closing when clicking inside content
    .on('click', selectors.modalContent, function (e) {
      e.stopPropagation();
    });

  // ---------- One-time CSS (animation) ----------
  (function injectKeyframes() {
    const style = document.createElement('style');
    style.type = 'text/css';
    style.textContent = `
      @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to   { transform: translateX(0);   opacity: 1; }
      }
      .modal-open { overflow: hidden; }
    `;
    document.head.appendChild(style);
  })();

  // ---------- Initial paint (ensure correct active state on load) ----------
  if (CONFIG.activeTemplate) {
    updateActiveTemplateUI(CONFIG.activeTemplate);
  }

})(jQuery);
<?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>


<?php



// Helper function to convert hex to RGB
function dapfforwc_hex2rgb($hex)
{
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }

    return "$r, $g, $b";
}
