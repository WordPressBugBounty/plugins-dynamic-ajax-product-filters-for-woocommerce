<?php
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- This admin template uses local template variables; plugin functions, options, and hooks remain prefixed.
?>

<form method="post" action="options.php" id="dapfforwc-style-options-form">
    <?php
    settings_fields('dapfforwc_style_options_group');
    do_settings_sections('dapfforwc-style');
    global $dapfforwc_advance_settings;

    // Fetch WooCommerce attributes
    $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
    $all_cata = isset($all_data['categories']) ? $all_data['categories'] : [];
    $all_tags = isset($all_data['tags']) ? $all_data['tags'] : [];
    $all_attributes = isset($all_data['attributes']) ? $all_data['attributes'] : [];
    $all_brands = isset($all_data['brands']) ? $all_data['brands'] : [];
    $all_authors = isset($all_data['authors']) ? $all_data['authors'] : [];
    $all_stock_status = isset($all_data['stock_status']) ? $all_data['stock_status'] : [];
    $all_sale_status = isset($all_data['sale_status']) ? $all_data['sale_status'] : [];
    $exclude_attributes = isset($dapfforwc_advance_settings['exclude_attributes']) ? explode(',', $dapfforwc_advance_settings['exclude_attributes']) : [];
    $exclude_custom_fields = isset($dapfforwc_advance_settings['exclude_custom_fields']) ? explode(',', $dapfforwc_advance_settings['exclude_custom_fields']) : [];
    $dapfforwc_attributes = [];
    foreach ($all_attributes as $attribute) {
        if (in_array($attribute['attribute_name'], $exclude_attributes)) {
            continue;
        }
        $dapfforwc_attributes[] = (object) [
            'attribute_name' => $attribute['attribute_name'],
            'attribute_label' => $attribute['attribute_label'],
        ];
    }
    $custom_fields = isset($all_data['custom_fields']) ? $all_data['custom_fields'] : [];
    $all_custom_fields = [];
    foreach ($custom_fields as $custom_field) {
        if (in_array($custom_field['name'], $exclude_custom_fields)) {
            continue;
        }
        $all_custom_fields[] = (object) [
            'attribute_name' => $custom_field['name'],
            'attribute_label' => $custom_field['label'],
        ];
    }

    $dapfforwc_form_styles = function_exists('dapfforwc_get_style_options')
        ? dapfforwc_get_style_options()
        : (get_option('dapfforwc_style_options') ?: []);

    // Define extra options
    $dapfforwc_extra_options = [
        (object) ['attribute_name' => "product-category", 'attribute_label' => esc_html__('Category Options', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'tag', 'attribute_label' => esc_html__('Tag Options', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'price', 'attribute_label' => esc_html__('Price', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'rating', 'attribute_label' => esc_html__('Rating', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'brands', 'attribute_label' => esc_html__('Brands', 'dynamic-ajax-product-filters-for-woocommerce')],

        (object) ['attribute_name' => 'authors', 'attribute_label' => esc_html__('Authors', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'status', 'attribute_label' => esc_html__('Stock Status', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'sale_status', 'attribute_label' => esc_html__('Sale Status', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'dimensions', 'attribute_label' => esc_html__('Dimensions', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'sku', 'attribute_label' => esc_html__('SKU', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'discount', 'attribute_label' => esc_html__('Discount', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'date_filter', 'attribute_label' => esc_html__('Post Date', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'search', 'attribute_label' => esc_html__('Search Product', 'dynamic-ajax-product-filters-for-woocommerce')],
        (object) ['attribute_name' => 'reset_btn', 'attribute_label' => esc_html__('Apply, Reset Button', 'dynamic-ajax-product-filters-for-woocommerce')],
    ];
    $dapfforwc_extra_main_options = [
        (object) ['attribute_name' => "attributes", 'attribute_label' => esc_html__('Attributes', 'dynamic-ajax-product-filters-for-woocommerce')],
        // (object) ['attribute_name' => "custom_fields", 'attribute_label' => esc_html__('Custom Fields', 'dynamic-ajax-product-filters-for-woocommerce')],
    ];



    // Combine attributes and extra options
    $dapfforwc_all_options = array_merge($dapfforwc_attributes, $dapfforwc_extra_options);
    $main_texonomy = array_merge($dapfforwc_extra_options, $dapfforwc_extra_main_options);

    // Get the first attribute for default display
    $dapfforwc_first_attribute = !empty($dapfforwc_all_options) ? $dapfforwc_all_options[0]->attribute_name : '';
    $dapfforwc_option_names = array_map(function ($option) {
        return $option->attribute_name;
    }, $dapfforwc_all_options);
    $dapfforwc_selected_attribute = $dapfforwc_first_attribute;
    $dapfforwc_has_style_attr_nonce = isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dapfforwc_tab_nonce');
    if ($dapfforwc_has_style_attr_nonce && isset($_GET['style_attr'])) {
        $requested_attribute = sanitize_text_field(wp_unslash($_GET['style_attr']));
        if (in_array($requested_attribute, $dapfforwc_option_names, true)) {
            $dapfforwc_selected_attribute = $requested_attribute;
        }
    }

    $dapfforwc_labels = [];
    foreach ($dapfforwc_all_options as $option) {
        $dapfforwc_labels[$option->attribute_name] = $option->attribute_label;
    }

    $dapfforwc_simple_attributes = ['reset_btn'];
    $dapfforwc_menu_order_attributes = array_unique(array_merge(
        ['product-category', 'tag', 'brands'],
        array_map(function ($option) {
            return $option->attribute_name;
        }, $dapfforwc_attributes)
    ));
    $dapfforwc_per_attribute_groups = [
        'show_in_active_filters',
        'widget_title',
        'placeholder',
        'btntext',
        'show_apply_button',
        'applybtntext',
        'apply_behavior',
        'show_reset_button',
        'show_apply_reset_on',
        'max_height',
        'css_class',
        'operator',
        'terms',
        'include_exclude',
        'enable_terms_search',
        'terms_search_texts',
        'terms_search_position',
        'layout',
        'num_columns',
        'enable_tooltip',
        'tooltip_text',
        'enable_auto_suggestion',
        'search_behavior',
        'enable_full_match',
        'additional_text_1',
        'additional_text',
        'additional_text_5',
        'input_label',
    ];

    $dapfforwc_normalize_hex_color = function ($value, $fallback = '#000000') {
        $value = is_string($value) ? trim($value) : '';

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $value)) {
            return '#' . strtolower($value);
        }

        return $fallback;
    };

    $dapfforwc_normalize_terms = function ($terms, $include_brand_image = false, $attribute_name = '') use ($dapfforwc_normalize_hex_color) {
        $normalized = [];
        if (!is_array($terms)) {
            return $normalized;
        }
        foreach ($terms as $term) {
            $term_id = 0;
            $slug = '';
            $name = '';
            $taxonomy = '';
            $source_slug = '';
            if (is_array($term)) {
                $term_id = isset($term['term_id']) ? absint($term['term_id']) : 0;
                $slug = $term['slug'] ?? '';
                $name = $term['name'] ?? '';
                $taxonomy = $term['taxonomy'] ?? '';
                $source_slug = $term['source_slug'] ?? '';
            } elseif (is_object($term)) {
                $term_id = isset($term->term_id) ? absint($term->term_id) : 0;
                $slug = $term->slug ?? '';
                $name = $term->name ?? '';
                $taxonomy = $term->taxonomy ?? '';
                $source_slug = $term->source_slug ?? '';
            }

            if ($slug === '' && $name === '') {
                continue;
            }

            $slug = sanitize_text_field($slug);
            $name = sanitize_text_field($name);
            $taxonomy = sanitize_key($taxonomy);
            $source_slug = sanitize_text_field($source_slug);
            if ($term_id <= 0 && $slug !== '') {
                $lookup_taxonomy = $taxonomy !== '' ? $taxonomy : (function_exists('dapfforwc_get_style_term_taxonomy') ? dapfforwc_get_style_term_taxonomy((string) $attribute_name) : '');
                if ($lookup_taxonomy !== '') {
                    $resolved_term = get_term_by('slug', $slug, $lookup_taxonomy);
                    if (!$resolved_term && $source_slug !== '') {
                        $resolved_term = get_term_by('slug', $source_slug, $lookup_taxonomy);
                    }
                    if ($resolved_term && !is_wp_error($resolved_term)) {
                        $term_id = absint($resolved_term->term_id);
                        $taxonomy = sanitize_key($resolved_term->taxonomy);
                    }
                }
            }
            $default_color = $dapfforwc_normalize_hex_color($slug !== '' ? dapfforwc_color_name_to_hex($slug) : '', '#000000');

            $item = [
                'term_id' => $term_id,
                'slug' => $slug,
                'name' => $name,
                'taxonomy' => $taxonomy,
                'source_slug' => $source_slug,
                'style_key' => $term_id > 0 ? (string) $term_id : $slug,
                'default_color' => $default_color,
            ];

            if ($include_brand_image && $slug !== '') {
                $item['default_image'] = dapfforwc_get_wc_brand_image_by_slug($slug) ?: '';
            }

            $normalized[] = $item;
        }

        return $normalized;
    };

    $dapfforwc_terms_map = [];
    $dapfforwc_terms_map['product-category'] = $dapfforwc_normalize_terms(array_values($all_cata), false, 'product-category');
    $dapfforwc_terms_map['tag'] = $dapfforwc_normalize_terms(array_values($all_tags), false, 'tag');
    $dapfforwc_terms_map['brands'] = $dapfforwc_normalize_terms(array_values($all_brands), true, 'brands');
    $dapfforwc_terms_map['authors'] = $dapfforwc_normalize_terms(array_values($all_authors), false, 'authors');
    $dapfforwc_terms_map['status'] = $dapfforwc_normalize_terms(array_values($all_stock_status), false, 'status');
    $dapfforwc_terms_map['sale_status'] = $dapfforwc_normalize_terms(array_values($all_sale_status), false, 'sale_status');

    foreach ($all_attributes as $attribute_name => $attribute_data) {
        $dapfforwc_terms_map[$attribute_name] = $dapfforwc_normalize_terms(array_values($attribute_data['terms'] ?? []), false, $attribute_name);
    }

    foreach ($custom_fields as $field_name => $field_data) {
        $dapfforwc_terms_map[$field_name] = $dapfforwc_normalize_terms(array_values($field_data['terms'] ?? []), false, $field_name);
    }

    $dapfforwc_image_base = plugins_url('../assets/images/', __FILE__);
    $dapfforwc_upload_placeholder = plugin_dir_url(__FILE__) . '../assets/images/upload.png';
    ?>

    <?php if (!empty($dapfforwc_all_options)) : ?>
        <div class="attribute-selection">
            <label for="attribute-dropdown">
                <strong><?php esc_html_e('Configure Style for:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong>
            </label>
            <div style="display: flex; gap:10px">
                <select id="main-texonomy-dropdown" style="margin-bottom: 20px;">
                    <?php foreach ($main_texonomy as $option) : ?>
                        <option value="<?php echo esc_attr($option->attribute_name); ?>">
                            <?php echo esc_html($option->attribute_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="child-attr-dropdown" style="margin-bottom: 20px; display:none;">
                    <?php foreach ($dapfforwc_attributes as $option) : ?>
                        <option value="<?php echo esc_attr($option->attribute_name); ?>">
                            <?php echo esc_html($option->attribute_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="child-custom-dropdown" style="margin-bottom: 20px; display:none;">
                    <?php foreach ($all_custom_fields as $option) : ?>
                        <option value="<?php echo esc_attr($option->attribute_name); ?>">
                            <?php echo esc_html($option->attribute_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <select hidden id="attribute-dropdown" style="margin-bottom: 20px;">
                <?php foreach ($dapfforwc_all_options as $option) : ?>
                    <option value="<?php echo esc_attr($option->attribute_name); ?>">
                        <?php echo esc_html($option->attribute_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


        <?php ob_start(); ?>
            document.addEventListener('DOMContentLoaded', function() {
                const mainTaxonomyDropdown = document.getElementById('main-texonomy-dropdown');
                const childAttrDropdown = document.getElementById('child-attr-dropdown');
                const childCustomDropdown = document.getElementById('child-custom-dropdown');
                const attributeDropdown = document.getElementById('attribute-dropdown');
                const style_container = document.getElementById('style-options-container');
                const targetInput = document.getElementById('dapfforwc_style_options_target');
                const jsonInput = document.getElementById('dapfforwc_style_options_json');
                const selectedAttribute = style_container ? style_container.dataset.selectedAttribute : '';
                let isInitializing = true;

                const getStoredAttribute = function() {
                    try {
                        const stored = localStorage.getItem('dapfforwc_selected_attribute');
                        if (!stored) {
                            return '';
                        }
                        const parsed = JSON.parse(stored);
                        if (parsed && typeof parsed.attribute === 'string') {
                            return parsed.attribute;
                        }
                    } catch (e) {}
                    return '';
                };

                const isValidAttribute = function(value) {
                    if (!value || !attributeDropdown || !attributeDropdown.options) {
                        return false;
                    }
                    return Array.from(attributeDropdown.options).some(function(option) {
                        return option.value === value;
                    });
                };

                const normalizeAttributeValue = function(value) {
                    if (typeof value === 'string') {
                        return value;
                    }
                    if (value && typeof value.value === 'string') {
                        return value.value;
                    }
                    return '';
                };

                const getEffectiveAttribute = function(value) {
                    const normalized = normalizeAttributeValue(value);
                    if (normalized && isValidAttribute(normalized)) {
                        return normalized;
                    }
                    if (attributeDropdown && isValidAttribute(attributeDropdown.value)) {
                        return attributeDropdown.value;
                    }
                    if (style_container && isValidAttribute(style_container.dataset.selectedAttribute)) {
                        return style_container.dataset.selectedAttribute;
                    }
                    return '';
                };

                const setHiddenValue = function(input, value) {
                    if (!input) {
                        return;
                    }
                    const safeValue = typeof value === 'string' ? value : '';
                    input.value = safeValue;
                    input.setAttribute('value', safeValue);
                };

                const syncHiddenInputs = function(value) {
                    const effective = getEffectiveAttribute(value);
                    setHiddenValue(targetInput, effective);
                    if (jsonInput) {
                        const jsonValue = typeof jsonInput.value === 'string' ? jsonInput.value : '';
                        if (jsonValue === '[object Object]') {
                            setHiddenValue(jsonInput, '');
                        }
                    }
                };

                const setStoredAttribute = function(value) {
                    if (!value || typeof value !== 'string') {
                        return;
                    }
                    try {
                        localStorage.setItem('dapfforwc_selected_attribute', JSON.stringify({
                            attribute: value
                        }));
                    } catch (e) {}
                };

                const toggleChildAttrSelect2 = function(shouldEnable) {
                    if (!childAttrDropdown || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                        return;
                    }
                    const $select = window.jQuery(childAttrDropdown);
                    const isInitialized = $select.hasClass('select2-hidden-accessible');

                    if (shouldEnable) {
                        if (!isInitialized) {
                            $select.select2({
                                placeholder: 'Select an attribute',
                                width: 'resolve',
                                allowClear: false,
                            });
                        }
                        const $container = $select.next('.select2');
                        if ($container.length) {
                            $container.show();
                        }
                    } else if (isInitialized) {
                        $select.select2('destroy');
                    }
                };

                mainTaxonomyDropdown.addEventListener('change', function() {
                    const selectedValue = this.value;

                    // Hide both child dropdowns initially
                    childAttrDropdown.style.display = 'none';
                    childCustomDropdown.style.display = 'none';
                    toggleChildAttrSelect2(false);

                    if (selectedValue === 'attributes') {
                        childAttrDropdown.style.display = 'block';
                        toggleChildAttrSelect2(true);
                        let selectedattr = childAttrDropdown.value;
                        if (!selectedattr) {
                            style_container.style.display = 'none';
                            syncHiddenInputs('');
                        } else {
                            updateAttributeDropdown(selectedattr, !isInitializing);
                        }
                    } else if (selectedValue === 'custom_fields') {
                        childCustomDropdown.style.display = 'block';
                        let selectedcus = childCustomDropdown.value;
                        if (!selectedcus) {
                            style_container.style.display = 'none';
                            syncHiddenInputs('');
                        } else {
                            updateAttributeDropdown(selectedcus, !isInitializing);
                        }
                    } else {
                        // Trigger changes in attribute-dropdown
                        updateAttributeDropdown(selectedValue, !isInitializing);
                    }
                });

                const handleChildDropdownChange = function(value) {
                    if (style_container) {
                        style_container.style.display = 'block';
                    }
                    updateAttributeDropdown(value, !isInitializing);
                };

                const bindChildAttrChange = function() {
                    if (!childAttrDropdown) {
                        return;
                    }
                    const handler = function() {
                        handleChildDropdownChange(childAttrDropdown.value);
                    };
                    if (window.jQuery && window.jQuery.fn) {
                        window.jQuery(childAttrDropdown).on('change', handler);
                    } else {
                        childAttrDropdown.addEventListener('change', handler);
                    }
                };

                bindChildAttrChange();

                if (childCustomDropdown) {
                    childCustomDropdown.addEventListener('change', function() {
                        handleChildDropdownChange(this.value);
                    });
                }

                function updateAttributeDropdown(selectedValue, shouldSwitch) {
                    const effectiveAttribute = getEffectiveAttribute(selectedValue);
                    if (!effectiveAttribute) {
                        syncHiddenInputs('');
                        return;
                    }

                    // Set the attribute-dropdown to the selected value
                    attributeDropdown.value = effectiveAttribute;
                    setHiddenValue(targetInput, effectiveAttribute);
                    setHiddenValue(jsonInput, '');
                    setStoredAttribute(effectiveAttribute);
                    if (shouldSwitch && typeof window.dapfforwcSwitchAttribute === 'function') {
                        window.dapfforwcSwitchAttribute(effectiveAttribute);
                    }
                    setTimeout(function() {
                        syncHiddenInputs(effectiveAttribute);
                    }, 0);
                }

                const storedAttribute = getStoredAttribute();
                const initialAttribute = isValidAttribute(storedAttribute) ? storedAttribute : selectedAttribute;
                if (initialAttribute) {
                    if (!isValidAttribute(storedAttribute)) {
                        setStoredAttribute(initialAttribute);
                    }
                    attributeDropdown.value = initialAttribute;
                    setHiddenValue(targetInput, initialAttribute);
                    mainTaxonomyDropdown.value = initialAttribute;
                    childAttrDropdown.value = initialAttribute;
                    childCustomDropdown.value = initialAttribute;

                    if (childAttrDropdown.value) {
                        mainTaxonomyDropdown.value = 'attributes';
                        childAttrDropdown.style.display = 'block';
                        childCustomDropdown.style.display = 'none';
                        toggleChildAttrSelect2(true);
                    } else if (childCustomDropdown.value) {
                        mainTaxonomyDropdown.value = 'custom_fields';
                        childCustomDropdown.style.display = 'block';
                        childAttrDropdown.style.display = 'none';
                        toggleChildAttrSelect2(false);
                    }

                    if (style_container) {
                        style_container.dataset.selectedAttribute = initialAttribute;
                    }
                    if (storedAttribute && storedAttribute !== selectedAttribute && typeof window.dapfforwcSwitchAttribute === 'function') {
                        window.dapfforwcSwitchAttribute(initialAttribute);
                    }
                    syncHiddenInputs(initialAttribute);
                }

                setTimeout(function() {
                    isInitializing = false;
                }, 0);

                if (attributeDropdown) {
                    attributeDropdown.addEventListener('change', function() {
                        updateAttributeDropdown(this.value, !isInitializing);
                    });
                }
            });
        <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>

        <!-- Style Options Container -->
        <div id="style-options-container" data-dynamic="true" data-selected-attribute="<?php echo esc_attr($dapfforwc_selected_attribute); ?>">
            <?php foreach ($dapfforwc_all_options as $option) :
                $dapfforwc_attribute_name = $option->attribute_name;
                if ($dapfforwc_attribute_name !== $dapfforwc_selected_attribute) {
                    continue;
                }
                $dapfforwc_is_simple = in_array(
                    $dapfforwc_attribute_name,
                    ['reset_btn'],
                    true
                );
            ?>
                <div class="style-options" id="options-<?php echo esc_attr($dapfforwc_attribute_name); ?>" data-current-attribute="<?php echo esc_attr($dapfforwc_attribute_name); ?>" style="display: block;">
                    <h3 class="section-title"><span class="style-attribute-label"><?php echo esc_html($option->attribute_label); ?></span> <?php echo esc_html__('Filter Style', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h3>
                    <div class="style-options-block style-options-simple" data-style-block="simple" style="display: <?php echo $dapfforwc_is_simple ? 'block' : 'none'; ?>;">
                        <div class="optional_settings">
                            <h4><?php esc_html_e('Optional Settings:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h4>

                            <div data-attr-exclude="reset_btn">
                                <!-- Enable Minimization Option -->
                                <div class="setting-item" style="padding-top: 20px;">
                                    <p><strong><?php esc_html_e('Enable Minimization Option:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                    <label>
                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="disabled"
                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'disabled'); ?>>
                                        <?php esc_html_e('Disabled', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="arrow"
                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'arrow'); ?>>
                                        <?php esc_html_e('Enabled with Arrow', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="no_arrow"
                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'no_arrow'); ?>>
                                        <?php esc_html_e('Enabled without Arrow', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="minimize_initial"
                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'minimize_initial'); ?>>
                                        <?php esc_html_e('Initially Minimized', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                    </label>
                                </div>
                                <!-- Show in Active/Chips Filter widget -->
                                <div class="setting-item" style="padding-top: 20px;">
                                    <p><strong><?php esc_html_e('Show in Active/Chips Filter Widget:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                    <label>
                                        <select name="dapfforwc_style_options[show_in_active_filters][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                            <option value="yes" <?php selected($dapfforwc_form_styles["show_in_active_filters"][$dapfforwc_attribute_name] ?? 'yes', 'yes'); ?>>
                                                <?php esc_html_e('Yes', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </option>
                                            <option value="no" <?php selected($dapfforwc_form_styles["show_in_active_filters"][$dapfforwc_attribute_name] ?? 'yes', 'no'); ?>>
                                                <?php esc_html_e('No', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </option>
                                        </select>
                                    </label>
                                </div>
                                <!-- Show/Hide Widget Title -->
                                <div class="setting-item">
                                    <p><strong><?php esc_html_e('Show/Hide Widget Title:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                    <label>
                                        <select name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][show_widget_title]">
                                            <option value="yes" <?php selected($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['show_widget_title'] ?? 'yes', 'yes'); ?>>
                                                <?php esc_html_e('Show', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </option>
                                            <option value="no" <?php selected($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['show_widget_title'] ?? 'yes', 'no'); ?>>
                                                <?php esc_html_e('Hide', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </option>
                                        </select>
                                    </label>
                                </div>
                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Widget Title:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $widget_title = isset($dapfforwc_form_styles["widget_title"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["widget_title"][$dapfforwc_attribute_name]) : ''; ?>
                                                <input type="text" name="dapfforwc_style_options[widget_title][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($widget_title); ?>">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div data-attr-only="search">
                                <div class="row btn_text" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Button Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $btntext = isset($dapfforwc_form_styles["btntext"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["btntext"][$dapfforwc_attribute_name]) : ''; ?>
                                                <input type="text" name="dapfforwc_style_options[btntext][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($btntext); ?>">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div data-attr-only="reset_btn">
                                <!-- show reset button only one instead of multiple -->
                                <!-- show apply button checkbox -->
                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Enable Apply Button:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $show_apply = isset($dapfforwc_form_styles['show_apply_button'][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles['show_apply_button'][$dapfforwc_attribute_name]) : 'no'; ?>
                                                <input type="checkbox" name="dapfforwc_style_options[show_apply_button][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes" <?php checked($show_apply, 'yes'); ?>>
                                                <?php esc_html_e('Show Apply Button', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row btn_text" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Apply Button Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $btntext = isset($dapfforwc_form_styles["applybtntext"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["applybtntext"][$dapfforwc_attribute_name]) : ''; ?>
                                                <input type="text" name="dapfforwc_style_options[applybtntext][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($btntext); ?>" <?php echo $show_apply === 'no' ? 'disabled' : ''; ?>>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php $apply_behavior = isset($dapfforwc_form_styles['apply_behavior'][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles['apply_behavior'][$dapfforwc_attribute_name]) : 'only_apply'; ?>
                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Apply filter on:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <select name="dapfforwc_style_options[apply_behavior][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" <?php echo $show_apply === 'no' ? 'disabled' : ''; ?>>
                                                    <option value="only_apply" <?php selected($apply_behavior, 'only_apply'); ?>>
                                                        <?php esc_html_e('Button click', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </option>
                                                    <option value="apply_and_select" <?php selected($apply_behavior, 'apply_and_select'); ?>>
                                                        <?php esc_html_e('Button click & Select Option', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </option>
                                                </select>
                                            </label>
                                            <p class="description"><?php esc_html_e('Apply Button Only: filters apply only when the apply button is clicked. Apply Button & Select Option: filters apply on both selecting an option and clicking the apply button.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Enable Reset Button:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $show_reset = isset($dapfforwc_form_styles['show_reset_button'][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles['show_reset_button'][$dapfforwc_attribute_name]) : 'no'; ?>
                                                <input type="checkbox" name="dapfforwc_style_options[show_reset_button][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes" <?php checked($show_reset, 'yes'); ?>>
                                                <?php esc_html_e('Show Reset Button', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row btn_text" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Reset Button Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $btntext = isset($dapfforwc_form_styles["btntext"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["btntext"][$dapfforwc_attribute_name]) : ''; ?>
                                                <input type="text" name="dapfforwc_style_options[btntext][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($btntext); ?>" <?php echo $show_reset === 'no' ? 'disabled' : ''; ?>>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row btn_text" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Show Apply & Reset Button On:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $show_apply_reset_on = isset($dapfforwc_form_styles["show_apply_reset_on"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["show_apply_reset_on"][$dapfforwc_attribute_name]) : 'separate'; ?>
                                                <select name="dapfforwc_style_options[show_apply_reset_on][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" <?php echo ($show_apply === 'no' && $show_reset === 'no') ? 'disabled' : ''; ?> id="show_apply_reset_on_<?php echo esc_attr($dapfforwc_attribute_name); ?>">
                                                    <option value="top" <?php selected($show_apply_reset_on, 'top'); ?>><?php esc_html_e('Top', 'dynamic-ajax-product-filters-for-woocommerce'); ?></option>
                                                    <option value="bottom" <?php selected($show_apply_reset_on, 'bottom'); ?>><?php esc_html_e('Bottom', 'dynamic-ajax-product-filters-for-woocommerce'); ?></option>
                                                    <option value="separate" <?php selected($show_apply_reset_on, 'separate'); ?>><?php esc_html_e('Separately', 'dynamic-ajax-product-filters-for-woocommerce'); ?></option>
                                                </select>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Desktop Filter Heading:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $show_filter_heading = isset($dapfforwc_form_styles['reset_btn']['show_filter_heading']) ? esc_attr($dapfforwc_form_styles['reset_btn']['show_filter_heading']) : 'no'; ?>
                                                <input type="checkbox" name="dapfforwc_style_options[reset_btn][show_filter_heading]" value="yes" <?php checked($show_filter_heading, 'yes'); ?>>
                                                <?php esc_html_e('Show a heading beside the top buttons on desktop', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Desktop Filter Heading Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $filter_heading_text = isset($dapfforwc_form_styles['reset_btn']['filter_heading_text']) ? esc_attr($dapfforwc_form_styles['reset_btn']['filter_heading_text']) : ''; ?>
                                                <input type="text" name="dapfforwc_style_options[reset_btn][filter_heading_text]" value="<?php echo esc_attr($filter_heading_text); ?>" placeholder="<?php echo esc_attr__('Filter', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" style="padding-top: 16px;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Mobile Drawer Title:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $mobile_filter_title = isset($dapfforwc_form_styles['reset_btn']['mobile_filter_title']) ? esc_attr($dapfforwc_form_styles['reset_btn']['mobile_filter_title']) : ''; ?>
                                                <input type="text" name="dapfforwc_style_options[reset_btn][mobile_filter_title]" value="<?php echo esc_attr($mobile_filter_title); ?>" placeholder="<?php echo esc_attr__('Showing (All)', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                                            </label>
                                            <p class="description"><?php esc_html_e('Leave empty to show the dynamic product result count.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <p class="description"><?php esc_html_e('Note: "Separately" option is only available when there is any select layout or single selection layout in the filter.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                            </div>
                            <div data-attr-only="date_filter">
                                <!-- date_filter text manage -->
                                <div class="row" style="padding-top: 16px; gap:16px; flex-wrap: wrap;">
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('All Time:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $all_time_text = isset($dapfforwc_form_styles["date_filter_texts"]["all_time_text"]) ? esc_attr($dapfforwc_form_styles["date_filter_texts"]["all_time_text"]) : 'All Time'; ?>
                                                <input type="text" name="dapfforwc_style_options[date_filter_texts][all_time_text]" value="<?php echo esc_attr($all_time_text); ?>">
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Today -->
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('Today:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $today_text = isset($dapfforwc_form_styles["date_filter_texts"]["today_text"]) ? esc_attr($dapfforwc_form_styles["date_filter_texts"]["today_text"]) : 'Today'; ?>
                                                <input type="text" name="dapfforwc_style_options[date_filter_texts][today_text]" value="<?php echo esc_attr($today_text); ?>">
                                            </label>
                                        </div>
                                    </div>
                                    <!-- This Week -->
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('This Week:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $this_week_text = isset($dapfforwc_form_styles["date_filter_texts"]["this_week_text"]) ? esc_attr($dapfforwc_form_styles["date_filter_texts"]["this_week_text"]) : 'This Week'; ?>
                                                <input type="text" name="dapfforwc_style_options[date_filter_texts][this_week_text]" value="<?php echo esc_attr($this_week_text); ?>">
                                            </label>
                                        </div>
                                    </div>
                                    <!-- This Month -->
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('This Month:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $this_month_text = isset($dapfforwc_form_styles["date_filter_texts"]["this_month_text"]) ? esc_attr($dapfforwc_form_styles["date_filter_texts"]["this_month_text"]) : 'This Month'; ?>
                                                <input type="text" name="dapfforwc_style_options[date_filter_texts][this_month_text]" value="<?php echo esc_attr($this_month_text); ?>">
                                            </label>
                                        </div>
                                    </div>
                                    <!-- This Year -->
                                    <div class="col-6">
                                        <div class="setting-item">
                                            <p><strong><?php esc_html_e('This Year:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                            <label>
                                                <?php $this_year_text = isset($dapfforwc_form_styles["date_filter_texts"]["this_year_text"]) ? esc_attr($dapfforwc_form_styles["date_filter_texts"]["this_year_text"]) : 'This Year'; ?>
                                                <input type="text" name="dapfforwc_style_options[date_filter_texts][this_year_text]" value="<?php echo esc_attr($this_year_text); ?>">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>





                        </div>
                    </div>
                    <div class="style-options-block style-options-full" data-style-block="full" style="display: <?php echo $dapfforwc_is_simple ? 'none' : 'block'; ?>;">
                        <?php
                        $dapfforwc_selected_style = $dapfforwc_form_styles[$dapfforwc_attribute_name]['type'] ?? 'checkbox';
                        $dapfforwc_sub_option = $dapfforwc_form_styles[$dapfforwc_attribute_name]['sub_option'] ?? ''; // current stored in database
                        global $dapfforwc_sub_options; //get from root page

                        ?>
                        <div class="style-options-inner">

                            <!-- Primary Options -->
                            <div class="primary_options">
                                <?php foreach ($dapfforwc_sub_options as $key => $label) :
                                    if (($dapfforwc_attribute_name === 'brands' || $dapfforwc_attribute_name === 'product-category' || $dapfforwc_attribute_name === 'tag') && ($key === "plugincy_color" || $key === "price" || $key === "rating" || $key === "dimension_input" || $key === "plugincy_search" || $key === "simple_input" || $key === "discount_slider" || $key === "date_range")) {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name !== 'search' && $key === "plugincy_search") {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name === 'search' && $key !== "plugincy_search") {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name !== 'dimensions' && $key === "dimension_input") {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name === 'dimensions' && $key !== "dimension_input") {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name === 'sku' && $key !== "simple_input") {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name === 'discount' && !in_array($key, array("simple_input", "discount_slider"), true)) {
                                        continue;
                                    }

                                    if ($dapfforwc_attribute_name === 'date_filter' && $key !== "date_range") {
                                        continue;
                                    }

                                ?>
                                    <?php
                                    $dapfforwc_primary_style_classes = array($key);
                                    if ($dapfforwc_selected_style === $key) {
                                        $dapfforwc_primary_style_classes[] = 'active';
                                    }
                                    $dapfforwc_primary_style_display = in_array($key, array('price', 'rating', 'dimension_input', 'discount_slider', 'simple_input', 'date_range'), true) ? 'none' : 'block';
                                    ?>
                                    <label class="<?php echo esc_attr(implode(' ', $dapfforwc_primary_style_classes)); ?>" style="display:<?php echo esc_attr($dapfforwc_primary_style_display); ?>;">
                                        <span class="active" style="display:none;">
                                        </span>
                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][type]" value="<?php echo esc_html($key); ?>" <?php checked($dapfforwc_selected_style, $key); ?> data-type="<?php echo esc_html($key); ?>">
                                        <img src="<?php echo esc_url(plugins_url('../assets/images/' . $key . '.png', __FILE__)); ?>" alt="<?php echo esc_attr($key); ?>">
                                        <!-- <div class="plugincy_title"> -->
                                        <?php
                                        // echo esc_html($key); 
                                        ?>
                                        <!-- </div> -->
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Sub-Options -->
                            <div class="sub-options">
                                <p class="sub-options-title"><strong><?php echo esc_html__('Choose Display Style', 'dynamic-ajax-product-filters-for-woocommerce'); ?> <span class="required">*</span></strong></p>
                                <div class="dynamic-sub-options">
                                    <?php foreach ($dapfforwc_sub_options[$dapfforwc_selected_style] as $key => $label) : ?>

                                        <label class="<?php echo $dapfforwc_sub_option === $key ? 'active ' : '';
                                                        echo esc_attr($key);
                                                        echo ($key === "dynamic-rating" || $key === "rating-slider" || $key === "input-price-range" || $key === "chart-slider" || $key === "price-range-button" || $key === "price-range-card" || $key === "color_circle" || $key === "color_value" || $key === "color_swatch_label" || $key === "image_value" || $key === "button_check" || $key === "chips" || $key === "button_chips" || $key === "pill-stepper" || $key === "compact-stepper" || $key === "boxed-stepper" || $key === "stepper" || $key === "icon_only" || $key === "icon_badge" || $key === "icon_chip" || $key === "icon_list" || $key === "icon_card" || $key === "checkbox_dropdown" || $key === "modern_dropdown" || $key === "chip_dropdown" || $key === "dimension_range" || $key === "dimensions-compact-range-card" || $key === "sku_search_button" || $key === "sku_icon_search" || $key === "discount_slider" || $key === "date-modern-calendar" || $key === "date-quick-picks" || $key === "date-dual-calendar" || $key === "date-stacked-list") ? ' pro-only' : ''; ?>">
                                            <span class="active" style="display:none;">
                                            </span>
                                            <input <?php if ($key === "dynamic-rating" || $key === "rating-slider" || $key === "input-price-range" || $key === "chart-slider" || $key === "price-range-button" || $key === "price-range-card" || $key === "color_circle" || $key === "color_value" || $key === "color_swatch_label" || $key === "image_value" || $key === "button_check" || $key === "chips" || $key === "button_chips" || $key === "pill-stepper" || $key === "compact-stepper" || $key === "boxed-stepper" || $key === "stepper" || $key === "icon_only" || $key === "icon_badge" || $key === "icon_chip" || $key === "icon_list" || $key === "icon_card" || $key === "checkbox_dropdown" || $key === "modern_dropdown" || $key === "chip_dropdown" || $key === "dimension_range" || $key === "dimensions-compact-range-card" || $key === "sku_search_button" || $key === "sku_icon_search" || $key === "discount_slider" || $key === "date-modern-calendar" || $key === "date-quick-picks" || $key === "date-dual-calendar" || $key === "date-stacked-list") {
                                                        echo 'disabled';
                                                    } ?> type="radio" class="optionselect" name="<?php echo ($key === "dynamic-rating" || $key === "rating-slider" || $key === "input-price-range" || $key === "chart-slider" || $key === "price-range-button" || $key === "price-range-card" || $key === "color_circle" || $key === "color_value" || $key === "color_swatch_label" || $key === "image_value" || $key === "button_check" || $key === "chips" || $key === "button_chips" || $key === "pill-stepper" || $key === "compact-stepper" || $key === "boxed-stepper" || $key === "stepper" || $key === "icon_only" || $key === "icon_badge" || $key === "icon_chip" || $key === "icon_list" || $key === "icon_card" || $key === "checkbox_dropdown" || $key === "modern_dropdown" || $key === "chip_dropdown" || $key === "dimension_range" || $key === "dimensions-compact-range-card" || $key === "sku_search_button" || $key === "sku_icon_search" || $key === "discount_slider" || $key === "date-modern-calendar" || $key === "date-quick-picks" || $key === "date-dual-calendar" || $key === "date-stacked-list") ? '_pro' : 'dapfforwc_style_options'; ?>[<?php echo esc_attr($dapfforwc_attribute_name); ?>][sub_option]" value="<?php echo esc_attr($key); ?>" <?php checked($dapfforwc_sub_option, $key); ?>>
                                            <img src="<?php echo esc_url(plugins_url('../assets/images/' . $key . '.png', __FILE__)); ?>" alt="<?php echo esc_attr($label); ?>">
                                            <!-- <div class="plugincy_title"> -->
                                            <?php
                                            // echo esc_html($label); 
                                            ?>
                                            <!-- </div> -->
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Advanced Options for Color/Image -->
                            <div style="margin-bottom: 16px;">
                                <?php
                                // Determine terms based on attribute type
                                $dapfforwc_terms = [];
                                if ($dapfforwc_attribute_name === "price" || $dapfforwc_attribute_name === "rating" || $dapfforwc_attribute_name === "dimensions" || $dapfforwc_attribute_name === "discount" || $dapfforwc_attribute_name === "sku" || $dapfforwc_attribute_name === "date_filter") {
                                    $dapfforwc_terms = [];
                                } elseif ($dapfforwc_attribute_name === "product-category") {
                                    $dapfforwc_terms = $all_cata;
                                } elseif ($dapfforwc_attribute_name === "tag") {
                                    $dapfforwc_terms = $all_tags;
                                } elseif (isset($all_attributes[$dapfforwc_attribute_name]["terms"])) {
                                    // For WooCommerce attributes
                                    $dapfforwc_terms = $all_attributes[$dapfforwc_attribute_name]["terms"];
                                } elseif (isset($custom_fields[$dapfforwc_attribute_name])) {
                                    // For custom fields - you might need to define how custom field terms are structured
                                    $dapfforwc_terms = $custom_fields[$dapfforwc_attribute_name]["terms"] ?? [];
                                } elseif ($dapfforwc_attribute_name === "brands") {
                                    foreach ($all_brands as $brand) {
                                        $dapfforwc_terms[] = $brand;
                                    }
                                } elseif ($dapfforwc_attribute_name === "authors") {
                                    $dapfforwc_terms = $all_authors;
                                } elseif ($dapfforwc_attribute_name === "status") {
                                    $dapfforwc_terms = $all_stock_status;
                                } elseif ($dapfforwc_attribute_name === "sale_status") {
                                    $dapfforwc_terms = $all_sale_status;
                                } else {
                                    $dapfforwc_terms = [];
                                }
                                ?>
                                <div class="advanced-options" data-attr-exclude="tag search" style="display: <?php echo $dapfforwc_selected_style === 'plugincy_color' || $dapfforwc_selected_style === 'image' ? 'block' : 'none'; ?>;">
                                    <h4 class="advanced-title" style="margin: 0;"><?php esc_html_e('Advanced Options for Terms', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h4>
                                    <p class="no-terms-message" style="display: <?php echo empty($dapfforwc_terms) ? 'block' : 'none'; ?>;"><?php esc_html_e('No terms found. Please create terms for this attribute first.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>

                                    <!-- Color Options -->
                                    <div class="plugincy_color" style="display: <?php echo $dapfforwc_selected_style === 'plugincy_color' ? 'block' : 'none'; ?>;">
                                        <h5><?php esc_html_e('Set Colors for Terms', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h5>
                                        <div class="color-options">
                                            <?php foreach ($dapfforwc_terms as $term) :
                                                $dapfforwc_term_slug = (string) dapfforwc_get_term_item_field($term, 'slug', '');
                                                $dapfforwc_term_name = (string) dapfforwc_get_term_item_field($term, 'name', '');
                                                $dapfforwc_term_style_key = dapfforwc_get_term_style_storage_key($term);
                                                if ($dapfforwc_term_style_key === '' && $dapfforwc_term_slug === '') {
                                                    continue;
                                                }
                                                $dapfforwc_term_input_id = sanitize_title($dapfforwc_attribute_name . '-' . ($dapfforwc_term_style_key !== '' ? $dapfforwc_term_style_key : $dapfforwc_term_slug));
                                                $dapfforwc_default_color = $dapfforwc_term_slug !== '' ? dapfforwc_color_name_to_hex($dapfforwc_term_slug) : '';
                                                $dapfforwc_raw_color_value = dapfforwc_get_term_style_option_value($dapfforwc_form_styles, $dapfforwc_attribute_name, 'colors', $term, $dapfforwc_default_color);
                                                $dapfforwc_color_value = $dapfforwc_normalize_hex_color($dapfforwc_raw_color_value, '#000000');
                                            ?>
                                                <div class="term-option">
                                                    <label for="color-<?php echo esc_attr($dapfforwc_term_input_id); ?>">
                                                        <strong><?php echo esc_html($dapfforwc_term_name); ?></strong>
                                                    </label>
                                                    <input type="color" id="color-<?php echo esc_attr($dapfforwc_term_input_id); ?>" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][colors][<?php echo esc_attr($dapfforwc_term_style_key); ?>]" value="<?php echo esc_attr($dapfforwc_color_value); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Image Options -->
                                    <div class="image" style="display: <?php echo $dapfforwc_selected_style === 'image' ? 'block' : 'none'; ?>;">
                                        <h5><?php esc_html_e('Set Images for Terms', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h5>
                                        <div class="image-options">
                                            <?php foreach ($dapfforwc_terms as $term) :
                                                $dapfforwc_term_slug = (string) dapfforwc_get_term_item_field($term, 'slug', '');
                                                $dapfforwc_term_name = (string) dapfforwc_get_term_item_field($term, 'name', '');
                                                $dapfforwc_term_style_key = dapfforwc_get_term_style_storage_key($term);
                                                if ($dapfforwc_term_style_key === '' && $dapfforwc_term_slug === '') {
                                                    continue;
                                                }
                                                $dapfforwc_term_input_id = sanitize_title($dapfforwc_attribute_name . '-' . ($dapfforwc_term_style_key !== '' ? $dapfforwc_term_style_key : $dapfforwc_term_slug));
                                                $dapfforwc_default_image_url = $dapfforwc_term_slug !== '' ? (dapfforwc_get_wc_brand_image_by_slug($dapfforwc_term_slug) ?: '') : '';
                                                $dapfforwc_custom_image_value = dapfforwc_get_term_style_option_value($dapfforwc_form_styles, $dapfforwc_attribute_name, 'images', $term, '');

                                                $dapfforwc_image_preview = !empty($dapfforwc_custom_image_value)
                                                    ? $dapfforwc_custom_image_value
                                                    : (!empty($dapfforwc_default_image_url) ? $dapfforwc_default_image_url : $dapfforwc_upload_placeholder);

                                            ?>
                                                <div class="term-option <?php echo !empty($dapfforwc_custom_image_value) ? 'has-custom-image' : ''; ?>" data-default-image="<?php echo esc_attr($dapfforwc_default_image_url); ?>" data-placeholder-image="<?php echo esc_attr($dapfforwc_upload_placeholder); ?>">
                                                    <img src="<?php echo esc_attr($dapfforwc_image_preview); ?>" style="max-width: 170px;">
                                                    <label for="image-<?php echo esc_attr($dapfforwc_term_input_id); ?>">
                                                        <strong><?php echo esc_html($dapfforwc_term_name); ?></strong>
                                                    </label>
                                                    <input type="hidden" id="image-<?php echo esc_attr($dapfforwc_term_input_id); ?>" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][images][<?php echo esc_attr($dapfforwc_term_style_key); ?>]" value="<?php echo esc_attr($dapfforwc_custom_image_value); ?>" placeholder="<?php esc_attr_e('Image URL', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                                                    <div class="term-option-actions">
                                                        <button type="button" class="upload-image-button" title="<?php echo esc_attr__('Select image', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" aria-label="<?php echo esc_attr__('Select image', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                                                            <svg class="edit-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                                                            </svg>
                                                            <span class="screen-reader-text"><?php esc_html_e('Select image', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                                                        </button>
                                                        <button type="button" class="remove-image-button <?php echo empty($dapfforwc_custom_image_value) ? 'is-hidden' : ''; ?>" title="<?php echo esc_attr__('Remove image', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" aria-label="<?php echo esc_attr__('Remove image', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                                                            <svg class="trash-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                <path d="M9 3h6l1 2h5v2H3V5h5l1-2zm1 6h2v8h-2V9zm4 0h2v8h-2V9zM7 9h2v8H7V9zm1 12c-1.1 0-2-.9-2-2V8h12v11c0 1.1-.9 2-2 2H8z" />
                                                            </svg>
                                                            <span class="screen-reader-text"><?php esc_html_e('Remove image', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Options for Color/Image Ends -->

                                <div style="display: flex;flex-wrap: wrap;gap: 20px;">
                                    <!-- Optional Settings -->
                                    <div class="optional_settings">
                                        <h4><?php esc_html_e('Optional Settings:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h4>

                                        <div class="row" style="padding-top: 16px;">
                                            <div class="col-6">
                                                <!-- Hierarchical -->
                                                <div class="setting-item hierarchical" data-attr-only="product-category" style="display:none;">
                                                    <p><strong><?php esc_html_e('Enable Hierarchical:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][hierarchical][type]" value="disabled"
                                                            <?php
                                                            $hierarchical_type = $dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['hierarchical']['type'] ?? 'disabled';
                                                            checked($hierarchical_type === 'disabled' || ($hierarchical_type === 'enable_separate'));
                                                            ?>>
                                                        <?php esc_html_e('Disabled', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][hierarchical][type]" value="enable"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['hierarchical']['type'] ?? 'disabled', 'enable'); ?>>
                                                        <?php esc_html_e('Enabled', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                    <label <?php echo 'class="pro-only"'; ?>>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][hierarchical][type]" value="enable_separate"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['hierarchical']['type'] ?? 'disabled', 'enable_separate'); ?>>
                                                        <?php esc_html_e('Enabled & Separate', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][hierarchical][type]" value="enable_hide_child"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['hierarchical']['type'] ?? 'disabled', 'enable_hide_child'); ?>>
                                                        <?php esc_html_e('Enabled & hide child', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                </div>

                                                <!-- Enable Minimization Option -->
                                                <div class="setting-item">
                                                    <p><strong><?php esc_html_e('Enable Minimization Option:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="disabled"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'disabled'); ?>>
                                                        <?php esc_html_e('Disabled', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="arrow"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'arrow'); ?>>
                                                        <?php esc_html_e('Enabled with Arrow', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="no_arrow"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'no_arrow'); ?>>
                                                        <?php esc_html_e('Enabled without Arrow', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][minimize][type]" value="minimize_initial"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['minimize']['type'] ?? 'arrow', 'minimize_initial'); ?>>
                                                        <?php esc_html_e('Initially Minimized', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                </div>


                                                <!-- Single Selection Option -->
                                                <div class="setting-item single-selection" data-attr-exclude="price rating search dimensions sku discount date_filter" style="display: <?php echo $dapfforwc_sub_option === 'select' ? 'none' : 'block'; ?> ;">
                                                    <p><strong><?php esc_html_e('Single Selection:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <input type="checkbox" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][single_selection]" value="yes"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['single_selection'] ?? '', 'yes'); ?>>
                                                        <?php esc_html_e('Only one value can be selected at a time', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                </div>

                                                <!-- Show/Hide Number of Products -->
                                                <div class="setting-item show-product-count" data-attr-exclude="price rating search dimensions sku discount date_filter">
                                                    <p><strong><?php esc_html_e('Show/Hide Number of Products:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <input type="checkbox" name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][show_product_count]" value="yes"
                                                            <?php checked($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['show_product_count'] ?? '', 'yes'); ?>>
                                                        <?php esc_html_e('Show number of products', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                </div>
                                                <!-- Max Height -->
                                                <div class="setting-item" data-attr-exclude="price dimensions sku discount date_filter">
                                                    <p><strong><?php esc_html_e('Max Height:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <?php $max_height = isset($dapfforwc_form_styles["max_height"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["max_height"][$dapfforwc_attribute_name]) : 0; ?>
                                                        <input type="number" name="dapfforwc_style_options[max_height][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($max_height); ?>">
                                                    </label>
                                                </div>
                                                <!-- show/hide widget title -->
                                                <div class="setting-item">
                                                    <p><strong><?php esc_html_e('Show/Hide Widget Title:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <select name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][show_widget_title]">
                                                            <option value="yes" <?php selected($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['show_widget_title'] ?? 'yes', 'yes'); ?>>
                                                                <?php esc_html_e('Show', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                            </option>
                                                            <option value="no" <?php selected($dapfforwc_form_styles[esc_attr($dapfforwc_attribute_name)]['show_widget_title'] ?? 'yes', 'no'); ?>>
                                                                <?php esc_html_e('Hide', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                            </option>
                                                        </select>
                                                    </label>
                                                </div>
                                                <!-- Widget Title -->
                                                <div class="setting-item">
                                                    <p><strong><?php esc_html_e('Widget Title:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <?php $widget_title = isset($dapfforwc_form_styles["widget_title"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["widget_title"][$dapfforwc_attribute_name]) : ''; ?>
                                                        <input type="text" name="dapfforwc_style_options[widget_title][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($widget_title); ?>">
                                                    </label>
                                                </div>
                                                <div class="setting-item widget-title-icon-setting pro-only" data-attr-exclude="reset_btn">
                                                    <div class="dapfforwc-widget-title-icon-header">
                                                        <div class="dapfforwc-widget-title-icon-heading">
                                                            <p><strong><?php esc_html_e('Widget Title Icon:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                            <span><?php esc_html_e('Display a small icon before this filter title.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                                                        </div>
                                                        <label class="dapfforwc-widget-title-icon-toggle">
                                                            <input disabled type="checkbox" class="enable-widget-title-icon-checkbox" name="dapfforwc_style_options[enable_widget_title_icon][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes">
                                                            <span><?php esc_html_e('Enable', 'dynamic-ajax-product-filters-for-woocommerce'); ?></span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- additional text for rating -->
                                                <div class="additional_txt_rating" data-attr-only="rating" style="display: <?php echo $dapfforwc_sub_option !== 'rating-text' ? 'none' : 'block'; ?> ;">
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Additional Text for 1 Star:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <?php $additional_text_1 = isset($dapfforwc_form_styles["additional_text_1"]["rating"]) ? esc_attr($dapfforwc_form_styles["additional_text_1"]["rating"]) : ''; ?>
                                                            <input type="text" name="dapfforwc_style_options[additional_text_1][rating]" value="<?php echo esc_attr($additional_text_1); ?>">
                                                        </label>
                                                    </div>
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Additional Text for 2-4 Stars:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <?php $additional_text = isset($dapfforwc_form_styles["additional_text"]["rating"]) ? esc_attr($dapfforwc_form_styles["additional_text"]["rating"]) : ''; ?>
                                                            <input type="text" name="dapfforwc_style_options[additional_text][rating]" value="<?php echo esc_attr($additional_text); ?>">
                                                        </label>
                                                    </div>
                                                    <!-- additional text for 5 -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Additional Text for 5 Stars:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <?php $additional_text_5 = isset($dapfforwc_form_styles["additional_text_5"]["rating"]) ? esc_attr($dapfforwc_form_styles["additional_text_5"]["rating"]) : ''; ?>
                                                            <input type="text" name="dapfforwc_style_options[additional_text_5][rating]" value="<?php echo esc_attr($additional_text_5); ?>">
                                                        </label>
                                                    </div>
                                                </div>
                                                <div data-attr-only="price">
                                                    <div class="row" style="padding-top: 16px; gap:20px;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Min input label:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $input_label = isset($dapfforwc_form_styles["input_label"]["price"]["min"]) ? esc_attr($dapfforwc_form_styles["input_label"]["price"]["min"]) : 'Min Price:'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[input_label][price][min]" placeholder="Min Price:" value="<?php echo esc_attr($input_label); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row" style="padding-top: 16px; gap:20px;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Max input label:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $input_label = isset($dapfforwc_form_styles["input_label"]["price"]["max"]) ? esc_attr($dapfforwc_form_styles["input_label"]["price"]["max"]) : 'Max Price:'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[input_label][price][max]" placeholder="Max Price:" value="<?php echo esc_attr($input_label); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div data-attr-only="search reset_btn">
                                                    <div class="row btn_text" style="padding-top: 16px;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Button Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $btntext = isset($dapfforwc_form_styles["btntext"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["btntext"][$dapfforwc_attribute_name]) : ''; ?>
                                                                    <input type="text" name="dapfforwc_style_options[btntext][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($btntext); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- stock status -->
                                                <div data-attr-only="status">
                                                    <div class="row" style="padding-top: 16px; gap:16px; flex-wrap: wrap;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('In Stock Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $in_stock_text = isset($dapfforwc_form_styles["stock_status_text"]["instock"]) ? esc_attr($dapfforwc_form_styles["stock_status_text"]["instock"]) : 'In Stock'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[stock_status_text][instock]" value="<?php echo esc_attr($in_stock_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <!-- out of stock -->
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Out of Stock Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $out_of_stock_text = isset($dapfforwc_form_styles["stock_status_text"]["outofstock"]) ? esc_attr($dapfforwc_form_styles["stock_status_text"]["outofstock"]) : 'Out of Stock'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[stock_status_text][outofstock]" value="<?php echo esc_attr($out_of_stock_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div data-attr-only="sale_status">
                                                    <div class="row" style="padding-top: 16px; gap:16px; flex-wrap: wrap;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('On Sale Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $on_sale_text = isset($dapfforwc_form_styles["sale_status_text"]["onsale"]) ? esc_attr($dapfforwc_form_styles["sale_status_text"]["onsale"]) : 'On Sale'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[sale_status_text][onsale]" value="<?php echo esc_attr($on_sale_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <!-- not on sale -->
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Not On Sale Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $not_on_sale_text = isset($dapfforwc_form_styles["sale_status_text"]["notonsale"]) ? esc_attr($dapfforwc_form_styles["sale_status_text"]["notonsale"]) : 'Not On Sale'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[sale_status_text][notonsale]" value="<?php echo esc_attr($not_on_sale_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div data-attr-only="search sku discount">
                                                    <div class="row" style="padding-top: 16px;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Placeholder:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $placeholder = isset($dapfforwc_form_styles["placeholder"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["placeholder"][$dapfforwc_attribute_name]) : ''; ?>
                                                                    <input type="text" name="dapfforwc_style_options[placeholder][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($placeholder); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div data-attr-only="dimensions">
                                                    <p class="description" style="margin: 12px 0 0; padding: 10px 12px; background: #f8fafc; border-left: 3px solid #764ba2;">
                                                        <?php
                                                        printf(
                                                            /* translators: 1: placeholder token, 2: example placeholder token. */
                                                            esc_html__('Dimension number fields stay empty until a shopper enters a value. Use %1$s or {value} in the min/max placeholder text to show the current catalog bound, for example Min (%2$s).', 'dynamic-ajax-product-filters-for-woocommerce'),
                                                            esc_html('%s'),
                                                            esc_html('%s')
                                                        );
                                                        ?>
                                                    </p>
                                                    <div class="row" style="padding-top: 16px; gap:16px; flex-wrap: wrap;">
                                                        <!-- Length (cm): -->
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Length (cm):', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $dimensions_text = isset($dapfforwc_form_styles["dimensions_text"]["length"]) ? esc_attr($dapfforwc_form_styles["dimensions_text"]["length"]) : 'Length (cm):'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[dimensions_text][length]" value="<?php echo esc_attr($dimensions_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <!-- Width (cm): -->
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Width (cm):', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $dimensions_text = isset($dapfforwc_form_styles["dimensions_text"]["width"]) ? esc_attr($dapfforwc_form_styles["dimensions_text"]["width"]) : 'Width (cm):'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[dimensions_text][width]" value="<?php echo esc_attr($dimensions_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <!-- Height (cm): -->
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Height (cm):', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $dimensions_text = isset($dapfforwc_form_styles["dimensions_text"]["height"]) ? esc_attr($dapfforwc_form_styles["dimensions_text"]["height"]) : 'Height (cm):'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[dimensions_text][height]" value="<?php echo esc_attr($dimensions_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <!-- Weight (kg): -->
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Weight (kg):', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $dimensions_text = isset($dapfforwc_form_styles["dimensions_text"]["weight"]) ? esc_attr($dapfforwc_form_styles["dimensions_text"]["weight"]) : 'Weight (kg):'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[dimensions_text][weight]" value="<?php echo esc_attr($dimensions_text); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row" style="padding-top: 16px; gap:16px; flex-wrap: wrap;">
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Min Placeholder:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $dimensions_placeholder = isset($dapfforwc_form_styles["dimensions_placeholder"]["min"]) ? esc_attr($dapfforwc_form_styles["dimensions_placeholder"]["min"]) : 'Min (%s)'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[dimensions_placeholder][min]" placeholder="Min (%s)" value="<?php echo esc_attr($dimensions_placeholder); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="setting-item">
                                                                <p><strong><?php esc_html_e('Max Placeholder:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                                <label>
                                                                    <?php $dimensions_placeholder = isset($dapfforwc_form_styles["dimensions_placeholder"]["max"]) ? esc_attr($dapfforwc_form_styles["dimensions_placeholder"]["max"]) : 'Max (%s)'; ?>
                                                                    <input type="text" name="dapfforwc_style_options[dimensions_placeholder][max]" placeholder="Max (%s)" value="<?php echo esc_attr($dimensions_placeholder); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>


                                                </div>
                                                <?php
                                                $order_by = isset($dapfforwc_form_styles[$dapfforwc_attribute_name]['order_by']) ? esc_attr($dapfforwc_form_styles[$dapfforwc_attribute_name]['order_by']) : 'default';
                                                $order_direction = isset($dapfforwc_form_styles[$dapfforwc_attribute_name]['order_direction']) ? esc_attr($dapfforwc_form_styles[$dapfforwc_attribute_name]['order_direction']) : 'asc';
                                                ?>
                                                <div data-attr-exclude="price rating search status sale_status dimensions sku discount date_filter reset_btn authors">
                                                    <!-- Order By Setting -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Order Values/Terms By:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <select name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][order_by]">
                                                                <option value="default" <?php selected($order_by, 'default'); ?>>
                                                                    <?php esc_html_e('Default', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="alpha" <?php selected($order_by, 'alpha'); ?>>
                                                                    <?php esc_html_e('Alphabetical', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="numeric" <?php selected($order_by, 'numeric'); ?>>
                                                                    <?php esc_html_e('Numeric', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="month_year" <?php selected($order_by, 'month_year'); ?>>
                                                                    <?php esc_html_e('Month & Year', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="menu_order" data-menu-order-option="1" <?php selected($order_by, 'menu_order'); ?>>
                                                                    <?php esc_html_e('Menu Order', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="count" <?php selected($order_by, 'count'); ?>>
                                                                    <?php esc_html_e('Product Count', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                            </select>
                                                        </label>
                                                    </div>

                                                    <!-- Order Direction Setting -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Order Direction:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <select name="dapfforwc_style_options[<?php echo esc_attr($dapfforwc_attribute_name); ?>][order_direction]">
                                                                <option value="asc" <?php selected($order_direction, 'asc'); ?>>
                                                                    <?php esc_html_e('Ascending', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="desc" <?php selected($order_direction, 'desc'); ?>>
                                                                    <?php esc_html_e('Descending', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                            </select>
                                                        </label>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <!-- optional ends -->
                                    <!-- Advanced settings -->
                                    <div class="dapfforwc-advanced-settings-container optional_settings" style="border: 1px solid #764ba2;">
                                        <div class="dapfforwc-advanced-settings-header">
                                            <h4 style="color: #764ba2;"><?php esc_html_e('Advanced Settings', 'dynamic-ajax-product-filters-for-woocommerce'); ?></h4>
                                        </div>
                                        <div class="dapfforwc-advanced-settings-content" style="padding-top: 20px;">
                                            <div class="dapfforwc-advanced-settings-inner">
                                                <div class="setting-item">
                                                    <p><strong><?php esc_html_e('CSS Class:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <?php $css_class = isset($dapfforwc_form_styles["css_class"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["css_class"][$dapfforwc_attribute_name]) : ''; ?>
                                                        <input type="text" name="dapfforwc_style_options[css_class][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($css_class); ?>">
                                                    </label>
                                                </div>
                                                <!-- Condition Management -->
                                                <div data-attr-exclude="search price rating dimensions sku discount date_filter reset_btn">
                                                    <!-- Operator -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Operator:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <?php $operator = isset($dapfforwc_form_styles["operator"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["operator"][$dapfforwc_attribute_name]) : 'OR'; ?>
                                                            <select name="dapfforwc_style_options[operator][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                                                <option value="AND" <?php selected($operator, 'AND'); ?>>
                                                                    <?php esc_html_e('AND', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="OR" <?php selected($operator, 'OR'); ?>>
                                                                    <?php esc_html_e('OR', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                            </select>
                                                        </label>
                                                    </div>

                                                    <!-- Include/Exclude terms use select with multiple from filters -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Include/Exclude Terms From Filters:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <!-- terms select -->
                                                        <div class="terms-include-exclude" style="display: flex; gap: 10px; align-items: center;">
                                                            <label>
                                                                <?php
                                                                $terms_selected = isset($dapfforwc_form_styles["terms"][$dapfforwc_attribute_name]) ? $dapfforwc_form_styles["terms"][$dapfforwc_attribute_name] : array();
                                                                ?>
                                                                <select class="plugincy_select2" name="dapfforwc_style_options[terms][<?php echo esc_attr($dapfforwc_attribute_name); ?>][]" multiple style="width: 200px; height: 100px;" data-placeholder="<?php esc_attr_e('Select terms', 'dynamic-ajax-product-filters-for-woocommerce'); ?>">
                                                                    <?php foreach ($dapfforwc_terms as $term) :
                                                                        $value = dapfforwc_get_term_style_storage_key($term);
                                                                        $title = (string) dapfforwc_get_term_item_field($term, 'name', '');
                                                                        if ($value === '') {
                                                                            continue;
                                                                        }
                                                                    ?>
                                                                        <option value="<?php echo esc_attr($value); ?>" <?php selected(dapfforwc_term_matches_stored_filter_value($term, $terms_selected, $dapfforwc_attribute_name)); ?>>
                                                                            <?php echo esc_html($title); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </label>
                                                            <label>
                                                                <?php $include_exclude = isset($dapfforwc_form_styles["include_exclude"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["include_exclude"][$dapfforwc_attribute_name]) : 'none'; ?>
                                                                <select name="dapfforwc_style_options[include_exclude][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                                                    <option value="none" <?php selected($include_exclude, 'none'); ?>>
                                                                        <?php esc_html_e('None', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="include" <?php selected($include_exclude, 'include'); ?>>
                                                                        <?php esc_html_e('Include', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="exclude" <?php selected($include_exclude, 'exclude'); ?>>
                                                                        <?php esc_html_e('Exclude', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                </select>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <!-- always display all terms -->

                                                    <!-- <div class="setting-item">
                                                <p><strong><?php //esc_html_e('Always Display All Terms:', 'dynamic-ajax-product-filters-for-woocommerce'); 
                                                            ?></strong></p>
                                                <label>
                                                    <input type="checkbox" name="dapfforwc_style_options[always_display_all_terms][<?php //echo esc_attr($dapfforwc_attribute_name); 
                                                                                                                                    ?>]" value="yes"
                                                        <?php //checked($dapfforwc_form_styles["always_display_all_terms"][$dapfforwc_attribute_name] ?? '', 'yes'); 
                                                        ?>>
                                                    <?php //esc_html_e('Always Display All Terms', 'dynamic-ajax-product-filters-for-woocommerce'); 
                                                    ?>
                                                </label>
                                            </div> -->

                                                    <!-- enable/disable Terms Search -->
                                                    <div class="search_settings" style="display: <?php echo $dapfforwc_sub_option === 'select' || $dapfforwc_sub_option === 'pluginy_select2' ? 'none' : 'block'; ?> ;">
                                                        <span class="dapfforwc_divider"><span class="dapfforwc_divider_title">Terms Search</span></span>
                                                        <div class="setting-item">
                                                            <p><strong><?php esc_html_e('Enable/Disable Terms Search:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                            <label>
                                                                <input type="checkbox" name="dapfforwc_style_options[enable_terms_search][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes" class="enable-terms-search-checkbox"
                                                                    <?php checked($dapfforwc_form_styles["enable_terms_search"][$dapfforwc_attribute_name] ?? '', 'yes'); ?>>
                                                                <?php esc_html_e('Enable Terms Search', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                            </label>
                                                        </div>
                                                        <!-- terms search title & placeholder -->
                                                        <div class="setting-item search-terms-rel" style="display: <?php echo isset($dapfforwc_form_styles["enable_terms_search"][$dapfforwc_attribute_name]) && $dapfforwc_form_styles["enable_terms_search"][$dapfforwc_attribute_name] === 'yes' ? 'block' : 'none'; ?>;">
                                                            <p><strong><?php esc_html_e('Terms Search Title & Placeholder:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                            <div class="terms-search-texts" style="display: flex; gap: 10px; align-items: center;">
                                                                <label>
                                                                    <?php $terms_search_placeholder = isset($dapfforwc_form_styles["terms_search_texts"][$dapfforwc_attribute_name]['placeholder']) ? esc_attr($dapfforwc_form_styles["terms_search_texts"][$dapfforwc_attribute_name]['placeholder']) : ''; ?>
                                                                    <input type="text" name="dapfforwc_style_options[terms_search_texts][<?php echo esc_attr($dapfforwc_attribute_name); ?>][placeholder]" placeholder="<?php esc_attr_e('Placeholder', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" value="<?php echo esc_attr($terms_search_placeholder); ?>">
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <!-- Search position in the title bar/after title/ after all terms -->
                                                        <div class="setting-item search-terms-rel" style="display: <?php echo isset($dapfforwc_form_styles["enable_terms_search"][$dapfforwc_attribute_name]) && $dapfforwc_form_styles["enable_terms_search"][$dapfforwc_attribute_name] === 'yes' ? 'block' : 'none'; ?>;">
                                                            <p><strong><?php esc_html_e('Terms Search Position:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                            <label>
                                                                <?php $terms_search_position = isset($dapfforwc_form_styles["terms_search_position"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["terms_search_position"][$dapfforwc_attribute_name]) : 'after_title'; ?>
                                                                <select name="dapfforwc_style_options[terms_search_position][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                                                    <option value="in_title_bar" disabled>
                                                                        <?php esc_html_e('In Title Bar (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="after_title" <?php selected($terms_search_position, 'after_title'); ?>>
                                                                        <?php esc_html_e('After Title', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="after_all_terms" <?php selected($terms_search_position, 'after_all_terms'); ?>>
                                                                        <?php esc_html_e('After All Terms', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                </select>
                                                            </label>
                                                        </div>

                                                    </div>



                                                    <!-- layout vertical/horizontal if vertical then show no. of columns -->
                                                    <div class="setting-item layout_settings" data-display="flex" style="display: <?php echo $dapfforwc_sub_option === 'select' || $dapfforwc_sub_option === 'pluginy_select2' ? 'none' : 'flex'; ?> ; gap: 20px; align-items: center; flex-wrap: wrap;">
                                                        <span style="width: 100%;display:block;height: 1px;background: #eee;margin: 10px 0;"></span>
                                                        <div>
                                                            <p><strong><?php esc_html_e('Layout:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                            <label>
                                                                <?php $layout = isset($dapfforwc_form_styles["layout"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["layout"][$dapfforwc_attribute_name]) : 'vertical'; ?>
                                                                <select name="dapfforwc_style_options[layout][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                                                    <option value="vertical" <?php selected($layout, 'vertical'); ?>>
                                                                        <?php esc_html_e('Vertical', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="horizontal" <?php selected($layout, 'horizontal'); ?>>
                                                                        <?php esc_html_e('Horizontal', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                </select>
                                                            </label>
                                                        </div>
                                                        <div class="layout-rel" style="display: <?php echo isset($dapfforwc_form_styles["layout"][$dapfforwc_attribute_name]) && $dapfforwc_form_styles["layout"][$dapfforwc_attribute_name] === 'horizontal' ? 'block' : 'none'; ?>;">
                                                            <p><strong><?php esc_html_e('Number of Columns:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                            <label>
                                                                <?php $num_columns = isset($dapfforwc_form_styles["num_columns"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["num_columns"][$dapfforwc_attribute_name]) : '1'; ?>
                                                                <select name="dapfforwc_style_options[num_columns][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                                                    <option value="1" <?php selected($num_columns, '1'); ?>>
                                                                        <?php esc_html_e('1', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="2" <?php selected($num_columns, '2'); ?>>
                                                                        <?php esc_html_e('2', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="3" <?php selected($num_columns, '3'); ?>>
                                                                        <?php esc_html_e('3', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                    <option value="4" <?php selected($num_columns, '4'); ?>>
                                                                        <?php esc_html_e('4', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                    </option>
                                                                </select>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span style="width: 100%;display:block;height: 1px;background: #eee;margin: 10px 0;"></span>
                                                <!-- enable/disable tooltip -->
                                                <div class="setting-item">
                                                    <p><strong><?php esc_html_e('Enable/Disable Tooltip:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <input type="checkbox" name="dapfforwc_style_options[enable_tooltip][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes" class="enable-tooltip-checkbox"
                                                            <?php checked($dapfforwc_form_styles["enable_tooltip"][$dapfforwc_attribute_name] ?? '', 'yes'); ?>>
                                                        <?php esc_html_e('Enable Tooltip', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                    </label>
                                                </div>
                                                <!-- tooltip text -->
                                                <div class="setting-item tooltip-rel" style="display: <?php echo isset($dapfforwc_form_styles["enable_tooltip"][$dapfforwc_attribute_name]) && $dapfforwc_form_styles["enable_tooltip"][$dapfforwc_attribute_name] === 'yes' ? 'block' : 'none'; ?>;">
                                                    <p><strong><?php esc_html_e('Tooltip Text:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <?php $tooltip_text = isset($dapfforwc_form_styles["tooltip_text"][$dapfforwc_attribute_name]) ? esc_attr($dapfforwc_form_styles["tooltip_text"][$dapfforwc_attribute_name]) : ''; ?>
                                                        <input type="text" name="dapfforwc_style_options[tooltip_text][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="<?php echo esc_attr($tooltip_text); ?>">
                                                    </label>
                                                </div>
                                                <span style="width: 100%;display:block;height: 1px;background: #eee;margin: 10px 0;"></span>
                                                <!-- Show in Active/Chips Filter widget -->
                                                <div class="setting-item" style="padding-top: 20px;">
                                                    <p><strong><?php esc_html_e('Show in Active/Chips Filter Widget:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                    <label>
                                                        <select name="dapfforwc_style_options[show_in_active_filters][<?php echo esc_attr($dapfforwc_attribute_name); ?>]">
                                                            <option value="yes" <?php selected($dapfforwc_form_styles["show_in_active_filters"][$dapfforwc_attribute_name] ?? 'yes', 'yes'); ?>>
                                                                <?php esc_html_e('Yes', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                            </option>
                                                            <option value="no" <?php selected($dapfforwc_form_styles["show_in_active_filters"][$dapfforwc_attribute_name] ?? 'yes', 'no'); ?>>
                                                                <?php esc_html_e('No', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                            </option>
                                                        </select>
                                                    </label>
                                                </div>
                                                <!-- Search specific settings -->
                                                <div data-attr-only="search">
                                                    <!-- Enable/Disable Auto Suggestion -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Enable/Disable Auto Suggestion:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label class="pro-only">
                                                            <input disabled type="checkbox" name="dapfforwc_style_options[enable_auto_suggestion][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes">
                                                            <?php esc_html_e('Enable Auto Suggestion (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                        </label>
                                                    </div>
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Search In (Pro):', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label class="pro-only">
                                                            <?php
                                                            $search_behavior = $dapfforwc_form_styles["search_behavior"][$dapfforwc_attribute_name] ?? ['title'];
                                                            if (!is_array($search_behavior)) {
                                                                $search_behavior = [$search_behavior];
                                                            }
                                                            $search_behavior = array_map('sanitize_text_field', $search_behavior);
                                                            ?>
                                                            <select class="plugincy_select2" name="dapfforwc_style_options[search_behavior][<?php echo esc_attr($dapfforwc_attribute_name); ?>][]" multiple style="width: 200px; height: 100px;" data-placeholder="<?php esc_attr_e('Select search behavior', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" disabled>
                                                                <option value="title" selected="selected" disabled>
                                                                    <?php esc_html_e('Title', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="content" disabled>
                                                                    <?php esc_html_e('Content', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="excerpt" disabled>
                                                                    <?php esc_html_e('Excerpt', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="sku" disabled>
                                                                    <?php esc_html_e('SKU', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="tags" disabled>
                                                                    <?php esc_html_e('Tags', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="categories" disabled>
                                                                    <?php esc_html_e('Categories', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="attributes" disabled>
                                                                    <?php esc_html_e('Attributes', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="authors" disabled>
                                                                    <?php esc_html_e('Authors', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                                <option value="brands" disabled>
                                                                    <?php esc_html_e('Brands', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                                </option>
                                                            </select>
                                                        </label>
                                                    </div>
                                                    <!-- enable/disable full match -->
                                                    <div class="setting-item">
                                                        <p><strong><?php esc_html_e('Enable/Disable Full Match:', 'dynamic-ajax-product-filters-for-woocommerce'); ?></strong></p>
                                                        <label>
                                                            <input type="checkbox" name="dapfforwc_style_options[enable_full_match][<?php echo esc_attr($dapfforwc_attribute_name); ?>]" value="yes"
                                                                <?php checked($dapfforwc_form_styles["enable_full_match"][$dapfforwc_attribute_name] ?? '', 'yes'); ?>>
                                                            <?php esc_html_e('Enable Full Match', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e('No attributes found. Please create attributes in WooCommerce first.', 'dynamic-ajax-product-filters-for-woocommerce'); ?></p>
                <?php endif; ?>
                <input type="hidden" name="dapfforwc_style_options_target" id="dapfforwc_style_options_target" value="<?php echo esc_attr($dapfforwc_selected_attribute); ?>">
                <input type="hidden" name="dapfforwc_style_options_json" id="dapfforwc_style_options_json" value="">
                <?php submit_button(); ?>
</form>
</div>
</div>
<?php ob_start(); ?>
    window.dapfforwcStyleData = <?php echo wp_json_encode([
                                    'selectedAttribute' => $dapfforwc_selected_attribute,
                                    'attributeNames' => array_values($dapfforwc_option_names),
                                    'labels' => $dapfforwc_labels,
                                    'options' => $dapfforwc_form_styles,
                                    'subOptions' => $dapfforwc_sub_options,
                                    'terms' => $dapfforwc_terms_map,
                                    'simpleAttributes' => $dapfforwc_simple_attributes,
                                    'menuOrderAttributes' => $dapfforwc_menu_order_attributes,
                                    'imageBase' => $dapfforwc_image_base,
                                    'uploadPlaceholder' => $dapfforwc_upload_placeholder,
                                    'perAttributeGroups' => $dapfforwc_per_attribute_groups,
                                    'selectImageLabel' => esc_html__('Select image', 'dynamic-ajax-product-filters-for-woocommerce'),
                                    'removeImageLabel' => esc_html__('Remove image', 'dynamic-ajax-product-filters-for-woocommerce'),
                                    'isPremium' => false,
                                ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
<?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>
<?php ob_start(); ?>
    (function() {
        const data = window.dapfforwcStyleData;
        if (!data) {
            return;
        }

        const form = document.getElementById('dapfforwc-style-options-form') || document.querySelector('form[action="options.php"]');
        const container = document.getElementById('style-options-container');
        const styleOptions = container ? container.querySelector('.style-options') : null;
        if (!form || !container || !styleOptions) {
            return;
        }

        const attributeSet = new Set(data.attributeNames || []);
        const perAttributeGroups = new Set(data.perAttributeGroups || []);
        const state = JSON.parse(JSON.stringify(data.options || {}));
        const isPremium = !!data.isPremium;
        const imageBase = data.imageBase || '';
        const uploadPlaceholder = data.uploadPlaceholder || '';
        const proOnlyKeys = new Set(['dynamic-rating', 'rating-slider', 'input-price-range', "chart-slider", "price-range-button", "price-range-card", 'color_circle', 'color_value', 'color_swatch_label', 'image_value', 'button_check', 'chips', 'button_chips', 'pill-stepper', 'compact-stepper', 'boxed-stepper', 'stepper', 'icon_only', 'icon_badge', 'icon_chip', 'icon_list', 'icon_card', 'checkbox_dropdown', 'modern_dropdown', 'chip_dropdown', 'dimension_range', 'dimensions-compact-range-card', 'sku_search_button', 'sku_icon_search', 'discount_slider', "date-modern-calendar" , "date-quick-picks" , "date-dual-calendar" , "date-stacked-list"]);

        let currentAttribute = data.selectedAttribute || container.dataset.selectedAttribute || '';
        if (!currentAttribute && data.attributeNames && data.attributeNames.length) {
            currentAttribute = data.attributeNames[0];
        }

        const headerLabel = styleOptions.querySelector('.style-attribute-label');
        const simpleBlock = styleOptions.querySelector('.style-options-simple');
        const fullBlock = styleOptions.querySelector('.style-options-full');

        const defaults = {
            date_filter_texts: {
                all_time_text: 'All Time',
                today_text: 'Today',
                this_week_text: 'This Week',
                this_month_text: 'This Month',
                this_year_text: 'This Year',
            },
            dimensions_text: {
                length: 'Length (cm):',
                width: 'Width (cm):',
                height: 'Height (cm):',
                weight: 'Weight (kg):',
            },
            dimensions_placeholder: {
                min: 'Min (%s)',
                max: 'Max (%s)',
            },
            input_label: {
                price: {
                    min: 'Min Price:',
                    max: 'Max Price:',
                },
            },
            stock_status_text: {
                instock: 'In Stock',
                outofstock: 'Out of Stock',
            },
            sale_status_text: {
                onsale: 'On Sale',
                notonsale: 'Not On Sale',
            },
        };

        const getAllowedTypes = function(attribute) {
            if (attribute === 'price') {
                return ['price'];
            }
            if (attribute === 'dimensions') {
                return ['dimension_input'];
            }
            if (attribute === 'date_filter') {
                return ['date_range'];
            }
            if (attribute === 'discount') {
                return ['simple_input', 'discount_slider'];
            }
            if (attribute === 'sku') {
                return ['simple_input'];
            }
            if (attribute === 'rating') {
                return ['rating'];
            }
            if (attribute === 'search') {
                return ['plugincy_search'];
            }

            const types = Object.keys(data.subOptions || {}).filter(function(type) {
                return type !== 'price' && type !== 'rating' && type !== 'plugincy_search' && type !== 'dimension_input' && type !== 'simple_input' && type !== 'discount_slider' && type !== 'date_range';
            });

            if (attribute === 'brands' || attribute === 'product-category' || attribute === 'tag') {
                return types.filter(function(type) {
                    return type !== 'plugincy_color';
                });
            }

            return types;
        };

        const parseName = function(name) {
            const parts = [];
            name.replace(/\[([^\]]*)\]/g, function(match, key) {
                parts.push(key);
            });
            if (parts.length && parts[parts.length - 1] === '') {
                parts.pop();
            }
            return parts;
        };

        const buildName = function(parts, hasArraySuffix) {
            let rebuilt = 'dapfforwc_style_options';
            parts.forEach(function(part) {
                rebuilt += '[' + part + ']';
            });
            if (hasArraySuffix) {
                rebuilt += '[]';
            }
            return rebuilt;
        };

        const extractAttribute = function(parts) {
            if (!parts || !parts.length) {
                return '';
            }
            if (attributeSet.has(parts[0])) {
                return parts[0];
            }
            if (parts.length > 1 && attributeSet.has(parts[1])) {
                return parts[1];
            }
            return '';
        };

        const dirtyAttributes = new Set();
        let hasGlobalChanges = false;

        const markDirtyFromField = function(field) {
            if (!field || !field.name || field.name.indexOf('dapfforwc_style_options') !== 0) {
                return;
            }
            const parts = parseName(field.name);
            if (!parts.length) {
                return;
            }
            const attr = extractAttribute(parts);
            if (attr) {
                dirtyAttributes.add(attr);
                return;
            }
            hasGlobalChanges = true;
        };

        const syncStateFromField = function(field) {
            if (!field || !field.name || field.name.indexOf('dapfforwc_style_options') !== 0) {
                return;
            }

            const parts = parseName(field.name);
            if (!parts.length) {
                return;
            }

            if (field.type === 'radio') {
                if (field.checked) {
                    setNestedValue(state, parts, field.value);
                }
                return;
            }

            if (field.type === 'checkbox') {
                setNestedValue(state, parts, field.checked ? field.value : '');
                return;
            }

            if (field.tagName === 'SELECT' && field.multiple) {
                const values = Array.from(field.selectedOptions).map(function(option) {
                    return option.value;
                });
                setNestedValue(state, parts, values);
                return;
            }

            setNestedValue(state, parts, field.value);
        };

        const getNestedValue = function(obj, parts) {
            let current = obj;
            for (let i = 0; i < parts.length; i++) {
                if (!current || typeof current !== 'object') {
                    return undefined;
                }
                current = current[parts[i]];
            }
            return current;
        };

        const setNestedValue = function(obj, parts, value) {
            let current = obj;
            for (let i = 0; i < parts.length; i++) {
                const key = parts[i];
                const isLast = i === parts.length - 1;
                if (isLast) {
                    current[key] = value;
                    return;
                }
                if (!current[key] || typeof current[key] !== 'object') {
                    current[key] = {};
                }
                current = current[key];
            }
        };

        const normalizeType = function(attribute, type) {
            if (type && data.subOptions && data.subOptions[type]) {
                return type;
            }
            if (attribute === 'price') {
                return 'price';
            }
            if (attribute === 'dimensions') {
                return 'dimension_input';
            }
            if (attribute === 'date_filter') {
                return 'date_range';
            }
            if (attribute === 'discount') {
                return 'simple_input';
            }
            if (attribute === 'sku') {
                return 'simple_input';
            }
            if (attribute === 'rating') {
                return 'rating';
            }
            if (attribute === 'search') {
                return 'plugincy_search';
            }
            if (attribute === 'brands') {
                return 'image';
            }
            return 'checkbox';
        };

        const normalizeSubOption = function(attribute, type, subOption) {
            const options = (data.subOptions && data.subOptions[type]) ? data.subOptions[type] : {};
            if (subOption && options[subOption]) {
                return subOption;
            }
            if (type === 'price') {
                return 'price';
            }
            if (type === 'dimension_input') {
                return 'dimension_input';
            }
            if (type === 'date_range') {
                return 'date-classic-select';
            }
            if (type === 'simple_input') {
                return 'simple_input';
            }
            if (type === 'discount_slider') {
                return 'discount_slider';
            }
            if (type === 'rating') {
                return 'rating';
            }
            if (type === 'plugincy_search') {
                return 'plugincy_search';
            }
            if (type === 'plugincy_color') {
                return 'plugincy_color';
            }
            if (type === 'image') {
                return 'image';
            }
            if (type === 'dropdown') {
                return 'select';
            }
            return Object.keys(options)[0] || '';
        };

        const getDefaultValue = function(parts) {
            if (!parts || !parts.length) {
                return undefined;
            }

            const attr = extractAttribute(parts);
            const key = parts[0];

            if (key === 'date_filter_texts') {
                return defaults.date_filter_texts[parts[1]] || '';
            }
            if (key === 'dimensions_text') {
                return defaults.dimensions_text[parts[1]] || '';
            }
            if (key === 'dimensions_placeholder') {
                return defaults.dimensions_placeholder[parts[1]] || '';
            }
            if (key === 'input_label') {
                return (defaults.input_label[parts[1]] || {})[parts[2]] || '';
            }
            if (key === 'stock_status_text') {
                return defaults.stock_status_text[parts[1]] || '';
            }
            if (key === 'sale_status_text') {
                return defaults.sale_status_text[parts[1]] || '';
            }
            if (key === 'show_in_active_filters') {
                return 'yes';
            }
            if (key === 'show_widget_title') {
                return 'yes';
            }
            if (key === 'max_height') {
                return 0;
            }
            if (key === 'order_by') {
                return 'default';
            }
            if (key === 'order_direction') {
                return 'asc';
            }
            if (key === 'include_exclude') {
                return 'none';
            }
            if (key === 'operator') {
                return 'OR';
            }
            if (key === 'terms_search_position') {
                return 'after_title';
            }
            if (key === 'layout') {
                return 'vertical';
            }
            if (key === 'num_columns') {
                return '1';
            }
            if (key === 'show_apply_reset_on') {
                return 'separate';
            }
            if (key === 'apply_behavior') {
                return 'only_apply';
            }
            if (key === 'search_behavior') {
                return ['title'];
            }

            if (attr && parts[0] === attr) {
                const attrKey = parts[1] || '';
                if (attrKey === 'type') {
                    return normalizeType(attr);
                }
                if (attrKey === 'sub_option') {
                    return normalizeSubOption(attr, normalizeType(attr), '');
                }
                if (attrKey === 'minimize' && parts[2] === 'type') {
                    return 'arrow';
                }
                if (attrKey === 'hierarchical' && parts[2] === 'type') {
                    return 'disabled';
                }
                if (attrKey === 'show_widget_title') {
                    return 'yes';
                }
                if (attrKey === 'order_by') {
                    return 'default';
                }
                if (attrKey === 'order_direction') {
                    return 'asc';
                }
                if (attrKey === 'single_selection') {
                    return '';
                }
                if (attrKey === 'show_product_count') {
                    return '';
                }
            }

            return '';
        };

        const normalizeAttrToken = function(value) {
            return String(value || '').trim().toLowerCase();
        };

        const normalizeAttributeName = function(value) {
            const normalized = normalizeAttrToken(value);
            if (normalized === 'plugincy_search') {
                return 'search';
            }
            return normalized;
        };

        const parseAttrTokens = function(value) {
            if (!value) {
                return [];
            }
            return String(value)
                .split(/[\s,]+/)
                .map(normalizeAttrToken)
                .filter(Boolean);
        };

        const isAttrAllowed = function(section, attribute) {
            if (!section) {
                return false;
            }

            const attr = normalizeAttributeName(attribute);
            const only = parseAttrTokens(section.dataset.attrOnly);
            const exclude = parseAttrTokens(section.dataset.attrExclude);
            if (only.length) {
                return only.includes(attr);
            }
            if (exclude.length) {
                return !exclude.includes(attr);
            }
            return true;
        };

        const hideExcludedSections = function(attribute) {
            const attr = normalizeAttributeName(attribute);
            styleOptions.querySelectorAll('[data-attr-only], [data-attr-exclude]').forEach(function(section) {
                if (!isAttrAllowed(section, attr)) {
                    setSectionVisibility(section, false);
                }
            });
        };

        const setSectionVisibility = function(section, show) {
            if (!section) {
                return;
            }

            const defaultDisplay = section.dataset.display || (section.classList.contains('layout_settings') ? 'flex' : 'block');
            section.style.display = show ? defaultDisplay : 'none';
            const isVisible = show && section.offsetParent !== null;
            section.querySelectorAll('input, select, textarea').forEach(function(input) {
                input.disabled = !isVisible;
            });
        };

        const updateHeader = function(attribute) {
            if (headerLabel && data.labels && data.labels[attribute]) {
                headerLabel.textContent = data.labels[attribute];
            }
        };

        const updateAttributeNames = function(oldAttr, newAttr) {
            styleOptions.dataset.currentAttribute = newAttr;
            container.dataset.selectedAttribute = newAttr;
            styleOptions.id = 'options-' + newAttr;

            styleOptions.querySelectorAll('[name]').forEach(function(el) {
                if (!el.name || el.name.indexOf('dapfforwc_style_options') !== 0) {
                    return;
                }
                const hasArraySuffix = /\[\]$/.test(el.name);
                const parts = parseName(el.name);
                if (!parts.length) {
                    return;
                }

                let changed = false;
                if (parts[0] === oldAttr) {
                    parts[0] = newAttr;
                    changed = true;
                } else if (parts.length > 1 && parts[1] === oldAttr && perAttributeGroups.has(parts[0])) {
                    parts[1] = newAttr;
                    changed = true;
                }

                if (changed) {
                    el.name = buildName(parts, hasArraySuffix);
                }
            });

            const applyResetSelect = styleOptions.querySelector('[id^="show_apply_reset_on_"]');
            if (applyResetSelect) {
                applyResetSelect.id = 'show_apply_reset_on_' + newAttr;
            }

            styleOptions.querySelectorAll('label[for^="show_apply_reset_on_"]').forEach(function(label) {
                label.setAttribute('for', 'show_apply_reset_on_' + newAttr);
            });
        };

        const updateAttributeVisibility = function(attribute) {
            styleOptions.querySelectorAll('[data-attr-only]').forEach(function(section) {
                setSectionVisibility(section, isAttrAllowed(section, attribute));
            });
            styleOptions.querySelectorAll('[data-attr-exclude]').forEach(function(section) {
                setSectionVisibility(section, isAttrAllowed(section, attribute));
            });

            if (simpleBlock && fullBlock) {
                const isSimple = (data.simpleAttributes || []).includes(attribute);
                setSectionVisibility(simpleBlock, isSimple);
                setSectionVisibility(fullBlock, !isSimple);
            }
        };

        const updateActiveLabels = function(containerEl, selectedValue) {
            if (!containerEl) {
                return;
            }
            containerEl.querySelectorAll('label').forEach(function(label) {
                const input = label.querySelector('input[type="radio"]');
                const active = input && input.value === selectedValue;
                label.classList.toggle('active', active);
                const marker = label.querySelector('span.active');
                if (marker) {
                    marker.style.display = active ? 'block' : 'none';
                }
            });
        };

        const renderPrimaryOptions = function(attribute) {
            const containerEl = styleOptions.querySelector('.primary_options');
            if (!containerEl) {
                return;
            }

            containerEl.innerHTML = '';
            const allowedTypes = getAllowedTypes(attribute);
            const selectedType = normalizeType(attribute, state[attribute] ? state[attribute].type : '');
            state[attribute] = state[attribute] || {};
            state[attribute].type = selectedType;

            const fragment = document.createDocumentFragment();
            allowedTypes.forEach(function(type) {
                const label = document.createElement('label');
                label.className = type + (selectedType === type ? ' active' : '');

                const marker = document.createElement('span');
                marker.className = 'active';
                marker.style.display = 'none';
                label.appendChild(marker);

                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'dapfforwc_style_options[' + attribute + '][type]';
                input.value = type;
                input.dataset.type = type;
                input.checked = selectedType === type;

                const img = document.createElement('img');
                img.src = imageBase + type + '.png';
                img.alt = type;

                label.appendChild(input);
                label.appendChild(img);
                fragment.appendChild(label);
            });

            containerEl.appendChild(fragment);
            updateActiveLabels(containerEl, selectedType);
        };

        const renderSubOptions = function(attribute) {
            const containerEl = styleOptions.querySelector('.dynamic-sub-options');
            if (!containerEl) {
                return;
            }

            const selectedType = normalizeType(attribute, state[attribute] ? state[attribute].type : '');
            const options = (data.subOptions && data.subOptions[selectedType]) ? data.subOptions[selectedType] : {};
            const selectedSubOption = normalizeSubOption(attribute, selectedType, state[attribute] ? state[attribute].sub_option : '');
            state[attribute] = state[attribute] || {};
            state[attribute].sub_option = selectedSubOption;

            containerEl.innerHTML = '';
            const fragment = document.createDocumentFragment();

            Object.keys(options).forEach(function(key) {
                const label = document.createElement('label');
                const isPro = proOnlyKeys.has(key);
                const isActive = selectedSubOption === key;
                label.className = key + (isActive ? ' active' : '') + (!isPremium && isPro ? ' pro-only' : '');

                const marker = document.createElement('span');
                marker.className = 'active';
                marker.style.display = 'none';
                label.appendChild(marker);

                const input = document.createElement('input');
                input.type = 'radio';
                input.className = 'optionselect';
                input.value = key;
                input.name = (!isPremium && isPro) ? ('_pro[' + attribute + '][sub_option]') : ('dapfforwc_style_options[' + attribute + '][sub_option]');
                input.checked = isActive;
                if (!isPremium && isPro) {
                    input.disabled = true;
                }

                const img = document.createElement('img');
                img.src = imageBase + key + '.png';
                img.alt = options[key] || key;

                label.appendChild(input);
                label.appendChild(img);
                fragment.appendChild(label);
            });

            containerEl.appendChild(fragment);
            updateActiveLabels(containerEl, selectedSubOption);
        };

        const refreshSelect2 = function(select) {
            if (!select || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }
            const $select = window.jQuery(select);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                placeholder: function() {
                    return $select.data('placeholder');
                },
                width: 'resolve',
                allowClear: true,
            });
        };

        const sanitizeFieldId = function(value) {
            return String(value || '').replace(/[^a-zA-Z0-9_-]/g, '-');
        };

        const getTermStyleKey = function(term) {
            if (!term) {
                return '';
            }

            if (term.style_key !== undefined && term.style_key !== null && String(term.style_key) !== '') {
                return String(term.style_key);
            }

            if (term.term_id !== undefined && term.term_id !== null && String(term.term_id) !== '' && String(term.term_id) !== '0') {
                return String(term.term_id);
            }

            return term.slug ? String(term.slug) : '';
        };

        const getTermLegacyKeys = function(term) {
            const keys = [];
            const addKey = function(key) {
                if (key !== undefined && key !== null && String(key) !== '' && keys.indexOf(String(key)) === -1) {
                    keys.push(String(key));
                }
            };

            addKey(getTermStyleKey(term));
            addKey(term && term.slug);
            addKey(term && term.source_slug);

            return keys;
        };

        const getTermStoredValue = function(collection, term, fallbackValue) {
            if (!collection || typeof collection !== 'object') {
                return fallbackValue;
            }

            const keys = getTermLegacyKeys(term);
            for (let index = 0; index < keys.length; index++) {
                if (Object.prototype.hasOwnProperty.call(collection, keys[index])) {
                    return collection[keys[index]];
                }
            }

            return fallbackValue;
        };

        const isTermSelected = function(term, selectedValues) {
            const keys = getTermLegacyKeys(term);
            for (let index = 0; index < keys.length; index++) {
                if (selectedValues.indexOf(keys[index]) !== -1) {
                    return true;
                }
            }

            return false;
        };

        const renderTerms = function(attribute) {
            const terms = (data.terms && data.terms[attribute]) ? data.terms[attribute] : [];
            const termsSelect = styleOptions.querySelector('select[name^="dapfforwc_style_options[terms]"]');
            if (termsSelect) {
                const selected = getNestedValue(state, ['terms', attribute]) || [];
                const selectedValues = Array.isArray(selected) ? selected : (selected ? [selected] : []);
                termsSelect.innerHTML = '';
                terms.forEach(function(term) {
                    const styleKey = getTermStyleKey(term);
                    if (!styleKey) {
                        return;
                    }
                    const option = document.createElement('option');
                    option.value = styleKey;
                    option.textContent = term.name || term.slug;
                    option.selected = isTermSelected(term, selectedValues);
                    termsSelect.appendChild(option);
                });
                refreshSelect2(termsSelect);
            }

            const advanced = styleOptions.querySelector('.advanced-options');
            if (!advanced) {
                return;
            }

            const noTermsMessage = advanced.querySelector('.no-terms-message');
            if (noTermsMessage) {
                noTermsMessage.style.display = terms.length ? 'none' : 'block';
            }

            const colorContainer = advanced.querySelector('.color-options');
            const imageContainer = advanced.querySelector('.image-options');
            if (!colorContainer || !imageContainer) {
                return;
            }

            colorContainer.innerHTML = '';
            imageContainer.innerHTML = '';

            const colors = (state[attribute] && state[attribute].colors) ? state[attribute].colors : {};
            const images = (state[attribute] && state[attribute].images) ? state[attribute].images : {};

            const selectImageLabel = data.selectImageLabel || 'Select image';
            const removeImageLabel = data.removeImageLabel || 'Remove image';
            const uploadSvgIcon = '<svg class="edit-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" /></svg>';
            const removeSvgIcon = '<svg class="trash-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3h6l1 2h5v2H3V5h5l1-2zm1 6h2v8h-2V9zm4 0h2v8h-2V9zM7 9h2v8H7V9zm1 12c-1.1 0-2-.9-2-2V8h12v11c0 1.1-.9 2-2 2H8z" /></svg>';
            const normalizeHexColor = function(value, fallback) {
                const fallbackValue = /^#[0-9a-fA-F]{6}$/.test(fallback || '') ? fallback : '#000000';
                const normalized = (value || '').toString().trim();

                if (/^#[0-9a-fA-F]{6}$/.test(normalized)) {
                    return normalized.toLowerCase();
                }

                if (/^[0-9a-fA-F]{6}$/.test(normalized)) {
                    return '#' + normalized.toLowerCase();
                }

                return fallbackValue;
            };

            terms.forEach(function(term) {
                const slug = term.slug;
                const styleKey = getTermStyleKey(term);
                const name = term.name || term.slug;
                if (!slug || !styleKey) {
                    return;
                }
                const rawColorValue = getTermStoredValue(colors, term, term.default_color || '#000000');
                const colorValue = normalizeHexColor(rawColorValue, '#000000');
                const storedImageValue = getTermStoredValue(images, term, '');
                const defaultImageValue = term.default_image || '';
                const termId = sanitizeFieldId(attribute + '-' + styleKey);

                const colorItem = document.createElement('div');
                colorItem.className = 'term-option';
                colorItem.innerHTML = '<label for="color-' + termId + '"><strong>' + name + '</strong></label>' +
                    '<input type="color" id="color-' + termId + '" name="dapfforwc_style_options[' + attribute + '][colors][' + styleKey + ']" value="' + colorValue + '">';
                colorContainer.appendChild(colorItem);

                const imageItem = document.createElement('div');
                imageItem.className = 'term-option' + (storedImageValue ? ' has-custom-image' : '');
                imageItem.setAttribute('data-default-image', defaultImageValue);
                imageItem.setAttribute('data-placeholder-image', uploadPlaceholder);
                const imageSrc = storedImageValue || defaultImageValue || uploadPlaceholder;
                imageItem.innerHTML = '<img src="' + imageSrc + '" style="max-width: 170px;">' +
                    '<label for="image-' + termId + '"><strong>' + name + '</strong></label>' +
                    '<input type="hidden" id="image-' + termId + '" name="dapfforwc_style_options[' + attribute + '][images][' + styleKey + ']" value="' + storedImageValue + '" placeholder="Image URL">' +
                    '<div class="term-option-actions">' +
                    '<button type="button" class="upload-image-button" title="' + selectImageLabel + '" aria-label="' + selectImageLabel + '">' + uploadSvgIcon + '<span class="screen-reader-text">' + selectImageLabel + '</span></button>' +
                    '<button type="button" class="remove-image-button' + (storedImageValue ? '' : ' is-hidden') + '" title="' + removeImageLabel + '" aria-label="' + removeImageLabel + '">' + removeSvgIcon + '<span class="screen-reader-text">' + removeImageLabel + '</span></button>' +
                    '</div>';
                imageContainer.appendChild(imageItem);
            });
        };

        const updateAdvancedOptions = function(attribute) {
            const advanced = styleOptions.querySelector('.advanced-options');
            if (!advanced) {
                return;
            }

            const type = state[attribute] ? state[attribute].type : '';
            const canShow = isAttrAllowed(advanced, attribute) && (type === 'plugincy_color' || type === 'image');
            setSectionVisibility(advanced, canShow);

            if (!canShow) {
                return;
            }

            const colorSection = advanced.querySelector('.plugincy_color');
            const imageSection = advanced.querySelector('.image');
            setSectionVisibility(colorSection, type === 'plugincy_color');
            setSectionVisibility(imageSection, type === 'image');
        };

        const updateTypeDependentUI = function(attribute) {
            const type = state[attribute] ? state[attribute].type : '';
            const singleSelection = styleOptions.querySelector('.setting-item.single-selection');
            const searchSettings = styleOptions.querySelector('.search_settings');
            const layoutSettings = styleOptions.querySelector('.layout_settings');

            if (type === 'plugincy_color' || type === 'image') {
                if (singleSelection && isAttrAllowed(singleSelection, attribute)) {
                    setSectionVisibility(singleSelection, true);
                }
                if (searchSettings && isAttrAllowed(searchSettings, attribute)) {
                    setSectionVisibility(searchSettings, true);
                }
                if (layoutSettings && isAttrAllowed(layoutSettings, attribute)) {
                    setSectionVisibility(layoutSettings, true);
                }
                return;
            }

            if (type === 'dropdown') {
                if (singleSelection) {
                    setSectionVisibility(singleSelection, false);
                }
                if (searchSettings) {
                    setSectionVisibility(searchSettings, false);
                }
                if (layoutSettings) {
                    setSectionVisibility(layoutSettings, false);
                }
                return;
            }

            if (singleSelection && isAttrAllowed(singleSelection, attribute)) {
                setSectionVisibility(singleSelection, true);
            }
            if (searchSettings && isAttrAllowed(searchSettings, attribute)) {
                setSectionVisibility(searchSettings, true);
            }
            if (layoutSettings && isAttrAllowed(layoutSettings, attribute)) {
                setSectionVisibility(layoutSettings, true);
            }
        };

        const updateSubOptionDependent = function(attribute) {
            const subOption = state[attribute] ? state[attribute].sub_option : '';
            const isSelectSubOption = subOption === 'select';
            const isSelect2SubOption = subOption === 'pluginy_select2';
            const isSelectType = isSelectSubOption || isSelect2SubOption;
            const singleSelection = styleOptions.querySelector('.setting-item.single-selection');
            const singleSelectionCheckbox = singleSelection ? singleSelection.querySelector('input[type="checkbox"]') : null;

            if (singleSelection && isAttrAllowed(singleSelection, attribute)) {
                const display = singleSelection.dataset.display || 'block';
                if (isSelectSubOption) {
                    if (singleSelectionCheckbox) {
                        singleSelectionCheckbox.checked = true;
                        if (state[attribute]) {
                            state[attribute].single_selection = 'yes';
                        }
                        singleSelectionCheckbox.disabled = false;
                    }
                    singleSelection.style.display = 'none';
                } else {
                    if (singleSelectionCheckbox) {
                        const storedValue = state[attribute] ? state[attribute].single_selection : '';
                        singleSelectionCheckbox.checked = String(storedValue) === 'yes';
                        singleSelectionCheckbox.disabled = false;
                    }
                    singleSelection.style.display = display;
                }
                singleSelection.querySelectorAll('input, select, textarea').forEach(function(input) {
                    input.disabled = false;
                });
            }

            const searchSettings = styleOptions.querySelector('.search_settings');
            const layoutSettings = styleOptions.querySelector('.layout_settings');
            if (searchSettings && isAttrAllowed(searchSettings, attribute)) {
                setSectionVisibility(searchSettings, !isSelectType);
            }
            if (layoutSettings && isAttrAllowed(layoutSettings, attribute)) {
                setSectionVisibility(layoutSettings, !isSelectType);
            }

            const ratingText = styleOptions.querySelector('.additional_txt_rating');
            if (ratingText && attribute === 'rating') {
                setSectionVisibility(ratingText, subOption === 'rating-text');
            }

            if (attribute === 'search') {
                const searchButtonWrapper = styleOptions.querySelector('[data-attr-only~="search"][data-attr-only~="reset_btn"]');
                if (searchButtonWrapper) {
                    setSectionVisibility(searchButtonWrapper, subOption !== 'icon_search');
                }
            }
        };

        const updateTermsSearchVisibility = function() {
            const checkbox = styleOptions.querySelector('.enable-terms-search-checkbox');
            const sections = styleOptions.querySelectorAll('.search-terms-rel');
            const show = checkbox && checkbox.checked;
            sections.forEach(function(section) {
                setSectionVisibility(section, !!show);
            });
        };

        const updateLayoutVisibility = function() {
            const layoutSelect = styleOptions.querySelector('select[name^="dapfforwc_style_options[layout]"]');
            const layoutRel = styleOptions.querySelector('.layout-rel');
            if (!layoutSelect || !layoutRel) {
                return;
            }
            setSectionVisibility(layoutRel, layoutSelect.value === 'horizontal');
        };

        const updateTooltipVisibility = function() {
            const checkbox = styleOptions.querySelector('.enable-tooltip-checkbox');
            const tooltipRel = styleOptions.querySelector('.tooltip-rel');
            if (!checkbox || !tooltipRel) {
                return;
            }
            setSectionVisibility(tooltipRel, checkbox.checked);
        };

        const updateApplyResetVisibility = function() {
            const resetWrapper = styleOptions.querySelector('[data-attr-only~="reset_btn"]');
            if (!resetWrapper || resetWrapper.offsetParent === null) {
                return;
            }

            const showApply = resetWrapper.querySelector('input[name^="dapfforwc_style_options"][name*="[show_apply_button]"]');
            const showReset = resetWrapper.querySelector('input[name^="dapfforwc_style_options"][name*="[show_reset_button]"]');
            const applyText = resetWrapper.querySelector('input[name^="dapfforwc_style_options"][name*="[applybtntext]"]');
            const applyBehavior = resetWrapper.querySelector('select[name^="dapfforwc_style_options"][name*="[apply_behavior]"]');
            const resetText = resetWrapper.querySelector('input[name^="dapfforwc_style_options"][name*="[btntext]"]');
            const applyResetOn = resetWrapper.querySelector('select[name^="dapfforwc_style_options"][name*="[show_apply_reset_on]"]');

            const showApplyChecked = !!(showApply && showApply.checked);
            const showResetChecked = !!(showReset && showReset.checked);

            if (applyText) {
                applyText.disabled = !showApplyChecked;
            }
            if (applyBehavior) {
                applyBehavior.disabled = !showApplyChecked;
            }
            if (resetText) {
                resetText.disabled = !showResetChecked;
            }
            if (applyResetOn) {
                applyResetOn.disabled = !(showApplyChecked || showResetChecked);
            }
        };

        const updateOrderingVisibility = function(attribute) {
            const orderSelect = styleOptions.querySelector('select[name^="dapfforwc_style_options"][name*="[order_by]"]');
            if (!orderSelect) {
                return;
            }
            const menuOption = orderSelect.querySelector('option[data-menu-order-option]');
            const allowMenuOrder = Array.isArray(data.menuOrderAttributes) && data.menuOrderAttributes.indexOf(attribute) !== -1;
            if (menuOption) {
                menuOption.hidden = !allowMenuOrder;
            }
            if (!allowMenuOrder && orderSelect.value === 'menu_order') {
                orderSelect.value = 'default';
                if (state[attribute]) {
                    state[attribute].order_by = 'default';
                }
            }
        };

        const syncFormValues = function() {
            styleOptions.querySelectorAll('[name^="dapfforwc_style_options"]').forEach(function(field) {
                if (field.disabled) {
                    return;
                }
                const parts = parseName(field.name);
                const value = getNestedValue(state, parts);
                const fallback = value === undefined ? getDefaultValue(parts) : value;

                if (field.type === 'checkbox') {
                    const expected = fallback === undefined ? '' : fallback;
                    field.checked = Array.isArray(expected) ? expected.indexOf(field.value) !== -1 : String(expected) === field.value;
                    return;
                }

                if (field.type === 'radio') {
                    field.checked = String(fallback) === field.value;
                    return;
                }

                if (field.tagName === 'SELECT' && field.multiple) {
                    const values = Array.isArray(fallback) ? fallback : (fallback ? [fallback] : []);
                    Array.from(field.options).forEach(function(option) {
                        option.selected = values.indexOf(option.value) !== -1;
                    });
                    refreshSelect2(field);
                    return;
                }

                if (field.tagName === 'SELECT') {
                    if (fallback !== undefined && fallback !== '') {
                        field.value = fallback;
                    } else if (field.options.length) {
                        field.value = field.options[0].value;
                    }
                    return;
                }

                field.value = fallback !== undefined ? fallback : '';
            });
        };

        const captureCurrentState = function() {
            const radioGroups = {};
            styleOptions.querySelectorAll('[name^="dapfforwc_style_options"]').forEach(function(field) {
                if (field.disabled) {
                    return;
                }
                const name = field.name;
                const parts = parseName(name);

                if (field.type === 'radio') {
                    if (!Object.prototype.hasOwnProperty.call(radioGroups, name)) {
                        radioGroups[name] = '';
                    }
                    if (field.checked) {
                        radioGroups[name] = field.value;
                    }
                    return;
                }

                if (field.type === 'checkbox') {
                    setNestedValue(state, parts, field.checked ? field.value : '');
                    return;
                }

                if (field.tagName === 'SELECT' && field.multiple) {
                    const values = Array.from(field.selectedOptions).map(function(option) {
                        return option.value;
                    });
                    setNestedValue(state, parts, values);
                    return;
                }

                setNestedValue(state, parts, field.value);
            });

            Object.keys(radioGroups).forEach(function(name) {
                const parts = parseName(name);
                setNestedValue(state, parts, radioGroups[name]);
            });
        };

        const applyStateForAttribute = function(attribute) {
            state[attribute] = state[attribute] || {};
            state[attribute].type = normalizeType(attribute, state[attribute].type);
            state[attribute].sub_option = normalizeSubOption(attribute, state[attribute].type, state[attribute].sub_option);

            updateAttributeVisibility(attribute);
            renderPrimaryOptions(attribute);
            renderSubOptions(attribute);
            renderTerms(attribute);
            updateAttributeVisibility(attribute);
            syncFormValues();
            updateHeader(attribute);
            updateAdvancedOptions(attribute);
            updateTypeDependentUI(attribute);
            updateSubOptionDependent(attribute);
            updateTermsSearchVisibility();
            updateLayoutVisibility();
            updateTooltipVisibility();
            updateApplyResetVisibility();
            updateOrderingVisibility(attribute);
            hideExcludedSections(attribute);
        };

        const switchAttribute = function(attribute) {
            if (!attribute || attribute === currentAttribute) {
                return;
            }

            captureCurrentState();
            const previousAttribute = currentAttribute;
            currentAttribute = attribute;
            updateAttributeNames(previousAttribute, attribute);
            applyStateForAttribute(attribute);
        };

        window.dapfforwcStyleManager = {
            capture: captureCurrentState,
            getState: function() {
                return state;
            },
            switchAttribute: switchAttribute,
            getDirtyAttributes: function() {
                return Array.from(dirtyAttributes);
            },
            hasGlobalChanges: function() {
                return hasGlobalChanges;
            },
        };

        window.dapfforwcSwitchAttribute = function(attribute) {
            switchAttribute(attribute);
        };

        form.addEventListener('change', function(event) {
            const target = event.target;
            if (!target) {
                return;
            }
            syncStateFromField(target);
            markDirtyFromField(target);

            if (target.matches('.primary_options input[type="radio"]')) {
                state[currentAttribute] = state[currentAttribute] || {};
                state[currentAttribute].type = target.value;
                state[currentAttribute].sub_option = normalizeSubOption(currentAttribute, target.value, state[currentAttribute].sub_option);
                renderSubOptions(currentAttribute);
                updateActiveLabels(styleOptions.querySelector('.primary_options'), target.value);
                updateAdvancedOptions(currentAttribute);
                updateTypeDependentUI(currentAttribute);
                updateSubOptionDependent(currentAttribute);
                updateOrderingVisibility(currentAttribute);
                hideExcludedSections(currentAttribute);
                return;
            }

            if (target.matches('.optionselect')) {
                state[currentAttribute] = state[currentAttribute] || {};
                state[currentAttribute].sub_option = target.value;
                updateActiveLabels(styleOptions.querySelector('.dynamic-sub-options'), target.value);
                updateSubOptionDependent(currentAttribute);
                hideExcludedSections(currentAttribute);
                return;
            }

            if (target.matches('.enable-terms-search-checkbox')) {
                updateTermsSearchVisibility();
                return;
            }

            if (target.matches('select[name^="dapfforwc_style_options[layout]"]')) {
                updateLayoutVisibility();
                return;
            }

            if (target.matches('.enable-tooltip-checkbox')) {
                updateTooltipVisibility();
                return;
            }

            if (target.matches('input[name^="dapfforwc_style_options"][name*="[show_apply_button]"]') ||
                target.matches('input[name^="dapfforwc_style_options"][name*="[show_reset_button]"]')) {
                updateApplyResetVisibility();
            }
        });


        form.addEventListener('input', function(event) {
            const target = event.target;
            if (!target) {
                return;
            }
            syncStateFromField(target);
            markDirtyFromField(target);
        });

        if (currentAttribute) {
            applyStateForAttribute(currentAttribute);
        }
    })();
<?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>
<?php ob_start(); ?>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('dapfforwc-style-options-form') || document.querySelector('form[action="options.php"]');
        if (!form) {
            return;
        }

        const optionPage = form.querySelector('input[name="option_page"]');
        if (!optionPage || optionPage.value !== 'dapfforwc_style_options_group') {
            return;
        }

        const jsonInput = form.querySelector('#dapfforwc_style_options_json');
        const targetInput = form.querySelector('#dapfforwc_style_options_target');
        if (!jsonInput) {
            return;
        }

        const attributeNames = (window.dapfforwcStyleData && Array.isArray(window.dapfforwcStyleData.attributeNames)) ?
            window.dapfforwcStyleData.attributeNames : [];
        const perAttributeGroups = (window.dapfforwcStyleData && Array.isArray(window.dapfforwcStyleData.perAttributeGroups)) ?
            window.dapfforwcStyleData.perAttributeGroups : [];
        const perAttributeGroupSet = new Set(perAttributeGroups);

        const resolveTargetAttribute = function(value) {
            if (typeof value === 'string' && value !== '' && (!attributeNames.length || attributeNames.indexOf(value) !== -1)) {
                return value;
            }

            const container = document.getElementById('style-options-container');
            if (container && container.dataset && container.dataset.selectedAttribute) {
                const selected = container.dataset.selectedAttribute;
                if (!attributeNames.length || attributeNames.indexOf(selected) !== -1) {
                    return selected;
                }
            }

            const styleOptions = container ? container.querySelector('.style-options') : null;
            if (styleOptions && styleOptions.dataset && styleOptions.dataset.currentAttribute) {
                const current = styleOptions.dataset.currentAttribute;
                if (!attributeNames.length || attributeNames.indexOf(current) !== -1) {
                    return current;
                }
            }

            return '';
        };

        const buildPayload = function(options, attributes, includeGlobals) {
            if (!options || typeof options !== 'object') {
                return {};
            }

            const attrList = Array.isArray(attributes) ? attributes.filter(Boolean) : [];
            const shouldIncludeGlobals = !!includeGlobals;

            const payload = {};
            if (attrList.length) {
                attrList.forEach(function(attribute) {
                    if (Object.prototype.hasOwnProperty.call(options, attribute)) {
                        payload[attribute] = options[attribute];
                    }
                });

                perAttributeGroupSet.forEach(function(group) {
                    const groupValues = options[group];
                    if (!groupValues || typeof groupValues !== 'object') {
                        return;
                    }
                    attrList.forEach(function(attribute) {
                        if (Object.prototype.hasOwnProperty.call(groupValues, attribute)) {
                            if (!payload[group] || typeof payload[group] !== 'object') {
                                payload[group] = {};
                            }
                            payload[group][attribute] = groupValues[attribute];
                        }
                    });
                });
            }

            if (shouldIncludeGlobals) {
                Object.keys(options).forEach(function(key) {
                    if (attrList.indexOf(key) !== -1) {
                        return;
                    }
                    if (perAttributeGroupSet.has(key)) {
                        return;
                    }
                    if (attributeNames.indexOf(key) !== -1) {
                        return;
                    }
                    payload[key] = options[key];
                });
            }

            return payload;
        };

        const safeStringify = function(value) {
            try {
                return JSON.stringify(value);
            } catch (error) {
                const seen = new WeakSet();
                return JSON.stringify(value, function(key, val) {
                    if (typeof val === 'object' && val !== null) {
                        if (seen.has(val)) {
                            return;
                        }
                        seen.add(val);
                    }
                    return val;
                });
            }
        };

        const setHiddenValue = function(input, value) {
            if (!input) {
                return;
            }
            const safeValue = typeof value === 'string' ? value : '';
            input.value = safeValue;
            input.setAttribute('value', safeValue);
        };

        if (targetInput) {
            const normalizedTarget = resolveTargetAttribute(targetInput.value);
            if (normalizedTarget) {
                setHiddenValue(targetInput, normalizedTarget);
            }
        }

        if (jsonInput.value === '[object Object]') {
            setHiddenValue(jsonInput, '');
        }

        const setNestedValue = function(target, parts, value) {
            let current = target;
            for (let i = 0; i < parts.length; i++) {
                const key = parts[i];
                const isLast = i === parts.length - 1;

                if (isLast) {
                    if (key === '') {
                        if (Array.isArray(current)) {
                            current.push(value);
                        }
                        return;
                    }

                    if (current[key] === undefined) {
                        current[key] = value;
                    } else if (Array.isArray(current[key])) {
                        current[key].push(value);
                    } else {
                        current[key] = [current[key], value];
                    }
                    return;
                }

                if (key === '') {
                    return;
                }

                const nextKey = parts[i + 1];
                if (!Object.prototype.hasOwnProperty.call(current, key) || typeof current[key] !== 'object' || current[key] === null) {
                    current[key] = nextKey === '' ? [] : {};
                }
                current = current[key];
            }
        };

        const collectLiveFormOptions = function() {
            const formData = new FormData(form);
            const liveOptions = {};

            for (const [name, value] of formData.entries()) {
                if (!name || name === 'dapfforwc_style_options_json' || name === 'dapfforwc_style_options_target') {
                    continue;
                }

                if (!name.startsWith('dapfforwc_style_options')) {
                    continue;
                }

                const parts = [];
                name.replace(/\[([^\]]*)\]/g, function(match, key) {
                    parts.push(key);
                });

                if (!parts.length) {
                    continue;
                }

                setNestedValue(liveOptions, parts, value);
            }

            return liveOptions;
        };

        const mergeOptionValues = function(target, source) {
            const baseTarget = (target && typeof target === 'object') ? target : {};
            if (!source || typeof source !== 'object') {
                return baseTarget;
            }

            Object.keys(source).forEach(function(key) {
                const sourceValue = source[key];
                if (Array.isArray(sourceValue)) {
                    baseTarget[key] = sourceValue.slice();
                    return;
                }

                if (sourceValue && typeof sourceValue === 'object') {
                    const targetValue = baseTarget[key];
                    baseTarget[key] = mergeOptionValues(
                        targetValue && typeof targetValue === 'object' && !Array.isArray(targetValue) ? targetValue : {},
                        sourceValue
                    );
                    return;
                }

                baseTarget[key] = sourceValue;
            });

            return baseTarget;
        };

        form.addEventListener('submit', function() {
            if (window.dapfforwcStyleManager && typeof window.dapfforwcStyleManager.capture === 'function') {
                window.dapfforwcStyleManager.capture();
            }

            let options = {};
            if (window.dapfforwcStyleManager && typeof window.dapfforwcStyleManager.getState === 'function') {
                const stateOptions = window.dapfforwcStyleManager.getState();
                if (stateOptions && typeof stateOptions === 'object') {
                    options = stateOptions;
                }
            }

            if (!options || typeof options !== 'object') {
                options = {};
            }

            const liveOptions = collectLiveFormOptions();
            options = mergeOptionValues(options, liveOptions);

            const currentAttribute = resolveTargetAttribute(targetInput ? targetInput.value : '');

            let dirtyAttributes = [];
            let includeGlobals = true;
            if (window.dapfforwcStyleManager && typeof window.dapfforwcStyleManager.getDirtyAttributes === 'function') {
                const managerDirty = window.dapfforwcStyleManager.getDirtyAttributes();
                managerDirty.forEach(function(attribute) {
                    if (dirtyAttributes.indexOf(attribute) === -1) {
                        dirtyAttributes.push(attribute);
                    }
                });
                includeGlobals = typeof window.dapfforwcStyleManager.hasGlobalChanges === 'function' ?
                    window.dapfforwcStyleManager.hasGlobalChanges() :
                    true;
            }

            if (!dirtyAttributes.length) {
                if (currentAttribute) {
                    dirtyAttributes.push(currentAttribute);
                } else {
                    dirtyAttributes = Object.keys(options).filter(function(key) {
                        return attributeNames.indexOf(key) !== -1;
                    });
                }
            }

            if (currentAttribute && dirtyAttributes.indexOf(currentAttribute) === -1) {
                dirtyAttributes.push(currentAttribute);
            }
            const payloadObject = buildPayload(options, dirtyAttributes, includeGlobals);
            if (payloadObject && Object.keys(payloadObject).length) {
                const payload = safeStringify(payloadObject);
                if (!payload) {
                    return;
                }
                setHiddenValue(jsonInput, payload);
            } else {
                setHiddenValue(jsonInput, '');
            }

            if (targetInput) {
                setHiddenValue(targetInput, currentAttribute);
            }
        });

    });
<?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>
