<?php
if (!defined('ABSPATH')) exit;  // if direct access




// Limit Login
add_action('wp_login_failed', 'user_verification_login_failed');

function user_verification_login_failed($username)
{

    $user_verification_settings = get_option('user_verification_settings');
    $loginAttempt = isset($user_verification_settings['loginAttempt']) ? $user_verification_settings['loginAttempt'] : [];

    $enable = isset($loginAttempt['enable']) ? $loginAttempt['enable'] : 'no';
    $max_attempts = isset($loginAttempt['max_attempts']) ? (int)$loginAttempt['max_attempts'] : 3;
    $locked_minutes = isset($loginAttempt['locked_minutes']) ? (int)$loginAttempt['locked_minutes'] : 5;
    $locked_message = isset($loginAttempt['locked_message']) ? $loginAttempt['locked_message'] : "Please wait $locked_minutes minutes";
    $remaining_message = isset($loginAttempt['remaining_message']) ? $loginAttempt['remaining_message'] : "Login Failed, You have remaining: %s";
    $redirect_max_attempt = isset($loginAttempt['redirect_max_attempt']) ? $loginAttempt['redirect_max_attempt'] : "";


    if ($enable != 'yes') return;

    $UserVerificationStats = new UserVerificationStats();


    $redirect_url = get_permalink($redirect_max_attempt);



    $key = 'login_attempts_' . $username;
    $attempts = (int)get_transient($key);
    $max_attempts = 3;
    $remaining = $max_attempts - ($attempts + 1);

    if ($attempts >= $max_attempts) {

        $UserVerificationStats->add_stats('login_attempt_max');

        if (!empty($redirect_max_attempt)) {
            wp_redirect($redirect_url);
            exit;
        }

        wp_die($locked_message);
    }

    set_transient($key, $attempts + 1, $locked_minutes * 60);
    $UserVerificationStats->add_stats('login_attempt_failed');

    wp_die(sprintf($remaining_message, $remaining));
}

// Reset if login success
add_action('wp_login', function ($user_login) {
    delete_transient('login_attempts_' . $user_login);
}, 10, 1);
