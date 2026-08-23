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
            <code>/shop/filters/color/blue</code>
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

function dapfforwc_permalinks_prefix_render()
{
    global $dapfforwc_seo_permalinks_options, $dapfforwc_advance_settings;
    $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
    $all_attributes = isset($all_data['attributes']) ? $all_data['attributes'] : [];
    $exclude_attributes = isset($dapfforwc_advance_settings['exclude_attributes']) ? explode(',', $dapfforwc_advance_settings['exclude_attributes']) : [];
    $custom_fields = isset($all_data['custom_fields']) ? $all_data['custom_fields'] : [];
    $exclude_custom_fields = isset($dapfforwc_advance_settings['exclude_custom_fields']) ? explode(',', $dapfforwc_advance_settings['exclude_custom_fields']) : [];
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
    $options = isset($dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options']) ? $dapfforwc_seo_permalinks_options['dapfforwc_permalinks_prefix_options'] : [
        "product-category" => 'cata',
        'tag' => 'tags',
        'price' => 'price',
        'rating' => 'rating',
        'brand' => 'brand',
        'author' => 'author',
        'stock_status' => 'stockStatus',
        'sale_status' => 'saleStatus',
        'length' => 'length',
        'min_length' => 'min_length',
        'max_length' => 'max_length',
        'width' => 'width',
        'min_width' => 'min_width',
        'max_width' => 'max_width',
        'height' => 'height',
        'min_height' => 'min_height',
        'max_height' => 'max_height',
        'weight' => 'weight',
        'min_weight' => 'min_weight',
        'max_weight' => 'max_weight',
        'sku' => 'sku',
        'discount' => 'discount',
        'discount' => 'discount',
        'date_filter' => 'date',
        'pagination' => 'paged',
        'plugincy_search' => 'title',
        'orderby' => 'orderby',
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
        'custom' => !empty($all_custom_fields) ? array_reduce($all_custom_fields, function ($carry, $attr) {
            $carry[$attr->attribute_name] = $attr->attribute_name;
            return $carry;
        }, []) : [],
    ];
    ob_start();
    ?>
        .attribute_prefix_list .prefix_list_container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            row-gap: 0;
        }

        .dapfforwc-form-group {
            margin-bottom: 15px;
        }

        .dapfforwc-form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .dapfforwc-form-group input[type="text"] {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .dapfforwc-form-group input[type="text"] {
                max-width: 100%;
            }
        }

        .dapfforwc-info-message {
            font-style: italic;
            color: #666;
            margin-top: 10px;
        }
    <?php
    dapfforwc_add_inline_style(ob_get_clean(), 'dapfforwc-admin-style');

    ob_start();
    ?>
        document.addEventListener('DOMContentLoaded', function() {
            const useAttributeTypeCheckbox = document.querySelector('input[name="dapfforwc_seo_permalinks_options[use_attribute_type_in_permalinks]"]');
            const attributePrefixList = document.querySelector('.attribute_prefix_list');
            const infoMessage = document.querySelector('.dapfforwc-info-message');

            function toggleAttributePrefixList() {
                if (useAttributeTypeCheckbox.checked) {
                    attributePrefixList.style.display = 'grid';
                    infoMessage.style.display = 'none';
                } else {
                    attributePrefixList.style.display = 'none';
                    infoMessage.style.display = 'block';
                }
            }

            if (useAttributeTypeCheckbox) {
                toggleAttributePrefixList(); // Initialize on page load
            }
            useAttributeTypeCheckbox.addEventListener('change', function() {
                toggleAttributePrefixList(); // Toggle on change
            });
        });
    <?php
    dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script');
    ?>
    <div class="attribute_prefix_list" style="margin-bottom: 10px;">
        <div class="prefix_list_container" style="height: 440px; overflow:hidden; transition: height 0.3s ease;">
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_category_prefix"><?php esc_html_e("product-category", 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_category_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][product-category]"
                    value="<?php echo isset($options["product-category"]) ? esc_attr($options["product-category"]) : 'cata'; ?>"
                    placeholder="<?php esc_attr_e("product-category", 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_tag_prefix"><?php esc_html_e('Tags', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_tag_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][tag]"
                    value="<?php echo isset($options['tag']) ? esc_attr($options['tag']) : 'tags'; ?>"
                    placeholder="<?php esc_attr_e('Tags', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_price_prefix"><?php esc_html_e('Price', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_price_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][price]"
                    value="<?php echo isset($options['price']) ? esc_attr($options['price']) : 'price'; ?>"
                    placeholder="<?php esc_attr_e('Price', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_rating_prefix"><?php esc_html_e('Rating', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_rating_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][rating]"
                    value="<?php echo isset($options['rating']) ? esc_attr($options['rating']) : 'rating'; ?>"
                    placeholder="<?php esc_attr_e('Rating', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_brand_prefix"><?php esc_html_e('Brand', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_brand_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][brand]"
                    value="<?php echo isset($options['brand']) ? esc_attr($options['brand']) : 'brand'; ?>"
                    placeholder="<?php esc_attr_e('Brand', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_author_prefix"><?php esc_html_e('Author', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_author_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][author]"
                    value="<?php echo isset($options['author']) ? esc_attr($options['author']) : 'author'; ?>"
                    placeholder="<?php esc_attr_e('Author', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_stock_status_prefix"><?php esc_html_e('Stock Status', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_stock_status_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][stock_status]"
                    value="<?php echo isset($options['stock_status']) ? esc_attr($options['stock_status']) : 'stockStatus'; ?>"
                    placeholder="<?php esc_attr_e('Stock Status', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_sale_status_prefix"><?php esc_html_e('Sale Status', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_sale_status_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][sale_status]"
                    value="<?php echo isset($options['sale_status']) ? esc_attr($options['sale_status']) : 'saleStatus'; ?>"
                    placeholder="<?php esc_attr_e('Sale Status', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_width_prefix"><?php esc_html_e('Width', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_width_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][width]"
                    value="<?php echo isset($options['width']) ? esc_attr($options['width']) : 'width'; ?>"
                    placeholder="<?php esc_attr_e('Width', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_min_width_prefix"><?php esc_html_e('Min Width', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_min_width_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][min_width]"
                    value="<?php echo isset($options['min_width']) ? esc_attr($options['min_width']) : 'min_width'; ?>"
                    placeholder="<?php esc_attr_e('Min Width', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_max_width_prefix"><?php esc_html_e('Max Width', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_max_width_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][max_width]"
                    value="<?php echo isset($options['max_width']) ? esc_attr($options['max_width']) : 'max_width'; ?>"
                    placeholder="<?php esc_attr_e('Max Width', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_length_prefix"><?php esc_html_e('Length', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_length_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][length]"
                    value="<?php echo isset($options['length']) ? esc_attr($options['length']) : 'length'; ?>"
                    placeholder="<?php esc_attr_e('Length', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_min_length_prefix"><?php esc_html_e('Min Length', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_min_length_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][min_length]"
                    value="<?php echo isset($options['min_length']) ? esc_attr($options['min_length']) : 'min_length'; ?>"
                    placeholder="<?php esc_attr_e('Min Length', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_max_length_prefix"><?php esc_html_e('Max Length', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_max_length_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][max_length]"
                    value="<?php echo isset($options['max_length']) ? esc_attr($options['max_length']) : 'max_length'; ?>"
                    placeholder="<?php esc_attr_e('Max Length', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_height_prefix"><?php esc_html_e('Height', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_height_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][height]"
                    value="<?php echo isset($options['height']) ? esc_attr($options['height']) : 'height'; ?>"
                    placeholder="<?php esc_attr_e('Height', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_min_height_prefix"><?php esc_html_e('Min Height', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_min_height_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][min_height]"
                    value="<?php echo isset($options['min_height']) ? esc_attr($options['min_height']) : 'min_height'; ?>"
                    placeholder="<?php esc_attr_e('Min Height', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_max_height_prefix"><?php esc_html_e('Max Height', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_max_height_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][max_height]"
                    value="<?php echo isset($options['max_height']) ? esc_attr($options['max_height']) : 'max_height'; ?>"
                    placeholder="<?php esc_attr_e('Max Height', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_weight_prefix"><?php esc_html_e('Weight', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_weight_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][weight]"
                    value="<?php echo isset($options['weight']) ? esc_attr($options['weight']) : 'weight'; ?>"
                    placeholder="<?php esc_attr_e('Weight', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_min_weight_prefix"><?php esc_html_e('Min Weight', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_min_weight_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][min_weight]"
                    value="<?php echo isset($options['min_weight']) ? esc_attr($options['min_weight']) : 'min_weight'; ?>"
                    placeholder="<?php esc_attr_e('Min Weight', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_max_weight_prefix"><?php esc_html_e('Max Weight', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_max_weight_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][max_weight]"
                    value="<?php echo isset($options['max_weight']) ? esc_attr($options['max_weight']) : 'max_weight'; ?>"
                    placeholder="<?php esc_attr_e('Max Weight', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_sku_prefix"><?php esc_html_e('SKU', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_sku_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][sku]"
                    value="<?php echo isset($options['sku']) ? esc_attr($options['sku']) : 'sku'; ?>"
                    placeholder="<?php esc_attr_e('SKU', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_discount_prefix"><?php esc_html_e('Discount', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_discount_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][discount]"
                    value="<?php echo isset($options['discount']) ? esc_attr($options['discount']) : 'discount'; ?>"
                    placeholder="<?php esc_attr_e('Discount', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>

            <div class="dapfforwc-form-group">
                <label for="dapfforwc_date_filter_prefix"><?php esc_html_e('Date Filter', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_date_filter_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][date_filter]"
                    value="<?php echo isset($options['date_filter']) ? esc_attr($options['date_filter']) : 'date'; ?>"
                    placeholder="<?php esc_attr_e('Date Filter', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_plugincy_search_prefix"><?php esc_html_e('Search', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_plugincy_search_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][plugincy_search]"
                    value="<?php echo isset($options['plugincy_search']) ? esc_attr($options['plugincy_search']) : 'title'; ?>"
                    placeholder="<?php esc_attr_e('Search', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_pagination_prefix"><?php esc_html_e('Pagination', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_pagination_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][pagination]"
                    value="<?php echo isset($options['pagination']) ? esc_attr($options['pagination']) : 'paged'; ?>"
                    placeholder="<?php esc_attr_e('Pagination', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <div class="dapfforwc-form-group">
                <label for="dapfforwc_orderby_prefix"><?php esc_html_e('Order By', 'dynamic-ajax-product-filters-for-woocommerce'); ?></label>
                <input type="text" id="dapfforwc_orderby_prefix" name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][orderby]"
                    value="<?php echo isset($options['orderby']) ? esc_attr($options['orderby']) : 'orderby'; ?>"
                    placeholder="<?php esc_attr_e('Order By', 'dynamic-ajax-product-filters-for-woocommerce'); ?>" />
            </div>
            <?php if (!empty($attributes)) : ?>
                <?php foreach ($attributes as $attribute) : ?>
                    <div class="dapfforwc-form-group">
                        <label for="dapfforwc_attribute_prefix_<?php echo esc_attr($attribute->attribute_name); ?>">
                            <?php
                            printf(
                                /* translators: %s: Product attribute label. */
                                esc_html__('Attribute (%s)', 'dynamic-ajax-product-filters-for-woocommerce'),
                                esc_html($attribute->attribute_label)
                            );
                            ?>
                        </label>
                        <input type="text" id="dapfforwc_attribute_prefix_<?php echo esc_attr($attribute->attribute_name); ?>"
                            name="dapfforwc_seo_permalinks_options[dapfforwc_permalinks_prefix_options][attribute][<?php echo esc_attr($attribute->attribute_name); ?>]"
                            value="<?php echo isset($options['attribute'][$attribute->attribute_name]) ? esc_attr($options['attribute'][$attribute->attribute_name]) : esc_attr($attribute->attribute_name); ?>"
                            <?php
                            $placeholder_text = sprintf(
                                /* translators: %s: Product attribute label. */
                                esc_attr__('Attribute (%s)', 'dynamic-ajax-product-filters-for-woocommerce'),
                                esc_html($attribute->attribute_label)
                            );
                            ?>
                            placeholder="<?php echo esc_html($placeholder_text); ?>" />
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div style=" text-align: center; ">
            <button type="button" id="dapfforwc_see_more_btn" style="margin-top: 10px; padding: 8px 16px; border-radius: 4px; border: 1px solid #ccc; background: #f7f7f7; cursor: pointer;">
                <?php esc_html_e('See More', 'dynamic-ajax-product-filters-for-woocommerce'); ?>
            </button>
        </div>
        <?php ob_start(); ?>
            document.addEventListener('DOMContentLoaded', function() {
                var seeMoreBtn = document.getElementById('dapfforwc_see_more_btn');
                var prefixListContainer = document.querySelector('.attribute_prefix_list > div');
                if (seeMoreBtn && prefixListContainer) {
                    seeMoreBtn.addEventListener('click', function() {
                        prefixListContainer.style.height = 'auto';
                        seeMoreBtn.style.display = 'none';
                    });
                }
            });
        <?php dapfforwc_add_inline_script(ob_get_clean(), 'dapfforwc-admin-script'); ?>
    </div>
    <div class="dapfforwc-info-message" style="display: none;">
        Enable <b>"Use Attribute Type in Permalinks"</b> to manage the prefix. Currently your URL format is: <code>?filters=compact-design,large,tag1</code>
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
