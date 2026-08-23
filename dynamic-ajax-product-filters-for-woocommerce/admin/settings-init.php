<?php
// settings-init.php

if (!defined('ABSPATH')) {
    exit;
}
function dapfforwc_settings_init()
{
    $default_dapfforwc_options = [
        'show_categories' => "on",
        'show_attributes' => "on",
        'show_tags' => "on",
        'show_custom_taxonomies' => "",
        'show_price_range' => "on",
        'show_rating' => "on",
        'show_search' => "on",
        'show_brand' => "",
        'show_author' => "",
        'show_status' => "",
        'show_onsale' => "",
        'show_featured' => "",
        'show_dimension' => "",
        'show_sku' => "",
        'show_discount' => "",
        'show_date_filter' => "",
        'use_url_filter' => 'query_string',
        'update_filter_options' => "on",
        'show_loader' => "on",
        'pages' => [],
        'loader_html' => '<div id="loader" style="display:none;"></div>',
        'loader_css' => '#loader { width: 56px; height: 56px; border-radius: 50%; -webkit-mask: radial-gradient(farthest-side,#0000 calc(100% - 9px),#000 0); animation: spinner-zp9dbg 1s infinite linear; } @keyframes spinner-zp9dbg { to { transform: rotate(1turn); } }',
        'use_custom_template' => 0,
        'custom_template_code' => '',
        'product_selector' => '.products',
        'pagination_selector' => '.woocommerce-pagination',
        'filters_word_in_permalinks' => 'filters',
    ];
    $dapfforwc_options = get_option('dapfforwc_options', false);
    if ($dapfforwc_options === false || !is_array($dapfforwc_options)) {
        $dapfforwc_options = $default_dapfforwc_options;
        add_option('dapfforwc_options', $dapfforwc_options);
    } else {
        $dapfforwc_options = wp_parse_args($dapfforwc_options, $default_dapfforwc_options);
    }

    register_setting(
        'dapfforwc_options_group',
        'dapfforwc_options',
        'dapfforwc_sanitize_options'
    );
    register_setting(
        'dapfforwc_options_group',
        'dapfforwc_advance_options',
        'dapfforwc_sanitize_advance_options'
    );

    add_settings_section('dapfforwc_section', '', null, 'dapfforwc-admin');

    $fields = [
        'show_categories' => [
            'label' => esc_html__('Show Categories', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show categories in the filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_attributes' => [
            'label' => esc_html__('Show Attributes', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show attributes in the filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_tags' => [
            'label' => esc_html__('Show Tags', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show tags in the filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_custom_taxonomies' => [
            'label' => esc_html__('Show Custom Taxonomies', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show selected custom product taxonomies in the filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_price_range' => [
            'label' => esc_html__('Show Price Range', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show price range filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_rating' => [
            'label' => esc_html__('Show Rating', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show product rating filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_search' => [
            'label' => esc_html__('Show Search', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show search box for products.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_brand' => [
            'label' => esc_html__('Show Brand', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show brand filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_author' => [
            'label' => esc_html__('Show Authors', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show authors filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_status' => [
            'label' => esc_html__('Show Stock Status', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show status filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_onsale' => [
            'label' => esc_html__('Show Sale Status', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show on sale filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_featured' => [
            'label' => esc_html__('Show Featured Products', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show featured product filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_dimension' => [
            'label' => esc_html__('Show Dimensions', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show dimensions filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_sku' => [
            'label' => esc_html__('Show SKU', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show sku filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_discount' => [
            'label' => esc_html__('Show Discount', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show discount filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_date_filter' => [
            'label' => esc_html__('Show Date Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show date filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_custom_fields' => [
            'label' => esc_html__('Show Custom Fields Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show custom fields filter.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'use_url_filter' => [
            'label' => esc_html__('Use URL-Based Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Choose Filter Method', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'update_filter_options' => [
            'label' => esc_html__('Update filter options', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to dynamically update filter options.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        'show_loader' => [
            'label' => esc_html__('Show Loader', 'dynamic-ajax-product-filters-for-woocommerce'),
            'description' => esc_html__('Enable this option to show a loading indicator during AJAX requests.', 'dynamic-ajax-product-filters-for-woocommerce')
        ],
        // 'use_custom_template' => [
        //     'label' => esc_html__('Use Custom Product Template', 'dynamic-ajax-product-filters-for-woocommerce'),
        //     'description' => esc_html__('Enable this option to use a custom product template.', 'dynamic-ajax-product-filters-for-woocommerce')
        // ],
    ];

    foreach ($fields as $key => $field) {
        add_settings_field($key, '<p>' . $field['label'] . '</p><p class="admin-description">' . $field['description'] . '</p>', "dapfforwc_{$key}_render", 'dapfforwc-admin', 'dapfforwc_section');
    }

    // custom code template
    // add_settings_field('custom_template_code', esc_html__('product custom template code', 'dynamic-ajax-product-filters-for-woocommerce'), 'dapfforwc_custom_template_code_render', 'dapfforwc-admin', 'dapfforwc_section');

    $default_style_options = [
        'price' => ['type' => 'price', 'sub_option' => 'price'],
        'rating' => ['type' => 'rating', 'sub_option' => 'rating'],
        'brands' => ['type' => 'brands', 'sub_option' => 'image'],
        'max_height' => ['product-category' => '400', "tag" => '400'],
    ];
    $default_style = get_option('dapfforwc_style_options', false);
    if ($default_style === false || !is_array($default_style)) {
        $default_style = $default_style_options;
        add_option('dapfforwc_style_options', $default_style);
    } else {
        $default_style = wp_parse_args($default_style, $default_style_options);
    }
    // form style register
    register_setting(
        'dapfforwc_style_options_group',
        'dapfforwc_style_options',
        'dapfforwc_sanitize_style_options'
    );

    // Add Form Style section
    add_settings_section(
        'dapfforwc_style_section',
        '<p class="page-title">' . esc_html__('Form Style Configuration', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>',
        function () {
            echo '<p style="padding-bottom: 20px;">' . esc_html__('Customize the appearance and behavior of your filter components with our comprehensive styling options', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>';
        },
        'dapfforwc-style'
    );

    //   advance settings register
    $default_advance_options = [
        'product_selector' => 'ul.products',
        'pagination_selector' => '.woocommerce-pagination',
        'sorting_selector' => 'form.woocommerce-ordering select',
        'result_count_selector' => '.woocommerce-result-count',
        'advanced_pagination_enabled' => '',
        'advanced_pagination_mode' => 'number',
        'advanced_pagination_prev_selector' => '',
        'advanced_pagination_next_selector' => '',
        'advanced_pagination_load_more_selector' => '',
        'advanced_pagination_infinite_scroll_selector' => '',
        'product_shortcode' => 'products',
        'remove_outofStock' => 0,
        'allow_data_share' => "off",
        'sidebar_on_top' => "on",
        'mobile_breakpoint' => 768,
        'default_value_selected' => 0,
        'exclude_attributes' => "",
        'exclude_custom_fields' => "",
        'no_products_text' => 'No products were found matching your selection.',
        'select2_placeholder' => 'Select Options',
    ];
    $Advance_options = get_option('dapfforwc_advance_options', false);
    if ($Advance_options === false || !is_array($Advance_options)) {
        $Advance_options = $default_advance_options;
        add_option('dapfforwc_advance_options', $Advance_options);
    } else {
        $Advance_options = wp_parse_args($Advance_options, $default_advance_options);
    }
    register_setting(
        'dapfforwc_advance_settings',
        'dapfforwc_advance_options',
        'dapfforwc_sanitize_advance_options'
    );
    // Add the "Advance Settings" section
    add_settings_section(
        'dapfforwc_advance_settings_section',
        '<p class="page-title">' . esc_html__('Advanced Settings', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>',
        function () {
            echo '<p style="padding-bottom: 20px; border-bottom: 2px solid #f1f5f9;">' . esc_html__('You can handle advanced settings for your product filters here.', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>';
        },
        'dapfforwc-advance-settings'
    );

    // Add the "Product Selector" field
    add_settings_field(
        'product_selector',
        esc_html__('Product Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
        'dapfforwc_product_selector_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );
    // Add the "Pagination Selector" field
    add_settings_field(
        'pagination_selector',
        esc_html__('Pagination Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
        'dapfforwc_pagination_selector_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );
    add_settings_field(
        'sorting_selector',
        esc_html__('Sorting Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
        'dapfforwc_sorting_selector_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );
    add_settings_field(
        'result_count_selector',
        esc_html__('Result Count Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
        'dapfforwc_result_count_selector_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );
    // Add the "Product shotcode Selector" field
    add_settings_field(
        'product_shortcode',
        esc_html__('Product Shortcode Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
        'dapfforwc_product_shortcode_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );

    // Add the "Text Manage" field
    add_settings_field(
        'text_manage',
        esc_html__('Text Manage', 'dynamic-ajax-product-filters-for-woocommerce'),
        'dapfforwcpro_text_manage_render',
        'dapfforwcpro-advance-settings',
        'dapfforwcpro_advance_settings_section'
    );

    add_settings_field('wait_cursor_on_filtering', esc_html__('Wait Cursor on Filtering', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_wait_cursor_on_filtering_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('use_overlay', esc_html__('Use Overlay', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_use_overlay_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('smart_auto_scroll', esc_html__('Smart Auto Scroll', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_smart_auto_scroll_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('pagination_via_ajax', esc_html__('Pagination via AJAX', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_pagination_via_ajax_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('sorting_via_ajax', esc_html__('Product Sorting via AJAX', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_sorting_via_ajax_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('browser_history_step_navigation', esc_html__('Browser History Step Navigation', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_browser_history_step_navigation_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('remove_outofStock', esc_html__('Remove out of stock product', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_remove_outofStock_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('product_exclusion_rules', esc_html__('Exclude Products From Results', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_product_exclusion_rules_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('allow_data_share', esc_html__('Contribute to Plugincy', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_allow_data_share_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('sidebar_on_top', esc_html__('Sidebar Top (Mobile only)', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_side_bar_top_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('mobile_breakpoint', esc_html__('Mobile Breakpoint', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_mobile_breakpoint_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');
    add_settings_field('default_value_selected', esc_html__('Make Default Options Selected', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_default_value_selected_render", 'dapfforwc-advance-settings', 'dapfforwc_advance_settings_section');

    $exclude_attributes = isset($Advance_options['exclude_attributes']) ? explode(',', $Advance_options['exclude_attributes']) : [];
    $attributes = function_exists('dapfforwc_get_registered_attribute_prefix_options')
        ? dapfforwc_get_registered_attribute_prefix_options($exclude_attributes)
        : [];

    register_setting(
        'dapfforwc_template_options_group',
        'dapfforwc_template_options',
        'dapfforwc_sanitize_template_options'
    );

    global $template_options;

    if (get_option('dapfforwc_template_options', false) === false) {
        add_option('dapfforwc_template_options', $template_options);
    }

    // seo-permalinks settings register
    $default_seo_permalinks_options = (
        function_exists('dapfforwc_get_seo_permalink_default_options')
            ? dapfforwc_get_seo_permalink_default_options($Advance_options)
            : [
                'use_attribute_type_in_permalinks' => "on",
                'dapfforwc_permalinks_prefix_options' => [
                    "product-category" => 'cata',
                    'tag' => 'tags',
                    'attribute' => !empty($attributes) ? array_reduce($attributes, function ($carry, $attr) {
                        $carry[$attr->attribute_name] = $attr->attribute_name;
                        return $carry;
                    }, []) : [
                        'color' => 'color',
                        'size' => 'size',
                        'brand' => 'brand',
                        'material' => 'material',
                        'style' => 'style',
                    ],
                    'custom' => [],
                    'price' => 'price',
                    'rating' => 'rating',
                    'brand' => 'brand',
                    'author' => 'author',
                    'stock_status' => 'stockStatus',
                    'sale_status' => 'saleStatus',
                    'width' => 'width',
                    'min_width' => 'min_width',
                    'max_width' => 'max_width',
                    'length' => 'length',
                    'height' => 'height',
                    'min_height' => 'min_height',
                    'max_height' => 'max_height',
                    'weight' => 'weight',
                    'min_weight' => 'min_weight',
                    'max_weight' => 'max_weight',
                    'sku' => 'sku',
                    'discount' => 'discount',
                    'date_filter' => 'date',
                    'pagination' => 'paged',
                    'plugincy_search' => 'title',
                    'orderby' => 'orderby',
                ],
                'filters_word_in_permalinks' => 'filters',
                'use_filters_word_in_permalinks' => '',
                'use_anchor' => 0,
            ]
    );
    $seo_permalinks_options = get_option('dapfforwc_seo_permalinks_options', false);
    if ($seo_permalinks_options === false || !is_array($seo_permalinks_options)) {
        $seo_permalinks_options = $default_seo_permalinks_options;
        add_option('dapfforwc_seo_permalinks_options', $seo_permalinks_options);
    } else {
        $seo_permalinks_options = wp_parse_args($seo_permalinks_options, $default_seo_permalinks_options);
    }

    register_setting(
        'dapfforwc_seo_permalinks_settings',
        'dapfforwc_seo_permalinks_options',
        'dapfforwc_sanitize_options'
    );

    add_settings_section(
        'dapfforwc_seo_permalinks_section',
        '<p class="page-title">' . esc_html__('SEO Configuration', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>',
        function () {
            echo '<p style="padding-bottom: 20px; border-bottom: 2px solid #f1f5f9;">' . esc_html__('Configure SEO options for your product filters to improve search engine visibility and customize permalinks structure.', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>';
        },
        'dapfforwc-seo-permalinks'
    );

    // add_settings_field('use_filters_word_in_permalinks', esc_html__('Use Filters Word in Permalinks', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_use_filters_word_in_permalinks_render", 'dapfforwc-seo-permalinks', 'dapfforwc_seo_permalinks_section');
    if (isset($dapfforwc_options["use_url_filter"]) && $dapfforwc_options["use_url_filter"] !== "ajax") {
        add_settings_field('use_attribute_type_in_permalinks', esc_html__('Use Attribute Type in Permalinks', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_use_attribute_type_in_permalinks_render", 'dapfforwc-seo-permalinks', 'dapfforwc_seo_permalinks_section');
        add_settings_field('filters_word_in_permalinks', esc_html__('Filters Word in Permalinks', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_filters_word_in_permalinks_render", 'dapfforwc-seo-permalinks', 'dapfforwc_seo_permalinks_section');
        add_settings_field('permalinks_prefix', esc_html__('Permalinks Prefix', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_permalinks_prefix_render", 'dapfforwc-seo-permalinks', 'dapfforwc_seo_permalinks_section');
    }
    // Add the "SEO Setup" section
    add_settings_section(
        'dapfforwc_seo_section',
        '<p class="h3title">' . esc_html__('SEO Setup', 'dynamic-ajax-product-filters-for-woocommerce') . '</p>',
        null,
        'dapfforwc-seo-permalinks'
    );

    if (isset($dapfforwc_options["use_url_filter"]) && $dapfforwc_options["use_url_filter"] !== "ajax") {
        // add Enable SEO option
        add_settings_field('enable_seo', esc_html__('Enable SEO', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_enable_seo_render", 'dapfforwc-seo-permalinks', 'dapfforwc_seo_section');
    }
    add_settings_field('use_anchor', esc_html__('Make filter link indexable for best SEO', 'dynamic-ajax-product-filters-for-woocommerce'), "dapfforwc_use_anchor_render", 'dapfforwc-seo-permalinks', 'dapfforwc_seo_section');
    if (isset($dapfforwc_options["use_url_filter"]) && $dapfforwc_options["use_url_filter"] !== "ajax") {
        // Add the "SEO Title" field
        add_settings_field(
            'seo_title',
            esc_html__('SEO Title', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_seo_title_callback',
            'dapfforwc-seo-permalinks',
            'dapfforwc_seo_section'
        );
        // Add the "SEO Description" field

        add_settings_field(
            'seo_description',
            esc_html__('SEO Description', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_seo_description_callback',
            'dapfforwc-seo-permalinks',
            'dapfforwc_seo_section'
        );

        // Add the "SEO Keywords" field

        add_settings_field(
            'seo_keywords',
            esc_html__('SEO Keywords', 'dynamic-ajax-product-filters-for-woocommerce'),
            'dapfforwc_seo_keywords_callback',
            'dapfforwc-seo-permalinks',
            'dapfforwc_seo_section'
        );
    }
    if (isset($dapfforwc_options["use_url_filter"]) && $dapfforwc_options["use_url_filter"] === "ajax") {
        add_settings_field(
            'use_url_filter_notice',
            '',
            function () {
                echo '<div style="margin:10px 0 20px 0;padding:10px 15px;background:#f1f5f9;border-left:4px solid #2271b1;">' .
                    esc_html__('To access more SEO and permalinks settings, please change the "Use URL-Based Filter" option from "ajax" to another method.', 'dynamic-ajax-product-filters-for-woocommerce') .
                    '</div>';
            },
            'dapfforwc-seo-permalinks',
            'dapfforwc_seo_section'
        );
    }
}

add_action('admin_init', 'dapfforwc_settings_init');

/**
 * Sanitize a template color while preserving supported alpha channels.
 *
 * @param mixed $color Submitted color value.
 * @return string|null Sanitized hex color, or null when invalid.
 */
function dapfforwc_sanitize_template_color($color)
{
    $color = trim(sanitize_text_field(wp_unslash($color)));
    $opaque_color = sanitize_hex_color($color);

    if ($opaque_color !== null) {
        return $opaque_color;
    }

    return preg_match('/^#(?:[0-9a-f]{4}|[0-9a-f]{8})$/i', $color)
        ? strtolower($color)
        : null;
}

// Sanitize template options
function dapfforwc_sanitize_template_options($input)
{
    $sanitized = array();

    if (isset($input['active_template'])) {
        $sanitized['active_template'] = sanitize_text_field(wp_unslash($input['active_template']));
    }

    if (isset($input['background_color'])) {
        $sanitized['background_color'] = dapfforwc_sanitize_template_color($input['background_color']);
    }

    if (isset($input['primary_color'])) {
        $sanitized['primary_color'] = dapfforwc_sanitize_template_color($input['primary_color']);
    }

    if (isset($input['secondary_color'])) {
        $sanitized['secondary_color'] = dapfforwc_sanitize_template_color($input['secondary_color']);
    }

    if (isset($input['border_color'])) {
        $sanitized['border_color'] = dapfforwc_sanitize_template_color($input['border_color']);
    }

    if (isset($input['text_color'])) {
        $sanitized['text_color'] = dapfforwc_sanitize_template_color($input['text_color']);
    }

    return $sanitized;
}


function dapfforwc_sanitize_options($input)
{
    if (isset($input["product_selector"]) && !isset($input["allow_data_share"])) {
        $input["allow_data_share"] = "off";
    }
    if (isset($input["product_selector"]) && !isset($input["sidebar_on_top"])) {
        $input["sidebar_on_top"] = "off";
    }
    // If input is not an array, make it one
    if (!is_array($input)) {
        return array();
    }

    $sanitized = array();
    $is_seo_permalinks_submission =
        array_key_exists('dapfforwc_permalinks_prefix_options', $input) ||
        array_key_exists('seo_title', $input) ||
        array_key_exists('seo_description', $input) ||
        array_key_exists('seo_keywords', $input) ||
        array_key_exists('enable_seo', $input) ||
        array_key_exists('use_attribute_type_in_permalinks', $input);

    if ($is_seo_permalinks_submission) {
        // Unchecked checkboxes are omitted from POST; keep an explicit off-state
        // so the SEO permalink defaults logic does not turn this back on.
        $sanitized['use_attribute_type_in_permalinks'] =
            isset($input['use_attribute_type_in_permalinks']) && $input['use_attribute_type_in_permalinks'] === 'on' ? 'on' : '';
    }

    // Loop through each element of the array
    foreach ($input as $key => $value) {

        if($value === '#000000' || $value === "" || $value === 0 || $value === "0" ){
            continue; // Skip if the value is the default color
        }
        // Sanitize based on what type of data this is
        if (is_array($value)) {
            // Recursively sanitize nested arrays
            $sanitized[$key] = dapfforwc_sanitize_options($value);
        } else {
            // Determine the right sanitization based on the key or value type
            switch ($key) {
                // Examples for different types of fields
                case 'custom_template_code':
                    $sanitized[$key] = wp_kses_post($value);
                    break;

                case 'url_field':
                    $sanitized[$key] = esc_url_raw($value);
                    break;

                case 'email_field':
                    $sanitized[$key] = sanitize_email($value);
                    break;

                case 'number_field':
                    $sanitized[$key] = intval($value);
                    break;

                case 'mobile_breakpoint':
                    $bp = absint($value);
                    $sanitized[$key] = $bp > 0 ? $bp : 768;
                    break;

                default:
                    // Default sanitization for text fields
                    $sanitized[$key] = sanitize_text_field(wp_unslash($value));
                    if ($key === 'use_attribute_type_in_permalinks' && $value === 'on') {
                        update_option('woocommerce_slug_check_dismissed_time', time() + (100 * DAY_IN_SECONDS));
                        break;
                    }
            }
        }
    }

    if (isset($input['seo_title']) && isset($input['seo_description']) && !isset($input['use_attribute_type_in_permalinks'])) {
        update_option('woocommerce_slug_check_dismissed_time', false);
    }

    if (function_exists('dapfforwc_clear_woocommerce_caches')) {
        dapfforwc_clear_woocommerce_caches();
    }

    return $sanitized;
}

function dapfforwc_sanitize_advance_options($input)
{
    $defaults = [
        'product_selector' => 'ul.products',
        'pagination_selector' => '.woocommerce-pagination',
        'sorting_selector' => 'form.woocommerce-ordering select',
        'result_count_selector' => '.woocommerce-result-count',
        'advanced_pagination_enabled' => '',
        'advanced_pagination_mode' => 'number',
        'advanced_pagination_prev_selector' => '',
        'advanced_pagination_next_selector' => '',
        'advanced_pagination_load_more_selector' => '',
        'advanced_pagination_infinite_scroll_selector' => '',
        'product_shortcode' => 'products',
        'remove_outofStock' => 0,
        'allow_data_share' => "off",
        'sidebar_on_top' => "on",
        'mobile_breakpoint' => 768,
        'default_value_selected' => 0,
        'exclude_attributes' => "",
        'exclude_custom_fields' => "",
        'no_products_text' => 'No products were found matching your selection.',
        'select2_placeholder' => 'Select Options',
    ];

    $existing = get_option('dapfforwc_advance_options');
    $sanitized = wp_parse_args(is_array($existing) ? $existing : [], $defaults);

    if (!is_array($input)) {
        return $sanitized;
    }

    $full_form_keys = [
        'product_selector',
        'pagination_selector',
        'sorting_selector',
        'result_count_selector',
        'advanced_pagination_mode',
        'advanced_pagination_prev_selector',
        'advanced_pagination_next_selector',
        'advanced_pagination_load_more_selector',
        'advanced_pagination_infinite_scroll_selector',
        'product_shortcode',
        'mobile_breakpoint',
        'no_products_text',
        'select2_placeholder',
    ];

    $checkbox_keys = [
        'advanced_pagination_enabled',
        'remove_outofStock',
        'allow_data_share',
        'sidebar_on_top',
        'default_value_selected',
    ];

    $is_full_submission = false;

    foreach ($full_form_keys as $full_form_key) {
        if (array_key_exists($full_form_key, $input)) {
            $is_full_submission = true;
            break;
        }
    }

    foreach ($checkbox_keys as $checkbox_key) {
        if ($is_full_submission) {
            $sanitized[$checkbox_key] = isset($input[$checkbox_key]) && $input[$checkbox_key] === 'on' ? 'on' : '';
        } elseif (array_key_exists($checkbox_key, $input)) {
            $sanitized[$checkbox_key] = $input[$checkbox_key] === 'on' ? 'on' : '';
        }
    }

    if (array_key_exists('product_selector', $input)) {
        $product_selector = sanitize_text_field(wp_unslash($input['product_selector']));
        $sanitized['product_selector'] = $product_selector !== '' ? $product_selector : 'ul.products';
    }

    if (array_key_exists('pagination_selector', $input)) {
        $pagination_selector = sanitize_text_field(wp_unslash($input['pagination_selector']));
        $sanitized['pagination_selector'] = $pagination_selector !== '' ? $pagination_selector : '.woocommerce-pagination';
    }

    if (array_key_exists('sorting_selector', $input)) {
        $sorting_selector = sanitize_text_field(wp_unslash($input['sorting_selector']));
        $sanitized['sorting_selector'] = $sorting_selector !== '' ? $sorting_selector : 'form.woocommerce-ordering select';
    }

    if (array_key_exists('result_count_selector', $input)) {
        $result_count_selector = sanitize_text_field(wp_unslash($input['result_count_selector']));
        $sanitized['result_count_selector'] = $result_count_selector !== '' ? $result_count_selector : '.woocommerce-result-count';
    }

    if (array_key_exists('advanced_pagination_mode', $input)) {
        $allowed_modes = [
            'number',
            'number_prev_next',
            'load_more',
            'infinite_scroll',
        ];
        $advanced_pagination_mode = sanitize_key(wp_unslash($input['advanced_pagination_mode']));
        $sanitized['advanced_pagination_mode'] = in_array($advanced_pagination_mode, $allowed_modes, true) ? $advanced_pagination_mode : 'number';
    }

    $pagination_selector_fields = [
        'advanced_pagination_prev_selector',
        'advanced_pagination_next_selector',
        'advanced_pagination_load_more_selector',
        'advanced_pagination_infinite_scroll_selector',
    ];

    foreach ($pagination_selector_fields as $selector_field) {
        if (!array_key_exists($selector_field, $input)) {
            continue;
        }

        $sanitized[$selector_field] = sanitize_text_field(wp_unslash($input[$selector_field]));
    }

    if (array_key_exists('product_shortcode', $input)) {
        $product_shortcode = sanitize_text_field(wp_unslash($input['product_shortcode']));
        $sanitized['product_shortcode'] = $product_shortcode !== '' ? $product_shortcode : 'products';
    }

    if (array_key_exists('mobile_breakpoint', $input)) {
        $mobile_breakpoint = absint($input['mobile_breakpoint']);
        $sanitized['mobile_breakpoint'] = $mobile_breakpoint > 0 ? $mobile_breakpoint : 768;
    }

    if (array_key_exists('exclude_attributes', $input)) {
        $raw_values = $input['exclude_attributes'];
        if (!is_array($raw_values)) {
            $raw_values = explode(',', wp_unslash((string) $raw_values));
        }
        $clean_values = array_filter(array_map(static function ($value) {
            return sanitize_text_field(wp_unslash((string) $value));
        }, array_map('trim', $raw_values)), 'strlen');
        $sanitized['exclude_attributes'] = implode(',', array_unique($clean_values));
    }

    if (array_key_exists('exclude_custom_fields', $input)) {
        $raw_values = $input['exclude_custom_fields'];
        if (!is_array($raw_values)) {
            $raw_values = explode(',', wp_unslash((string) $raw_values));
        }
        $clean_values = array_filter(array_map(static function ($value) {
            return sanitize_text_field(wp_unslash((string) $value));
        }, array_map('trim', $raw_values)), 'strlen');
        $sanitized['exclude_custom_fields'] = implode(',', array_unique($clean_values));
    }

    if (array_key_exists('no_products_text', $input)) {
        $no_products_text = sanitize_text_field(wp_unslash($input['no_products_text']));
        $sanitized['no_products_text'] = $no_products_text !== '' ? $no_products_text : 'No products were found matching your selection.';
    }

    if (array_key_exists('select2_placeholder', $input)) {
        $select2_placeholder = sanitize_text_field(wp_unslash($input['select2_placeholder']));
        $sanitized['select2_placeholder'] = $select2_placeholder !== '' ? $select2_placeholder : 'Select Options';
    }

    if (function_exists('dapfforwc_clear_woocommerce_caches')) {
        dapfforwc_clear_woocommerce_caches();
    }

    return $sanitized;
}

function dapfforwc_normalize_style_term_option_key(string $attribute, $term_key): string
{
    if (!is_scalar($term_key)) {
        return '';
    }

    $term_key = rawurldecode(trim((string) $term_key));
    if ($term_key === '') {
        return '';
    }

    $taxonomy = function_exists('dapfforwc_get_style_term_taxonomy')
        ? dapfforwc_get_style_term_taxonomy($attribute)
        : '';

    if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
        return $term_key;
    }

    $term = null;
    $term_id = absint($term_key);
    if ($term_id > 0) {
        $term = get_term($term_id, $taxonomy);
    }

    if (!$term || is_wp_error($term)) {
        $term = get_term_by('slug', $term_key, $taxonomy);
    }

    $sanitized_key = sanitize_title($term_key);
    if ((!$term || is_wp_error($term)) && $sanitized_key !== '' && $sanitized_key !== $term_key) {
        $term = get_term_by('slug', $sanitized_key, $taxonomy);
    }

    return ($term && !is_wp_error($term) && !empty($term->term_id)) ? (string) absint($term->term_id) : $term_key;
}

function dapfforwc_normalize_style_term_keyed_group(array $values, string $attribute): array
{
    $normalized = [];

    foreach ($values as $term_key => $value) {
        $normalized_key = dapfforwc_normalize_style_term_option_key($attribute, $term_key);
        if ($normalized_key === '') {
            continue;
        }

        if ($normalized_key !== (string) $term_key && array_key_exists($normalized_key, $normalized)) {
            continue;
        }

        $normalized[$normalized_key] = $value;
    }

    return $normalized;
}

function dapfforwc_normalize_style_term_value_list(array $values, string $attribute): array
{
    $normalized = [];

    foreach ($values as $term_key) {
        $normalized_key = dapfforwc_normalize_style_term_option_key($attribute, $term_key);
        if ($normalized_key === '' || in_array($normalized_key, $normalized, true)) {
            continue;
        }

        $normalized[] = $normalized_key;
    }

    return $normalized;
}

function dapfforwc_normalize_style_option_term_keys(array $style_options): array
{
    $term_keyed_groups = ['colors', 'images'];

    foreach ($style_options as $attribute => $attribute_options) {
        if ((!is_string($attribute) && !is_int($attribute)) || !is_array($attribute_options)) {
            continue;
        }
        $attribute = (string) $attribute;

        foreach ($term_keyed_groups as $group) {
            if (isset($attribute_options[$group]) && is_array($attribute_options[$group])) {
                $style_options[$attribute][$group] = dapfforwc_normalize_style_term_keyed_group($attribute_options[$group], $attribute);
            }
        }
    }

    if (isset($style_options['terms']) && is_array($style_options['terms'])) {
        foreach ($style_options['terms'] as $attribute => $selected_terms) {
            if ((is_string($attribute) || is_int($attribute)) && is_array($selected_terms)) {
                $attribute = (string) $attribute;
                $style_options['terms'][$attribute] = dapfforwc_normalize_style_term_value_list($selected_terms, $attribute);
            }
        }
    }

    return function_exists('dapfforwc_normalize_style_option_attribute_keys')
        ? dapfforwc_normalize_style_option_attribute_keys($style_options)
        : $style_options;
}

function dapfforwc_maybe_migrate_style_option_term_keys()
{
    if (get_option('dapfforwc_style_options_term_id_migrated') === '1') {
        return;
    }

    $style_options = get_option('dapfforwc_style_options', []);
    if (!is_array($style_options)) {
        update_option('dapfforwc_style_options_term_id_migrated', '1', false);
        return;
    }

    $normalized = dapfforwc_normalize_style_option_term_keys($style_options);
    if ($normalized !== $style_options) {
        update_option('dapfforwc_style_options', $normalized);
    }

    update_option('dapfforwc_style_options_term_id_migrated', '1', false);
}
add_action('admin_init', 'dapfforwc_maybe_migrate_style_option_term_keys', 20);

function dapfforwc_maybe_migrate_style_option_attribute_keys()
{
    if (get_option('dapfforwc_style_options_attribute_id_migrated') === '1') {
        return;
    }

    $style_options = get_option('dapfforwc_style_options', []);
    if (!is_array($style_options)) {
        update_option('dapfforwc_style_options_attribute_id_migrated', '1', false);
        return;
    }

    $normalized = dapfforwc_normalize_style_option_term_keys($style_options);
    if ($normalized !== $style_options) {
        update_option('dapfforwc_style_options', $normalized);
    }

    update_option('dapfforwc_style_options_attribute_id_migrated', '1', false);
}
add_action('admin_init', 'dapfforwc_maybe_migrate_style_option_attribute_keys', 21);

function dapfforwc_sanitize_style_options($input, bool $normalize_term_keys = true)
{
    if (!is_array($input)) {
        return [];
    }

    $sanitized = [];

    foreach ($input as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = dapfforwc_sanitize_style_options($value, false);
            continue;
        }

        $sanitized[$key] = sanitize_text_field(wp_unslash($value));
    }

    if ($normalize_term_keys) {
        $sanitized = dapfforwc_normalize_style_option_term_keys($sanitized);
    }

    if (function_exists('dapfforwc_clear_woocommerce_caches')) {
        dapfforwc_clear_woocommerce_caches();
    }

    return $sanitized;
}
