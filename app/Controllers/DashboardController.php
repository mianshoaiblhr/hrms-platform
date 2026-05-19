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
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $user = Auth::getInstance()->user();
        $userId = (int)$user['id'];
        $employeeId = (int)($user['employee_id'] ?? 0);

        // KPI Stats
        $stats = [
            'total_employees'  => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM employees WHERE status='active' AND deleted_at IS NULL"),
            'pending_leaves'   => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM leave_applications WHERE status='pending' AND deleted_at IS NULL"),
            'today_present'    => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE() AND status='present' AND deleted_at IS NULL"),
            'today_absent'     => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE() AND status='absent' AND deleted_at IS NULL"),
            'pending_tasks'    => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM tasks WHERE status='pending' AND deleted_at IS NULL"),
            'unread_notifs'    => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL", [$userId]),
        ];

        // My attendance today (for employee role)
        $myAttendance = null;
        if ($employeeId) {
            $myAttendance = $this->db->fetchOne(
                "SELECT * FROM attendance WHERE employee_id=? AND attendance_date=CURDATE() AND deleted_at IS NULL",
                [$employeeId]
            );
        }

        // Department distribution
        $deptChart = $this->db->fetchAll(
            "SELECT d.name, COUNT(e.id) AS cnt FROM departments d
             LEFT JOIN employees e ON e.department_id=d.id AND e.status='active' AND e.deleted_at IS NULL
             WHERE d.deleted_at IS NULL GROUP BY d.id ORDER BY cnt DESC LIMIT 8"
        );

        // Monthly attendance chart (last 7 days)
        $attendanceChart = $this->db->fetchAll(
            "SELECT attendance_date, 
                    SUM(status='present') AS present,
                    SUM(status='absent') AS absent,
                    SUM(status='late') AS late
             FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND deleted_at IS NULL
             GROUP BY attendance_date ORDER BY attendance_date"
        );

        // Recent leave applications
        $recentLeaves = $this->db->fetchAll(
            "SELECT la.*, lt.name AS leave_type, CONCAT(e.first_name,' ',e.last_name) AS employee_name
             FROM leave_applications la
             JOIN employees e ON la.employee_id=e.id
             JOIN leave_types lt ON la.leave_type_id=lt.id
             WHERE la.deleted_at IS NULL ORDER BY la.created_at DESC LIMIT 5"
        );

        // Recent employees
        $recentEmployees = $this->db->fetchAll(
            "SELECT e.*, d.name AS dept_name, des.title AS designation
             FROM employees e
             LEFT JOIN departments d ON e.department_id=d.id
             LEFT JOIN designations des ON e.designation_id=des.id
             WHERE e.deleted_at IS NULL ORDER BY e.created_at DESC LIMIT 5"
        );

        // Payroll summary (current month)
        $payrollSummary = $this->db->fetchOne(
            "SELECT COUNT(*) AS total_payrolls,
                    COALESCE(SUM(net_salary),0) AS total_net
             FROM payroll_items WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND deleted_at IS NULL"
        );

        // Upcoming birthdays (next 7 days)
        $birthdays = $this->db->fetchAll(
            "SELECT first_name, last_name, date_of_birth, avatar
             FROM employees WHERE status='active' AND deleted_at IS NULL
             AND DATE_FORMAT(date_of_birth,'%m-%d') BETWEEN DATE_FORMAT(CURDATE(),'%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL 7 DAY),'%m-%d')
             ORDER BY DATE_FORMAT(date_of_birth,'%m-%d') LIMIT 5"
        );

        // My leave balance
        $myLeaveBalance = [];
        if ($employeeId) {
            $myLeaveBalance = $this->db->fetchAll(
                "SELECT lb.*, lt.name AS leave_type, lt.color
                 FROM leave_balances lb JOIN leave_types lt ON lb.leave_type_id=lt.id
                 WHERE lb.employee_id=? AND lb.year=YEAR(NOW())",
                [$employeeId]
            );
        }

        // Pending approvals (for managers/admin)
        $pendingApprovals = [];
        if (Auth::getInstance()->can('leaves.approve')) {
            $pendingApprovals = $this->db->fetchAll(
                "SELECT 'Leave' AS type, la.id, CONCAT(e.first_name,' ',e.last_name) AS name,
                        la.from_date AS date, la.created_at
                 FROM leave_applications la JOIN employees e ON la.employee_id=e.id
                 WHERE la.status='pending' AND la.deleted_at IS NULL ORDER BY la.created_at DESC LIMIT 5"
            );
        }

        $this->view('dashboard/index', compact(
            'stats', 'deptChart', 'attendanceChart', 'recentLeaves',
            'recentEmployees', 'payrollSummary', 'birthdays',
            'myAttendance', 'myLeaveBalance', 'pendingApprovals'
        ));
    }

    // AJAX: Notifications panel
    public function notifications(): void
    {
        $user = Auth::getInstance()->user();
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20",
            [(int)$user['id']]
        );
        $this->json(['notifications' => $notifications]);
    }

    // AJAX: Mark notification read
    public function markRead(): void
    {
        $user = Auth::getInstance()->user();
        $id   = (int)($this->input('id', 0));
        if ($id) {
            $this->db->query(
                "UPDATE notifications SET read_at=NOW() WHERE id=? AND user_id=?",
                [$id, (int)$user['id']]
            );
        } else {
            $this->db->query("UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL", [(int)$user['id']]);
        }
        $this->json(['success' => true]);
    }

    // AJAX: Global search
    public function search(): void
    {
        $q = '%' . $this->input('q', '') . '%';
        $results = [];
        if (strlen(trim($this->input('q', ''))) >= 2) {
            $employees = $this->db->fetchAll(
                "SELECT id, CONCAT(first_name,' ',last_name) AS name, employee_code AS code, 'Employee' AS type
                 FROM employees WHERE (first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ?) AND deleted_at IS NULL LIMIT 5",
                [$q,$q,$q]
            );
            $results = array_merge($results, $employees);
        }
        $this->json(['results' => $results]);
    }
}
