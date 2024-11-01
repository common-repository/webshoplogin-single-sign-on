<?php

// This is a workaround for the activation hook which gets called 3 times instead of 1 after activation of a plug-in
add_action('admin_init', 'wsl_install_plugin_after_activation');
function wsl_install_plugin_after_activation() {
    if(get_option('wsl_plugin_activated')) {
        delete_option('wsl_plugin_activated');
        wsl_installation();
    }
}

//This code is executed when the install button is clicked
add_action('admin_post_wsl_install_button', 'wsl_install_button_click');
function wsl_install_button_click() {
    return wp_redirect(admin_url('admin.php?page=wsl-login&oneclick=true'));
    wp_die();
}

add_action('wsl_custom_update_notice', 'wsl_custom_update', 10, 2);
function wsl_custom_update($title, $message) {
    ?>
    <div class="updated notice">
        <h2><?php echo($title); ?></h2>
        <p><?php echo($message); ?></p>
    </div>
    <?php
}

add_action('wsl_custom_error_notice', 'wsl_custom_error', 10, 2);
function wsl_custom_error($title, $message) {
    ?>
    <div class="error notice">
        <h2><?php echo($title); ?></h2>
        <p><?php echo($message); ?></p>
    </div>
    <?php
}

function wsl_installation() {
    /* wsl_installation can be:
        - notinstalled
        - installed
        - nocredentials

        notinstalled means there is no Webshoplogin account for this webshop and no API credentials
        installed means there is a Webshoplogin account for this webshop and API credentials are filled in
        nocredentials means there is already a Webshoplogin account for this webshop and an e-mail has been send
    */

    if (get_option('wsl_installation') === FALSE) {
        add_option('wsl_installation', 'notinstalled');
    }

    if(get_option('wsl_installation') === 'installed') {
        return true;
    }

    if(get_option('wsl_installation') === 'notinstalled') {
        if(!wsl_woocommerce_install()) {
            return false;
        }
    }

    if(get_option('wsl_installation') === 'nocredentials') {
        if(!wsl_woocommerce_get_credentials()) {
            return false;
        }
        update_option('wsl_installation', 'installed');
        update_option('wsl_login_enable', 'on');
        delete_option('wsl_installation_token');
        if(get_option('wsl_webshoplogin_account_exists') !== 'true') {
            $admin_email = get_option('admin_email');
            do_action('wsl_custom_update_notice', __('De plugin is succesvol geactiveerd.', 'webshoplogin-single-sign-on'), sprintf(__('Er is een e-mail gestuurd naar uw e-mailadres( %s ) om uw nieuwe WebshopLogin account te activeren.',
                'webshoplogin-single-sign-on'), $admin_email));
        } else {
            delete_option('wsl_webshoplogin_account_exists');
            do_action('wsl_custom_update_notice', __('De plugin is succesvol geactiveerd.', 'webshoplogin-single-sign-on'), __('De activatie is succesvol verlopen.', 'webshoplogin-single-sign-on'));
        }
        return true;
    }

    return false;
}

function wsl_woocommerce_install() {
    $token = wsl_make_nonce();
    $admin_email = get_option('admin_email');

    if (update_option('wsl_installation_token', $token) === FALSE) {
        add_option('wsl_installation_token', $token);
    }

    $url = wsl_installation_url_builder($token, $admin_email);
    $api_response = wsl_api_call_get($url, false);

    if(!$api_response) {
        do_action('wsl_custom_error_notice', __('WebshopLogin installatie is mislukt.', 'webshoplogin-single-sign-on'), __('Er is iets fout gegaan tijdens de activatie van de plugin. Probeer op een later tijdstip de plugin te activeren via de plugin instellingen.', 'webshoplogin-single-sign-on'));
        return false;
    }

    if($api_response === "application already exists") {
        do_action('wsl_custom_error_notice', __('WebshopLogin installatie is mislukt.', 'webshoplogin-single-sign-on'), __('Er bestaat al een applicatie met deze URL op Webshoplogin.com, login op uw <a target="_blank" href="https://developers.webshoplogin.com/dashboard">WebshopLogin account</a> en vul uw API key & secret handmatig in op de instellingen pagina van de WebshopLogin plugin.', 'webshoplogin-single-sign-on'));
        return false;
    }

    update_option('wsl_installation', 'nocredentials');

    if($api_response === 'email already in use') {
        add_option('wsl_webshoplogin_account_exists', 'true');
        $admin_email = get_option('admin_email');
        do_action('wsl_custom_error_notice', __('WebshopLogin installatie is mislukt.', 'webshoplogin-single-sign-on'), sprintf(__('Er bestaat al een WebshopLogin account met uw e-mailadres( %s ), u krijgt een e-mail met verdere instructies.', 'webshoplogin-single-sign-on'), $admin_email));
        return false;
    }
    return true;
}

function wsl_woocommerce_get_credentials() {
    $token = get_option('wsl_installation_token');
    $url = wsl_credentials_url_builder($token);
    $api_response = wsl_api_call_get($url, false);

    if(!$api_response) {
        do_action('wsl_custom_error_notice', __('WebshopLogin installatie is mislukt.', 'webshoplogin-single-sign-on'), __('Er is iets fout gegaan tijdens de activatie van de plugin. Probeer op een later tijdstip de plugin te activeren via de plugin instellingen.', 'webshoplogin-single-sign-on'));
        return false;
    }

    wsl_save_credentials($api_response);
    if(!wsl_verify_api_keys()) {
        do_action('wsl_custom_error_notice', __('WebshopLogin installatie is mislukt.', 'webshoplogin-single-sign-on'), __('Er is iets fout gegaan tijdens de activatie van de plugin. Activeer de plugin via het instellingen scherm door handmatig de API key & secret in te vullen uit uw WebshopLogin account.', 'webshoplogin-single-sign-on'));
        return false;
    }
    return true;
}

function wsl_installation_url_builder($token, $admin_email) {
    $admin_email_hashed = base64_encode($admin_email);
    $token_hashed = base64_encode($token);
    return WSL_API_CONSTANT . '/woocommerce/install/' . $admin_email_hashed . '/' . $token_hashed;
}


function wsl_credentials_url_builder($token) {
    $token_hashed = base64_encode($token);
    return WSL_API_CONSTANT . '/woocommerce/getCredentials/' . $token_hashed;
}

function wsl_save_credentials($credentials) {
    if (update_option('wsl_login_api_key', $credentials['api_key']) === FALSE) {
        add_option('wsl_login_api_key', $credentials['api_key']);
    }
    if (update_option('wsl_login_api_secret', $credentials['api_secret']) === FALSE) {
        add_option('wsl_login_api_secret', $credentials['api_secret']);
    }
}


function wsl_verify_api_keys() {
    $url = WSL_API_CONSTANT . "/api/" . WSL_API_VERSION . "/keyValidation";

    $verified =  wsl_api_call_get($url);

    if($verified !== true) {
        if (update_option('wsl_login_state_key', 'invalid') === FALSE && get_option('wsl_login_state_key') === FALSE) {
            add_option('wsl_login_state_key', 'invalid');
        }

        update_option('wsl_login_enable', '');
        return false;
    }

    if (update_option('wsl_login_state_key', 'valid') === FALSE && get_option('wsl_login_state_key') === FALSE) {
        add_option('wsl_login_state_key', 'valid');
    }

    return true;
}