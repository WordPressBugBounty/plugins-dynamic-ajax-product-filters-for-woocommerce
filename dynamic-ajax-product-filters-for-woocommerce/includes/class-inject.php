<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce Custom Class Injection System - Enhanced Version
 * Adds consistent custom classes to WooCommerce elements for reliable filtering
 * Compatible with more themes and handles edge cases better
 * Now includes support for WooCommerce block templates
 */

class dapfforwc_Custom_Class_Injector
{

    private $custom_classes = array(
        'products' => 'plugincy-filter-products',
        'pagination' => 'plugincy-filter-pagination',
        'orderby' => 'plugincy-filter-orderby',
        'result_count' => 'plugincy-filter-result-count',
        'product_item' => 'plugincy-filter-product-item'
    );

    private $is_capturing_pagination = false;

    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize all hooks
     */
    private function init_hooks()
    {
        // Products loop classes - multiple approaches for better compatibility
        add_filter('woocommerce_product_loop_start', array($this, 'add_products_wrapper_class'), 10, 2);
        add_filter('woocommerce_shortcode_products_container_classes', array($this, 'add_shortcode_container_classes'));

        // Product item classes
        add_filter('woocommerce_post_class', array($this, 'add_product_item_class'), 10, 2);
        add_filter('post_class', array($this, 'add_product_item_class_fallback'), 10, 3);

        // Pagination classes - multiple hooks for different implementations
        add_filter('woocommerce_pagination_args', array($this, 'add_pagination_class'));
        add_action('woocommerce_after_shop_loop', array($this, 'inject_pagination_class'), 30);
        add_action('init', array($this, 'inject_pagination_class_early'));
        add_action('init', array($this, 'modify_pagination_html'));


        // Capture the HTML WooCommerce prints at priority 10
        add_action('woocommerce_after_shop_loop', array($this, 'start_pagination_capture'), 9);
        add_action('woocommerce_after_shop_loop', array($this, 'end_pagination_capture'), 11);


        // Orderby dropdown classes
        add_action('woocommerce_before_shop_loop', array($this, 'start_orderby_capture'), 19);
        add_action('woocommerce_before_shop_loop', array($this, 'end_orderby_capture'), 21);

        // Result count classes
        add_filter('woocommerce_result_count', array($this, 'add_result_count_class'));
        add_action('woocommerce_before_shop_loop', array($this, 'inject_result_count_class'), 25);

        // WooCommerce Block Template support - only server-side rendering
        add_filter('render_block', array($this, 'add_block_template_class'), 10, 2);
    }

    private function get_advanced_selector_setting($key, $default)
    {
        $advanced_settings = get_option('dapfforwc_advance_options');

        if (!is_array($advanced_settings) || !isset($advanced_settings[$key])) {
            return $default;
        }

        $value = trim((string) $advanced_settings[$key]);

        return $value !== '' ? $value : $default;
    }

    /**
     * Improved products wrapper class addition
     */
    public function add_products_wrapper_class($html, $wc_get_template_part_args = array())
    {
        // More robust class injection
        $class_to_add = $this->custom_classes['products'];

        // Handle different HTML structures
        if (preg_match('/<(ul|div|section)([^>]*?)class=(["\'])([^"\']*?)\3([^>]*?)>/i', $html, $matches)) {
            $existing_classes = $matches[4];
            $new_classes = $existing_classes . ' ' . $class_to_add;
            $html = str_replace($matches[0], '<' . $matches[1] . $matches[2] . 'class=' . $matches[3] . $new_classes . $matches[3] . $matches[5] . '>', $html);
        } elseif (preg_match('/<(ul|div|section)([^>]*?)>/i', $html, $matches)) {
            $html = str_replace($matches[0], '<' . $matches[1] . ' class="' . $class_to_add . '"' . $matches[2] . '>', $html);
        }

        return $html;
    }

    /**
     * Add class to WooCommerce block templates during render
     */
    public function add_block_template_class($block_content, $block)
    {
        // Check if it's a WooCommerce product template block
        if (isset($block['blockName']) && $block['blockName'] === 'woocommerce/product-template') {
            $class_to_add = $this->custom_classes['products'];

            // Add class to elements with data-block-name="woocommerce/product-template"
            $block_content = preg_replace_callback(
                '/<(ul|div|section)([^>]*?)data-block-name=["\']woocommerce\/product-template["\']([^>]*?)>/i',
                function ($matches) use ($class_to_add) {
                    $element = $matches[1];
                    $before_attrs = $matches[2];
                    $after_attrs = $matches[3];

                    // Check if class attribute exists
                    if (preg_match('/class=(["\'])([^"\']*?)\1/', $before_attrs . $after_attrs, $class_matches)) {
                        // Add to existing class
                        $existing_classes = $class_matches[2];
                        $new_classes = trim($existing_classes . ' ' . $class_to_add);
                        $full_attrs = $before_attrs . 'data-block-name="woocommerce/product-template"' . $after_attrs;
                        $full_attrs = preg_replace('/class=(["\'])([^"\']*?)\1/', 'class=$1' . $new_classes . '$1', $full_attrs);
                        return '<' . $element . $full_attrs . '>';
                    } else {
                        // Add new class attribute
                        return '<' . $element . $before_attrs . 'class="' . $class_to_add . '" data-block-name="woocommerce/product-template"' . $after_attrs . '>';
                    }
                },
                $block_content
            );
        }

        return $block_content;
    }

    /**
     * Add classes to shortcode container
     */
    public function add_shortcode_container_classes($classes)
    {
        if (!is_array($classes)) {
            $classes = array();
        }
        $classes[] = $this->custom_classes['products'];
        return $classes;
    }

    /**
     * Add custom class to individual product items (excluding body tag)
     */
    public function add_product_item_class($classes, $product = null)
    {
        // Only add class in WooCommerce contexts and not on body tag
        if ((is_woocommerce() || (is_object($product) && is_a($product, 'WC_Product'))) && !doing_filter('body_class')) {
            $classes[] = $this->custom_classes['product_item'];
        }
        return $classes;
    }

    /**
     * Fallback for themes that don't use woocommerce_post_class (excluding body tag)
     */
    public function add_product_item_class_fallback($classes, $css_class, $post_id)
    {
        // Only add class for product post types and not on body tag
        if (get_post_type($post_id) === 'product' && !doing_filter('body_class')) {
            $classes[] = $this->custom_classes['product_item'];
        }
        return $classes;
    }

    /**
     * Add custom class to pagination
     */
    public function add_pagination_class($args)
    {
        if (!isset($args['class'])) {
            $args['class'] = '';
        }
        $args['class'] = trim($args['class'] . ' ' . $this->custom_classes['pagination']);

        // Also add to base class if not present
        if (!isset($args['base_class'])) {
            $args['base_class'] = 'woocommerce-pagination';
        }
        $args['base_class'] = trim($args['base_class'] . ' ' . $this->custom_classes['pagination']);

        return $args;
    }

    /**
     * PHP-only pagination capture start (before Woo prints pagination at priority 10)
     */
    public function start_pagination_capture()
    {
        if (is_woocommerce() && !is_singular('product') && !$this->is_capturing_pagination) {
            $this->is_capturing_pagination = true;
            ob_start();
        }
    }

    /**
     * PHP-only pagination capture end & rewrite
     */
    public function end_pagination_capture()
    {
        global $dapfforwc_allowed_tags;
        if (!$this->is_capturing_pagination) {
            return;
        }

        $html = ob_get_clean();
        $this->is_capturing_pagination = false;

        $class = esc_attr($this->custom_classes['pagination']);

        // 1) Add class to <nav ...> wrapper (WooCommerce default has <nav class="woocommerce-pagination">)
        $html = preg_replace_callback(
            '/<nav([^>]*?)class=(["\'])([^"\']*?)\2([^>]*)>/i',
            function ($m) use ($class) {
                $existing = $m[3];
                if (stripos($existing, $class) === false) {
                    $existing = trim($existing . ' ' . $class);
                }
                return '<nav' . $m[1] . 'class=' . $m[2] . $existing . $m[2] . $m[4] . '>';
            },
            $html
        );

        // If no class attr at all on <nav>, add it
        $html = preg_replace(
            '/<nav(?![^>]*\bclass=)([^>]*)>/i',
            '<nav class="' . $class . '"$1>',
            $html
        );

        // 2) Add class to <ul class="page-numbers"> list (paginate_links type=list)
        $html = preg_replace_callback(
            '/<ul([^>]*?)class=(["\'])([^"\']*?\bpage-numbers\b[^"\']*?)\2([^>]*)>/i',
            function ($m) use ($class) {
                $existing = $m[3];
                if (stripos($existing, $class) === false) {
                    $existing = trim($existing . ' ' . $class);
                }
                return '<ul' . $m[1] . 'class=' . $m[2] . $existing . $m[2] . $m[4] . '>';
            },
            $html
        );

        // If a theme prints a <div class="pagination"> or similar — be defensive
        $html = preg_replace_callback(
            '/<(div|nav)([^>]*?)class=(["\'])([^"\']*?\bpagination\b[^"\']*?)\3([^>]*)>/i',
            function ($m) use ($class) {
                $existing = $m[4];
                if (stripos($existing, $class) === false) {
                    $existing = trim($existing . ' ' . $class);
                }
                return '<' . $m[1] . $m[2] . 'class=' . $m[3] . $existing . $m[3] . $m[5] . '>';
            },
            $html
        );

        echo wp_kses($html, $dapfforwc_allowed_tags);
    }


    /**
     * JavaScript injection for pagination (fallback)
     */
    public function inject_pagination_class()
    {
        if (is_woocommerce()) {
            $result_count_selector = $this->get_advanced_selector_setting('result_count_selector', '.woocommerce-result-count');
            $pagination_class = $this->custom_classes['pagination'];
            dapfforwc_add_inline_script('
                jQuery(document).ready(function($) {
                    // Target multiple possible pagination selectors
                    var paginationSelectors = [
                        ".woocommerce-pagination:not(header .woocommerce-pagination)",
                        ".woocommerce nav.navigation:not(header nav)",
                        ".woocommerce .navigation:not(header .navigation)",
                        ".pagination:not(header .pagination)",
                        ".page-numbers:not(header .page-numbers)",
                        ".nav-links:not(header .nav-links)",
                        ".woocommerce .paginate_links:not(header .paginate_links)",
                        ".products + .navigation",
                        "' . esc_js($result_count_selector) . ' + .navigation",
                        ".wc-block-pagination:not(header .wc-block-pagination)"
                    ];
                    
                    $.each(paginationSelectors, function(index, selector) {
                        $(selector).addClass("' . esc_js($pagination_class) . '");
                    });
                    
                    // Also target parent containers that might contain pagination
                    $(".woocommerce .navigation:not(header .navigation)").parent().addClass("' . esc_js($pagination_class) . '-container");
                });
            ', 'urlfilter-ajax');
        }
    }

    public function inject_pagination_class_early()
    {
        add_action('wp_footer', function () {
            if (is_woocommerce()) {
                $pagination_class = $this->custom_classes['pagination'];
                dapfforwc_add_inline_script('
                    jQuery(document).ready(function($) {
                        // Wait for AJAX updates and late-loading pagination
                        setTimeout(function() {
                            $(".woocommerce-pagination:not(header .woocommerce-pagination), .woocommerce nav.navigation:not(header nav), .pagination:not(header .pagination)").addClass("' . esc_js($pagination_class) . '");
                        }, 100);
                    });
                ', 'urlfilter-ajax');
            }
        });
    }

    public function modify_pagination_html()
    {
        // Adds our class into the markup template used by some core helpers.
        add_filter('navigation_markup_template', array($this, 'add_pagination_class_to_template'), 10, 2);

        // Handles WP page links (rare in product loops but harmless)
        add_filter('wp_link_pages', array($this, 'add_class_to_wp_link_pages'));

        // If your site runs WP 6.1+, also catch core paginate_links filter (when available)
        if (has_filter('paginate_links_output') === false && function_exists('add_filter')) {
            // Not all versions have paginate_links_output; safely add if exists
            if (function_exists('paginate_links')) {
                add_filter('paginate_links_output', array($this, 'add_class_to_paginate_links_output'), 10, 2);
            }
        }
    }

    public function add_pagination_class_to_template($template, $class)
    {
        if (is_woocommerce()) {
            $custom_class = $this->custom_classes['pagination'];
            // Add our custom class to the navigation wrapper
            $template = str_replace('class="navigation %1$s"', 'class="navigation %1$s ' . $custom_class . '"', $template);
        }
        return $template;
    }

    public function add_class_to_wp_link_pages($output)
    {
        if (is_woocommerce() && !empty($output)) {
            $custom_class = $this->custom_classes['pagination'];
            // Add class to pagination containers
            $output = preg_replace(
                '/<div([^>]*?)class=(["\'])([^"\']*?)\2([^>]*?)>/i',
                '<div$1class=$2$3 ' . $custom_class . '$2$4>',
                $output,
                1
            );
        }
        return $output;
    }


    /**
     * Attach class to paginate_links output (if filter is present in this WP version)
     */
    public function add_class_to_paginate_links_output($output, $args)
    {
        $custom = esc_attr($this->custom_classes['pagination']);

        // UL (type=list)
        $output = preg_replace_callback(
            '/<ul([^>]*?)class=(["\'])([^"\']*?\bpage-numbers\b[^"\']*?)\2([^>]*)>/i',
            function ($m) use ($custom) {
                $existing = $m[3];
                if (stripos($existing, $custom) === false) {
                    $existing = trim($existing . ' ' . $custom);
                }
                return '<ul' . $m[1] . 'class=' . $m[2] . $existing . $m[2] . $m[4] . '>';
            },
            $output
        );

        // Nav wrapper (some themes wrap paginate_links in nav)
        $output = preg_replace_callback(
            '/<nav([^>]*?)class=(["\'])([^"\']*?)\2([^>]*)>/i',
            function ($m) use ($custom) {
                $existing = $m[3];
                if (stripos($existing, $custom) === false) {
                    $existing = trim($existing . ' ' . $custom);
                }
                return '<nav' . $m[1] . 'class=' . $m[2] . $existing . $m[2] . $m[4] . '>';
            },
            $output
        );

        return $output;
    }


    /**
     * Capture and modify orderby dropdown
     */
    public function start_orderby_capture()
    {
        ob_start();
    }

    public function end_orderby_capture()
    {
        global $dapfforwc_allowed_tags;

        $content = ob_get_contents();
        ob_end_clean();

        // Add class to orderby form
        $content = preg_replace(
            '/<form([^>]*?)class=(["\'])([^"\']*?)\2([^>]*?)>/i',
            '<form$1class=$2$3 ' . $this->custom_classes['orderby'] . '$2$4>',
            $content
        );

        // If no class exists, add one
        if (strpos($content, 'class=') === false) {
            $content = str_replace('<form', '<form class="' . $this->custom_classes['orderby'] . '"', $content);
        }

        echo wp_kses($content, $dapfforwc_allowed_tags);
    }

    /**
     * Add custom class to result count
     */
    public function add_result_count_class($html)
    {
        $class_to_add = $this->custom_classes['result_count'];

        // More robust class injection
        if (preg_match('/<p([^>]*?)class=(["\'])([^"\']*?)\2([^>]*?)>/i', $html, $matches)) {
            $existing_classes = $matches[3];
            $new_classes = $existing_classes . ' ' . $class_to_add;
            $html = str_replace($matches[0], '<p' . $matches[1] . 'class=' . $matches[2] . $new_classes . $matches[2] . $matches[4] . '>', $html);
        } elseif (preg_match('/<p([^>]*?)>/i', $html, $matches)) {
            $html = str_replace($matches[0], '<p class="' . $class_to_add . '"' . $matches[1] . '>', $html);
        }

        return $html;
    }

    /**
     * JavaScript fallback for result count
     */
    public function inject_result_count_class()
    {
        if (is_woocommerce()) {
            $result_count_selector = $this->get_advanced_selector_setting('result_count_selector', '.woocommerce-result-count');
            dapfforwc_add_inline_script('
                jQuery(document).ready(function($) {
                    $("' . esc_js($result_count_selector) . '").addClass("' . esc_attr($this->custom_classes['result_count']) . '");
                });
            ', 'urlfilter-ajax');
        }
    }


    /**
     * Get custom classes array (for external access)
     */
    public function get_custom_classes()
    {
        return $this->custom_classes;
    }
}

// Initialize the class
new dapfforwc_Custom_Class_Injector();










/**
 * Universal Pagination Class Injector (PHP-only)
 * - Robust across Woo templates, Core Query Pagination, and Woo Blocks
 * - Injects a custom class into both wrapper and list container (if present)
 * - Safe to run alongside your existing product classes injection
 */

if (!class_exists('dapfforwc_Pagination_Normalizer')) {

    class dapfforwc_Pagination_Normalizer
    {
        private $custom_class = 'plugincy-filter-pagination';

        public function __construct($custom_class = null)
        {
            if (!empty($custom_class)) {
                $this->custom_class = sanitize_html_class($custom_class);
            }
            $this->init_hooks();
        }

        private function init_hooks()
        {
            // 1) WooCommerce templates (classic) – capture what Woo prints and normalize it
            add_action('woocommerce_after_shop_loop', [$this, 'capture_start'], 9);
            add_action('woocommerce_after_shop_loop', [$this, 'capture_end'], 11);

            // 2) Woo args (helps when themes respect args->class / base_class)
            add_filter('woocommerce_pagination_args', [$this, 'filter_woocommerce_pagination_args']);

            // 3) Core navigation template (used by many themes/wrappers)
            add_filter('navigation_markup_template', [$this, 'filter_navigation_template'], 10, 2);

            // 4) WP paginate_links() string filters (cover many generators)
            // Some WP versions provide these; add conditionally.
            if (has_filter('paginate_links_output') === false) {
                add_filter('paginate_links_output', [$this, 'filter_paginate_links_output'], 10, 2);
            }

            // 5) Block themes (FSE): Core Query Pagination + Woo Blocks pagination
            add_filter('render_block', [$this, 'filter_render_block'], 10, 2);
        }

        /* ------------------------------
         * (1) Capture Woo template output
         * ------------------------------ */
        public function capture_start()
        {
            if (function_exists('is_woocommerce') && is_woocommerce() && !is_singular('product')) {
                ob_start();
            }
        }

        public function capture_end()
        {
            global $dapfforwc_allowed_tags;
            if (!function_exists('is_woocommerce') || !is_woocommerce() || is_singular('product')) {
                return;
            }
            $html = ob_get_clean();
            echo wp_kses($this->inject_into_any_pagination($html), $dapfforwc_allowed_tags);
        }

        /* ----------------------------------------------
         * (2) Help Woo by adding classes via args early
         * ---------------------------------------------- */
        public function filter_woocommerce_pagination_args($args)
        {
            $cls = $this->custom_class;

            if (empty($args['class'])) {
                $args['class'] = $cls;
            } elseif (stripos($args['class'], $cls) === false) {
                $args['class'] .= ' ' . $cls;
            }

            // Base wrapper class some themes read
            if (empty($args['base_class'])) {
                $args['base_class'] = 'woocommerce-pagination ' . $cls;
            } elseif (stripos($args['base_class'], $cls) === false) {
                $args['base_class'] .= ' ' . $cls;
            }

            return $args;
        }

        /* ------------------------------------------------------
         * (3) Core nav wrappers used by posts/product paginations
         * ------------------------------------------------------ */
        public function filter_navigation_template($template, $class)
        {
            $cls = esc_attr($this->custom_class);
            // Ensure our class appears on the core wrapper nav
            $template = preg_replace(
                '/class="navigation\s*%1\$s"/',
                'class="navigation %1$s ' . $cls . '"',
                $template
            );
            return $template;
        }

        /* ---------------------------------------------------------
         * (4) Paginate links HTML string filters (defensive rewrite)
         * --------------------------------------------------------- */
        public function filter_paginate_links_output($output, $args)
        {
            return $this->inject_into_any_pagination($output);
        }

        /* --------------------------------------------------------
         * (5) Block themes: Core Query Pagination & Woo Blocks
         * -------------------------------------------------------- */
        public function filter_render_block($block_content, $block)
        {
            if (empty($block['blockName']) || empty($block_content)) {
                return $block_content;
            }

            $name = $block['blockName'];

            // Core Query Pagination
            if ($name === 'core/query-pagination') {
                return $this->inject_into_any_pagination($block_content);
            }

            // Woo Blocks (names may vary by version)
            // Common: woocommerce/product-collection-pagination, woocommerce/pagination
            if (strpos($name, 'woocommerce/') === 0 && stripos($name, 'pagination') !== false) {
                return $this->inject_into_any_pagination($block_content);
            }

            return $block_content;
        }

        /* ==========================================================
         * Core logic: Inject class into ANY plausible pagination HTML
         * ========================================================== */
        private function inject_into_any_pagination($html)
        {
            if (empty($html) || !is_string($html)) {
                return $html;
            }

            $cls = esc_attr($this->custom_class);

            // Quick check: only proceed if markup smells like pagination.
            if (
                stripos($html, 'page-numbers') === false &&
                stripos($html, 'pagination') === false &&
                stripos($html, 'nav-links') === false &&
                stripos($html, 'wc-block-pagination') === false &&
                stripos($html, 'wp-block-query-pagination') === false &&
                stripos($html, 'woocommerce-pagination') === false
            ) {
                // As a fallback, still try if there are page links with rel next/prev
                if (stripos($html, 'rel="next"') === false &&
                    stripos($html, 'rel="prev"') === false &&
                    stripos($html, 'aria-label="Next"') === false &&
                    stripos($html, 'aria-label="Previous"') === false
                ) {
                    return $html;
                }
            }

            $out = $html;

            // 1) Add class to the OUTER WRAPPER if it's a nav/div/section that looks like pagination
            $out = preg_replace_callback(
                '/<(nav|div|section)([^>]*?)class=(["\'])([^"\']*?)\3([^>]*)>/i',
                function ($m) use ($cls) {
                    $existing = $m[4];
                    if (
                        stripos($existing, 'pagination') !== false ||
                        stripos($existing, 'page-numbers') !== false ||
                        stripos($existing, 'nav-links') !== false ||
                        stripos($existing, 'wc-block-pagination') !== false ||
                        stripos($existing, 'wp-block-query-pagination') !== false ||
                        stripos($existing, 'woocommerce-pagination') !== false
                    ) {
                        if (stripos($existing, $cls) === false) {
                            $existing = trim($existing . ' ' . $cls);
                        }
                        return '<' . $m[1] . $m[2] . 'class=' . $m[3] . $existing . $m[3] . $m[5] . '>';
                    }
                    return $m[0];
                },
                $out
            );

            // If wrapper has NO class, but is clearly a pagination container (has page items inside), add class
            $out = preg_replace(
                '/<(nav|div|section)(?![^>]*\bclass=)([^>]*)>(?=[\s\S]*?\bpage-numbers\b|[\s\S]*?\bnav-links\b|[\s\S]*?\bwp-block-query-pagination\b|[\s\S]*?\bwc-block-pagination\b)/i',
                '<$1 class="' . $cls . '"$2>',
                $out
            );

            // 2) Add class to the LIST container <ul|ol> when it’s a page number list
            $out = preg_replace_callback(
                '/<(ul|ol)([^>]*?)class=(["\'])([^"\']*?)\3([^>]*)>/i',
                function ($m) use ($cls) {
                    $existing = $m[4];
                    if (
                        stripos($existing, 'page-numbers') !== false ||
                        stripos($existing, 'pagination') !== false ||
                        stripos($existing, 'nav-links') !== false
                    ) {
                        if (stripos($existing, $cls) === false) {
                            $existing = trim($existing . ' ' . $cls);
                        }
                        return '<' . $m[1] . $m[2] . 'class=' . $m[3] . $existing . $m[3] . $m[5] . '>';
                    }
                    return $m[0];
                },
                $out
            );

            // If <ul>/<ol> has no class but contains page items inside, add class
            $out = preg_replace(
                '/<(ul|ol)(?![^>]*\bclass=)([^>]*)>(?=[\s\S]*?\bpage-numbers\b|[\s\S]*?\bnav-links\b)/i',
                '<$1 class="' . $cls . '"$2>',
                $out
            );

            return $out;
        }
    }
}
/**
 * Instantiate with your desired class. It’s safe to keep your existing product injection class as-is.
 * You can also pass a different class if needed: new dapfforwc_Pagination_Normalizer('my-custom-class');
 */
new dapfforwc_Pagination_Normalizer('plugincy-filter-pagination');
