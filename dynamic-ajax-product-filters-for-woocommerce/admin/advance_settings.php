<?php
if (!defined('ABSPATH')) {
    exit;
}
// Render the "Product Selector" field
function dapfforwc_product_selector_callback()
{
    global $dapfforwc_advance_settings;
    $product_selector = isset($dapfforwc_advance_settings['product_selector']) ? esc_attr($dapfforwc_advance_settings['product_selector']) : '.products';
?>
    <input type="text" name="dapfforwc_advance_options[product_selector]" value="<?php echo esc_attr($product_selector); ?>" placeholder=".products">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the product container. Default is .products.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
    </p>
<?php
}

// Render the "Pagination Selector" field
function dapfforwc_pagination_selector_callback()
{
    global $dapfforwc_advance_settings;
    $pagination_selector = isset($dapfforwc_advance_settings['pagination_selector']) ? esc_attr($dapfforwc_advance_settings['pagination_selector']) : '.woocommerce-pagination';
?>
    <div class="dapfforwc-form-manage-setting">
        <input type="text" name="dapfforwc_advance_options[pagination_selector]" value="<?php echo esc_attr($pagination_selector); ?>" placeholder=".woocommerce-pagination">
        <?php
        dapfforwc_render_form_manage_popup(
            'dapfforwc-advanced-pagination-popup',
            __('Manage advanced pagination settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Advanced Pagination', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Set an explicit pagination mode only when your theme uses custom pagination markup or mixed pagination behaviors.', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_render_advanced_pagination_settings_popup'
        );
        ?>
    </div>
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the pagination container. Default is .woocommerce-pagination.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
    </p>
<?php
}

function dapfforwc_sorting_selector_callback()
{
    global $dapfforwc_advance_settings;
    $sorting_selector = isset($dapfforwc_advance_settings['sorting_selector']) ? esc_attr($dapfforwc_advance_settings['sorting_selector']) : 'form.woocommerce-ordering select';
?>
    <input type="text" name="dapfforwc_advance_options[sorting_selector]" value="<?php echo esc_attr($sorting_selector); ?>" placeholder="form.woocommerce-ordering select">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the WooCommerce sorting dropdown. Default is form.woocommerce-ordering select.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
    </p>
<?php
}

function dapfforwc_result_count_selector_callback()
{
    global $dapfforwc_advance_settings;
    $result_count_selector = isset($dapfforwc_advance_settings['result_count_selector']) ? esc_attr($dapfforwc_advance_settings['result_count_selector']) : '.woocommerce-result-count';
?>
    <input type="text" name="dapfforwc_advance_options[result_count_selector]" value="<?php echo esc_attr($result_count_selector); ?>" placeholder=".woocommerce-result-count">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the result count wrapper. Default is .woocommerce-result-count.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
    </p>
<?php
}

function dapfforwc_get_advanced_pagination_setting($key, $default = '')
{
    global $dapfforwc_advance_settings;

    if (!isset($dapfforwc_advance_settings[$key])) {
        return $default;
    }

    return $dapfforwc_advance_settings[$key];
}

function dapfforwc_render_advanced_pagination_switch($key, $is_checked)
{
    ?>
    <label class="switch <?php echo esc_attr($key); ?>">
        <input type="checkbox" id="<?php echo esc_attr($key); ?>" name="dapfforwc_advance_options[<?php echo esc_attr($key); ?>]" <?php checked($is_checked); ?>>
        <span class="slider round"></span>
        <span class="switch-on">On</span>
        <span class="switch-off">Off</span>
    </label>
    <?php
}

function dapfforwc_render_advanced_pagination_popup_assets()
{
    static $assets_rendered = false;

    if ($assets_rendered) {
        return;
    }

    $assets_rendered = true;
    ob_start();
    ?>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-dapfforwc-advanced-pagination-root="true"]').forEach(function (root) {
                const toggle = root.querySelector('input[name="dapfforwc_advance_options[advanced_pagination_enabled]"]');
                const modeField = root.querySelector('select[name="dapfforwc_advance_options[advanced_pagination_mode]"]');

                if (!toggle || !modeField) {
                    return;
                }

                const updateState = function () {
                    const isEnabled = !!toggle.checked;
                    const selectedMode = modeField.value || 'number';

                    root.querySelectorAll('[data-dapfforwc-pagination-managed="true"]').forEach(function (section) {
                        const modeList = (section.getAttribute('data-dapfforwc-pagination-modes') || '')
                            .split(',')
                            .map(function (value) {
                                return value.trim();
                            })
                            .filter(Boolean);
                        const isVisible = isEnabled && (!modeList.length || modeList.indexOf(selectedMode) !== -1);

                        section.hidden = !isVisible;
                        section.querySelectorAll('input, select, textarea').forEach(function (field) {
                            field.disabled = !isVisible;
                        });
                    });
                };

                toggle.addEventListener('change', updateState);
                modeField.addEventListener('change', updateState);
                updateState();
            });
        });
    <?php
    dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script');
}

function dapfforwc_render_advanced_pagination_settings_popup()
{
    $enabled = dapfforwc_get_advanced_pagination_setting('advanced_pagination_enabled') === 'on';
    $mode = sanitize_key((string) dapfforwc_get_advanced_pagination_setting('advanced_pagination_mode', 'number'));
    $allowed_modes = [
        'number' => __('Number', 'dynamic-ajax-product-filters-for-woocommerce'),
        'number_prev_next' => __('Number + Prev/Next', 'dynamic-ajax-product-filters-for-woocommerce'),
        'load_more' => __('Load More', 'dynamic-ajax-product-filters-for-woocommerce'),
        'infinite_scroll' => __('Infinite Scroll', 'dynamic-ajax-product-filters-for-woocommerce'),
    ];

    if (!isset($allowed_modes[$mode])) {
        $mode = 'number';
    }

    $prev_selector = (string) dapfforwc_get_advanced_pagination_setting('advanced_pagination_prev_selector', '');
    $next_selector = (string) dapfforwc_get_advanced_pagination_setting('advanced_pagination_next_selector', '');
    $load_more_selector = (string) dapfforwc_get_advanced_pagination_setting('advanced_pagination_load_more_selector', '');
    $infinite_scroll_selector = (string) dapfforwc_get_advanced_pagination_setting('advanced_pagination_infinite_scroll_selector', '');

    $show_mode_field = $enabled;
    $show_prev_next_fields = $enabled && $mode === 'number_prev_next';
    $show_load_more_field = $enabled && $mode === 'load_more';
    $show_infinite_scroll_field = $enabled && $mode === 'infinite_scroll';

    dapfforwc_render_advanced_pagination_popup_assets();
    ?>
    <div class="dapfforwc-popup-setting-grid" data-dapfforwc-advanced-pagination-root="true">
        <p class="dapfforwc-popup-setting-note">
            <?php esc_html_e('Keep the Pagination Selector above pointed at the container that should be refreshed after AJAX. Enable advanced pagination only when you need to force a specific pagination behavior.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
        </p>

        <div class="dapfforwc-popup-setting">
            <div class="dapfforwc-popup-setting-heading">
                <label class="dapfforwc-popup-setting-label" for="advanced_pagination_enabled"><?php esc_html_e('Enable Advanced Pagination', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <?php dapfforwc_render_advanced_pagination_switch('advanced_pagination_enabled', $enabled); ?>
            </div>
            <p class="description"><?php esc_html_e('When enabled, the selected mode and selectors will take priority over the plugin\'s automatic pagination detection.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>

        <div class="dapfforwc-popup-setting" data-dapfforwc-pagination-managed="true" <?php echo $show_mode_field ? '' : 'hidden'; ?>>
            <label class="dapfforwc-popup-setting-label" for="advanced_pagination_mode"><?php esc_html_e('Pagination Mode', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
            <select id="advanced_pagination_mode" name="dapfforwc_advance_options[advanced_pagination_mode]" <?php disabled(!$show_mode_field); ?>>
                <?php foreach ($allowed_modes as $option_value => $option_label) : ?>
                    <option value="<?php echo esc_attr($option_value); ?>" <?php selected($mode, $option_value); ?>><?php echo esc_html($option_label); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Choose the only pagination behavior this filter should use to avoid conflicts with theme or builder markup.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>

        <div class="dapfforwc-popup-setting" data-dapfforwc-pagination-managed="true" data-dapfforwc-pagination-modes="number_prev_next" <?php echo $show_prev_next_fields ? '' : 'hidden'; ?>>
            <label class="dapfforwc-popup-setting-label" for="advanced_pagination_prev_selector"><?php esc_html_e('Previous Selector', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
            <input type="text" id="advanced_pagination_prev_selector" name="dapfforwc_advance_options[advanced_pagination_prev_selector]" value="<?php echo esc_attr($prev_selector); ?>" placeholder=".page-numbers .prev, .pagination .prev" <?php disabled(!$show_prev_next_fields); ?>>
            <p class="description"><?php esc_html_e('Optional. Use this when the Previous control has custom markup or sits outside the main pagination wrapper.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>

        <div class="dapfforwc-popup-setting" data-dapfforwc-pagination-managed="true" data-dapfforwc-pagination-modes="number_prev_next" <?php echo $show_prev_next_fields ? '' : 'hidden'; ?>>
            <label class="dapfforwc-popup-setting-label" for="advanced_pagination_next_selector"><?php esc_html_e('Next Selector', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
            <input type="text" id="advanced_pagination_next_selector" name="dapfforwc_advance_options[advanced_pagination_next_selector]" value="<?php echo esc_attr($next_selector); ?>" placeholder=".page-numbers .next, .pagination .next" <?php disabled(!$show_prev_next_fields); ?>>
            <p class="description"><?php esc_html_e('Optional. Use this when the Next control has custom markup or sits outside the main pagination wrapper.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>

        <div class="dapfforwc-popup-setting" data-dapfforwc-pagination-managed="true" data-dapfforwc-pagination-modes="load_more" <?php echo $show_load_more_field ? '' : 'hidden'; ?>>
            <label class="dapfforwc-popup-setting-label" for="advanced_pagination_load_more_selector"><?php esc_html_e('Load More Trigger Selector', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
            <input type="text" id="advanced_pagination_load_more_selector" name="dapfforwc_advance_options[advanced_pagination_load_more_selector]" value="<?php echo esc_attr($load_more_selector); ?>" placeholder=".load-more, .woocommerce-load-more" <?php disabled(!$show_load_more_field); ?>>
            <p class="description"><?php esc_html_e('Optional. Use this when the Load More button is custom or outside the main pagination wrapper.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>

        <div class="dapfforwc-popup-setting" data-dapfforwc-pagination-managed="true" data-dapfforwc-pagination-modes="infinite_scroll" <?php echo $show_infinite_scroll_field ? '' : 'hidden'; ?>>
            <label class="dapfforwc-popup-setting-label" for="advanced_pagination_infinite_scroll_selector"><?php esc_html_e('Infinite Scroll Trigger Selector', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
            <input type="text" id="advanced_pagination_infinite_scroll_selector" name="dapfforwc_advance_options[advanced_pagination_infinite_scroll_selector]" value="<?php echo esc_attr($infinite_scroll_selector); ?>" placeholder=".infinite-scroll-trigger, .yith-wcan-infinite-scroll" <?php disabled(!$show_infinite_scroll_field); ?>>
            <p class="description"><?php esc_html_e('Optional. Use this when infinite scrolling depends on a custom trigger or sentinel element.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>
    </div>
    <?php
}

// Render the "Product Shortcode Selector" field
function dapfforwc_product_shortcode_callback()
{
    global $dapfforwc_advance_settings;
    $product_shortcode = isset($dapfforwc_advance_settings['product_shortcode']) ? esc_attr($dapfforwc_advance_settings['product_shortcode']) : 'products';
?>
    <input type="text" name="dapfforwc_advance_options[product_shortcode]" value="<?php echo esc_attr($product_shortcode); ?>" placeholder="products">
    <p class="description">
        <?php esc_html_e('Enter the selector for the products shortcode. Default is products', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
    </p>
<?php
}

function dapfforwc_text_manage_render()
{
    global $dapfforwc_advance_settings;
    $no_products_text = isset($dapfforwc_advance_settings['no_products_text']) && $dapfforwc_advance_settings['no_products_text'] !== '' ? $dapfforwc_advance_settings['no_products_text'] : 'No products were found matching your selection.';
    $select2_placeholder = isset($dapfforwc_advance_settings['select2_placeholder']) && $dapfforwc_advance_settings['select2_placeholder'] !== '' ? $dapfforwc_advance_settings['select2_placeholder'] : 'Select Options';
    ?>
    <div style="max-width: 500px;">
        <label for="dapfforwcpro-no-products-text" style="font-weight: 600; display:block; margin-bottom:4px;"><?php esc_html_e('No products message', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
        <input type="text" id="dapfforwcpro-no-products-text" name="dapfforwc_advance_options[no_products_text]" value="<?php echo esc_attr($no_products_text); ?>" class="regular-text" placeholder="No products were found matching your selection.">
        <p class="description"><?php esc_html_e('Shown when a filter results in zero products.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>

        <label for="dapfforwcpro-select2-placeholder" style="font-weight: 600; display:block; margin:14px 0 4px;"><?php esc_html_e('Select2 dropdown placeholder', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
        <input type="text" id="dapfforwcpro-select2-placeholder" name="dapfforwc_advance_options[select2_placeholder]" value="<?php echo esc_attr($select2_placeholder); ?>" class="regular-text" placeholder="Select Options">
        <p class="description"><?php esc_html_e('Default placeholder text for Select2 dropdowns.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
    </div>
    <?php
}

function dapfforwc_remove_outofStock_render()
{
    dapfforwc_render_advance_checkbox('remove_outofStock', esc_html__('Enable this option to remove out-of-stock products from the filter results.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_product_exclusion_rules_render()
{
?>
        <div class="dapfforwcpro-product-exclusion-settings dapfforwcpro-product-exclusion-settings-locked pro-only">
            <?php ob_start(); ?>
                .dapfforwcpro-product-exclusion-settings {
                    max-width: 920px;
                }

                .dapfforwcpro-product-exclusion-trigger {
                    display: inline-flex !important;
                    align-items: center;
                    gap: 8px;
                    justify-content: flex-start;
                    min-height: 36px !important;
                    width: auto;
                    height: auto;
                    padding: 4px 12px !important;
                    border-radius: 4px !important;
                }

                .dapfforwcpro-product-exclusion-trigger[disabled] {
                    cursor: not-allowed;
                    opacity: 1;
                }

                .dapfforwcpro-product-exclusion-trigger .dashicons {
                    width: 16px;
                    height: 16px;
                    font-size: 16px;
                }

                .dapfforwcpro-product-exclusion-count,
                .dapfforwcpro-product-exclusion-pro-badge {
                    border-radius: 999px;
                    display: inline-flex;
                    font-size: 12px;
                    font-weight: 600;
                    justify-content: center;
                    line-height: 1;
                    min-width: 22px;
                    padding: 4px 8px;
                }

                .dapfforwcpro-product-exclusion-count {
                    background: #2271b1;
                    color: #fff;
                }

                .dapfforwcpro-product-exclusion-pro-badge {
                    text-transform: uppercase;
                }

                .dapfforwcpro-product-exclusion-help {
                    margin: 8px 0 0;
                    max-width: 760px;
                }
            <?php dapfforwc_add_inline_style(ob_get_clean(), 'dapfforwc-admin-style'); ?>
            <button type="button" class="button button-secondary dapfforwcpro-product-exclusion-trigger" disabled aria-disabled="true">
                <span class="dashicons dashicons-filter" aria-hidden="true"></span>
                <span><?php esc_html_e('Manage exclusions', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                <span class="dapfforwcpro-product-exclusion-count" data-product-exclusion-count>0</span>
                <span class="dapfforwcpro-product-exclusion-pro-badge"><?php esc_html_e('Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
            </button>
            <p class="description dapfforwcpro-product-exclusion-help">
                <?php esc_html_e('Activate a premium license to exclude products from filter results.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
            </p>
        </div>
<?php
}
function dapfforwc_wait_cursor_on_filtering_render()
{
    dapfforwc_render_advance_checkbox('wait_cursor_on_filtering', esc_html__('Show the browser wait cursor while filters are loading new products.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_use_overlay_render()
{
    dapfforwc_render_advance_checkbox('use_overlay', esc_html__('Display a page overlay during AJAX filtering requests.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_smart_auto_scroll_render()
{
    dapfforwc_render_advance_checkbox('smart_auto_scroll', esc_html__('Automatically scroll to the product grid after AJAX updates and enable scroll-based pagination helpers.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_pagination_via_ajax_render()
{
    dapfforwc_render_advance_checkbox('pagination_via_ajax', esc_html__('Load pagination links through AJAX instead of a full page reload.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_sorting_via_ajax_render()
{
    dapfforwc_render_advance_checkbox('sorting_via_ajax', esc_html__('Apply WooCommerce product sorting with AJAX instead of a full page reload.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_browser_history_step_navigation_render()
{
    dapfforwc_render_advance_checkbox('browser_history_step_navigation', esc_html__('Enable one-step browser back/forward navigation for URL-based filters by reloading the page for each history step.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_allow_data_share_render()
{
    dapfforwc_render_advance_checkbox(
        'allow_data_share',
        esc_html__('We collect non-sensitive technical details from your website, like the PHP version and features usage, to help us troubleshoot issues faster, make informed development decisions, and build features that truly benefit you.', 'dynamic-ajax-product-filters-for-woocommerce') .
            ' <a href="' . esc_url('https://plugincy.com/usage-tracking/') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn more...', 'dynamic-ajax-product-filters-for-woocommerce') . '</a>'
    );
}
function dapfforwc_side_bar_top_render()
{
    dapfforwc_render_advance_checkbox('sidebar_on_top', esc_html__('For mobile move the sidebar to top position.', 'dynamic-ajax-product-filters-for-woocommerce'));
}
function dapfforwc_mobile_breakpoint_render()
{
    global $dapfforwc_advance_settings;
    $mobile_breakpoint = isset($dapfforwc_advance_settings['mobile_breakpoint']) ? absint($dapfforwc_advance_settings['mobile_breakpoint']) : 768;
    ?>
    <input type="number" name="dapfforwc_advance_options[mobile_breakpoint]" value="<?php echo esc_attr($mobile_breakpoint); ?>" min="320" step="1" placeholder="768">
    <p class="description">
        <?php esc_html_e('Set the maximum viewport width (in pixels) where mobile-specific filter behavior should apply.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
    </p>
    <?php
}
function dapfforwc_default_value_selected_render()
{
    dapfforwc_render_advance_checkbox('default_value_selected', esc_html__('Make default value from shortcode & pages selected/checked', 'dynamic-ajax-product-filters-for-woocommerce'));
}


function dapfforwc_render_advance_checkbox($key, $message = null)
{
    global $dapfforwc_allowed_tags;
    global $dapfforwc_advance_settings;
?>
    <label class="switch <?php echo esc_attr($key); ?> <?php echo $key === 'wait_cursor_on_filtering' || $key === 'use_overlay' || $key === 'smart_auto_scroll' || $key === 'pagination_via_ajax' || $key === 'sorting_via_ajax' || $key === 'browser_history_step_navigation' ? 'pro-only' : ''; ?>">
        <input <?php echo $key === 'wait_cursor_on_filtering' || $key === 'use_overlay' || $key === 'smart_auto_scroll' || $key === 'pagination_via_ajax' || $key === 'sorting_via_ajax' || $key === 'browser_history_step_navigation' ? 'disabled' : ''; ?> type='checkbox' name='dapfforwc_advance_options[<?php echo esc_attr($key); ?>]' <?php checked(isset($dapfforwc_advance_settings[$key]) && $dapfforwc_advance_settings[$key] === "on"); ?>>
        <span class="slider round"></span>
        <span class="switch-on">On</span>
        <span class="switch-off">Off</span>
    </label>
    <?php
    if (isset($message) && !empty($message)) {
        echo "<p class='description' style='max-width: 800px;'>" . wp_kses($message, $dapfforwc_allowed_tags) . "</p>";
    }
}

function dapfforwc_exclude_attributes_render()
{
    global $dapfforwc_advance_settings;
    $exclude_attributes = isset($dapfforwc_advance_settings['exclude_attributes']) ? explode(',', $dapfforwc_advance_settings['exclude_attributes']) : []; // Convert string to array
    $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
    $all_attributes = isset($all_data['attributes']) ? $all_data['attributes'] : [];
    $dapfforwc_attributes = [];
    
    foreach ($all_attributes as $attribute) {
        $dapfforwc_attributes[] = (object) [
            'attribute_name' => $attribute['attribute_name'],
            'attribute_label' => $attribute['attribute_label'],
        ];
    }
    ?>
    <input type="hidden" name="dapfforwc_advance_options[exclude_attributes][]" value="">
    <select class="plugincy_select2" id="exclude-attributes" name="dapfforwc_advance_options[exclude_attributes][]" multiple style="scrollbar-width: thin;min-width: 141px;" data-placeholder="<?php esc_html_e('Select Attributes to Exclude', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
        <?php foreach ($dapfforwc_attributes as $option) : ?>
            <option value="<?php echo esc_attr($option->attribute_name); ?>" 
                <?php echo in_array($option->attribute_name, $exclude_attributes) ? 'selected' : ''; ?>>
                <?php echo esc_html($option->attribute_label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}
function dapfforwc_exclude_custom_fields_render()
{
    global $dapfforwc_advance_settings;
    $exclude_custom_fields = isset($dapfforwc_advance_settings['exclude_custom_fields']) ? explode(',', $dapfforwc_advance_settings['exclude_custom_fields']) : []; // Convert string to array
    $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
    $custom_fields = isset($all_data['custom_fields']) ? $all_data['custom_fields'] : [];
    $dapfforwc_attributes = [];
    
    foreach ($custom_fields as $attribute) {
        $dapfforwc_attributes[] = (object) [
            'attribute_name' => $attribute['name'],
            'attribute_label' => $attribute['label'],
        ];
    }
    ?>
    <div class="pro-only">
        <select class="plugincy_select2" disabled id="exclude_custom_fields" name="dapfforwc_advance_options[exclude_custom_fields][]" multiple style="scrollbar-width: thin;min-width: 141px;" data-placeholder="<?php esc_html_e('Select Custom Fields to Exclude', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
            <?php foreach ($dapfforwc_attributes as $option) : ?>
                <option value="<?php echo esc_attr($option->attribute_name); ?>" 
                    <?php echo in_array($option->attribute_name, $exclude_custom_fields) ? 'selected' : ''; ?>>
                    <?php echo esc_html($option->attribute_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}
