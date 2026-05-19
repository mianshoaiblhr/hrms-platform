<?php
namespace App\Models;

// =====================================================
// User Model
// =====================================================
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'employee_id','username','email','password','role_id',
        'status','must_change_password','two_factor_enabled',
        'two_factor_secret','remember_token','password_changed_at',
        'last_login_at','last_login_ip','failed_login_attempts','locked_until',
    ];
    protected array $hidden = ['password','remember_token','two_factor_secret','reset_token'];

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.username = ? AND u.deleted_at IS NULL",
            [$username]
        ) ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
            [$email]
        ) ?: null;
    }

    public function getWithRole(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                    e.first_name, e.last_name, e.employee_code, e.avatar,
                    d.name AS department_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN employees e ON u.employee_id = e.id
             LEFT JOIN departments d ON e.department_id = d.id
             WHERE u.id = ? AND u.deleted_at IS NULL",
            [$id]
        ) ?: null;
    }

    public function getUserPermissions(int $userId): array
    {
        $user = $this->find($userId);
        if (!$user) return [];
        return $this->db->fetchAll(
            "SELECT p.name FROM permissions p
             INNER JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?",
            [$user['role_id']]
        );
    }

    public function listAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['u.deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s,$s,$s,$s]);
        }
        if (!empty($filters['role_id'])) { $where[] = "u.role_id = ?"; $params[] = $filters['role_id']; }
        if (!empty($filters['status']))  { $where[] = "u.status = ?";   $params[] = $filters['status']; }
        $whereStr = implode(' AND ', $where);
        $base = "FROM users u
                 LEFT JOIN roles r ON u.role_id = r.id
                 LEFT JOIN employees e ON u.employee_id = e.id
                 WHERE $whereStr";
        return $this->db->paginate(
            "SELECT u.id,u.username,u.email,u.status,u.last_login_at,u.created_at,
                    r.name AS role_name, e.first_name, e.last_name, e.employee_code $base ORDER BY u.username",
            "SELECT COUNT(*) $base", $params, $page, $perPage
        );
    }
}

// =====================================================
// Attendance Model
// =====================================================
class Attendance extends Model
{
    protected string $table = 'attendance';
    protected array $fillable = [
        'employee_id','attendance_date','check_in','check_out',
        'status','late_minutes','overtime_minutes','working_hours',
        'remarks','source','created_by',
    ];

    public function getMonthlyAttendance(int $employeeId, int $year, int $month): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = ?
             AND YEAR(attendance_date) = ? AND MONTH(attendance_date) = ?
             AND deleted_at IS NULL ORDER BY attendance_date",
            [$employeeId, $year, $month]
        );
    }

    public function getTodayStatus(int $employeeId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() AND deleted_at IS NULL",
            [$employeeId]
        ) ?: null;
    }

    public function getSummary(int $employeeId, string $from, string $to): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total_days,
                SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) AS absent,
                SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN status='half_day' THEN 1 ELSE 0 END) AS half_day,
                SUM(CASE WHEN status='on_leave' THEN 1 ELSE 0 END) AS on_leave,
                COALESCE(SUM(overtime_minutes),0) AS total_overtime,
                COALESCE(SUM(late_minutes),0) AS total_late
             FROM attendance WHERE employee_id = ?
             AND attendance_date BETWEEN ? AND ? AND deleted_at IS NULL",
            [$employeeId, $from, $to]
        ) ?: [];
    }

    public function listWithEmployee(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = ['a.deleted_at IS NULL','e.deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['date'])) { $where[] = "a.attendance_date = ?"; $params[] = $filters['date']; }
        if (!empty($filters['from'])) { $where[] = "a.attendance_date >= ?"; $params[] = $filters['from']; }
        if (!empty($filters['to']))   { $where[] = "a.attendance_date <= ?"; $params[] = $filters['to']; }
        if (!empty($filters['department_id'])) { $where[] = "e.department_id = ?"; $params[] = $filters['department_id']; }
        if (!empty($filters['employee_id']))   { $where[] = "a.employee_id = ?";   $params[] = $filters['employee_id']; }
        if (!empty($filters['status']))        { $where[] = "a.status = ?";        $params[] = $filters['status']; }
        $whereStr = implode(' AND ', $where);
        $base = "FROM attendance a
                 JOIN employees e ON a.employee_id = e.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE $whereStr";
        return $this->db->paginate(
            "SELECT a.*, CONCAT(e.first_name,' ',e.last_name) AS employee_name,
                    e.employee_code, d.name AS department_name $base ORDER BY a.attendance_date DESC, e.first_name",
            "SELECT COUNT(*) $base", $params, $page, $perPage
        );
    }
}

// =====================================================
// Leave Model
// =====================================================
class Leave extends Model
{
    protected string $table = 'leave_applications';
    protected array $fillable = [
        'employee_id','leave_type_id','from_date','to_date','days',
        'reason','status','approved_by','approved_at','rejection_reason',
        'half_day','half_day_type','documents',
    ];

    public function getWithDetails(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT la.*, lt.name AS leave_type, lt.color,
                    CONCAT(e.first_name,' ',e.last_name) AS employee_name,
                    e.employee_code, e.avatar,
                    d.name AS department_name,
                    CONCAT(au.first_name,' ',au.last_name) AS approved_by_name
             FROM leave_applications la
             JOIN employees e ON la.employee_id = e.id
             JOIN leave_types lt ON la.leave_type_id = lt.id
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN employees au ON la.approved_by = au.id
             WHERE la.id = ? AND la.deleted_at IS NULL",
            [$id]
        ) ?: null;
    }

    public function getPendingCount(): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM leave_applications WHERE status='pending' AND deleted_at IS NULL"
        );
    }

    public function listWithDetails(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = ['la.deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['employee_id'])) { $where[] = "la.employee_id = ?"; $params[] = $filters['employee_id']; }
        if (!empty($filters['status']))      { $where[] = "la.status = ?";      $params[] = $filters['status']; }
        if (!empty($filters['from']))        { $where[] = "la.from_date >= ?";  $params[] = $filters['from']; }
        if (!empty($filters['to']))          { $where[] = "la.to_date <= ?";    $params[] = $filters['to']; }
        if (!empty($filters['department_id'])) { $where[] = "e.department_id = ?"; $params[] = $filters['department_id']; }
        $whereStr = implode(' AND ', $where);
        $base = "FROM leave_applications la
                 JOIN employees e ON la.employee_id = e.id
                 JOIN leave_types lt ON la.leave_type_id = lt.id
                 LEFT JOIN departments d ON e.department_id = d.id
                 WHERE $whereStr";
        return $this->db->paginate(
            "SELECT la.*, lt.name AS leave_type, lt.color,
                    CONCAT(e.first_name,' ',e.last_name) AS employee_name,
                    e.employee_code, d.name AS department_name $base ORDER BY la.created_at DESC",
            "SELECT COUNT(*) $base", $params, $page, $perPage
        );
    }
}

// =====================================================
// Department Model
// =====================================================
class Department extends Model
{
    protected string $table = 'departments';
    protected array $fillable = ['name','code','parent_id','manager_id','description','status'];
    public function getAllActive(): array
    {
        return $this->db->fetchAll("SELECT * FROM departments WHERE status='active' AND deleted_at IS NULL ORDER BY name");
    }
}

// =====================================================
// Designation Model
// =====================================================
class Designation extends Model
{
    protected string $table = 'designations';
    protected array $fillable = ['title','department_id','grade','status'];
    public function getByDepartment(int $deptId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM designations WHERE department_id = ? AND status='active' AND deleted_at IS NULL ORDER BY title",
            [$deptId]
        );
    }
}

// =====================================================
// Role Model
// =====================================================
class Role extends Model
{
    protected string $table = 'roles';
    protected bool $softDelete = false;
    protected array $fillable = ['name','slug','description','is_system','status'];

    public function getAllActive(): array
    {
        return $this->db->fetchAll("SELECT * FROM roles WHERE status='active' ORDER BY name");
    }

    public function getPermissions(int $roleId): array
    {
        return $this->db->fetchAll(
            "SELECT p.* FROM permissions p
             JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = ? ORDER BY p.module, p.name",
            [$roleId]
        );
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        foreach ($permissionIds as $pid) {
            $this->db->insert('role_permissions', ['role_id' => $roleId, 'permission_id' => (int)$pid]);
        }
    }
}

// =====================================================
// Notification Model
// =====================================================
class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = ['user_id','type','title','message','data','read_at','link'];

    public function getUnread(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL
             ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->query(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }

    public function getUnreadCount(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }

    public static function send(int $userId, string $type, string $title, string $message, string $link = ''): void
    {
        $db = \App\Core\Database::getInstance();
        $db->insert('notifications', [
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
