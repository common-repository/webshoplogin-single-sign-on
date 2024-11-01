<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly    
}
function wsl_login_settings()
{
    ?>
    <div class="wrap">
        <?php
        // If the settings page is accessed by someone other than the administrator
        if(!current_user_can('manage_options')) {
            return;
        }

        if(isset($_GET['settings-updated'])) {
            if(!wsl_verify_api_keys()) {
                add_settings_error( 'wsl_messages', 'wsl_message', __('API key of secret is niet geldig', 'webshoplogin-single-sign-on') , 'error' );
            } else {
                update_option('wsl_installation', 'installed');
                update_option('wsl_login_enable', 'on');
                add_settings_error( 'wsl_messages', 'wsl_message', __('Plugin is geactiveerd', 'webshoplogin-single-sign-on') , 'updated' );
            }
        }

        settings_errors('wsl_messages');

        ?>
        <h2 class="nav-tab-wrapper">
            <a href="#" id="wsl-settings-tab" class="nav-tab nav-tab-active" onclick="clickSettingsTab()" ><?php _e('Instellingen', 'webshoplogin-single-sign-on') ?></a>
            <a href="#" id="wsl-developers-tab" class="nav-tab" onclick="clickWslTab()"><?php _e('WebshopLogin', 'webshoplogin-single-sign-on') ?></a>
            <a href="#" id="wsl-buttons-tab" class="nav-tab" onclick="clickButtonsTab()"><?php _e('Knoppen', 'webshoplogin-single-sign-on') ?></a>
        </h2>

        <?php
        if(isset($_GET['oneclick'])) {
            wsl_installation();
        }

        if(get_option('wsl_installation') !== 'installed') {
            ?>
            <form action="<?=admin_url( 'admin-post.php' ) ?>" method="post">
                <input type="hidden" name="action" value="wsl_install_button"/>
                <button id="wsl-configure-one-click"><?php _e('Installeer WebshopLogin Single Sign On', 'webshoplogin-single-sign-on') ?></button>
            </form>
            <?php
        } else {
            ?>
            <h3 id="plugin-succesfull-install-notice"><?php _e('Plugin is geactiveerd', 'webshoplogin-single-sign-on') ?></h3>
            <?php
        }
        ?>

        <form action="options.php" method="post">
            <div id="wsl-settings" class="tabpage">
                <button type="button" id="collapsible-button"><?php _e('Geavanceerde instellingen', 'webshoplogin-single-sign-on') ?></button>
                <div id="collapsible-content">
                    <?php
                    settings_fields('wsl_login');
                    do_settings_sections('api_settings');
                    do_settings_sections('plugin_settings');
                    submit_button(__('Opslaan', 'webshoplogin-single-sign-on'));
                    ?>
                </div>
            </div>
            <div id="wsl-developers" class="tabpage" style="display: none;">
                <iframe src="<?php echo(WSL_API_DASHBOARD); ?>" style="height: 750px; width: 100%;"></iframe>
            </div>
            <div id="wsl-buttons" class="tabpage" style="display: none;">
                <?php
                do_settings_sections('button_settings');
                ?>
            </div>
        </form>
    </div>
    <?php
}

add_action('admin_menu', 'wsl_admin_settings');
function wsl_admin_settings()
{
    $plugin_dir_url = plugin_dir_url(__FILE__);
    add_menu_page('wsl_login', __('WebshopLogin', 'webshoplogin-single-sign-on'), 'nosuchcapability', 'wsl_login', null, $plugin_dir_url . '/images/wsl-logo.png', 57);
    add_submenu_page('wsl_login', null, __('Instellingen', 'webshoplogin-single-sign-on'), 'manage_options', 'wsl-login', 'wsl_login_settings');

}

add_action('admin_enqueue_scripts', 'wsl_enqueue_scripts');
function wsl_enqueue_scripts($hook) {
    if( $hook !== 'webshoplogin_page_wsl-login' ) {
        return;
    }
    wp_register_style('wsl_settings_page_style', plugins_url('css/wsl-settings-page.css',__FILE__));
    wp_enqueue_style('wsl_settings_page_style');
    wp_register_script('wsl_settings_page_script', plugins_url('javascript/wsl-settings-page.js',__FILE__));
    wp_enqueue_script('wsl_settings_page_script');
}

function wsl_settings_init() {
    // register_settings will add it to the wp_options table.
    register_setting( 'wsl_login', 'wsl_login_api_key' );
    register_setting( 'wsl_login', 'wsl_login_api_secret' );
    register_setting( 'wsl_login', 'wsl_login_enable' );

    add_settings_section(
        'api_settings',
        __('API instellingen', 'webshoplogin-single-sign-on'),
        'wsl_api_settings_callback',
        'api_settings'
    );

    add_settings_field(
        'wsl_api_state',
        __('WebshopLogin API key & secret', 'webshoplogin-single-sign-on'),
        'wsl_api_state_field_callback',
        'api_settings',
        'api_settings',
        [
            'label_for' => 'wsl_login_api_state',
            'class' => 'wsl_api_state_field',
            'wsl_data_field' => 'wsl_login_state_key',
        ]
    );

    add_settings_field(
        'wsl_api_key',
        __('WebshopLogin API key', 'webshoplogin-single-sign-on'),
        'wsl_api_key_field_callback',
        'api_settings',
        'api_settings',
        [
            'label_for' => 'wsl_login_api_key',
            'class' => 'wsl_api_key_field',
            'wsl_data_field' => 'wsl_login_api_key',
        ]
    );

    add_settings_field(
        'wsl_api_secret',
        __('WebshopLogin API secret', 'webshoplogin-single-sign-on'),
        'wsl_api_secret_field_callback',
        'api_settings',
        'api_settings',
        [
            'label_for' => 'wsl_login_api_secret',
            'class' => 'wsl_api_key_field',
            'wsl_data_field' => 'wsl_login_api_secret',
        ]
    );

    add_settings_section(
        'plugin_settings',
        __('Plugin instellingen', 'webshoplogin-single-sign-on'),
        'wsl_plugin_settings_callback',
        'plugin_settings'
    );

    add_settings_field(
        'wsl_enable_key',
        __('WebshopLogin plugin aanzetten', 'webshoplogin-single-sign-on'),
        'wsl_enable_field_callback',
        'plugin_settings',
        'plugin_settings',
        [
            'label_for' => 'wsl_login_enable',
            'class' => 'wsl_enable_field',
            'wsl_data_field' => 'wsl_login_enable',
        ]
    );

    add_settings_section(
        'button_settings',
        __('Knop instellingen', 'webshoplogin-single-sign-on'),
        'wsl_button_settings_callback',
        'button_settings'
    );

    add_settings_field(
        'wsl_shortcode_login_key',
        __('WebshopLogin shortcode om een extra loginknop weer te geven', 'webshoplogin-single-sign-on'),
        'wsl_shortcode_login_field_callback',
        'button_settings',
        'button_settings',
        [
            'class' => 'wsl_shortcode_login_field',
        ]
    );

    add_settings_field(
        'wsl_shortcode_register_key',
        __('WebshopLogin shortcode om een extra registreer knop weer te geven', 'webshoplogin-single-sign-on'),
        'wsl_shortcode_register_field_callback',
        'button_settings',
        'button_settings',
        [
            'class' => 'wsl_shortcode_register_field',
        ]
    );

}
add_action('admin_init', 'wsl_settings_init');


/** All callback functions for the setting fields**/
function wsl_plugin_settings_callback($args) {

}

function wsl_enable_field_callback($args) {
    ?>
    <input type="checkbox" class="<?php echo($args['class'])?>" name="<?php echo($args['label_for'])?>"
        <?php
        echo(get_option($args['wsl_data_field']) == "on" ? 'checked' : '');
        ?>
    />
    <?php
}

function wsl_api_settings_callback($args) {
    ?>
    <p style="font-size: 16px;"><?php _e('Om de WebshopLogin plugin te activeren moeten de API key & secret worden ingevuld. Deze kunnen worden gevonden in de <a href="#" onclick="clickWslTab()">Webshoplogin tab</a> of op <a href="https://developers.webshoplogin.com/dashboard" target="_blank">https://developers.webshoplogin.com</a>', 'webshoplogin-single-sign-on') ?></p>
    <?php
}

function wsl_api_state_field_callback($args) {
    ?>
    <p size="75"
       style="font-size: 16px; font-weight: bold; color: <?php echo(get_option($args['wsl_data_field']) == 'invalid' ? 'red' : 'green'); ?>;"
       class="<?php echo($args['class'])?>"
       name="<?php echo($args['label_for'])?>">
        <?php echo(get_option($args['wsl_data_field']) == 'invalid' ? __('Ongeldig', 'webshoplogin-single-sign-on') : __('Geldig', 'webshoplogin-single-sign-on') )?>
    </p>
    <?php
}

function wsl_api_key_field_callback($args) {
    ?>
    <input type="textfield" size="75" value="<?php echo(get_option($args['wsl_data_field']))?>" class="<?php echo($args['class'])?>" name="<?php echo($args['label_for'])?>">
    <?php
}

function wsl_api_secret_field_callback($args) {
    ?>
    <input type="textfield" size="75" value="<?php echo(get_option($args['wsl_data_field']))?>" class="<?php echo($args['class'])?>" name="<?php echo($args['label_for'])?>">
    <?php
}

function wsl_button_settings_callback($args) {

}

function wsl_shortcode_login_field_callback($args) {
    ?>
    <input type="textfield" size="75" value="[wsl_login_button]" class="<?php echo($args['class'])?>">
    <p><?php _e('Kopieer de shortcode en plak deze op een pagina om de Webshoplogin inlogknop weer te geven.', 'webshoplogin-single-sign-on') ?></p>
    <?php
}

function wsl_shortcode_register_field_callback($args) {
    ?>
    <input type="textfield" size="75" value="[wsl_register_button]" class="<?php echo($args['class'])?>">
    <p><?php _e('Kopieer de shortcode en plak deze op een pagina om de Webshoplogin registreer knop weer te geven.', 'webshoplogin-single-sign-on') ?></p>
    <?php
}

