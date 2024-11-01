<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Webshoplogin WooCommerce plug-in configuration file
define("WSL_API_CONSTANT", "https://developers.webshoplogin.com");
define("WSL_API_DASHBOARD", "https://developers.webshoplogin.com/dashboard");
define("WSL_API_VERSION", "v2.0");
define("WSL_API_GET_USER", "/getUser");
define("WSL_SHOPPING_CART_ENDPOINT", "/wsl_analytics_shopping_cart");
define("WSL_CHECKOUT_ENDPOINT", "/wsl_analytics_checkout");
define("WSL_SUCCESS_ENDPOINT", "/wsl_analytics_success");

function wsl_plugin_enabled()
{
    $wsl_enabled = get_option('wsl_login_enable');
    if($wsl_enabled === 'on') {
        return true;
    }
    return false;
}

// Loading plugin translation files
add_action( 'plugins_loaded', 'wsl_plugin_load_plugin_textdomain' );
function wsl_plugin_load_plugin_textdomain() {
    load_plugin_textdomain( 'webshoplogin-single-sign-on', FALSE, basename(  __DIR__  ) . '/languages/' );
}
