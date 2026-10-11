<?php
if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_use_attribute_type_in_permalinks_render()
{
    dapfforwc_render_checkbox('use_attribute_type_in_permalinks', "dapfforwc_seo_permalinks_options");
}

function dapfforwc_filters_word_in_permalinks_render()
{
?>
    <div class="dapfforwc-form-group pro-only">
        <input disabled type="text" id="dapfforwc_filters_word_in_permalinks" name="_pro"
            value="filters"
            placeholder="<?php esc_attr_e('filters', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
        <p class="description">
            <?php echo esc_html__('Choose the permalink segment used before filter values.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
            <code>/shop/filters/color=blue</code>
            <?php echo esc_html__('Use lowercase letters, numbers, or hyphens. Rewrite rules refresh when you save.', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
        </p>
    </div>
<?php
}

function dapfforwc_enable_seo_render()
{
    dapfforwc_render_checkbox('enable_seo', "dapfforwc_seo_permalinks_options");
}

function dapfforwc_use_anchor_render()
{
    dapfforwc_render_checkbox('use_anchor', 'dapfforwc_seo_permalinks_options');
}

/**
 * Fixed (non-attribute) prefix fields shown on the SEO page.
 *
 * "default" is the prefix the front end falls back to when the field has never been saved.
 * "attribute_type_only" marks prefixes that only appear in URLs when "Use Attribute Type in
 * Permalinks" is on; the others are also used by the compact `?filters=` URL format.
 */
function dapfforwc_get_seo_fixed_prefix_fields()
{
    return [
        'product-category' => ['id' => 'dapfforwc_category_prefix', 'label' => __('Categories', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'cata', 'attribute_type_only' => true],
        'tag' => ['label' => __('Tags', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'tags', 'attribute_type_only' => true],
        'price' => ['label' => __('Price', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'price', 'attribute_type_only' => true],
        'rating' => ['label' => __('Rating', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'rating', 'attribute_type_only' => true],
        'brand' => ['label' => __('Brand', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'brand', 'attribute_type_only' => true],
        'author' => ['label' => __('Author', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'authors', 'attribute_type_only' => true],
        'stock_status' => ['label' => __('Stock Status', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'stockStatus', 'attribute_type_only' => true],
        'sale_status' => ['label' => __('Sale Status', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'saleStatus', 'attribute_type_only' => true],
        'width' => ['label' => __('Width', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'width', 'attribute_type_only' => false],
        'min_width' => ['label' => __('Min Width', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'min_width', 'attribute_type_only' => true],
        'max_width' => ['label' => __('Max Width', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'max_width', 'attribute_type_only' => true],
        'length' => ['label' => __('Length', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'length', 'attribute_type_only' => false],
        'min_length' => ['label' => __('Min Length', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'min_length', 'attribute_type_only' => true],
        'max_length' => ['label' => __('Max Length', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'max_length', 'attribute_type_only' => true],
        'height' => ['label' => __('Height', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'height', 'attribute_type_only' => false],
        'min_height' => ['label' => __('Min Height', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'min_height', 'attribute_type_only' => true],
        'max_height' => ['label' => __('Max Height', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'max_height', 'attribute_type_only' => true],
        'weight' => ['label' => __('Weight', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'weight', 'attribute_type_only' => false],
        'min_weight' => ['label' => __('Min Weight', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'min_weight', 'attribute_type_only' => true],
        'max_weight' => ['label' => __('Max Weight', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'max_weight', 'attribute_type_only' => true],
        'sku' => ['label' => __('SKU', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'sku', 'attribute_type_only' => false],
        'discount' => ['label' => __('Discount', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'discount', 'attribute_type_only' => false],
        'date_filter' => ['label' => __('Date Filter', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'date', 'attribute_type_only' => false],
        'plugincy_search' => ['label' => __('Search', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'title', 'attribute_type_only' => false],
        'pagination' => ['label' => __('Pagination', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'paged', 'attribute_type_only' => false],
        'orderby' => ['label' => __('Order By', 'dynamic-ajax-product-filters-for-woocommerce'), 'default' => 'orderby', 'attribute_type_only' => false],
    ];
}

/**
 * Prints one prefix input. $group is '' for fixed prefixes, or 'attribute'.
 */
function dapfforwc_render_seo_prefix_field($id, $group, $key, $label, $value, $attribute_type_only)
{
    $name = 'dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options]' . ($group !== '' ? '[' . $group . ']' : '') . '[' . $key . ']';
?>
    <div class="dapfforwc-form-group"<?php echo $attribute_type_only ? ' data-attribute-type-only="1"' : ''; ?>>
        <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
        <input type="text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"
            value="<?php echo esc_attr($value); ?>"
            placeholder="<?php echo esc_attr($label); ?>" />
    </div>
<?php
}

/**
 * Normalizes an "exclude" advanced setting (comma string or list) to a list of names.
 */
function dapfforwc_seo_prefix_exclusion_list($value)
{
    $items = is_array($value) ? $value : explode(',', (string) $value);
    return array_values(array_filter(array_map(static function ($item) {
        return is_scalar($item) ? trim((string) $item) : '';
    }, $items), 'strlen'));
}

function dapfforwc_permalinks_prefix_render()
{
    global $dapfforwc_seo_permalinks_options, $dapfforwc_advance_settings;
    $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
    $all_attributes = isset($all_data['attributes']) ? $all_data['attributes'] : [];
    $exclude_attributes = dapfforwc_seo_prefix_exclusion_list(isset($dapfforwc_advance_settings['exclude_attributes']) ? $dapfforwc_advance_settings['exclude_attributes'] : []);
    $options = isset($dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options']) && is_array($dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options'])
        ? $dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options']
        : [];

    // Excluded attributes get no input here; the settings sanitizer merges their saved prefix back on save.
    $attributes = [];
    foreach ($all_attributes as $attribute) {
        $name = (string) $attribute['attribute_name'];
        if (in_array($name, $exclude_attributes, true)) {
            continue;
        }
        /* translators: %s: Product attribute label. */
        $attributes[$name] = sprintf(__('Attribute (%s)', 'dynamic-ajax-product-filters-for-woocommerce'), $attribute['attribute_label']);
    }
?>
    <div class="attribute_prefix_list">
        <div class="prefix_list_container" id="dapfforwc-prefix-list">
            <?php
            foreach (dapfforwc_get_seo_fixed_prefix_fields() as $key => $field) {
                $id = isset($field['id']) ? $field['id'] : 'dapfforwc_' . $key . '_prefix';
                $value = isset($options[$key]) && is_scalar($options[$key]) ? (string) $options[$key] : $field['default'];
                dapfforwc_render_seo_prefix_field($id, '', $key, $field['label'], $value, $field['attribute_type_only']);
            }
            foreach ($attributes as $name => $label) {
                $value = isset($options['attribute'][$name]) && is_scalar($options['attribute'][$name]) ? (string) $options['attribute'][$name] : $name;
                dapfforwc_render_seo_prefix_field('dapfforwc_attribute_prefix_' . $name, 'attribute', $name, $label, $value, true);
            }
            ?>
        </div>
        <div class="dapfforwc-see-more-wrap">
            <button type="button" id="dapfforwc_see_more_btn" class="dapfforwc-see-more-btn" aria-controls="dapfforwc-prefix-list" aria-expanded="false">
                <?php esc_html_e('See More', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
            </button>
        </div>
    </div>
    <div class="dapfforwc-info-message" style="display: none;">
        <?php
        echo wp_kses(
            sprintf(
                /* translators: %1$s: name of the "Use Attribute Type in Permalinks" setting, %2$s: example URL query. */
                __('Enable %1$s to manage the category, tag, attribute and other taxonomy prefixes. The prefixes shown above still apply. Currently your URL format is: %2$s', 'dynamic-ajax-product-filters-for-woocommerce'),
                '<b>"' . esc_html__('Use Attribute Type in Permalinks', 'dynamic-ajax-product-filters-for-woocommerce') . '"</b>',
                '<code>?filters=compact-design,large,tag1</code>'
            ),
            array('b' => array(), 'code' => array())
        );
        ?>
    </div>
<?php
}

function dapfforwc_seo_title_callback()
{
?>
    <div class="dapfforwc-form-group pro-only">
        <input disabled type="text" id="dapfforwc_seo_title" name="_pro[seo_title]"
            placeholder="<?php esc_attr_e('Enter SEO Title', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
        <p class="description"><?php echo esc_html__('Set a custom SEO title for your permalinks.', 'dynamic-ajax-product-filters-for-woocommerce'); ?> <code>{site_title} {page_title} {attribute_prefix} {value}</code></p>
    </div>
<?php
}

function dapfforwc_seo_description_callback()
{
?>
    <div class="dapfforwc-form-group pro-only">
        <textarea disabled id="dapfforwc_seo_description" name="_pro[seo_description]"
            rows="4"
            style="width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
            placeholder="<?php esc_attr_e('Enter SEO Description', 'dynamic-ajax-product-filters-for-woocommerce'); ?>"></textarea>
        <p class="description"><?php echo esc_html__('Set a custom SEO description for your permalinks.', 'dynamic-ajax-product-filters-for-woocommerce'); ?> <code>{site_title} {page_title} {attribute_prefix} {value}</code></p>
    </div>
<?php
}

function dapfforwc_seo_keywords_callback()
{
?>
    <div class="dapfforwc-form-group pro-only">
        <input type="text" id="dapfforwc_seo_keywords" name="_pro[seo_keywords]"
            placeholder="<?php esc_attr_e('Enter SEO Keywords', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
        <p class="description"><?php echo esc_html__('Set custom SEO keywords for your permalinks.', 'dynamic-ajax-product-filters-for-woocommerce'); ?> <code>{site_title} {page_title} {attribute_prefix} {value}</code></p>
    </div>
<?php
}
