<?php
// ============================================================
// app/Controllers/EmployeeController.php
// ============================================================

namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuditLogger;
use App\Core\Session;

class EmployeeController extends Controller
{
    // --------------------------------------------------------
    // LIST EMPLOYEES
    // --------------------------------------------------------
    public function index(): void
    {
        try { $this->_index(); } catch (\Throwable $e) {
            error_log('[EmployeeController] ' . $e->getMessage());
            $this->view('employees/index', [
                'data' => ['data'=>[],'total'=>0,'per_page'=>25,'current_page'=>1,'last_page'=>1],
                'departments' => [], 'filters' => [], 'stats' => []
            ]);
        }
    }
    private function _index(): void
    {
        $this->requirePermission('employees.view');

        $search     = $this->sanitize($this->input('search', ''));
        $department = (int)$this->input('department', 0);
        $status     = $this->input('status', 'active');
        $sortBy     = $this->input('sort', 'e.id');
        $sortDir    = $this->input('dir', 'DESC');

        // Whitelist sort columns
        $allowedSort = ['e.id', 'e.first_name', 'e.join_date', 'd.name', 'des.title'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'e.id';
        if (!in_array($sortDir, ['ASC', 'DESC'])) $sortDir = 'DESC';

        $sql = "SELECT e.id, e.employee_code, e.first_name, e.last_name, e.cnic,
                       e.mobile, e.join_date, e.status, e.basic_salary,
                       e.profile_photo, e.contract_type,
                       d.name AS department_name, des.title AS designation,
                       u.email, u.last_login_at
                FROM employees e
                JOIN departments d ON e.department_id = d.id
                JOIN designations des ON e.designation_id = des.id
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.deleted_at IS NULL";

        $params = [];

        if ($search) {
            $sql    .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.cnic LIKE ? OR e.mobile LIKE ?)";
            $params  = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
        }
        if ($department) {
            $sql    .= " AND e.department_id = ?";
            $params[] = $department;
        }
        if ($status) {
            $sql    .= " AND e.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY {$sortBy} {$sortDir}";

        $employees   = $this->paginate($sql, $params, 20);
        $departments = $this->db->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
        $stats       = $this->getEmployeeStats();

        $this->view('employees.index', [
            'title'       => 'Employee Management',
            'employees'   => $employees,
            'departments' => $departments,
            'stats'       => $stats,
            'filters'     => compact('search', 'department', 'status'),
            'csrf_token'  => Session::csrfToken(),
        ]);
    }

    // --------------------------------------------------------
    // SHOW EMPLOYEE PROFILE
    // --------------------------------------------------------
    public function show(string $id): void
    {
        $this->requirePermission('employees.view');

        $employee = $this->db->fetchOne(
            "SELECT e.*, 
                    d.name AS department_name, d.code AS dept_code,
                    des.title AS designation, des.grade,
                    u.username, u.email AS login_email, u.last_login_at, u.is_active AS account_active,
                    r.name AS role_name,
                    CONCAT(m.first_name, ' ', m.last_name) AS manager_name
             FROM employees e
             JOIN departments d ON e.department_id = d.id
             JOIN designations des ON e.designation_id = des.id
             LEFT JOIN users u ON e.user_id = u.id
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN employees m ON e.reporting_to = m.id
             WHERE e.id = ? AND e.deleted_at IS NULL",
            [$id]
        );

        if (!$employee) {
            $this->abort(404, 'Employee not found.');
        }

        // Restrict: non-admin employees can only view their own profile
        if ($this->auth->roleSlug() === 'employee') {
            $myEmpId = $this->db->fetchColumn(
                "SELECT id FROM employees WHERE user_id = ?",
                [$this->auth->id()]
            );
            if ($myEmpId != $id) {
                $this->abort(403, 'Access denied.');
            }
        }

        $education   = $this->db->fetchAll("SELECT * FROM employee_education WHERE employee_id = ?", [$id]);
        $experience  = $this->db->fetchAll("SELECT * FROM employee_experience WHERE employee_id = ?", [$id]);
        $promotions  = $this->db->fetchAll(
            "SELECT ep.*, d_old.name AS old_dept, d_new.name AS new_dept, 
                    des_old.title AS old_designation, des_new.title AS new_designation
             FROM employee_promotions ep
             LEFT JOIN departments d_old ON ep.old_department_id = d_old.id
             LEFT JOIN departments d_new ON ep.new_department_id = d_new.id
             LEFT JOIN designations des_old ON ep.old_designation_id = des_old.id
             LEFT JOIN designations des_new ON ep.new_designation_id = des_new.id
             WHERE ep.employee_id = ? ORDER BY ep.effective_date DESC",
            [$id]
        );
        $documents  = $this->db->fetchAll(
            "SELECT d.*, dc.name AS category_name FROM documents d
             LEFT JOIN document_categories dc ON d.category_id = dc.id
             WHERE d.employee_id = ? AND d.deleted_at IS NULL ORDER BY d.created_at DESC",
            [$id]
        );
        $leaveBalance = $this->db->fetchAll(
            "SELECT lb.*, lt.name AS leave_type FROM leave_balances lb
             JOIN leave_types lt ON lb.leave_type_id = lt.id
             WHERE lb.employee_id = ? AND lb.year = YEAR(NOW())",
            [$id]
        );
        $attendanceSummary = $this->db->fetchOne(
            "SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) AS present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) AS absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) AS late,
                COUNT(CASE WHEN status = 'leave' THEN 1 END) AS on_leave,
                SUM(working_hours) AS total_hours,
                SUM(overtime_hours) AS overtime_hours
             FROM attendance
             WHERE employee_id = ? AND MONTH(date) = MONTH(NOW()) AND YEAR(date) = YEAR(NOW())",
            [$id]
        );

        $this->view('employees.show', compact(
            'employee', 'education', 'experience', 'promotions',
            'documents', 'leaveBalance', 'attendanceSummary'
        ) + ['title' => $employee['first_name'] . ' ' . $employee['last_name']]);
    }

    // --------------------------------------------------------
    // CREATE FORM
    // --------------------------------------------------------
    public function create(): void
    {
        $this->requirePermission('employees.create');

        $departments  = $this->db->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
        $designations = $this->db->fetchAll("SELECT id, title, department_id FROM designations WHERE is_active = 1 ORDER BY title");
        $managers     = $this->db->fetchAll("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status = 'active' AND deleted_at IS NULL ORDER BY first_name");
        $shifts       = $this->db->fetchAll("SELECT id, name FROM shifts WHERE is_active = 1");

        $this->view('employees.create', [
            'title'        => 'Add New Employee',
            'departments'  => $departments,
            'designations' => $designations,
            'managers'     => $managers,
            'shifts'       => $shifts,
            'csrf_token'   => Session::csrfToken(),
        ]);
    }

    // --------------------------------------------------------
    // STORE EMPLOYEE
    // --------------------------------------------------------
    public function store(): void
    {
        $this->requirePermission('employees.create');
        $this->verifyCsrf();

        $data = $this->getAllInput();

        $errors = $this->validate($data, [
            'first_name'       => 'required|max:100',
            'last_name'        => 'required|max:100',
            'department_id'    => 'required|numeric',
            'designation_id'   => 'required|numeric',
            'join_date'        => 'required|date',
            'basic_salary'     => 'required|numeric',
            'cnic'             => 'max:20',
            'personal_email'   => 'email',
            'official_email'   => 'email',
        ]);

        if (!empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old_input', $data);
            $this->redirect('/employees/create');
            return;
        }

        // Check CNIC uniqueness
        if (!empty($data['cnic'])) {
            $cnicExists = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM employees WHERE cnic = ? AND deleted_at IS NULL",
                [$data['cnic']]
            );
            if ($cnicExists > 0) {
                $this->flash('danger', 'An employee with this CNIC already exists.');
                $this->redirect('/employees/create');
                return;
            }
        }

        // Generate employee code
        $lastCode = $this->db->fetchColumn("SELECT MAX(CAST(SUBSTRING(employee_code, 4) AS UNSIGNED)) FROM employees");
        $newCode  = 'EMP' . str_pad(($lastCode ?? 0) + 1, 5, '0', STR_PAD_LEFT);

        // Handle profile photo
        $photoPath = null;
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $photoPath = $this->uploadFile($_FILES['profile_photo'], 'photos');
        }

        $this->db->beginTransaction();
        try {
            $employeeId = $this->db->insert('employees', [
                'employee_code'      => $newCode,
                'first_name'         => $this->sanitize($data['first_name']),
                'last_name'          => $this->sanitize($data['last_name']),
                'father_name'        => $this->sanitize($data['father_name'] ?? ''),
                'department_id'      => (int)$data['department_id'],
                'designation_id'     => (int)$data['designation_id'],
                'reporting_to'       => !empty($data['reporting_to']) ? (int)$data['reporting_to'] : null,
                'date_of_birth'      => $data['date_of_birth'] ?: null,
                'gender'             => $data['gender'] ?? null,
                'marital_status'     => $data['marital_status'] ?? null,
                'cnic'               => $data['cnic'] ?: null,
                'cnic_issue_date'    => $data['cnic_issue_date'] ?: null,
                'cnic_expiry_date'   => $data['cnic_expiry_date'] ?: null,
                'personal_email'     => $data['personal_email'] ?: null,
                'official_email'     => $data['official_email'] ?: null,
                'mobile'             => $data['mobile'] ?: null,
                'emergency_contact_name'  => $this->sanitize($data['emergency_contact_name'] ?? ''),
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_relation' => $data['emergency_contact_relation'] ?? null,
                'present_address'    => $this->sanitize($data['present_address'] ?? ''),
                'permanent_address'  => $this->sanitize($data['permanent_address'] ?? ''),
                'city'               => $this->sanitize($data['city'] ?? ''),
                'province'           => $this->sanitize($data['province'] ?? ''),
                'join_date'          => $data['join_date'],
                'contract_type'      => $data['contract_type'] ?? 'probation',
                'contract_end_date'  => $data['contract_end_date'] ?: null,
                'basic_salary'       => (float)$data['basic_salary'],
                'bank_name'          => $this->sanitize($data['bank_name'] ?? ''),
                'bank_account'       => $data['bank_account'] ?? null,
                'bank_branch'        => $this->sanitize($data['bank_branch'] ?? ''),
                'iban'               => $data['iban'] ?? null,
                'ntn'                => $data['ntn'] ?? null,
                'eobi_number'        => $data['eobi_number'] ?? null,
                'pessi_number'       => $data['pessi_number'] ?? null,
                'profile_photo'      => $photoPath,
                'status'  => 'active',
                'created_by'         => $this->auth->id(),
            ]);

            // Create system account if requested
            if (!empty($data['create_account']) && !empty($data['user_email'])) {
                $tempPassword = $this->generateTempPassword();
                $roleId = (int)($data['role_id'] ?? 6); // Default: Employee role

                $userId = $this->db->insert('users', [
                    'employee_id'           => $employeeId,
                    'username'              => strtolower($data['username'] ?? $newCode),
                    'email'                 => $data['user_email'],
                    'password'              => password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                    'role_id'               => $roleId,
                    'department_id'         => (int)$data['department_id'],
                    'full_name'             => $data['first_name'] . ' ' . $data['last_name'],
                    'force_password_change' => 1,
                    'created_by'            => $this->auth->id(),
                ]);

                $this->db->update('employees', ['user_id' => $userId], 'id = ?', [$employeeId]);
            }

            // Initialize leave balances for current year
            $leaveTypes = $this->db->fetchAll("SELECT id, days_per_year, gender_specific FROM leave_types WHERE is_active = 1");
            $employee   = $this->db->fetchOne("SELECT gender FROM employees WHERE id = ?", [$employeeId]);

            foreach ($leaveTypes as $lt) {
                if ($lt['gender_specific'] === 'all'
                    || $lt['gender_specific'] === ($employee['gender'] ?? 'all')) {
                    $this->db->insert('leave_balances', [
                        'employee_id'    => $employeeId,
                        'leave_type_id'  => $lt['id'],
                        'year'           => date('Y'),
                        'entitled_days'  => $lt['days_per_year'],
                        'used_days'      => 0,
                        'remaining_days' => $lt['days_per_year'],
                    ]);
                }
            }

            $this->db->commit();

            AuditLogger::log('employee_created', 'employees', $employeeId, 'employee',
                "New employee created: {$newCode}", [], $data);

            $this->flash('success', "Employee {$data['first_name']} {$data['last_name']} ({$newCode}) created successfully.");
            $this->redirect("/employees/{$employeeId}");

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('Employee creation failed: ' . $e->getMessage());
            $this->flash('danger', 'Failed to create employee. Please try again.');
            $this->redirect('/employees/create');
        }
    }

    // --------------------------------------------------------
    // EDIT FORM
    // --------------------------------------------------------
    public function edit(string $id): void
    {
        $this->requirePermission('employees.edit');

        $employee     = $this->db->fetchOne("SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$employee) $this->abort(404);

        $departments  = $this->db->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
        $designations = $this->db->fetchAll("SELECT id, title, department_id FROM designations WHERE is_active = 1 ORDER BY title");
        $managers     = $this->db->fetchAll("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM employees WHERE status = 'active' AND id != ? AND deleted_at IS NULL ORDER BY first_name", [$id]);

        $this->view('employees.edit', compact('employee', 'departments', 'designations', 'managers') + [
            'title'      => 'Edit Employee',
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    // --------------------------------------------------------
    // UPDATE EMPLOYEE
    // --------------------------------------------------------
    public function update(string $id): void
    {
        $this->requirePermission('employees.edit');
        $this->verifyCsrf();

        $employee = $this->db->fetchOne("SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$employee) $this->abort(404);

        $data = $this->getAllInput();

        $this->db->beginTransaction();
        try {
            $oldData = $employee;

            $updateData = [
                'first_name'        => $this->sanitize($data['first_name'] ?? $employee['first_name']),
                'last_name'         => $this->sanitize($data['last_name'] ?? $employee['last_name']),
                'father_name'       => $this->sanitize($data['father_name'] ?? ''),
                'department_id'     => (int)($data['department_id'] ?? $employee['department_id']),
                'designation_id'    => (int)($data['designation_id'] ?? $employee['designation_id']),
                'reporting_to'      => !empty($data['reporting_to']) ? (int)$data['reporting_to'] : null,
                'mobile'            => $data['mobile'] ?? $employee['mobile'],
                'personal_email'    => $data['personal_email'] ?? $employee['personal_email'],
                'present_address'   => $this->sanitize($data['present_address'] ?? ''),
                'permanent_address' => $this->sanitize($data['permanent_address'] ?? ''),
                'bank_name'         => $this->sanitize($data['bank_name'] ?? ''),
                'bank_account'      => $data['bank_account'] ?? null,
                'contract_type'     => $data['contract_type'] ?? $employee['contract_type'],
                'contract_end_date' => $data['contract_end_date'] ?: null,
            ];

            // Handle profile photo update
            if (!empty($_FILES['profile_photo']['tmp_name'])) {
                $updateData['profile_photo'] = $this->uploadFile($_FILES['profile_photo'], 'photos');
            }

            $this->db->update('employees', $updateData, 'id = ?', [$id]);
            $this->db->commit();

            AuditLogger::log('employee_updated', 'employees', (int)$id, 'employee',
                "Employee updated: {$employee['employee_code']}", $oldData, $updateData);

            $this->flash('success', 'Employee updated successfully.');
            $this->redirect("/employees/{$id}");

        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->flash('danger', 'Update failed. Please try again.');
            $this->redirect("/employees/{$id}/edit");
        }
    }

    // --------------------------------------------------------
    // TERMINATE / SOFT DELETE
    // --------------------------------------------------------
    public function terminate(string $id): void
    {
        $this->requirePermission('employees.delete');
        $this->verifyCsrf();

        $employee = $this->db->fetchOne("SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$employee) $this->abort(404);

        $reason          = $this->sanitize($this->input('reason', ''));
        $separationDate  = $this->input('separation_date', date('Y-m-d'));

        $this->db->update('employees', [
            'status' => 'terminated',
            'separation_date'   => $separationDate,
            'separation_reason' => $reason,
            'is_active'         => 0,
        ], 'id = ?', [$id]);

        // Disable user account
        if ($employee['user_id']) {
            $this->db->update('users', ['is_active' => 0], 'id = ?', [$employee['user_id']]);
        }

        AuditLogger::log('employee_terminated', 'employees', (int)$id, 'employee',
            "Employee terminated: {$employee['employee_code']} - Reason: {$reason}",
            [], [], 'warning');

        $this->flash('warning', 'Employee has been terminated successfully.');
        $this->redirect('/employees');
    }

    // --------------------------------------------------------
    // EXPORT TO EXCEL
    // --------------------------------------------------------
    public function export(): void
    {
        $this->requirePermission('employees.export');

        $employees = $this->db->fetchAll(
            "SELECT e.employee_code, CONCAT(e.first_name, ' ', e.last_name) AS name,
                    e.cnic, e.mobile, e.official_email, e.join_date, e.status,
                    e.contract_type, e.basic_salary, e.bank_account,
                    d.name AS department, des.title AS designation
             FROM employees e
             JOIN departments d ON e.department_id = d.id
             JOIN designations des ON e.designation_id = des.id
             WHERE e.deleted_at IS NULL AND e.status = 'active'
             ORDER BY e.first_name"
        );

        AuditLogger::log('employees_exported', 'employees', null, null, 'Employee list exported');

        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($output, ['Employee Code','Name','CNIC','Mobile','Email','Join Date','Status','Contract','Basic Salary','Bank Account','Department','Designation']);

        foreach ($employees as $emp) {
            fputcsv($output, array_values($emp));
        }

        fclose($output);
        exit;
    }

    // --------------------------------------------------------
    // HELPERS
    // --------------------------------------------------------
    private function getAllInput(): array
    {
        return array_map(function($val) {
            return is_string($val) ? trim($val) : $val;
        }, $_POST);
    }

    private function uploadFile(array $file, string $folder): ?string
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $maxSize      = 5 * 1024 * 1024; // 5MB

        if ($file['error'] !== UPLOAD_ERR_OK)   return null;
        if ($file['size'] > $maxSize)            return null;
        if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) return null;

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        $path     = getenv('STORAGE_PATH') . "/uploads/{$folder}/" . date('Y/m');

        if (!is_dir($path)) mkdir($path, 0755, true);

        $fullPath = "{$path}/{$filename}";
        move_uploaded_file($file['tmp_name'], $fullPath);

        return "{$folder}/" . date('Y/m') . "/{$filename}";
    }

    private function generateTempPassword(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
        return substr(str_shuffle($chars), 0, 12);
    }

    private function getEmployeeStats(): array
    {
        return [
            'total'       => $this->db->fetchColumn("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND status = 'active'"),
            'new_this_month' => $this->db->fetchColumn("SELECT COUNT(*) FROM employees WHERE strftime('%m',join_date) = strftime('%m','now') AND strftime('%Y',join_date) = strftime('%Y','now') AND deleted_at IS NULL"),
            'on_leave'    => $this->db->fetchColumn("SELECT COUNT(DISTINCT employee_id) FROM leave_applications WHERE status = 'approved' AND CURDATE() BETWEEN from_date AND to_date"),
            'departments' => $this->db->fetchColumn("SELECT COUNT(DISTINCT department_id) FROM employees WHERE deleted_at IS NULL AND status = 'active'"),
        ];
    }
    public function payslips(int $id): void
    {
        $this->requirePermission('payroll.view');
        $this->redirect("/payroll?employee={$id}");
    }

}
