<?php

if (!defined('ABSPATH')) {
    exit;
}

class SLM_Shortcodes
{
    public function __construct()
    {
        add_shortcode('slm_login_form', [$this, 'render_login_form']);
        add_shortcode('slm_employee_dashboard', [$this, 'employee_dashboard']);
        add_shortcode('slm_apply_leave_form', [$this, 'apply_leave_form']);
        add_shortcode('slm_my_leaves', [$this, 'my_leaves']);
        add_shortcode('slm_manager_dashboard', [$this, 'manager_dashboard']);
        add_shortcode('slm_manage_leaves', [$this, 'manage_leaves']);
        add_shortcode('slm_team_calendar', [$this, 'team_calendar']);

        add_action('init', [$this, 'handle_login_submission']);
        add_action('init', [$this, 'handle_apply_leave_submission']);
        add_action('init', [$this, 'handle_cancel_leave_submission']);
        add_action('init', [$this, 'handle_approve_leave_submission']);
        add_action('init', [$this, 'handle_reject_leave_submission']);
    }

    /* =====================================================
       LOGIN
    ======================================================*/

    public function handle_login_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['slm_login_submit'])) {
            return;
        }

        if (
            !isset($_POST['slm_login_nonce']) ||
            !wp_verify_nonce($_POST['slm_login_nonce'], 'slm_login_action')
        ) {
            $_SESSION['slm_login_error'] = 'Security check failed.';
            return;
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');

        $result = SLM_API::login($email, $password);

        if (!empty($result['success']) && !empty($result['data']['token'])) {
            SLM_Auth::login_user($result);

            $role = $result['data']['role'] ?? '';

            if ($role === 'MANAGER') {
                wp_safe_redirect(site_url('/manager-dashboard'));
                exit;
            }

            wp_safe_redirect(site_url('/employee-dashboard'));
            exit;
        }

        $_SESSION['slm_login_error'] = $result['message'] ?? 'Login failed.';
    }

    public function render_login_form()
    {
        $error = $_SESSION['slm_login_error'] ?? '';
        unset($_SESSION['slm_login_error']);

        ob_start();
        ?>
        <div class="slm-container" style="max-width: 620px;">
            <div class="slm-card">
                <div class="slm-hero-copy">
                    <span class="slm-kicker">SANYARK SPACE</span>
                    <h2 class="slm-title">Mission Control Login</h2>
                    <p class="slm-subtitle">
                        Access the Leave Management System built for a space-first team. Sign in to manage requests, approvals, schedules, and operational continuity across Sanyark Space.
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="slm-alert slm-alert-error">
                        <?php echo esc_html($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field('slm_login_action', 'slm_login_nonce'); ?>

                    <div class="slm-form-group">
                        <label class="slm-label" for="slm_login_email">Work Email</label>
                        <input class="slm-input" id="slm_login_email" type="email" name="email" placeholder="Enter your Sanyark email" required>
                    </div>

                    <div class="slm-form-group">
                        <label class="slm-label" for="slm_login_password">Password</label>
                        <input class="slm-input" id="slm_login_password" type="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="slm-actions">
                        <button class="slm-button" type="submit" name="slm_login_submit">
                            Secure Login
                        </button>
                    </div>

                    <p class="slm-section-note">
                        Designed for a company building fused PNT and communications infrastructure from LEO satellites for secure and autonomous systems.
                    </p>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       EMPLOYEE DASHBOARD
    ======================================================*/

    public function employee_dashboard()
    {
        if (!SLM_Auth::is_logged_in()) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Please login.</div></div>';
        }

        $user = SLM_Auth::get_user();
        $token = SLM_Auth::get_token();

        $dashboard_result = SLM_API::get_employee_dashboard($token);
        $dashboard_error = '';

        if (empty($dashboard_result['success']) && empty($dashboard_result['data'])) {
            $dashboard_error = $dashboard_result['message'] ?? 'Failed to fetch employee dashboard.';
        }

        $data = $dashboard_result['data'] ?? [];

        $total = intval($data['totalLeaves'] ?? 0);
        $used = intval($data['usedLeaves'] ?? 0);
        $remaining = intval($data['remainingLeaves'] ?? 0);
        $pending = intval($data['pendingLeaves'] ?? 0);

        $upcoming_holidays = is_array($data['upcomingHolidays'] ?? null) ? $data['upcomingHolidays'] : [];
        $upcoming_leaves = is_array($data['upcomingApprovedLeaves'] ?? null) ? $data['upcomingApprovedLeaves'] : [];

        $holiday_preview = array_slice($upcoming_holidays, 0, 6);
        $leave_preview = array_slice($upcoming_leaves, 0, 6);

        ob_start();
        ?>
        <div class="slm-container">
            <div class="slm-card">
                <div class="slm-hero-copy">
                    <span class="slm-kicker">EMPLOYEE PORTAL</span>
                    <h2 class="slm-title">Leave Operations Dashboard</h2>
                    <p class="slm-subtitle">
                        Welcome, <?php echo esc_html($user['fullName'] ?? 'Team Member'); ?>. Review your current leave balance, keep track of upcoming approved time away, and stay aligned with company holidays and manager approvals.
                    </p>
                </div>

                <?php if ($dashboard_error): ?>
                    <div class="slm-alert slm-alert-error">
                        <?php echo esc_html($dashboard_error); ?>
                    </div>
                <?php endif; ?>

                <div class="slm-grid slm-grid-4">
                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Total Leaves</span>
                        <div class="slm-stat-value"><?php echo esc_html($total); ?></div>
                        <p class="slm-muted">Allocated leave balance for the current cycle.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Used Leaves</span>
                        <div class="slm-stat-value"><?php echo esc_html($used); ?></div>
                        <p class="slm-muted">Approved leave days already consumed.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Remaining Leaves</span>
                        <div class="slm-stat-value"><?php echo esc_html($remaining); ?></div>
                        <p class="slm-muted">Available leave days you can still request.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Pending Requests</span>
                        <div class="slm-stat-value"><?php echo esc_html($pending); ?></div>
                        <p class="slm-muted">Requests currently waiting for approval.</p>
                    </div>
                </div>

                <div class="slm-grid slm-grid-2 slm-dashboard-sections">
                    <div class="slm-card-sm slm-equal-card">
                        <h3>Upcoming Holidays</h3>

                        <?php if (empty($holiday_preview)): ?>
                            <div class="slm-empty">No upcoming holidays available right now.</div>
                        <?php else: ?>
                            <div class="slm-list-wrap">
                                <?php foreach ($holiday_preview as $holiday): ?>
                                    <div class="slm-list-item">
                                        <div class="slm-list-main">
                                            <strong><?php echo esc_html($holiday['holidayName'] ?? 'Holiday'); ?></strong>
                                            <span><?php echo esc_html($holiday['holidayDate'] ?? '-'); ?></span>
                                            <?php if (!empty($holiday['description'])): ?>
                                                <span><?php echo esc_html($holiday['description']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="slm-list-side">
                                            <?php echo esc_html($holiday['holidayType'] ?? 'PUBLIC'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="slm-card-sm slm-equal-card">
                        <h3>Upcoming Approved Leaves</h3>

                        <?php if (empty($leave_preview)): ?>
                            <div class="slm-empty">No upcoming approved leaves found.</div>
                        <?php else: ?>
                            <div class="slm-list-wrap">
                                <?php foreach ($leave_preview as $leave): ?>
                                    <div class="slm-list-item">
                                        <div class="slm-list-main">
                                            <strong><?php echo esc_html($this->format_leave_type($leave['leaveType'] ?? '')); ?></strong>
                                            <span>
                                                <?php echo esc_html($leave['startDate'] ?? '-'); ?> to <?php echo esc_html($leave['endDate'] ?? '-'); ?>
                                            </span>
                                            <?php if (!empty($leave['managerComment'])): ?>
                                                <span>Manager Note: <?php echo esc_html($leave['managerComment']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="slm-list-side">
                                            <span class="<?php echo esc_attr($this->get_status_badge_class($leave['status'] ?? 'APPROVED')); ?>">
                                                <?php echo esc_html($leave['status'] ?? 'APPROVED'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="slm-grid slm-grid-3 slm-dashboard-sections">
                    <div class="slm-card-sm slm-action-card">
                        <h3>Submit New Request</h3>
                        <p class="slm-muted">
                            Create a fresh leave request with leave type, dates, and reason so your manager can review it quickly.
                        </p>
                        <div class="slm-actions slm-actions-bottom">
                            <a class="slm-button" href="<?php echo esc_url(site_url('/apply-leave')); ?>">
                                Apply for Leave
                            </a>
                        </div>
                    </div>

                    <div class="slm-card-sm slm-action-card">
                        <h3>Track Leave Status</h3>
                        <p class="slm-muted">
                            Review your complete leave history, approval progress, status changes, and manager comments.
                        </p>
                        <div class="slm-actions slm-actions-bottom">
                            <a class="slm-button slm-button-secondary" href="<?php echo esc_url(site_url('/my-leaves')); ?>">
                                View My Leaves
                            </a>
                        </div>
                    </div>

                    <div class="slm-card-sm slm-action-card">
                        <h3>Secure Session</h3>
                        <p class="slm-muted">
                            End your session safely after reviewing or submitting your leave requests.
                        </p>
                        <div class="slm-actions slm-actions-bottom">
                            <a class="slm-button slm-button-danger" href="<?php echo esc_url(site_url('/?slm_logout=1')); ?>">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       APPLY LEAVE
    ======================================================*/

    public function handle_apply_leave_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['slm_apply_leave_submit'])) {
            return;
        }

        if (!SLM_Auth::is_logged_in()) {
            wp_safe_redirect(site_url('/login'));
            exit;
        }

        if (
            !isset($_POST['slm_apply_leave_nonce']) ||
            !wp_verify_nonce($_POST['slm_apply_leave_nonce'], 'slm_apply_leave_action')
        ) {
            $_SESSION['slm_apply_leave_error'] = 'Security check failed.';
            return;
        }

        $payload = [
            'leaveType' => sanitize_text_field($_POST['leaveType'] ?? ''),
            'startDate' => sanitize_text_field($_POST['startDate'] ?? ''),
            'endDate'   => sanitize_text_field($_POST['endDate'] ?? ''),
            'reason'    => sanitize_textarea_field($_POST['reason'] ?? ''),
        ];

        $_SESSION['slm_apply_leave_old'] = $payload;

        $token = SLM_Auth::get_token();
        $result = SLM_API::create_leave($token, $payload);

        if (!empty($result['success'])) {
            unset($_SESSION['slm_apply_leave_old']);
            $_SESSION['slm_apply_leave_success'] = $result['message'] ?? 'Leave request submitted successfully.';
            wp_safe_redirect(site_url('/apply-leave'));
            exit;
        }

        $_SESSION['slm_apply_leave_error'] = $result['message'] ?? 'Failed to create leave request.';
        wp_safe_redirect(site_url('/apply-leave'));
        exit;
    }

    public function apply_leave_form()
    {
        if (!SLM_Auth::is_logged_in()) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Please login.</div></div>';
        }

        $error = $_SESSION['slm_apply_leave_error'] ?? '';
        $success = $_SESSION['slm_apply_leave_success'] ?? '';
        $old = $_SESSION['slm_apply_leave_old'] ?? [];

        unset($_SESSION['slm_apply_leave_error']);
        unset($_SESSION['slm_apply_leave_success']);

        ob_start();
        ?>
        <div class="slm-container" style="max-width: 900px;">
            <div class="slm-card">
                <div class="slm-page-topbar">
                    <a class="slm-button slm-button-secondary slm-back-button" href="<?php echo esc_url(site_url('/employee-dashboard')); ?>">
                        ← Back to Dashboard
                    </a>
                </div>

                <div class="slm-hero-copy">
                    <span class="slm-kicker">LEAVE REQUEST</span>
                    <h2 class="slm-title">Submit a Leave Application</h2>
                    <p class="slm-subtitle">
                        Plan time away with visibility. Submit your leave details clearly so managers can maintain smooth team coordination and operational readiness.
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="slm-alert slm-alert-error">
                        <?php echo esc_html($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="slm-alert slm-alert-success">
                        <?php echo esc_html($success); ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field('slm_apply_leave_action', 'slm_apply_leave_nonce'); ?>

                    <div class="slm-form-group">
                        <label class="slm-label" for="slm_leave_type">Leave Type</label>
                        <select class="slm-select" id="slm_leave_type" name="leaveType" required>
                            <option value="">Select Leave Type</option>
                            <option value="CASUAL" <?php selected(($old['leaveType'] ?? ''), 'CASUAL'); ?>>Casual Leave</option>
                            <option value="SICK" <?php selected(($old['leaveType'] ?? ''), 'SICK'); ?>>Sick Leave</option>
                            <option value="PAID" <?php selected(($old['leaveType'] ?? ''), 'PAID'); ?>>Paid Leave</option>
                            <option value="MATERNITY" <?php selected(($old['leaveType'] ?? ''), 'MATERNITY'); ?>>Maternity Leave</option>
                        </select>
                    </div>

                    <div class="slm-grid slm-grid-2">
                        <div class="slm-form-group">
                            <label class="slm-label" for="slm_start_date">Start Date</label>
                            <input class="slm-input" id="slm_start_date" type="date" name="startDate" value="<?php echo esc_attr($old['startDate'] ?? ''); ?>" required>
                        </div>

                        <div class="slm-form-group">
                            <label class="slm-label" for="slm_end_date">End Date</label>
                            <input class="slm-input" id="slm_end_date" type="date" name="endDate" value="<?php echo esc_attr($old['endDate'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="slm-form-group">
                        <label class="slm-label" for="slm_reason">Reason for Leave</label>
                        <textarea class="slm-textarea" id="slm_reason" name="reason" rows="5" placeholder="Provide the reason and any context your manager should know." required><?php echo esc_textarea($old['reason'] ?? ''); ?></textarea>
                    </div>

                    <div class="slm-actions">
                        <button class="slm-button" type="submit" name="slm_apply_leave_submit">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       CANCEL LEAVE
    ======================================================*/

    public function handle_cancel_leave_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['slm_cancel_leave_submit'])) {
            return;
        }

        if (!SLM_Auth::is_logged_in()) {
            wp_safe_redirect(site_url('/login'));
            exit;
        }

        if (
            !isset($_POST['slm_cancel_leave_nonce']) ||
            !wp_verify_nonce($_POST['slm_cancel_leave_nonce'], 'slm_cancel_leave_action')
        ) {
            $_SESSION['slm_my_leaves_error'] = 'Security check failed.';
            wp_safe_redirect(site_url('/my-leaves'));
            exit;
        }

        $leave_id = intval($_POST['leave_id'] ?? 0);

        if ($leave_id <= 0) {
            $_SESSION['slm_my_leaves_error'] = 'Invalid leave request.';
            wp_safe_redirect(site_url('/my-leaves'));
            exit;
        }

        $token = SLM_Auth::get_token();
        $result = SLM_API::cancel_leave($token, $leave_id);

        if (!empty($result['success'])) {
            $_SESSION['slm_my_leaves_success'] = $result['message'] ?? 'Leave request cancelled successfully.';
            wp_safe_redirect(site_url('/my-leaves'));
            exit;
        }

        $_SESSION['slm_my_leaves_error'] = $result['message'] ?? 'Failed to cancel leave request.';
        wp_safe_redirect(site_url('/my-leaves'));
        exit;
    }

    /* =====================================================
       MY LEAVES
    ======================================================*/

    public function my_leaves()
    {
        if (!SLM_Auth::is_logged_in()) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Please login.</div></div>';
        }

        $token = SLM_Auth::get_token();
        $result = SLM_API::get_my_leaves($token);

        $error = $_SESSION['slm_my_leaves_error'] ?? '';
        $success = $_SESSION['slm_my_leaves_success'] ?? '';

        unset($_SESSION['slm_my_leaves_error']);
        unset($_SESSION['slm_my_leaves_success']);

        if (empty($result['success']) && empty($result['data'])) {
            $error = $error ?: ($result['message'] ?? 'Failed to fetch leave history.');
        }

        $leaves = is_array($result['data'] ?? null) ? $result['data'] : [];
        $status_filter = sanitize_text_field($_GET['status'] ?? '');

        if ($status_filter !== '') {
            $leaves = array_filter($leaves, function ($leave) use ($status_filter) {
                return strtoupper((string) ($leave['status'] ?? '')) === strtoupper($status_filter);
            });
        }

        ob_start();
        ?>
        <div class="slm-container">
            <div class="slm-card">
                <div class="slm-page-topbar">
                    <a class="slm-button slm-button-secondary slm-back-button" href="<?php echo esc_url(site_url('/employee-dashboard')); ?>">
                        ← Back to Dashboard
                    </a>
                </div>

                <div class="slm-hero-copy">
                    <span class="slm-kicker">REQUEST HISTORY</span>
                    <h2 class="slm-title">My Leave Timeline</h2>
                    <p class="slm-subtitle">
                        Review every request you have submitted, monitor live status updates, and cancel pending requests when plans change.
                    </p>
                </div>

                <div class="slm-filter-bar">
                    <form method="get" class="slm-filter-form">
                        <select class="slm-select" name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="PENDING" <?php selected($status_filter, 'PENDING'); ?>>Pending</option>
                            <option value="APPROVED" <?php selected($status_filter, 'APPROVED'); ?>>Approved</option>
                            <option value="REJECTED" <?php selected($status_filter, 'REJECTED'); ?>>Rejected</option>
                            <option value="CANCELLED" <?php selected($status_filter, 'CANCELLED'); ?>>Cancelled</option>
                        </select>
                    </form>
                </div>

                <?php if ($error): ?>
                    <div class="slm-alert slm-alert-error">
                        <?php echo esc_html($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="slm-alert slm-alert-success">
                        <?php echo esc_html($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($leaves)): ?>
                    <div class="slm-empty">No leave requests found yet. Start by creating your first leave request.</div>
                <?php else: ?>
                    <div class="slm-table-wrap">
                        <table class="slm-table">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Manager Comment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaves as $leave): ?>
                                    <tr>
                                        <td><?php echo esc_html($this->format_leave_type($leave['leaveType'] ?? '')); ?></td>
                                        <td><?php echo esc_html($leave['startDate'] ?? '-'); ?></td>
                                        <td><?php echo esc_html($leave['endDate'] ?? '-'); ?></td>
                                        <td><?php echo esc_html($leave['reason'] ?? '-'); ?></td>
                                        <td>
                                            <span class="<?php echo esc_attr($this->get_status_badge_class($leave['status'] ?? 'PENDING')); ?>">
                                                <?php echo esc_html($leave['status'] ?? 'PENDING'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($leave['managerComment'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (($leave['status'] ?? '') === 'PENDING'): ?>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to cancel this leave request?');">
                                                    <?php wp_nonce_field('slm_cancel_leave_action', 'slm_cancel_leave_nonce'); ?>
                                                    <input type="hidden" name="leave_id" value="<?php echo esc_attr($leave['id']); ?>">
                                                    <button class="slm-button slm-button-danger" type="submit" name="slm_cancel_leave_submit">
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="slm-muted">No action available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       MANAGER DASHBOARD
    ======================================================*/

    public function manager_dashboard()
    {
        if (!SLM_Auth::is_logged_in()) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Please login.</div></div>';
        }

        $user = SLM_Auth::get_user();
        $token = SLM_Auth::get_token();

        $dashboard_result = SLM_API::get_manager_dashboard($token);
        $dashboard_error = '';

        if (empty($dashboard_result['success']) && empty($dashboard_result['data'])) {
            $dashboard_error = $dashboard_result['message'] ?? 'Failed to fetch manager dashboard.';
        }

        $dashboard_data = $dashboard_result['data'] ?? [];

        $total_employees = intval($dashboard_data['totalEmployees'] ?? 0);
        $pending_approvals = intval($dashboard_data['pendingApprovals'] ?? 0);
        $currently_on_leave_count = intval($dashboard_data['employeesCurrentlyOnLeave'] ?? 0);
        $leaves_this_week = intval($dashboard_data['leavesThisWeek'] ?? 0);
        $leaves_this_month = intval($dashboard_data['leavesThisMonth'] ?? 0);

        $all_leaves_result = SLM_API::get_all_leaves($token);
        $all_leaves = is_array($all_leaves_result['data'] ?? null) ? $all_leaves_result['data'] : [];

        $today = current_time('Y-m-d');
        $upcoming_limit_date = date('Y-m-d', strtotime($today . ' +14 days'));

        $currently_on_leave = [];
        $upcoming_approved = [];
        $pending_requests = [];

        foreach ($all_leaves as $leave) {
            $status = strtoupper((string) ($leave['status'] ?? ''));
            $start_date = $leave['startDate'] ?? '';
            $end_date = $leave['endDate'] ?? '';

            if ($status === 'PENDING') {
                $pending_requests[] = $leave;
            }

            if (
                $status === 'APPROVED' &&
                !empty($start_date) &&
                !empty($end_date) &&
                $start_date <= $today &&
                $end_date >= $today
            ) {
                $currently_on_leave[] = $leave;
            }

            if (
                $status === 'APPROVED' &&
                !empty($start_date) &&
                $start_date > $today &&
                $start_date <= $upcoming_limit_date
            ) {
                $upcoming_approved[] = $leave;
            }
        }

        usort($currently_on_leave, function ($a, $b) {
            return strcmp((string) ($a['startDate'] ?? ''), (string) ($b['startDate'] ?? ''));
        });

        usort($upcoming_approved, function ($a, $b) {
            return strcmp((string) ($a['startDate'] ?? ''), (string) ($b['startDate'] ?? ''));
        });

        usort($pending_requests, function ($a, $b) {
            return strcmp((string) ($a['startDate'] ?? ''), (string) ($b['startDate'] ?? ''));
        });

        $pending_preview = array_slice($pending_requests, 0, 5);
        $upcoming_preview = array_slice($upcoming_approved, 0, 6);
        $current_preview = array_slice($currently_on_leave, 0, 6);

        ob_start();
        ?>
        <div class="slm-container">
            <div class="slm-card">
                <div class="slm-hero-copy">
                    <span class="slm-kicker">MANAGER CONSOLE</span>
                    <h2 class="slm-title">Leave Command Dashboard</h2>
                    <p class="slm-subtitle">
                        Welcome, <?php echo esc_html($user['fullName'] ?? 'Manager'); ?>. Review requests, monitor live team availability, and keep operations stable with a clear view of who is away now and who will be away soon.
                    </p>
                </div>

                <?php if ($dashboard_error): ?>
                    <div class="slm-alert slm-alert-error">
                        <?php echo esc_html($dashboard_error); ?>
                    </div>
                <?php endif; ?>

                <div class="slm-grid slm-grid-5">
                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Total Employees</span>
                        <div class="slm-stat-value"><?php echo esc_html($total_employees); ?></div>
                        <p class="slm-muted">Employees available in the organization.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Pending Approvals</span>
                        <div class="slm-stat-value"><?php echo esc_html($pending_approvals); ?></div>
                        <p class="slm-muted">Requests still waiting for a manager decision.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Currently On Leave</span>
                        <div class="slm-stat-value"><?php echo esc_html($currently_on_leave_count); ?></div>
                        <p class="slm-muted">Employees unavailable today.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Leaves This Week</span>
                        <div class="slm-stat-value"><?php echo esc_html($leaves_this_week); ?></div>
                        <p class="slm-muted">Approved or scheduled during the current week.</p>
                    </div>

                    <div class="slm-card-sm slm-stat-card">
                        <span class="slm-stat-label">Leaves This Month</span>
                        <div class="slm-stat-value"><?php echo esc_html($leaves_this_month); ?></div>
                        <p class="slm-muted">Approved or scheduled during the current month.</p>
                    </div>
                </div>

                <div class="slm-grid slm-grid-3 slm-dashboard-sections">
                    <div class="slm-card-sm slm-equal-card">
                        <h3>On Leave Today</h3>

                        <?php if (empty($current_preview)): ?>
                            <div class="slm-empty">No employees are on leave today.</div>
                        <?php else: ?>
                            <div class="slm-list-wrap">
                                <?php foreach ($current_preview as $leave): ?>
                                    <div class="slm-list-item">
                                        <div class="slm-list-main">
                                            <strong><?php echo esc_html($leave['employeeName'] ?? '-'); ?></strong>
                                            <span>
                                                <?php echo esc_html($leave['employeeCode'] ?? '-'); ?> ·
                                                <?php echo esc_html($this->format_leave_type($leave['leaveType'] ?? '')); ?>
                                            </span>
                                            <span>
                                                <?php echo esc_html($leave['startDate'] ?? '-'); ?> to <?php echo esc_html($leave['endDate'] ?? '-'); ?>
                                            </span>
                                        </div>
                                        <div class="slm-list-side">
                                            Today
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="slm-card-sm slm-equal-card">
                        <h3>Upcoming Approved Leaves</h3>

                        <?php if (empty($upcoming_preview)): ?>
                            <div class="slm-empty">No approved upcoming leaves in the next 14 days.</div>
                        <?php else: ?>
                            <div class="slm-list-wrap">
                                <?php foreach ($upcoming_preview as $leave): ?>
                                    <div class="slm-list-item">
                                        <div class="slm-list-main">
                                            <strong><?php echo esc_html($leave['employeeName'] ?? '-'); ?></strong>
                                            <span>
                                                <?php echo esc_html($leave['employeeCode'] ?? '-'); ?> ·
                                                <?php echo esc_html($this->format_leave_type($leave['leaveType'] ?? '')); ?>
                                            </span>
                                            <span>
                                                <?php echo esc_html($leave['startDate'] ?? '-'); ?> to <?php echo esc_html($leave['endDate'] ?? '-'); ?>
                                            </span>
                                        </div>
                                        <div class="slm-list-side">
                                            Approved
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="slm-card-sm slm-equal-card">
                        <h3>Pending Request Snapshot</h3>

                        <?php if (empty($pending_preview)): ?>
                            <div class="slm-empty">No pending leave requests right now.</div>
                        <?php else: ?>
                            <div class="slm-list-wrap">
                                <?php foreach ($pending_preview as $leave): ?>
                                    <div class="slm-list-item">
                                        <div class="slm-list-main">
                                            <strong><?php echo esc_html($leave['employeeName'] ?? '-'); ?></strong>
                                            <span>
                                                <?php echo esc_html($leave['employeeCode'] ?? '-'); ?> ·
                                                <?php echo esc_html($this->format_leave_type($leave['leaveType'] ?? '')); ?>
                                            </span>
                                            <span>
                                                <?php echo esc_html($leave['startDate'] ?? '-'); ?> to <?php echo esc_html($leave['endDate'] ?? '-'); ?>
                                            </span>
                                        </div>
                                        <div class="slm-list-side">
                                            Pending
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="slm-grid slm-grid-3 slm-dashboard-sections">
                    <div class="slm-card-sm slm-action-card">
                        <h3>Review Active Requests</h3>
                        <p class="slm-muted">
                            Open the full request queue, read employee context, and approve or reject pending requests with manager comments.
                        </p>
                        <div class="slm-actions slm-actions-bottom">
                            <a class="slm-button" href="<?php echo esc_url(site_url('/manage-leaves')); ?>">
                                Manage Leave Requests
                            </a>
                        </div>
                    </div>

                    <div class="slm-card-sm slm-action-card">
                        <h3>Team Leave Calendar</h3>
                        <p class="slm-muted">
                            View the full monthly leave calendar to understand team availability day by day across approved and pending requests.
                        </p>
                        <div class="slm-actions slm-actions-bottom">
                            <a class="slm-button slm-button-secondary" href="<?php echo esc_url(site_url('/team-calendar')); ?>">
                                Open Team Calendar
                            </a>
                        </div>
                    </div>

                    <div class="slm-card-sm slm-action-card">
                        <h3>Secure Session</h3>
                        <p class="slm-muted">
                            Close your current session securely when managerial review is complete.
                        </p>
                        <div class="slm-actions slm-actions-bottom">
                            <a class="slm-button slm-button-danger" href="<?php echo esc_url(site_url('/?slm_logout=1')); ?>">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       APPROVE / REJECT
    ======================================================*/

    public function handle_approve_leave_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['slm_approve_leave_submit'])) {
            return;
        }

        if (!SLM_Auth::is_logged_in()) {
            wp_safe_redirect(site_url('/login'));
            exit;
        }

        if (
            !isset($_POST['slm_manager_action_nonce']) ||
            !wp_verify_nonce($_POST['slm_manager_action_nonce'], 'slm_manager_action')
        ) {
            $_SESSION['slm_manage_leaves_error'] = 'Security check failed.';
            wp_safe_redirect(site_url('/manage-leaves'));
            exit;
        }

        $leave_id = intval($_POST['leave_id'] ?? 0);
        $comment = sanitize_textarea_field($_POST['manager_comment'] ?? '');

        if ($leave_id <= 0) {
            $_SESSION['slm_manage_leaves_error'] = 'Invalid leave request.';
            wp_safe_redirect(site_url('/manage-leaves'));
            exit;
        }

        $token = SLM_Auth::get_token();
        $result = SLM_API::approve_leave($token, $leave_id, [
            'comment' => $comment,
        ]);

        if (!empty($result['success'])) {
            $_SESSION['slm_manage_leaves_success'] = $result['message'] ?? 'Leave request approved successfully.';
            wp_safe_redirect(site_url('/manage-leaves'));
            exit;
        }

        $_SESSION['slm_manage_leaves_error'] = $result['message'] ?? 'Failed to approve leave request.';
        wp_safe_redirect(site_url('/manage-leaves'));
        exit;
    }

    public function handle_reject_leave_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['slm_reject_leave_submit'])) {
            return;
        }

        if (!SLM_Auth::is_logged_in()) {
            wp_safe_redirect(site_url('/login'));
            exit;
        }

        if (
            !isset($_POST['slm_manager_action_nonce']) ||
            !wp_verify_nonce($_POST['slm_manager_action_nonce'], 'slm_manager_action')
        ) {
            $_SESSION['slm_manage_leaves_error'] = 'Security check failed.';
            wp_safe_redirect(site_url('/manage-leaves'));
            exit;
        }

        $leave_id = intval($_POST['leave_id'] ?? 0);
        $comment = sanitize_textarea_field($_POST['manager_comment'] ?? '');

        if ($leave_id <= 0) {
            $_SESSION['slm_manage_leaves_error'] = 'Invalid leave request.';
            wp_safe_redirect(site_url('/manage-leaves'));
            exit;
        }

        $token = SLM_Auth::get_token();
        $result = SLM_API::reject_leave($token, $leave_id, [
            'comment' => $comment,
        ]);

        if (!empty($result['success'])) {
            $_SESSION['slm_manage_leaves_success'] = $result['message'] ?? 'Leave request rejected successfully.';
            wp_safe_redirect(site_url('/manage-leaves'));
            exit;
        }

        $_SESSION['slm_manage_leaves_error'] = $result['message'] ?? 'Failed to reject leave request.';
        wp_safe_redirect(site_url('/manage-leaves'));
        exit;
    }

    /* =====================================================
       MANAGE LEAVES
    ======================================================*/

    public function manage_leaves()
    {
        if (!SLM_Auth::is_logged_in()) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Please login.</div></div>';
        }

        $token = SLM_Auth::get_token();
        $result = SLM_API::get_all_leaves($token);

        $error = $_SESSION['slm_manage_leaves_error'] ?? '';
        $success = $_SESSION['slm_manage_leaves_success'] ?? '';

        unset($_SESSION['slm_manage_leaves_error']);
        unset($_SESSION['slm_manage_leaves_success']);

        if (empty($result['success']) && empty($result['data'])) {
            $error = $error ?: ($result['message'] ?? 'Failed to fetch leave requests.');
        }

        $leaves = is_array($result['data'] ?? null) ? $result['data'] : [];

        usort($leaves, function ($a, $b) {
            if (($a['status'] ?? '') === 'PENDING' && ($b['status'] ?? '') !== 'PENDING') {
                return -1;
            }

            if (($b['status'] ?? '') === 'PENDING' && ($a['status'] ?? '') !== 'PENDING') {
                return 1;
            }

            return strcmp((string) ($b['startDate'] ?? ''), (string) ($a['startDate'] ?? ''));
        });

        ob_start();
        ?>
        <div class="slm-container">
            <div class="slm-card">
                <div class="slm-page-topbar">
                    <a class="slm-button slm-button-secondary slm-back-button" href="<?php echo esc_url(site_url('/manager-dashboard')); ?>">
                        ← Back to Dashboard
                    </a>
                </div>

                <div class="slm-hero-copy">
                    <span class="slm-kicker">APPROVAL WORKFLOW</span>
                    <h2 class="slm-title">Manage Leave Requests</h2>
                    <p class="slm-subtitle">
                        Review team availability with full context. Prioritize pending requests, capture manager comments, and keep internal operations predictable and well coordinated.
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="slm-alert slm-alert-error">
                        <?php echo esc_html($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="slm-alert slm-alert-success">
                        <?php echo esc_html($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($leaves)): ?>
                    <div class="slm-empty">No leave requests found right now.</div>
                <?php else: ?>
                    <div class="slm-filter-bar">
                        <input class="slm-input" type="text" id="slm-search" placeholder="Search employee name...">
                    </div>

                    <div class="slm-grid">
                        <?php foreach ($leaves as $leave): ?>
                            <div class="slm-card-sm">
                                <div class="slm-meta-grid">
                                    <div class="slm-meta-item">
                                        <strong>Employee</strong>
                                        <?php echo esc_html($leave['employeeName'] ?? '-'); ?>
                                    </div>

                                    <div class="slm-meta-item">
                                        <strong>Employee Code</strong>
                                        <?php echo esc_html($leave['employeeCode'] ?? '-'); ?>
                                    </div>

                                    <div class="slm-meta-item">
                                        <strong>Leave Type</strong>
                                        <?php echo esc_html($this->format_leave_type($leave['leaveType'] ?? '')); ?>
                                    </div>

                                    <div class="slm-meta-item">
                                        <strong>Start Date</strong>
                                        <?php echo esc_html($leave['startDate'] ?? '-'); ?>
                                    </div>

                                    <div class="slm-meta-item">
                                        <strong>End Date</strong>
                                        <?php echo esc_html($leave['endDate'] ?? '-'); ?>
                                    </div>

                                    <div class="slm-meta-item">
                                        <strong>Status</strong>
                                        <span class="<?php echo esc_attr($this->get_status_badge_class($leave['status'] ?? 'PENDING')); ?>">
                                            <?php echo esc_html($leave['status'] ?? 'PENDING'); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="slm-form-group">
                                    <label class="slm-label">Employee Reason</label>
                                    <div class="slm-muted">
                                        <?php echo nl2br(esc_html($leave['reason'] ?? '-')); ?>
                                    </div>
                                </div>

                                <div class="slm-form-group">
                                    <label class="slm-label">Current Manager Comment</label>
                                    <div class="slm-muted">
                                        <?php echo esc_html($leave['managerComment'] ?? '-'); ?>
                                    </div>
                                </div>

                                <?php if (($leave['status'] ?? '') === 'PENDING'): ?>
                                    <form method="post">
                                        <?php wp_nonce_field('slm_manager_action', 'slm_manager_action_nonce'); ?>
                                        <input type="hidden" name="leave_id" value="<?php echo esc_attr($leave['id']); ?>">

                                        <div class="slm-form-group">
                                            <label class="slm-label" for="slm_comment_<?php echo esc_attr($leave['id']); ?>">
                                                Manager Comment
                                            </label>
                                            <textarea
                                                class="slm-textarea"
                                                id="slm_comment_<?php echo esc_attr($leave['id']); ?>"
                                                name="manager_comment"
                                                rows="4"
                                                placeholder="Add optional context for approval or rejection."
                                            ></textarea>
                                        </div>

                                        <div class="slm-manager-actions">
                                            <button
                                                class="slm-button slm-button-success"
                                                type="submit"
                                                name="slm_approve_leave_submit"
                                                onclick="return confirm('Approve this leave request?');"
                                            >
                                                Approve Request
                                            </button>

                                            <button
                                                class="slm-button slm-button-danger"
                                                type="submit"
                                                name="slm_reject_leave_submit"
                                                onclick="return confirm('Reject this leave request?');"
                                            >
                                                Reject Request
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="slm-empty">
                                        This request has already been processed.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =====================================================
       TEAM CALENDAR
    ======================================================*/

    public function team_calendar()
    {
        if (!SLM_Auth::is_logged_in()) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Please login.</div></div>';
        }

        $user = SLM_Auth::get_user();
        $role = strtoupper((string) ($user['role'] ?? ''));

        if ($role !== 'MANAGER') {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">Access denied.</div></div>';
        }

        $token = SLM_Auth::get_token();
        $result = SLM_API::get_all_leaves($token);

        if (empty($result['success']) && empty($result['data'])) {
            return '<div class="slm-container"><div class="slm-alert slm-alert-error">' . esc_html($result['message'] ?? 'Failed to fetch calendar data.') . '</div></div>';
        }

        $leaves = is_array($result['data'] ?? null) ? $result['data'] : [];

        $month = intval($_GET['month'] ?? current_time('n'));
        $year = intval($_GET['year'] ?? current_time('Y'));

        if ($month < 1 || $month > 12) {
            $month = intval(current_time('n'));
        }

        if ($year < 2000 || $year > 2100) {
            $year = intval(current_time('Y'));
        }

        $first_day_timestamp = strtotime(sprintf('%04d-%02d-01', $year, $month));
        if (!$first_day_timestamp) {
            $first_day_timestamp = strtotime(date('Y-m-01'));
        }

        $month_name = date('F Y', $first_day_timestamp);
        $first_weekday = intval(date('N', $first_day_timestamp));
        $grid_start_timestamp = strtotime('-' . ($first_weekday - 1) . ' days', $first_day_timestamp);

        $prev_month_timestamp = strtotime('-1 month', $first_day_timestamp);
        $next_month_timestamp = strtotime('+1 month', $first_day_timestamp);

        $events_by_day = [];

        foreach ($leaves as $leave) {
            $start_date = $leave['startDate'] ?? '';
            $end_date = $leave['endDate'] ?? '';

            if (empty($start_date) || empty($end_date)) {
                continue;
            }

            $start_ts = strtotime($start_date);
            $end_ts = strtotime($end_date);

            if (!$start_ts || !$end_ts || $end_ts < $start_ts) {
                continue;
            }

            for ($current_ts = $start_ts; $current_ts <= $end_ts; $current_ts = strtotime('+1 day', $current_ts)) {
                $day_key = date('Y-m-d', $current_ts);

                if (!isset($events_by_day[$day_key])) {
                    $events_by_day[$day_key] = [];
                }

                $events_by_day[$day_key][] = [
                    'employeeName' => $leave['employeeName'] ?? '-',
                    'employeeCode' => $leave['employeeCode'] ?? '-',
                    'leaveType'    => $leave['leaveType'] ?? '',
                    'status'       => $leave['status'] ?? 'PENDING',
                    'startDate'    => $leave['startDate'] ?? '',
                    'endDate'      => $leave['endDate'] ?? '',
                ];
            }
        }

        $week_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $today = current_time('Y-m-d');

        ob_start();
        ?>
        <div class="slm-container">
            <div class="slm-card">
                <div class="slm-page-topbar">
                    <a class="slm-button slm-button-secondary slm-back-button" href="<?php echo esc_url(site_url('/manager-dashboard')); ?>">
                        ← Back to Dashboard
                    </a>
                </div>

                <div class="slm-hero-copy">
                    <span class="slm-kicker">TEAM AVAILABILITY</span>
                    <h2 class="slm-title">Team Leave Calendar</h2>
                    <p class="slm-subtitle">
                        View the full month at a glance, monitor approved and pending leave events, and keep team scheduling aligned with operational priorities.
                    </p>
                </div>

                <div class="slm-calendar-toolbar">
                    <a
                        class="slm-button slm-button-secondary"
                        href="<?php echo esc_url(add_query_arg([
                            'month' => date('n', $prev_month_timestamp),
                            'year'  => date('Y', $prev_month_timestamp),
                        ], site_url('/team-calendar'))); ?>"
                    >
                        ← Previous
                    </a>

                    <div class="slm-calendar-title"><?php echo esc_html($month_name); ?></div>

                    <div class="slm-calendar-toolbar-actions">
                        <a
                            class="slm-button slm-button-secondary"
                            href="<?php echo esc_url(site_url('/team-calendar')); ?>"
                        >
                            Today
                        </a>

                        <a
                            class="slm-button slm-button-secondary"
                            href="<?php echo esc_url(add_query_arg([
                                'month' => date('n', $next_month_timestamp),
                                'year'  => date('Y', $next_month_timestamp),
                            ], site_url('/team-calendar'))); ?>"
                        >
                            Next →
                        </a>
                    </div>
                </div>

                <div class="slm-calendar-grid slm-calendar-weekdays">
                    <?php foreach ($week_days as $week_day): ?>
                        <div class="slm-calendar-weekday"><?php echo esc_html($week_day); ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="slm-calendar-grid slm-calendar-days">
                    <?php for ($cell = 0; $cell < 42; $cell++): ?>
                        <?php
                        $cell_timestamp = strtotime('+' . $cell . ' days', $grid_start_timestamp);
                        $cell_date = date('Y-m-d', $cell_timestamp);
                        $cell_day = date('j', $cell_timestamp);
                        $is_current_month = intval(date('n', $cell_timestamp)) === $month;
                        $is_today = $cell_date === $today;
                        $day_events = $events_by_day[$cell_date] ?? [];
                        $visible_events = array_slice($day_events, 0, 2);
                        $extra_count = count($day_events) - count($visible_events);

                        $cell_classes = ['slm-calendar-day'];
                        if (!$is_current_month) {
                            $cell_classes[] = 'slm-calendar-day-outside';
                        }
                        if ($is_today) {
                            $cell_classes[] = 'slm-calendar-day-today';
                        }
                        ?>
                        <div class="<?php echo esc_attr(implode(' ', $cell_classes)); ?>">
                            <div class="slm-calendar-day-number"><?php echo esc_html($cell_day); ?></div>

                            <div class="slm-calendar-events">
                                <?php foreach ($visible_events as $event): ?>
                                    <div class="<?php echo esc_attr($this->get_calendar_event_class($event['status'] ?? 'PENDING')); ?>">
                                        <strong><?php echo esc_html($event['employeeName'] ?? '-'); ?></strong>
                                        <span><?php echo esc_html($this->format_leave_type($event['leaveType'] ?? '')); ?></span>
                                        <span><?php echo esc_html(strtoupper((string) ($event['status'] ?? 'PENDING'))); ?></span>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($extra_count > 0): ?>
                                    <div class="slm-calendar-more">+<?php echo esc_html($extra_count); ?> more</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="slm-calendar-legend">
                    <span class="slm-calendar-legend-item">
                        <span class="slm-calendar-legend-dot slm-calendar-legend-approved"></span>
                        Approved
                    </span>
                    <span class="slm-calendar-legend-item">
                        <span class="slm-calendar-legend-dot slm-calendar-legend-pending"></span>
                        Pending
                    </span>
                    <span class="slm-calendar-legend-item">
                        <span class="slm-calendar-legend-dot slm-calendar-legend-rejected"></span>
                        Rejected
                    </span>
                    <span class="slm-calendar-legend-item">
                        <span class="slm-calendar-legend-dot slm-calendar-legend-cancelled"></span>
                        Cancelled
                    </span>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /* =====================================================
       HELPERS
    ======================================================*/

    private function get_calendar_event_class($status)
    {
        switch (strtoupper((string) $status)) {
            case 'APPROVED':
                return 'slm-calendar-pill-approved';

            case 'REJECTED':
                return 'slm-calendar-pill-rejected';

            case 'CANCELLED':
                return 'slm-calendar-pill-cancelled';

            default:
                return 'slm-calendar-pill-pending';
        }
    }

    private function get_status_badge_class($status)
    {
        switch (strtoupper((string) $status)) {
            case 'APPROVED':
                return 'slm-badge slm-badge-approved';

            case 'REJECTED':
                return 'slm-badge slm-badge-rejected';

            case 'CANCELLED':
                return 'slm-badge slm-badge-cancelled';

            default:
                return 'slm-badge slm-badge-pending';
        }
    }

    private function format_leave_type($type)
    {
        switch (strtoupper((string) $type)) {
            case 'CASUAL':
                return 'Casual Leave';
            case 'SICK':
                return 'Sick Leave';
            case 'PAID':
                return 'Paid Leave';
            case 'MATERNITY':
                return 'Maternity Leave';

            default:
                return (string) $type;
        }
    }
}