<?php
namespace App\Models;

use App\Core\Database;

class Employee extends Model
{
    protected string $table = 'employees';
    protected array $fillable = [
        'employee_code','user_id','first_name','last_name','father_name','cnic',
        'date_of_birth','gender','marital_status','nationality','religion',
        'personal_email','personal_phone','emergency_contact_name','emergency_contact_phone',
        'present_address','permanent_address','department_id','designation_id',
        'employment_type','joining_date','confirmation_date','resignation_date',
        'termination_date','status','bank_name','bank_account','bank_branch',
        'eobi_number','pessi_number','ntn_number','avatar','notes',
    ];

    public function getFullName(int $id): string
    {
        $e = $this->find($id);
        return $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
    }

    public function getWithDepartment(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT e.*, d.name AS department_name, des.title AS designation_title,
                    u.username, u.email
             FROM employees e
             LEFT JOIN departments d ON e.department_id = d.id
             LEFT JOIN designations des ON e.designation_id = des.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.id = ? AND e.deleted_at IS NULL",
            [$id]
        ) ?: null;
    }

    public function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = ['e.deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.cnic LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }
        if (!empty($filters['department_id'])) {
            $where[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['employment_type'])) {
            $where[] = "e.employment_type = ?";
            $params[] = $filters['employment_type'];
        }
        $whereStr = implode(' AND ', $where);
        $base = "FROM employees e
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN designations des ON e.designation_id = des.id
                 WHERE $whereStr";
        return $this->db->paginate(
            "SELECT e.*, d.name AS department_name, des.title AS designation_title $base ORDER BY e.first_name",
            "SELECT COUNT(*) $base",
            $params, $page, $perPage
        );
    }

    public function getActiveCount(): int
    {
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM employees WHERE status='active' AND deleted_at IS NULL");
    }

    public function getDepartmentStats(): array
    {
        return $this->db->fetchAll(
            "SELECT d.name, COUNT(e.id) AS total
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.status='active' AND e.deleted_at IS NULL
             WHERE d.deleted_at IS NULL
             GROUP BY d.id ORDER BY total DESC LIMIT 10"
        );
    }

    public function generateEmployeeCode(): string
    {
        $last = $this->db->fetchColumn("SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1");
        if ($last) {
            preg_match('/(\d+)$/', $last, $m);
            $num = isset($m[1]) ? (int)$m[1] + 1 : 1;
        } else {
            $num = 1;
        }
        $prefix = env('EMPLOYEE_CODE_PREFIX', 'EMP');
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
