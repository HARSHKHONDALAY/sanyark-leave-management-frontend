<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLM_Auth
{
    public static function login_user($login_data)
    {
        if (
            empty($login_data['success']) ||
            empty($login_data['data']['token'])
        ) {
            return false;
        }

        $_SESSION['slm_auth'] = [
            'token'    => $login_data['data']['token'],
            'userId'   => $login_data['data']['userId'] ?? null,
            'fullName' => $login_data['data']['fullName'] ?? '',
            'email'    => $login_data['data']['email'] ?? '',
            'role'     => $login_data['data']['role'] ?? '',
        ];

        return true;
    }

    public static function logout_user()
    {
        unset($_SESSION['slm_auth']);
    }

    public static function is_logged_in()
    {
        return !empty($_SESSION['slm_auth']['token']);
    }

    public static function get_token()
    {
        return $_SESSION['slm_auth']['token'] ?? '';
    }

    public static function get_user()
    {
        return $_SESSION['slm_auth'] ?? null;
    }

    public static function get_role()
    {
        return $_SESSION['slm_auth']['role'] ?? '';
    }
}