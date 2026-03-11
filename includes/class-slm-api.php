<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLM_API
{
    public static function login($email, $password)
    {
        $url = SLM_API_BASE_URL . '/api/auth/login';

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode([
                'email'    => $email,
                'password' => $password,
            ]),
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Login failed.');
    }

    public static function create_leave($token, $payload)
    {
        $url = SLM_API_BASE_URL . '/api/leaves';

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to create leave request.');
    }

    public static function get_my_leaves($token)
    {
        $url = SLM_API_BASE_URL . '/api/leaves/my';

        $response = wp_remote_get($url, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to fetch leave history.');
    }

    public static function cancel_leave($token, $leave_id)
    {
        $url = SLM_API_BASE_URL . '/api/leaves/' . intval($leave_id) . '/cancel';

        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to cancel leave request.');
    }

    public static function get_all_leaves($token)
    {
        $url = SLM_API_BASE_URL . '/api/manager/leaves';

        $response = wp_remote_get($url, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to fetch all leave requests.');
    }

    public static function approve_leave($token, $leave_id, $payload)
    {
        $url = SLM_API_BASE_URL . '/api/manager/leaves/' . intval($leave_id) . '/approve';

        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to approve leave request.');
    }

    public static function reject_leave($token, $leave_id, $payload)
    {
        $url = SLM_API_BASE_URL . '/api/manager/leaves/' . intval($leave_id) . '/reject';

        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body'    => wp_json_encode($payload),
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to reject leave request.');
    }

    public static function get_employee_dashboard($token)
    {
        $url = SLM_API_BASE_URL . '/api/dashboard/employee';

        $response = wp_remote_get($url, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to fetch employee dashboard.');
    }

    public static function get_manager_dashboard($token)
    {
        $url = SLM_API_BASE_URL . '/api/dashboard/manager';

        $response = wp_remote_get($url, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout' => 20,
        ]);

        return self::handle_response($response, 'Failed to fetch manager dashboard.');
    }

    private static function handle_response($response, $default_message)
    {
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'data'    => null,
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);
        $decoded     = json_decode($body, true);

        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'Invalid response from backend.',
                'data'    => null,
            ];
        }

        if ($status_code >= 200 && $status_code < 300) {
            return $decoded;
        }

        return [
            'success' => false,
            'message' => $decoded['message'] ?? $default_message,
            'data'    => $decoded['data'] ?? null,
        ];
    }
}