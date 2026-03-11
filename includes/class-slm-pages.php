<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLM_Pages
{
    public function __construct()
    {
        add_action('init', [$this, 'handle_logout']);
        add_action('template_redirect', [$this, 'handle_page_access']);
    }

    public function handle_logout()
    {
        if (isset($_GET['slm_logout'])) {
            SLM_Auth::logout_user();
            wp_safe_redirect(site_url('/login'));
            exit;
        }
    }

    public function handle_page_access()
    {
        if (is_admin()) {
            return;
        }

        $login_page     = 'login';
        $employee_pages = ['employee-dashboard', 'apply-leave', 'my-leaves'];
        $manager_pages  = ['manager-dashboard', 'manage-leaves', 'team-calendar'];

        if (is_page($login_page) && SLM_Auth::is_logged_in()) {
            $role = SLM_Auth::get_role();

            if ($role === 'MANAGER') {
                wp_safe_redirect(site_url('/manager-dashboard'));
                exit;
            }

            wp_safe_redirect(site_url('/employee-dashboard'));
            exit;
        }

        if (is_page($employee_pages)) {
            if (!SLM_Auth::is_logged_in()) {
                wp_safe_redirect(site_url('/login'));
                exit;
            }

            if (SLM_Auth::get_role() !== 'EMPLOYEE') {
                wp_safe_redirect(site_url('/manager-dashboard'));
                exit;
            }
        }

        if (is_page($manager_pages)) {
            if (!SLM_Auth::is_logged_in()) {
                wp_safe_redirect(site_url('/login'));
                exit;
            }

            if (SLM_Auth::get_role() !== 'MANAGER') {
                wp_safe_redirect(site_url('/employee-dashboard'));
                exit;
            }
        }
    }
}