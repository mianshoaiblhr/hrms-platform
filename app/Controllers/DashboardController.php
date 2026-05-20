<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class DashboardController extends Controller
{
    protected Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $user       = Auth::getInstance()->user();
        $userId     = (int)$user['id'];
        $employeeId = (int)($user['employee_id'] ?? 0);

        // Safe query helpers — never crash the dashboard on bad columns/tables
        $safeCount = function (string $sql, array $p = []): int {
            try { return (int) $this->db->fetchColumn($sql, $p); }
            catch (\Throwable $e) { return 0; }
        };
        $safeFetch = function (string $sql, array $p = []): array {
            try { return $this->db->fetchAll($sql, $p) ?: []; }
            catch (\Throwable $e) { return []; }
        };
        $safeFetchOne = function (string $sql, array $p = []): ?array {
            try { return $this->db->fetchOne($sql, $p) ?: null; }
            catch (\Throwable $e) { return null; }
        };

        // ── KPI stats ──────────────────────────────────────────────────────
        $stats = [
            'total_employees' => $safeCount("SELECT COUNT(*) FROM employees WHERE employment_status='active' AND deleted_at IS NULL"),
            'pending_leaves'  => $safeCount("SELECT COUNT(*) FROM leave_applications WHERE status='pending'"),
            'today_present'   => $safeCount("SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE() AND status='present' AND deleted_at IS NULL"),
            'today_absent'    => $safeCount("SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE() AND status='absent' AND deleted_at IS NULL"),
            'pending_tasks'   => $safeCount("SELECT COUNT(*) FROM tasks WHERE status='pending' AND deleted_at IS NULL"),
            'unread_notifs'   => $safeCount("SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL", [$userId]),
        ];

        // ── My attendance today ────────────────────────────────────────────
        $myAttendance = $employeeId
            ? $safeFetchOne("SELECT * FROM attendance WHERE employee_id=? AND attendance_date=CURDATE() AND deleted_at IS NULL", [$employeeId])
            : null;

        // ── Department headcount chart ─────────────────────────────────────
        $deptChart = $safeFetch(
            "SELECT d.name, COUNT(e.id) AS cnt
             FROM departments d
             LEFT JOIN employees e ON e.department_id=d.id AND e.status='active' AND e.deleted_at IS NULL
             WHERE d.deleted_at IS NULL
             GROUP BY d.id, d.name ORDER BY cnt DESC LIMIT 8"
        );

        // ── Attendance chart (last 7 days) ─────────────────────────────────
        $attendanceChart = $safeFetch(
            "SELECT attendance_date,
                    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN status='absent'  THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN status='late'    THEN 1 ELSE 0 END) AS late
             FROM attendance
             WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND deleted_at IS NULL
             GROUP BY attendance_date ORDER BY attendance_date"
        );

        // ── Recent leave applications ──────────────────────────────────────
        $recentLeaves = $safeFetch(
            "SELECT la.*, lt.name AS leave_type,
                    (CONCAT(e.first_name,' ',e.last_name)) AS employee_name
             FROM leave_applications la
             JOIN employees e  ON la.employee_id   = e.id
             JOIN leave_types lt ON la.leave_type_id = lt.id
             ORDER BY la.created_at DESC LIMIT 5"
        );

        // ── Recent employees ───────────────────────────────────────────────
        $recentEmployees = $safeFetch(
            "SELECT e.*, d.name AS dept_name, des.title AS designation
             FROM employees e
             LEFT JOIN departments  d   ON e.department_id  = d.id
             LEFT JOIN designations des ON e.designation_id = des.id
             WHERE e.deleted_at IS NULL
             ORDER BY e.created_at DESC LIMIT 5"
        );

        // ── Payroll summary (latest period) ───────────────────────────────
        // Use payroll_periods (not payroll_items) — correct table for totals
        $payrollSummary = $safeFetchOne(
            "SELECT COUNT(*) AS total_payrolls,
                    COALESCE(SUM(total_net), 0) AS total_net
             FROM payroll_periods
             WHERE status != 'draft'"
        ) ?? ['total_payrolls' => 0, 'total_net' => 0];

        // ── Upcoming birthdays ─────────────────────────────────────────────
        $birthdays = $safeFetch(
            "SELECT first_name, last_name, date_of_birth, avatar
             FROM employees
             WHERE status='active' AND deleted_at IS NULL
               AND strftime('%m-%d', date_of_birth) BETWEEN strftime('%m-%d', 'now')
                                                        AND strftime('%m-%d', date('now', '+7 days'))
             ORDER BY strftime('%m-%d', date_of_birth) LIMIT 5"
        );

        // ── My leave balances ──────────────────────────────────────────────
        $myLeaveBalance = $employeeId
            ? $safeFetch(
                "SELECT lb.*, lt.name AS leave_type
                 FROM leave_balances lb
                 JOIN leave_types lt ON lb.leave_type_id = lt.id
                 WHERE lb.employee_id = ? AND lb.year = strftime('%Y', 'now')",
                [$employeeId]
            )
            : [];

        // ── Pending approvals (managers / admin) ───────────────────────────
        $pendingApprovals = [];
        if (Auth::getInstance()->can('leaves.approve')) {
            $pendingApprovals = $safeFetch(
                "SELECT 'Leave' AS type, la.id,
                        (CONCAT(e.first_name,' ',e.last_name)) AS name,
                        la.from_date AS date, la.created_at
                 FROM leave_applications la
                 JOIN employees e ON la.employee_id = e.id
                 WHERE la.status = 'pending'
                 ORDER BY la.created_at DESC LIMIT 5"
            );
        }

        $this->view('dashboard/index', compact(
            'stats', 'deptChart', 'attendanceChart', 'recentLeaves',
            'recentEmployees', 'payrollSummary', 'birthdays',
            'myAttendance', 'myLeaveBalance', 'pendingApprovals'
        ));
    }

    // AJAX: Notifications
    public function notifications(): void
    {
        $uid  = (int) Auth::getInstance()->user()['id'];
        $data = [];
        try {
            $data = $this->db->fetchAll(
                "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20",
                [$uid]
            );
        } catch (\Throwable $e) {}
        $this->json(['notifications' => $data]);
    }

    // AJAX: Mark notification read
    public function markRead(): void
    {
        $uid = (int) Auth::getInstance()->user()['id'];
        $id  = (int) $this->input('id', 0);
        try {
            if ($id) {
                $this->db->query("UPDATE notifications SET read_at=NOW() WHERE id=? AND user_id=?", [$id, $uid]);
            } else {
                $this->db->query("UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL", [$uid]);
            }
        } catch (\Throwable $e) {}
        $this->json(['success' => true]);
    }

    // AJAX: Global search
    public function search(): void
    {
        $q       = trim($this->input('q', ''));
        $results = [];
        if (strlen($q) >= 2) {
            $like = '%' . $q . '%';
            try {
                $employees = $this->db->fetchAll(
                    "SELECT id, (CONCAT(first_name,' ',last_name)) AS name, employee_code AS code, 'Employee' AS type
                     FROM employees
                     WHERE (first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ?)
                       AND deleted_at IS NULL LIMIT 5",
                    [$like, $like, $like]
                );
                $results = array_merge($results, $employees);
            } catch (\Throwable $e) {}
        }
        $this->json(['results' => $results]);
    }
}
