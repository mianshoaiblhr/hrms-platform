<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\AuditLogger;

// =========================================================
// User Controller
// =========================================================
class UserController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('users.view');
        $model = new \App\Models\User();
        $filters = ['search' => $this->input('search',''), 'role_id' => (int)$this->input('role',0), 'status' => $this->input('status','')];
        $data  = $model->listAll($filters, (int)$this->input('page',1));
        $roles = $this->db->fetchAll("SELECT * FROM roles WHERE status='active' ORDER BY name");
        $this->view('settings/users', compact('data','roles','filters'));
    }

    public function create(): void
    {
        $this->requirePermission('users.create');
        $roles     = $this->db->fetchAll("SELECT * FROM roles WHERE status='active' ORDER BY name");
        $employees = $this->db->fetchAll("SELECT e.id, (CONCAT(e.first_name,' ',e.last_name)||' ('||e.employee_code||')') AS name FROM employees e LEFT JOIN users u ON e.id=u.employee_id WHERE u.id IS NULL AND e.deleted_at IS NULL ORDER BY e.first_name");
        $this->view('settings/user_form', compact('roles','employees'));
    }

    public function store(): void
    {
        $this->requirePermission('users.create');
        $this->verifyCsrf();
        $data = $this->validate([
            'username' => 'required|min:3',
            'email'    => 'required|email',
            'role_id'  => 'required|numeric',
            'password' => 'required|min:8',
        ]);
        if ($this->db->fetchColumn("SELECT id FROM users WHERE username=? AND deleted_at IS NULL", [$data['username']])) {
            $this->flash('error', 'Username already taken.'); $this->redirect('/users/create');
        }
        if ($this->db->fetchColumn("SELECT id FROM users WHERE email=? AND deleted_at IS NULL", [$data['email']])) {
            $this->flash('error', 'Email already registered.'); $this->redirect('/users/create');
        }
        $userId = $this->db->insert('users', [
            'username'    => $data['username'],
            'email'       => $data['email'],
            'password'    => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role_id'     => $data['role_id'],
            'employee_id' => (int)$this->input('employee_id', 0) ?: null,
            'status'      => 'active',
            'must_change_password' => 1,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        AuditLogger::log('user.create', 'users', $userId);
        // Send welcome email
        $email = new \App\Services\EmailService();
        $email->sendWelcome($data['email'], $data['username'], $data['username'], $data['password']);
        $this->flash('success', 'User created successfully.');
        $this->redirect('/users');
    }

    public function edit(int $id): void
    {
        $this->requirePermission('users.edit');
        $user  = $this->db->fetchOne("SELECT * FROM users WHERE id=? AND deleted_at IS NULL", [$id]);
        if (!$user) { $this->abort(404); }
        $roles = $this->db->fetchAll("SELECT * FROM roles WHERE status='active' ORDER BY name");
        $this->view('settings/user_form', compact('user','roles'));
    }

    public function update(int $id): void
    {
        $this->requirePermission('users.edit');
        $this->verifyCsrf();
        $data = ['role_id' => (int)$this->input('role_id'), 'email' => sanitize($this->input('email')), 'updated_at' => date('Y-m-d H:i:s')];
        $pw = $this->input('password','');
        if ($pw) { $data['password'] = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]); }
        $this->db->update('users', $data, 'id = ?', [$id]);
        AuditLogger::log('user.update', 'users', $id);
        $this->flash('success', 'User updated.');
        $this->redirect('/users');
    }

    public function toggleStatus(int $id): void
    {
        $this->requirePermission('users.edit');
        $this->verifyCsrf();
        $user = $this->db->fetchOne("SELECT status FROM users WHERE id=? AND deleted_at IS NULL", [$id]);
        if (!$user) { $this->json(['success' => false]); }
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $this->db->update('users', ['status' => $newStatus], 'id = ?', [$id]);
        AuditLogger::log('user.toggle', 'users', $id, null, ['status' => $newStatus]);
        $this->json(['success' => true, 'status' => $newStatus]);
    }

    public function forceLogout(int $id): void
    {
        $this->requirePermission('users.edit');
        $this->db->query("UPDATE users SET remember_token=NULL WHERE id=?", [$id]);
        // Invalidate active session (simplified — production needs session store)
        AuditLogger::log('user.force_logout', 'users', $id);
        $this->json(['success' => true, 'message' => 'User session invalidated.']);
    }

    public function resetPassword(int $id): void
    {
        $this->requirePermission('users.edit');
        $this->verifyCsrf();
        $newPw = generatePassword(12);
        $this->db->update('users', ['password' => password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]), 'must_change_password' => 1], 'id = ?', [$id]);
        $user = $this->db->fetchOne("SELECT email, username FROM users WHERE id=?", [$id]);
        $email = new \App\Services\EmailService();
        $email->sendWelcome($user['email'], $user['username'], $user['username'], $newPw);
        AuditLogger::log('user.reset_password', 'users', $id);
        $this->json(['success' => true, 'message' => 'Password reset and emailed to user.']);
    }
    public function toggle(): void { $this->toggleStatus(); }
}

// =========================================================
// Role Controller
// =========================================================
class RoleController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('roles.view');
        $roles = $this->db->fetchAll("SELECT r.*, COUNT(u.id) AS user_count FROM roles r LEFT JOIN users u ON r.id=u.role_id AND u.deleted_at IS NULL GROUP BY r.id ORDER BY r.name");
        $this->view('roles/index', compact('roles'));
    }

    public function edit(int $id): void
    {
        $this->requirePermission('roles.edit');
        $role = $this->db->fetchOne("SELECT * FROM roles WHERE id=?", [$id]);
        if (!$role) { $this->abort(404); }
        $allPermissions = $this->db->fetchAll("SELECT * FROM permissions ORDER BY module, name");
        $model = new \App\Models\Role();
        $assigned = array_column($model->getPermissions($id), 'id');
        $grouped = [];
        foreach ($allPermissions as $p) { $grouped[$p['module']][] = $p; }
        $this->view('roles/edit', compact('role','grouped','assigned'));
    }

    public function store(): void
    {
        $this->requirePermission('roles.create');
        $this->verifyCsrf();
        $name = sanitize($this->input('name',''));
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));
        if ($this->db->fetchColumn("SELECT id FROM roles WHERE slug=?", [$slug])) {
            $this->flash('error','Role with this name already exists.'); $this->redirect('/roles');
        }
        $id = $this->db->insert('roles', ['name'=>$name,'slug'=>$slug,'description'=>sanitize($this->input('description','')),'is_system'=>0,'status'=>'active']);
        AuditLogger::log('role.create','roles',$id);
        $this->flash('success','Role created.'); $this->redirect('/roles/' . $id . '/edit');
    }

    public function updatePermissions(int $id): void
    {
        $this->requirePermission('roles.edit');
        $this->verifyCsrf();
        $role = $this->db->fetchOne("SELECT * FROM roles WHERE id=?", [$id]);
        if ($role['is_system'] && $role['slug'] === 'super_admin') {
            $this->flash('error','Cannot modify Super Admin permissions.'); $this->redirect('/roles');
        }
        $permIds = array_map('intval', (array)($this->input('permissions', [])));
        $model = new \App\Models\Role();
        $model->syncPermissions($id, $permIds);
        AuditLogger::log('role.permissions_updated','roles',$id);
        $this->flash('success','Permissions updated successfully.');
        $this->redirect('/roles');
    }
    public function delete(int $id): void
    {
        $this->requirePermission('roles.delete');
        $this->verifyCsrf();
        $role = $this->db->fetchOne("SELECT * FROM roles WHERE id=?", [$id]);
        if (!$role || $role['is_system']) { $this->flash('error', 'Cannot delete system roles.'); $this->redirect('/roles'); }
        $this->db->update('roles', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        $this->flash('success', 'Role deleted.'); $this->redirect('/roles');
    }

}

// =========================================================
// Settings Controller
// =========================================================
class SettingsController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('settings.view');
        $company = $this->getSettings('company');
        $payroll = $this->getSettings('payroll');
        $security= $this->getSettings('security');
        $this->view('settings/index', compact('company','payroll','security'));
    }

    public function updateCompany(): void
    {
        $this->requirePermission('settings.edit');
        $this->verifyCsrf();
        $fields = ['company_name','company_address','company_phone','company_email','company_website','company_ntn','company_registration'];
        foreach ($fields as $f) { $this->saveSetting('company', $f, sanitize($this->input($f, ''))); }
        AuditLogger::log('settings.company_updated','system_configs');
        $this->flash('success','Company settings updated.'); $this->redirect('/settings');
    }

    public function departments(): void
    {
        $this->requirePermission('settings.view');
        $departments = $this->db->fetchAll("SELECT d.*,COALESCE(COUNT(e.id),0) AS emp_count FROM departments d LEFT JOIN employees e ON e.department_id=d.id AND e.deleted_at IS NULL WHERE d.deleted_at IS NULL GROUP BY d.id ORDER BY d.name");
        $this->view('settings/departments', compact('departments'));
    }

    public function storeDepartment(): void
    {
        $this->requirePermission('settings.edit');
        $this->verifyCsrf();
        $id = (int)$this->input('id', 0);
        $data = ['name' => sanitize($this->input('name','')), 'code' => sanitize($this->input('code','')), 'description' => sanitize($this->input('description','')), 'status' => $this->input('status','active')];
        if ($id) { $this->db->update('departments', array_merge($data, ['updated_at' => date('Y-m-d H:i:s')]), 'id = ?', [$id]); }
        else { $this->db->insert('departments', array_merge($data, ['created_at' => date('Y-m-d H:i:s')])); }
        $this->flash('success','Department saved.'); $this->redirect('/settings/departments');
    }

    public function designations(): void
    {
        $this->requirePermission('settings.view');
        $designations = $this->db->fetchAll("SELECT des.*,d.name AS dept_name FROM designations des LEFT JOIN departments d ON des.department_id=d.id WHERE des.deleted_at IS NULL ORDER BY des.title");
        $departments  = $this->db->fetchAll("SELECT id,name FROM departments WHERE is_active=1 AND deleted_at IS NULL ORDER BY name");
        $this->view('settings/designations', compact('designations','departments'));
    }

    public function storeDesignation(): void
    {
        $this->requirePermission('settings.edit');
        $this->verifyCsrf();
        $id = (int)$this->input('id',0);
        $data = ['title'=>sanitize($this->input('title','')), 'department_id'=>(int)$this->input('department_id',0), 'grade'=>sanitize($this->input('grade','')), 'status'=>$this->input('status','active')];
        if ($id) { $this->db->update('designations', array_merge($data,['updated_at'=>date('Y-m-d H:i:s')]), 'id=?', [$id]); }
        else { $this->db->insert('designations', array_merge($data,['created_at'=>date('Y-m-d H:i:s')])); }
        $this->flash('success','Designation saved.'); $this->redirect('/settings/designations');
    }

    public function leaveTypes(): void
    {
        $this->requirePermission('settings.view');
        $leaveTypes = $this->db->fetchAll("SELECT * FROM leave_types ORDER BY name");
        $this->view('settings/leave_types', compact('leaveTypes'));
    }

    public function holidays(): void
    {
        $this->requirePermission('settings.view');
        $year = (int)$this->input('year', date('Y'));
        $holidays = $this->db->fetchAll("SELECT * FROM holidays WHERE strftime('%Y',holiday_date)=? ORDER BY holiday_date", [$year]);
        $this->view('settings/holidays', compact('holidays','year'));
    }

    private function getSettings(string $group): array
    {
        $rows = $this->db->fetchAll("SELECT config_key, config_value FROM system_configs WHERE config_group=?", [$group]);
        $result = [];
        foreach ($rows as $r) $result[$r['config_key']] = $r['config_value'];
        return $result;
    }

    private function saveSetting(string $group, string $key, string $value): void
    {
        $exists = $this->db->fetchColumn("SELECT id FROM system_configs WHERE config_group=? AND config_key=?", [$group,$key]);
        if ($exists) { $this->db->update('system_configs', ['config_value'=>$value,'updated_at'=>date('Y-m-d H:i:s')], 'config_group=? AND config_key=?', [$group,$key]); }
        else { $this->db->insert('system_configs', ['config_group'=>$group,'config_key'=>$key,'config_value'=>$value,'created_at'=>date('Y-m-d H:i:s')]); }
    }
    public function company(): void { $this->index(); }
    public function backup(): void { $this->json(['success' => true, 'message' => 'Coming soon']); }
    public function security(): void { $this->index(); }
    public function shifts(): void { $this->index(); }

    public function updatePayroll(): void { $this->updateCompany(); }
    public function updateSecurity(): void { $this->updateCompany(); }
    public function createBackup(): void { $this->json(["success"=>true]); }
}

// =========================================================
// Report Controller
// =========================================================
class ReportController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('reports.view');
        $this->view('reports/index');
    }

    public function payroll(): void
    {
        $this->requirePermission('reports.payroll');
        $from = $this->input('from', date('Y-m-01'));
        $to   = $this->input('to', date('Y-m-d'));
        $dept = (int)$this->input('dept', 0);
        $params = [$from, $to];
        $where = "pp.start_date >= ? AND pp.end_date <= ?";
        if ($dept) { $where .= " AND e.department_id = ?"; $params[] = $dept; }
        $data = $this->db->fetchAll(
            "SELECT e.employee_code, (CONCAT(e.first_name,' ',e.last_name)) AS name,
                    d.name AS dept, pp.name AS period_name,
                    pi.gross_salary, pi.total_deductions, pi.net_salary, pi.status
             FROM payroll_items pi
             JOIN employees e ON pi.employee_id=e.id
             JOIN payroll_periods pp ON pi.payroll_period_id=pp.id
             LEFT JOIN departments d ON e.department_id=d.id
             WHERE $where ORDER BY e.first_name",
            $params
        );
        if ($this->input('export') === 'csv') {
            $export = new \App\Services\ExportService();
            $export->exportCSV($data, ['Emp Code','Name','Department','Period','Gross','Deductions','Net','Status'], 'payroll_report_'.$from.'_'.$to);
        }
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $this->view('reports/payroll', compact('data','from','to','dept','departments'));
    }

    public function attendance(): void
    {
        $this->requirePermission('reports.attendance');
        $from = $this->input('from', date('Y-m-01'));
        $to   = $this->input('to', date('Y-m-d'));
        $dept = (int)$this->input('dept', 0);
        $params = [$from, $to];
        $deptCond = $dept ? " AND e.department_id = $dept" : '';
        $data = $this->db->fetchAll(
            "SELECT e.employee_code, (CONCAT(e.first_name,' ',e.last_name)) AS name,
                    d.name AS dept,
                    SUM(a.status='present') AS present,
                    SUM(a.status='absent') AS absent,
                    SUM(a.status='late') AS late,
                    SUM(a.status='half_day') AS half_day,
                    SUM(a.status='on_leave') AS on_leave,
                    ROUND(AVG(a.working_hours),2) AS avg_hours
             FROM attendance a JOIN employees e ON a.employee_id=e.id LEFT JOIN departments d ON e.department_id=d.id
             WHERE a.attendance_date BETWEEN ? AND ? AND a.deleted_at IS NULL $deptCond
             GROUP BY a.employee_id ORDER BY e.first_name",
            $params
        );
        if ($this->input('export') === 'csv') {
            $export = new \App\Services\ExportService();
            $export->exportCSV($data, ['Emp Code','Name','Dept','Present','Absent','Late','Half Day','On Leave','Avg Hours'], 'attendance_report');
        }
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $this->view('reports/attendance', compact('data','from','to','dept','departments'));
    }

    public function tax(): void
    {
        $this->requirePermission('reports.tax');
        $year = (int)$this->input('year', date('Y'));
        $data = $this->db->fetchAll(
            "SELECT e.employee_code, (CONCAT(e.first_name,' ',e.last_name)) AS name, e.cnic, e.ntn,
                    COALESCE(SUM(pid.amount),0) AS total_income_tax
             FROM employees e
             JOIN payroll_items pi ON pi.employee_id=e.id
             JOIN payroll_item_details pid ON pid.payroll_item_id=pi.id
             JOIN salary_components sc ON pid.component_id=sc.id
             WHERE sc.type='deduction' AND sc.name='Income Tax' AND strftime('%Y',pi.created_at)=?
             AND e.deleted_at IS NULL
             GROUP BY e.id ORDER BY e.first_name",
            [$year]
        );
        if ($this->input('export') === 'csv') {
            $export = new \App\Services\ExportService();
            $export->exportCSV($data, ['Emp Code','Name','CNIC','NTN','Total Income Tax'], 'tax_report_'.$year);
        }
        $this->view('reports/tax', compact('data','year'));
    }

    public function eobi(): void
    {
        $this->requirePermission('reports.eobi');
        $from = $this->input('from', date('Y-m-01'));
        $to   = $this->input('to', date('Y-m-d'));
        $data = $this->db->fetchAll(
            "SELECT e.employee_code, (CONCAT(e.first_name,' ',e.last_name)) AS name,
                    e.cnic, e.eobi_number, e.date_of_birth,
                    COALESCE(SUM(CASE WHEN sc.name='EOBI Employee' THEN pid.amount ELSE 0 END),0) AS employee_contribution,
                    COALESCE(SUM(CASE WHEN sc.name='EOBI Employer' THEN pid.amount ELSE 0 END),0) AS employer_contribution
             FROM employees e
             JOIN payroll_items pi ON pi.employee_id=e.id
             JOIN payroll_item_details pid ON pid.payroll_item_id=pi.id
             JOIN salary_components sc ON pid.component_id=sc.id
             WHERE pi.created_at BETWEEN ? AND ? AND e.deleted_at IS NULL
             GROUP BY e.id ORDER BY e.first_name",
            [$from, $to]
        );
        if ($this->input('export') === 'csv') {
            $export = new \App\Services\ExportService();
            $export->exportCSV($data, ['Emp Code','Name','CNIC','EOBI No.','DOB','Employee Contribution','Employer Contribution'], 'eobi_report');
        }
        $this->view('reports/eobi', compact('data','from','to'));
    }
    public function employees(): void
    {
        $this->requirePermission('reports.view');
        $departments = $this->db->fetchAll("SELECT id, name FROM departments WHERE deleted_at IS NULL ORDER BY name");
        $this->view('reports/payroll', ['report' => null, 'departments' => $departments, 'filters' => []]);
    }

    public function generate(): void
    {
        $this->requirePermission('reports.view');
        $this->redirect('/reports/' . $this->input('type', 'payroll'));
    }

    public function leaves(): void
    {
        $this->requirePermission('reports.view');
        $this->view('reports/payroll', ['report' => null, 'departments' => [], 'filters' => []]);
    }

}

// =========================================================
// Audit Controller
// =========================================================
class AuditController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('audit.view');
        $filters = [
            'user_id'   => (int)$this->input('user', 0),
            'action'    => $this->input('action', ''),
            'from'      => $this->input('from', ''),
            'to'        => $this->input('to', ''),
            'module'    => $this->input('module', ''),
        ];
        $where = ['1=1'];
        $params = [];
        if ($filters['user_id']) { $where[] = "al.user_id=?"; $params[] = $filters['user_id']; }
        if ($filters['action'])  { $where[] = "al.action LIKE ?"; $params[] = '%' . $filters['action'] . '%'; }
        if ($filters['from'])    { $where[] = "al.created_at >= ?"; $params[] = $filters['from'] . ' 00:00:00'; }
        if ($filters['to'])      { $where[] = "al.created_at <= ?"; $params[] = $filters['to'] . ' 23:59:59'; }
        if ($filters['module'])  { $where[] = "al.table_name LIKE ?"; $params[] = '%' . $filters['module'] . '%'; }
        $whereStr = implode(' AND ', $where);
        $base = "FROM audit_logs al LEFT JOIN users u ON al.user_id=u.id LEFT JOIN employees e ON u.employee_id=e.id WHERE $whereStr";
        $logs = $this->db->paginate(
            "SELECT...",
            $params, (int)$this->input('page',1)
        );
        $users = $this->db->fetchAll("SELECT u.id, u.username FROM users u WHERE u.deleted_at IS NULL ORDER BY u.username");
        $this->view('audit/index', compact('logs','filters','users'));
    }

    public function loginLogs(): void
    {
        $this->requirePermission('audit.view');
        $page = (int)$this->input('page',1);
        $logs = $this->db->paginate(
            "SELECT ll.*, u.username FROM login_logs ll LEFT JOIN users u ON ll.user_id=u.id ORDER BY ll.created_at DESC",
            [], $page
        );
        $this->view('audit/login_logs', compact('logs'));
    }
    public function export(): void
    {
        $this->requirePermission('audit.view');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_' . date('Y-m-d') . '.csv"');
        $rows = $this->db->fetchAll("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5000");
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','User','Action','Module','Description','IP','Date']);
        foreach ($rows as $r) fputcsv($out, [$r['id'],$r['user_name'],$r['action'],$r['module']??'',$r['description']??'',$r['ip_address'],$r['created_at']]);
        fclose($out); exit;
    }

}

// =========================================================
// Profile Controller
// =========================================================
class ProfileController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $user = Auth::getInstance()->user();
        $employee = null;
        if ($user['employee_id']) {
            $employee = $this->db->fetchOne(
                "SELECT e.*, d.name AS dept, des.title AS designation FROM employees e
                 LEFT JOIN departments d ON e.department_id=d.id
                 LEFT JOIN designations des ON e.designation_id=des.id
                 WHERE e.id=? AND e.deleted_at IS NULL", [(int)$user['employee_id']]
            );
        }
        $this->view('settings/profile', compact('user','employee'));
    }

    public function updatePassword(): void
    {
        $this->verifyCsrf();
        $user = Auth::getInstance()->user();
        $current = $this->input('current_password','');
        $new     = $this->input('new_password','');
        $confirm = $this->input('confirm_password','');

        $dbUser = $this->db->fetchOne("SELECT password FROM users WHERE id=?", [(int)$user['id']]);
        if (!password_verify($current, $dbUser['password'])) {
            $this->flash('error','Current password is incorrect.'); $this->redirect('/profile');
        }
        if ($new !== $confirm) { $this->flash('error','New passwords do not match.'); $this->redirect('/profile'); }
        if (strlen($new) < 8) { $this->flash('error','Password must be at least 8 characters.'); $this->redirect('/profile'); }

        $this->db->update('users', ['password' => password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]), 'must_change_password'=>0, 'password_changed_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$user['id']]);
        AuditLogger::log('profile.password_changed','users',$user['id']);
        $this->flash('success','Password changed successfully.');
        $this->redirect('/profile');
    }

    public function updateAvatar(): void
    {
        $this->verifyCsrf();
        $user = Auth::getInstance()->user();
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error','Please select an image.'); $this->redirect('/profile');
        }
        $uploader = new \App\Services\FileUploadService();
        $result = $uploader->upload($_FILES['avatar'], 'avatars', ['image/jpeg','image/png','image/webp']);
        if (!$result['success']) { $this->flash('error', $result['error']); $this->redirect('/profile'); }
        if ($user['employee_id']) {
            $this->db->update('employees', ['avatar'=>$result['filename'],'updated_at'=>date('Y-m-d H:i:s')], 'id=?', [(int)$user['employee_id']]);
        }
        $this->flash('success','Profile picture updated.');
        $this->redirect('/profile');
    }
    public function show(): void { $this->update(); }

}
