<?php
/**
 * Plugin Name: WebshopLogin Single Sign On
 * Description: Laat je bezoekers inloggen met één account met hulp van hun bestaande accounts van bijvoorbeeld: Facebook, Paypal, Amazon, of Google of een van de vele aangesloten webwinkels.
 * Version: 1.0.5
 * Author: https://www.webshoplogin.com
 * Text Domain: webshoplogin-single-sign-on
 * Domain Path: /languages
 * Author URI: https://www.webshoplogin.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 3.0.1
 * WC tested up to: 4.9
 **/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action( 'admin_notices', 'wsl_woocommerce_not_installed' );
    return false;
}

function wsl_woocommerce_not_installed()
{
    ?>
    <div class="error notice is-dismissible">
        <h2><?php _e('Om WebshopLogin te kunnen gebruiken is WooCommerce vereist, ', 'webshoplogin-single-sign-on'); ?><a href=" <?php admin_url('plugin-install.php?s=woocommerce&tab=search') ?>"> <?php _e('installeer WooCommerce' , 'webshoplogin-single-sign-on'); ?></a></h2>
        <p><?php _e('Er is een actieve WooCommerce installatie vereist om de WebshopLogin Single Sign On plug-in te laten werken. Installeer de WooCommerce plug-in, hierdoor wordt WebshopLogin automatisch geactiveerd.', 'webshoplogin-single-sign-on'); ?></p>
    </div>
    <?php
}

session_start();
require_once('wsl-config.php');
require_once('wsl-installation.php');
require_once('wsl-settings-page.php');
require_once('wsl-javascript.php');

add_action('init', 'wsl_api_rule');
function wsl_api_rule()
{
    add_rewrite_rule('wsl_sync_api$', 'index.php?wsl_sync_api=1', 'top');
    add_rewrite_rule('wsl_login_api$', 'index.php?wsl_login_api=1', 'top');
    add_rewrite_rule('wsl_analytics_shopping_cart$', 'index.php?wsl_analytics_shopping_cart=1', 'top');
    add_rewrite_rule('wsl_analytics_checkout$', 'index.php?wsl_analytics_checkout=1', 'top');
    add_rewrite_rule('wsl_analytics_success$', 'index.php?wsl_analytics_success=1', 'top');
}

add_filter('query_vars', 'wsl_query_vars');
function wsl_query_vars($query_vars)
{
    array_push($query_vars,
        "wsl_sync_api",
        "wsl_login_api",
        "wsl_analytics_shopping_cart",
        "wsl_analytics_checkout",
        "wsl_analytics_success"
    );
    return $query_vars;
}

add_action('parse_request', 'wsl_parse_request');
function wsl_parse_request(&$wp)
{
    if (array_key_exists('wsl_login_api', $wp->query_vars)) {
        $rawpostdata = file_get_contents('php://input');
        if ($rawpostdata) {
            $postdata = json_decode($rawpostdata, true);
            $wsl_user = wsl_get_user_from_wsl_api($postdata);
            $response = wsl_login_wsl_user($wsl_user);
            http_response_code(200);
            echo($response);
        }
        exit();
    }

    if (array_key_exists('wsl_sync_api', $wp->query_vars)) {
        $rawpostdata = file_get_contents('php://input');
        if ($rawpostdata) {
            $postdata = json_decode($rawpostdata, true);
            $wsl_user = wsl_get_user_from_wsl_api($postdata);
            $response = wsl_sync_wsl_user($wsl_user);
            http_response_code(200);
            echo($response);
        }
        exit();
    }

    if (array_key_exists('wsl_analytics_shopping_cart', $wp->query_vars)) {
        $rawpostdata = file_get_contents('php://input');
        if ($rawpostdata) {
            $postdata = json_decode($rawpostdata, true);
            $response = wsl_analytics_shopping_cart($postdata);
            http_response_code(200);
            echo($response);
        }
        exit();
    }

    if (array_key_exists('wsl_analytics_checkout', $wp->query_vars)) {
        $rawpostdata = file_get_contents('php://input');
        if ($rawpostdata) {
            $postdata = json_decode($rawpostdata, true);
            $response = wsl_analytics_checkout($postdata);
            http_response_code(200);
            echo($response);
        }
        exit();
    }

    if (array_key_exists('wsl_analytics_success', $wp->query_vars)) {
        $rawpostdata = file_get_contents('php://input');
        if ($rawpostdata) {
            $postdata = json_decode($rawpostdata, true);
            $response = wsl_analytics_success($postdata);
            http_response_code(200);
            echo($response);
        }
        exit();
    }
}

register_activation_hook(__FILE__, 'wsl_plugin_activation');
function wsl_plugin_activation()
{
    wsl_api_rule();
    flush_rewrite_rules();
    add_option('wsl_login_state_key', 'invalid');
    if(get_option('wsl_login_state_key') !== 'valid') {
        add_option('wsl_plugin_activated', 'true');
    }
}

register_deactivation_hook(__FILE__, 'wsl_plugin_deactivation');
function wsl_plugin_deactivation()
{
    update_option('wsl_login_enable', '');
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wsl_add_settings_link');
function wsl_add_settings_link($links) {
    $settings_link = array(
            '<a href="admin.php?page=wsl-login">' . __('Instellingen', 'webshoplogin-single-sign-on') . '</a>',
            '<a href="https://webshoplogin.com/dashboard">' . __('Dashboard', 'webshoplogin-single-sign-on') . '</a>',
        );
    return array_merge($links, $settings_link);
}

add_action( 'admin_notices', 'wsl_update_nag_notice' );
function wsl_update_nag_notice() {
    if(get_option('wsl_login_state_key') == 'invalid') {
        ?>
        <div class="error notice">
            <p><?php _e('<a href="admin.php?page=wsl-login">Om de WebshopLogin plugin te activeren moeten eerst de API key & secret worden ingevuld.</a>', 'webshoplogin-single-sign-on'); ?></p>
        </div>
        <?php
    }
}

// WSL button hooks
add_action('woocommerce_before_customer_login_form', 'wsl_show_login_button');
add_action('woocommerce_register_form', 'wsl_show_register_button');
add_action('woocommerce_before_checkout_form', 'wsl_show_checkout_button', 10);
add_action('woocommerce_before_cart', 'wsl_show_shoppingcart_button', 11);

// WSL analytic hooks
add_action('woocommerce_after_cart', 'wsl_javascript_analytics_shopping_cart', 10, 1);
add_action('woocommerce_after_checkout_form', 'wsl_javascript_analytics_checkout', 10, 1);
add_action('woocommerce_thankyou', 'wsl_javascript_analytics_success', 10, 1);

add_action('init', 'wsl_shortcode_init');
function wsl_shortcode_init()
{
    add_shortcode('wsl_login_button', 'shortcode_login_wsl');
    function shortcode_login_wsl()
    {
        return wsl_button('login');
    }

    add_shortcode('wsl_register_button', 'shortcode_register_wsl');
    function shortcode_register_wsl()
    {
        return wsl_button('register');
    }
}

#region buttons

function wsl_show_login_button()
{
    if(!is_user_logged_in() && wsl_plugin_enabled()) {
        ?>
        <div class="webshoploginbuttonparent" style="display: block;">
            <h3>Login met Shoplogin</h3>
            <span>Als u een account bij ons heeft, log dan in a.u.b.</span>

            <div id="wsl-button" class="webshoploginbutton"
                 data-type="login"
                 data-autoresize="true"
                 data-lang="<?php echo substr(get_bloginfo('language'), 0, 2); ?>">
            </div>

            -- OF --
            <br>
        </div>
        <?php
    }
}

function wsl_show_register_button()
{
    return wsl_button("register");
}

function wsl_show_checkout_button()
{
    return wsl_button("checkout");
}

function wsl_show_shoppingcart_button()
{
    return wsl_button("shoppingcart");
}

function wsl_button($datatype) {
    if(!is_user_logged_in() && wsl_plugin_enabled()) {
        ?>
        <div class="webshoploginbuttonparent" style="display: block;">
            <div id="wsl-button" class="webshoploginbutton"
                 data-type="<?php echo $datatype; ?>"
                 data-autoresize="true"
                 data-lang="<?php echo substr(get_bloginfo('language'), 0, 2); ?>">
            </div>
        </div>
        <?php
    }
}

#endregion

#region analytics

#region load javascript for analytics
function wsl_javascript_analytics_shopping_cart()
{
    wsl_load_analytics_javascript(WSL_SHOPPING_CART_ENDPOINT);
}

function wsl_javascript_analytics_checkout()
{
    wsl_load_analytics_javascript(WSL_CHECKOUT_ENDPOINT);
}

function wsl_javascript_analytics_success($order_id)
{
    wsl_load_analytics_javascript(WSL_SUCCESS_ENDPOINT, $order_id);
}

function wsl_load_analytics_javascript($endpoint, $order_id = null)
{
    ?>
    <script type="text/javascript">
        window.addEventListener("load", function(event) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "<?php echo(get_site_url() . $endpoint); ?>", true);
            var order_id = <?php echo(empty($order_id) ? '""' : $order_id); ?>;
            xhr.send(JSON.stringify(order_id));
        });
    </script>
    <?php
}

#endregion

#region send analytics to WSL

function wsl_analytics_shopping_cart($order_id)
{
    $url = WSL_API_CONSTANT . "/api/" . WSL_API_VERSION . "/analyticsshoppingcart";
    return wsl_post_analytics_to_wsl($url);
}

function wsl_analytics_checkout($order_id)
{
    $url = WSL_API_CONSTANT . "/api/" . WSL_API_VERSION . "/analyticscheckout";
    return wsl_post_analytics_to_wsl($url);
}

function wsl_analytics_success($order_id)
{
    $url = WSL_API_CONSTANT . "/api/" . WSL_API_VERSION . "/analyticssuccess";
    return wsl_post_analytics_to_wsl($url, $order_id);
}

function wsl_post_analytics_to_wsl($url, $order_id = null) {
    if($order_id !== null)
    {
        $order = wc_get_order($order_id);
        if(!$order) {
            return false;
        }

        $order_meta = wc_get_order_item_meta($order_id, '_send_success_analytics', true);

        if($order_meta) {
            return true;
        }

        wc_update_order_item_meta($order_id, '_send_success_analytics', true);

        $grand_total = $order->get_total();
        $currency = $order->get_currency();

    } else {
        global $woocommerce;
        $grand_total = $woocommerce->cart->total;
        $currency = get_woocommerce_currency();
    }

    $wsl_user_trackingid = $_COOKIE['wsl_tracking_id'];

    $data = array(
        "tracking_id" => $wsl_user_trackingid,
        "grto" => $grand_total,
        "currency" => $currency
    );

    $api_response = wsl_api_call_post($url, $data);

    if(!$api_response) {
        return false;
    }
    return true;
}

#endregion
#endregion analytics

#region login & sync WSL user
function wsl_get_wordpress_user_by_wsl_id($wsl_user_id)
{
    $args = array(
        'meta_query' => array(
            array(
                'key'     => '_wsl_user_id',
                'value'   => $wsl_user_id,
                'compare' => 'EXISTS',
            ),
        ),
    );
    $user = get_users($args);

    if (!empty($user)) {
        return $user[0];
    }

    return false;
}

function wsl_login_wordpress_user($user)
{
    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    do_action('wp_login', $user->data->user_login, $user);
}

function wsl_overwrite_wordpress_user($user_exists)
{
    $random_password = wp_generate_password(99, true);

    $user_data = array(
        'ID'        => $user_exists->ID,
        'user_pass' => $random_password,
    );
    wp_update_user($user_data);

    $metas = array(
        '_wsl_login_user_id',
        '_wsl_user_version',
        '_wsl_billing_address_version',
        '_wsl_shipping_address_version',
        '_wsl_customer_version',
    );

    foreach ($metas as $meta) {
        delete_user_meta($user_exists->ID, $meta);
    }
}

function wsl_delete_user_same_email($user_info)
{
    $duplicate_user = get_user_by('email', $user_info['username']);

    if (!($duplicate_user instanceof WP_User)) {
        return true;
    }

    if(wsl_get_wordpress_user_by_wsl_id($user_info['id'])->ID === $duplicate_user->ID) {
        return true;
    }

    if (!$user_info['verified']) {
        return false;
    }

    require_once(ABSPATH . 'wp-admin/includes/user.php');
    wp_delete_user($duplicate_user->ID);

    return true;
}

function wsl_update_user_info($wordpress_user, $user_info)
{
    $wordpress_user_version = get_user_meta($wordpress_user->ID, '_wsl_user_version', true);
    if ($wordpress_user_version === $user_info['version']) {
        return true;
    }

    $user_data = array(
        'ID'         => $wordpress_user->ID,
        'user_email' => $user_info['username'],
    );
    wp_update_user($user_data);
    update_user_meta($wordpress_user->ID, '_wsl_user_version', $user_info['version']);
    update_user_meta($wordpress_user->ID, '_wsl_user_id', $user_info['id']);

    return true;
}

function wsl_update_customer_info($wordpress_user, $customer_info)
{
    // If the WSL user and wordpress user information are the same
    $wordpress_customer_version = get_user_meta($wordpress_user->ID, '_wsl_customer_version', true);
    if ($wordpress_customer_version === $customer_info['version']) {
        return true;
    }

    $user_data = array(
        'ID'           => $wordpress_user->ID,
        'display_name' => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
        'first_name'   => $customer_info['firstname'],
        'last_name'    => $customer_info['lastname'],
    );
    wp_update_user($user_data);
    update_user_meta($wordpress_user->ID, '_wsl_customer_version', $customer_info['version']);

    return true;
}

function wsl_update_address_info($wordpress_user, $address_info)
{
    $wordpress_billing_address_version = get_user_meta($wordpress_user->ID, '_wsl_billing_address_version', true);
    $wordpress_shipping_address_version = get_user_meta($wordpress_user->ID, '_wsl_shipping_address_version', true);

    foreach ($address_info as $address) {
        if ($address['default_billing'] === 1 && $wordpress_billing_address_version < $address['version']) {
            $metas = wsl_make_address_array($address, 'billing');

            foreach ($metas as $key => $value) {
                update_user_meta($wordpress_user->ID, $key, $value);
            }

            update_user_meta($wordpress_user->ID, '_wsl_billing_address_version', $address['version']);
        }

        if ($address['default_shipping'] === 1 && $wordpress_shipping_address_version < $address['version']) {
            $metas = wsl_make_address_array($address, 'shipping');

            foreach ($metas as $key => $value) {
                update_user_meta($wordpress_user->ID, $key, $value);
            }

            update_user_meta($wordpress_user->ID, '_wsl_shipping_address_version', $address['version']);
        }
    }

    return true;
}

function wsl_make_address_array($address_info, $address_type)
{
    $metas = array(
        $address_type . '_first_name' => $address_info['firstname'],
        $address_type . '_last_name'  => $address_info['lastname'],
        $address_type . '_address_1'  => $address_info['street'] . ' ' . $address_info['number'],
        $address_type . '_country'    => $address_info['country'],
        $address_type . '_state'      => $address_info['province'],
        $address_type . '_city'       => $address_info['city'],
        $address_type . '_postcode'   => $address_info['postal'],
        $address_type . '_company'    => $address_info['company'],
    );

    if($address_type === 'billing') {
        $metas[$address_type . '_phone'] = $address_info['telephone'];
    }

    return $metas;
}

function wsl_login_wsl_user($wsl_user)
{
    if (!$wsl_user['user']['username'] && !$wsl_user['user']['id']) {
        return "ERROR-NO-ACCOUNT-DATA";
    }

    // If a Wordpress user exists with this Webshoplogin user id
    $wordpress_user = wsl_get_wordpress_user_by_wsl_id($wsl_user['user']['id']);
    if ($wordpress_user) {
        if (wsl_delete_user_same_email($wsl_user['user'])) {
            wsl_update_user_info($wordpress_user, $wsl_user['user']);
        }
        wsl_update_customer_info($wordpress_user, $wsl_user['customer']);
        wsl_update_address_info($wordpress_user, $wsl_user['addresses']);
        wsl_login_wordpress_user($wordpress_user);

        return "OK";
    }

    // If a Wordpress user exists with same emailaddress as the WSL user
    $wordpress_user = get_user_by('email', $wsl_user['user']['username']);
    if ($wordpress_user instanceof WP_User) {
        if ($wsl_user['user']['verified']) {
            wsl_overwrite_wordpress_user($wordpress_user);
            wsl_update_user_info($wordpress_user, $wsl_user['user']);
            wsl_update_customer_info($wordpress_user, $wsl_user['customer']);
            wsl_update_address_info($wordpress_user, $wsl_user['addresses']);
            wsl_login_wordpress_user($wordpress_user);

            return "OK";
        } else {
            return "ERROR-NOT-VERIFIED";
        }
    }

    // If no Wordpress user with wsl_id or email already exists, create new user
    $random_password = wp_generate_password(99, true);
    $wordpress_user_id = wp_create_user($wsl_user['user']['username'], $random_password, $wsl_user['user']['username']);

    // Set WooCommerce customer role
    $wordpress_user = new WP_User($wordpress_user_id);
    $wordpress_user->set_role('customer');

    // Update Wordpress user with wsl user data
    $wordpress_user = get_user_by('ID', $wordpress_user_id);
    if ($wordpress_user instanceof WP_User) {
        wsl_update_user_info($wordpress_user, $wsl_user['user']);
        wsl_update_customer_info($wordpress_user, $wsl_user['customer']);
        wsl_update_address_info($wordpress_user, $wsl_user['addresses']);
        wsl_login_wordpress_user($wordpress_user);
    }

    return "OK";
}

function wsl_sync_wsl_user($wsl_user) {
    if (!$wsl_user['user']['username'] && !$wsl_user['user']['id']) {
        return "ERROR-NO-ACCOUNT-DATA";
    }

    $logged_in_wordpress_user = wp_get_current_user();
    $wordpress_user = wsl_get_wordpress_user_by_wsl_id($wsl_user['user']['id']);

    // WSL user has no account
    if(!$wordpress_user) {
        return "ERROR";
    }

    // Not logged in
    if($logged_in_wordpress_user->ID == 0) {
        return "ERROR";
    }

    // When the current logged in user does not have the same ID as the wordpress user of the logged in WSL user
    if($logged_in_wordpress_user->ID !== $wordpress_user->ID) {
        return "ERROR";
    }

    if (wsl_delete_user_same_email($wsl_user['user'])) {
        wsl_update_user_info($wordpress_user, $wsl_user['user']);
    }
    wsl_update_customer_info($wordpress_user, $wsl_user['customer']);
    wsl_update_address_info($wordpress_user, $wsl_user['addresses']);

    return "OK";
}

#endregion login & sync WSL user

#region api calls
function wsl_make_nonce()
{
    $random_chars = '';
    for ($i = 0; $i < 255; $i++) {
        $random_chars .= chr(mt_rand(0, 255));
    }

    return hash('sha512', $random_chars);
}

function wsl_make_signature($api_key, $api_secret, $nonce)
{
    return hash_hmac('sha256', $api_key . $nonce, $api_secret);
}

function wsl_get_user_from_wsl_api($post_data)
{
    if (!wsl_plugin_enabled()) {
        return "ERROR";
    }
    if (empty($post_data['accesstoken'])) {
        return "ERROR-NO-TOKEN";
    }
    $get_user_api_url = WSL_API_CONSTANT . "/api/" . WSL_API_VERSION . WSL_API_GET_USER . "?accesstoken=" . $post_data['accesstoken'];
    return wsl_api_call_get($get_user_api_url);
}

function wsl_url_builder($url)
{
    if (empty($url)) {
        return false;
    }

    $api_key = get_option('wsl_login_api_key');
    $api_secret = get_option('wsl_login_api_secret');
    $nonce = wsl_make_nonce();
    $signature = wsl_make_signature($api_key, $api_secret, $nonce);

    if(strpos($url, '?') !== false) {
        $request_url = $url . "&api_key=" . $api_key . "&sig=" . $signature . "&nonce=" . $nonce;
    } else {
        $request_url = $url . "?api_key=" . $api_key . "&sig=" . $signature . "&nonce=" . $nonce;
    }

    return $request_url;
}

function wsl_api_call_get($request_url, $build_url = true)
{
    if($build_url) {
        $request_url = wsl_url_builder($request_url);
    }

    if(!$request_url) {
        return false;
    }

    $response = wp_remote_get($request_url, array(
        'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Origin' => get_site_url()
        ),
        'method'  => 'GET',
    ));

    $body = wp_remote_retrieve_body($response);

    $result = json_decode($body, true);
    if(json_last_error() === JSON_ERROR_NONE) {
        return $result;
    }
    return $body;
}

function wsl_api_call_post($url, $data, $build_url = true)
{
    if (empty($data)) {
        return false;
    }

    if($build_url) {
        $request_url = wsl_url_builder($url);
    } else {
        $request_url = $url;
    }

    if(!$request_url) {
        return false;
    }

    $response = wp_remote_post($request_url, array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Origin' => get_site_url()
        ),
        'method'  => 'POST',
        'body'    => json_encode($data),
    ));
    $body = wp_remote_retrieve_body($response);

    return json_decode($body, true);
}
#endregion api calls
?>
