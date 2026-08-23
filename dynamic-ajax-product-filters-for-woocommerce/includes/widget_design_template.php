<?php
if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_filter_form($updated_filters, $default_filter, $use_anchor, $use_filters_word, $atts, $min_price, $max_price, $min_max_prices, $search_txt = '', $is_filters_in_url = true, $disable_unselected = false, $dimension_bounds = [])
{
    global $dapfforwc_styleoptions, $post, $dapfforwc_options, $dapfforwc_advance_settings;
    $dapfforwc_product_count = [];

    $filter_enabled = static function ($option_key) use ($dapfforwc_options) {
        return isset($dapfforwc_options[$option_key]) && $dapfforwc_options[$option_key] === 'on';
    };


    $search_icon = '<svg fill="#fff" xmlns="http://www.w3.org/2000/svg" viewBox="-100.62 53.746 16 16" xml:space="preserve" width="16" height="16"><path d="M-88.721 64.079h-.71l-.27-.27c.89-.98 1.42-2.31 1.42-3.73 0-3.2-2.58-5.78-5.78-5.78s-5.78 2.58-5.78 5.78 2.58 5.78 5.78 5.78c1.42 0 2.749-.53 3.73-1.42l.27.27v.71l4.439 4.439 1.33-1.33zm-5.33 0c-2.22 0-4-1.78-4-4s1.78-4 4-4 4 1.78 4 4c-.011 2.231-1.78 4-4 4"/></svg>';
    $question_icon = '<svg width="16" height="16" viewBox="0 0 20.48 20.48" xmlns="http://www.w3.org/2000/svg"><path d="M10.24 0C4.585 0 0 4.585 0 10.24s4.585 10.24 10.24 10.24 10.24-4.584 10.24-10.24C20.48 4.585 15.896 0 10.24 0m0 19.22c-4.94 0-8.96-4.04-8.96-8.98s4.02-8.96 8.96-8.96 8.96 4.02 8.96 8.96-4.019 8.98-8.96 8.98m-.941-3.211h1.61v-1.625h-1.61zm.922-11.539q-1.407 0-2.317.758c-.607.505-.902 1.517-.887 2.356l.024.047H8.51c0-.5.167-1.219.5-1.477q.5-.387 1.211-.387.82 0 1.261.445.441.446.441 1.273 0 .696-.327 1.188-.329.492-1.102 1.406-.798.719-.985 1.156-.187.438-.195 1.57h1.539q0-.711.09-1.047t.511-.758q.906-.874 1.458-1.711.55-.836.55-1.844 0-1.407-.852-2.191-.852-.786-2.391-.785"/></svg>';
    $default_terms_search_placeholder = esc_html__('Type to search...', 'dynamic-ajax-product-filters-for-woocommerce');

    $get_terms_search_settings = function ($filter_key) use ($dapfforwc_styleoptions, $default_terms_search_placeholder) {
        return [
            'enabled' => $dapfforwc_styleoptions["enable_terms_search"][$filter_key] ?? "no",
            'placeholder' => isset($dapfforwc_styleoptions["terms_search_texts"][$filter_key]["placeholder"]) ? esc_attr($dapfforwc_styleoptions["terms_search_texts"][$filter_key]["placeholder"]) : $default_terms_search_placeholder,
            'position' => isset($dapfforwc_styleoptions["terms_search_position"][$filter_key]) ? esc_attr($dapfforwc_styleoptions["terms_search_position"][$filter_key]) : 'after_title',
        ];
    };

    $get_tooltip_settings = function ($filter_key) use ($dapfforwc_styleoptions) {
        return [
            'enabled' => $dapfforwc_styleoptions["enable_tooltip"][$filter_key] ?? "no",
            'message' => $dapfforwc_styleoptions["tooltip_text"][$filter_key] ?? "",
        ];
    };

    $render_title_bar = function ($title_html, $minimizable, $search_settings, $tooltip_settings, $attribute = '') use ($search_icon, $question_icon) {
        global $dapfforwc_styleoptions;
        $show_title = !isset($dapfforwc_styleoptions[$attribute]["show_widget_title"]) || (isset($dapfforwc_styleoptions[$attribute]["show_widget_title"]) && $dapfforwc_styleoptions[$attribute]["show_widget_title"] === "yes");
        $sub_option = isset($dapfforwc_styleoptions[$attribute]["sub_option"]) ? $dapfforwc_styleoptions[$attribute]["sub_option"] : 'checkbox';
        $title = '<div class="plugincy_title plugincy_collapsable_' . esc_attr($minimizable) . '">';
        $title .= $show_title ? '<span>' . $title_html . '</span>' : '';
        $title .= '<div style="display: flex;align-items: center;gap: 13px;' . ($show_title ? '' : 'width: 100%') . ';justify-content: flex-end;">';
        $title .= ($tooltip_settings['enabled'] === "yes" ? '<span class="tooltip-icon" style="cursor: help;" title="' . esc_attr($tooltip_settings['message']) . '">' . $question_icon . '</span>' : '');
        $title .= ($minimizable === "arrow" || $minimizable === "minimize_initial" ? '<div class="collaps"><svg class="rotatable" xmlns="https://www.w3.org/2000/svg" viewBox="0 0 448 512" role="graphics-symbol" aria-hidden="false" aria-label=""><path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"></path></svg></div>' : '') . '</div></div>';
        return $title;
    };

    $render_search_container = function ($search_settings, $sub_option, $search_type) use ($search_icon) {
        if ($search_settings['enabled'] === "yes" && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") && $search_settings['position'] !== 'after_all_terms') {
            $search_container_class = '';
            return '<div class="search-container ' . esc_attr($search_settings['position']) . $search_container_class . '" style="margin-bottom:20px;">'
                . '<input type="text" class="search-field search-terms" placeholder="' . esc_attr($search_settings['placeholder']) . '" data-search-type="' . esc_attr($search_type) . '">'
                . '<button class="plugincy-term-search-submit">' . $search_icon . '</button>'
                . '</div>';
        }
        return '';
    };

    $render_search_after_terms = function ($search_settings, $sub_option, $search_type) use ($search_icon) {
        if ($search_settings['enabled'] === "yes" && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") && $search_settings['position'] === 'after_all_terms') {
            return '<div class="search-container ' . $search_settings['position'] . '">'
                . '<input type="text" class="search-field search-terms" placeholder="' . esc_attr($search_settings['placeholder']) . '" data-search-type="' . esc_attr($search_type) . '">'
                . '<button class="plugincy-term-search-submit">' . $search_icon . '</button>'
                . '</div>';
        }
        return '';
    };

    $get_layout_settings = function ($filter_key) use ($dapfforwc_styleoptions) {
        return [
            'layout' => $dapfforwc_styleoptions["layout"][$filter_key] ?? "",
            'num_columns' => $dapfforwc_styleoptions["num_columns"][$filter_key] ?? "1",
        ];
    };

    $render_layout_start = function ($layout_settings, $sub_option) {
        $supports_term_layout = ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic");
        if ($layout_settings['layout'] === "horizontal" && $supports_term_layout) {
            $columns = max(1, absint($layout_settings['num_columns']));
            return '<div class="plugincy-terms-layout-horizontal" style="display: grid; grid-template-columns: repeat(' . esc_attr((string) $columns) . ', minmax(0, 1fr)); column-gap: 10px; row-gap: 10px; width: 100%;">';
        }
        if ($layout_settings['layout'] === "vertical" && $supports_term_layout) {
            return '<div class="plugincy-terms-layout-vertical">';
        }
        return '';
    };

    $render_layout_end = function ($layout_settings, $sub_option) {
        $supports_term_layout = ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic");
        if (($layout_settings['layout'] === "horizontal" || $layout_settings['layout'] === "vertical") && $supports_term_layout) {
            return '</div>';
        }
        return '';
    };

    $show_apply_reset_on = isset($dapfforwc_styleoptions["show_apply_reset_on"]["reset_btn"]) ? $dapfforwc_styleoptions["show_apply_reset_on"]["reset_btn"] : "separate";
    $show_reset_btn = isset($dapfforwc_styleoptions["show_reset_button"]["reset_btn"]) ? $dapfforwc_styleoptions["show_reset_button"]["reset_btn"] : "no";
    $show_apply_btn = isset($dapfforwc_styleoptions["show_apply_button"]["reset_btn"]) ? $dapfforwc_styleoptions["show_apply_button"]["reset_btn"] : "no";
    $show_filter_heading = isset($dapfforwc_styleoptions['reset_btn']['show_filter_heading']) ? $dapfforwc_styleoptions['reset_btn']['show_filter_heading'] : 'no';
    $filter_heading_text = isset($dapfforwc_styleoptions['reset_btn']['filter_heading_text']) ? trim((string) $dapfforwc_styleoptions['reset_btn']['filter_heading_text']) : '';

    // Extract category counts
    $dapfforwc_product_count['categories'] = [];
    if (isset($updated_filters['categories']) && is_array($updated_filters['categories'])) {
        foreach ($updated_filters['categories'] as $category) {
            // Ensure $category has the properties you're accessing
            if (isset($category->slug) && isset($category->count)) {
                $dapfforwc_product_count['categories'][$category->slug] = $category->count;
            }
        }
    }

    // Extract tag counts
    $dapfforwc_product_count['tags'] = [];

    // Check if 'tags' exists and is an array
    if (isset($updated_filters['tags']) && is_array($updated_filters['tags'])) {
        foreach ($updated_filters['tags'] as $tag) {
            // Ensure $tag has the properties you're accessing
            if (isset($tag->slug) && isset($tag->count)) {
                $dapfforwc_product_count['tags'][$tag->slug] = $tag->count;
            }
        }
    }

    // Extract brands counts
    $dapfforwc_product_count['brands'] = [];

    // Check if 'brands' exists and is an array
    if (isset($updated_filters['brands']) && is_array($updated_filters['brands'])) {
        foreach ($updated_filters['brands'] as $brand) {
            // Ensure $brand has the properties you're accessing
            if (isset($brand->slug) && isset($brand->count)) {
                $dapfforwc_product_count['brands'][$brand->slug] = $brand->count;
            }
        }
    }

    // Extract attribute counts
    $dapfforwc_product_count['attributes'] = [];

    // Check if 'attributes' exists and is an array
    if (isset($updated_filters['attributes']) && is_array($updated_filters['attributes'])) {
        foreach ($updated_filters['attributes'] as $key => $terms) {
            // Initialize the key in the attributes array
            $dapfforwc_product_count['attributes'][$key] = [];

            // Check if $terms is an array
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    // Ensure $term has the properties you're accessing
                    $slug = is_object($term) ? esc_attr($term->slug) : esc_attr($term['slug']);
                    $count = is_object($term) ? esc_attr($term->count) : esc_attr(isset($term['count']) ? $term['count'] : 0);
                    $dapfforwc_product_count['attributes'][$key][$slug] = $count;
                }
            }
        }
    }

    // Extract custom field counts
    $dapfforwc_product_count['custom_fields'] = [];

    // Check if 'custom_fields' exists and is an array
    if (isset($updated_filters['custom_fields']) && is_array($updated_filters['custom_fields'])) {
        foreach ($updated_filters['custom_fields'] as $key => $terms) {
            // Initialize the key in the custom_fields array
            $dapfforwc_product_count['custom_fields'][$key] = [];

            // Check if $terms is an array
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    // Ensure $term has the properties you're accessing
                    $slug = is_object($term) ? esc_attr($term->slug) : esc_attr($term['slug']);
                    $count = is_object($term) ? esc_attr($term->count) : esc_attr(isset($term['count']) ? $term['count'] : 0);
                    $dapfforwc_product_count['custom_fields'][$key][$slug] = $count;
                }
            }
        }
    }

    // Extract authors counts
    $dapfforwc_product_count['authors'] = [];
    if (isset($updated_filters['authors']) && is_array($updated_filters['authors'])) {
        foreach ($updated_filters['authors'] as $author) {
            $slug = is_object($author) ? (string) ($author->slug ?? '') : (string) ($author['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $count = is_object($author) ? intval($author->count ?? 0) : intval($author['count'] ?? 0);
            $dapfforwc_product_count['authors'][$slug] = $count;
        }
    }

    // Extract stock status counts
    $dapfforwc_product_count['status'] = [];
    if (isset($updated_filters['stock_status']) && is_array($updated_filters['stock_status'])) {
        foreach ($updated_filters['stock_status'] as $stock_status) {
            $slug = is_object($stock_status) ? (string) ($stock_status->slug ?? '') : (string) ($stock_status['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $count = is_object($stock_status) ? intval($stock_status->count ?? 0) : intval($stock_status['count'] ?? 0);
            $dapfforwc_product_count['status'][$slug] = $count;
        }
    }

    // Extract sale status counts
    $dapfforwc_product_count['sale_status'] = [];
    if (isset($updated_filters['sale_status']) && is_array($updated_filters['sale_status'])) {
        foreach ($updated_filters['sale_status'] as $sale_status) {
            $slug = is_object($sale_status) ? (string) ($sale_status->slug ?? '') : (string) ($sale_status['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $count = is_object($sale_status) ? intval($sale_status->count ?? 0) : intval($sale_status['count'] ?? 0);
            $dapfforwc_product_count['sale_status'][$slug] = $count;
        }
    }

    $formOutPut = "";

?>
    
    <?php
    // display search
    // Initialize variables with default values
    $sub_option = "plugincy_search";
    $minimizable = "arrow";

    // Check if 'search' key exists in the style options
    if (isset($dapfforwc_styleoptions['search'])) {
        // Fetch the sub_option value safely
        $sub_option = $dapfforwc_styleoptions['search']['sub_option'] ?? $sub_option;

        // Check if 'minimize' key exists and fetch its type
        if (isset($dapfforwc_styleoptions['search']['minimize'])) {
            $minimizable = $dapfforwc_styleoptions['search']['minimize']['type'] ?? $minimizable;
        }
    }

    $search_filter_search_settings = $get_terms_search_settings('search');
    $search_filter_tooltip_settings = $get_tooltip_settings('search');
    $search_layout_settings = $get_layout_settings('search');

    if ($filter_enabled('show_search')) {
        $search_placeholder = isset($dapfforwc_styleoptions["placeholder"]["search"]) && $dapfforwc_styleoptions["placeholder"]["search"] !== ""
            ? $dapfforwc_styleoptions["placeholder"]["search"]
            : __('Search Product', 'dynamic-ajax-product-filters-for-woocommerce');
        $search_value = $search_txt !== '' ? $search_txt : ($default_filter["plugincy_search"] ?? '');
        $search_value = is_scalar($search_value) ? (string) $search_value : '';
        $formOutPut .= '<div id="search_text" class="plugincy-filter-group search_text ' . (isset($dapfforwc_styleoptions['css_class']['search']) ? $dapfforwc_styleoptions['css_class']['search'] : '') . '">';
        $formOutPut .= $render_title_bar(((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["search"]) && $dapfforwc_styleoptions["widget_title"]["search"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["search"]) : esc_html__('Search Product', 'dynamic-ajax-product-filters-for-woocommerce')), $minimizable, $search_filter_search_settings, $search_filter_tooltip_settings, "search");
        $search_container_class = '';
        $formOutPut .= '<div class="items">';
        $formOutPut .= $render_layout_start($search_layout_settings, $sub_option);
        $formOutPut .= '<div class="search-container' . $search_container_class . '">';
        $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="search" id="plugincy-search-field" class="search-field" placeholder="' . esc_attr($search_placeholder) . '&hellip;" value="' . esc_attr($search_value) . '" name="plugincy_search"/>';
        if ($sub_option === "icon_search") {
            $formOutPut .= ' <button class="plugincy-search-submit">' . $search_icon . '</button>';
        } else {
            $formOutPut .= ' <button class="plugincy-search-submit">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["search"]) && $dapfforwc_styleoptions["btntext"]["search"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["search"]) : esc_html__('Search', 'dynamic-ajax-product-filters-for-woocommerce')) . '</button>';
        }
        $formOutPut .= '</div>';
        $formOutPut .= $render_layout_end($search_layout_settings, $sub_option);
        $formOutPut .= '</div>';
        $formOutPut .= '</div>';
    }
    // search ends

    // Initialize variables with default values
    $sub_option = "price";
    $minimizable_price = "arrow";
    $sub_option_rating = "rating-text";
    $minimizable_rating = "arrow";

    // Check if 'price' key exists in the style options
    if (isset($dapfforwc_styleoptions['price'])) {
        // Fetch the sub_option value safely
        $sub_option = isset($dapfforwc_styleoptions['price']['sub_option']) ? $dapfforwc_styleoptions['price']['sub_option'] : $sub_option;

        // Check if 'minimize' key exists and fetch its type
        if (isset($dapfforwc_styleoptions['price']['minimize'])) {
            $minimizable_price = isset($dapfforwc_styleoptions['price']['minimize']['type']) ? $dapfforwc_styleoptions['price']['minimize']['type'] : $minimizable_price;
        }
    }

    // Check if 'rating' key exists in the style options
    if (isset($dapfforwc_styleoptions['rating'])) {
        // Fetch the sub_option value safely
        $sub_option_rating = isset($dapfforwc_styleoptions['rating']['sub_option']) ? $dapfforwc_styleoptions['rating']['sub_option'] : $sub_option_rating;
        if ($sub_option_rating === 'dynamic-rating') {
            $sub_option_rating = 'rating';
        }

        // Check if 'minimize' key exists and fetch its type
        if (isset($dapfforwc_styleoptions['rating']['minimize'])) {
            $minimizable_rating = isset($dapfforwc_styleoptions['rating']['minimize']['type']) ? $dapfforwc_styleoptions['rating']['minimize']['type'] : $minimizable_rating;
        }
    }
    $rating_search_settings = $get_terms_search_settings('rating');
    $rating_tooltip_settings = $get_tooltip_settings('rating');
    $rating_layout_settings = $get_layout_settings('rating');
    $price_layout_settings = $get_layout_settings('price');
    ?>
      
<?php
    if ($filter_enabled('show_rating')) {
        $formOutPut .= '<div id="plugincy_rating" class="plugincy-filter-group rating ' . (isset($dapfforwc_styleoptions['css_class']['rating']) ? $dapfforwc_styleoptions['css_class']['rating'] : '') . '">';
        $rating_title_content = (isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["rating"]) && $dapfforwc_styleoptions["widget_title"]["rating"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["rating"]) : esc_html__('Rating', 'dynamic-ajax-product-filters-for-woocommerce');
        if ($show_reset_btn === "yes" && $show_apply_reset_on === "separate") {
            $rating_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($rating_title_content, $minimizable_rating, $rating_search_settings, $rating_tooltip_settings, "rating");
        $formOutPut .= '<div class="items rating ' . ($sub_option_rating === "dynamic-rating"  ? "rating" : esc_attr($sub_option_rating)) . '">';
        $formOutPut .= $render_search_container($rating_search_settings, $sub_option_rating, 'rating');
        $formOutPut .= $render_layout_start($rating_layout_settings, $sub_option_rating);
        if ($sub_option_rating) {
            $formOutPut .=  dapfforwc_render_filter_option($sub_option_rating, "", "", $checked = isset($default_filter['rating[]']) ? $default_filter['rating[]'] : [], $dapfforwc_styleoptions, "", "", "", "", 0, null, [], $disable_unselected);
        } else {
            $formOutPut .= "Choose style from product filters->form style -> rating";
        }
        $formOutPut .= $render_layout_end($rating_layout_settings, $sub_option_rating);
        $formOutPut .= $render_search_after_terms($rating_search_settings, $sub_option_rating, 'rating');
        $formOutPut .= '</div></div>';
    }
?>

   <?php
    $price_search_settings = $get_terms_search_settings('price');
    $price_tooltip_settings = $get_tooltip_settings('price');
    if ($filter_enabled('show_price_range')) {
        $formOutPut .= '<div id="price-range" class="plugincy-filter-group price-range ' . (isset($dapfforwc_styleoptions['css_class']['price']) ? $dapfforwc_styleoptions['css_class']['price'] : '') . '">';
        $price_title_content = (isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["price"]) && $dapfforwc_styleoptions["widget_title"]["price"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["price"]) : esc_html__('Price Range', 'dynamic-ajax-product-filters-for-woocommerce');
        if ($show_reset_btn === "yes" && $show_apply_reset_on === "separate") {
            $price_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($price_title_content, $minimizable_price, $price_search_settings, $price_tooltip_settings, "price");
        $formOutPut .= '<div class="items ' . ($sub_option !== "slider" ? $sub_option : '') . '">';
        $formOutPut .= $render_search_container($price_search_settings, $sub_option, 'price');
        $formOutPut .= $render_layout_start($price_layout_settings, $sub_option);
        if ($sub_option) {
            $formOutPut .=  dapfforwc_render_filter_option($sub_option, "", "", "", $dapfforwc_styleoptions, "", "", "", "", $min_price, $max_price, $min_max_prices, $disable_unselected);
        } else {
            $formOutPut .= "Choose style from product filters->form style -> price";
        }
        $formOutPut .= $render_layout_end($price_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($price_search_settings, $sub_option, 'price');

        $formOutPut .= '</div></div>';
    }
    // Fetch global options and style configurations

    $sub_option = '';
    $minimizable = 'arrow';
    $show_count = '';
    $singlevaluecataSelect = '';
    $hierarchical = '';

    // Additional checks to ensure the structure exists before accessing
    if (isset($dapfforwc_styleoptions["product-category"])) {
        $sub_option = isset($dapfforwc_styleoptions["product-category"]['sub_option']) ? $dapfforwc_styleoptions["product-category"]['sub_option'] : '';

        if (isset($dapfforwc_styleoptions["product-category"]['minimize'])) {
            $minimizable = isset($dapfforwc_styleoptions["product-category"]['minimize']['type']) ? $dapfforwc_styleoptions["product-category"]['minimize']['type'] : '';
        } else {
            $minimizable = '';
        }

        $show_count = isset($dapfforwc_styleoptions["product-category"]['show_product_count']) ? $dapfforwc_styleoptions["product-category"]['show_product_count'] : '';
        $singlevaluecataSelect = isset($dapfforwc_styleoptions["product-category"]['single_selection']) ? $dapfforwc_styleoptions["product-category"]['single_selection'] : '';

        if (isset($dapfforwc_styleoptions["product-category"]['hierarchical'])) {
            $hierarchical = isset($dapfforwc_styleoptions["product-category"]['hierarchical']['type']) ? $dapfforwc_styleoptions["product-category"]['hierarchical']['type'] : '';
        } else {
            $hierarchical = '';
        }
    }

    $category_search_settings = $get_terms_search_settings('product-category');
    $category_tooltip_settings = $get_tooltip_settings('product-category');
    $category_layout_settings = $get_layout_settings('product-category');

    if ($filter_enabled('show_categories')) {
        $updated_filters["categories"] = isset($updated_filters["categories"]) && is_array($updated_filters["categories"]) ? $updated_filters["categories"] : [];

        // include/exclude categories
        $include_exclude_categories = isset($dapfforwc_styleoptions['terms']['product-category']) ? $dapfforwc_styleoptions['terms']['product-category'] : [];
        $include_or_exclude_categories_option = $dapfforwc_styleoptions['include_exclude']['product-category'] ?? 'none';

    // Filter categories based on include/exclude settings
    if (!empty($include_exclude_categories) && $include_or_exclude_categories_option === 'include') {
        $updated_filters["categories"] = array_filter($updated_filters["categories"], function ($category) use ($include_exclude_categories) {
            return dapfforwc_term_matches_stored_filter_value($category, $include_exclude_categories, 'product-category');
        });
    } elseif (!empty($include_exclude_categories) && $include_or_exclude_categories_option === 'exclude') {
        $updated_filters["categories"] = array_filter($updated_filters["categories"], function ($category) use ($include_exclude_categories) {
            return !dapfforwc_term_matches_stored_filter_value($category, $include_exclude_categories, 'product-category');
        });
    }

    $category_order_by = $dapfforwc_styleoptions["product-category"]["order_by"] ?? "default";
    $category_order = $dapfforwc_styleoptions["product-category"]["order_direction"] ?? "asc";

    // Sort categories if needed
    if ($category_order_by !== "default") {
        $updated_filters["categories"] = dapfforwc_sort_terms($updated_filters["categories"], $category_order_by, $category_order);
    }

    $selected_categories = !empty($default_filter) && isset($default_filter["product-category[]"]) ? $default_filter["product-category[]"] : [];

    // Fetch categories

    // Render categories based on hierarchical mode
    if ($hierarchical !== 'enable_separate' && !empty($updated_filters["categories"])) {
        $formOutPut .= '<div id="product-category" class="plugincy-filter-group category ' . (isset($dapfforwc_styleoptions['css_class']['product-category']) ? $dapfforwc_styleoptions['css_class']['product-category'] : '') . '">';
        $category_title_content = (isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["product-category"]) && $dapfforwc_styleoptions["widget_title"]["product-category"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["product-category"]) : esc_html__('Category', 'dynamic-ajax-product-filters-for-woocommerce');
        if ($show_reset_btn === "yes" && $singlevaluecataSelect === "yes" && $show_apply_reset_on === "separate") {
            $category_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($category_title_content, $minimizable, $category_search_settings, $category_tooltip_settings, "product-category");

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($category_search_settings, $sub_option, 'product-category');
        $formOutPut .= $render_layout_start($category_layout_settings, $sub_option);

        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="product-category[]" class="' . esc_attr($sub_option) . ' filter-select" ' . ($singlevaluecataSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }
    }

    if ($hierarchical === 'enable' || $hierarchical === 'enable_hide_child') {
        $parent_categories = [];
        $child_category = [];
        $parent_categories = [];
        $child_category = [];
        if (isset($updated_filters["categories"]) && is_array($updated_filters["categories"])) {
            // Build a lookup of term IDs present in the provided categories
            $present_ids = [];
            foreach ($updated_filters["categories"] as $cat) {
                if (is_object($cat)) {
                    $id = isset($cat->term_id) ? (int) $cat->term_id : null;
                } elseif (is_array($cat)) {
                    $id = isset($cat['term_id']) ? (int) $cat['term_id'] : null;
                } else {
                    $id = null;
                }
                if ($id !== null) {
                    $present_ids[$id] = true;
                }
            }

            // Separate into parents and children.
            // If a category has a parent id but that parent is NOT present in the list,
            // treat the category as a parent (top-level) as requested.
            foreach ($updated_filters["categories"] as $category) {
                if (!is_object($category) && !is_array($category)) {
                    continue;
                }

                $parent_id = is_object($category) ? (int) ($category->parent ?? 0) : (int) ($category['parent'] ?? 0);

                // If parent is 0 OR parent id is not present among provided categories,
                // treat this item as a parent.
                if ($parent_id === 0 || !isset($present_ids[$parent_id])) {
                    $parent_categories[] = $category;
                } else {
                    $child_category[] = $category;
                }
            }
        }

        if ($parent_categories) {
            $formOutPut .= dapfforwc_render_category_hierarchy($parent_categories, $selected_categories, $sub_option, $dapfforwc_styleoptions, $singlevaluecataSelect, $show_count, $use_anchor, $use_filters_word, $hierarchical, $child_category);
        } else if (empty($parent_categories) && !empty($child_category)) {
            // If no parent categories, render child categories as top-level
            $formOutPut .= dapfforwc_render_category_hierarchy($child_category, $selected_categories, $sub_option, $dapfforwc_styleoptions, $singlevaluecataSelect, $show_count, $use_anchor, $use_filters_word, $hierarchical, []);
        }
    } elseif ($hierarchical === 'enable_separate') {

        // Render parent categories in a unified section
        $parent_categories = [];
        $child_category = [];
        $parent_categories = [];
        $child_category = [];
        if (isset($updated_filters["categories"]) && is_array($updated_filters["categories"])) {
            // Build a lookup of term IDs present in the provided categories
            $present_ids = [];
            foreach ($updated_filters["categories"] as $cat) {
                if (is_object($cat)) {
                    $id = isset($cat->term_id) ? (int) $cat->term_id : null;
                } elseif (is_array($cat)) {
                    $id = isset($cat['term_id']) ? (int) $cat['term_id'] : null;
                } else {
                    $id = null;
                }
                if ($id !== null) {
                    $present_ids[$id] = true;
                }
            }

            // Separate into parents and children.
            // If a category has a parent id but that parent is NOT present in the list,
            // treat the category as a parent (top-level) as requested.
            foreach ($updated_filters["categories"] as $category) {
                if (!is_object($category) && !is_array($category)) {
                    continue;
                }

                $parent_id = is_object($category) ? (int) ($category->parent ?? 0) : (int) ($category['parent'] ?? 0);

                // If parent is 0 OR parent id is not present among provided categories,
                // treat this item as a parent.
                if ($parent_id === 0 || !isset($present_ids[$parent_id])) {
                    $parent_categories[] = $category;
                } else {
                    $child_category[] = $category;
                }
            }
        }

        $formOutPut .= '<div id="product-category" class="plugincy-filter-group category">';
        $category_title_content = (isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["product-category"]) && $dapfforwc_styleoptions["widget_title"]["product-category"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["product-category"]) : esc_html__('Category', 'dynamic-ajax-product-filters-for-woocommerce');
        if ($show_reset_btn === "yes" && $singlevaluecataSelect === "yes" && $show_apply_reset_on === "separate") {
            $category_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($category_title_content, $minimizable, $category_search_settings, $category_tooltip_settings, "product-category");
        if ($sub_option === 'color_circle' || $sub_option === 'color_value' || $sub_option === 'color_swatch_label') {
            $sub_option = 'plugincy_color';
        } elseif ($sub_option === 'button_check') {
            $sub_option = '';
        }

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($category_search_settings, $sub_option, 'product-category');
        $formOutPut .= $render_layout_start($category_layout_settings, $sub_option);

        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="product-category[]" class="select ' . esc_attr($sub_option) . ' filter-select" ' . ($singlevaluecataSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }

        foreach (isset($parent_categories) ? $parent_categories : [] as $parent_category) {
            $value = is_object($parent_category) ? esc_attr($parent_category->slug) : esc_attr($parent_category['slug']);
            $title = is_object($parent_category) ? esc_html($parent_category->name) : esc_html($parent_category['name']);
            $style_value = dapfforwc_get_term_style_storage_key($parent_category);
            $count = $show_count === 'yes' ? (is_object($parent_category) ? esc_attr($parent_category->count) : esc_attr(isset($parent_category['count']) ? $parent_category['count'] : 0)) : 0;
            $checked = in_array($value, $selected_categories) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';
            $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
            $formOutPut .= $use_anchor === 'on'   && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic")
                ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "product-category", "product-category", $singlevaluecataSelect, $count, 0, null, [], $disable_unselected, $style_value) . '</a>'
                : dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "product-category", "product-category", $singlevaluecataSelect, $count, 0, null, [], $disable_unselected, $style_value);
        }

        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select>';
        }
        $formOutPut .= $render_layout_end($category_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($category_search_settings, $sub_option, 'product-category');
        $formOutPut .= '</div></div>';

        // Render child categories grouped by parent
        foreach ($parent_categories as $parent_category) {
            $child_categories = dapfforwc_get_child_categories($child_category, $parent_category->term_id) ?: [];

            if (!empty($child_categories)) {
                $formOutPut .= '<div id="category-with-child" class="plugincy-filter-group category with-child">';
                $child_title_content = esc_html($parent_category->name);
                if ($show_reset_btn === "yes" && $singlevaluecataSelect === "yes" && $show_apply_reset_on === "separate") {
                    $child_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
                }
                $formOutPut .= $render_title_bar($child_title_content, $minimizable, $category_search_settings, $category_tooltip_settings, "product-category");
                if ($sub_option === 'color_circle' || $sub_option === 'color_value' || $sub_option === 'color_swatch_label') {
                    $sub_option = 'plugincy_color';
                } elseif ($sub_option === 'button_check') {
                    $sub_option = '';
                }

                $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
                $formOutPut .= $render_search_container($category_search_settings, $sub_option, 'product-category');
                $formOutPut .= $render_layout_start($category_layout_settings, $sub_option);

                if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
                    $formOutPut .= '<select name="product-category[]" class="select ' . esc_attr($sub_option) . ' filter-select" ' . ($singlevaluecataSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
                    $formOutPut .= '<option class="filter-checkbox" > Any </option>';
                }

                $formOutPut .= dapfforwc_render_category_hierarchy($child_categories, $selected_categories, $sub_option, $dapfforwc_styleoptions, $singlevaluecataSelect, $show_count, $use_anchor, $use_filters_word, $hierarchical, $child_categories);

                if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
                    $formOutPut .= '</select>';
                }
                $formOutPut .= $render_layout_end($category_layout_settings, $sub_option);
                $formOutPut .= $render_search_after_terms($category_search_settings, $sub_option, 'product-category');
                $formOutPut .= '</div></div>';
            }
        }
    } else {
        // Render categories non-hierarchically
        foreach (isset($updated_filters["categories"]) ? $updated_filters["categories"] : [] as $category) {
            $value = is_object($category) ? esc_attr($category->slug) : esc_attr($category['slug']);
            $title = is_object($category) ? esc_html($category->name) : esc_html($category['name']);
            $style_value = dapfforwc_get_term_style_storage_key($category);
            $count = $show_count === 'yes' ? $dapfforwc_product_count['categories'][$value] : 0;

            $checked = in_array($value, $selected_categories) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';
            $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
            $formOutPut .= $use_anchor === 'on'   && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic")
                ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "product-category", "product-category", $singlevaluecataSelect, $count, 0, null, [], $disable_unselected, $style_value) . '</a>'
                : dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "product-category", "product-category", $singlevaluecataSelect, $count, 0, null, [], $disable_unselected, $style_value);
        }
    }

    if ($hierarchical !== 'enable_separate' && !empty($updated_filters["categories"])) {
        $formOutPut .= $render_layout_end($category_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($category_search_settings, $sub_option, 'product-category');
        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select></div></div>';
        } else {
            $formOutPut .= '</div></div>';
        }
    }
    }
    ?>
<?php
    // category ends


    // display attributes
    $attributes = isset($updated_filters['attributes']) && is_array($updated_filters['attributes']) ? $updated_filters["attributes"] : [];
    $exclude_attributes = isset($dapfforwc_advance_settings['exclude_attributes']) ? explode(',', $dapfforwc_advance_settings['exclude_attributes']) : [];

    if ($filter_enabled('show_attributes') && $attributes) {
        foreach ($attributes as $attribute_name => $attribute_terms) {
            if (in_array($attribute_name, $exclude_attributes)) {
                continue;
            }
            $terms = $attribute_terms; // Directly use the terms from the array
            $include_exclude_terms = isset($dapfforwc_styleoptions['terms'][$attribute_name]) ? $dapfforwc_styleoptions['terms'][$attribute_name] : [];
            $include_or_exclude_terms_option = $dapfforwc_styleoptions['include_exclude'][$attribute_name] ?? 'none';
            // Filter terms based on include/exclude settings
            if (!empty($include_exclude_terms) && $include_or_exclude_terms_option === 'include') {
                $terms = array_filter($terms, function ($term) use ($include_exclude_terms, $attribute_name) {
                    return dapfforwc_term_matches_stored_filter_value($term, $include_exclude_terms, $attribute_name);
                });
            } elseif (!empty($include_exclude_terms) && $include_or_exclude_terms_option === 'exclude') {
                $terms = array_filter($terms, function ($term) use ($include_exclude_terms, $attribute_name) {
                    return !dapfforwc_term_matches_stored_filter_value($term, $include_exclude_terms, $attribute_name);
                });
            }
            $sub_optionattr = isset($dapfforwc_styleoptions[$attribute_name]["sub_option"]) ? $dapfforwc_styleoptions[$attribute_name]["sub_option"] : "";
            $minimizable = isset($dapfforwc_styleoptions[$attribute_name]["minimize"]["type"]) ? $dapfforwc_styleoptions[$attribute_name]["minimize"]["type"] : "arrow";
            $show_count = isset($dapfforwc_styleoptions[$attribute_name]["show_product_count"]) ? $dapfforwc_styleoptions[$attribute_name]["show_product_count"] : "";
            $singlevalueattrSelect = isset($dapfforwc_styleoptions[$attribute_name]["single_selection"]) ? $dapfforwc_styleoptions[$attribute_name]["single_selection"] : "";
            $attr_search_settings = $get_terms_search_settings($attribute_name);
            $attr_tooltip_settings = $get_tooltip_settings($attribute_name);
            $attr_layout_settings = $get_layout_settings($attribute_name);

            $attr_order_by = $dapfforwc_styleoptions[$attribute_name]["order_by"] ?? "default";
            $attr_order = $dapfforwc_styleoptions[$attribute_name]["order_direction"] ?? "asc";

            $terms = dapfforwc_sort_attribute_terms($terms, $attribute_name, $attr_order_by, $attr_order);

            if ($terms) {
                $formOutPut .= '<div id="' . esc_attr($attribute_name) . '" class="plugincy-filter-group ' . esc_attr($attribute_name) . ' ' . (isset($dapfforwc_styleoptions['css_class'][$attribute_name]) ? $dapfforwc_styleoptions['css_class'][$attribute_name] : '') . '">';
                $attribute_default_title = '';
                $first_term = is_array($terms) ? reset($terms) : null;
                if (is_object($first_term) && !empty($first_term->attribute_label)) {
                    $attribute_default_title = (string) $first_term->attribute_label;
                } elseif (is_array($first_term) && !empty($first_term['attribute_label'])) {
                    $attribute_default_title = (string) $first_term['attribute_label'];
                }

                $attribute_title_content = (isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"][$attribute_name]) && $dapfforwc_styleoptions["widget_title"][$attribute_name] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"][$attribute_name]) : esc_html($attribute_default_title);
                if ($show_reset_btn === "yes" && $singlevalueattrSelect === "yes" && $show_apply_reset_on === "separate") {
                    $attribute_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
                }
                $formOutPut .= $render_title_bar($attribute_title_content, $minimizable, $attr_search_settings, $attr_tooltip_settings, $attribute_name);

                $formOutPut .= '<div class="items ' . esc_attr($sub_optionattr) . '">';
                $formOutPut .= $render_search_container($attr_search_settings, $sub_optionattr, $attribute_name);
                $formOutPut .= $render_layout_start($attr_layout_settings, $sub_optionattr);

                if ($sub_optionattr === "select" || $sub_optionattr === "pluginy_select2" || $sub_optionattr === "select2_classic") {
                    $formOutPut .= '<select name="attribute[' . esc_attr($attribute_name) . '][]" class="' . esc_attr($sub_optionattr) . ' filter-select" ' . ($singlevalueattrSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
                    $formOutPut .= '<option class="filter-checkbox" > Any </option>';
                }

                $selected_terms = isset($default_filter["attribute[$attribute_name][]"]) ? $default_filter["attribute[$attribute_name][]"] : [];
                foreach ($terms as $term) {

                    $name = is_object($term) ? esc_html($term->name) : esc_html($term['name']);
                    $slug = is_object($term) ? esc_attr($term->slug) : esc_attr($term['slug']);
                    $style_value = dapfforwc_get_term_style_storage_key($term);
                    $checked = in_array($slug, $selected_terms) ? ($sub_optionattr === 'select' || str_contains($sub_optionattr, 'pluginy_select2') ? ' selected' : ' checked') : '';
                    $count = $show_count === "yes" ? (is_object($term) ? esc_attr($term->count ?? 0) : esc_attr(isset($term['count']) ? $term['count'] : 0)) : 0; // Use term count directly
                    $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? esc_attr($slug) : "filters/" . esc_attr($slug)) : '?filters=' . esc_attr($slug);
                    $formOutPut .= $use_anchor === "on" && $sub_optionattr !== "select" && $sub_optionattr !== "pluginy_select2" && $sub_optionattr !== "select2_classic" ?
                        '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_optionattr, esc_html($name), esc_attr($slug), $checked, $dapfforwc_styleoptions, "attribute[$attribute_name]", $attribute_name, $singlevalueattrSelect, $count, 0, null, [],  $disable_unselected, $style_value) . '</a>' :
                        dapfforwc_render_filter_option($sub_optionattr, esc_html($name), esc_attr($slug), $checked, $dapfforwc_styleoptions, "attribute[$attribute_name]", $attribute_name, $singlevalueattrSelect, $count, 0, null, [],   $disable_unselected, $style_value);
                }

                $formOutPut .= $render_layout_end($attr_layout_settings, $sub_optionattr);
                $formOutPut .= $render_search_after_terms($attr_search_settings, $sub_optionattr, $attribute_name);

                if ($sub_optionattr === "select" || $sub_optionattr === "pluginy_select2" || $sub_optionattr === "select2_classic") {
                    $formOutPut .= '</select>';
                }
                $formOutPut .= '</div></div>';
            }
        }
    }

    // display tags
    $tags = isset($updated_filters['tags']) && is_array($updated_filters['tags']) ? $updated_filters["tags"] : [];
    $include_exclude_tags = isset($dapfforwc_styleoptions['terms']['tag']) ? $dapfforwc_styleoptions['terms']['tag'] : [];
    $include_or_exclude_tags_option = $dapfforwc_styleoptions['include_exclude']['tag'] ?? 'none';

    // Filter tags based on include/exclude settings
    if (!empty($include_exclude_tags) && $include_or_exclude_tags_option === 'include') {
        $tags = array_filter($tags, function ($tag) use ($include_exclude_tags) {
            return dapfforwc_term_matches_stored_filter_value($tag, $include_exclude_tags, 'tag');
        });
    } elseif (!empty($include_exclude_tags) && $include_or_exclude_tags_option === 'exclude') {
        $tags = array_filter($tags, function ($tag) use ($include_exclude_tags) {
            return !dapfforwc_term_matches_stored_filter_value($tag, $include_exclude_tags, 'tag');
        });
    }
    if ($filter_enabled('show_tags') && !empty($tags)) {
        $selected_tags = !empty($default_filter) && isset($default_filter["tag[]"]) ? $default_filter["tag[]"] : [];
        $sub_option = isset($dapfforwc_styleoptions["tag"]["sub_option"]) ? $dapfforwc_styleoptions["tag"]["sub_option"] : ""; // Fetch the sub_option value
        $minimizable = isset($dapfforwc_styleoptions["tag"]["minimize"]["type"]) ? $dapfforwc_styleoptions["tag"]["minimize"]["type"] : "arrow";
        $show_count = isset($dapfforwc_styleoptions["tag"]["show_product_count"]) ? $dapfforwc_styleoptions["tag"]["show_product_count"] : "";
        $singlevalueSelect = isset($dapfforwc_styleoptions["tag"]["single_selection"]) ? $dapfforwc_styleoptions["tag"]["single_selection"] : "";
        $tag_search_settings = $get_terms_search_settings('tag');
        $tag_tooltip_settings = $get_tooltip_settings('tag');
        $tag_layout_settings = $get_layout_settings('tag');
        $formOutPut .= '<div id="tag" class="plugincy-filter-group tag ' . (isset($dapfforwc_styleoptions['css_class']['tag']) ? $dapfforwc_styleoptions['css_class']['tag'] : '') . '">';
        $tag_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["tag"]) && $dapfforwc_styleoptions["widget_title"]["tag"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["tag"]) : esc_html__('Tags', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $singlevalueSelect === "yes" && $show_apply_reset_on === "separate") {
            $tag_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($tag_title_content, $minimizable, $tag_search_settings, $tag_tooltip_settings, 'tag');

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($tag_search_settings, $sub_option, 'tag');
        $formOutPut .= $render_layout_start($tag_layout_settings, $sub_option);

        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="tags[]" class="' . esc_attr($sub_option) . ' filter-select" ' . ($singlevalueSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }

        $tags_order_by = $dapfforwc_styleoptions["tag"]["order_by"] ?? "default";
        $tags_order = $dapfforwc_styleoptions["tag"]["order_direction"] ?? "asc";

        // Sort tags if order_by is not default
        if ($tags_order_by !== "default") {
            $tags = dapfforwc_sort_terms($tags, $tags_order_by, $tags_order);
        }

        if ($tags) {
            foreach ($tags as $tag) {
                $value = is_object($tag) ? esc_attr($tag->slug) : esc_attr($tag['slug']);
                $title = is_object($tag) ? esc_html($tag->name) : esc_html($tag['name']);
                $style_value = dapfforwc_get_term_style_storage_key($tag);
                $checked = in_array($value, $selected_tags) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';
                $count = $show_count === "yes" ? $dapfforwc_product_count["tags"][$value] : 0;
                $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
                $formOutPut .= $use_anchor === "on"  && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "tags", $attribute = "tag", $singlevalueSelect, $count, 0, null, [], $disable_unselected, $style_value) . '</a>' :  dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "tags", $attribute = "tag", $singlevalueSelect, $count, 0, null, [], $disable_unselected, $style_value);
            }
        }
        $formOutPut .= $render_layout_end($tag_layout_settings, $sub_option);
        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select>';
        }
        $formOutPut .= $render_search_after_terms($tag_search_settings, $sub_option, 'tag');
        $formOutPut .= '</div></div>';
    }
    // tags ends
    // display brands
    $brands = isset($updated_filters['brands']) && is_array($updated_filters['brands']) ? $updated_filters["brands"] : [];
    $include_exclude_brands = isset($dapfforwc_styleoptions['terms']['brands']) ? $dapfforwc_styleoptions['terms']['brands'] : [];
    $include_or_exclude_brands_option = $dapfforwc_styleoptions['include_exclude']['brands'] ?? 'none';
    // Filter brands based on include/exclude settings
    if (!empty($include_exclude_brands) && $include_or_exclude_brands_option === 'include') {
        $brands = array_filter($brands, function ($brand) use ($include_exclude_brands) {
            return dapfforwc_term_matches_stored_filter_value($brand, $include_exclude_brands, 'brands');
        });
    } elseif (!empty($include_exclude_brands) && $include_or_exclude_brands_option === 'exclude') {
        $brands = array_filter($brands, function ($brand) use ($include_exclude_brands) {
            return !dapfforwc_term_matches_stored_filter_value($brand, $include_exclude_brands, 'brands');
        });
    }
    $brands_order_by = $dapfforwc_styleoptions["brands"]["order_by"] ?? "default";
    $brands_order = $dapfforwc_styleoptions["brands"]["order_direction"] ?? "asc";
    // Sort brands if order_by is not default
    if ($brands_order_by !== "default") {
        $brands = dapfforwc_sort_terms($brands, $brands_order_by, $brands_order);
    }
    $brands_search_settings = $get_terms_search_settings('brands');
    $brands_tooltip_settings = $get_tooltip_settings('brands');
    if ($filter_enabled('show_brand') && !empty($brands)) {
        $selected_brands = !empty($default_filter) && isset($default_filter["rplurand[]"]) ? $default_filter["rplurand[]"] : (isset($default_filter["brand[]"]) ? $default_filter["brand[]"] : []);
        $sub_option = isset($dapfforwc_styleoptions["brands"]["sub_option"]) ? $dapfforwc_styleoptions["brands"]["sub_option"] : ""; // Fetch the sub_option value
        $minimizable = isset($dapfforwc_styleoptions["brands"]["minimize"]["type"]) ? $dapfforwc_styleoptions["brands"]["minimize"]["type"] : "arrow";
        $show_count = isset($dapfforwc_styleoptions["brands"]["show_product_count"]) ? $dapfforwc_styleoptions["brands"]["show_product_count"] : "";
        $singlevalueSelect = isset($dapfforwc_styleoptions["brands"]["single_selection"]) ? $dapfforwc_styleoptions["brands"]["single_selection"] : "";
        $brands_layout_settings = $get_layout_settings('brands');
        $formOutPut .= '<div id="brands" class="plugincy-filter-group brands ' . (isset($dapfforwc_styleoptions['css_class']['brands']) ? $dapfforwc_styleoptions['css_class']['brands'] : '') . '">';
        $brands_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["brands"]) && $dapfforwc_styleoptions["widget_title"]["brands"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["brands"]) : esc_html__('Brands', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $singlevalueSelect === "yes" && $show_apply_reset_on === "separate") {
            $brands_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($brands_title_content, $minimizable, $brands_search_settings, $brands_tooltip_settings, 'brands');

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($brands_search_settings, $sub_option, 'brands');
        $formOutPut .= $render_layout_start($brands_layout_settings, $sub_option);

        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="rplurand[]" class="' . esc_attr($sub_option) . ' filter-select" ' . ($singlevalueSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }
        if ($brands) {
            foreach ($brands as $brand) {
                $value = is_object($brand) ? esc_attr($brand->slug) : esc_attr($brand['slug']);
                $title = is_object($brand) ? esc_html($brand->name) : esc_html($brand['name']);
                $style_value = dapfforwc_get_term_style_storage_key($brand);
                $checked = in_array($value, $selected_brands) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';

                $count = $show_count === "yes" ? $dapfforwc_product_count["brands"][$value] : 0;
                $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
                $formOutPut .= $use_anchor === "on"  && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rplurand", $attribute = "brands", $singlevalueSelect, $count, 0, null, [], $disable_unselected, $style_value) . '</a>' :  dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rplurand", $attribute = "brands", $singlevalueSelect, $count, 0, null, [], $disable_unselected, $style_value);
            }
        }
        $formOutPut .= $render_layout_end($brands_layout_settings, $sub_option);
        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select>';
        }
        $formOutPut .= $render_search_after_terms($brands_search_settings, $sub_option, 'brands');
        $formOutPut .= '</div></div>';
    }
    // brands ends
    // display Authors
    $authors = isset($updated_filters['authors']) && is_array($updated_filters['authors']) ? $updated_filters["authors"] : [];
    $include_exclude_authors = isset($dapfforwc_styleoptions['terms']['authors']) ? $dapfforwc_styleoptions['terms']['authors'] : [];
    $include_or_exclude_authors_option = $dapfforwc_styleoptions['include_exclude']['authors'] ?? 'none';
    // Filter authors based on include/exclude settings
    if (!empty($include_exclude_authors) && $include_or_exclude_authors_option === 'include') {
        $authors = array_filter($authors, function ($author) use ($include_exclude_authors) {
            return dapfforwc_term_matches_stored_filter_value($author, $include_exclude_authors, 'authors');
        });
    } elseif (!empty($include_exclude_authors) && $include_or_exclude_authors_option === 'exclude') {
        $authors = array_filter($authors, function ($author) use ($include_exclude_authors) {
            return !dapfforwc_term_matches_stored_filter_value($author, $include_exclude_authors, 'authors');
        });
    }
    $authors_search_settings = $get_terms_search_settings('authors');
    $authors_tooltip_settings = $get_tooltip_settings('authors');
    if ($filter_enabled('show_author') && !empty($authors)) {
        $selected_authors = !empty($default_filter) && isset($default_filter["rpluthor[]"]) ? $default_filter["rpluthor[]"] : (isset($default_filter["author[]"]) ? $default_filter["author[]"] : []);
        $sub_option = isset($dapfforwc_styleoptions["authors"]["sub_option"]) ? $dapfforwc_styleoptions["authors"]["sub_option"] : ""; // Fetch the sub_option value
        $minimizable = isset($dapfforwc_styleoptions["authors"]["minimize"]["type"]) ? $dapfforwc_styleoptions["authors"]["minimize"]["type"] : "arrow";
        $show_count = isset($dapfforwc_styleoptions["authors"]["show_product_count"]) ? $dapfforwc_styleoptions["authors"]["show_product_count"] : "";
        $singlevalueSelect = isset($dapfforwc_styleoptions["authors"]["single_selection"]) ? $dapfforwc_styleoptions["authors"]["single_selection"] : "";
        $authors_layout_settings = $get_layout_settings('authors');
        $formOutPut .= '<div id="authors" class="plugincy-filter-group authors ' . (isset($dapfforwc_styleoptions['css_class']['authors']) ? $dapfforwc_styleoptions['css_class']['authors'] : '') . '">';
        $authors_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["authors"]) && $dapfforwc_styleoptions["widget_title"]["authors"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["authors"]) : esc_html__('Authors', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $singlevalueSelect === "yes" && $show_apply_reset_on === "separate") {
            $authors_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($authors_title_content, $minimizable, $authors_search_settings, $authors_tooltip_settings, 'authors');
        if ($sub_option === 'color_circle' || $sub_option === 'color_value' || $sub_option === 'color_swatch_label') {
            $sub_option = 'plugincy_color';
        } elseif ($sub_option === 'button_check') {
            $sub_option = '';
        }

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($authors_search_settings, $sub_option, 'authors');
        $formOutPut .= $render_layout_start($authors_layout_settings, $sub_option);


        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="rpluthor[]" class="' . esc_attr($sub_option) . ' filter-select" ' . ($singlevalueSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }
        if ($authors) {
            foreach ($authors as $author) {
                $value = is_object($author) ? esc_attr($author->slug) : esc_attr($author['slug']);
                $title = is_object($author) ? esc_html($author->name) : esc_html($author['name']);
                $checked = in_array($value, $selected_authors) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';
                $count = $show_count === "yes" ? $dapfforwc_product_count["authors"][$value] : 0;
                $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
                $formOutPut .= $use_anchor === "on"  && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rpluthor", $attribute = "authors", $singlevalueSelect, $count, 0, null, [], $disable_unselected) . '</a>' :  dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rpluthor", $attribute = "authors", $singlevalueSelect, $count, 0, null, [], $disable_unselected);
            }
        }
        $formOutPut .= $render_layout_end($authors_layout_settings, $sub_option);
        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select>';
        }
        $formOutPut .= $render_search_after_terms($authors_search_settings, $sub_option, 'authors');
        $formOutPut .= '</div></div>';
    }
    // authors ends
    // display Stock Status
    $status = isset($updated_filters['stock_status']) && is_array($updated_filters['stock_status']) ? $updated_filters["stock_status"] : [];
    $include_exclude_status = isset($dapfforwc_styleoptions['terms']['status']) ? $dapfforwc_styleoptions['terms']['status'] : [];
    $include_or_exclude_status_option = $dapfforwc_styleoptions['include_exclude']['status'] ?? 'none';
    // Filter stock status based on include/exclude settings
    if (!empty($include_exclude_status) && $include_or_exclude_status_option === 'include') {
        $status = array_filter($status, function ($stat) use ($include_exclude_status) {
            return dapfforwc_term_matches_stored_filter_value($stat, $include_exclude_status, 'status');
        });
    } elseif (!empty($include_exclude_status) && $include_or_exclude_status_option === 'exclude') {
        $status = array_filter($status, function ($stat) use ($include_exclude_status) {
            return !dapfforwc_term_matches_stored_filter_value($stat, $include_exclude_status, 'status');
        });
    }
    $status_search_settings = $get_terms_search_settings('status');
    $status_tooltip_settings = $get_tooltip_settings('status');
    if ($filter_enabled('show_status') && !empty($status)) {
        $selected_status = !empty($default_filter) && isset($default_filter["rplutock_status[]"]) ? $default_filter["rplutock_status[]"] : (isset($default_filter["stock_status[]"]) ? $default_filter["stock_status[]"] : []);
        $sub_option = isset($dapfforwc_styleoptions["status"]["sub_option"]) ? $dapfforwc_styleoptions["status"]["sub_option"] : ""; // Fetch the sub_option value
        $minimizable = isset($dapfforwc_styleoptions["status"]["minimize"]["type"]) ? $dapfforwc_styleoptions["status"]["minimize"]["type"] : "arrow";
        $show_count = isset($dapfforwc_styleoptions["status"]["show_product_count"]) ? $dapfforwc_styleoptions["status"]["show_product_count"] : "";
        $singlevalueSelect = isset($dapfforwc_styleoptions["status"]["single_selection"]) ? $dapfforwc_styleoptions["status"]["single_selection"] : "";
        $status_layout_settings = $get_layout_settings('status');
        $formOutPut .= '<div id="status" class="plugincy-filter-group status ' . (isset($dapfforwc_styleoptions['css_class']['status']) ? $dapfforwc_styleoptions['css_class']['status'] : '') . '">';
        $status_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["status"]) && $dapfforwc_styleoptions["widget_title"]["status"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["status"]) : esc_html__('Stock Status', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $singlevalueSelect === "yes" && $show_apply_reset_on === "separate") {
            $status_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($status_title_content, $minimizable, $status_search_settings, $status_tooltip_settings, 'status');
        if ($sub_option === 'color_circle' || $sub_option === 'color_value' || $sub_option === 'color_swatch_label') {
            $sub_option = 'plugincy_color';
        } elseif ($sub_option === 'button_check') {
            $sub_option = '';
        }

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($status_search_settings, $sub_option, 'status');
        $formOutPut .= $render_layout_start($status_layout_settings, $sub_option);


        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="rpluthor[]" class="' . esc_attr($sub_option) . ' filter-select" ' . ($singlevalueSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }

        if ($status) {
            foreach ($status as $stat) {
                $value = is_object($stat) ? esc_attr($stat->slug) : esc_attr($stat['slug']);
                $title = is_object($stat) ? esc_html($stat->name) : esc_html($stat['name']);
                if ($value === 'instock') {
                    $title = isset($dapfforwc_styleoptions["stock_status_text"]["instock"]) && !empty($dapfforwc_styleoptions["stock_status_text"]["instock"]) ? esc_html($dapfforwc_styleoptions["stock_status_text"]["instock"]) : esc_html__('In Stock', 'dynamic-ajax-product-filters-for-woocommerce');
                } elseif ($value === 'outofstock') {
                    $title = isset($dapfforwc_styleoptions["stock_status_text"]["outofstock"]) && !empty($dapfforwc_styleoptions["stock_status_text"]["outofstock"]) ? esc_html($dapfforwc_styleoptions["stock_status_text"]["outofstock"]) : esc_html__('Out of Stock', 'dynamic-ajax-product-filters-for-woocommerce');
                } elseif ($value === 'onbackorder') {
                    $title = isset($dapfforwc_styleoptions["stock_status_text"]["onbackorder"]) && !empty($dapfforwc_styleoptions["stock_status_text"]["onbackorder"]) ? esc_html($dapfforwc_styleoptions["stock_status_text"]["onbackorder"]) : esc_html__('On Backorder', 'dynamic-ajax-product-filters-for-woocommerce');
                }
                $checked = in_array($value, $selected_status) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';
                $count = $show_count === "yes" ? $dapfforwc_product_count["status"][$value] : 0;
                $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
                $formOutPut .= $use_anchor === "on"  && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rplutock_status", $attribute = "status", $singlevalueSelect, $count, 0, null, [], $disable_unselected) . '</a>' :  dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rplutock_status", $attribute = "status", $singlevalueSelect, $count, 0, null, [], $disable_unselected);
            }
        }
        $formOutPut .= $render_layout_end($status_layout_settings, $sub_option);
        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select>';
        }
        $formOutPut .= $render_search_after_terms($status_search_settings, $sub_option, 'status');
        $formOutPut .= '</div></div>';
    }
    // Stock Status ends
    // display Sale Status
    $sale_status = isset($updated_filters['sale_status']) && is_array($updated_filters['sale_status']) ? $updated_filters["sale_status"] : [];
    $include_exclude_sale_status = isset($dapfforwc_styleoptions['terms']['sale_status']) ? $dapfforwc_styleoptions['terms']['sale_status'] : [];
    $include_or_exclude_sale_status_option = $dapfforwc_styleoptions['include_exclude']['sale_status'] ?? 'none';
    // Filter sale status based on include/exclude settings
    if (!empty($include_exclude_sale_status) && $include_or_exclude_sale_status_option === 'include') {
        $sale_status = array_filter($sale_status, function ($stat) use ($include_exclude_sale_status) {
            return dapfforwc_term_matches_stored_filter_value($stat, $include_exclude_sale_status, 'sale_status');
        });
    } elseif (!empty($include_exclude_sale_status) && $include_or_exclude_sale_status_option === 'exclude') {
        $sale_status = array_filter($sale_status, function ($stat) use ($include_exclude_sale_status) {
            return !dapfforwc_term_matches_stored_filter_value($stat, $include_exclude_sale_status, 'sale_status');
        });
    }
    $sale_status_search_settings = $get_terms_search_settings('sale_status');
    $sale_status_tooltip_settings = $get_tooltip_settings('sale_status');
    if ($filter_enabled('show_onsale') && !empty($sale_status)) {
        $selected_sale_status = !empty($default_filter) && isset($default_filter["rpn_sale[]"]) ? $default_filter["rpn_sale[]"] : (isset($default_filter["sale_status[]"]) ? $default_filter["sale_status[]"] : []);
        $sub_option = isset($dapfforwc_styleoptions["sale_status"]["sub_option"]) ? $dapfforwc_styleoptions["sale_status"]["sub_option"] : ""; // Fetch the sub_option value
        $minimizable = isset($dapfforwc_styleoptions["sale_status"]["minimize"]["type"]) ? $dapfforwc_styleoptions["sale_status"]["minimize"]["type"] : "arrow";
        $show_count = isset($dapfforwc_styleoptions["sale_status"]["show_product_count"]) ? $dapfforwc_styleoptions["sale_status"]["show_product_count"] : "";
        $singlevalueSelect = isset($dapfforwc_styleoptions["sale_status"]["single_selection"]) ? $dapfforwc_styleoptions["sale_status"]["single_selection"] : "";
        $sale_layout_settings = $get_layout_settings('sale_status');
        $formOutPut .= '<div id="sale_status" class="plugincy-filter-group sale_status ' . (isset($dapfforwc_styleoptions['css_class']['sale_status']) ? $dapfforwc_styleoptions['css_class']['sale_status'] : '') . '">';
        $sale_status_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["sale_status"]) && $dapfforwc_styleoptions["widget_title"]["sale_status"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["sale_status"]) : esc_html__('Sale Status', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $singlevalueSelect === "yes" && $show_apply_reset_on === "separate") {
            $sale_status_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($sale_status_title_content, $minimizable, $sale_status_search_settings, $sale_status_tooltip_settings, 'sale_status');
        if ($sub_option === 'color_circle' || $sub_option === 'color_value' || $sub_option === 'color_swatch_label') {
            $sub_option = 'plugincy_color';
        } elseif ($sub_option === 'button_check') {
            $sub_option = '';
        }

        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($sale_status_search_settings, $sub_option, 'sale_status');
        $formOutPut .= $render_layout_start($sale_layout_settings, $sub_option);

        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '<select name="rpluthor[]" class="' . esc_attr($sub_option) . ' filter-select" ' . ($singlevalueSelect !== "yes" ? 'multiple="multiple"' : '') . '>';
            $formOutPut .= '<option class="filter-checkbox" > Any </option>';
        }
        if ($sale_status) {
            foreach ($sale_status as $stat) {
                $value = is_object($stat) ? esc_attr($stat->slug) : esc_attr($stat['slug']);
                $title = is_object($stat) ? esc_html($stat->name) : esc_html($stat['name']);
                if ($value === 'onsale') {
                    $title = isset($dapfforwc_styleoptions["sale_status_text"]["onsale"]) && !empty($dapfforwc_styleoptions["sale_status_text"]["onsale"]) ? esc_html($dapfforwc_styleoptions["sale_status_text"]["onsale"]) : esc_html__('On Sale', 'dynamic-ajax-product-filters-for-woocommerce');
                } elseif ($value === 'notonsale') {
                    $title = isset($dapfforwc_styleoptions["sale_status_text"]["notonsale"]) && !empty($dapfforwc_styleoptions["sale_status_text"]["notonsale"]) ? esc_html($dapfforwc_styleoptions["sale_status_text"]["notonsale"]) : esc_html__('Not on Sale', 'dynamic-ajax-product-filters-for-woocommerce');
                }
                $checked = in_array($value, $selected_sale_status) ? ($sub_option === 'select' || str_contains($sub_option, 'pluginy_select2') ? ' selected' : ' checked') : '';
                $count = $show_count === "yes" ? $dapfforwc_product_count["sale_status"][$value] : 0;
                $anchorlink = $use_filters_word === 'on' ? ($is_filters_in_url ? "$value" : "filters/$value") : '?filters=' . $value;
                $formOutPut .= $use_anchor === "on"  && ($sub_option !== "select" && $sub_option !== "pluginy_select2" && $sub_option !== "select2_classic") ? '<a href="' . esc_attr($anchorlink) . '">' . dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rpn_sale", $attribute = "sale_status", $singlevalueSelect, $count, 0, null, [], $disable_unselected) . '</a>' :  dapfforwc_render_filter_option($sub_option, $title, $value, $checked, $dapfforwc_styleoptions, "rpn_sale", $attribute = "sale_status", $singlevalueSelect, $count, 0, null, [], $disable_unselected);
            }
        }
        $formOutPut .= $render_layout_end($sale_layout_settings, $sub_option);
        if ($sub_option === "select" || $sub_option === "pluginy_select2" || $sub_option === "select2_classic") {
            $formOutPut .= '</select>';
        }
        $formOutPut .= $render_search_after_terms($sale_status_search_settings, $sub_option, 'sale_status');
        $formOutPut .= '</div></div>';
    }
    // Sale Status ends

    // Unified Dimension Filter
    $dimensions = [
        'length' => [
            'label' => !empty(trim((string) ($dapfforwc_styleoptions['dimensions_text']['length'] ?? ''))) ? $dapfforwc_styleoptions['dimensions_text']['length'] : 'Length (cm):',
            'unit' => 'cm'
        ],
        'width' => [
            'label' => !empty(trim((string) ($dapfforwc_styleoptions['dimensions_text']['width'] ?? ''))) ? $dapfforwc_styleoptions['dimensions_text']['width'] : 'Width (cm):',
            'unit' => 'cm'
        ],
        'height' => [
            'label' => !empty(trim((string) ($dapfforwc_styleoptions['dimensions_text']['height'] ?? ''))) ? $dapfforwc_styleoptions['dimensions_text']['height'] : 'Height (cm):',
            'unit' => 'cm'
        ],
        'weight' => [
            'label' => !empty(trim((string) ($dapfforwc_styleoptions['dimensions_text']['weight'] ?? ''))) ? $dapfforwc_styleoptions['dimensions_text']['weight'] : 'Weight (kg):',
            'unit' => 'kg'
        ]
    ];

    $dimension_min_placeholder = !empty(trim((string) ($dapfforwc_styleoptions['dimensions_placeholder']['min'] ?? ''))) ? $dapfforwc_styleoptions['dimensions_placeholder']['min'] : 'Min (%s)';
    $dimension_max_placeholder = !empty(trim((string) ($dapfforwc_styleoptions['dimensions_placeholder']['max'] ?? ''))) ? $dapfforwc_styleoptions['dimensions_placeholder']['max'] : 'Max (%s)';
    $get_dimension_bound_value = static function (string $dimension, string $edge) use ($dimension_bounds): string {
        $value = $dimension_bounds[$dimension][$edge] ?? '';
        return is_scalar($value) ? trim((string) $value) : '';
    };
    $format_dimension_placeholder = static function (string $format, string $bound_value, string $fallback): string {
        $format = trim($format);
        if ($format === '') {
            return $fallback;
        }

        if ($bound_value === '') {
            return trim(str_replace(['%s', '{value}'], '', $format)) ?: $fallback;
        }

        if (strpos($format, '%s') !== false) {
            return str_replace('%s', $bound_value, $format);
        }

        if (strpos($format, '{value}') !== false) {
            return str_replace('{value}', $bound_value, $format);
        }

        return $format;
    };


    $show_any_dimension = $filter_enabled('show_dimension');

    if ($show_any_dimension) {
        // Get common settings from the first dimension (or you can make this configurable)
        $sub_option = isset($dapfforwc_styleoptions["dimensions"]["sub_option"]) ? $dapfforwc_styleoptions["dimensions"]["sub_option"] : "";
        $minimizable = isset($dapfforwc_styleoptions["dimensions"]["minimize"]["type"]) ? $dapfforwc_styleoptions["dimensions"]["minimize"]["type"] : "arrow";
        $dimensions_search_settings = $get_terms_search_settings('dimensions');
        $dimensions_tooltip_settings = $get_tooltip_settings('dimensions');
        $dimensions_layout_settings = $get_layout_settings('dimensions');

        $formOutPut .= '<div id="dimensions" class="plugincy-filter-group dimensions">';
        $dimensions_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["dimensions"]) && $dapfforwc_styleoptions["widget_title"]["dimensions"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["dimensions"]) : esc_html__('Dimensions', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $show_apply_reset_on === "separate") {
            $dimensions_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($dimensions_title_content, $minimizable, $dimensions_search_settings, $dimensions_tooltip_settings, 'dimensions');
        $formOutPut .= '<div class="items ' . esc_attr($sub_option) . '">';
        $formOutPut .= $render_search_container($dimensions_search_settings, $sub_option, 'dimensions');
        $formOutPut .= $render_layout_start($dimensions_layout_settings, $sub_option);

        if ($sub_option === 'slider' || $sub_option === 'input-price-range') {
            // Unified Slider implementation
            $formOutPut .= '<div class="unified-dimension-slider-container">';

            foreach ($dimensions as $dimension => $config) {
                $min_value = $default_filter["min_{$dimension}"] ?? '';
                $max_value = $default_filter["max_{$dimension}"] ?? '';
                $default_min_value = $get_dimension_bound_value($dimension, 'min');
                $default_max_value = $get_dimension_bound_value($dimension, 'max');
                $resolved_min_value = $min_value !== '' ? $min_value : $default_min_value;
                $resolved_max_value = $max_value !== '' ? $max_value : $default_max_value;
                $shared_range_attrs = ($default_min_value !== '' ? ' min="' . esc_attr($default_min_value) . '"' : '') . ($default_max_value !== '' ? ' max="' . esc_attr($default_max_value) . '"' : '');

                $formOutPut .= '<div class="dimension-row">';
                $formOutPut .= '<div class="dimension-label">' . esc_html($config['label']) . '</div>';
                $formOutPut .= '<div class="dimension-values">';
                $formOutPut .= '<span class="min-value">' . esc_html($resolved_min_value) . '</span>';
                $formOutPut .= ' - ';
                $formOutPut .= '<span class="max-value">' . esc_html($resolved_max_value) . '</span>';
                $formOutPut .= ' ' . esc_html($config['unit']);
                $formOutPut .= '</div>';
                $formOutPut .= '<div class="dimension-slider" data-dimension="' . esc_attr($dimension) . '"></div>';
                $formOutPut .= '<input type="hidden" name="min_' . esc_attr($dimension) . '" value="' . esc_attr($resolved_min_value) . '"' . $shared_range_attrs . ' data-default="' . esc_attr($default_min_value) . '" />';
                $formOutPut .= '<input type="hidden" name="max_' . esc_attr($dimension) . '" value="' . esc_attr($resolved_max_value) . '"' . $shared_range_attrs . ' data-default="' . esc_attr($default_max_value) . '" />';
                $formOutPut .= '</div>';
            }

            $formOutPut .= '</div>';
        } else {
            // Unified Input fields implementation
            $formOutPut .= '<div class="unified-dimension-input-container">';

            foreach ($dimensions as $dimension => $config) {
                $min_value = $default_filter["min_{$dimension}"] ?? '';
                $max_value = $default_filter["max_{$dimension}"] ?? '';
                $default_min_value = $get_dimension_bound_value($dimension, 'min');
                $default_max_value = $get_dimension_bound_value($dimension, 'max');
                $min_attr = $default_min_value !== '' ? $default_min_value : '0';
                $max_attr = $default_max_value !== '' ? ' max="' . esc_attr($default_max_value) . '"' : '';
                $min_placeholder = $format_dimension_placeholder($dimension_min_placeholder, $default_min_value, 'Min');
                $max_placeholder = $format_dimension_placeholder($dimension_max_placeholder, $default_max_value, 'Max');

                $formOutPut .= '<div class="dimension-row">';
                $formOutPut .= '<div class="dimension-label" style=" margin-bottom: 5px; ">' . esc_html($config['label']) . '</div>';
                $formOutPut .= '<div class="dimension-inputs" style=" display: flex; gap: 10px; margin-bottom: 10px; ">';
                $formOutPut .= '<div class="dimension-input-group">';
                $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="number" name="min_' . esc_attr($dimension) . '" value="' . esc_attr($min_value) . '" placeholder="' . esc_attr($min_placeholder) . '" step="0.1" min="' . esc_attr($min_attr) . '"' . $max_attr . ' data-default="' . esc_attr($default_min_value) . '" />';
                $formOutPut .= '</div>';
                $formOutPut .= '<div class="dimension-input-group">';
                $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="number" name="max_' . esc_attr($dimension) . '" value="' . esc_attr($max_value) . '" placeholder="' . esc_attr($max_placeholder) . '" step="0.1" min="' . esc_attr($min_attr) . '"' . $max_attr . ' data-default="' . esc_attr($default_max_value) . '" />';
                $formOutPut .= '</div>';
                $formOutPut .= '</div>';
                $formOutPut .= '</div>';
            }

            $formOutPut .= '</div>';
        }

        $formOutPut .= $render_layout_end($dimensions_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($dimensions_search_settings, $sub_option, 'dimensions');
        $formOutPut .= '</div>';
        $formOutPut .= '</div>';
    }
    // Unified Dimension Filter end

    // SKU Filter
    $sub_option = isset($dapfforwc_styleoptions["sku"]["sub_option"]) ? $dapfforwc_styleoptions["sku"]["sub_option"] : "input";
    $minimizable = isset($dapfforwc_styleoptions["sku"]["minimize"]["type"]) ? $dapfforwc_styleoptions["sku"]["minimize"]["type"] : "arrow";
    $show_sku = $filter_enabled('show_sku');
    $sku_placeholder = isset($dapfforwc_styleoptions["placeholder"]["sku"]) ? $dapfforwc_styleoptions["placeholder"]["sku"] : esc_html__('Enter SKU...', 'dynamic-ajax-product-filters-for-woocommerce');
    $sku_search_settings = $get_terms_search_settings('sku');
    $sku_tooltip_settings = $get_tooltip_settings('sku');
    $sku_layout_settings = $get_layout_settings('sku');

    if (!empty($show_sku)) {
        $formOutPut .= '<div id="sku" class="plugincy-filter-group sku">';
        $sku_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["sku"]) && $dapfforwc_styleoptions["widget_title"]["sku"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["sku"]) : esc_html__('SKU', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $show_apply_reset_on === "separate") {
            $sku_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($sku_title_content, $minimizable, $sku_search_settings, $sku_tooltip_settings, 'sku');
        $formOutPut .= '<div class="items sku-container">';
        $formOutPut .= $render_search_container($sku_search_settings, $sub_option, 'sku');
        $formOutPut .= $render_layout_start($sku_layout_settings, $sub_option);

        // Get current SKU value from default filter
        $sku_value = isset($default_filter["sku"]) ? $default_filter["sku"] : (isset($default_filter["sku[]"][0]) ? $default_filter["sku[]"][0] : "");

        $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="text" name="sku" class="sku-field" placeholder="' . $sku_placeholder . '" value="' . esc_attr($sku_value) . '" />';

        $formOutPut .= $render_layout_end($sku_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($sku_search_settings, $sub_option, 'sku');
        $formOutPut .= '</div>';
        $formOutPut .= '</div>';
    }
    // SKU Filter end

    // Discount Filter
    $sub_option = isset($dapfforwc_styleoptions["discount"]["sub_option"]) ? $dapfforwc_styleoptions["discount"]["sub_option"] : "input";
    $minimizable = isset($dapfforwc_styleoptions["discount"]["minimize"]["type"]) ? $dapfforwc_styleoptions["discount"]["minimize"]["type"] : "arrow";
    $show_discount = $filter_enabled('show_discount');
    $discount_placeholder = isset($dapfforwc_styleoptions["placeholder"]["discount"]) ? $dapfforwc_styleoptions["placeholder"]["discount"] : esc_html__('Min. % off', 'dynamic-ajax-product-filters-for-woocommerce');
    $discount_search_settings = $get_terms_search_settings('discount');
    $discount_tooltip_settings = $get_tooltip_settings('discount');
    $discount_layout_settings = $get_layout_settings('discount');

    if (!empty($show_discount)) {
        $formOutPut .= '<div id="discount" class="plugincy-filter-group discount">';
        $discount_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["discount"]) && $dapfforwc_styleoptions["widget_title"]["discount"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["discount"]) : esc_html__('Minimum Discount (%)', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $show_apply_reset_on === "separate") {
            $discount_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($discount_title_content, $minimizable, $discount_search_settings, $discount_tooltip_settings, 'discount');
        $formOutPut .= '<div class="items discount-container">';
        $formOutPut .= $render_search_container($discount_search_settings, $sub_option, 'discount');
        $formOutPut .= $render_layout_start($discount_layout_settings, $sub_option);


        // Get current discount value from default filter
        $discount_value = isset($default_filter["discount"]) ? $default_filter["discount"] : '';

        $formOutPut .= '<div class="discount-input-group">';
        $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="number" name="discount" class="discount-field" placeholder="' . $discount_placeholder . '" value="' . esc_attr($discount_value) . '" min="0" max="100" step="1" />';
        $formOutPut .= '</div>';

        $formOutPut .= $render_layout_end($discount_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($discount_search_settings, $sub_option, 'discount');
        $formOutPut .= '</div>';
        $formOutPut .= '</div>';
    }
    // Discount Filter end

    // Date Filter
    $sub_option = isset($dapfforwc_styleoptions["date_filter"]["sub_option"]) ? $dapfforwc_styleoptions["date_filter"]["sub_option"] : "select";
    $minimizable = isset($dapfforwc_styleoptions["date_filter"]["minimize"]["type"]) ? $dapfforwc_styleoptions["date_filter"]["minimize"]["type"] : "arrow";
    $show_date_filter = $filter_enabled('show_date_filter');
    $date_filter_search_settings = $get_terms_search_settings('date_filter');
    $date_filter_tooltip_settings = $get_tooltip_settings('date_filter');
    $date_filter_layout_settings = $get_layout_settings('date_filter');

    if (!empty($show_date_filter)) {
        $formOutPut .= '<div id="date_filter" class="plugincy-filter-group date_filter">';
        $date_filter_title_content = ((isset($dapfforwc_styleoptions["widget_title"]) && isset($dapfforwc_styleoptions["widget_title"]["date_filter"]) && $dapfforwc_styleoptions["widget_title"]["date_filter"] !== "") ? esc_html($dapfforwc_styleoptions["widget_title"]["date_filter"]) : esc_html__('Date Filter', 'dynamic-ajax-product-filters-for-woocommerce'));
        if ($show_reset_btn === "yes" && $show_apply_reset_on === "separate") {
            $date_filter_title_content .= ' <span class="reset-value">' . ((isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        $formOutPut .= $render_title_bar($date_filter_title_content, $minimizable, $date_filter_search_settings, $date_filter_tooltip_settings, 'date_filter');
        $formOutPut .= '<div class="items date-filter-container">';
        $formOutPut .= $render_search_container($date_filter_search_settings, $sub_option, 'date_filter');
        $formOutPut .= $render_layout_start($date_filter_layout_settings, $sub_option);


        // Get current values from default filter
        $date_filter_value = isset($default_filter["date_filter"]) ? $default_filter["date_filter"] : '';
        $date_from_value = isset($default_filter["date_from"]) ? $default_filter["date_from"] : '';
        $date_to_value = isset($default_filter["date_to"]) ? $default_filter["date_to"] : '';
        $date_filter_all_time_text = !empty(trim((string) ($dapfforwc_styleoptions['date_filter_texts']['all_time_text'] ?? ''))) ? $dapfforwc_styleoptions['date_filter_texts']['all_time_text'] : 'All Time';
        $date_filter_today_text = !empty(trim((string) ($dapfforwc_styleoptions['date_filter_texts']['today_text'] ?? ''))) ? $dapfforwc_styleoptions['date_filter_texts']['today_text'] : 'Today';
        $date_filter_this_week_text = !empty(trim((string) ($dapfforwc_styleoptions['date_filter_texts']['this_week_text'] ?? ''))) ? $dapfforwc_styleoptions['date_filter_texts']['this_week_text'] : 'This Week';
        $date_filter_this_month_text = !empty(trim((string) ($dapfforwc_styleoptions['date_filter_texts']['this_month_text'] ?? ''))) ? $dapfforwc_styleoptions['date_filter_texts']['this_month_text'] : 'This Month';
        $date_filter_this_year_text = !empty(trim((string) ($dapfforwc_styleoptions['date_filter_texts']['this_year_text'] ?? ''))) ? $dapfforwc_styleoptions['date_filter_texts']['this_year_text'] : 'This Year';

        // Date filter options
        $date_options = [
            '' => $date_filter_all_time_text,
            'today' => $date_filter_today_text,
            'this_week' => $date_filter_this_week_text,
            'this_month' => $date_filter_this_month_text,
            'this_year' => $date_filter_this_year_text,
            // 'custom' => 'Custom Range'
        ];

        $formOutPut .= '<div class="date-filter-select-group">';
        $formOutPut .= '<select ' . ($disable_unselected ? "disabled" : "") . ' name="date_filter" class="date-filter-select">';

        foreach ($date_options as $value => $label) {
            $selected = ($date_filter_value === $value) ? ' selected' : '';
            $formOutPut .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }

        $formOutPut .= '</select>';
        $formOutPut .= '</div>';

        // Custom date range inputs (hidden by default, shown when custom is selected)
        $custom_display = ($date_filter_value === 'custom') ? 'block' : 'none !important';
        $formOutPut .= '<div class="custom-date-range" style="display: ' . $custom_display . ';">';
        $formOutPut .= '<div class="date-input-group">';
        $formOutPut .= '<label>From:</label>';
        $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="date" name="date_from" value="' . esc_attr($date_from_value) . '" class="date-from-field" />';
        $formOutPut .= '</div>';
        $formOutPut .= '<div class="date-input-group">';
        $formOutPut .= '<label>To:</label>';
        $formOutPut .= '<input ' . ($disable_unselected ? "disabled" : "") . ' type="date" name="date_to" value="' . esc_attr($date_to_value) . '" class="date-to-field" />';
        $formOutPut .= '</div>';
        $formOutPut .= '</div>';

        $formOutPut .= $render_layout_end($date_filter_layout_settings, $sub_option);
        $formOutPut .= $render_search_after_terms($date_filter_search_settings, $sub_option, 'date_filter');
        $formOutPut .= '</div>';
        $formOutPut .= '</div>';
    }
    // Date Filter end


    // apply & reset buttons
    if ($show_apply_reset_on !== "separate") {
        $has_filter_heading = $show_filter_heading === 'yes' && $show_apply_reset_on === 'top';
        $formOutPut .= '<div class="dapfforwc-apply-reset-container' . ($has_filter_heading ? ' has-filter-heading' : '') . '" style="order:' . ($show_apply_reset_on === "top" ? "-1" : "9999") . ';">';
        if ($has_filter_heading) {
            $formOutPut .= '<span class="dapfforwc-filter-heading">' . ($filter_heading_text !== '' ? esc_html($filter_heading_text) : esc_html__('Filter', 'dynamic-ajax-product-filters-for-woocommerce')) . '</span>';
        }
        if ($show_apply_btn === "yes") {
            $formOutPut .= '<button type="button" class="button dapfforwc-apply-filters-btn">
            <svg width="16" height="16" fill="#fff" viewBox="-0.04 -0.04 0.48 0.48" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMinYMin" class="jam jam-filter"><path d="m.042.04.13.162A.04.04 0 0 1 .18.227V.36L.22.33V.227A.04.04 0 0 1 .229.202L.358.04zm0-.04h.317A.04.04 0 0 1 .39.065L.26.227V.33a.04.04 0 0 1-.016.032l-.04.03A.04.04 0 0 1 .14.36V.227L.01.065A.04.04 0 0 1 .042 0"/></svg>
            ' . ((isset($dapfforwc_styleoptions["applybtntext"]) && isset($dapfforwc_styleoptions["applybtntext"]["reset_btn"]) && $dapfforwc_styleoptions["applybtntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["applybtntext"]["reset_btn"]) : esc_html__('Apply Filters', 'dynamic-ajax-product-filters-for-woocommerce')) . '</button>';
        }
        if ($show_reset_btn === "yes") {
            $formOutPut .= '<button type="button" class="button dapfforwc-reset-filters-btn">
                <svg width="16" fill="#fff" height="16" viewBox="0 0 38.4 38.4" xmlns="http://www.w3.org/2000/svg"><path d="M19.2 0v4.267c8.233 0 14.933 6.699 14.933 14.933s-6.7 14.933-14.933 14.933S4.267 27.435 4.267 19.2c0-3.94 1.568-7.65 4.267-10.415v5.082H12.8V2.133H1.067V6.4h3.821A19.18 19.18 0 0 0 0 19.2c0 10.586 8.612 19.2 19.2 19.2s19.2-8.614 19.2-19.2S29.788 0 19.2 0" fill-rule="evenodd"/></svg>
                ' . ((isset($dapfforwc_styleoptions["btntext"]) && isset($dapfforwc_styleoptions["btntext"]["reset_btn"]) && $dapfforwc_styleoptions["btntext"]["reset_btn"] !== "") ? esc_html($dapfforwc_styleoptions["btntext"]["reset_btn"]) : esc_html__('Reset Filters', 'dynamic-ajax-product-filters-for-woocommerce')) . '</button>';
        }
        $formOutPut .= '</div>';
    }

    return $formOutPut;
} //function ends


/**
 * Reusable function to sort terms by various criteria
 * 
 * @param array $terms Array of term objects or arrays to sort
 * @param string $order_by Sort criteria: 'alpha', 'numeric', 'month_year', 'menu_order', 'count', 'slug', 'default'
 * @param string $order Sort direction: 'asc' or 'desc'
 * @return array Sorted terms array
 */
function dapfforwc_sort_terms($terms, $order_by = 'default', $order = 'asc')
{
    // Return original array if default or empty
    if ($order_by === 'default' || empty($terms)) {
        return $terms;
    }

    $menu_orders = [];
    if ($order_by === 'menu_order') {
        $term_ids = [];
        foreach ($terms as $term) {
            $term_id = is_object($term) ? ($term->term_id ?? 0) : ($term['term_id'] ?? 0);
            if ($term_id) {
                $term_ids[] = (int) $term_id;
            }
        }

        if (!empty($term_ids)) {
            $term_ids = array_values(array_unique($term_ids));
            update_meta_cache('term', $term_ids);
            foreach ($term_ids as $term_id) {
                $menu_order = get_term_meta($term_id, 'order', true);
                $menu_orders[$term_id] = is_numeric($menu_order) ? (int) $menu_order : 0;
            }
        }
    }

    usort($terms, function ($a, $b) use ($order_by, $order, $menu_orders) {
        // Handle both object and array formats
        $nameA = is_object($a) ? ($a->name ?? '') : ($a['name'] ?? '');
        $nameB = is_object($b) ? ($b->name ?? '') : ($b['name'] ?? '');
        $slugA = is_object($a) ? ($a->slug ?? '') : ($a['slug'] ?? '');
        $slugB = is_object($b) ? ($b->slug ?? '') : ($b['slug'] ?? '');
        $countA = is_object($a) ? ($a->count ?? 0) : ($a['count'] ?? 0);
        $countB = is_object($b) ? ($b->count ?? 0) : ($b['count'] ?? 0);
        $valueA = null;
        $valueB = null;

        switch ($order_by) {
            case 'alpha':
                // Alphabetical sorting by name
                $valueA = strtolower($nameA);
                $valueB = strtolower($nameB);
                break;

            case 'numeric':
                // Extract numeric values from names
                preg_match('/\d+/', $nameA, $matchesA);
                preg_match('/\d+/', $nameB, $matchesB);
                $valueA = !empty($matchesA) ? (int)$matchesA[0] : 0;
                $valueB = !empty($matchesB) ? (int)$matchesB[0] : 0;
                break;

            case 'month_year':
                // Parse dates from names
                $valueA = strtotime($nameA);
                $valueB = strtotime($nameB);
                // If strtotime fails, fallback to alphabetical
                if ($valueA === false) $valueA = 0;
                if ($valueB === false) $valueB = 0;
                break;

            case 'count':
                // Sort by product count
                $valueA = (int)$countA;
                $valueB = (int)$countB;
                break;

            case 'menu_order':
                $termIdA = is_object($a) ? ($a->term_id ?? 0) : ($a['term_id'] ?? 0);
                $termIdB = is_object($b) ? ($b->term_id ?? 0) : ($b['term_id'] ?? 0);
                $valueA = $menu_orders[(int) $termIdA] ?? 0;
                $valueB = $menu_orders[(int) $termIdB] ?? 0;
                break;

            case 'slug':
                // Sort by slug
                $valueA = strtolower($slugA);
                $valueB = strtolower($slugB);
                break;

            default:
                return 0;
        }

        // Handle equal values
        if ($valueA == $valueB) {
            if ($order_by === 'menu_order') {
                return strcasecmp((string) $nameA, (string) $nameB);
            }
            return 0;
        }

        // Apply sort direction
        if ($order === 'asc') {
            return ($valueA < $valueB) ? -1 : 1;
        } else {
            return ($valueA > $valueB) ? -1 : 1;
        }
    });

    return $terms;
}
