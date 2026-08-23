<?php
if (!defined('ABSPATH')) {
    exit;
}

class DAPFFORWC_WC_Query_Filter_Enhanced
{

    private static $instance = null;
    private $nonce_valid = null;
    private $filter_params = null;
    private $rating_style = null;
    private $third_party_hooks_initialized = false;
    private $dimension_product_ids = array();
    private $category_alias_request_values = array();

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        if (did_action('init')) {
            $this->capture_category_alias_request();
        }
        add_action('init', array($this, 'capture_category_alias_request'), 0);
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'init_elementor_hooks'));
    }

    private function get_style_operator($filter_key, $fallback = 'IN')
    {
        global $dapfforwc_styleoptions;

        if (is_array($fallback)) {
            $fallback = 'IN';
        }

        $fallback = strtoupper((string) $fallback);
        if ($fallback === 'OR') {
            $fallback = 'IN';
        }
        if ($fallback === 'AND') {
            return 'AND';
        }

        $operator = $dapfforwc_styleoptions['operator'][$filter_key] ?? $fallback;
        if (is_array($operator)) {
            $operator = $fallback;
        }

        return strtoupper((string) $operator) === 'AND' ? 'AND' : 'IN';
    }

    public function init()
    {
        // Hook into pre_get_posts to modify queries
        add_action('pre_get_posts', array($this, 'modify_product_query'));

        // Hook into WooCommerce product query
        add_filter('woocommerce_product_query_meta_query', array($this, 'modify_wc_query'), 10, 2);

        // Hook into main query for shop/archive pages
        add_action('woocommerce_product_query', array($this, 'modify_wc_product_query'));

        // Hook into WooCommerce shortcodes
        add_filter('woocommerce_shortcode_products_query', array($this, 'filter_shortcode_products_query'), 10, 2);

        // Hook into WooCommerce REST API
        add_filter('woocommerce_rest_product_object_query', array($this, 'filter_rest_product_query'), 10, 2);

        // Hook into general WP_Query for products
        add_filter('posts_clauses', array($this, 'filter_posts_clauses'), 10, 2);

        // Hook into WooCommerce product data store
        add_filter('woocommerce_product_data_store_cpt_get_products_query', array($this, 'filter_data_store_query'), 10, 2);

        // AJAX handlers
        add_action('wp_ajax_woocommerce_get_products', array($this, 'ajax_handler'));
        add_action('wp_ajax_nopriv_woocommerce_get_products', array($this, 'ajax_handler'));
    }

    public function init_elementor_hooks()
    {
        // Hook into Elementor after it's loaded
        if (class_exists('\Elementor\Plugin')) {
            add_action('elementor/query/custom_query', array($this, 'elementor_custom_query'), 10, 2);
            add_filter('elementor_pro/woocommerce/query', array($this, 'elementor_woocommerce_query'), 10, 2);
        }

        // Hook into common third-party plugins
        $this->init_third_party_hooks();
    }

    public function init_third_party_hooks()
    {
        if ($this->third_party_hooks_initialized) {
            return;
        }

        $this->third_party_hooks_initialized = true;

        $third_party_enabled_default = defined('DAPFFORWC_ENABLE_THIRD_PARTY_HOOKS')
            ? (bool) DAPFFORWC_ENABLE_THIRD_PARTY_HOOKS
            : false;

        if (!apply_filters('dapfforwc_enable_third_party_hooks', $third_party_enabled_default)) {
            return;
        }

        // Hook into popular product grid plugins
        if (apply_filters('dapfforwc_enable_woo_product_grid_hook', true)) {
            add_filter('woo_product_grid_query_args', array($this, 'filter_product_grid_args'), 10, 2);
        }
        if (apply_filters('dapfforwc_enable_wc_product_table_hook', true)) {
            add_filter('wc_product_table_query_args', array($this, 'filter_product_grid_args'), 10, 2);
        }
        if (apply_filters('dapfforwc_enable_wcpgsk_hook', true)) {
            add_filter('wcpgsk_query_args', array($this, 'filter_product_grid_args'), 10, 2);
        }

        // Hook into WooCommerce Product Filter plugins
        if (apply_filters('dapfforwc_enable_wc_product_filter_hook', true)) {
            add_filter('woocommerce_product_filter_query_args', array($this, 'filter_product_grid_args'), 10, 2);
        }

        // Hook into JetEngine (Crocoblock)
        if (apply_filters('dapfforwc_enable_jet_engine_hook', true)) {
            add_filter('jet-engine/listing/grid/query-args', array($this, 'filter_jet_engine_query'), 10, 2);
        }

        // Hook into WooCommerce Product Blocks
        if (apply_filters('dapfforwc_enable_wc_blocks_hook', true)) {
            add_filter('woocommerce_blocks_product_grid_query_args', array($this, 'filter_product_grid_args'), 10, 2);
        }

        // Hook into custom post type queries
        if (apply_filters('dapfforwc_enable_parse_query_hook', true)) {
            add_action('parse_query', array($this, 'parse_custom_query'));
        }
    }

    /**
     * Modify the main WordPress query for products
     */
    public function modify_product_query($query)
    {
        // Only modify frontend queries and product queries
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Get all filter parameters
        $params = $this->get_filter_params();

        // If no parameters, return
        if (empty($params)) {
            return;
        }

        // Check if this is a product query
        if (
            $query->is_shop() || $query->is_product_category() || $query->is_product_tag() ||
            (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'product')
        ) {

            $this->apply_filters_to_query($query);
        }
    }

    /**
     * Modify WooCommerce specific product queries
     */
    public function modify_wc_product_query($query)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return;
        }

        $this->apply_filters_to_query($query);
    }

    /**
     * Modify WooCommerce meta query
     */
    public function modify_wc_query($meta_query, $query)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $meta_query;
        }

        // Apply taxonomy filters if we have the query object
        if ($query && method_exists($query, 'set')) {
            $this->apply_filters_to_query($query);
        }

        return $meta_query;
    }

    /**
     * Filter shortcode products query
     */
    public function filter_shortcode_products_query($args, $atts)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        return $this->apply_filters_to_args($args);
    }

    /**
     * Filter REST API product query
     */
    public function filter_rest_product_query($args, $request)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        return $this->apply_filters_to_args($args);
    }

    /**
     * Filter WooCommerce blocks query
     */
    public function filter_blocks_query($args, $attributes)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        return $this->apply_filters_to_args($args);
    }

    /**
     * Filter posts clauses for any product query
     */
    public function filter_posts_clauses($clauses, $query)
    {
        // Only apply to product queries
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            return $clauses;
        }

        $params = $this->get_filter_params();

        if (empty($params)) {
            return $clauses;
        }

        // Apply additional WHERE clauses if needed
        $additional_where = $this->get_additional_where_clauses($params);
        if (!empty($additional_where)) {
            $clauses['where'] .= ' AND ' . $additional_where;
        }

        return $clauses;
    }

    /**
     * Filter WooCommerce data store query
     */
    public function filter_data_store_query($args, $query_vars)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        return $this->apply_filters_to_args($args);
    }

    /**
     * Handle Elementor custom queries
     */
    public function elementor_custom_query($query, $widget)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return;
        }

        $this->apply_filters_to_query($query);
    }

    /**
     * Handle Elementor WooCommerce queries
     */
    public function elementor_woocommerce_query($query, $widget)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $query;
        }

        return $this->apply_filters_to_args($query);
    }

    /**
     * Filter product grid arguments
     */
    public function filter_product_grid_args($args, $settings = null)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        return $this->apply_filters_to_args($args);
    }

    /**
     * Filter JetEngine query
     */
    public function filter_jet_engine_query($args, $widget)
    {
        // Only apply to product queries
        if (!isset($args['post_type']) || $args['post_type'] !== 'product') {
            return $args;
        }

        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        return $this->apply_filters_to_args($args);
    }

    /**
     * Parse custom queries
     */
    public function parse_custom_query($query)
    {
        // Only apply to product queries
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            return;
        }

        $params = $this->get_filter_params();

        if (empty($params)) {
            return;
        }

        $this->apply_filters_to_query($query);
    }


    /**
     * Determine if the current request carries a verified nonce.
     */
    private function is_nonce_valid(): bool
    {
        if ($this->nonce_valid !== null) {
            return $this->nonce_valid;
        }

        if (!isset($_GET['gm-product-filter-nonce'])) {
            return $this->nonce_valid = false;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['gm-product-filter-nonce']));
        return $this->nonce_valid = (bool) wp_verify_nonce($nonce, 'gm-product-filter-action');
    }

    /**
     * Reset cached request state so we can re-evaluate parameters after mutation.
     */
    private function reset_request_cache()
    {
        $this->nonce_valid = null;
        $this->filter_params = null;
    }

    /**
     * Capture duplicate category aliases before themes/page builders read them.
     *
     * Some product-grid implementations treat product-category as their own query
     * argument before this plugin applies the WooCommerce product_cat filter. Keep
     * that value for DAPF parsing, but remove the raw public key so those
     * implementations cannot turn the same category selection into an empty native
     * query.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public storefront GET filters are read-only and shareable; normalize_filter_values() unslashes and sanitizes every value before use.
    public function capture_category_alias_request()
    {
        if ((is_admin() && !wp_doing_ajax()) || !$this->request_is_read_only()) {
            return;
        }

        if (empty($_GET['product-category'])) {
            return;
        }

        if ($this->product_category_query_targets_attribute()) {
            $this->capture_legacy_product_category_attribute_request();
            return;
        }

        $alias_keys = $this->get_category_alias_query_keys();
        $category_values = array();

        $category_values = array_merge($category_values, $this->normalize_filter_values($_GET['product-category']));

        foreach ($alias_keys as $alias_key) {
            if ($alias_key === 'product-category') {
                continue;
            }

            if (isset($_GET[$alias_key]) && $_GET[$alias_key] !== '') {
                $category_values = array_merge($category_values, $this->normalize_filter_values($_GET[$alias_key]));
            }
        }

        if (empty($category_values)) {
            return;
        }

        $this->category_alias_request_values = array_values(array_unique(array_merge(
            $this->category_alias_request_values,
            $category_values
        )));

        unset($_GET['product-category'], $_REQUEST['product-category']);
        $this->reset_request_cache();
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    /**
     * Keep legacy product-category=<attribute-term> URLs working without letting
     * themes or product-grid widgets mistake them for native product_cat queries.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public storefront GET filters are read-only and shareable; normalize_filter_values() unslashes and sanitizes every value before use.
    private function capture_legacy_product_category_attribute_request(string $attribute_name = '')
    {
        $attribute_name = $attribute_name !== '' ? sanitize_title($attribute_name) : $this->get_attribute_name_for_public_query_key('product-category');
        if ($attribute_name === '') {
            return;
        }

        $attribute_values = $this->normalize_filter_values($_GET['product-category']);
        if (empty($attribute_values)) {
            return;
        }

        $safe_query_key = $this->get_collision_safe_attribute_query_key($attribute_name, 'product-category');
        if ($safe_query_key === '') {
            return;
        }

        if (empty($_GET[$safe_query_key])) {
            $_GET[$safe_query_key] = implode(',', $attribute_values);
            $_REQUEST[$safe_query_key] = $_GET[$safe_query_key];
        }

        unset($_GET['product-category'], $_REQUEST['product-category']);
        $this->reset_request_cache();
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    /**
     * Determine if the current request is a safe, read-only GET.
     */
    private function request_is_read_only(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD'])
            ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])))
            : 'GET';
        return $method === 'GET';
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public storefront filter query vars are read-only and intentionally support shareable URLs. Non-GET requests without the AJAX nonce are rejected before this parser is used.
    /**
     * Check if the request contains any filter-related query vars.
     */
    private function has_filter_query_vars(): bool
    {
        $simple_keys = array(
            'product-category',
            'tags',
            'mn_price',
            'mx_price',
            'rating',
            'plugincy_search',
            'rcata',
            'rtag',
            'cat_operator',
            'tag_operator',
            'terms_operator',
            'brand_operator',
            'tax_relation',
            'meta_relation',
            'rplurand',
            'rplutock_status',
            'rpluthor',
            'rpn_sale',
            'min_length',
            'max_length',
            'min_width',
            'max_width',
            'min_height',
            'max_height',
            'min_weight',
            'max_weight',
            'sku',
            'product_id',
            'discount',
            'new_arrivals',
            'date_filter',
            'date_from',
            'date_to',
            'per_page',
            'orderby',
            'order',
            'filters',
            'price',
            'title',
            'cata'
        );
        $simple_keys = array_merge($simple_keys, $this->get_prefixed_query_keys());

        foreach ($simple_keys as $key) {
            if ((isset($_GET[$key]) && $_GET[$key] !== '') || (isset($_GET["{$key}[]"]) && $_GET["{$key}[]"] !== '')) {
                return true;
            }
        }

        foreach ($_GET as $key => $value) {
            if (($value === '' || $value === null)) {
                continue;
            }

            $normalized_key = preg_replace('/\[\]$/', '', $key);

            if (strpos($normalized_key, 'rplugpa_') === 0 || strpos($normalized_key, 'rplugcusf_') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply filters to query object
     */
    private function apply_filters_to_query($query)
    {
        if ($query->get('dapfforwc_filters_applied')) {
            return;
        }

        $params = $this->get_filter_params();

        if (empty($params)) {
            return;
        }

        if (!$this->is_nonce_valid() && !$this->request_is_read_only()) {
            return;
        }

        $query->set('dapfforwc_filters_applied', true);

        if (apply_filters('dapfforwc_disable_query_cache', true, $params, $query)) {
            $query->set('cache_results', false);
        }

        $tax_query = array();
        $meta_query = array();
        $cat_operator = $this->get_style_operator('product-category', $params['cat_operator'] ?? 'IN');
        if (is_product_category()) {
            $cat_operator = "AND";
        }
        $tag_operator = $this->get_style_operator('tag', $params['tag_operator'] ?? 'IN');
        if (is_product_tag()) {
            $tag_operator = "AND";
        }
        $terms_operator = isset($params['terms_operator']) ? $params['terms_operator'] : 'IN';
        if (dapfforwc_is_product_attribute()) {
            $terms_operator = "AND";
        }
        $brand_operator = $this->get_style_operator('brands', $params['brand_operator'] ?? 'IN');
        if (dapfforwc_is_product_brand()) {
            $brand_operator = "AND";
        }

        if (isset($params['per_page']) && $params['per_page'] > 0) {
            $query->set('posts_per_page', $params['per_page']);
        }
        if (isset($params['paged']) && $params['paged'] > 0) {
            $query->set('paged', absint($params['paged']));
        }
        $ordering_args = $this->resolve_ordering_args($params);
        foreach ($ordering_args as $key => $value) {
            $query->set($key, $value);
        }

        $requested_category_slugs = isset($params["product-category"])
            ? $this->normalize_filter_values($params["product-category"])
            : array();

        // Apply category filter
        if (!empty($requested_category_slugs)) {
            $categories = $requested_category_slugs;

            // WP ignores include_children when operator=AND on a single clause.
            // Split into multiple IN clauses so child terms are honoured.
            if ($cat_operator === 'AND' && count($categories) > 1) {
                $category_group = array('relation' => 'AND');

                foreach ($categories as $category_slug) {
                    $category_group[] = array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => array($category_slug),
                        'operator' => 'IN',
                        'include_children' => true,
                    );
                }

                $tax_query[] = $category_group;
            } else {
                $category_clause_operator = ($cat_operator === 'AND' && count($categories) === 1) ? 'IN' : $cat_operator;

                $category_clause = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $categories,
                    'operator' => $category_clause_operator,
                );

                if ($cat_operator === 'AND') {
                    $category_clause['include_children'] = true;
                }

                $tax_query[] = $category_clause;
            }
        }

        // Apply tag filter
        if (isset($params['tags']) && !empty($params['tags'])) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => $params['tags'],
                'operator' => $tag_operator,
            );
        }

        // Apply attribute filters
        if (isset($params['attributes']) && is_array($params['attributes'])) {
            foreach ($params['attributes'] as $attribute_name => $attribute_values) {
                if (!empty($attribute_values)) {
                    $tax_query[] = array(
                        'taxonomy' => 'pa_' . $attribute_name,
                        'field'    => 'slug',
                        'terms'    => $attribute_values,
                        'operator' => $this->get_style_operator($attribute_name, $terms_operator),
                    );
                }
            }
        }

        // Apply price range filter
        if ((isset($params['mn_price']) && $params['mn_price'] > 0) ||
            (isset($params['mx_price']) && $params['mx_price'] > 0)
        ) {
            $min = isset($params['mn_price']) ? floatval($params['mn_price']) : 0;
            $max = isset($params['mx_price']) ? floatval($params['mx_price']) : 0;

            if ($max > 0) {
                $meta_query[] = array(
                    'key'     => '_price',
                    'value'   => array($min, $max),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            } else {
                $meta_query[] = array(
                    'key'     => '_price',
                    'value'   => $min,
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        }

        // Apply rating filter
        if (isset($params['rating']) && !empty($params['rating'])) {
            $rating_meta_query = $this->build_rating_meta_query($params['rating']);
            if (!empty($rating_meta_query)) {
                $meta_query[] = $rating_meta_query;
            }
        }

        // Apply plugincy_search filter
        if (isset($params['plugincy_search']) && !empty($params['plugincy_search'])) {
            add_filter('posts_search', 'dapfforwc_search_by_title_only', 500, 2);
            $query->set('s', $params['plugincy_search']);
            $query->set('dapfforwc_search_post_title', true);
        }


        // Apply rplurand filter (assuming rplurand is a taxonomy)
        if (isset($params['rplurand']) && !empty($params['rplurand'])) {
            $brand_clause = $this->build_product_brand_tax_clause($params['rplurand'], $brand_operator);
            if (!empty($brand_clause)) {
                $tax_query[] = $brand_clause;
            }
        }

        // Apply stock status filter
        if (isset($params['rplutock_status']) && !empty($params['rplutock_status'])) {
            if ($this->get_style_operator('status') === 'AND' && count((array) $params['rplutock_status']) > 1) {
                $query->set('post__in', array(0));
            } else {
                $meta_query[] = array(
                    'key'     => '_stock_status',
                    'value'   => $params['rplutock_status'],
                    'compare' => 'IN',
                );
            }
        }

        // Apply rpluthor filter
        if (isset($params['rpluthor']) && !empty($params['rpluthor'])) {
            if ($this->get_style_operator('authors') === 'AND' && count((array) $params['rpluthor']) > 1) {
                $query->set('post__in', array(0));
            } else {
                $query->set('author__in', $params['rpluthor']);
            }
        }

        // Apply sale status filter (supports on sale and not on sale)
        $sale_filter = $this->get_sale_filter_flags($params);
        if (!$this->use_database_safe_mode() && ($sale_filter['onsale'] || $sale_filter['notonsale'])) {
            $sale_product_ids = $this->get_on_sale_product_filter_ids();

            if ($sale_filter['onsale'] && !$sale_filter['notonsale']) {
                // Limit to WooCommerce's sale lookup IDs so variable parents are included reliably.
                $post_in = $this->merge_post_in_lists($query->get('post__in'), $sale_product_ids);
                $query->set('post__in', !empty($post_in) ? $post_in : array(0));
            } elseif ($sale_filter['notonsale'] && !$sale_filter['onsale']) {
                // Exclude WooCommerce's sale lookup IDs for the inverse filter.
                if (!empty($sale_product_ids)) {
                    $query->set('post__not_in', $this->merge_post_not_in_lists($query->get('post__not_in'), $sale_product_ids));
                }
            }
            elseif ($sale_filter['onsale'] && $sale_filter['notonsale'] && $this->get_style_operator('sale_status') === 'AND') {
                $query->set('post__in', array(0));
            }
        }

        // Match dimensions against the effective dimension rows for each
        // product so variable parents only pass when a real variation satisfies
        // the full requested range.
        $dimension_filters = $this->get_dimension_filters($params);
        if (!$this->use_database_safe_mode() && !empty($dimension_filters)) {
            $dimension_product_ids = $this->get_product_ids_by_dimensions($dimension_filters);
            $post_in = $this->merge_post_in_lists($query->get('post__in'), $dimension_product_ids);
            $query->set('post__in', !empty($post_in) ? $post_in : array(0));
        }

        // Apply custom meta filters
        if (isset($params['custom_meta']) && is_array($params['custom_meta'])) {
            foreach ($params['custom_meta'] as $meta_key => $meta_values) {
                if (!empty($meta_values)) {
                    if ($this->get_style_operator($meta_key, $terms_operator) === 'AND' && count((array) $meta_values) > 1) {
                        $meta_group = array('relation' => 'AND');
                        foreach ((array) $meta_values as $meta_value) {
                            $meta_group[] = array(
                                'key'     => $meta_key,
                                'value'   => $meta_value,
                                'compare' => '=',
                            );
                        }
                        $meta_query[] = $meta_group;
                    } else {
                        $meta_query[] = array(
                            'key'     => $meta_key,
                            'value'   => $meta_values,
                            'compare' => 'IN',
                        );
                    }
                }
            }
        }

        // Apply SKU filter
        if (isset($params['sku']) && !empty($params['sku'])) {
            $meta_query[] = array(
                'key'     => '_sku',
                'value'   => $params['sku'],
                'compare' => 'IN',
            );
        }

        // Apply product ID filter
        if (isset($params['product_id']) && !empty($params['product_id'])) {
            $query->set('post__in', $params['product_id']);
        }

        // Apply discount filter
        if (!$this->use_database_safe_mode() && isset($params['discount']) && $params['discount'] > 0) {
            $discount_products = $this->get_products_with_discount($params['discount']);
            if (!empty($discount_products)) {
                $post_in = $this->merge_post_in_lists($query->get('post__in'), $discount_products);
                $query->set('post__in', !empty($post_in) ? $post_in : array(0));
            } else {
                $query->set('post__in', array(0)); // No products found
            }
        }

        // Apply new arrivals filter
        if (isset($params['new_arrivals']) && $params['new_arrivals'] > 0) {
            $date_from = gmdate('Y-m-d', strtotime('-' . $params['new_arrivals'] . ' days'));
            $query->set('date_query', array(
                array(
                    'after' => $date_from,
                    'inclusive' => true,
                ),
            ));
        }

        // Apply date filters
        if (isset($params['date_filter'])) {
            $date_query = $this->get_date_query($params);
            if (!empty($date_query)) {
                $query->set('date_query', $date_query);
            }
        }

        // Apply tax_query if we have any
        if (!empty($tax_query)) {
            $existing_tax_query = $query->get('tax_query');

            if (!empty($requested_category_slugs)) {
                $existing_tax_query = $this->remove_duplicate_category_alias_tax_clauses($existing_tax_query, $requested_category_slugs);
            }

            if (!empty($existing_tax_query)) {
                $tax_query = array_merge($existing_tax_query, $tax_query);
            }

            if (count($tax_query) > 1) {
                $tax_query['relation'] = $params['tax_relation'] ?? 'AND';
            }

            $query->set('tax_query', $tax_query);
        }

        // Apply meta_query if we have any
        if (!empty($meta_query)) {
            $existing_meta_query = $query->get('meta_query');

            if (!empty($existing_meta_query)) {
                $meta_query = array_merge($existing_meta_query, $meta_query);
            }

            if (count($meta_query) > 1) {
                $meta_query['relation'] = $params['meta_relation'] ?? 'AND';
            }

            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Apply filters to arguments array
     */
    private function apply_filters_to_args($args)
    {
        $params = $this->get_filter_params();

        if (empty($params)) {
            return $args;
        }

        if (!$this->is_nonce_valid() && !$this->request_is_read_only()) {
            return $args;
        }

        if (apply_filters('dapfforwc_disable_query_cache', true, $params, $args)) {
            $args['cache_results'] = false;
        }

        $tax_query = isset($args['tax_query']) ? $args['tax_query'] : array();
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : array();
        $requested_category_slugs = isset($params["product-category"])
            ? $this->normalize_filter_values($params["product-category"])
            : array();

        if (!empty($requested_category_slugs)) {
            $tax_query = $this->remove_duplicate_category_alias_tax_clauses($tax_query, $requested_category_slugs);
        }

        $cat_operator = $this->get_style_operator('product-category', $params['cat_operator'] ?? 'IN');
        if (is_product_category()) {
            $cat_operator = "AND";
        }
        $tag_operator = $this->get_style_operator('tag', $params['tag_operator'] ?? 'IN');
        if (is_product_tag()) {
            $tag_operator = "AND";
        }
        $terms_operator = isset($params['terms_operator']) ? $params['terms_operator'] : 'IN';
        if (dapfforwc_is_product_attribute()) {
            $terms_operator = "AND";
        }
        $brand_operator = $this->get_style_operator('brands', $params['brand_operator'] ?? 'IN');
        if (dapfforwc_is_product_brand()) {
            $brand_operator = "AND";
        }

        if (isset($params['per_page']) && $params['per_page'] > 0) {
            $args['posts_per_page'] = $params['per_page'];
        }
        if (isset($params['paged']) && $params['paged'] > 0) {
            $args['paged'] = absint($params['paged']);
        }
        $ordering_args = $this->resolve_ordering_args($params);
        foreach ($ordering_args as $key => $value) {
            $args[$key] = $value;
        }

        // Apply category filter
        if (!empty($requested_category_slugs)) {
            $categories = $requested_category_slugs;

            if ($cat_operator === 'AND' && count($categories) > 1) {
                $category_group = array('relation' => 'AND');

                foreach ($categories as $category_slug) {
                    $category_group[] = array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => array($category_slug),
                        'operator' => 'IN',
                        'include_children' => true,
                    );
                }

                $tax_query[] = $category_group;
            } else {
                $category_clause_operator = ($cat_operator === 'AND' && count($categories) === 1) ? 'IN' : $cat_operator;

                $category_clause = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $categories,
                    'operator' => $category_clause_operator,
                );

                if ($cat_operator === 'AND') {
                    $category_clause['include_children'] = true;
                }

                $tax_query[] = $category_clause;
            }
        }


        // Apply tag filter
        if (isset($params['tags']) && !empty($params['tags'])) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => $params['tags'],
                'operator' => $tag_operator,
            );
        }

        // Apply attribute filters
        if (isset($params['attributes']) && is_array($params['attributes'])) {
            foreach ($params['attributes'] as $attribute_name => $attribute_values) {
                if (!empty($attribute_values)) {
                    $tax_query[] = array(
                        'taxonomy' => 'pa_' . $attribute_name,
                        'field'    => 'slug',
                        'terms'    => $attribute_values,
                        'operator' => $this->get_style_operator($attribute_name, $terms_operator),
                    );
                }
            }
        }

        // Apply price range filter
        if ((isset($params['mn_price']) && $params['mn_price'] > 0) ||
            (isset($params['mx_price']) && $params['mx_price'] > 0)
        ) {
            $min = isset($params['mn_price']) ? floatval($params['mn_price']) : 0;
            $max = isset($params['mx_price']) ? floatval($params['mx_price']) : 0;

            if ($max > 0) {
                $meta_query[] = array(
                    'key'     => '_price',
                    'value'   => array($min, $max),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            } else {
                $meta_query[] = array(
                    'key'     => '_price',
                    'value'   => $min,
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        }

        // Apply rating filter
        if (isset($params['rating']) && !empty($params['rating'])) {
            $rating_meta_query = $this->build_rating_meta_query($params['rating']);
            if (!empty($rating_meta_query)) {
                $meta_query[] = $rating_meta_query;
            }
        }

        // Apply plugincy_search filter
        if (isset($params['plugincy_search']) && !empty($params['plugincy_search'])) {
            $args['s'] = $params['plugincy_search'];
            $args['dapfforwc_search_post_title'] = true;
        }


        // Apply rplurand filter
        if (isset($params['rplurand']) && !empty($params['rplurand'])) {
            $brand_clause = $this->build_product_brand_tax_clause($params['rplurand'], $brand_operator);
            if (!empty($brand_clause)) {
                $tax_query[] = $brand_clause;
            }
        }

        // Apply stock status filter
        if (isset($params['rplutock_status']) && !empty($params['rplutock_status'])) {
            if ($this->get_style_operator('status') === 'AND' && count((array) $params['rplutock_status']) > 1) {
                $args['post__in'] = array(0);
            } else {
                $meta_query[] = array(
                    'key'     => '_stock_status',
                    'value'   => $params['rplutock_status'],
                    'compare' => 'IN',
                );
            }
        }

        // Apply rpluthor filter
        if (isset($params['rpluthor']) && !empty($params['rpluthor'])) {
            if ($this->get_style_operator('authors') === 'AND' && count((array) $params['rpluthor']) > 1) {
                $args['post__in'] = array(0);
            } else {
                $args['author__in'] = $params['rpluthor'];
            }
        }

        // Apply sale status filter (supports on sale and not on sale)
        $sale_filter = $this->get_sale_filter_flags($params);
        if (!$this->use_database_safe_mode() && ($sale_filter['onsale'] || $sale_filter['notonsale'])) {
            $sale_product_ids = $this->get_on_sale_product_filter_ids();

            if ($sale_filter['onsale'] && !$sale_filter['notonsale']) {
                // Limit to WooCommerce's sale lookup IDs so variable parents are included reliably.
                $post_in = $this->merge_post_in_lists($args['post__in'] ?? array(), $sale_product_ids);
                $args['post__in'] = !empty($post_in) ? $post_in : array(0);
            } elseif ($sale_filter['notonsale'] && !$sale_filter['onsale']) {
                // Exclude WooCommerce's sale lookup IDs for the inverse filter.
                if (!empty($sale_product_ids)) {
                    // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Required for the explicit "not on sale" filter state.
                    $args['post__not_in'] = $this->merge_post_not_in_lists($args['post__not_in'] ?? array(), $sale_product_ids);
                }
            }
            elseif ($sale_filter['onsale'] && $sale_filter['notonsale'] && $this->get_style_operator('sale_status') === 'AND') {
                $args['post__in'] = array(0);
            }
        }

        // Match dimensions against effective variation dimensions so shortcode,
        // REST, and third-party grid queries stay aligned with archive behavior.
        $dimension_filters = $this->get_dimension_filters($params);
        if (!$this->use_database_safe_mode() && !empty($dimension_filters)) {
            $dimension_product_ids = $this->get_product_ids_by_dimensions($dimension_filters);
            $post_in = $this->merge_post_in_lists($args['post__in'] ?? array(), $dimension_product_ids);
            $args['post__in'] = !empty($post_in) ? $post_in : array(0);
        }

        // Apply custom meta filters
        if (isset($params['custom_meta']) && is_array($params['custom_meta'])) {
            foreach ($params['custom_meta'] as $meta_key => $meta_values) {
                if (!empty($meta_values)) {
                    if ($this->get_style_operator($meta_key, $terms_operator) === 'AND' && count((array) $meta_values) > 1) {
                        $meta_group = array('relation' => 'AND');
                        foreach ((array) $meta_values as $meta_value) {
                            $meta_group[] = array(
                                'key'     => $meta_key,
                                'value'   => $meta_value,
                                'compare' => '=',
                            );
                        }
                        $meta_query[] = $meta_group;
                    } else {
                        $meta_query[] = array(
                            'key'     => $meta_key,
                            'value'   => $meta_values,
                            'compare' => 'IN',
                        );
                    }
                }
            }
        }

        // Apply SKU filter
        if (isset($params['sku']) && !empty($params['sku'])) {
            $meta_query[] = array(
                'key'     => '_sku',
                'value'   => $params['sku'],
                'compare' => 'IN',
            );
        }

        // Apply product ID filter
        if (isset($params['product_id']) && !empty($params['product_id'])) {
            $args['post__in'] = $params['product_id'];
        }

        // Apply discount filter
        if (!$this->use_database_safe_mode() && isset($params['discount']) && $params['discount'] > 0) {
            $discount_products = $this->get_products_with_discount($params['discount']);
            if (!empty($discount_products)) {
                $post_in = $this->merge_post_in_lists($args['post__in'] ?? array(), $discount_products);
                $args['post__in'] = !empty($post_in) ? $post_in : array(0);
            } else {
                $args['post__in'] = array(0);
            }
        }


        // Apply new arrivals filter
        if (isset($params['new_arrivals']) && $params['new_arrivals'] > 0) {
            $date_from = gmdate('Y-m-d', strtotime('-' . $params['new_arrivals'] . ' days'));
            $args['date_query'] = array(
                array(
                    'after' => $date_from,
                    'inclusive' => true,
                ),
            );
        }

        // Apply date filters
        if (isset($params['date_filter'])) {
            $date_query = $this->get_date_query($params);
            if (!empty($date_query)) {
                $args['date_query'] = $date_query;
            }
        }

        // Apply queries
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = $params['tax_relation'] ?? 'AND';
            }
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- User-selected taxonomy filters require WP_Query tax constraints.
            $args['tax_query'] = $tax_query;
        }

        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = $params['meta_relation'] ?? 'AND';
            }
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- User-selected meta filters require WP_Query meta constraints.
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    /**
     * Get normalized on-sale product IDs for archive filtering.
     *
     * WooCommerce may return variation IDs here; map them back to the parent product
     * so product archive queries include variable products consistently.
     */
    private function get_on_sale_product_filter_ids(): array
    {
        $ids = array();

        foreach (wc_get_product_ids_on_sale() as $sale_id) {
            $product = wc_get_product($sale_id);
            if (!$product) {
                continue;
            }

            $target_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            if ($target_id) {
                $ids[] = $target_id;
            }
        }

        return $this->sanitize_id_list($ids);
    }

    private function get_dimension_filters($params): array
    {
        if (function_exists('dapfforwc_normalize_dimension_filters')) {
            return dapfforwc_normalize_dimension_filters(is_array($params) ? $params : array());
        }

        return array();
    }

    private function use_database_safe_mode(): bool
    {
        return function_exists('dapfforwc_use_large_catalog_database_mode') && dapfforwc_use_large_catalog_database_mode();
    }

    private function get_dimension_sql_condition(string $product_expression, array $dimension_filters, string $alias_prefix = ''): string
    {
        global $wpdb;

        if (empty($dimension_filters)) {
            return '';
        }

        $meta_aliases = array();
        $parent_conditions = array();
        $variation_conditions = array();

        foreach ($dimension_filters as $dimension => $range) {
            $dimension = sanitize_key($dimension);
            if (!in_array($dimension, array('length', 'width', 'height', 'weight'), true)) {
                continue;
            }

            $parent_alias = $alias_prefix . 'p_' . $dimension;
            $variation_alias = $alias_prefix . 'v_' . $dimension;
            $fallback_alias = $alias_prefix . 'vp_' . $dimension;
            $meta_key = '_' . $dimension;

            $meta_aliases[] = "LEFT JOIN {$wpdb->postmeta} {$parent_alias} ON {$parent_alias}.post_id = {$product_expression} AND {$parent_alias}.meta_key = '{$meta_key}'";

            $parent_value = "CAST({$parent_alias}.meta_value AS DECIMAL(20,6))";
            $variation_value = "CAST(COALESCE(NULLIF({$variation_alias}.meta_value, ''), NULLIF({$fallback_alias}.meta_value, '')) AS DECIMAL(20,6))";

            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- SQL aliases and CAST expressions are built from allowlisted dimension keys; range values remain prepared.
            if (isset($range['min']) && $range['min'] !== null) {
                $parent_conditions[] = $wpdb->prepare("{$parent_alias}.meta_value != '' AND {$parent_value} >= %f", (float) $range['min']);
                $variation_conditions[] = $wpdb->prepare("COALESCE(NULLIF({$variation_alias}.meta_value, ''), NULLIF({$fallback_alias}.meta_value, '')) != '' AND {$variation_value} >= %f", (float) $range['min']);
            }

            if (isset($range['max']) && $range['max'] !== null) {
                $parent_conditions[] = $wpdb->prepare("{$parent_alias}.meta_value != '' AND {$parent_value} <= %f", (float) $range['max']);
                $variation_conditions[] = $wpdb->prepare("COALESCE(NULLIF({$variation_alias}.meta_value, ''), NULLIF({$fallback_alias}.meta_value, '')) != '' AND {$variation_value} <= %f", (float) $range['max']);
            }
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        if (empty($parent_conditions) && empty($variation_conditions)) {
            return '';
        }

        $parent_exists = 'EXISTS (SELECT 1 FROM ' . $wpdb->posts . ' dp ' . implode(' ', $meta_aliases) . " WHERE dp.ID = {$product_expression} AND " . implode(' AND ', $parent_conditions) . ')';

        $variation_joins = array();
        foreach (array_keys($dimension_filters) as $dimension) {
            $dimension = sanitize_key($dimension);
            if (!in_array($dimension, array('length', 'width', 'height', 'weight'), true)) {
                continue;
            }

            $variation_alias = $alias_prefix . 'v_' . $dimension;
            $fallback_alias = $alias_prefix . 'vp_' . $dimension;
            $meta_key = '_' . $dimension;
            $variation_joins[] = "LEFT JOIN {$wpdb->postmeta} {$variation_alias} ON {$variation_alias}.post_id = dv.ID AND {$variation_alias}.meta_key = '{$meta_key}'";
            $variation_joins[] = "LEFT JOIN {$wpdb->postmeta} {$fallback_alias} ON {$fallback_alias}.post_id = {$product_expression} AND {$fallback_alias}.meta_key = '{$meta_key}'";
        }

        $variation_exists = 'EXISTS (SELECT 1 FROM ' . $wpdb->posts . ' dv ' . implode(' ', $variation_joins) . " WHERE dv.post_parent = {$product_expression} AND dv.post_type = 'product_variation' AND dv.post_status = 'publish' AND " . implode(' AND ', $variation_conditions) . ')';

        return '(' . $parent_exists . ' OR ' . $variation_exists . ')';
    }

    private function get_product_ids_by_dimensions(array $dimension_filters): array
    {
        if (empty($dimension_filters)) {
            return array();
        }

        if ($this->use_database_safe_mode()) {
            return array();
        }

        $cache_key = md5(wp_json_encode($dimension_filters));
        if (isset($this->dimension_product_ids[$cache_key])) {
            return $this->dimension_product_ids[$cache_key];
        }

        $product_data = function_exists('dapfforwc_get_woocommerce_product_details')
            ? dapfforwc_get_woocommerce_product_details()
            : array();
        $product_details = is_array($product_data['products'] ?? null) ? $product_data['products'] : array();
        $product_ids = array();

        foreach ($product_details as $product) {
            if (!is_array($product)) {
                continue;
            }

            if (
                function_exists('dapfforwc_product_matches_dimension_filters')
                && dapfforwc_product_matches_dimension_filters($product, $dimension_filters)
            ) {
                $product_ids[] = intval($product['ID'] ?? 0);
            }
        }

        return $this->dimension_product_ids[$cache_key] = $this->sanitize_id_list($product_ids);
    }


    /**
     * Helper method to get products with specific discount percentage
     */
    private function get_products_with_discount($min_discount)
    {
        $min_discount = floatval($min_discount);
        if ($min_discount <= 0) {
            return array();
        }

        $ids = array();
        foreach ($this->get_on_sale_product_filter_ids() as $sale_id) {
            $product = wc_get_product($sale_id);
            if (!$product) {
                continue;
            }

            $discount = $this->calculate_product_discount_percentage($product);
            if ($discount >= $min_discount) {
                $ids[] = $sale_id;
            }
        }

        return $this->sanitize_id_list($ids); // removes duplicates
    }


    /**
     * Calculate the discount percentage for a WooCommerce product.
     */
    private function calculate_product_discount_percentage($product): float
    {
        if (!$product || !$product->is_on_sale()) {
            return 0.0;
        }

        if ($product->is_type('variable')) {
            $variation_prices = $product->get_variation_prices();

            if (empty($variation_prices['regular_price'])) {
                return 0.0;
            }

            $discounts = array();
            foreach ($variation_prices['regular_price'] as $variation_id => $regular_price) {
                $regular_price = floatval($regular_price);
                $sale_price_raw = $variation_prices['sale_price'][$variation_id] ?? '';

                if ($regular_price <= 0 || $sale_price_raw === '' || $sale_price_raw === null) {
                    continue;
                }

                $sale_price = floatval($sale_price_raw);
                if ($sale_price < $regular_price) {
                    $discounts[] = (($regular_price - $sale_price) / $regular_price) * 100;
                }
            }

            return !empty($discounts) ? max($discounts) : 0.0;
        }

        $regular_price = floatval($product->get_regular_price());
        $sale_price_raw = $product->get_sale_price();

        if ($regular_price <= 0 || $sale_price_raw === '' || $sale_price_raw === null) {
            return 0.0;
        }

        $sale_price = floatval($sale_price_raw);

        if ($sale_price >= $regular_price) {
            return 0.0;
        }

        return (($regular_price - $sale_price) / $regular_price) * 100;
    }

    /**
     * Helper method to get date query based on filter parameters
     */
    private function get_date_query($params)
    {
        $date_query = array();

        if (isset($params['date_filter'])) {
            switch ($params['date_filter']) {
                case 'today':
                    $date_query = array(
                        array(
                            'year'  => gmdate('Y'),
                            'month' => gmdate('n'),
                            'day'   => gmdate('j'),
                        ),
                    );
                    break;

                case 'this_week':
                    $date_query = array(
                        array(
                            'after' => gmdate('Y-m-d', strtotime('monday this week')),
                            'before' => gmdate('Y-m-d', strtotime('sunday this week')),
                            'inclusive' => true,
                        ),
                    );
                    break;

                case 'this_month':
                    $date_query = array(
                        array(
                            'year'  => gmdate('Y'),
                            'month' => gmdate('n'),
                        ),
                    );
                    break;

                case 'this_year':
                    $date_query = array(
                        array(
                            'year' => gmdate('Y'),
                        ),
                    );
                    break;

                case 'custom':
                    if (isset($params['date_from']) && isset($params['date_to'])) {
                        $date_query = array(
                            array(
                                'after' => $params['date_from'],
                                'before' => $params['date_to'],
                                'inclusive' => true,
                            ),
                        );
                    } elseif (isset($params['date_from'])) {
                        $date_query = array(
                            array(
                                'after' => $params['date_from'],
                                'inclusive' => true,
                            ),
                        );
                    } elseif (isset($params['date_to'])) {
                        $date_query = array(
                            array(
                                'before' => $params['date_to'],
                                'inclusive' => true,
                            ),
                        );
                    }
                    break;
            }
        }

        return $date_query;
    }

    /**
     * Map user friendly ordering values to WooCommerce query args.
     */
    private function resolve_ordering_args($params)
    {
        if (empty($params['orderby']) && empty($params['order'])) {
            return array();
        }

        if (!function_exists('wc_get_catalog_ordering_args')) {
            $ordering = array();
        } else {
            $ordering = wc_get_catalog_ordering_args(
                $params['orderby'] ?? '',
                $params['order'] ?? ''
            );
        }

        $resolved = array();
        foreach (array('orderby', 'order', 'meta_key', 'meta_value') as $key) {
            if (isset($ordering[$key]) && $ordering[$key] !== '') {
                $resolved[$key] = $ordering[$key];
            }
        }

        return $resolved;
    }

    /**
     * Get additional WHERE clauses
     */
    private function get_additional_where_clauses($params)
    {
        global $wpdb;

        $where_clauses = array();
        $posts_table = $wpdb->posts;

        // Sale status filter: enforce sale/not sale IDs at SQL level so fragment queries
        // and third-party loops can't drop variable parent products.
        $sale_filter = $this->get_sale_filter_flags($params);
        if ($sale_filter['onsale'] xor $sale_filter['notonsale']) {
            if ($this->use_database_safe_mode()) {
                $sale_exists = '(' .
                    "EXISTS (" .
                        "SELECT 1 FROM {$wpdb->postmeta} rp " .
                        "JOIN {$wpdb->postmeta} sp ON sp.post_id = rp.post_id AND sp.meta_key = '_sale_price' " .
                        "WHERE rp.post_id = {$posts_table}.ID " .
                        "AND rp.meta_key = '_regular_price' " .
                        "AND rp.meta_value <> '' AND sp.meta_value <> '' AND sp.meta_value <> '0' " .
                        "AND CAST(sp.meta_value AS DECIMAL(20,6)) > 0 " .
                        "AND CAST(sp.meta_value AS DECIMAL(20,6)) < CAST(rp.meta_value AS DECIMAL(20,6))" .
                    ")" .
                    " OR EXISTS (" .
                        "SELECT 1 FROM {$wpdb->posts} v " .
                        "JOIN {$wpdb->postmeta} vrp ON vrp.post_id = v.ID AND vrp.meta_key = '_regular_price' " .
                        "JOIN {$wpdb->postmeta} vsp ON vsp.post_id = v.ID AND vsp.meta_key = '_sale_price' " .
                        "WHERE v.post_parent = {$posts_table}.ID " .
                        "AND v.post_type = 'product_variation' " .
                        "AND v.post_status = 'publish' " .
                        "AND vrp.meta_value <> '' AND vsp.meta_value <> '' AND vsp.meta_value <> '0' " .
                        "AND CAST(vsp.meta_value AS DECIMAL(20,6)) > 0 " .
                        "AND CAST(vsp.meta_value AS DECIMAL(20,6)) < CAST(vrp.meta_value AS DECIMAL(20,6))" .
                    ")" .
                ')';

                $where_clauses[] = $sale_filter['onsale'] ? $sale_exists : "NOT {$sale_exists}";
            } else {
                $sale_product_ids = $this->get_on_sale_product_filter_ids();

                if ($sale_filter['onsale']) {
                    $where_clauses[] = !empty($sale_product_ids)
                        ? "{$posts_table}.ID IN (" . implode(',', $sale_product_ids) . ')'
                        : '1=0';
                } elseif (!empty($sale_product_ids)) {
                    $where_clauses[] = "{$posts_table}.ID NOT IN (" . implode(',', $sale_product_ids) . ')';
                }
            }
        }

        $dimension_filters = $this->get_dimension_filters($params);
        if (!empty($dimension_filters)) {
            if ($this->use_database_safe_mode()) {
                $dimension_clause = $this->get_dimension_sql_condition("{$posts_table}.ID", $dimension_filters, 'dapff_dim_');
                if ($dimension_clause !== '') {
                    $where_clauses[] = $dimension_clause;
                }
            } else {
                $dimension_product_ids = $this->get_product_ids_by_dimensions($dimension_filters);
                $where_clauses[] = !empty($dimension_product_ids)
                    ? "{$posts_table}.ID IN (" . implode(',', $dimension_product_ids) . ')'
                    : '1=0';
            }
        }

        // Discount filter: enforce minimum percentage off via SQL so third-party queries can't drop it.
        if (isset($params['discount']) && floatval($params['discount']) > 0) {
            $threshold = floatval($params['discount']);
            $postmeta = $wpdb->postmeta;

            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names and the posts-table alias are internal WordPress SQL identifiers; discount thresholds are prepared.
            $discount_clause = $wpdb->prepare(
                '(' .
                    'EXISTS (' .
                    "SELECT 1 FROM {$postmeta} rp " .
                    "JOIN {$postmeta} sp ON sp.post_id = rp.post_id AND sp.meta_key = '_sale_price' " .
                    "WHERE rp.post_id = {$posts_table}.ID " .
                    "AND rp.meta_key = '_regular_price' " .
                    "AND rp.meta_value <> '' AND sp.meta_value <> '' " .
                    'AND CAST(sp.meta_value AS DECIMAL(10,2)) < CAST(rp.meta_value AS DECIMAL(10,2)) ' .
                    'AND ((CAST(rp.meta_value AS DECIMAL(10,2)) - CAST(sp.meta_value AS DECIMAL(10,2))) / NULLIF(CAST(rp.meta_value AS DECIMAL(10,2)), 0) * 100) >= %f' .
                    ')' .
                    ' OR ' .
                    'EXISTS (' .
                    "SELECT 1 FROM {$wpdb->posts} variation " .
                    "JOIN {$postmeta} rp ON rp.post_id = variation.ID AND rp.meta_key = '_regular_price' " .
                    "JOIN {$postmeta} sp ON sp.post_id = variation.ID AND sp.meta_key = '_sale_price' " .
                    "WHERE variation.post_parent = {$posts_table}.ID " .
                    "AND variation.post_type = 'product_variation' " .
                    "AND variation.post_status = 'publish' " .
                    "AND rp.meta_value <> '' AND sp.meta_value <> '' " .
                    'AND CAST(sp.meta_value AS DECIMAL(10,2)) < CAST(rp.meta_value AS DECIMAL(10,2)) ' .
                    'AND ((CAST(rp.meta_value AS DECIMAL(10,2)) - CAST(sp.meta_value AS DECIMAL(10,2))) / NULLIF(CAST(rp.meta_value AS DECIMAL(10,2)), 0) * 100) >= %f' .
                    ')' .
                    ')',
                $threshold,
                $threshold
            );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

            if (!empty($discount_clause)) {
                $where_clauses[] = $discount_clause;
            }
        }

        // Add any additional WHERE conditions here if needed
        // For example, for complex price filtering or custom fields

        return !empty($where_clauses) ? implode(' AND ', $where_clauses) : '';
    }

    /**
     * Get the configured rating display style so we can tailor comparison logic.
     */
    private function get_rating_style(): string
    {
        if ($this->rating_style !== null) {
            return $this->rating_style;
        }

        $style_options = isset($GLOBALS['dapfforwc_styleoptions'])
            ? $GLOBALS['dapfforwc_styleoptions']
            : (function_exists('dapfforwc_get_style_options') ? dapfforwc_get_style_options() : get_option('dapfforwc_style_options'));

        if (is_array($style_options) && !empty($style_options['rating']['sub_option'])) {
            $this->rating_style = $style_options['rating']['sub_option'];
        } else {
            $this->rating_style = 'rating';
        }

        return $this->rating_style;
    }

    /**
     * Build the rating meta query based on the configured rating style.
     */
    private function build_rating_meta_query($rating_values): array
    {
        $ratings = is_array($rating_values) ? $rating_values : explode(',', (string)$rating_values);
        $ratings = array_filter(array_map('floatval', $ratings), static function ($rating) {
            return $rating > 0;
        });

        if (empty($ratings)) {
            return array();
        }

        if ($this->get_rating_style() === 'rating-text') {
            return array(
                'key'     => '_wc_average_rating',
                'value'   => min($ratings),
                'compare' => '>=',
                'type'    => 'DECIMAL',
            );
        }

        $rating_clauses = array();
        foreach ($ratings as $rating) {
            $rating_clauses[] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_wc_average_rating',
                    'value'   => $rating - 0.5,
                    'compare' => '>=',
                    'type'    => 'DECIMAL',
                ),
                array(
                    'key'     => '_wc_average_rating',
                    'value'   => $rating + 0.5,
                    'compare' => '<',
                    'type'    => 'DECIMAL',
                ),
            );
        }

        if (count($rating_clauses) === 1) {
            return $rating_clauses[0];
        }

        return array_merge(array('relation' => 'OR'), $rating_clauses);
    }

    private function build_product_brand_tax_clause($brand_values, string $operator): array
    {
        $brands = $this->normalize_filter_values($brand_values);
        if (empty($brands)) {
            return array();
        }

        $term_ids = array();
        $unresolved = array();

        foreach ($brands as $brand) {
            $term = is_numeric($brand)
                ? get_term(absint($brand), 'product_brand')
                : get_term_by('slug', $brand, 'product_brand');

            if ($term && !is_wp_error($term)) {
                $term_ids[] = absint($term->term_id);
            } else {
                $unresolved[] = $brand;
            }
        }

        if (!empty($term_ids) && empty($unresolved)) {
            return array(
                'taxonomy' => 'product_brand',
                'field'    => 'term_id',
                'terms'    => array_values(array_unique($term_ids)),
                'operator' => $operator,
            );
        }

        return array(
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => $brands,
            'operator' => $operator,
        );
    }

    /**
     * Get all filter parameters from URL
     */
    public function get_filter_params()
    {
        if ($this->filter_params !== null) {
            return $this->filter_params;
        }

        $nonce_valid = $this->is_nonce_valid();
        if (!$nonce_valid) {
            if (!$this->request_is_read_only()) {
                return $this->filter_params = array();
            }
            if (!$this->has_filter_query_vars()) {
                return $this->filter_params = array();
            }
        }

        $params = array();
        $cat_operator = 'IN';
        $tag_operator = 'IN';
        $terms_operator = 'IN';
        $brand_operator = 'IN';
        $permalink_params = $this->normalize_prefixed_query_params($_GET);
        $attributes = $permalink_params['attributes'] ?? array();
        $custom_meta = $permalink_params['custom_meta'] ?? array();
        $filters_brand_values = array();
        $filters_author_slugs = array();
        $filters_rating_values = array();
        $filters_stock_status = array();
        $filters_sale_status = array();
        $filters_date_filter = '';

        // Category filter
        $category_values = array();
        if (!empty($this->category_alias_request_values)) {
            $category_values = array_merge($category_values, $this->category_alias_request_values);
        }
        if (!empty($permalink_params['product-category'])) {
            $category_values = array_merge($category_values, $permalink_params['product-category']);
        }
        if (isset($_GET['product-category']) && !empty($_GET['product-category']) && !$this->product_category_query_targets_attribute()) {
            $category_values = array_merge(
                $category_values,
                is_array($_GET['product-category'])
                    ? array_map('sanitize_text_field', wp_unslash($_GET['product-category']))
                    : explode(',', sanitize_text_field(wp_unslash($_GET['product-category'])))
            );
        }

        // Tag filter
        $tag_values = array();
        if (!empty($permalink_params['tags'])) {
            $tag_values = array_merge($tag_values, $permalink_params['tags']);
        }
        if (isset($_GET['tags']) && !empty($_GET['tags'])) {
            $tag_values = array_merge(
                $tag_values,
                is_array($_GET['tags'])
                    ? array_map('sanitize_text_field', wp_unslash($_GET['tags']))
                    : explode(',', sanitize_text_field(wp_unslash($_GET['tags'])))
            );
        }

        // Simple category alias used by permalinks (?filters=1&cata=laptop)
        if (isset($_GET['cata']) && !empty($_GET['cata'])) {
            $cata_values = is_array($_GET['cata'])
                ? array_map('sanitize_text_field', wp_unslash($_GET['cata']))
                : explode(',', sanitize_text_field(wp_unslash($_GET['cata'])));

            if (!empty($cata_values)) {
                $category_values = array_merge($category_values, $cata_values);
            }
        }

        // Permalink shorthand (?filters=white,laptop) can target multiple filter types
        if (isset($_GET['filters']) && $_GET['filters'] !== '' && $_GET['filters'] !== '1') {
            $filters_values = is_array($_GET['filters'])
                ? array_map('sanitize_text_field', wp_unslash($_GET['filters']))
                : explode(',', sanitize_text_field(wp_unslash($_GET['filters'])));
            $filters_values = array_values(array_unique(array_filter(array_map('trim', $filters_values), static function ($value) {
                return $value !== '';
            })));

            if (!empty($filters_values)) {
                $filters_matched_values = array();
                $all_data = function_exists('dapfforwc_get_woocommerce_attributes_with_terms')
                    ? dapfforwc_get_woocommerce_attributes_with_terms()
                    : array();

                $category_slugs = !empty($all_data['categories']) ? array_column($all_data['categories'], 'slug') : array();
                $matched_categories = array_values(array_intersect($filters_values, $category_slugs));
                if (!empty($matched_categories)) {
                    $category_values = array_merge($category_values, $matched_categories);
                    $filters_matched_values = array_merge($filters_matched_values, $matched_categories);
                }

                $tag_slugs = !empty($all_data['tags']) ? array_column($all_data['tags'], 'slug') : array();
                $matched_tags = array_values(array_intersect($filters_values, $tag_slugs));
                if (!empty($matched_tags)) {
                    $tag_values = array_merge($tag_values, $matched_tags);
                    $filters_matched_values = array_merge($filters_matched_values, $matched_tags);
                }

                $brand_slugs = !empty($all_data['brands']) ? array_column($all_data['brands'], 'slug') : array();
                $matched_brands = array_values(array_intersect($filters_values, $brand_slugs));
                if (!empty($matched_brands)) {
                    $filters_brand_values = array_values(array_unique(array_merge($filters_brand_values, $matched_brands)));
                    $filters_matched_values = array_merge($filters_matched_values, $matched_brands);
                }

                $author_slugs = !empty($all_data['authors']) ? array_column($all_data['authors'], 'slug') : array();
                $matched_authors = array_values(array_intersect($filters_values, $author_slugs));
                if (!empty($matched_authors)) {
                    $filters_author_slugs = array_values(array_unique(array_merge($filters_author_slugs, $matched_authors)));
                    $filters_matched_values = array_merge($filters_matched_values, $matched_authors);
                }

                $stock_slugs = !empty($all_data['stock_status']) ? array_column($all_data['stock_status'], 'slug') : array();
                $matched_stock = array_values(array_intersect($filters_values, $stock_slugs));
                if (!empty($matched_stock)) {
                    $filters_stock_status = array_values(array_unique(array_merge($filters_stock_status, $matched_stock)));
                    $filters_matched_values = array_merge($filters_matched_values, $matched_stock);
                }

                $sale_slugs = !empty($all_data['sale_status']) ? array_column($all_data['sale_status'], 'slug') : array();
                $matched_sale = array_values(array_intersect($filters_values, $sale_slugs));
                if (!empty($matched_sale)) {
                    $filters_sale_status = array_values(array_unique(array_merge($filters_sale_status, $matched_sale)));
                    $filters_matched_values = array_merge($filters_matched_values, $matched_sale);
                }

                $date_candidates = array_values(array_intersect($filters_values, array('today', 'this_week', 'this_month', 'this_year')));
                if (!empty($date_candidates)) {
                    $filters_date_filter = reset($date_candidates);
                    $filters_matched_values = array_merge($filters_matched_values, $date_candidates);
                }

                $numeric_ratings = array_values(array_filter($filters_values, function ($value) {
                    return is_numeric($value) && $value >= 1 && $value <= 5;
                }));
                if (!empty($numeric_ratings)) {
                    $filters_rating_values = array_values(array_unique(array_merge($filters_rating_values, array_map('floatval', $numeric_ratings))));
                    $filters_matched_values = array_merge($filters_matched_values, $numeric_ratings);
                }

                if (!empty($all_data['attributes']) && is_array($all_data['attributes'])) {
                    foreach ($all_data['attributes'] as $attribute_name => $attribute_data) {
                        $attribute_terms = $attribute_data['terms'] ?? array();
                        if (empty($attribute_terms)) {
                            continue;
                        }
                        $attribute_slugs = array_column($attribute_terms, 'slug');
                        $matched_terms = array_values(array_intersect($filters_values, $attribute_slugs));
                        if (!empty($matched_terms)) {
                            $existing = isset($attributes[$attribute_name]) ? (array)$attributes[$attribute_name] : array();
                            $attributes[$attribute_name] = array_values(array_unique(array_merge($existing, $matched_terms)));
                            $filters_matched_values = array_merge($filters_matched_values, $matched_terms);
                        }
                    }
                }

                if (!empty($all_data['custom_fields']) && is_array($all_data['custom_fields'])) {
                    foreach ($all_data['custom_fields'] as $meta_key => $meta_data) {
                        $meta_terms = $meta_data['terms'] ?? array();
                        if (empty($meta_terms)) {
                            continue;
                        }
                        $meta_slugs = array_column($meta_terms, 'slug');
                        $matched_terms = array_values(array_intersect($filters_values, $meta_slugs));
                        if (!empty($matched_terms)) {
                            $existing = $custom_meta[$meta_key] ?? array();
                            $custom_meta[$meta_key] = array_values(array_unique(array_merge($existing, $matched_terms)));
                            $filters_matched_values = array_merge($filters_matched_values, $matched_terms);
                        }
                    }
                }
            }
        }
        if (!empty($category_values)) {
            $params['product-category'] = array_values(array_unique($category_values));
        }

        // Attribute filters
        foreach ($_GET as $key => $value) {
            if (strpos($key, 'rplugpa_') === 0 && !empty($value)) {
                $attribute_name = str_replace('rplugpa_', '', $key);
                $new_values = is_array($value)
                    ? array_map('sanitize_text_field', wp_unslash($value))
                    : explode(',', sanitize_text_field(wp_unslash($value)));
                $existing = isset($attributes[$attribute_name]) ? (array)$attributes[$attribute_name] : array();
                $attributes[$attribute_name] = array_values(array_unique(array_merge($existing, $new_values)));
            }
        }
        if (!empty($attributes)) {
            $params['attributes'] = $attributes;
        }

        // Price range shorthand from permalink (?price=100-500)
        if (isset($_GET['price']) && $_GET['price'] !== '') {
            $price_range = sanitize_text_field(wp_unslash($_GET['price']));
            list($min_raw, $max_raw) = array_pad(explode('-', $price_range, 2), 2, '');
            if ($min_raw !== '' && is_numeric($min_raw)) {
                $params['mn_price'] = floatval($min_raw);
            }
            if ($max_raw !== '' && is_numeric($max_raw)) {
                $params['mx_price'] = floatval($max_raw);
            }
        }

        // Price range (permalink shorthand first)
        if (isset($permalink_params['mn_price'])) {
            $params['mn_price'] = $permalink_params['mn_price'];
        }
        if (isset($permalink_params['mx_price'])) {
            $params['mx_price'] = $permalink_params['mx_price'];
        }

        // Price range
        if (isset($_GET['mn_price']) && !empty($_GET['mn_price'])) {
            $params['mn_price'] = floatval($_GET['mn_price']);
        }
        if (isset($_GET['mx_price']) && !empty($_GET['mx_price'])) {
            $params['mx_price'] = floatval($_GET['mx_price']);
        }


        // Pagination / ordering
        if (isset($permalink_params['paged']) && absint($permalink_params['paged']) > 0) {
            $params['paged'] = absint($permalink_params['paged']);
        }
        if (isset($_GET['paged']) && absint($_GET['paged']) > 0) {
            $params['paged'] = absint($_GET['paged']);
        } elseif (isset($_GET['product-page']) && absint($_GET['product-page']) > 0) {
            $params['paged'] = absint($_GET['product-page']);
        }

        if (isset($_GET['per_page']) && $_GET['per_page'] !== '') {
            $per_page = absint($_GET['per_page']);
            if ($per_page > 0) {
                $params['per_page'] = max(1, min($per_page, 120));
            }
        }

        if (isset($permalink_params['orderby']) && $permalink_params['orderby'] !== '') {
            $normalized_orderby = $this->normalize_orderby_value($permalink_params['orderby']);
            if ($normalized_orderby !== '') {
                $params['orderby'] = $normalized_orderby;
            }
        }

        if (isset($_GET['orderby']) && $_GET['orderby'] !== '') {
            $normalized_orderby = $this->normalize_orderby_value(sanitize_text_field(wp_unslash($_GET['orderby'])));
            if ($normalized_orderby !== '') {
                $params['orderby'] = $normalized_orderby;
            }
        }

        if (isset($_GET['order']) && $_GET['order'] !== '') {
            $order = strtoupper(sanitize_text_field(wp_unslash($_GET['order'])));
            if (in_array($order, array('ASC', 'DESC'), true)) {
                $params['order'] = $order;
            }
        }

        if (isset($_GET['cat_operator']) && $_GET['cat_operator'] !== '') {
            $operator = strtoupper(sanitize_text_field(wp_unslash($_GET['cat_operator'])));
            if (in_array($operator, array('IN', 'NOT IN', 'AND'), true)) {
                $params['cat_operator'] = $operator;
                $cat_operator = $operator;
            }
        } else {
            $params['cat_operator'] = $cat_operator;
        }
        if (isset($_GET['tag_operator']) && $_GET['tag_operator'] !== '') {
            $operator = strtoupper(sanitize_text_field(wp_unslash($_GET['tag_operator'])));
            if (in_array($operator, array('IN', 'NOT IN', 'AND'), true)) {
                $params['tag_operator'] = $operator;
                $tag_operator = $operator;
            }
        } else {
            $params['tag_operator'] = $tag_operator;
        }
        if (isset($_GET['terms_operator']) && $_GET['terms_operator'] !== '') {
            $operator = strtoupper(sanitize_text_field(wp_unslash($_GET['terms_operator'])));
            if (in_array($operator, array('IN', 'NOT IN', 'AND'), true)) {
                $params['terms_operator'] = $operator;
                $terms_operator = $operator;
            }
        } else {
            $params['terms_operator'] = $terms_operator;
        }
        if (isset($_GET['brand_operator']) && $_GET['brand_operator'] !== '') {
            $operator = strtoupper(sanitize_text_field(wp_unslash($_GET['brand_operator'])));
            if (in_array($operator, array('IN', 'NOT IN', 'AND'), true)) {
                $params['brand_operator'] = $operator;
                $brand_operator = $operator;
            }
        } else {
            $params['brand_operator'] = $brand_operator;
        }

        if (isset($_GET['tax_relation'])) {
            $relation = strtoupper(sanitize_text_field(wp_unslash($_GET['tax_relation'])));
            if (in_array($relation, array('AND', 'OR'), true)) {
                $params['tax_relation'] = $relation;
            }
        }

        if (isset($_GET['meta_relation'])) {
            $relation = strtoupper(sanitize_text_field(wp_unslash($_GET['meta_relation'])));
            if (in_array($relation, array('AND', 'OR'), true)) {
                $params['meta_relation'] = $relation;
            }
        }

        // Rating filter
        if (isset($permalink_params['rating']) && !empty($permalink_params['rating'])) {
            $params['rating'] = $permalink_params['rating'];
        }
        if (isset($_GET['rating']) && !empty($_GET['rating'])) {
            $rating_values = is_array($_GET['rating'])
                ? array_map('sanitize_text_field', wp_unslash($_GET['rating']))
                : explode(',', sanitize_text_field(wp_unslash($_GET['rating'])));
            $existing_ratings = $params['rating'] ?? array();
            $params['rating'] = array_values(array_unique(array_merge((array)$existing_ratings, $rating_values)));
        }
        if (!empty($filters_rating_values)) {
            $existing_ratings = $params['rating'] ?? array();
            $params['rating'] = array_values(array_unique(array_merge((array)$existing_ratings, $filters_rating_values)));
        }

        // Permalink/search alias using configured prefix (default: title)
        if (isset($permalink_params['plugincy_search']) && $permalink_params['plugincy_search'] !== '') {
            $params['plugincy_search'] = $permalink_params['plugincy_search'];
        }

        // Legacy search aliases
        if (!isset($params['plugincy_search']) && isset($_GET['title']) && !empty($_GET['title'])) {
            $params['plugincy_search'] = sanitize_text_field(wp_unslash($_GET['title']));
        }

        // plugincy_search filter
        if (!isset($params['plugincy_search']) && isset($_GET['plugincy_search']) && !empty($_GET['plugincy_search'])) {
            $params['plugincy_search'] = sanitize_text_field(wp_unslash($_GET['plugincy_search']));
        }

        // Backward compatibility with old parameters
        if (isset($_GET['rcata']) && !empty($_GET['rcata'])) {
            $category_ids = $this->parse_ids(sanitize_text_field(wp_unslash($_GET['rcata'])));
            if (!empty($category_ids)) {
                $category_slugs = array();
                foreach ($category_ids as $id) {
                    $term = get_term($id, 'product_cat');
                    if ($term && !is_wp_error($term)) {
                        $category_slugs[] = $term->slug;
                    }
                }
                if (!empty($category_slugs)) {
                    $params['product-category'] = $category_slugs;
                }
            }
        }

        if (isset($_GET['rtag']) && !empty($_GET['rtag'])) {
            $tag_ids = $this->parse_ids(sanitize_text_field(wp_unslash($_GET['rtag'])));
            if (!empty($tag_ids)) {
                $tag_slugs = array();
                foreach ($tag_ids as $id) {
                    $term = get_term($id, 'product_tag');
                    if ($term && !is_wp_error($term)) {
                        $tag_slugs[] = $term->slug;
                    }
                }
                if (!empty($tag_slugs)) {
                    $tag_values = array_merge($tag_values, $tag_slugs);
                }
            }
        }
        if (!empty($tag_values)) {
            $params['tags'] = array_values(array_unique($tag_values));
        }

        if (isset($permalink_params['rplurand']) && !empty($permalink_params['rplurand'])) {
            $params['rplurand'] = $permalink_params['rplurand'];
        }
        // rplurand filter
        if (isset($_GET['rplurand']) && !empty($_GET['rplurand'])) {
            $params['rplurand'] = is_array($_GET['rplurand'])
                ? array_map('sanitize_text_field', wp_unslash($_GET['rplurand']))
                : explode(',', sanitize_text_field(wp_unslash($_GET['rplurand'])));
        }
        if (!empty($filters_brand_values)) {
            $existing_brands = $params['rplurand'] ?? array();
            $params['rplurand'] = array_values(array_unique(array_merge((array)$existing_brands, $filters_brand_values)));
        }

        // Stock status filter
        if (isset($permalink_params['rplutock_status']) && !empty($permalink_params['rplutock_status'])) {
            $params['rplutock_status'] = $permalink_params['rplutock_status'];
        }
        if (isset($_GET['rplutock_status']) && !empty($_GET['rplutock_status'])) {
            $params['rplutock_status'] = is_array($_GET['rplutock_status'])
                ? array_map('sanitize_text_field', wp_unslash($_GET['rplutock_status']))
                : explode(',', sanitize_text_field(wp_unslash($_GET['rplutock_status'])));
        }
        if (!empty($filters_stock_status)) {
            $existing_stock = $params['rplutock_status'] ?? array();
            $params['rplutock_status'] = array_values(array_unique(array_merge((array)$existing_stock, $filters_stock_status)));
        }
        if (!empty($params['rplutock_status'])) {
            $params['rplutock_status'] = $this->normalize_visible_stock_status_values($params['rplutock_status']);
            if (empty($params['rplutock_status'])) {
                unset($params['rplutock_status']);
            }
        }

        // rpluthor filter (using username instead of user ID)
        $author_values = array();
        if (!empty($filters_author_slugs)) {
            $author_values = array_merge($author_values, $filters_author_slugs);
        }
        if (isset($permalink_params['rpluthor']) && !empty($permalink_params['rpluthor'])) {
            $author_values = array_merge($author_values, $permalink_params['rpluthor']);
        }
        if (isset($_GET['rpluthor']) && !empty($_GET['rpluthor'])) {
            $author_values = array_merge(
                $author_values,
                is_array($_GET['rpluthor'])
                    ? array_map('sanitize_text_field', wp_unslash($_GET['rpluthor']))
                    : array_map('sanitize_text_field', explode(',', sanitize_text_field(wp_unslash($_GET['rpluthor']))))
            );
        }
        if (!empty($author_values)) {
            // Convert usernames to user IDs
            $user_ids = array();
            foreach (array_unique($author_values) as $username) {
                $user = get_user_by('login', $username);
                if ($user) {
                    $user_ids[] = $user->ID;
                }
            }

            if (!empty($user_ids)) {
                $params['rpluthor'] = $user_ids;
            }
        }

        // Sale status filter (on sale / not on sale)
        $sale_values = array();
        if (!empty($filters_sale_status)) {
            $sale_values = array_merge($sale_values, $filters_sale_status);
        }
        if (isset($permalink_params['rpn_sale'])) {
            $sale_values = array_merge($sale_values, (array)$permalink_params['rpn_sale']);
        }
        if (isset($_GET['rpn_sale']) && $_GET['rpn_sale'] !== '') {
            $raw_sale_values = map_deep(wp_unslash($_GET['rpn_sale']), 'sanitize_text_field');
            $sale_values = array_merge(
                $sale_values,
                is_array($raw_sale_values)
                    ? $raw_sale_values
                    : explode(',', $raw_sale_values)
            );
        }
        if (isset($_GET['rpn_sale[]']) && $_GET['rpn_sale[]'] !== '') {
            $raw_sale_values = map_deep(wp_unslash($_GET['rpn_sale[]']), 'sanitize_text_field');
            $sale_values = array_merge(
                $sale_values,
                is_array($raw_sale_values)
                    ? $raw_sale_values
                    : explode(',', $raw_sale_values)
            );
        }
        $sale_values = $this->normalize_sale_values($sale_values);
        if (!empty($sale_values)) {
            $params['rpn_sale'] = $sale_values;
        }

        // Dimensions filters
        foreach (array('min_length', 'max_length', 'min_width', 'max_width', 'min_height', 'max_height', 'min_weight', 'max_weight') as $dimension_key) {
            if (isset($permalink_params[$dimension_key])) {
                $params[$dimension_key] = $permalink_params[$dimension_key];
            }
        }
        if (isset($_GET['min_length']) && !empty($_GET['min_length'])) {
            $params['min_length'] = floatval($_GET['min_length']);
        }
        if (isset($_GET['max_length']) && !empty($_GET['max_length'])) {
            $params['max_length'] = floatval($_GET['max_length']);
        }
        if (isset($_GET['min_width']) && !empty($_GET['min_width'])) {
            $params['min_width'] = floatval($_GET['min_width']);
        }
        if (isset($_GET['max_width']) && !empty($_GET['max_width'])) {
            $params['max_width'] = floatval($_GET['max_width']);
        }
        if (isset($_GET['min_height']) && !empty($_GET['min_height'])) {
            $params['min_height'] = floatval($_GET['min_height']);
        }
        if (isset($_GET['max_height']) && !empty($_GET['max_height'])) {
            $params['max_height'] = floatval($_GET['max_height']);
        }
        if (isset($_GET['min_weight']) && !empty($_GET['min_weight'])) {
            $params['min_weight'] = floatval($_GET['min_weight']);
        }
        if (isset($_GET['max_weight']) && !empty($_GET['max_weight'])) {
            $params['max_weight'] = floatval($_GET['max_weight']);
        }

        // Custom meta filters
        foreach ($_GET as $key => $value) {
            if (strpos($key, 'rplugcusf_') === 0 && !empty($value)) {
                $meta_key = str_replace('rplugcusf_', '', $key);
                $new_values = is_array($value)
                    ? array_map('sanitize_text_field', wp_unslash($value))
                    : explode(',', sanitize_text_field(wp_unslash($value)));
                $existing = $custom_meta[$meta_key] ?? array();
                $custom_meta[$meta_key] = array_values(array_unique(array_merge($existing, $new_values)));
            }
        }
        if (!empty($custom_meta)) {
            $params['custom_meta'] = $custom_meta;
        }

        // SKU filter
        if (isset($permalink_params['sku']) && !empty($permalink_params['sku'])) {
            $params['sku'] = $permalink_params['sku'];
        }
        if (isset($_GET['sku']) && !empty($_GET['sku'])) {
            $params['sku'] = is_array($_GET['sku'])
                ? array_map('sanitize_text_field', wp_unslash($_GET['sku']))
                : explode(',', sanitize_text_field(wp_unslash($_GET['sku'])));
        }

        // Product ID filter
        if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
            $params['product_id'] = is_array($_GET['product_id'])
                ? array_map('intval', $_GET['product_id'])
                : array_map('intval', explode(',', sanitize_text_field(wp_unslash($_GET['product_id']))));
        }

        // Discount filter
        if (isset($permalink_params['discount']) && $permalink_params['discount'] !== '') {
            $params['discount'] = $permalink_params['discount'];
        }
        if (isset($_GET['discount']) && !empty($_GET['discount'])) {
            $params['discount'] = floatval($_GET['discount']);
        }

        // New arrivals filter
        if (isset($_GET['new_arrivals']) && !empty($_GET['new_arrivals'])) {
            $params['new_arrivals'] = intval($_GET['new_arrivals']); // days
        }

        // Date filters
        if ($filters_date_filter !== '') {
            $params['date_filter'] = $filters_date_filter;
        }
        if (isset($permalink_params['date_filter']) && !empty($permalink_params['date_filter'])) {
            $params['date_filter'] = $permalink_params['date_filter'];
        }
        if (isset($_GET['date_filter']) && !empty($_GET['date_filter'])) {
            $params['date_filter'] = sanitize_text_field(wp_unslash($_GET['date_filter']));
        }
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $params['date_from'] = sanitize_text_field(wp_unslash($_GET['date_from']));
        }
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $params['date_to'] = sanitize_text_field(wp_unslash($_GET['date_to']));
        }

        return $this->filter_params = $params;
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    /**
     * Normalize permalinks-based query params to the standard filter keys
     */
    private function normalize_prefixed_query_params(array $request): array
    {
        $normalized = array();

        $prefixes = $this->get_prefixes_config();

        $prefixed_category = $this->resolve_prefix_key($prefixes, 'product-category', 'cata');
        if (isset($request[$prefixed_category]) && $request[$prefixed_category] !== '') {
            $normalized['product-category'] = $this->normalize_filter_values($request[$prefixed_category]);
        }

        $prefixed_tags = $this->resolve_prefix_key($prefixes, 'tag', 'tags');
        if (isset($request[$prefixed_tags]) && $request[$prefixed_tags] !== '') {
            $normalized['tags'] = $this->normalize_filter_values($request[$prefixed_tags]);
        }

        $prefixed_brand = $this->resolve_prefix_key($prefixes, 'brand', 'brand');
        if (isset($request[$prefixed_brand]) && $request[$prefixed_brand] !== '') {
            $normalized['rplurand'] = $this->normalize_filter_values($request[$prefixed_brand]);
        }

        $prefixed_author = $this->resolve_prefix_key($prefixes, 'author', 'authors');
        if (isset($request[$prefixed_author]) && $request[$prefixed_author] !== '') {
            $normalized['rpluthor'] = $this->normalize_filter_values($request[$prefixed_author]);
        }

        $prefixed_stock = $this->resolve_prefix_key($prefixes, 'stock_status', 'stockStatus');
        if (isset($request[$prefixed_stock]) && $request[$prefixed_stock] !== '') {
            $normalized['rplutock_status'] = $this->normalize_filter_values($request[$prefixed_stock]);
        }

        $prefixed_sale = $this->resolve_prefix_key($prefixes, 'sale_status', 'saleStatus');
        if (isset($request[$prefixed_sale]) && $request[$prefixed_sale] !== '') {
            $sale_values = $this->normalize_sale_values($request[$prefixed_sale]);
            if (!empty($sale_values)) {
                $normalized['rpn_sale'] = $sale_values;
            }
        }

        $prefixed_rating = $this->resolve_prefix_key($prefixes, 'rating', 'rating');
        if (isset($request[$prefixed_rating]) && $request[$prefixed_rating] !== '') {
            $normalized['rating'] = array_map('floatval', $this->normalize_filter_values($request[$prefixed_rating]));
        }

        $prefixed_search = $this->resolve_prefix_key($prefixes, 'plugincy_search', 'title');
        if (isset($request[$prefixed_search]) && $request[$prefixed_search] !== '') {
            $normalized['plugincy_search'] = sanitize_text_field(wp_unslash($request[$prefixed_search]));
        }

        $prefixed_price = $this->resolve_prefix_key($prefixes, 'price', 'price');
        if (isset($request[$prefixed_price]) && $request[$prefixed_price] !== '') {
            $price_range = sanitize_text_field(wp_unslash($request[$prefixed_price]));
            list($min_raw, $max_raw) = array_pad(explode('-', $price_range, 2), 2, '');
            if ($min_raw !== '' && is_numeric($min_raw)) {
                $normalized['mn_price'] = floatval($min_raw);
            }
            if ($max_raw !== '' && is_numeric($max_raw)) {
                $normalized['mx_price'] = floatval($max_raw);
            }
        }

        $prefixed_pagination = $this->resolve_prefix_key($prefixes, 'pagination', 'paged');
        if (isset($request[$prefixed_pagination]) && $request[$prefixed_pagination] !== '') {
            $prefixed_page = absint(wp_unslash($request[$prefixed_pagination]));
            if ($prefixed_page > 0) {
                $normalized['paged'] = $prefixed_page;
            }
        }

        $prefixed_orderby = $this->resolve_prefix_key($prefixes, 'orderby', 'orderby');
        if (isset($request[$prefixed_orderby]) && $request[$prefixed_orderby] !== '') {
            $normalized_orderby = $this->normalize_orderby_value($request[$prefixed_orderby]);
            if ($normalized_orderby !== '') {
                $normalized['orderby'] = $normalized_orderby;
            }
        }

        $prefixed_length = $this->resolve_prefix_key($prefixes, 'length', 'length');
        if (isset($request[$prefixed_length]) && $request[$prefixed_length] !== '') {
            $range = sanitize_text_field(wp_unslash($request[$prefixed_length]));
            list($min_raw, $max_raw) = array_pad(explode('-', $range, 2), 2, '');
            if ($min_raw !== '' && is_numeric($min_raw)) {
                $normalized['min_length'] = floatval($min_raw);
            }
            if ($max_raw !== '' && is_numeric($max_raw)) {
                $normalized['max_length'] = floatval($max_raw);
            }
        }

        $prefixed_width = $this->resolve_prefix_key($prefixes, 'width', 'width');
        if (isset($request[$prefixed_width]) && $request[$prefixed_width] !== '') {
            $range = sanitize_text_field(wp_unslash($request[$prefixed_width]));
            list($min_raw, $max_raw) = array_pad(explode('-', $range, 2), 2, '');
            if ($min_raw !== '' && is_numeric($min_raw)) {
                $normalized['min_width'] = floatval($min_raw);
            }
            if ($max_raw !== '' && is_numeric($max_raw)) {
                $normalized['max_width'] = floatval($max_raw);
            }
        }

        $prefixed_height = $this->resolve_prefix_key($prefixes, 'height', 'height');
        if (isset($request[$prefixed_height]) && $request[$prefixed_height] !== '') {
            $range = sanitize_text_field(wp_unslash($request[$prefixed_height]));
            list($min_raw, $max_raw) = array_pad(explode('-', $range, 2), 2, '');
            if ($min_raw !== '' && is_numeric($min_raw)) {
                $normalized['min_height'] = floatval($min_raw);
            }
            if ($max_raw !== '' && is_numeric($max_raw)) {
                $normalized['max_height'] = floatval($max_raw);
            }
        }

        $prefixed_weight = $this->resolve_prefix_key($prefixes, 'weight', 'weight');
        if (isset($request[$prefixed_weight]) && $request[$prefixed_weight] !== '') {
            $range = sanitize_text_field(wp_unslash($request[$prefixed_weight]));
            list($min_raw, $max_raw) = array_pad(explode('-', $range, 2), 2, '');
            if ($min_raw !== '' && is_numeric($min_raw)) {
                $normalized['min_weight'] = floatval($min_raw);
            }
            if ($max_raw !== '' && is_numeric($max_raw)) {
                $normalized['max_weight'] = floatval($max_raw);
            }
        }

        $prefixed_sku = $this->resolve_prefix_key($prefixes, 'sku', 'sku');
        if (isset($request[$prefixed_sku]) && $request[$prefixed_sku] !== '') {
            $normalized['sku'] = $this->normalize_filter_values($request[$prefixed_sku]);
        }

        $prefixed_discount = $this->resolve_prefix_key($prefixes, 'discount', 'discount');
        if (isset($request[$prefixed_discount]) && $request[$prefixed_discount] !== '') {
            $normalized['discount'] = floatval(wp_unslash($request[$prefixed_discount]));
        }

        $prefixed_date = $this->resolve_prefix_key($prefixes, 'date_filter', 'date');
        if (isset($request[$prefixed_date]) && $request[$prefixed_date] !== '') {
            $normalized['date_filter'] = sanitize_text_field(wp_unslash($request[$prefixed_date]));
        }

        if (isset($prefixes['attribute']) && is_array($prefixes['attribute'])) {
            foreach ($prefixes['attribute'] as $attribute_name => $attribute_prefix) {
                foreach ($this->get_attribute_query_keys($attribute_name, $attribute_prefix) as $attribute_key) {
                    if (isset($request[$attribute_key]) && $request[$attribute_key] !== '') {
                        $normalized['attributes'][$attribute_name] = $this->normalize_filter_values($request[$attribute_key]);
                        break;
                    }
                }
            }
        }

        if (isset($prefixes['custom']) && is_array($prefixes['custom'])) {
            foreach ($prefixes['custom'] as $custom_name => $custom_prefix) {
                $custom_key = $custom_prefix !== '' ? $custom_prefix : $custom_name;
                if ($custom_key === '') {
                    continue;
                }
                if (isset($request[$custom_key]) && $request[$custom_key] !== '') {
                    $normalized['custom_meta'][$custom_name] = $this->normalize_filter_values($request[$custom_key]);
                }
            }
        }

        return $normalized;
    }

    private function normalize_filter_values($value): array
    {
        $values = is_array($value)
            ? array_map(static function ($item) {
                return sanitize_text_field(wp_unslash($item));
            }, $value)
            : explode(',', sanitize_text_field(wp_unslash($value)));

        $values = array_map('trim', $values);
        $values = array_filter($values, static function ($v) {
            return $v !== '';
        });

        return array_values(array_unique($values));
    }

    private function should_limit_stock_status_to_instock(): bool
    {
        global $dapfforwc_advance_settings;

        $settings = is_array($dapfforwc_advance_settings)
            ? $dapfforwc_advance_settings
            : get_option('dapfforwc_advance_options', array());

        return is_array($settings) && (($settings['remove_outofStock'] ?? '') === 'on');
    }

    private function normalize_visible_stock_status_values($value): array
    {
        $values = $this->normalize_filter_values($value);
        $allowed_statuses = $this->should_limit_stock_status_to_instock()
            ? array('instock')
            : array('instock', 'outofstock', 'onbackorder');

        return array_values(array_intersect($values, $allowed_statuses));
    }

    /**
     * Normalize sale status request values to supported slugs.
     */
    private function normalize_sale_values($value): array
    {
        $values = is_array($value) ? $value : array($value);

        $prepared_values = array();
        foreach ($values as $item) {
            if ($item === null || $item === '') {
                continue;
            }
            if ($item === true) {
                $prepared_values[] = 'onsale';
                continue;
            }
            if ($item === false) {
                continue;
            }
            $prepared_values[] = $item;
        }

        $values = $this->normalize_filter_values($prepared_values);
        $normalized = array();

        foreach ($values as $sale_val) {
            $sale_val = strtolower($sale_val);
            if (in_array($sale_val, array('onsale', 'on-sale', 'on_sale', '1', 'true', 'yes', 'sale', 'on'), true)) {
                $normalized[] = 'onsale';
            } elseif (in_array($sale_val, array('notonsale', 'not-on-sale', 'not_on_sale', 'not-sale', '0', 'false', 'no', 'off'), true)) {
                $normalized[] = 'notonsale';
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Normalize requested orderby values to supported WooCommerce options.
     */
    private function normalize_orderby_value($value): string
    {
        $orderby_key = sanitize_key(str_replace('-', '_', (string) wp_unslash($value)));
        $allowed_orderby = array(
            'date'        => 'date',
            'price'       => 'price',
            'price_desc'  => 'price-desc',
            'popularity'  => 'popularity',
            'rating'      => 'rating',
            'title'       => 'title',
            'menu_order'  => 'menu_order',
            'rand'        => 'rand',
        );

        return $allowed_orderby[$orderby_key] ?? '';
    }

    /**
     * Determine which sale states were requested.
     */
    private function get_sale_filter_flags($params): array
    {
        $sale_values = isset($params['rpn_sale'])
            ? $this->normalize_sale_values($params['rpn_sale'])
            : array();

        return array(
            'onsale' => in_array('onsale', $sale_values, true),
            'notonsale' => in_array('notonsale', $sale_values, true),
        );
    }

    /**
     * Ensure ID lists are clean integers.
     */
    private function sanitize_id_list($ids): array
    {
        $ids = is_array($ids) ? $ids : array($ids);

        $ids = array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    /**
     * Merge new inclusion IDs with any existing inclusion list.
     * If a list already exists, intersect so both constraints hold.
     */
    private function merge_post_in_lists($existing, $ids): array
    {
        $ids = $this->sanitize_id_list($ids);
        $existing = $this->sanitize_id_list($existing);

        if (empty($ids)) {
            return array();
        }

        if (empty($existing)) {
            return $ids;
        }

        return array_values(array_intersect($existing, $ids));
    }

    /**
     * Combine exclusion IDs with any existing exclusions.
     */
    private function merge_post_not_in_lists($existing, $ids): array
    {
        $ids = $this->sanitize_id_list($ids);
        $existing = $this->sanitize_id_list($existing);

        return array_values(array_unique(array_merge($existing, $ids)));
    }

    private function get_prefixes_config(): array
    {
        global $dapfforwc_seo_permalinks_options;
        $config = $dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options'] ?? array();
        return is_array($config) ? $config : array();
    }

    private function resolve_prefix_key(array $prefixes, string $key, string $default): string
    {
        if (isset($prefixes[$key]) && $prefixes[$key] !== '') {
            return $prefixes[$key];
        }
        return $default;
    }

    /**
     * When stores configure the Product Category attribute prefix as
     * product-category while the Woo category prefix is cata, the same public
     * query key can represent an attribute filter. Do not capture that request
     * as a Woo category alias when the requested values are real attribute terms.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public storefront GET filters are read-only and shareable; normalize_filter_values() unslashes and sanitizes every value before use.
    private function product_category_query_targets_attribute(): bool
    {
        if (empty($_GET['product-category'])) {
            return false;
        }

        $attribute_name = $this->get_attribute_name_for_public_query_key('product-category');
        if ($attribute_name === '') {
            return false;
        }

        $values = $this->normalize_filter_values($_GET['product-category']);
        if (empty($values)) {
            return false;
        }

        return $this->filter_values_exist_for_attribute($attribute_name, $values);
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    private function get_attribute_name_for_public_query_key(string $query_key): string
    {
        global $dapfforwc_seo_permalinks_options;

        if (($dapfforwc_seo_permalinks_options['use_attribute_type_in_permalinks'] ?? '') !== 'on') {
            return '';
        }

        $prefixes = $this->get_prefixes_config();
        if (empty($prefixes['attribute']) || !is_array($prefixes['attribute'])) {
            return '';
        }

        $query_key = sanitize_key($query_key);
        foreach ($prefixes['attribute'] as $attribute_name => $attribute_prefix) {
            foreach ($this->get_attribute_query_keys($attribute_name, $attribute_prefix) as $attribute_alias) {
                if ($attribute_alias === $query_key) {
                    return sanitize_title((string) $attribute_name);
                }
            }
        }

        return '';
    }

    private function get_attribute_query_keys($attribute_name, $attribute_prefix): array
    {
        $attribute_name = sanitize_title((string) $attribute_name);
        $attribute_key = trim((string) $attribute_prefix);
        $attribute_key = $attribute_key !== '' ? sanitize_key($attribute_key) : sanitize_key($attribute_name);

        if ($attribute_name === '' || $attribute_key === '') {
            return array();
        }

        $keys = array($attribute_key);
        $safe_key = $this->get_collision_safe_attribute_query_key($attribute_name, $attribute_key);
        if ($safe_key !== '' && $safe_key !== $attribute_key) {
            $keys[] = $safe_key;
        }

        return array_values(array_unique($keys));
    }

    private function get_collision_safe_attribute_query_key(string $attribute_name, string $attribute_key): string
    {
        $reserved_category_keys = $this->get_category_alias_query_keys();

        if (in_array(sanitize_key($attribute_key), $reserved_category_keys, true)) {
            return sanitize_key('pa-' . $attribute_name);
        }

        return sanitize_key($attribute_key);
    }

    private function filter_values_exist_for_attribute(string $attribute_name, array $values): bool
    {
        $values = array_values(array_unique(array_filter(array_map(static function ($value) {
            return sanitize_title((string) $value);
        }, $values))));

        if (empty($values)) {
            return false;
        }

        if (function_exists('wc_attribute_taxonomy_name') && function_exists('taxonomy_exists') && function_exists('get_term_by')) {
            $taxonomy = wc_attribute_taxonomy_name($attribute_name);
            if ($taxonomy !== '' && taxonomy_exists($taxonomy)) {
                $all_terms_exist = true;
                foreach ($values as $value) {
                    if (!get_term_by('slug', $value, $taxonomy)) {
                        $all_terms_exist = false;
                        break;
                    }
                }

                if ($all_terms_exist) {
                    return true;
                }
            }
        }

        if (!function_exists('dapfforwc_get_woocommerce_attributes_with_terms')) {
            return false;
        }

        $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
        $terms = $all_data['attributes'][$attribute_name]['terms'] ?? array();
        if (empty($terms) || !is_array($terms)) {
            return false;
        }

        $available_slugs = array();
        foreach ($terms as $term) {
            if (is_array($term) && isset($term['slug'])) {
                $available_slugs[] = sanitize_title((string) $term['slug']);
            }
        }

        return empty(array_diff($values, array_values(array_unique($available_slugs))));
    }

    private function get_prefixed_query_keys(): array
    {

        $prefixes = $this->get_prefixes_config();
        $keys = array();
        $add = static function ($key) use (&$keys) {
            if ($key !== '') {
                $keys[] = $key;
            }
        };

        $add($this->resolve_prefix_key($prefixes, 'product-category', 'cata'));
        $add($this->resolve_prefix_key($prefixes, 'tag', 'tags'));
        $add($this->resolve_prefix_key($prefixes, 'brand', 'brand'));
        $add($this->resolve_prefix_key($prefixes, 'author', 'authors'));
        $add($this->resolve_prefix_key($prefixes, 'stock_status', 'stockStatus'));
        $add($this->resolve_prefix_key($prefixes, 'sale_status', 'saleStatus'));
        $add($this->resolve_prefix_key($prefixes, 'rating', 'rating'));
        $add($this->resolve_prefix_key($prefixes, 'plugincy_search', 'title'));
        $add($this->resolve_prefix_key($prefixes, 'price', 'price'));
        $add($this->resolve_prefix_key($prefixes, 'length', 'length'));
        $add($this->resolve_prefix_key($prefixes, 'width', 'width'));
        $add($this->resolve_prefix_key($prefixes, 'height', 'height'));
        $add($this->resolve_prefix_key($prefixes, 'weight', 'weight'));
        $add($this->resolve_prefix_key($prefixes, 'sku', 'sku'));
        $add($this->resolve_prefix_key($prefixes, 'discount', 'discount'));
        $add($this->resolve_prefix_key($prefixes, 'date_filter', 'date'));
        $pagination_key = $this->resolve_prefix_key($prefixes, 'pagination', 'paged');
        if ($pagination_key !== '' && $pagination_key !== 'paged' && $pagination_key !== 'product-page') {
            $add($pagination_key);
        }
        $orderby_key = $this->resolve_prefix_key($prefixes, 'orderby', 'orderby');
        if ($orderby_key !== '' && $orderby_key !== 'orderby') {
            $add($orderby_key);
        }

        if (isset($prefixes['attribute']) && is_array($prefixes['attribute'])) {
            foreach ($prefixes['attribute'] as $attr_name => $attr_prefix) {
                foreach ($this->get_attribute_query_keys($attr_name, $attr_prefix) as $attribute_key) {
                    $add($attribute_key);
                }
            }
        }

        if (isset($prefixes['custom']) && is_array($prefixes['custom'])) {
            foreach ($prefixes['custom'] as $custom_name => $custom_prefix) {
                $custom_key = $custom_prefix !== '' ? $custom_prefix : $custom_name;
                $add($custom_key);
            }
        }

        return array_values(array_unique($keys));
    }

    private function get_category_alias_query_keys(): array
    {
        $prefixes = $this->get_prefixes_config();

        return array_values(array_unique(array_filter(array(
            'product-category',
            $this->resolve_prefix_key($prefixes, 'product-category', 'cata'),
            'cata',
        ))));
    }

    /**
     * Remove product category clauses that were already injected from category URL aliases.
     *
     * The frontend can send both the canonical form field name (product-category)
     * and the configured permalink alias (cata by default). Those two inputs must
     * resolve to one authoritative product_cat tax query, otherwise product grids
     * can combine a native/default category clause with the plugin clause and
     * return no products.
     */
    private function remove_duplicate_category_alias_tax_clauses($tax_query, array $category_slugs): array
    {
        if (empty($tax_query) || !is_array($tax_query)) {
            return array();
        }

        $category_slugs = $this->normalize_filter_values($category_slugs);
        if (empty($category_slugs)) {
            return $tax_query;
        }

        $cleaned = array();
        $relation = '';

        foreach ($tax_query as $key => $clause) {
            if ($key === 'relation') {
                $relation = is_string($clause) ? strtoupper($clause) : '';
                continue;
            }

            if (!is_array($clause)) {
                $cleaned[] = $clause;
                continue;
            }

            if ($this->is_duplicate_category_alias_tax_clause($clause, $category_slugs)) {
                continue;
            }

            if (!isset($clause['taxonomy'])) {
                $nested = $this->remove_duplicate_category_alias_tax_clauses($clause, $category_slugs);
                if ($this->tax_query_has_clauses($nested)) {
                    $cleaned[] = $nested;
                }
                continue;
            }

            $cleaned[] = $clause;
        }

        if ($relation !== '' && count($cleaned) > 1) {
            $cleaned = array_merge(array('relation' => $relation), $cleaned);
        }

        return $cleaned;
    }

    private function is_duplicate_category_alias_tax_clause(array $clause, array $category_slugs): bool
    {
        if (!isset($clause['taxonomy'])) {
            return false;
        }

        $taxonomy = (string) $clause['taxonomy'];
        $operator = isset($clause['operator']) ? strtoupper((string) $clause['operator']) : 'IN';

        if ($taxonomy === 'product-category') {
            return in_array($operator, array('IN', 'AND'), true);
        }

        if ($taxonomy !== 'product_cat' || !in_array($operator, array('IN', 'AND'), true)) {
            return false;
        }

        if (!isset($clause['terms'])) {
            return false;
        }

        $terms = $this->normalize_filter_values($clause['terms']);
        if (empty($terms)) {
            return false;
        }

        $field = isset($clause['field']) ? strtolower((string) $clause['field']) : '';
        if (in_array($field, array('term_id', 'id', 'term_taxonomy_id'), true) || $this->values_are_numeric($terms)) {
            $category_ids = $this->get_product_category_ids_by_slugs($category_slugs);
            return !empty(array_intersect(array_map('absint', $terms), $category_ids));
        }

        return !empty(array_intersect($terms, $category_slugs));
    }

    private function tax_query_has_clauses(array $tax_query): bool
    {
        foreach ($tax_query as $key => $clause) {
            if ($key !== 'relation') {
                return true;
            }
        }

        return false;
    }

    private function values_are_numeric(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return !empty($values);
    }

    private function get_product_category_ids_by_slugs(array $category_slugs): array
    {
        $ids = array();

        foreach ($category_slugs as $slug) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $ids[] = absint($term->term_id);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Parse comma-separated IDs
     */
    private function parse_ids($ids_string)
    {
        if (empty($ids_string)) {
            return array();
        }

        $ids = array_map('trim', explode(',', $ids_string));
        $ids = array_filter($ids, 'is_numeric');
        $ids = array_map('intval', $ids);

        return array_unique($ids);
    }

    /**
     * Sanitize request values recursively (supports arrays).
     */
    private function sanitize_request_value($value)
    {
        if (is_array($value)) {
            return array_map(array($this, 'sanitize_request_value'), $value);
        }

        return sanitize_text_field($value);
    }

    /**
     * AJAX request handler
     */
    public function ajax_handler()
    {

        if (!isset($_GET['gm-product-filter-nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['gm-product-filter-nonce'])), 'gm-product-filter-action')) {
            return;
        }

        // Existing filter params
        $filter_params = array(
            'product-category',
            'tags',
            'mn_price',
            'mx_price',
            'rating',
            'plugincy_search',
            'rcata',
            'rtag',
            'cat_operator',
            'tag_operator',
            'terms_operator',
            'brand_operator',
            'tax_relation',
            'meta_relation',
            'per_page',
            'orderby',
            'order',
            'rplurand',
            'rplutock_status',
            'rpluthor', // This will now handle usernames
            'rpn_sale',
            'min_length',
            'max_length',
            'min_width',
            'max_width',
            'min_height',
            'max_height',
            'min_weight',
            'max_weight',
            'sku',
            'product_id',
            'discount',
            'new_arrivals',
            'date_filter',
            'date_from',
            'date_to',
            'paged',
            'product-page'
        );

        foreach ($filter_params as $param) {
            if (isset($_REQUEST[$param])) {
                $_GET[$param] = map_deep(wp_unslash($_REQUEST[$param]), 'sanitize_text_field');
            }
        }

        // Handle custom meta parameters
        foreach (array_keys($_REQUEST) as $key) {
            $request_key = sanitize_key($key);
            if (strpos($request_key, 'rplugcusf_') === 0 && isset($_REQUEST[$key])) {
                $_GET[$request_key] = map_deep(wp_unslash($_REQUEST[$key]), 'sanitize_text_field');
            }
        }

        // Handle attribute parameters
        foreach (array_keys($_REQUEST) as $key) {
            $request_key = sanitize_key($key);
            if (strpos($request_key, 'rplugpa_') === 0 && isset($_REQUEST[$key])) {
                $_GET[$request_key] = map_deep(wp_unslash($_REQUEST[$key]), 'sanitize_text_field');
            }
        }

        // Also honor configured prefix keys for attributes/custom meta when permalinks prefixes are enabled
        $prefixes = $this->get_prefixes_config();
        $pagination_prefix = trim((string) $this->resolve_prefix_key($prefixes, 'pagination', 'paged'));
        if ($pagination_prefix !== '' && isset($_REQUEST[$pagination_prefix])) {
            $_GET[$pagination_prefix] = map_deep(wp_unslash($_REQUEST[$pagination_prefix]), 'sanitize_text_field');
        }
        $orderby_prefix = trim((string) $this->resolve_prefix_key($prefixes, 'orderby', 'orderby'));
        if ($orderby_prefix !== '' && isset($_REQUEST[$orderby_prefix])) {
            $_GET[$orderby_prefix] = map_deep(wp_unslash($_REQUEST[$orderby_prefix]), 'sanitize_text_field');
        }

        if (isset($prefixes['attribute']) && is_array($prefixes['attribute'])) {
            foreach ($prefixes['attribute'] as $attribute_name => $attribute_prefix) {
                $attribute_prefix = trim((string) $attribute_prefix);
                if ($attribute_prefix !== '' && isset($_REQUEST[$attribute_prefix])) {
                    $_GET[$attribute_prefix] = map_deep(wp_unslash($_REQUEST[$attribute_prefix]), 'sanitize_text_field');
                }
            }
        }

        if (isset($prefixes['custom']) && is_array($prefixes['custom'])) {
            foreach ($prefixes['custom'] as $custom_name => $custom_prefix) {
                $custom_prefix = trim((string) $custom_prefix);
                if ($custom_prefix !== '' && isset($_REQUEST[$custom_prefix])) {
                    $_GET[$custom_prefix] = map_deep(wp_unslash($_REQUEST[$custom_prefix]), 'sanitize_text_field');
                }
            }
        }

        $this->reset_request_cache();
    }
}

// Initialize the plugin
DAPFFORWC_WC_Query_Filter_Enhanced::get_instance();

/**
 * Additional hooks for specific page builders and plugins
 */
add_action('wp_loaded', function () {
    $filter_instance = DAPFFORWC_WC_Query_Filter_Enhanced::get_instance();

    // Hook into Divi Builder
    if (class_exists('ET_Builder_Plugin')) {
        add_filter('et_builder_module_posts_query_args', array($filter_instance, 'filter_product_grid_args'), 10, 2);
    }

    // Hook into Beaver Builder
    if (class_exists('FLBuilder')) {
        add_filter('fl_builder_loop_query_args', array($filter_instance, 'filter_product_grid_args'), 10, 2);
    }

    // Hook into Oxygen Builder
    if (class_exists('OxygenElement')) {
        add_filter('oxygen_repeater_query_args', array($filter_instance, 'filter_product_grid_args'), 10, 2);
    }

    // Hook into Bricks Builder
    if (class_exists('Bricks\Database')) {
        add_filter('bricks/query/run', array($filter_instance, 'filter_product_grid_args'), 10, 2);
    }
});

/**
 * Force query modification for stubborn plugins
 */
add_action('wp', function () {
    if (is_admin()) {
        return;
    }

    $filter_instance = DAPFFORWC_WC_Query_Filter_Enhanced::get_instance();
    if (empty($filter_instance->get_filter_params())) {
        return;
    }

    // Force hook into get_posts without infinite recursion
    $get_posts_handler = null;
    $get_posts_handler = function ($posts, $parsed_args) use (&$get_posts_handler, $filter_instance) {
        if (isset($parsed_args['post_type']) && $parsed_args['post_type'] === 'product') {
            remove_filter('get_posts', $get_posts_handler, 10);
            $query = new WP_Query($parsed_args);
            $filter_instance->apply_filters_to_query($query);
            add_filter('get_posts', $get_posts_handler, 10, 2);
            return $query->get_posts();
        }
        return $posts;
    };

    add_filter('get_posts', $get_posts_handler, 10, 2);
});
