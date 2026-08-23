<?php
if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_render_checkbox($key, $settings = "dapfforwc_options")
{
    global $$settings;
    $is_pro_only = $key === "use_anchor" || $key === "show_custom_fields" || $key === "show_custom_taxonomies" || $key === "show_featured";
?>
    <label class="switch <?php echo esc_attr($key); echo $is_pro_only ? ' pro-only' : ''; ?>">
        <input <?php echo $is_pro_only ? ' disabled' : ''; ?> type='checkbox' name='<?php echo $is_pro_only ? '_pro' : esc_attr($settings); ?>[<?php echo esc_attr($key); ?>]' <?php $is_pro_only ? '' : checked(isset($$settings[$key]) && $$settings[$key] === "on"); ?>>
        <span class="slider round"></span>
        <span class="switch-on">On</span>
        <span class="switch-off">Off</span>
    </label>
    <?php
    if ($key === "use_filters_word_in_permalinks") {
        echo "<p>if you want to use permalinks filter in your front page & archive page turn it on.</p>";
    } 
    elseif ($key === "use_attribute_type_in_permalinks") {
        echo "<p>".esc_html__('Enable this setting if you want permalinks Like', 'dynamic-ajax-product-filters-for-woocommerce') ."<code>?filters=1&product_cat=compact-design&pa-size=large&product-tag=tag1</code></p>";
    } 
    elseif ($key === "show_loader") {
        echo "<p><a href='#' id='customize_loader'>".esc_html__('Customize', 'dynamic-ajax-product-filters-for-woocommerce') ."</a> ".esc_html__('loading effect', 'dynamic-ajax-product-filters-for-woocommerce') ."</p>";
    }
    elseif ($key === "enable_seo") {
        echo "<p>".esc_html__('Add "robots" meta tag in head tag of HTML page if filters have been activated.', 'dynamic-ajax-product-filters-for-woocommerce') ."</p>";

        ?>
        <p>
            <h4 for="seo_meta_tag" style="margin: 20px 0 10px;"><?php esc_html_e('Choose Meta Tag:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h4>
            <select name="dapfforwc_seo_permalinks_options[seo_meta_tag]" id="seo_meta_tag">
                <?php
                $meta_tag_options = [
                    'noindex,nofollow' => 'noindex, nofollow',
                    'noindex,follow' => 'noindex, follow',
                    'index,nofollow' => 'index, nofollow',
                    'index,follow' => 'index, follow',
                ];
                foreach ($meta_tag_options as $value => $label) {
                    echo '<option value="' . esc_attr($value) . '" ' . selected(isset($$settings['seo_meta_tag']) ? $$settings['seo_meta_tag'] : '', $value, false) . '>' . esc_html($label) . '</option>';
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e('Select the meta tag to be added in the head section of the page.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </p>
        <?php
    }
}


function dapfforwc_render_form_manage_popup_assets()
{
    static $assets_rendered = false;

    if ($assets_rendered) {
        return;
    }

    $assets_rendered = true;
    ob_start();
    ?>
        .dapfforwc-form-manage-setting {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .dapfforwc-form-manage-setting label {
            margin: 0;
        }

        .dapfforwc-form-manage-settings-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #334155;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            cursor: pointer;
        }

        .dapfforwc-form-manage-settings-trigger:hover,
        .dapfforwc-form-manage-settings-trigger:focus {
            background: #eff6ff;
            border-color: #60a5fa;
            color: #1d4ed8;
            outline: none;
        }

        .dapfforwc-form-manage-settings-trigger .dashicons {
            width: 18px;
            height: 18px;
            font-size: 18px;
        }

        .dapfforwc-form-manage-popup {
            position: fixed;
            inset: 0;
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .dapfforwc-form-manage-popup[hidden] {
            display: none;
        }

        .dapfforwc-form-manage-popup-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
        }

        .dapfforwc-form-manage-popup-panel {
            position: relative;
            width: min(560px, 100%);
            max-height: calc(100vh - 48px);
            overflow: visible;
            padding: 24px;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 24px 80px rgba(15, 23, 42, 0.25);
        }

        .dapfforwc-form-manage-popup-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .dapfforwc-form-manage-popup-title {
            margin: 0;
            font-size: 20px;
            line-height: 1.3;
            color: #0f172a;
        }

        .dapfforwc-form-manage-popup-description {
            margin: 8px 0 0;
            color: #475569;
        }

        .dapfforwc-form-manage-popup-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            cursor: pointer;
        }

        .dapfforwc-form-manage-popup-close:hover,
        .dapfforwc-form-manage-popup-close:focus {
            background: #e2e8f0;
            outline: none;
        }

        .dapfforwc-form-manage-popup-body .plugincy_select2 {
            width: 100% !important;
        }

        .dapfforwc-form-manage-popup-body .select2-container {
            width: 100% !important;
        }

        .dapfforwc-form-manage-popup .select2-dropdown {
            z-index: 100001;
        }

        .dapfforwc-form-manage-popup-body .description {
            margin-top: 10px;
        }

        .dapfforwc-brand-settings-promo {
            display: grid;
            gap: 16px;
        }

        .dapfforwc-brand-settings-promo-intro {
            margin: 0;
            padding: 14px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            background: #eff6ff;
            color: #1e3a8a;
            line-height: 1.6;
        }

        .dapfforwc-brand-settings-promo-preview {
            display: grid;
            gap: 12px;
        }

        .dapfforwc-brand-settings-promo-card {
            padding: 16px 18px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            background: #fff;
        }

        .dapfforwc-brand-settings-promo-card.is-disabled {
            background: #f8fafc;
        }

        .dapfforwc-brand-settings-promo-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .dapfforwc-brand-settings-promo-card-title {
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
        }

        .dapfforwc-brand-settings-promo-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .dapfforwc-brand-settings-promo-card p,
        .dapfforwc-brand-settings-promo-helper {
            margin: 8px 0 0;
            color: #475569;
            line-height: 1.6;
        }

        .dapfforwc-brand-settings-promo-selection {
            display: grid;
            gap: 10px;
            padding: 16px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
        }

        .dapfforwc-brand-settings-promo-selection-label {
            font-weight: 600;
            color: #0f172a;
        }

        .dapfforwc-brand-settings-promo-field {
            min-height: 64px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #fff;
        }

        .dapfforwc-brand-settings-promo-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 500;
        }

        .dapfforwc-dimension-settings-promo {
            display: grid;
            gap: 18px;
        }

        .dapfforwc-dimension-settings-promo-intro {
            margin: 0;
            padding: 14px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #eff6ff;
            color: #1e3a8a;
            line-height: 1.6;
        }

        .dapfforwc-dimension-settings-promo-list {
            display: grid;
            gap: 12px;
        }

        .dapfforwc-dimension-settings-promo-item {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, opacity 0.2s ease;
        }

        .dapfforwc-dimension-settings-promo-item.is-disabled {
            opacity: 0.8;
        }

        .dapfforwc-dimension-settings-promo-main {
            min-width: 0;
        }

        .dapfforwc-dimension-settings-promo-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            color: #94a3b8;
        }

        .dapfforwc-dimension-settings-promo-title {
            display: block;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
        }

        .dapfforwc-dimension-settings-promo-text {
            display: block;
            margin-top: 3px;
            color: #475569;
            line-height: 1.5;
        }

        .dapfforwc-dimension-settings-promo-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dapfforwc-dimension-settings-promo-arrow {
            width: 34px;
            height: 34px;
            min-height: 34px;
            padding: 0;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
            background: #f8fafc;
            color: #94a3b8;
        }

        .dapfforwc-dimension-settings-promo-arrow[disabled] {
            border-color: #dbe3ec;
            background: #f8fafc;
            color: #94a3b8;
        }

        .dapfforwc-dimension-settings-promo-visibility {
            display: flex;
            justify-content: flex-end;
        }

        .dapfforwc-dimension-settings-promo-visibility .switch {
            margin-right: 0;
            opacity: 0.86;
        }

        .dapfforwc-dimension-settings-promo-note {
            margin: 0;
            padding: 16px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            color: #475569;
            line-height: 1.6;
        }

        @media (max-width: 782px) {
            .dapfforwc-dimension-settings-promo-item {
                grid-template-columns: auto minmax(0, 1fr);
                align-items: start;
            }

            .dapfforwc-dimension-settings-promo-actions,
            .dapfforwc-dimension-settings-promo-visibility {
                grid-column: 2;
            }

            .dapfforwc-dimension-settings-promo-visibility {
                justify-content: flex-start;
            }
        }

        .dapfforwc-popup-setting-grid {
            display: grid;
            gap: 18px;
        }

        .dapfforwc-popup-setting {
            display: grid;
            gap: 8px;
        }

        .dapfforwc-popup-setting[hidden] {
            display: none !important;
        }

        .dapfforwc-popup-setting-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .dapfforwc-popup-setting-label {
            margin: 0;
            font-weight: 600;
            color: #0f172a;
        }

        .dapfforwc-popup-setting-note {
            margin: 0;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            color: #475569;
        }

        .dapfforwc-popup-setting-field[hidden] {
            display: none !important;
        }

        .dapfforwc-popup-setting input[type="text"],
        .dapfforwc-popup-setting input[type="number"],
        .dapfforwc-popup-setting select {
            width: 100%;
            max-width: 100%;
        }

        .dapfforwc-popup-setting .switch {
            margin-right: 0;
        }

        .dapfforwc-form-manage-popup-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        body.dapfforwc-popup-open {
            overflow: hidden;
        }

        @media (max-width: 782px) {
            .dapfforwc-form-manage-popup {
                padding: 16px;
            }

            .dapfforwc-form-manage-popup-panel {
                padding: 20px;
            }
        }
    <?php
    dapfforwc_add_inline_style(ob_get_clean(), 'dapfforwc-admin-style');

    ob_start();
    ?>
        document.addEventListener('DOMContentLoaded', function () {
            const popupClass = '.dapfforwc-form-manage-popup';
            let activePopup = null;

            function initPopupSelects(popup) {
                if (typeof jQuery === 'undefined' || !jQuery.fn || typeof jQuery.fn.select2 !== 'function') {
                    return;
                }

                const $popup = jQuery(popup);

                jQuery(popup).find('.plugincy_select2').each(function () {
                    const $select = jQuery(this);

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        placeholder: function () {
                            return jQuery(this).data('placeholder');
                        },
                        width: '100%',
                        allowClear: true,
                        dropdownParent: $popup,
                    });
                });
            }

            function closePopup(popup) {
                if (!popup) {
                    return;
                }

                popup.hidden = true;
                document.body.classList.remove('dapfforwc-popup-open');

                if (popup.__triggerElement && typeof popup.__triggerElement.focus === 'function') {
                    popup.__triggerElement.focus();
                }

                if (activePopup === popup) {
                    activePopup = null;
                }
            }

            document.querySelectorAll('.dapfforwc-form-manage-settings-trigger').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    const popup = document.getElementById(trigger.getAttribute('data-popup-target'));

                    if (!popup) {
                        return;
                    }

                    popup.__triggerElement = trigger;
                    popup.hidden = false;
                    document.body.classList.add('dapfforwc-popup-open');
                    activePopup = popup;
                    initPopupSelects(popup);
                });
            });

            document.addEventListener('click', function (event) {
                const closeTarget = event.target.closest('[data-popup-close="true"]');

                if (!closeTarget) {
                    return;
                }

                closePopup(closeTarget.closest(popupClass));
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && activePopup) {
                    closePopup(activePopup);
                }
            });
        });
    <?php
    dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script');
}

function dapfforwc_render_form_manage_popup($popup_id, $button_label, $title, $description, $render_callback, $footer_callback = null)
{
    dapfforwc_render_form_manage_popup_assets();
    ?>
    <button
        type="button"
        class="dapfforwc-form-manage-settings-trigger dapfforwcpro-form-manage-settings-trigger"
        data-popup-target="<?php echo esc_attr($popup_id); ?>"
        aria-haspopup="dialog"
        aria-controls="<?php echo esc_attr($popup_id); ?>"
    >
        <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
        <span class="screen-reader-text"><?php echo esc_html($button_label); ?></span>
    </button>
    <div id="<?php echo esc_attr($popup_id); ?>" class="dapfforwc-form-manage-popup" hidden>
        <div class="dapfforwc-form-manage-popup-backdrop" data-popup-close="true"></div>
        <div class="dapfforwc-form-manage-popup-panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($popup_id); ?>-title">
            <div class="dapfforwc-form-manage-popup-header">
                <div>
                    <h2 id="<?php echo esc_attr($popup_id); ?>-title" class="dapfforwc-form-manage-popup-title"><?php echo esc_html($title); ?></h2>
                    <p class="dapfforwc-form-manage-popup-description"><?php echo esc_html($description); ?></p>
                </div>
                <button type="button" class="dapfforwc-form-manage-popup-close" data-popup-close="true" aria-label="<?php esc_attr_e('Close settings popup', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
            </div>
            <div class="dapfforwc-form-manage-popup-body">
                <?php
                if (is_callable($render_callback)) {
                    call_user_func($render_callback);
                }
                ?>
            </div>
            <div class="dapfforwc-form-manage-popup-footer">
                <?php
                if (is_callable($footer_callback)) {
                    call_user_func($footer_callback, $popup_id);
                } else {
                    ?>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Changes', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

function dapfforwc_render_hidden_advance_field($field_name, $value)
{
    if (is_array($value)) {
        foreach ($value as $key => $sub_value) {
            $next_field_name = is_int($key)
                ? $field_name . '[]'
                : $field_name . '[' . sanitize_key((string) $key) . ']';

            dapfforwc_render_hidden_advance_field($next_field_name, $sub_value);
        }

        return;
    }

    printf(
        '<input type="hidden" name="%1$s" value="%2$s">',
        esc_attr($field_name),
        esc_attr((string) $value)
    );
}

function dapfforwc_render_advance_settings_preserved_hidden_fields()
{
    global $dapfforwc_advance_settings;

    if (!is_array($dapfforwc_advance_settings) || !array_key_exists('exclude_attributes', $dapfforwc_advance_settings)) {
        return;
    }

    dapfforwc_render_hidden_advance_field('dapfforwc_advance_options[exclude_attributes]', $dapfforwc_advance_settings['exclude_attributes']);
}

function dapfforwc_render_form_manage_pro_notice($message)
{
    ?>
    <p class="description" style="margin-bottom: 12px;"><?php echo esc_html($message); ?></p>
    <?php
}

function dapfforwc_form_manage_attribute_settings_render()
{
    dapfforwc_exclude_attributes_render();
}

function dapfforwc_form_manage_custom_fields_settings_render()
{
    dapfforwc_render_form_manage_pro_notice(__('This control is available in Pro.', 'dynamic-ajax-product-filters-for-woocommerce'));
    dapfforwc_exclude_custom_fields_render();
}

function dapfforwc_get_product_custom_taxonomy_options()
{
    $taxonomies = get_object_taxonomies('product', 'objects');
    $excluded_taxonomies = [
        'product_cat',
        'product_tag',
        'product_brand',
        'product_shipping_class',
    ];

    $custom_taxonomies = [];

    foreach ($taxonomies as $taxonomy) {
        if (strpos($taxonomy->name, 'pa_') === 0) {
            continue;
        }

        if (in_array($taxonomy->name, $excluded_taxonomies, true)) {
            continue;
        }

        $custom_taxonomies[] = (object) [
            'name' => $taxonomy->name,
            'label' => $taxonomy->label ? $taxonomy->label : $taxonomy->name,
        ];
    }

    return $custom_taxonomies;
}

function dapfforwc_render_custom_taxonomy_popup_fallback()
{
    $custom_taxonomies = dapfforwc_get_product_custom_taxonomy_options();

    dapfforwc_render_form_manage_pro_notice(__('This control is available in Pro.', 'dynamic-ajax-product-filters-for-woocommerce'));

    if (empty($custom_taxonomies)) {
        ?>
        <p class="description"><?php esc_html_e('No custom product taxonomies were found on this site.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        <?php
        return;
    }

    ?>
    <select class="plugincy_select2" disabled multiple style="scrollbar-width: thin; min-width: 141px;" data-placeholder="<?php esc_attr_e('Select Custom Taxonomies', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
        <?php foreach ($custom_taxonomies as $taxonomy) : ?>
            <option value="<?php echo esc_attr($taxonomy->name); ?>"><?php echo esc_html($taxonomy->label); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

function dapfforwc_form_manage_custom_taxonomy_settings_render()
{
    if (function_exists('dapfforwc_selected_custom_taxonomies_render')) {
        dapfforwc_selected_custom_taxonomies_render();
        return;
    }

    dapfforwc_render_custom_taxonomy_popup_fallback();
}

function dapfforwc_form_manage_brand_settings_render()
{
    ?>
    <div class="dapfforwc-brand-settings-promo">
        <p class="dapfforwc-brand-settings-promo-intro">
            <?php esc_html_e('Auto-detect and manual brand taxonomy mapping are available in the Pro version. Upgrade when you want to control which product taxonomies behave as brand sources for filters and product queries.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
        </p>

        <div class="dapfforwc-brand-settings-promo-preview">
            <div class="dapfforwc-brand-settings-promo-card is-disabled" aria-hidden="true">
                <div class="dapfforwc-brand-settings-promo-card-header">
                    <span class="dapfforwc-brand-settings-promo-card-title"><?php esc_html_e('Auto-detect available brand taxonomies', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                    <span class="dapfforwc-brand-settings-promo-badge"><?php esc_html_e('Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                </div>
                <p><?php esc_html_e('Use detected WooCommerce and third-party brand taxonomies automatically.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
            </div>

            <div class="dapfforwc-brand-settings-promo-card is-disabled" aria-hidden="true">
                <div class="dapfforwc-brand-settings-promo-card-header">
                    <span class="dapfforwc-brand-settings-promo-card-title"><?php esc_html_e('Manually choose brand taxonomies', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                    <span class="dapfforwc-brand-settings-promo-badge"><?php esc_html_e('Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                </div>
                <p><?php esc_html_e('Map a custom product taxonomy to the brand filter when your store keeps brand data outside the default brand taxonomies.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
            </div>
        </div>

        <div class="dapfforwc-brand-settings-promo-selection">
            <span class="dapfforwc-brand-settings-promo-selection-label"><?php esc_html_e('Selected Brand Taxonomies', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
            <div class="dapfforwc-brand-settings-promo-field" aria-hidden="true">
                <span class="dapfforwc-brand-settings-promo-pill"><?php esc_html_e('Custom brand mapping preview', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
            </div>
            <p class="dapfforwc-brand-settings-promo-helper"><?php esc_html_e('Want to filter by custom brand taxonomies? Get the Pro version to unlock brand filter settings.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
        </div>
    </div>
    <?php
}

function dapfforwc_form_manage_brand_settings_footer($popup_id = '')
{
    $upgrade_url = 'https://plugincy.com/dynamic-ajax-product-filters-for-woocommerce/';
    ?>
    <button type="button" class="button" data-popup-close="true"><?php esc_html_e('Maybe Later', 'dynamic-ajax-product-filters-for-woocommerce'); ?></button>
    <a href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e('Get Pro', 'dynamic-ajax-product-filters-for-woocommerce'); ?></a>
    <?php
}

function dapfforwc_form_manage_dimension_settings_render()
{
    $preview_dimensions = array(
        array(
            'label' => __('Length', 'dynamic-ajax-product-filters-for-woocommerce'),
            'enabled' => true,
        ),
        array(
            'label' => __('Width', 'dynamic-ajax-product-filters-for-woocommerce'),
            'enabled' => true,
        ),
        array(
            'label' => __('Height', 'dynamic-ajax-product-filters-for-woocommerce'),
            'enabled' => false,
        ),
        array(
            'label' => __('Weight', 'dynamic-ajax-product-filters-for-woocommerce'),
            'enabled' => true,
        ),
    );
    ?>
    <div class="dapfforwc-dimension-settings-promo">
        <p class="dapfforwc-dimension-settings-promo-intro">
            <?php esc_html_e('Choose which dimensions should be visible and arrange their display order in the Pro version. The free version keeps the default dimension output.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
        </p>

        <div class="dapfforwc-dimension-settings-promo-list" aria-hidden="true">
            <?php foreach ($preview_dimensions as $index => $dimension) : ?>
                <div class="dapfforwc-dimension-settings-promo-item is-disabled">
                    <span class="dashicons dashicons-menu-alt2 dapfforwc-dimension-settings-promo-handle" aria-hidden="true"></span>

                    <div class="dapfforwc-dimension-settings-promo-main">
                        <div>
                            <span class="dapfforwc-dimension-settings-promo-title"><?php echo esc_html($dimension['label']); ?></span>
                            <span class="dapfforwc-dimension-settings-promo-text"><?php esc_html_e('Preview dimension visibility and ordering controls.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                        </div>
                    </div>

                    <div class="dapfforwc-dimension-settings-promo-actions">
                        <button type="button" class="button button-secondary dapfforwc-dimension-settings-promo-arrow" disabled aria-label="<?php esc_attr_e('Move dimension preview up', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">&#8593;</button>
                        <button type="button" class="button button-secondary dapfforwc-dimension-settings-promo-arrow" disabled aria-label="<?php esc_attr_e('Move dimension preview down', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">&#8595;</button>
                    </div>

                    <div class="dapfforwc-dimension-settings-promo-visibility">
                        <label class="switch">
                            <input type="checkbox" disabled <?php checked(!empty($dimension['enabled'])); ?>>
                            <span class="slider round"></span>
                            <span class="switch-on"><?php esc_html_e('On', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                            <span class="switch-off"><?php esc_html_e('Off', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="dapfforwc-dimension-settings-promo-note"><?php esc_html_e('Want to choose which dimensions are visible and reorder them? Get the Pro version to unlock dimension filter settings.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
    </div>
    <?php
}

function dapfforwc_show_categories_render()
{
    dapfforwc_render_checkbox('show_categories');
}
function dapfforwc_show_attributes_render()
{
    ?>
    <div class="dapfforwc-form-manage-setting">
        <?php dapfforwc_render_checkbox('show_attributes'); ?>
        <?php
        dapfforwc_render_form_manage_popup(
            'dapfforwc-attribute-settings-popup',
            __('Open attribute settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Exclude Attributes', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Choose which attributes should stay hidden from the filter form.', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_form_manage_attribute_settings_render'
        );
        ?>
    </div>
    <?php
}
function dapfforwc_show_tags_render()
{
    dapfforwc_render_checkbox('show_tags');
}
function dapfforwc_show_custom_taxonomies_render()
{
    ?>
    <div class="dapfforwc-form-manage-setting">
        <?php dapfforwc_render_checkbox('show_custom_taxonomies'); ?>
        <?php
        dapfforwc_render_form_manage_popup(
            'dapfforwc-custom-taxonomy-settings-popup',
            __('Open custom taxonomy filter settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Custom Taxonomy Filters', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Select which custom product taxonomies should be available in the filter form.', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_form_manage_custom_taxonomy_settings_render'
        );
        ?>
    </div>
    <?php
}
function dapfforwc_show_price_range_render()
{
    dapfforwc_render_checkbox('show_price_range');
}
function dapfforwc_show_rating_render()
{
    dapfforwc_render_checkbox('show_rating');
}
function dapfforwc_show_search_render()
{
    dapfforwc_render_checkbox('show_search');
}
function dapfforwc_show_brand_render()
{
    ?>
    <div class="dapfforwc-form-manage-setting">
        <?php dapfforwc_render_checkbox('show_brand'); ?>
        <?php
        dapfforwc_render_form_manage_popup(
            'dapfforwc-brand-settings-popup',
            __('Open brand filter settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Brand Filter Settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Choose how brand taxonomies are mapped to brand filters in the Pro version.', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_form_manage_brand_settings_render',
            'dapfforwc_form_manage_brand_settings_footer'
        );
        ?>
    </div>
    <?php
}
function dapfforwc_show_author_render()
{
    dapfforwc_render_checkbox('show_author');
}
function dapfforwc_show_status_render()
{
    dapfforwc_render_checkbox('show_status');
}
function dapfforwc_show_onsale_render()
{
    dapfforwc_render_checkbox('show_onsale');
}
function dapfforwc_show_featured_render()
{
    dapfforwc_render_checkbox('show_featured');
}
function dapfforwc_show_dimension_render()
{
    ?>
    <div class="dapfforwc-form-manage-setting">
        <?php dapfforwc_render_checkbox('show_dimension'); ?>
        <?php
        dapfforwc_render_form_manage_popup(
            'dapfforwc-dimension-settings-popup',
            __('Open dimension filter settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Dimension Filter Settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Preview dimension visibility and ordering controls available in the Pro version.', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_form_manage_dimension_settings_render',
            'dapfforwc_form_manage_brand_settings_footer'
        );
        ?>
    </div>
    <?php
}
function dapfforwc_show_sku_render()
{
    dapfforwc_render_checkbox('show_sku');
}
function dapfforwc_show_discount_render()
{
    dapfforwc_render_checkbox('show_discount');
}
function dapfforwc_show_date_filter_render()
{
    dapfforwc_render_checkbox('show_date_filter');
}
function dapfforwc_show_custom_fields_render()
{
    ?>
    <div class="dapfforwc-form-manage-setting">
        <?php dapfforwc_render_checkbox('show_custom_fields'); ?>
        <?php
        dapfforwc_render_form_manage_popup(
            'dapfforwc-custom-fields-settings-popup',
            __('Open custom field settings', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Exclude Custom Fields', 'dynamic-ajax-product-filters-for-woocommerce'),
            __('Choose which custom fields should stay hidden from the filter form.', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_form_manage_custom_fields_settings_render'
        );
        ?>
    </div>
    <?php
}
function dapfforwc_use_filters_word_in_permalinks_render()
{
    dapfforwc_render_checkbox('use_filters_word_in_permalinks', 'dapfforwc_seo_permalinks_options');
}

function dapfforwc_update_filter_options_render()
{
    dapfforwc_render_checkbox('update_filter_options');
}
function dapfforwc_show_loader_render()
{
    dapfforwc_render_checkbox('show_loader');
}
function dapfforwc_use_custom_template_render()
{
    dapfforwc_render_checkbox('use_custom_template');
}




function dapfforwc_custom_template_code_render()
{
    global $dapfforwc_options;
    echo '    
    <div class="custom_template_code" >';
    ?>
    <!-- Placeholder List -->
    <div id="placeholder-list" style="margin-bottom: 10px;">
        <?php
        $placeholders = [
            '{{product_link}}' => 'Product Link',
            '{{product_title}}' => 'Product Title',
            '{{product_image}}' => 'Product Image',
            '{{product_price}}' => 'Product Price',
            '{{product_excerpt}}' => 'Product Excerpt',
            '{{product_category}}' => 'Product Category',
            '{{product_sku}}' => 'Product SKU',
            '{{product_stock}}' => 'Product Stock',
            '{{add_to_cart_url}}' => 'Add to Cart URL',
            '{{product_id}}' => 'Product ID'
        ];
        foreach ($placeholders as $placeholder => $label) {
            echo "<span class='placeholder' onclick=\"insertPlaceholder('" . esc_html($placeholder) . "')\">" . esc_html($placeholder) . "</span>";
        }
        ?>
    </div>
    <textarea style="display:none;" id="custom_template_input" name="dapfforwc_options[custom_template_code]" rows="10" cols="50" class="large-text"><?php if (isset($dapfforwc_options['custom_template_code'])) {
                                                                                                                                                            echo esc_textarea($dapfforwc_options['custom_template_code']);
                                                                                                                                                        } ?></textarea>
    <div id="code-editor"></div>
    <p class="description"><?php esc_html_e('Enter your custom template code here.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
    </div>


<?php
}

function dapfforwc_use_url_filter_render()
{
    global $dapfforwc_options;
?>
    <fieldset>
        <legend><?php esc_html_e('Select URL Filter Type', 'dynamic-ajax-product-filters-for-woocommerce'); ?></legend>
        <?php
        $types = [
            'query_string' => esc_html__('With Query String (e.g., ?filters)', 'dynamic-ajax-product-filters-for-woocommerce'),
            'pro_only' => esc_html__('With Permalinks (e.g., brand/size/color)', 'dynamic-ajax-product-filters-for-woocommerce'),
            'ajax' => esc_html__('With Ajax', 'dynamic-ajax-product-filters-for-woocommerce'),
        ];
        foreach ($types as $value => $label) {
            // Check if the current value is "pro_only"
            $disabled = $value === 'pro_only' ? 'disabled' : '';
            $label_class = $value === 'pro_only' ? 'pro-only' : '';

            echo '<label class="' . esc_attr($label_class) . '">
                    <input type="radio" name="dapfforwc_options[use_url_filter]" value="' . esc_attr($value) . '" ' . checked(isset($dapfforwc_options['use_url_filter'])?$dapfforwc_options['use_url_filter']:"", $value, false) . ' ' . esc_attr($disabled) . '> 
                    ' . esc_html($label) . '
                  </label><br>';
        }
        ?>
    </fieldset>
<?php
}
