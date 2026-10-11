<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('dapfforwc_esc_shortcode_selector_attr')) {
    // Selector values are placed inside a generated shortcode string: a raw "]" would end the shortcode early.
    function dapfforwc_esc_shortcode_selector_attr($value)
    {
        return str_replace(array('[', ']'), array('&#91;', '&#93;'), esc_attr((string) $value));
    }
}

if (!function_exists('dapfforwc_register_dynamic_ajax_filter_block_script')) {
    /**
     * Register the block editor script once, with the packages block.min.js really uses (wp.blockEditor,
     * wp.data, wp.i18n ...). It was registered here and enqueued again elsewhere with a different list that
     * missed those and loaded the deprecated wp-editor package instead.
     */
    function dapfforwc_register_dynamic_ajax_filter_block_script()
    {
        if (wp_script_is('dapfforwc-filter-block', 'registered')) {
            return;
        }
        wp_register_script(
            'dapfforwc-filter-block',
            plugins_url('block.min.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'wp-api-fetch'),
            filemtime(plugin_dir_path(__FILE__) . 'block.min.js'),
            true
        );
        wp_set_script_translations('dapfforwc-filter-block', 'dynamic-ajax-product-filters-for-woocommerce', plugin_dir_path(dirname(__FILE__)) . 'languages');
    }
}

// creating blocks for gutenberg

function dapfforwc_register_dynamic_ajax_filter_block()
{
    dapfforwc_register_dynamic_ajax_filter_block_script();

    register_block_type('plugin/dynamic-ajax-filter', array(
        'editor_script' => 'dapfforwc-filter-block',
        'render_callback' => 'dapfforwc_render_dynamic_ajax_filter_block',
        'attributes' => array(
            'filterType' => array(
                'type' => 'string',
                'default' => 'all',
            ),
            'filterLayout' => array(
                'type' => 'string',
                'default' => 'sidebar',
            ),
            'productSelector' => array(
                'type' => 'string',
                'default' => '',
            ),
            'paginationSelector' => array(
                'type' => 'string',
                'default' => '',
            ),
            'category' => array(
                'type' => 'string',
                'default' => '',
            ),
            'tag' => array(
                'type' => 'string',
                'default' => '',
            ),
            'attribute' => array(
                'type' => 'string',
                'default' => '',
            ),
            'attributeTerms' => array(
                'type' => 'string',
                'default' => '',
            ),
            'filterName' => array(
                'type' => 'string',
                'default' => '',
            ),
            'backgroundColor' => array(
                'type' => 'string',
                'default' => '',
            ),
            'color' => array(
                'type' => 'string',
                'default' => '',
            ),
            'typography' => array(
                'type' => 'object',
                'default' => array(),
            ),
        ),
    ));
}
add_action('init', 'dapfforwc_register_dynamic_ajax_filter_block');

/**
 * Sanitise one block style value for a CSS declaration: it must not leave its declaration or the style element.
 */
function dapfforwc_block_css_value($value)
{
    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }
    if (!is_string($value)) {
        return '';
    }
    $value = trim(sanitize_text_field($value));
    if ($value === '' || preg_match('/[;{}<>\\\\]|expression\s*\(|javascript:|url\s*\(/i', $value)) {
        return '';
    }
    return $value;
}

/**
 * Block range controls store plain numbers (pixels); the font size picker may store a value with its own unit.
 */
function dapfforwc_block_css_length($value)
{
    if (is_numeric($value)) {
        return ((float) $value) . 'px';
    }
    return dapfforwc_block_css_value($value);
}

/**
 * Box controls store {top, right, bottom, left} in pixels; their "Reset" button stores a ready CSS string.
 */
function dapfforwc_block_css_box($value)
{
    if (!is_array($value)) {
        return dapfforwc_block_css_value($value);
    }
    $sides = array();
    foreach (array('top', 'right', 'bottom', 'left') as $side) {
        $sides[] = (isset($value[$side]) && is_numeric($value[$side]) ? (float) $value[$side] : 0) . 'px';
    }
    return implode(' ', $sides);
}

/**
 * Block style attributes are objects; anything else (old or malformed block markup) means "no styles".
 */
function dapfforwc_get_block_style_attribute($attributes, $name)
{
    return isset($attributes[$name]) && is_array($attributes[$name]) ? $attributes[$name] : array();
}

function dapfforwc_generate_css($styles, $device = 'desktop', $hover = false, $active = false, $sliderProgress = false, $sliderthumb = false, $slidertooltip = false, $device_values = false)
{
    $css = '';
    if (isset($styles['display']) && is_scalar($styles['display']) && '' !== sanitize_key($styles['display'])) {
        $css .= 'display: ' . sanitize_key($styles['display']) . ';';
    }
    if (isset($styles) && is_array($styles)) {
        // A state call (hover, active, slider parts) prints only its own keys; the other calls skip those keys.
        $state_keys = array(
            'hoverBackground' => $hover,
            'hoverColor' => $hover,
            'hoverfillColor' => $hover,
            'activeColor' => $active,
            'activefillColor' => $active,
            'plugrogress-border-radius' => $sliderProgress,
            'progressBackground' => $sliderProgress,
            'progressmargin' => $sliderProgress,
            'thumbBackground' => $sliderthumb,
            'thumbSize' => $sliderthumb,
            'tooltipBackground' => $slidertooltip,
        );
        $is_state_call = $hover || $active || $sliderProgress || $sliderthumb || $slidertooltip;
        // Values that are not stored per device belong to the base (desktop) rule only, not to every media query.
        $is_base_call = $device_values || $device === 'desktop' || $device === '';

        foreach ($styles as $key => $value) {
            if ($value === '' || $value === null || $value === array() || $key === 'display') {
                continue; // Skip empty values (display is printed above)
            }

            if (in_array($key, array('desktop', 'tablet', 'mobile', 'smartphone'), true)) {
                if ($key === $device && is_array($value) && !$device_values) {
                    $css .= dapfforwc_generate_css($value, $device, $hover, $active, $sliderProgress, $sliderthumb, $slidertooltip, true);
                }
                continue;
            }

            if (array_key_exists($key, $state_keys) ? !$state_keys[$key] : $is_state_call) {
                continue;
            }

            if (!$is_base_call && !in_array($key, array('background', 'height'), true)) {
                continue;
            }

            switch ($key) {
                case 'font-size':
                case 'width':
                case 'gap':
                    $length = dapfforwc_block_css_length($value);
                    if ($length !== '') {
                        $css .= "$key: {$length};";
                    }
                    break;

                case 'height':
                    if (is_array($value) && isset($value[$device]) && is_array($value[$device]) && !empty($value[$device]['value']) && is_numeric($value[$device]['value'])) {
                        $unit = isset($value[$device]['unit']) && in_array($value[$device]['unit'], array('px', '%', 'em', 'rem', 'vh', 'vw'), true) ? $value[$device]['unit'] : 'px';
                        $css .= $key . ": " . ((float) $value[$device]['value']) . $unit . ";";
                    }
                    break;

                case 'padding':
                case 'margin':
                case 'border-radius':
                case 'plugrogress-border-radius':
                case 'progressmargin':
                    $box = dapfforwc_block_css_box($value);
                    if ($box !== '') {
                        $property = $key === 'progressmargin' ? 'margin' : ($key === 'plugrogress-border-radius' ? 'border-radius' : $key);
                        $css .= "$property: {$box};";
                    }
                    break;

                case 'thumbSize':
                    $length = dapfforwc_block_css_length($value);
                    if ($length !== '') {
                        $css .= "width: {$length}; height: {$length};";
                    }
                    break;

                case 'background':
                    $background = dapfforwc_block_css_value(is_array($value) ? (isset($value[$device]) ? $value[$device] : '') : $value);
                    if ($background !== '') {
                        $css .= "background: {$background};";
                    }
                    break;

                case 'text-align':
                    $align = dapfforwc_block_css_value($value);
                    if ($align !== '') {
                        // "space-between" only exists for justify-content (the title is a flex container).
                        if (in_array($align, array('left', 'center', 'right', 'justify', 'start', 'end'), true)) {
                            $css .= "$key: $align;";
                        }
                        $css .= "justify-content: $align;";
                    }
                    break;

                default:
                    $state_properties = array(
                        'hoverBackground' => 'background',
                        'hoverColor' => 'color',
                        'hoverfillColor' => 'fill',
                        'activeColor' => 'color',
                        'activefillColor' => 'fill',
                        'progressBackground' => 'background',
                        'thumbBackground' => 'background',
                        'tooltipBackground' => 'background',
                    );
                    $property = isset($state_properties[$key]) ? $state_properties[$key] : $key;
                    $css_value = is_array($value) ? '' : dapfforwc_block_css_value($value);
                    if ($css_value === '' || !preg_match('/^[a-z][a-z-]*$/', (string) $property)) {
                        break;
                    }
                    $important = in_array($key, array('progressBackground', 'thumbBackground', 'tooltipBackground'), true) ? '' : ' !important';
                    $css .= "$property: {$css_value}{$important};";
                    break;
            }
        }
    }

    return $css;
}


function dapfforwc_render_dynamic_ajax_filter_block($attributes)
{
    global $dapfforwc_options;
    $filter_type = isset($attributes['filterType']) ? sanitize_key($attributes['filterType']) : '';
    $filter_layout = isset($attributes['filterLayout']) ? sanitize_key($attributes['filterLayout']) : '';
    $use_custom_design = isset($attributes['usecustomdesign']) ? sanitize_key($attributes['usecustomdesign']) : '';
    $perPage = isset($attributes['perPage']) ? intval(sanitize_key($attributes['perPage'])) : 12;
    $filter_options_manage = isset($attributes['filterOptions']) && is_array($attributes['filterOptions']) ? $attributes['filterOptions'] : [];
    // Empty ("Use global setting", the default) = the global Mobile Filter Style (left empty for the shortcode to
    // resolve, so the form knows it came from the setting), else the block's own default style_1.
    $mobile_style = isset($attributes['mobileStyle']) && is_scalar($attributes['mobileStyle']) ? sanitize_key((string) $attributes['mobileStyle']) : '';
    if (!dapfforwc_is_mobile_responsive_style($mobile_style)) {
        $mobile_style = dapfforwc_get_global_mobile_responsive_style() !== '' ? '' : 'style_1';
    }
    $mobile_breakpoint = dapfforwc_get_mobile_breakpoint();
    $mobile_breakpoint_css = intval($mobile_breakpoint);
    $output = '';

    // Extract styles
    $form_style = dapfforwc_get_block_style_attribute($attributes, 'formStyle');
    $container_style = dapfforwc_get_block_style_attribute($attributes, 'containerStyle');
    $widget_title_style = dapfforwc_get_block_style_attribute($attributes, 'widgetTitleStyle');
    $widget_items_style = dapfforwc_get_block_style_attribute($attributes, 'widgetItemsStyle');
    $button_style = dapfforwc_get_block_style_attribute($attributes, 'buttonStyle');
    $rating_style = dapfforwc_get_block_style_attribute($attributes, 'ratingStyle');
    $reset_button_style = dapfforwc_get_block_style_attribute($attributes, 'resetButtonStyle');
    $input_style = dapfforwc_get_block_style_attribute($attributes, 'inputStyle');
    $slider_style = dapfforwc_get_block_style_attribute($attributes, 'sliderStyle');
    // The editor only toggles display (block/none); anything else would be printed inside <style>.
    $filter_word_mobile_display = isset($attributes['filterWordMobile']['display']) && is_scalar($attributes['filterWordMobile']['display']) ? sanitize_key($attributes['filterWordMobile']['display']) : '';
    $filter_word_mobile = in_array($filter_word_mobile_display, array('block', 'none'), true) ? array('display' => $filter_word_mobile_display) : array();
    $custom_css = isset($attributes['customCSS']) && is_string($attributes['customCSS']) ? wp_strip_all_tags($attributes['customCSS']) : '';
    $raw_class_name = isset($attributes['className']) ? (string) $attributes['className'] : '';
    $class_parts = preg_split('/\s+/', $raw_class_name, -1, PREG_SPLIT_NO_EMPTY);
    $class_parts = array_map('sanitize_html_class', $class_parts);
    $class_name  = trim(implode(' ', array_filter($class_parts)));

    $single_filter_inactive_style = dapfforwc_get_block_style_attribute($attributes, 'singleFilterInactiveStyle');
    $single_filter_container_style = dapfforwc_get_block_style_attribute($attributes, 'singleFilterContainerStyle');
    $single_filter_active_style = dapfforwc_get_block_style_attribute($attributes, 'singleFilterActiveStyle');
    $single_filter_hover_style = dapfforwc_get_block_style_attribute($attributes, 'singleFilterHoverStyle');


    // Generate CSS for desktop, tablet, and smartphone
    $form_style_css = dapfforwc_generate_css($form_style);
    $form_sm_style_css = dapfforwc_generate_css($form_style, 'smartphone');
    $form_md_style_css = dapfforwc_generate_css($form_style, 'tablet');

    $container_style_css = dapfforwc_generate_css($container_style);
    $container_sm_style_css = dapfforwc_generate_css($container_style, 'smartphone');
    $container_md_style_css = dapfforwc_generate_css($container_style, 'tablet');

    $widget_title_style_css = dapfforwc_generate_css($widget_title_style);
    $widget_sm_title_style_css = dapfforwc_generate_css($widget_title_style, 'smartphone');
    $widget_md_title_style_css = dapfforwc_generate_css($widget_title_style, 'tablet');

    $widget_items_style_css = dapfforwc_generate_css($widget_items_style);
    $widget_items_sm_style_css = dapfforwc_generate_css($widget_items_style, 'smartphone');
    $widget_items_md_style_css = dapfforwc_generate_css($widget_items_style, 'tablet');

    $button_style_css = dapfforwc_generate_css($button_style);
    $button_sm_style_css = dapfforwc_generate_css($button_style, 'smartphone');
    $button_md_style_css = dapfforwc_generate_css($button_style, 'tablet');
    $button_hover_css = dapfforwc_generate_css($button_style, '', true);

    $rating_style_css = dapfforwc_generate_css($rating_style);
    $rating_sm_style_css = dapfforwc_generate_css($rating_style, 'smartphone');
    $rating_md_style_css = dapfforwc_generate_css($rating_style, 'tablet');
    $rating_hover_css = dapfforwc_generate_css($rating_style, '', true);
    $rating_active_css = dapfforwc_generate_css($rating_style, '', false, true);

    $reset_button_style_css = dapfforwc_generate_css($reset_button_style);
    $reset_button_sm_style_css = dapfforwc_generate_css($reset_button_style, 'smartphone');
    $reset_button_md_style_css = dapfforwc_generate_css($reset_button_style, 'tablet');
    $reset_button_hover_css = dapfforwc_generate_css($reset_button_style, '', true);

    $input_style_css = dapfforwc_generate_css($input_style);
    $input_sm_style_css = dapfforwc_generate_css($input_style, 'smartphone');
    $input_md_style_css = dapfforwc_generate_css($input_style, 'tablet');

    $slider_style_css = dapfforwc_generate_css($slider_style);
    $slider_sm_style_css = dapfforwc_generate_css($slider_style, 'smartphone');
    $slider_md_style_css = dapfforwc_generate_css($slider_style, 'tablet');
    $slider_progress_style_css = dapfforwc_generate_css($slider_style, 'desktop', false, false, true);
    $slider_progress_sm_style_css = dapfforwc_generate_css($slider_style, 'smartphone', false, false, true);
    $slider_progress_md_style_css = dapfforwc_generate_css($slider_style, 'tablet', false, false, true);
    $slider_thumb_style_css = dapfforwc_generate_css($slider_style, '', false, false, false, true);
    $slider_tooltip_style_css = dapfforwc_generate_css($slider_style, '', false, false, false, false, true);

    $filter_word_mobile_css = dapfforwc_generate_css($filter_word_mobile);


    $single_filter_inactive_css = dapfforwc_generate_css($single_filter_inactive_style);
    $single_filter_container_css = dapfforwc_generate_css($single_filter_container_style);
    $single_filter_active_css = dapfforwc_generate_css($single_filter_active_style);
    $single_filter_hover_css = dapfforwc_generate_css($single_filter_hover_style);


    switch ($filter_type) {
        case 'all':
            // $output .= json_encode($filter_options_manage);
            $index = 0;
            if ($filter_options_manage) {
                $filter_ordering_css = 'form#product-filter { display: flex ; flex-direction: column; }form#product-filter .plugincy-filter-group {order: 999;}';
                foreach ($filter_options_manage as $options) {
                    // Saved block data is not schema-checked: skip items without a usable id.
                    if (!is_array($options) || !isset($options['id']) || !is_scalar($options['id']) || '' === (string) $options['id']) {
                        continue;
                    }
                    $options['id'] = (string) $options['id'];
                    if ($options["id"] == "product-category" && !isset($dapfforwc_options["show_categories"])) {
                        continue;
                    }
                    if ($options["id"] == "tag" && !isset($dapfforwc_options["show_tags"])) {
                        continue;
                    }
                    if ($options["id"] == "price-range" && !isset($dapfforwc_options["show_price_range"])) {
                        continue;
                    }
                    if ($options["id"] == "rating" && !isset($dapfforwc_options["show_rating"])) {
                        continue;
                    }
                    if ($options["id"] == "search_text" && !isset($dapfforwc_options["show_search"])) {
                        continue;
                    }
                    if ($options["id"] == "rplurand" && !isset($dapfforwc_options["show_brand"])) {
                        continue;
                    }
                    if ($options["id"] == "rpluthor" && !isset($dapfforwc_options["show_author"])) {
                        continue;
                    }
                    if ($options["id"] == "rplutock_status" && !isset($dapfforwc_options["show_status"])) {
                        continue;
                    }
                    if ($options["id"] == "rpn_sale" && !isset($dapfforwc_options["show_onsale"])) {
                        continue;
                    }

                    $is_visible = !empty($options["visible"]) ? "flex" : "none";
                    $filter_ordering_css .= '.' . sanitize_html_class($options["id"]) . '{
                    order: ' . $index . '!important;
                    display:' . $is_visible . '!important;
                    flex-direction: column;                
                    }
                    ';
                    $index++;
                }
                dapfforwc_add_inline_style($filter_ordering_css, dapfforwc_get_frontend_style_handle());
            }
            $product_selector = isset($attributes['productSelector']) ? dapfforwc_esc_shortcode_selector_attr($attributes['productSelector']) : '';
            $pagination_selector = isset($attributes['paginationSelector']) ? dapfforwc_esc_shortcode_selector_attr($attributes['paginationSelector']) : '';
            $db_categories = isset($attributes['category']) ? esc_attr(strtolower(sanitize_text_field($attributes['category']))) : '';
            $db_tags = isset($attributes['tag']) ? esc_attr(strtolower(sanitize_text_field($attributes['tag']))) : '';
            $db_attribute = isset($attributes['attribute']) ? esc_attr(strtolower(sanitize_text_field($attributes['attribute']))) : '';
            $db_attribute_terms = isset($attributes['attributeTerms']) ? esc_attr(strtolower(sanitize_text_field($attributes['attributeTerms']))) : '';
            $block_css = '';
            if ($form_style_css) {
                $block_css .= 'form#product-filter {' . $form_style_css . '}';
            }
            if ($container_style_css) {
                $block_css .= '.plugincy-filter-group {' . $container_style_css . '}';
            }
            if ($widget_title_style_css) {
                $block_css .= '.plugincy-filter-group .plugincy_title {' . $widget_title_style_css . '}';
            }
            if ($widget_items_style_css) {
                $block_css .= '.plugincy-filter-group .items {' . $widget_items_style_css . '    display: flex; flex-direction: column;}';
            }
            if ($button_style_css) {
                $block_css .= 'form#product-filter button {' . $button_style_css . '}';
            }
            if ($rating_style_css) {
                $block_css .= 'form#product-filter svg {' . $rating_style_css . '} .dynamic-rating label{' . $rating_style_css . '}';
            }
            if ($reset_button_style_css) {
                $block_css .= 'form#product-filter span.reset-value {' . $reset_button_style_css . '}';
            }
            if ($input_style_css) {
                $block_css .= 'form#product-filter input[type="search"], form#product-filter input[type="number"] {' . $input_style_css . '}';
            }
            if ($slider_style_css) {
                $block_css .= 'form#product-filter .slider {' . $slider_style_css . '}';
            }
            if ($slider_progress_style_css) {
                $block_css .= 'form#product-filter .plugincy_slider .plugrogress {' . $slider_progress_style_css . '}';
            }
            if ($slider_thumb_style_css) {
                $block_css .= 'form#product-filter input[type="range"]::-webkit-slider-thumb {' . $slider_thumb_style_css . '} input[type="range"]::-moz-range-thumb {' . $slider_thumb_style_css . '}';
            }
            if ($slider_tooltip_style_css) {
                $block_css .= 'form#product-filter .plugrogress-percentage:before,form#product-filter  .plugrogress-percentage:after {' . $slider_tooltip_style_css . '}';
            }
            $block_css .= '
            
 @media screen and (max-width: ' . $mobile_breakpoint_css . 'px) {';
            if ($form_sm_style_css) {
                $block_css .= 'form#product-filter {' . $form_sm_style_css . '}';
            }
            if ($container_sm_style_css) {
                $block_css .= '.plugincy-filter-group {' . $container_sm_style_css . '}';
            }
            if ($widget_sm_title_style_css) {
                $block_css .= '.plugincy-filter-group .plugincy_title {' . $widget_sm_title_style_css . '}';
            }
            if ($widget_items_sm_style_css) {
                $block_css .= '.plugincy-filter-group .items {' . $widget_items_sm_style_css . '}';
            }
            if ($button_sm_style_css) {
                $block_css .= 'form#product-filter button {' . $button_sm_style_css . '}';
            }
            if ($rating_sm_style_css) {
                $block_css .= 'form#product-filter svg {' . $rating_sm_style_css . '} .dynamic-rating label{' . $rating_sm_style_css . '}';
            }
            if ($reset_button_sm_style_css) {
                $block_css .= 'form#product-filter span.reset-value {' . $reset_button_sm_style_css . '}';
            }
            if ($input_sm_style_css) {
                $block_css .= 'form#product-filter input[type="search"], form#product-filter input[type="number"] {' . $input_sm_style_css . '}';
            }
            if ($slider_sm_style_css) {
                $block_css .= 'form#product-filter .slider {' . $slider_sm_style_css . '}';
            }
            if ($slider_progress_sm_style_css) {
                $block_css .= 'form#product-filter .plugincy_slider .plugrogress {' . $slider_progress_sm_style_css . '}';
            }
            if ($filter_word_mobile_css) {
                $block_css .= 'form#product-filter:before {' . $filter_word_mobile_css . '}';
            }
            $block_css .= '}';
            $block_css .= '
            
@media screen and (min-width: 576px) and (max-width: ' . $mobile_breakpoint_css . 'px) {';
            if ($form_md_style_css) {
                $block_css .= 'form#product-filter {' . $form_md_style_css . '}';
            }
            if ($container_md_style_css) {
                $block_css .= '.plugincy-filter-group {' . $container_md_style_css . '}';
            }
            if ($widget_md_title_style_css) {
                $block_css .= '.plugincy-filter-group .plugincy_title {' . $widget_md_title_style_css . '}';
            }
            if ($widget_items_md_style_css) {
                $block_css .= '.plugincy-filter-group .items {' . $widget_items_md_style_css . '}';
            }
            if ($button_md_style_css) {
                $block_css .= 'form#product-filter button {' . $button_md_style_css . '}';
            }
            if ($rating_md_style_css) {
                $block_css .= 'form#product-filter svg {' . $rating_md_style_css . '} .dynamic-rating label{' . $rating_md_style_css . '}';
            }
            if ($reset_button_md_style_css) {
                $block_css .= 'form#product-filter span.reset-value {' . $reset_button_md_style_css . '}';
            }
            if ($input_md_style_css) {
                $block_css .= 'form#product-filter input[type="search"], form#product-filter input[type="number"] {' . $input_md_style_css . '}';
            }
            if ($slider_md_style_css) {
                $block_css .= 'form#product-filter .slider {' . $slider_md_style_css . '}';
            }
            if ($slider_progress_md_style_css) {
                $block_css .= 'form#product-filter .plugincy_slider .plugrogress {' . $slider_progress_md_style_css . '}';
            }
            $block_css .= '}
            
';
            $block_css .= $custom_css;
            if ($button_hover_css) {
                $block_css .= 'form#product-filter button:hover {' . $button_hover_css . '}';
            }
            if ($reset_button_hover_css) {
                $block_css .= 'form#product-filter span.reset-value:hover {' . $reset_button_hover_css . '}';
            }
            if ($rating_hover_css) {
                $block_css .= 'form#product-filter .plugincy-stars:hover svg,.dynamic-rating input:checked ~ label svg, .dynamic-rating:not(:checked) label:hover svg, .dynamic-rating:not(:checked) label:hover ~ label svg {' . $rating_hover_css . '}';
            }
            if ($rating_active_css) {
                $block_css .= 'form#product-filter input:checked + .plugincy-stars svg,   .dynamic-rating  input:checked + label:hover svg,
  .dynamic-rating  input:checked ~ label:hover svg,
  .dynamic-rating  label:hover ~ input:checked ~ label svg,
  .dynamic-rating  input:checked ~ label:hover ~ label svg {' . $rating_active_css . '}';
            }
            dapfforwc_add_inline_style($block_css, dapfforwc_get_frontend_style_handle());
            // Build the wrapper <div>; only add class attribute if non-empty
            $wrapper_open = '<div';
            if ($class_name !== '') {
                $wrapper_open .= ' class="' . esc_attr($class_name) . '"';
            }
            $wrapper_open .= '>';

            // Escape shortcode attribute values that are strings
            $shortcode = sprintf(
                '[plugincy_filters per_page="%d" layout="%s" use_custom_template_design="%s" mobile_responsive="%s" product_selector="%s" pagination_selector="%s" category="%s" tag="%s" attribute="%s" terms="%s"]',
                $perPage,
                $filter_layout,
                $use_custom_design,
                $mobile_style,
                $product_selector,
                $pagination_selector,
                $db_categories,
                $db_tags,
                $db_attribute,
                $db_attribute_terms
            );

            $output .= $wrapper_open . do_shortcode($shortcode) . '</div>';

            break;
        case 'single':
            $filter_name = esc_attr($attributes['filterName']);
            dapfforwc_add_inline_style(
                '.rfilterbuttons li{' . $single_filter_inactive_css . '}' .
                '.rfilterbuttons ul {' . $single_filter_container_css . '}' .
                '.rfilterbuttons ul li.checked {' . $single_filter_active_css . '}' .
                '.rfilterbuttons ul li:hover {' . $single_filter_hover_css . '}',
                dapfforwc_get_frontend_style_handle()
            );
            $shortcode = sprintf('[plugincy_filters_single name="%s"]', $filter_name);

            // 4) Output: escape only real HTML attributes
            $output .= ($class_name !== '' ? '<div class="' . esc_attr($class_name) . '">' : '<div>')
                . do_shortcode($shortcode)
                . '</div>';
            break;
        case 'selected':
            dapfforwc_add_inline_style(
                '.rfilterselected  li{' . $single_filter_inactive_css . '}' .
                '.rfilterselected  ul {' . $single_filter_container_css . '}' .
                '.rfilterselected  ul li.checked {' . $single_filter_active_css . '}' .
                '.rfilterselected  ul li:hover {' . $single_filter_hover_css . '}',
                dapfforwc_get_frontend_style_handle()
            );
            $output .= '<div class="' . esc_attr($class_name) . '">' . do_shortcode('[plugincy_filters_selected]') . '</div>';
            break;
    }

    return $output;
}



// creating blocks for elementor


/**
 * Check if Elementor is installed and active.
 *
 * @return bool True if Elementor is active, false otherwise.
 */
function dapfforwc_is_elementor_active()
{
    return defined('ELEMENTOR_VERSION');
}

/**
 * Register the custom Elementor widget if Elementor is active.
 */
function dapfforwc_register_dynamic_ajax_filter_widget_elementor()
{
    if (! dapfforwc_is_elementor_active() || ! class_exists('WooCommerce') || ! function_exists('wc_get_attribute_taxonomies')) {
        return;
    }

    // Define the custom widget class
    class Dapfforwc_Dynamic_Ajax_Filter_Widget extends \Elementor\Widget_Base
    {

        public function get_attribute()
        {
            global $dapfforwc_advance_settings;
            // Ensure WooCommerce functions are available
            if (! function_exists('wc_get_attribute_taxonomies')) {
                return [];
            }

            $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
            $all_attributes = isset($all_data['attributes']) ? $all_data['attributes'] : [];
            $exclude_attributes = isset($dapfforwc_advance_settings['exclude_attributes']) ? array_map('trim', explode(',', $dapfforwc_advance_settings['exclude_attributes'])) : [];
            $attributes = [];
            foreach ($all_attributes as $attribute) {
                if (in_array($attribute['attribute_name'], $exclude_attributes)) {
                    continue;
                }
                $attributes[] = (object) [
                    'attribute_name' => $attribute['attribute_name'],
                    'attribute_label' => $attribute['attribute_label'],
                ];
            }
            $options = [
                'search_text' => esc_html__('Search Attributes', 'dynamic-ajax-product-filters-for-woocommerce'),
                'rating' => esc_html__('Rating', 'dynamic-ajax-product-filters-for-woocommerce'),
                'price-range' => esc_html__('Price', 'dynamic-ajax-product-filters-for-woocommerce'),
                "category" => esc_html__("Category", 'dynamic-ajax-product-filters-for-woocommerce'),
                'tag' => esc_html__('Tag', 'dynamic-ajax-product-filters-for-woocommerce'),
                'brands' => esc_html__('Brands', 'dynamic-ajax-product-filters-for-woocommerce'),
                'authors' => esc_html__('Authors', 'dynamic-ajax-product-filters-for-woocommerce'),
                'status' => esc_html__('Stock Status', 'dynamic-ajax-product-filters-for-woocommerce'),
                'sale_status' => esc_html__('Sale Status', 'dynamic-ajax-product-filters-for-woocommerce'),
                'dimensions' => esc_html__('Dimensions', 'dynamic-ajax-product-filters-for-woocommerce'),
                'sku' => esc_html__('SKU', 'dynamic-ajax-product-filters-for-woocommerce'),
                'discount' => esc_html__('Discount', 'dynamic-ajax-product-filters-for-woocommerce'),
                'date_filter' => esc_html__('Date Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
            ];

            // Add attributes to options. Elementor prints select option labels unescaped, so escape them like the fixed labels above.
            foreach ($attributes as $attribute) {
                $options[$attribute->attribute_name] = esc_html($attribute->attribute_label);
            }

            $default = [];

            // One row per option; $options already contains every attribute.
            foreach ($options as $key => $label) {
                $default[] = [
                    'element_type' => $key,
                    'element_visible' => 'yes',
                ];
            }

            return ['options' => $options, 'default' => $default];
        }

        public function get_name()
        {
            return 'dynamic_ajax_filter';
        }

        public function get_title()
        {
            return esc_html__('Dynamic Ajax Filter', 'dynamic-ajax-product-filters-for-woocommerce');
        }

        public function get_icon()
        {
            return 'eicon-taxonomy-filter';
        }

        public function get_categories()
        {
            return ['plugincy'];
        }

        protected function register_controls()
        {

            // Content Tab: Filter Options
            $this->start_controls_section(
                'filter_options',
                [
                    'label' => esc_html__('Filter Options', 'dynamic-ajax-product-filters-for-woocommerce'),
                ]
            );

            $this->add_control(
                'filter_type',
                [
                    'label'   => esc_html__('Select Filter Type', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'all'      => esc_html__('All Filters', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'single'   => esc_html__('Single Filter', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'selected' => esc_html__('Selected Filters', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'all',
                ]
            );

            $this->add_control(
                'product_selector',
                [
                    'label' => esc_html__('Product Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_control(
                'pagination_selector',
                [
                    'label' => esc_html__('Pagination Selector', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );
            $this->add_control(
                'redirect_shop',
                [
                    'label'   => esc_html__('Redirect When Products Missing (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'no'  => esc_html__('No (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'yes' => esc_html__('Yes (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'no',
                    'description' => esc_html__('Redirect filter actions to the shop page when this widget is used on a page without products.', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                    'pro' => true,
                ]
            );

            $this->add_control(
                'filter_layout',
                [
                    'label'   => esc_html__('Select Filter Layout', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'sidebar'      => esc_html__('Sidebar Layout', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'top_view'      => esc_html__('Top View Layout', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'top_view_wrap' => esc_html__('Top View Wrap Layout (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'popup'         => esc_html__('Popup Layout (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'popup_horizontal_wrap' => esc_html__('Popup Horizontal Wrap Layout (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'drawer'        => esc_html__('Drawer Layout (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'sidebar',
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                    'pro' => true,
                ]
            );
            $this->add_control(
                'drawer_position',
                [
                    'label'   => esc_html__('Drawer Side (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'left'  => esc_html__('Left', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'right' => esc_html__('Right', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'right',
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );
            $this->add_control(
                'collapsable',
                [
                    'label'   => esc_html__('Collapsable Filters (Pro)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'no'  => esc_html__('No', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'yes' => esc_html__('Yes', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'no',
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );
            // $this->add_control(
            //     'use_custom_template_design',
            //     [
            //         'label'   => esc_html__('Select Filter Type', 'dynamic-ajax-product-filters-for-woocommerce'),
            //         'type'    => \Elementor\Controls_Manager::SELECT,
            //         'options' => [
            //             'yes'      => esc_html__('Yes', 'dynamic-ajax-product-filters-for-woocommerce'),
            //             'no'   => esc_html__('No', 'dynamic-ajax-product-filters-for-woocommerce')                        
            //         ],
            //         'default' => 'no',
            //     ]
            // );

            $this->add_control(
                'filter_name',
                [
                    'label' => esc_html__('attribute id', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'condition' => [
                        'filter_type' => 'single',
                    ],
                ]
            );


            $this->end_controls_section();

            $this->start_controls_section(
                'Database_query_section',
                [
                    'label' => esc_html__('Database Query', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_control(
                'database_query_note',
                [
                    'type'            => \Elementor\Controls_Manager::RAW_HTML,
                    'raw'             => esc_html__(
                        'Our plugin will automatically detect your query. If the automatic detection is incorrect and displays irrelevant filter options, please configure the database accordingly.',
                        'dynamic-ajax-product-filters-for-woocommerce'
                    ),
                    // Optional: Elementor’s panel alert styling classes
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                ]
            );

            $this->add_control(
                'category',
                [
                    'label' => esc_html__('Category', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'description' => esc_html__('Use Category slugs, comma separated.', 'dynamic-ajax-product-filters-for-woocommerce'),
                ]
            );
            $this->add_control(
                'tag',
                [
                    'label' => esc_html__('Tag', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'description' => esc_html__('Use Tag slugs, comma separated.', 'dynamic-ajax-product-filters-for-woocommerce'),
                ]
            );
            $this->add_control(
                'attribute',
                [
                    'label' => esc_html__('Attribute', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                ]
            );
            $this->add_control(
                'attributeTerms',
                [
                    'label' => esc_html__('Attribute Terms', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'description' => esc_html__('Use Attribute terms slugs, comma separated.', 'dynamic-ajax-product-filters-for-woocommerce'),
                ]
            );

            $this->end_controls_section();

            $allattribute = $this->get_attribute();

            $this->start_controls_section(
                'form_manage_section',
                [
                    'label' => esc_html__('Form Manage', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_control(
                'form_elements',
                [
                    'label' => esc_html__('Manage order & show/hide', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::REPEATER,
                    'prevent_empty' => false,
                    'fields' => [
                        [
                            'name' => 'element_type',
                            'label' => esc_html__('Element Type', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'type' => \Elementor\Controls_Manager::SELECT,
                            'default' => "category",
                            'options' => $allattribute['options'],
                        ],
                        [
                            'name' => 'element_visible',
                            'label' => esc_html__('Visible', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'type' => \Elementor\Controls_Manager::SWITCHER,
                            'label_on' => esc_html__('Show', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'label_off' => esc_html__('Hide', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'return_value' => 'yes',
                            'default' => 'yes',
                        ],
                    ],
                    'default' => $allattribute['default'],
                    // Show the option label (already escaped above) instead of the raw key; unknown keys are printed escaped.
                    'title_field' => '<# var dapfforwcElementLabels = ' . wp_json_encode($allattribute['options']) . '; #>{{{ dapfforwcElementLabels[element_type] || "" }}}{{ dapfforwcElementLabels[element_type] ? "" : element_type }}',
                ]
            );

            $this->end_controls_section();

            $this->start_controls_section(
                'mobile_responsive_style_section',
                [
                    'label' => esc_html__('Mobile Responsive Style', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_control(
                'mobile_responsive_style',
                [
                    'label'   => esc_html__('Choose style', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    // '' = the global Mobile Filter Style setting (Advanced settings), else style_4.
                    'options' => array_merge(
                        ['' => esc_html__('Use global setting', 'dynamic-ajax-product-filters-for-woocommerce')],
                        dapfforwc_get_mobile_responsive_styles()
                    ),
                    'default' => '',
                ]
            );

            $this->end_controls_section();



            // Style Tab: Form Styles

            $this->start_controls_section(
                'filters_mobile_style_section',
                [
                    'label' => esc_html__('Filters Word (Mobile)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                        'mobile_responsive_style' => ['', 'style_1'],
                    ],
                ]
            );

            $this->add_control(
                'filters_word_mode',
                [
                    'label'       => esc_html__('Filters Word (Mobile)', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'        => \Elementor\Controls_Manager::SELECT,
                    'default'     => 'show',
                    'options'     => [
                        'show' => esc_html__('Show', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'hide' => esc_html__('Hide', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    // This appends a class like "dafilter-word--show" or "dafilter-word--hide" to the widget wrapper
                    'prefix_class' => 'dafilter-word--',
                ]
            );



            $this->end_controls_section();

            // Form styles
            $this->start_controls_section(
                'form_style_section',
                [
                    'label' => esc_html__('Form Styles', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_responsive_control(
                'form_background',
                [
                    'label'     => esc_html__('Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '.plugincy_filter_wrapper' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
            $this->add_responsive_control(
                'form_border_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        '.plugincy_filter_wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'form_padding',
                [
                    'label'      => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        '.plugincy_filter_wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'form_margin',
                [
                    'label'      => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        '.plugincy_filter_wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'      => 'form_box_shadow',
                    'label'     => esc_html__('Box Shadow', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector'  => '.plugincy_filter_wrapper',
                ]
            );

            $this->add_responsive_control(
                'form_height',
                [
                    'label'      => esc_html__('Form Height', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'em', 'vh'],
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                        ],
                        '%'  => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'em' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                        'vh' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'selectors'  => [
                        '.plugincy_filter_wrapper' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );


            $this->end_controls_section();

            // widget container style
            $this->start_controls_section(
                'container_style_section',
                [
                    'label' => esc_html__('Container Styles', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_control(
                'container_background',
                [
                    'label'     => esc_html__('Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '#product-filter .plugincy-filter-group' => 'background-color: {{VALUE}} !important;',
                    ],
                ]
            );
            $this->add_responsive_control(
                'container_border_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        '#product-filter .plugincy-filter-group' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'container_padding',
                [
                    'label'      => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        '#product-filter .plugincy-filter-group' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'container_margin',
                [
                    'label'      => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        '#product-filter .plugincy-filter-group' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name'      => 'container_box_shadow',
                    'label'     => esc_html__('Box Shadow', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector'  => '#product-filter .plugincy-filter-group',
                ]
            );

            $this->add_control(
                'container_overflow',
                [
                    'label'     => esc_html__('Overflow', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::SELECT,
                    'options'   => [
                        'visible' => esc_html__('Visible', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'hidden'  => esc_html__('Hidden', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'scroll'  => esc_html__('Scroll', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'auto'    => esc_html__('Auto', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default'   => 'visible',
                    'selectors' => [
                        '#product-filter .plugincy-filter-group' => 'overflow: {{VALUE}};',
                    ],
                ]
            );

            $this->end_controls_section();


            // Style Tab: Widget Title (All Filters)
            $this->start_controls_section(
                'title_styles',
                [
                    'label' => esc_html__('Widget Title Styles', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Background::get_type(),
                [
                    'name'     => 'widget_title_background',
                    'label'    => esc_html__('Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'types'    => ['classic', 'gradient'],
                    'selector' => '{{WRAPPER}} .plugincy-filter-group .plugincy_title',
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'widget_title_typography',
                    'label' => esc_html__('Typography', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => '{{WRAPPER}} .plugincy-filter-group .plugincy_title',
                ]
            );

            $this->add_control(
                'widget_title_color',
                [
                    'label' => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .plugincy-filter-group .plugincy_title' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_title_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        '{{WRAPPER}} .plugincy-filter-group .plugincy_title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_title_alignment',
                [
                    'label' => esc_html__('Text Align', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        'left' => [
                            'title' => esc_html__('Left', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'icon' => 'eicon-text-align-left',
                        ],
                        'center' => [
                            'title' => esc_html__('Center', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'icon' => 'eicon-text-align-center',
                        ],
                        'space-between' => [
                            'title' => esc_html__('space-between', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'icon' => 'eicon-justify-space-between-h',
                        ],
                        'right' => [
                            'title' => esc_html__('Right', 'dynamic-ajax-product-filters-for-woocommerce'),
                            'icon' => 'eicon-text-align-right',
                        ],
                    ],
                    // The title is a flex container: justify-content does the alignment. "space-between" is not a text-align value.
                    'selectors_dictionary' => [
                        'left' => 'text-align: left !important; justify-content: left !important;',
                        'center' => 'text-align: center !important; justify-content: center !important;',
                        'space-between' => 'justify-content: space-between !important;',
                        'right' => 'text-align: right !important; justify-content: right !important;',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .plugincy-filter-group .plugincy_title' => '{{VALUE}}',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_title_padding',
                [
                    'label' => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .plugincy-filter-group .plugincy_title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_title_margin',
                [
                    'label' => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .plugincy-filter-group .plugincy_title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->end_controls_section();

            // Style Tab: Widget Items (All Filters)
            $this->start_controls_section(
                'items_styles',
                [
                    'label' => esc_html__('Widget Items Styles', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_items_background_color',
                [
                    'label' => esc_html__('Background Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .items' => 'background: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'widget_items_typography',
                    'label' => esc_html__('Typography', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => '{{WRAPPER}} .items label',
                ]
            );

            $this->add_control(
                'widget_items_color',
                [
                    'label' => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .items label, .price-input span,.price-input .separator' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_items_padding',
                [
                    'label' => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .items' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_items_margin',
                [
                    'label' => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .items' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_items_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        '{{WRAPPER}} .items' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'widget_items_gap',
                [
                    'label' => esc_html__('Gap', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'selectors' => [
                        '{{WRAPPER}} .items' => 'display: flex !important; flex-direction: column; gap: {{SIZE}}{{UNIT}} !important;',
                    ],
                ]
            );


            $this->end_controls_section();

            // button style

            $this->start_controls_section(
                'section_button_style',
                [
                    'label' => esc_html__('Button Style', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Background::get_type(),
                [
                    'name'     => 'button_background',
                    'label'    => esc_html__('Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'types'    => ['classic', 'gradient'],
                    'selector' => '{{WRAPPER}} form#product-filter button',
                ]
            );

            $this->add_control(
                'button_text_color',
                [
                    'label'     => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} form#product-filter button' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'button_hover_background',
                [
                    'label'     => esc_html__('Hover Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} form#product-filter button:hover' => 'background-color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'button_hover_text_color',
                [
                    'label'     => esc_html__('Hover Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} form#product-filter button:hover' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'button_border',
                    'label'    => esc_html__('Border', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => '{{WRAPPER}} form#product-filter button',
                ]
            );

            $this->add_responsive_control(
                'button_padding',
                [
                    'label'      => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        '{{WRAPPER}} form#product-filter button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'button_margin',
                [
                    'label'      => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        '{{WRAPPER}} form#product-filter button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );
            $this->add_responsive_control(
                'button_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        '{{WRAPPER}} form#product-filter button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->end_controls_section();

            // rating style
            $this->start_controls_section(
                'section_rating_style',
                [
                    'label' => esc_html__('Rating Style', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            $this->add_responsive_control(
                'rating_size',
                [
                    'label'      => esc_html__('Rating Size', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', '%'],
                    'range'      => [
                        'px' => [
                            'min' => 10,
                            'max' => 100,
                        ],
                    ],
                    'selectors'  => [
                        '{{WRAPPER}} .dynamic-rating  label:before, .items.rating svg' => 'width: {{SIZE}}{{UNIT}} !important;',
                    ],
                ]
            );
            $this->add_control(
                'rating_inactive_color',
                [
                    'label'     => esc_html__('Inactive Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-rating label, .items.rating svg' => 'fill: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'rating_active_color',
                [
                    'label'     => esc_html__('Active Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-rating  input:checked + label:hover,{{WRAPPER}} .dynamic-rating  input:checked ~ label:hover,{{WRAPPER}} .dynamic-rating  label:hover ~ input:checked ~ label,{{WRAPPER}} .dynamic-rating  input:checked ~ label:hover ~ label, .items.rating input:checked  + .plugincy-stars svg' => 'fill: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_control(
                'rating_hover_color',
                [
                    'label'     => esc_html__('Hover Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-rating input:checked ~ label,{{WRAPPER}} .dynamic-rating:not(:checked) label:hover,{{WRAPPER}} .dynamic-rating:not(:checked) label:hover ~ label, .items.rating input:hover  + .plugincy-stars svg' => 'fill: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'rating_gap',
                [
                    'label'      => esc_html__('Gap', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', '%'],
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                    ],
                    'selectors'  => [
                        '{{WRAPPER}} .dynamic-rating  label:before, .items.rating svg ' => 'margin: {{SIZE}}{{UNIT}} !important;',
                    ],
                ]
            );

            $this->end_controls_section();

            // reset button style
            $this->start_controls_section(
                'section_reset_button_style',
                [
                    'label' => esc_html__('Reset Button Style', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            // Background color
            $this->add_group_control(
                \Elementor\Group_Control_Background::get_type(),
                [
                    'name'     => 'reset_button_background',
                    'label'    => esc_html__('Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'types'    => ['classic', 'gradient'],
                    'selector' => 'form#product-filter span.reset-value',
                ]
            );
            // Inside the Reset Button Style section
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name'     => 'reset_button_typography',
                    'label'    => esc_html__('Typography', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => 'form#product-filter span.reset-value',
                ]
            );


            // Text color
            $this->add_control(
                'reset_button_text_color',
                [
                    'label'     => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        'form#product-filter span.reset-value' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );


            // Hover background color
            $this->add_control(
                'reset_button_hover_background',
                [
                    'label'     => esc_html__('Hover Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        'form#product-filter span.reset-value:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            // Hover text color
            $this->add_control(
                'reset_button_hover_text_color',
                [
                    'label'     => esc_html__('Hover Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        'form#product-filter span.reset-value:hover' => 'color: {{VALUE}}!important;',
                    ],
                ]
            );

            // Border
            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'reset_button_border',
                    'label'    => esc_html__('Border', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => 'form#product-filter span.reset-value',
                ]
            );

            // Padding
            $this->add_responsive_control(
                'reset_button_padding',
                [
                    'label'      => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        'form#product-filter span.reset-value' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            // Margin
            $this->add_responsive_control(
                'reset_button_margin',
                [
                    'label'      => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        'form#product-filter span.reset-value' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();

            // input style
            $this->start_controls_section(
                'section_input_style',
                [
                    'label' => esc_html__('Input Style', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            // Background color
            $this->add_control(
                'input_background_color',
                [
                    'label'     => esc_html__('Background Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        'form#product-filter input[type="search"], form#product-filter input[type="number"]' => 'background-color: {{VALUE}};',
                    ],
                ]
            );

            // Text color
            $this->add_control(
                'input_text_color',
                [
                    'label'     => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        'form#product-filter input[type="search"], form#product-filter input[type="number"]' => 'color: {{VALUE}};',
                    ],
                ]
            );

            // Padding
            $this->add_responsive_control(
                'input_padding',
                [
                    'label'      => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        'form#product-filter input[type="search"], form#product-filter input[type="number"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            // Margin
            $this->add_responsive_control(
                'input_margin',
                [
                    'label'      => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors'  => [
                        'form#product-filter input[type="search"], form#product-filter input[type="number"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            // Border radius
            $this->add_responsive_control(
                'input_border_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        'form#product-filter input[type="search"], form#product-filter input[type="number"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            // Border
            $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name'     => 'input_border',
                    'label'    => esc_html__('Border', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => 'form#product-filter input[type="search"], form#product-filter input[type="number"]',
                ]
            );

            $this->end_controls_section();
            // price slider
            $this->start_controls_section(
                'slider_style',
                [
                    'label' => esc_html__('Slider', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'all',
                    ],
                ]
            );

            // Slider background
            $this->add_control(
                'slider_background',
                [
                    'label'     => esc_html__('Slider Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'default'   => '',
                    'selectors' => [
                        '{{WRAPPER}} #product-filter .plugincy_slider' => 'background: {{VALUE}} !important;',
                    ],
                ]
            );

            // Slider border radius
            $this->add_control(
                'slider_border_radius',
                [
                    'label'      => esc_html__('Slider Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                    ],
                    'default'    => [
                        'size' => 5,
                    ],
                    'selectors'  => [
                        '{{WRAPPER}} #product-filter .plugincy_slider' => 'border-radius: {{SIZE}}px !important;',
                    ],
                ]
            );

            // plugrogress bar background
            $this->add_control(
                'progress_background',
                [
                    'label'     => esc_html__('progress Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'default'   => '',
                    'selectors' => [
                        '{{WRAPPER}} #product-filter .plugincy_slider .plugrogress' => 'background: {{VALUE}} !important;',
                    ],
                ]
            );

            // plugrogress bar border radius
            $this->add_control(
                'progress_border_radius',
                [
                    'label'      => esc_html__('plugrogress Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                    ],
                    'default'    => [
                        'size' => 5,
                    ],
                    'selectors'  => [
                        '{{WRAPPER}} #product-filter .plugincy_slider .plugrogress' => 'border-radius: {{SIZE}}px !important;',
                    ],
                ]
            );

            // Thumb margin
            $this->add_responsive_control(
                'thumb_margin',
                [
                    'label' => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} #product-filter input[type="range"]::-webkit-slider-thumb' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                        '{{WRAPPER}} #product-filter input[type="range"]::-moz-range-thumb'    => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
            );

            // Thumb width
            $this->add_control(
                'thumb_width',
                [
                    'label'      => esc_html__('Thumb Size', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'range'      => [
                        'px' => [
                            'min' => 5,
                            'max' => 50,
                        ],
                    ],
                    'default'    => [],
                    'selectors'  => [
                        '{{WRAPPER}} #product-filter input[type="range"]::-webkit-slider-thumb' => 'width: {{SIZE}}px; height: {{SIZE}}px !important;',
                        '{{WRAPPER}} #product-filter input[type="range"]::-moz-range-thumb' => 'width: {{SIZE}}px; height: {{SIZE}}px !important;',
                    ],
                ]
            );

            // Thumb background
            $this->add_control(
                'thumb_background',
                [
                    'label'     => esc_html__('Thumb Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'default'   => '',
                    'selectors' => [
                        '{{WRAPPER}} #product-filter input[type="range"]::-webkit-slider-thumb' => 'background:{{VALUE}} !important;',
                        '{{WRAPPER}} #product-filter input[type="range"]::-moz-range-thumb' => 'background: {{VALUE}} !important;',
                    ],
                ]
            );

            // Tooltip background
            $this->add_control(
                'tooltip_background',
                [
                    'label'     => esc_html__('Tooltip Background', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::COLOR,
                    'default'   => '',
                    'selectors' => [
                        '{{WRAPPER}} #product-filter .plugrogress-percentage:before, {{WRAPPER}} #product-filter .plugrogress-percentage:after' => 'background: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->end_controls_section();




            // Style Tab: Active & Inactive Items (Single Filter)
            $this->start_controls_section(
                'single_filter_styles',
                [
                    'label' => esc_html__('Single Filter Styles', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'single',
                    ],
                ]
            );

            $this->add_control(
                'inactive_item_background',
                [
                    'label' => esc_html__('Inactive Background Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons li' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
            $this->add_control(
                'active_item_background',
                [
                    'label' => esc_html__('Active Background Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul li.checked' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
            $this->add_control(
                'active_item_color',
                [
                    'label' => esc_html__('Active Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul li.checked label' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );


            $this->add_control(
                'inactive_item_hover_color',
                [
                    'label' => esc_html__('Inactive Hover Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons li:hover' => 'color: {{VALUE}}',
                        '{{WRAPPER}} .rfilterbuttons li:hover label' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'inactive_item_typography',
                    'label' => esc_html__('Typography', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => '{{WRAPPER}} .rfilterbuttons ul li',
                ]
            );

            $this->add_control(
                'inactive_item_color',
                [
                    'label' => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul li label' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'inactive_item_padding',
                [
                    'label' => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'inactive_item_margin',
                [
                    'label' => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul li' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );
            $this->add_responsive_control(
                'inactive_item_gap',
                [
                    'label' => esc_html__('Gap', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul' => 'gap: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'inactive_item_overflow',
                [
                    'label' => esc_html__('Overflow', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'visible' => esc_html__('Visible', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'hidden'  => esc_html__('Hidden', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'scroll'  => esc_html__('Scroll', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'auto'    => esc_html__('Auto', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'visible',
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul' => 'overflow-x: {{VALUE}};overflow-y: hidden;',
                    ],
                ]
            );
            $this->add_control(
                'inactive_item_flex_wrap',
                [
                    'label' => esc_html__('Flex Wrap', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'nowrap'  => esc_html__('No Wrap', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'wrap'    => esc_html__('Wrap', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'wrap-reverse' => esc_html__('Wrap Reverse', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'wrap',
                    'selectors' => [
                        '{{WRAPPER}} .rfilterbuttons ul' => 'flex-wrap: {{VALUE}};',
                    ],
                ]
            );

            $this->end_controls_section();

            // Style Tab: Selected Filters (Selected Filter)
            $this->start_controls_section(
                'selected_filter_styles',
                [
                    'label' => esc_html__('Selected Filter Styles', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'filter_type' => 'selected',
                    ],
                ]
            );

            $this->add_control(
                'selected_filter_background',
                [
                    'label' => esc_html__('Background Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected ul li.checked' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'selected_filter_typography',
                    'label' => esc_html__('Typography', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'selector' => '{{WRAPPER}} .rfilterselected ul li.checked',
                ]
            );

            $this->add_control(
                'selected_filter_color',
                [
                    'label' => esc_html__('Text Color', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected ul li.checked label' => 'color: {{VALUE}} !important;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_padding',
                [
                    'label' => esc_html__('Padding', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected ul li.checked' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_margin',
                [
                    'label' => esc_html__('Margin', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected ul' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );
            $this->add_responsive_control(
                'selected_filter_radius',
                [
                    'label'      => esc_html__('Border Radius', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors'  => [
                        '.rfilterselected li' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );
            $this->add_responsive_control(
                'selected_filter_gap',
                [
                    'label' => esc_html__('Gap', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected ul' => 'gap: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'selected_filter_overflow',
                [
                    'label' => esc_html__('Overflow', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'visible' => esc_html__('Visible', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'hidden'  => esc_html__('Hidden', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'scroll'  => esc_html__('Scroll', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'auto'    => esc_html__('Auto', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'visible',
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected>div' => 'overflow-x: {{VALUE}};overflow-y: hidden;',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_height',
                [
                    'label'      => esc_html__('Height', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'em', 'vh'],
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                        ],
                        '%'  => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'em' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                        'vh' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'selectors'  => [
                        '{{WRAPPER}} .rfilterselected>div' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );
            $this->add_control(
                'selected_filter_flex_wrap',
                [
                    'label' => esc_html__('Flex Wrap', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'nowrap'  => esc_html__('No Wrap', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'wrap'    => esc_html__('Wrap', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'wrap-reverse' => esc_html__('Wrap Reverse', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'wrap',
                    'selectors' => [
                        '{{WRAPPER}} .rfilterselected ul' => 'flex-wrap: {{VALUE}};',
                    ],
                ]
            );

            // position manage

            $this->add_control(
                'selected_filter_position',
                [
                    'label'   => esc_html__('Position', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'default'  => esc_html__('Default', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'absolute' => esc_html__('Absolute', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'fixed'    => esc_html__('Fixed', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'sticky'   => esc_html__('Sticky', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'default' => 'default',
                    'selectors' => [
                        '{{WRAPPER}}' => 'position: {{VALUE}};',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_orientation',
                [
                    'label'      => esc_html__('Vertical Orientation', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SELECT,
                    'options'    => [
                        'top'    => esc_html__('Top', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'bottom' => esc_html__('Bottom', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'condition'  => [
                        'selected_filter_position!' => 'default',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_offset',
                [
                    'label'      => esc_html__('Vertical Offset', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'em', 'vh'],
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                        ],
                    ],
                    'default'    => [
                        'unit' => 'px',
                        'size' => 0,
                    ],
                    'selectors'  => [
                        '{{WRAPPER}}' => '{{selected_filter_orientation.VALUE}}: {{SIZE}}{{UNIT}};',
                    ],
                    'condition'  => [
                        'selected_filter_position!' => 'default',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_horizontal_orientation',
                [
                    'label'      => esc_html__('Horizontal Orientation', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SELECT,
                    'options'    => [
                        'left'  => esc_html__('Left', 'dynamic-ajax-product-filters-for-woocommerce'),
                        'right' => esc_html__('Right', 'dynamic-ajax-product-filters-for-woocommerce'),
                    ],
                    'condition'  => [
                        'selected_filter_position!' => 'default',
                    ],
                ]
            );

            $this->add_responsive_control(
                'selected_filter_horizontal_offset',
                [
                    'label'      => esc_html__('Horizontal Offset', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'       => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'em', 'vw'],
                    'range'      => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                        ],
                    ],
                    'default'    => [
                        'unit' => 'px',
                        'size' => 0,
                    ],
                    'selectors'  => [
                        '{{WRAPPER}}' => '{{selected_filter_horizontal_orientation.VALUE}}: {{SIZE}}{{UNIT}};',
                    ],
                    'condition'  => [
                        'selected_filter_position!' => 'default',
                    ],
                ]
            );

            $this->add_control(
                'selected_filter_z_index',
                [
                    'label'     => esc_html__('Z-Index', 'dynamic-ajax-product-filters-for-woocommerce'),
                    'type'      => \Elementor\Controls_Manager::NUMBER,
                    'default'   => '',
                    'selectors' => [
                        '{{WRAPPER}}' => 'z-index: {{VALUE}};',
                    ]
                ]
            );


            $this->end_controls_section();
        }


        protected function render()
        {
            global $dapfforwc_allowed_tags;
            $settings = $this->get_settings_for_display();
            $output = '';

            if (!empty($settings['form_elements'])) {
                $element_ordering_css = 'form#product-filter { display: flex ; flex-direction: column; }form#product-filter .plugincy-filter-group {order: 999;}';
                foreach ($settings['form_elements'] as $index => $element) {
                    $element_type = sanitize_html_class($element['element_type']);
                    $is_visible = $element['element_visible'] === 'yes' ? "block" : "none";
                    $order = $index + 1;
                    $element_ordering_css .= '.' . $element_type . '{ display: ' . $is_visible . ' !important; order: ' . $order . ' !important; }';
                }
                dapfforwc_add_inline_style($element_ordering_css, dapfforwc_get_frontend_style_handle());
            }

            switch ($settings['filter_type']) {
                case 'all':
                    $product_selector = dapfforwc_esc_shortcode_selector_attr($settings['product_selector']);
                    $pagination_selector = dapfforwc_esc_shortcode_selector_attr($settings['pagination_selector']);
                    $db_categories = esc_attr($settings['category']);
                    $db_tags = esc_attr($settings['tag']);
                    $db_attribute = esc_attr($settings['attribute']);
                    $db_attributeTerms = esc_attr($settings['attributeTerms']);
                    $use_custom_template_design = isset($settings['use_custom_template_design']) ? esc_attr($settings['use_custom_template_design']) : '';
                    $per_page =  isset($settings['per_page']) ? esc_attr($settings['per_page']) : '';
                    // '' (Use global setting) is resolved by the shortcode: global Mobile Filter Style, else style_4.
                    $mobile_responsive_style = isset($settings['mobile_responsive_style']) ? esc_attr($settings['mobile_responsive_style']) : '';
                    $filter_layout = esc_attr($settings['filter_layout']);
                    $output .= do_shortcode("[plugincy_filters layout=\"$filter_layout\" use_custom_template_design=\"$use_custom_template_design\" mobile_responsive=\"$mobile_responsive_style\"  product_selector=\"$product_selector\" pagination_selector=\"$pagination_selector\" per_page=\"$per_page\"  category=\"$db_categories\" tag=\"$db_tags\" attribute=\"$db_attribute\" terms=\"$db_attributeTerms\"]");
                    break;

                case 'single':
                    $filter_name = esc_attr($settings['filter_name']);
                    $output .= do_shortcode("[plugincy_filters_single name=\"$filter_name\"]");
                    break;

                case 'selected':
                    $output .= do_shortcode("[plugincy_filters_selected]");
                    break;
            }

            echo dapfforwc_kses($output, $dapfforwc_allowed_tags); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- dapfforwc_kses() is wp_kses() with the plugin allow-list.
        }
    }

    add_action('elementor/widgets/register', function ($widgets_manager) {

        if (! defined('ELEMENTOR_VERSION') || ! class_exists('WooCommerce')) {
            return;
        }

        // Widget class must be loaded before this (preferably outside)
        $widgets_manager->register(new \Dapfforwc_Dynamic_Ajax_Filter_Widget());
    });
}
add_action('elementor/init', 'dapfforwc_register_dynamic_ajax_filter_widget_elementor', 100);
