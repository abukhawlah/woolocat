<?php
/**
 * Plugin Name: Woolocat
 * Plugin URI: https://github.com/abukhawlah/woolocat
 * Description: WooCommerce Order Location Analytics - Track and analyze your orders with advanced mapping features
 * Version: 1.7.2
 * Author: Abu Khawlah
 * Author URI: https://github.com/abukhawlah
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woolocat
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
function wc_orders_location_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>Woolocat requires WooCommerce to be installed and active.</p></div>';
        });
        return false;
    }
    return true;
}

// Add menu item to WordPress admin
function wc_orders_location_menu() {
    add_menu_page(
        'Woolocat',
        'Woolocat',
        'manage_woocommerce',
        'woolocat',
        'wc_orders_location_page',
        'dashicons-location',
        58
    );
}
add_action('admin_menu', 'wc_orders_location_menu');

// Add settings page
function wc_orders_location_settings_init() {
    register_setting(
        'wc_orders_location_settings',
        'wc_orders_location_options',
        array(
            'sanitize_callback' => 'wc_orders_location_sanitize_options',
            'default' => array(
                'google_maps_api_key' => '',
                'store_address' => '',
                'weather_api_key' => ''
            )
        )
    );
    
    add_settings_section(
        'wc_orders_location_section',
        'API Settings',
        'wc_orders_location_section_callback',
        'wc_orders_location_settings'
    );

    add_settings_field(
        'google_maps_api_key',
        'Google Maps API Key',
        'wc_orders_location_api_key_field',
        'wc_orders_location_settings',
        'wc_orders_location_section',
        array('label_for' => 'google_maps_api_key')
    );

    add_settings_field(
        'store_address',
        'Store Address',
        'wc_orders_location_store_address_field',
        'wc_orders_location_settings',
        'wc_orders_location_section',
        array('label_for' => 'store_address')
    );

    add_settings_field(
        'weather_api_key',
        'OpenWeatherMap API Key',
        'wc_orders_location_weather_api_key_field',
        'wc_orders_location_settings',
        'wc_orders_location_section',
        array('label_for' => 'weather_api_key')
    );
}
add_action('admin_init', 'wc_orders_location_settings_init');

function wc_orders_location_section_callback() {
    echo '<p>Enter your API keys and store location details below. Make sure to enable the following APIs in your Google Cloud Console:</p>';
    echo '<ul>';
    echo '<li>Maps JavaScript API</li>';
    echo '<li>Geocoding API</li>';
    echo '<li>Distance Matrix API</li>';
    echo '</ul>';
}

function wc_orders_location_api_key_field() {
    $options = get_option('wc_orders_location_options');
    $value = isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
    ?>
    <input type='text' 
           id='google_maps_api_key'
           name='wc_orders_location_options[google_maps_api_key]' 
           value='<?php echo esc_attr($value); ?>' 
           class="regular-text"
           required>
    <p class="description">Get your API key from the <a href="https://console.cloud.google.com/google/maps-apis/overview" target="_blank">Google Cloud Console</a></p>
    <?php
}

function wc_orders_location_store_address_field() {
    $options = get_option('wc_orders_location_options');
    $value = isset($options['store_address']) ? $options['store_address'] : '';
    ?>
    <input type='text' 
           id='store_address'
           name='wc_orders_location_options[store_address]' 
           value='<?php echo esc_attr($value); ?>' 
           class="regular-text"
           placeholder="Full address including country"
           required>
    <p class="description">Enter your complete store address (e.g., 19 Donovan close, Gordons Bay, 7780, South Africa)</p>
    <?php
}

function wc_orders_location_weather_api_key_field() {
    $options = get_option('wc_orders_location_options');
    $value = isset($options['weather_api_key']) ? $options['weather_api_key'] : '';
    ?>
    <input type='text' 
           id='weather_api_key'
           name='wc_orders_location_options[weather_api_key]' 
           value='<?php echo esc_attr($value); ?>' 
           class="regular-text"
           required>
    <p class="description">Get your API key from <a href="https://openweathermap.org/api" target="_blank">OpenWeatherMap</a></p>
    <?php
}

// Add settings page to menu
function wc_orders_location_settings_menu() {
    add_submenu_page(
        'woolocat',
        'Woolocat Settings',
        'Settings',
        'manage_options',
        'woolocat-settings',
        'wc_orders_location_settings_page'
    );
}
add_action('admin_menu', 'wc_orders_location_settings_menu');

function wc_orders_location_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'wc_orders_location_messages',
            'wc_orders_location_message',
            'Settings Saved',
            'updated'
        );
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('wc_orders_location_messages'); ?>
        <form action='options.php' method='post'>
            <?php
            settings_fields('wc_orders_location_settings');
            do_settings_sections('wc_orders_location_settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function wc_orders_location_page() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $options = get_option('wc_orders_location_options');
    $store_address = isset($options['store_address']) ? $options['store_address'] : '';
    ?>
    <div class="wrap wc-orders-location-wrap">
        <h1>Woolocat Analytics</h1>
        
        <?php if (empty($store_address)): ?>
            <div class="notice notice-error">
                <p>Please set your store address in the <a href="<?php echo admin_url('admin.php?page=woolocat-settings'); ?>">settings page</a>.</p>
            </div>
        <?php endif; ?>

        <!-- Store Address (hidden) -->
        <input type="hidden" id="store-address" value="<?php echo esc_attr($store_address); ?>">
        
        <!-- Export Button -->
        <form method="post" class="export-form">
            <button type="submit" name="export_location_data" class="button button-primary">
                <span class="dashicons dashicons-download"></span>
                Export Data
            </button>
        </form>

        <!-- View Toggle Buttons -->
        <div class="view-toggles">
            <button class="button active" data-view="map">
                <span class="dashicons dashicons-location"></span>
                Map View
            </button>
            <button class="button" data-view="heatmap">
                <span class="dashicons dashicons-chart-area"></span>
                Heat Map
            </button>
            <button class="button" data-view="clusters">
                <span class="dashicons dashicons-groups"></span>
                Customer Clusters
            </button>
        </div>

        <!-- Map Container -->
        <div id="map"></div>

        <!-- Orders Table -->
        <div class="orders-table-wrap">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Distance</th>
                        <th>Est. Delivery Time</th>
                        <th>Order Count</th>
                        <th>Total Revenue</th>
                        <th>Weather Impact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orders = wc_get_orders(array(
                        'limit' => -1,
                        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold')
                    ));

                    $orders_by_location = array();
                    foreach ($orders as $order) {
                        $address = array(
                            'address_1' => $order->get_shipping_address_1(),
                            'address_2' => $order->get_shipping_address_2(),
                            'city' => $order->get_shipping_city(),
                            'state' => $order->get_shipping_state(),
                            'postcode' => $order->get_shipping_postcode(),
                            'country' => $order->get_shipping_country()
                        );
                        
                        $full_address = implode(', ', array_filter($address));
                        if (!isset($orders_by_location[$full_address])) {
                            $orders_by_location[$full_address] = array(
                                'count' => 0,
                                'total' => 0,
                                'orders' => array()
                            );
                        }
                        
                        $orders_by_location[$full_address]['count']++;
                        $orders_by_location[$full_address]['total'] += $order->get_total();
                        $orders_by_location[$full_address]['orders'][] = array(
                            'id' => strval($order->get_id()),
                            'date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                            'status' => $order->get_status(),
                            'total' => strip_tags(wc_price($order->get_total())),
                            'customer' => strip_tags($order->get_formatted_billing_full_name())
                        );
                    }

                    foreach ($orders_by_location as $address => $data): ?>
                        <tr>
                            <td><?php echo esc_html($address); ?></td>
                            <td class="distance-cell loading">Calculating...</td>
                            <td class="delivery-time-cell loading">Calculating...</td>
                            <td><span class="badge"><?php echo esc_html($data['count']); ?></span></td>
                            <td><?php echo wc_price($data['total']); ?></td>
                            <td class="weather-impact" data-location="<?php echo esc_attr($address); ?>">
                                <span class="loading">Checking weather...</span>
                            </td>
                            <td>
                                <button type="button" class="button button-secondary view-orders" 
                                    data-orders="<?php echo htmlspecialchars(json_encode($data['orders']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    View Orders
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Orders Modal -->
        <div id="orders-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Order Details</h2>
                <div class="modal-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="modal-orders-list">
                            <!-- Orders will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 900px;
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .modal h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .modal-body {
        max-height: 60vh;
        overflow-y: auto;
    }

    .badge {
        display: inline-block;
        background-color: #007cba;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.9em;
    }

    #modal-orders-list .badge {
        text-transform: capitalize;
    }

    .button-small {
        padding: 2px 8px !important;
        font-size: 11px !important;
        line-height: 1.5 !important;
    }

    .button-small .dashicons {
        font-size: 14px;
        width: 14px;
        height: 14px;
        line-height: 1.3;
        margin-right: 2px;
    }
    </style>
    <?php
}

// Add AJAX handlers for delivery time and weather data
add_action('wp_ajax_get_delivery_time', 'wc_orders_location_get_delivery_time');
add_action('wp_ajax_get_weather_data', 'wc_orders_location_get_weather_ajax');

function wc_orders_location_get_delivery_time() {
    check_ajax_referer('woolocat_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Permission denied');
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if (!$order_id) {
        wp_send_json_error('Invalid order ID');
    }

    $delivery_time = get_post_meta($order_id, '_delivery_time', true);
    if ($delivery_time) {
        $hours = round($delivery_time / 3600, 1);
        wp_send_json_success(array('delivery_time' => $hours . ' hours'));
    } else {
        wp_send_json_success(array('delivery_time' => 'N/A'));
    }
}

function wc_orders_location_get_weather_ajax() {
    check_ajax_referer('woolocat_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Permission denied');
    }

    $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
    if (!$location) {
        wp_send_json_error('Invalid location');
    }

    $weather_data = wc_orders_location_get_weather_data($location);
    if ($weather_data && isset($weather_data['weather'][0]['main'])) {
        wp_send_json_success(array('weather' => $weather_data['weather'][0]['main']));
    } else {
        wp_send_json_success(array('weather' => 'N/A'));
    }
}

function wc_orders_location_get_weather_data($location) {
    $options = get_option('wc_orders_location_options');
    $api_key = isset($options['weather_api_key']) ? $options['weather_api_key'] : '';
    
    if (empty($api_key)) {
        return false;
    }

    // First, geocode the location to get coordinates
    $geocode_url = add_query_arg(
        array(
            'address' => urlencode($location),
            'key' => $options['google_maps_api_key']
        ),
        'https://maps.googleapis.com/maps/api/geocode/json'
    );

    $geocode_response = wp_remote_get($geocode_url);
    if (is_wp_error($geocode_response)) {
        return false;
    }

    $geocode_data = json_decode(wp_remote_retrieve_body($geocode_response), true);
    if (!isset($geocode_data['results'][0]['geometry']['location'])) {
        return false;
    }

    $lat = $geocode_data['results'][0]['geometry']['location']['lat'];
    $lng = $geocode_data['results'][0]['geometry']['location']['lng'];

    // Now get weather data using coordinates
    $weather_url = add_query_arg(
        array(
            'lat' => $lat,
            'lon' => $lng,
            'appid' => $api_key,
            'units' => 'metric'
        ),
        'https://api.openweathermap.org/data/2.5/weather'
    );

    $weather_response = wp_remote_get($weather_url);
    if (is_wp_error($weather_response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($weather_response), true);
}

function wc_orders_location_enqueue_scripts() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'woolocat') {
        return;
    }

    // Enqueue jQuery first
    wp_enqueue_script('jquery');

    // Enqueue our custom JS
    wp_enqueue_script(
        'woolocat-js',
        plugins_url('js/woolocat.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );

    // Localize script
    wp_localize_script('woolocat-js', 'woolocatData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('woolocat_nonce')
    ));

    // Enqueue our CSS
    wp_enqueue_style(
        'woolocat-css',
        plugins_url('css/style.css', __FILE__),
        array(),
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'wc_orders_location_enqueue_scripts');

// Add Google Maps script to head
function wc_orders_location_admin_head() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'woolocat') {
        return;
    }

    $options = get_option('wc_orders_location_options');
    $api_key = isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
    ?>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>&libraries=places,visualization"></script>
    <script type="text/javascript" src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
    <?php
}
add_action('admin_head', 'wc_orders_location_admin_head');

// Add meta tags for security
function wc_orders_location_admin_meta() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'woolocat') {
        return;
    }
    ?>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: blob:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://maps.googleapis.com https://*.gstatic.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https://*.googleapis.com https://*.gstatic.com; font-src 'self' https://fonts.gstatic.com;">
    <?php
}
add_action('admin_head', 'wc_orders_location_admin_meta');

// Add activation hook to create necessary options
register_activation_hook(__FILE__, 'wc_orders_location_activate');

function wc_orders_location_activate() {
    // Initialize plugin options with default values
    $default_options = array(
        'google_maps_api_key' => '',
        'store_address' => '',
        'weather_api_key' => ''
    );
    
    add_option('wc_orders_location_options', $default_options);
}

// Add sanitization function
function wc_orders_location_sanitize_options($input) {
    $sanitized_input = array();
    
    if (isset($input['google_maps_api_key'])) {
        $sanitized_input['google_maps_api_key'] = sanitize_text_field($input['google_maps_api_key']);
    }
    
    if (isset($input['store_address'])) {
        $sanitized_input['store_address'] = sanitize_text_field($input['store_address']);
    }
    
    if (isset($input['weather_api_key'])) {
        $sanitized_input['weather_api_key'] = sanitize_text_field($input['weather_api_key']);
    }
    
    return $sanitized_input;
}

// Add delivery time column
function add_delivery_time_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'distance') {
            $new_columns['est_delivery_time'] = 'Est. Delivery Time';
        }
    }
    return $new_columns;
}
add_filter('wc_orders_location_columns', 'add_delivery_time_column');

// Add delivery time cell content
function add_delivery_time_cell($column, $order) {
    if ($column === 'est_delivery_time') {
        echo '<span class="delivery-time-cell loading">Calculating...</span>';
    }
}
add_action('wc_orders_location_column_content', 'add_delivery_time_cell', 10, 2);
