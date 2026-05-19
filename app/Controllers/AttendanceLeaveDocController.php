<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\AuditLogger;
use App\Services\ExportService;

// =========================================================
// Attendance Controller
// =========================================================
class AttendanceController extends Controller
{
    private Database $db;

    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('attendance.view');
        $filters = [
            'date'          => $this->input('date', date('Y-m-d')),
            'from'          => $this->input('from', ''),
            'to'            => $this->input('to', ''),
            'department_id' => (int)$this->input('dept', 0),
            'status'        => $this->input('status', ''),
            'employee_id'   => (int)$this->input('emp', 0),
        ];
        $page    = (int)$this->input('page', 1);
        $model   = new \App\Models\Attendance();
        $data    = $model->listWithEmployee($filters, $page);
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE status='active' AND deleted_at IS NULL ORDER BY name");
        $employees   = $this->db->fetchAll("SELECT id,CONCAT(first_name,' ',last_name) AS name FROM employees WHERE status='active' AND deleted_at IS NULL ORDER BY first_name");
        $this->view('attendance/index', compact('data','filters','departments','employees'));
    }

    public function store(): void
    {
        $this->requirePermission('attendance.create');
        $this->verifyCsrf();
        $data = $this->validate([
            'employee_id'     => 'required|numeric',
            'attendance_date' => 'required|date',
            'check_in'        => 'required',
            'status'          => 'required',
        ]);
        $data['check_out']      = $this->input('check_out', null);
        $data['late_minutes']   = (int)$this->input('late_minutes', 0);
        $data['overtime_minutes'] = (int)$this->input('overtime_minutes', 0);
        $data['remarks']        = $this->input('remarks', '');
        $data['source']         = 'manual';
        $data['created_by']     = Auth::getInstance()->user()['id'];

        // Calculate working hours
        if ($data['check_out']) {
            $in  = strtotime($data['attendance_date'] . ' ' . $data['check_in']);
            $out = strtotime($data['attendance_date'] . ' ' . $data['check_out']);
            $data['working_hours'] = round(($out - $in) / 3600, 2);
        }

        // Duplicate check
        $existing = $this->db->fetchColumn(
            "SELECT id FROM attendance WHERE employee_id=? AND attendance_date=? AND deleted_at IS NULL",
            [$data['employee_id'], $data['attendance_date']]
        );
        if ($existing) {
            $this->flash('error', 'Attendance already recorded for this employee on this date.');
            $this->redirect('/attendance');
        }

        $this->db->insert('attendance', $data);
        AuditLogger::log('attendance.create', 'attendance', null, null, $data);
        $this->flash('success', 'Attendance recorded successfully.');
        $this->redirect('/attendance');
    }

    public function update(): void
    {
        $this->requirePermission('attendance.edit');
        $this->verifyCsrf();
        $id   = (int)$this->input('id', 0);
        $data = [
            'check_in'          => $this->input('check_in'),
            'check_out'         => $this->input('check_out'),
            'status'            => $this->input('status'),
            'late_minutes'      => (int)$this->input('late_minutes', 0),
            'overtime_minutes'  => (int)$this->input('overtime_minutes', 0),
            'remarks'           => $this->input('remarks', ''),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
        $this->db->update('attendance', $data, 'id = ?', [$id]);
        AuditLogger::log('attendance.update', 'attendance', $id);
        $this->json(['success' => true, 'message' => 'Updated successfully']);
    }

    public function export(): void
    {
        $this->requirePermission('attendance.export');
        $from = $this->input('from', date('Y-m-01'));
        $to   = $this->input('to', date('Y-m-d'));
        $rows = $this->db->fetchAll(
            "SELECT e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS name,
                    d.name AS dept, a.attendance_date, a.check_in, a.check_out,
                    a.status, a.working_hours, a.late_minutes, a.overtime_minutes
             FROM attendance a JOIN employees e ON a.employee_id=e.id
             LEFT JOIN departments d ON e.department_id=d.id
             WHERE a.attendance_date BETWEEN ? AND ? AND a.deleted_at IS NULL
             ORDER BY a.attendance_date, e.first_name",
            [$from, $to]
        );
        $headers = ['Emp Code','Employee Name','Department','Date','Check In','Check Out','Status','Hours','Late Min','OT Min'];
        $export  = new ExportService();
        $export->exportCSV($rows, $headers, 'attendance_' . $from . '_' . $to);
    }

    public function bulkImport(): void
    {
        $this->requirePermission('attendance.import');
        $this->verifyCsrf();
        // CSV import logic
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please upload a valid CSV file.');
            $this->redirect('/attendance');
        }
        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        fgetcsv($handle); // skip header
        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) continue;
            $emp = $this->db->fetchColumn("SELECT id FROM employees WHERE employee_code=? AND deleted_at IS NULL", [trim($row[0])]);
            if (!$emp) continue;
            $existing = $this->db->fetchColumn("SELECT id FROM attendance WHERE employee_id=? AND attendance_date=? AND deleted_at IS NULL", [$emp, trim($row[1])]);
            if (!$existing) {
                $this->db->insert('attendance', [
                    'employee_id'    => $emp,
                    'attendance_date'=> trim($row[1]),
                    'check_in'       => trim($row[2]) ?: null,
                    'check_out'      => trim($row[3]) ?: null,
                    'status'         => trim($row[4] ?? 'present'),
                    'source'         => 'import',
                    'created_by'     => Auth::getInstance()->user()['id'],
                ]);
                $imported++;
            }
        }
        fclose($handle);
        $this->flash('success', "$imported attendance records imported.");
        $this->redirect('/attendance');
    }
}

// =========================================================
// Leave Controller
// =========================================================
class LeaveController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('leaves.view');
        $user   = Auth::getInstance()->user();
        $filters = [
            'status'        => $this->input('status', ''),
            'from'          => $this->input('from', ''),
            'to'            => $this->input('to', ''),
            'department_id' => (int)$this->input('dept', 0),
            'employee_id'   => Auth::getInstance()->can('leaves.view_all') ? (int)$this->input('emp', 0) : (int)$user['employee_id'],
        ];
        $page  = (int)$this->input('page', 1);
        $model = new \App\Models\Leave();
        $data  = $model->listWithDetails($filters, $page);
        $leaveTypes  = $this->db->fetchAll("SELECT * FROM leave_types WHERE status='active' ORDER BY name");
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE status='active' AND deleted_at IS NULL ORDER BY name");
        $employees   = Auth::getInstance()->can('leaves.view_all')
            ? $this->db->fetchAll("SELECT id,CONCAT(first_name,' ',last_name) AS name FROM employees WHERE status='active' AND deleted_at IS NULL ORDER BY first_name")
            : [];
        $this->view('leaves/index', compact('data','filters','leaveTypes','departments','employees'));
    }

    public function apply(): void
    {
        $user = Auth::getInstance()->user();
        $employeeId = (int)$user['employee_id'];
        if (!$employeeId) { $this->abort(403, 'No employee profile linked.'); }
        $leaveTypes = $this->db->fetchAll("SELECT * FROM leave_types WHERE status='active' ORDER BY name");
        $balances = $this->db->fetchAll(
            "SELECT lb.*, lt.name FROM leave_balances lb JOIN leave_types lt ON lb.leave_type_id=lt.id WHERE lb.employee_id=? AND lb.year=YEAR(NOW())",
            [$employeeId]
        );
        $this->view('leaves/apply', compact('leaveTypes','balances'));
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $user = Auth::getInstance()->user();
        $employeeId = (int)$user['employee_id'];
        if (!$employeeId) { $this->json(['success' => false, 'message' => 'No employee profile.']); }

        $data = $this->validate([
            'leave_type_id' => 'required|numeric',
            'from_date'     => 'required|date',
            'to_date'       => 'required|date',
            'reason'        => 'required',
        ]);

        $from = new \DateTime($data['from_date']);
        $to   = new \DateTime($data['to_date']);
        if ($to < $from) {
            $this->flash('error', 'End date cannot be before start date.');
            $this->redirect('/leaves/apply');
        }

        // Calculate business days
        $days = 0;
        $d = clone $from;
        while ($d <= $to) {
            if ($d->format('N') < 6) $days++;
            $d->modify('+1 day');
        }

        // Check balance
        $balance = $this->db->fetchOne(
            "SELECT * FROM leave_balances WHERE employee_id=? AND leave_type_id=? AND year=YEAR(NOW())",
            [$employeeId, $data['leave_type_id']]
        );
        if ($balance && $balance['remaining_days'] < $days) {
            $this->flash('error', "Insufficient leave balance. Available: {$balance['remaining_days']} days.");
            $this->redirect('/leaves/apply');
        }

        // Overlap check
        $overlap = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM leave_applications WHERE employee_id=? AND status NOT IN ('rejected','cancelled')
             AND deleted_at IS NULL AND ((from_date BETWEEN ? AND ?) OR (to_date BETWEEN ? AND ?) OR (? BETWEEN from_date AND to_date))",
            [$employeeId, $data['from_date'], $data['to_date'], $data['from_date'], $data['to_date'], $data['from_date']]
        );
        if ($overlap > 0) {
            $this->flash('error', 'You have an overlapping leave application for these dates.');
            $this->redirect('/leaves/apply');
        }

        $id = $this->db->insert('leave_applications', [
            'employee_id'    => $employeeId,
            'leave_type_id'  => $data['leave_type_id'],
            'from_date'      => $data['from_date'],
            'to_date'        => $data['to_date'],
            'days'           => $days,
            'reason'         => sanitize($data['reason']),
            'status'         => 'pending',
            'half_day'       => (int)$this->input('half_day', 0),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        AuditLogger::log('leave.apply', 'leave_applications', $id);
        $this->flash('success', 'Leave application submitted successfully.');
        $this->redirect('/leaves');
    }

    public function approve(): void
    {
        $this->requirePermission('leaves.approve');
        $this->verifyCsrf();
        $id = (int)$this->input('id', 0);
        $leave = $this->db->fetchOne("SELECT * FROM leave_applications WHERE id=? AND deleted_at IS NULL", [$id]);
        if (!$leave || $leave['status'] !== 'pending') {
            $this->json(['success' => false, 'message' => 'Invalid request']);
        }
        $approver = Auth::getInstance()->user();
        $employeeId = (int)$approver['employee_id'];

        $this->db->update('leave_applications', [
            'status'      => 'approved',
            'approved_by' => $employeeId,
            'approved_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        // Deduct from balance
        $this->db->query(
            "UPDATE leave_balances SET used_days=used_days+?, remaining_days=remaining_days-?
             WHERE employee_id=? AND leave_type_id=? AND year=YEAR(NOW())",
            [$leave['days'], $leave['days'], $leave['employee_id'], $leave['leave_type_id']]
        );

        // Update attendance records to on_leave
        $from = new \DateTime($leave['from_date']);
        $to   = new \DateTime($leave['to_date']);
        for ($d = clone $from; $d <= $to; $d->modify('+1 day')) {
            if ($d->format('N') >= 6) continue;
            $dateStr = $d->format('Y-m-d');
            $existing = $this->db->fetchColumn("SELECT id FROM attendance WHERE employee_id=? AND attendance_date=? AND deleted_at IS NULL", [$leave['employee_id'], $dateStr]);
            if (!$existing) {
                $this->db->insert('attendance', ['employee_id' => $leave['employee_id'], 'attendance_date' => $dateStr, 'status' => 'on_leave', 'source' => 'system']);
            }
        }

        AuditLogger::log('leave.approve', 'leave_applications', $id);
        $this->json(['success' => true, 'message' => 'Leave approved successfully']);
    }

    public function reject(): void
    {
        $this->requirePermission('leaves.approve');
        $this->verifyCsrf();
        $id     = (int)$this->input('id', 0);
        $reason = sanitize($this->input('reason', ''));
        $this->db->update('leave_applications', ['status' => 'rejected', 'rejection_reason' => $reason], 'id = ?', [$id]);
        AuditLogger::log('leave.reject', 'leave_applications', $id);
        $this->json(['success' => true, 'message' => 'Leave rejected.']);
    }

    public function balances(): void
    {
        $this->requirePermission('leaves.view');
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE status='active' AND deleted_at IS NULL ORDER BY name");
        $leaveTypes  = $this->db->fetchAll("SELECT * FROM leave_types WHERE status='active' ORDER BY name");
        $year = (int)$this->input('year', date('Y'));
        $dept = (int)$this->input('dept', 0);
        $baseQ = "FROM leave_balances lb
                  JOIN employees e ON lb.employee_id=e.id
                  JOIN leave_types lt ON lb.leave_type_id=lt.id
                  LEFT JOIN departments d ON e.department_id=d.id
                  WHERE lb.year=? AND e.deleted_at IS NULL" . ($dept ? " AND e.department_id=$dept" : '');
        $balances = $this->db->paginate(
            "SELECT lb.*, e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS employee_name,
                    lt.name AS leave_type, lt.color, d.name AS dept_name $baseQ ORDER BY e.first_name, lt.name",
            "SELECT COUNT(*) $baseQ", [$year], (int)$this->input('page', 1)
        );
        $this->view('leaves/balances', compact('balances','departments','leaveTypes','year','dept'));
    }

    public function calendar(): void
    {
        $this->requirePermission('leaves.view');
        $year  = (int)$this->input('year', date('Y'));
        $month = (int)$this->input('month', (int)date('m'));
        $user  = Auth::getInstance()->user();
        $empFilter = Auth::getInstance()->can('leaves.view_all') ? '' : " AND la.employee_id=" . (int)$user['employee_id'];
        $leaves = $this->db->fetchAll(
            "SELECT la.from_date, la.to_date, la.status, lt.name AS leave_type, lt.color,
                    CONCAT(e.first_name,' ',e.last_name) AS employee_name
             FROM leave_applications la
             JOIN employees e ON la.employee_id=e.id
             JOIN leave_types lt ON la.leave_type_id=lt.id
             WHERE YEAR(la.from_date)=? AND MONTH(la.from_date)=? AND la.status!='rejected' AND la.deleted_at IS NULL $empFilter",
            [$year, $month]
        );
        $this->view('leaves/calendar', compact('leaves','year','month'));
    }
}

// =========================================================
// Document Controller
// =========================================================
class DocumentController extends Controller
{
    private Database $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void
    {
        $this->requirePermission('documents.view');
        $user   = Auth::getInstance()->user();
        $filters = ['category_id' => (int)$this->input('cat', 0), 'search' => $this->input('search', '')];
        $where  = ['d.deleted_at IS NULL'];
        $params = [];

        if (!Auth::getInstance()->can('documents.view_all')) {
            $where[] = "d.employee_id = ?";
            $params[] = (int)$user['employee_id'];
        } else {
            if ($filters['category_id']) { $where[] = "d.category_id=?"; $params[] = $filters['category_id']; }
        }
        if ($filters['search']) { $where[] = "(d.title LIKE ? OR d.description LIKE ?)"; $s = '%' . $filters['search'] . '%'; $params = array_merge($params, [$s,$s]); }

        $base = "FROM documents d LEFT JOIN document_categories dc ON d.category_id=dc.id LEFT JOIN employees e ON d.employee_id=e.id WHERE " . implode(' AND ', $where);
        $documents = $this->db->paginate(
            "SELECT d.*, dc.name AS category_name, CONCAT(e.first_name,' ',e.last_name) AS employee_name $base ORDER BY d.created_at DESC",
            "SELECT COUNT(*) $base", $params, (int)$this->input('page', 1)
        );
        $categories = $this->db->fetchAll("SELECT * FROM document_categories WHERE deleted_at IS NULL ORDER BY name");
        $this->view('documents/index', compact('documents','categories','filters'));
    }

    public function upload(): void
    {
        $this->requirePermission('documents.upload');
        $this->verifyCsrf();
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please select a file to upload.');
            $this->redirect('/documents');
        }
        $uploader = new \App\Services\FileUploadService();
        $result = $uploader->upload($_FILES['file'], 'documents');
        if (!$result['success']) {
            $this->flash('error', $result['error']);
            $this->redirect('/documents');
        }
        $user = Auth::getInstance()->user();
        $this->db->insert('documents', [
            'employee_id'  => (int)$this->input('employee_id', $user['employee_id']),
            'category_id'  => (int)$this->input('category_id', 0) ?: null,
            'title'        => sanitize($this->input('title', $result['original'])),
            'description'  => sanitize($this->input('description', '')),
            'file_name'    => $result['filename'],
            'original_name'=> $result['original'],
            'file_path'    => $result['path'],
            'file_size'    => $result['size'],
            'mime_type'    => $result['mime'],
            'uploaded_by'  => (int)$user['id'],
            'is_private'   => (int)$this->input('is_private', 0),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        AuditLogger::log('document.upload', 'documents', null, null, ['file' => $result['original']]);
        $this->flash('success', 'Document uploaded successfully.');
        $this->redirect('/documents');
    }

    public function download(int $id): void
    {
        $this->requirePermission('documents.download');
        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=? AND deleted_at IS NULL", [$id]);
        if (!$doc) { $this->abort(404, 'Document not found'); }
        $user = Auth::getInstance()->user();
        // Record-level access: private docs only for owner/admin
        if ($doc['is_private'] && $doc['employee_id'] != $user['employee_id'] && !Auth::getInstance()->can('documents.view_all')) {
            $this->abort(403, 'Access denied');
        }
        $filePath = UPLOAD_PATH . DS . str_replace('/', DS, $doc['file_path']);
        if (!file_exists($filePath)) { $this->abort(404, 'File not found on disk'); }
        $this->db->insert('document_access_log', ['document_id' => $id, 'user_id' => (int)$user['id'], 'action' => 'download', 'accessed_at' => date('Y-m-d H:i:s'), 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '']);
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, no-cache');
        readfile($filePath);
        exit;
    }

    public function delete(int $id): void
    {
        $this->requirePermission('documents.delete');
        $this->verifyCsrf();
        $doc = $this->db->fetchOne("SELECT * FROM documents WHERE id=? AND deleted_at IS NULL", [$id]);
        if (!$doc) { $this->json(['success' => false, 'message' => 'Not found']); }
        $this->db->softDelete('documents', $id);
        AuditLogger::log('document.delete', 'documents', $id);
        $this->json(['success' => true, 'message' => 'Document deleted.']);
    }
}
