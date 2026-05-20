<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

// ============================================================
// NOTIFICATION CONTROLLER
// ============================================================
class NotificationController extends Controller
{
    public function unread(): void
    {
        $db = Database::getInstance();
        $userId = Auth::user()['id'];
        $notifs = $db->fetchAll(
            "SELECT n.*, u.name as sender_name
             FROM notifications n
             LEFT JOIN users u ON n.sender_id = u.id
             WHERE n.user_id = ? AND n.read_at IS NULL
             ORDER BY n.created_at DESC LIMIT 15",
            [$userId]
        );
        $count = $db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
        // Compute time_ago
        foreach ($notifs as &$n) {
            $n['time_ago'] = timeSince($n['created_at']);
        }
        $this->json(['notifications' => $notifs, 'count' => (int)$count]);
    }

    public function markRead(): void
    {
        $this->verifyCsrf();
        $db  = Database::getInstance();
        $id  = $this->input('id');
        $uid = Auth::user()['id'];
        $db->query(
            "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?",
            [$id, $uid]
        );
        $this->json(['success' => true]);
    }

    public function markAllRead(): void
    {
        $this->verifyCsrf();
        $db = Database::getInstance();
        $db->query(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
            [Auth::user()['id']]
        );
        $this->json(['success' => true]);
    }

    public function index(): void
    {
        $this->requirePermission('notifications.view');
        $db     = Database::getInstance();
        $userId = Auth::user()['id'];
        $page   = (int)($_GET['page'] ?? 1);
        $data   = $db->paginate(
            "SELECT n.*, u.name AS sender_name
             FROM notifications n
             LEFT JOIN users u ON n.sender_id = u.id
             WHERE n.user_id = ?
             ORDER BY n.created_at DESC",
            [$userId], $page, 20
        );
        $this->view('notifications/index', compact('data'));
    }
    public function count(): void {
        $uid = (int)\App\Core\Auth::getInstance()->user()['id'];
        $n = \App\Core\Database::getInstance()->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL", [$uid]);
        $this->json(['count' => (int)$n]);
    }

    public function readAll(): void { $this->markAllRead(); }
}

// ============================================================
// TASK CONTROLLER
// ============================================================
class TaskController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('tasks.view');
        $db      = Database::getInstance();
        $filters = [
            'search'      => $this->input('search', ''),
            'status'      => $this->input('status', ''),
            'priority'    => $this->input('priority', ''),
            'assigned_to' => $this->input('assigned_to', ''),
        ];
        $where  = ['t.deleted_at IS NULL'];
        $params = [];

        if ($filters['search']) {
            $where[]  = "(t.title LIKE ? OR t.description LIKE ?)";
            $s        = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }
        if ($filters['status'])      { $where[] = "t.status = ?";      $params[] = $filters['status']; }
        if ($filters['priority'])    { $where[] = "t.priority = ?";    $params[] = $filters['priority']; }
        if ($filters['assigned_to']) { $where[] = "t.assigned_to = ?"; $params[] = $filters['assigned_to']; }

        // Non-admins see only their tasks
        if (!Auth::can('tasks.view_all')) {
            $where[]  = "(t.assigned_to = ? OR t.created_by = ?)";
            $uid      = Auth::user()['id'];
            $params[] = $uid;
            $params[] = $uid;
        }

        $page = (int)($_GET['page'] ?? 1);
        $data = $db->paginate(
            "SELECT t.*, u.name AS assigned_name, c.name AS created_name
             FROM tasks t
             LEFT JOIN users u ON t.assigned_to = u.id
             LEFT JOIN users c ON t.created_by  = c.id
             WHERE " . implode(' AND ', $where) . " ORDER BY t.due_date ASC, t.priority DESC",
            $params, $page, 25
        );

        $users = $db->fetchAll("SELECT id, name FROM users WHERE is_active = 1 ORDER BY name");
        $this->view('tasks/index', compact('data', 'filters', 'users'));
    }

    public function store(): void
    {
        $this->requirePermission('tasks.create');
        $this->verifyCsrf();

        $data = [
            'title'       => $this->input('title'),
            'description' => $this->input('description'),
            'assigned_to' => $this->input('assigned_to') ?: null,
            'due_date'    => $this->input('due_date') ?: null,
            'priority'    => $this->input('priority', 'medium'),
            'status'      => 'pending',
            'created_by'  => Auth::user()['id'],
        ];

        if (empty($data['title'])) {
            $this->flash('error', 'Task title is required.');
            $this->back();
        }

        $db = Database::getInstance();
        $id = $db->insert('tasks', $data);

        // Notify assigned user
        if (!empty($data['assigned_to'])) {
            \App\Models\Notification::send(
                $data['assigned_to'],
                'New Task Assigned',
                'You have been assigned a new task: ' . $data['title'],
                'tasks/' . $id,
                Auth::user()['id']
            );
        }

        $this->auditLog('create', 'tasks', $id, $data);
        $this->flash('success', 'Task created successfully.');
        $this->redirect('/tasks');
    }

    public function updateStatus(): void
    {
        $this->requirePermission('tasks.update');
        $this->verifyCsrf();

        $db     = Database::getInstance();
        $taskId = $_GET['id'] ?? 0;
        $status = $this->input('status');

        $allowed = ['pending', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $allowed)) {
            $this->json(['success' => false, 'message' => 'Invalid status']);
        }

        $extra = [];
        if ($status === 'completed') $extra['completed_at'] = date('Y-m-d H:i:s');

        $db->update('tasks', array_merge(['status' => $status], $extra), ['id' => $taskId]);
        $this->auditLog('update', 'tasks', $taskId, ['status' => $status]);
        $this->json(['success' => true]);
    }
    public function create(): void { $this->index(); }
    public function show(int $id): void { $this->index(); }
    public function close(int $id): void {
        $this->requirePermission('tasks.update');
        $this->verifyCsrf();
        \App\Core\Database::getInstance()->update('tasks', ['status'=>'completed','completed_at'=>date('Y-m-d H:i:s')], 'id = ?', [$id]);
        $this->flash('success', 'Task closed.'); $this->redirect('/tasks');
    }
    public function comment(): void { $this->json(['success'=>true]); }

    public function update(): void { $this->updateStatus(); }
}

// ============================================================
// ADVANCE / SALARY ADVANCE CONTROLLER
// ============================================================
class AdvanceController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('advances.view');
        $db      = Database::getInstance();
        $filters = [
            'search' => $this->input('search', ''),
            'status' => $this->input('status', ''),
        ];
        $where  = ['a.deleted_at IS NULL'];
        $params = [];

        if ($filters['search']) {
            $where[]  = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ?)";
            $s        = '%' . $filters['search'] . '%';
            $params   = array_merge($params, [$s, $s, $s]);
        }
        if ($filters['status']) { $where[] = "a.status = ?"; $params[] = $filters['status']; }

        if (!Auth::can('advances.view_all')) {
            $where[]  = "a.employee_id = ?";
            $params[] = Auth::user()['employee_id'] ?? 0;
        }

        $page = (int)($_GET['page'] ?? 1);
        $data = $db->paginate(
            "SELECT a.*, (CONCAT(e.first_name,' ',e.last_name)) AS employee_name, e.employee_code
             FROM salary_advances a
             JOIN employees e ON a.employee_id = e.id
             WHERE " . implode(' AND ', $where) . " ORDER BY a.created_at DESC",
            $params, $page, 25
        );

        $employees = $db->fetchAll("SELECT id, employee_code, first_name, last_name FROM employees WHERE employment_status='active' AND deleted_at IS NULL ORDER BY first_name");
        $stats     = [
            'pending'  => $db->fetchColumn("SELECT COUNT(*) FROM salary_advances WHERE status='pending' AND deleted_at IS NULL"),
            'approved' => $db->fetchColumn("SELECT COUNT(*) FROM salary_advances WHERE status='approved' AND deleted_at IS NULL"),
            'total_outstanding' => $db->fetchColumn("SELECT COALESCE(SUM(balance_amount),0) FROM salary_advances WHERE status='approved' AND deleted_at IS NULL"),
        ];

        $this->view('advances/index', compact('data', 'filters', 'employees', 'stats'));
    }

    public function store(): void
    {
        $this->requirePermission('advances.create');
        $this->verifyCsrf();

        $employeeId = $this->input('employee_id');
        $amount     = (float)$this->input('amount', 0);
        $reason     = $this->input('reason', '');
        $repayIn    = (int)$this->input('repay_months', 1);

        if ($amount <= 0) {
            $this->flash('error', 'Amount must be greater than zero.');
            $this->back();
        }

        $db = Database::getInstance();
        $id = $db->insert('salary_advances', [
            'employee_id'    => $employeeId,
            'amount'         => $amount,
            'balance_amount' => $amount,
            'reason'         => $reason,
            'repay_months'   => $repayIn,
            'monthly_deduction' => round($amount / max(1, $repayIn), 2),
            'status'         => 'pending',
            'requested_by'   => Auth::user()['id'],
            'request_date'   => date('Y-m-d'),
        ]);

        $this->auditLog('create', 'salary_advances', $id, compact('employeeId', 'amount'));
        $this->flash('success', 'Advance request submitted successfully.');
        $this->redirect('/advances');
    }

    public function approve(int $id): void
    {
        $this->requirePermission('advances.approve');
        $this->verifyCsrf();

        $db = Database::getInstance();
        $db->update('salary_advances', [
            'status'      => 'approved',
            'approved_by' => Auth::user()['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        $this->auditLog('approve', 'salary_advances', $id);
        $this->flash('success', 'Advance approved.');
        $this->redirect('/advances');
    }

    public function reject(int $id): void
    {
        $this->requirePermission('advances.approve');
        $this->verifyCsrf();

        $db = Database::getInstance();
        $db->update('salary_advances', [
            'status'          => 'rejected',
            'rejection_reason' => $this->input('reason', ''),
            'approved_by'     => Auth::user()['id'],
        ], ['id' => $id]);

        $this->auditLog('reject', 'salary_advances', $id);
        $this->flash('success', 'Advance rejected.');
        $this->redirect('/advances');
    }
    public function apply(): void { $this->requirePermission('advances.create'); $this->index(); }

}

// ============================================================
// LOAN CONTROLLER
// ============================================================
class LoanController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('loans.view');
        $db      = Database::getInstance();
        $filters = ['search' => $this->input('search', ''), 'status' => $this->input('status', '')];
        $where   = ['l.deleted_at IS NULL'];
        $params  = [];

        if ($filters['search']) {
            $where[]  = "(e.first_name LIKE ? OR e.last_name LIKE ?)";
            $s        = '%' . $filters['search'] . '%';
            $params   = [$s, $s];
        }
        if ($filters['status']) { $where[] = "l.status = ?"; $params[] = $filters['status']; }

        $page = (int)($_GET['page'] ?? 1);
        $data = $db->paginate(
            "SELECT l.*, (CONCAT(e.first_name,' ',e.last_name)) AS employee_name
             FROM employee_loans l
             JOIN employees e ON l.employee_id = e.id
             WHERE " . implode(' AND ', $where) . " ORDER BY l.created_at DESC",
            $params, $page, 25
        );

        $employees = $db->fetchAll("SELECT id, employee_code, first_name, last_name FROM employees WHERE employment_status='active' AND deleted_at IS NULL ORDER BY first_name");
        $this->view('loans/index', compact('data', 'filters', 'employees'));
    }

    public function store(): void
    {
        $this->requirePermission('loans.create');
        $this->verifyCsrf();

        $employeeId   = $this->input('employee_id');
        $amount       = (float)$this->input('amount', 0);
        $interestRate = (float)$this->input('interest_rate', 0);
        $termMonths   = (int)$this->input('term_months', 12);

        if ($amount <= 0 || $termMonths < 1) {
            $this->flash('error', 'Invalid loan details.');
            $this->back();
        }

        // Simple EMI calculation: EMI = P*(r*(1+r)^n)/((1+r)^n-1)
        $monthly = $interestRate / 12 / 100;
        if ($monthly > 0) {
            $emi = $amount * ($monthly * pow(1 + $monthly, $termMonths)) / (pow(1 + $monthly, $termMonths) - 1);
        } else {
            $emi = $amount / $termMonths;
        }

        $db = Database::getInstance();
        $id = $db->insert('employee_loans', [
            'employee_id'       => $employeeId,
            'loan_amount'       => $amount,
            'balance_amount'    => $amount,
            'interest_rate'     => $interestRate,
            'term_months'       => $termMonths,
            'monthly_installment' => round($emi, 2),
            'purpose'           => $this->input('purpose', ''),
            'status'            => 'pending',
            'requested_by'      => Auth::user()['id'],
            'loan_date'         => date('Y-m-d'),
        ]);

        $this->auditLog('create', 'employee_loans', $id);
        $this->flash('success', 'Loan application submitted.');
        $this->redirect('/loans');
    }

    public function approve(int $id): void
    {
        $this->requirePermission('loans.approve');
        $this->verifyCsrf();
        $db = Database::getInstance();
        $db->update('employee_loans', [
            'status' => 'approved', 'approved_by' => Auth::user()['id'], 'approved_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
        $this->flash('success', 'Loan approved.');
        $this->redirect('/loans');
    }
    public function apply(): void { $this->requirePermission('loans.create'); $this->index(); }

}

// ============================================================
// SESSION CONTROLLER (extend session)
// ============================================================
class SessionController extends Controller
{
    public function extend(): void
    {
        $this->verifyCsrf();
        // Touching session is enough — PHP auto-extends
        $_SESSION['_last_activity'] = time();
        $this->json(['success' => true, 'expires_in' => 1800]);
    }
}

// ============================================================
// SEARCH CONTROLLER (global search)
// ============================================================
class SearchController extends Controller
{
    public function search(): void
    {
        $q       = trim($this->input('q', ''));
        $results = [];

        if (strlen($q) < 2) {
            $this->json(['results' => []]);
        }

        $db   = Database::getInstance();
        $like = '%' . $q . '%';

        // Employees
        if (Auth::can('employees.view')) {
            $emps = $db->fetchAll(
                "SELECT id, employee_code, (CONCAT(first_name,' ',last_name)) AS name FROM employees
                 WHERE (first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ? OR cnic LIKE ?)
                   AND deleted_at IS NULL LIMIT 5",
                [$like, $like, $like, $like]
            );
            foreach ($emps as $e) {
                $results[] = ['type' => 'Employee', 'title' => $e['name'] . ' (' . $e['employee_code'] . ')', 'url' => '/employees/' . $e['id']];
            }
        }

        // Payroll
        if (Auth::can('payroll.view')) {
            $periods = $db->fetchAll(
                "SELECT id, period_label FROM payroll_periods WHERE period_label LIKE ? LIMIT 3",
                [$like]
            );
            foreach ($periods as $p) {
                $results[] = ['type' => 'Payroll', 'title' => $p['period_label'], 'url' => '/payroll/' . $p['id']];
            }
        }

        $this->json(['results' => $results]);
    }
}


namespace App\Controllers;

class SalaryController extends \App\Core\Controller
{
    public function index(): void   { $this->redirect('/payroll'); }
    public function create(): void  { $this->redirect('/payroll'); }
    public function store(): void   { $this->redirect('/payroll'); }
    public function components(): void { $this->redirect('/payroll'); }
    public function structure(): void  { $this->redirect('/payroll'); }
    public function employeeStructure(): void { $this->redirect("/payroll"); }
    public function saveStructure(): void { $this->redirect("/payroll"); }
    public function storeComponent(): void { $this->json(["success"=>true]); }
}
class SystemController extends \App\Core\Controller
{
    public function maintenance(): void { $this->json(['status' => 'ok']); }
    public function health(): void      { $this->json(['status' => 'ok']); }
}
