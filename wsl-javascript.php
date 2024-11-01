<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('wp_enqueue_scripts', 'wsl_javascript');
function wsl_javascript() {
    $wsl_javascript_url = WSL_API_CONSTANT . "/api/" . WSL_API_VERSION . "/webshoplogin.js?api_key=" . get_option('wsl_login_api_key');
    wp_enqueue_script('webshoploginjavascript', $wsl_javascript_url, null, null, true);

    wsl_inject_javascript();

    $plugin_dir_url = plugin_dir_url(__FILE__);
    wp_enqueue_script('wsl-pluginjavascript', $plugin_dir_url . '/javascript/wsl-javascript.js', null, null, true);
}

function wsl_inject_javascript()
{
    ?>
    <script type="text/javascript">
        var is_checkout = <?php echo(is_checkout() == true ? 'true' : 'false'); ?>;
        var is_user_logged_in = <?php echo(is_user_logged_in() == true ? 'true' : 'false'); ?>;
        var error_message = "<?php _e('Helaas. Er ging iets fout tijdens het inloggen. Probeer het later opnieuw!', 'webshoplogin-single-sign-on') ?>";
        var error_not_verified = "<?php _e('Er bestaat al een gebruiker met dit e-mail adres, om zeker te weten dat jij dit bent moet je eerst je e-mailadres valideren in ShopLogin', 'webshoplogin-single-sign-on')?>";
        var error_no_account_data = "<?php _e('Helaas. Er ging iets fout tijdens het inloggen. Er was geen account informatie beschikbaar.', 'webshoplogin-single-sign-on') ?>";
        var get_site_url = "<?php echo(get_site_url()); ?>";
    </script>
    <?php
}