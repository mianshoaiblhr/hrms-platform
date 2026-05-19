<?php
// ============================================================
// app/Controllers/PayrollController.php
// Pakistan FBR Tax Compliant Payroll Processing
// ============================================================

namespace App\Controllers;

use App\Core\Controller;
use App\Core\AuditLogger;
use App\Core\Session;

class PayrollController extends Controller
{
    // --------------------------------------------------------
    // PAYROLL PERIODS LIST
    // --------------------------------------------------------
    public function index(): void
    {
        $this->requirePermission('payroll.view');

        $periods = $this->db->fetchAll(
            "SELECT pp.*, 
                    u1.full_name AS processed_by_name,
                    u2.full_name AS approved_by_name
             FROM payroll_periods pp
             LEFT JOIN users u1 ON pp.processed_by = u1.id
             LEFT JOIN users u2 ON pp.approved_by = u2.id
             ORDER BY pp.year DESC, pp.month DESC"
        );

        $this->view('payroll.index', [
            'title'      => 'Payroll Management',
            'periods'    => $periods,
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    // --------------------------------------------------------
    // CREATE PAYROLL PERIOD
    // --------------------------------------------------------
    public function createPeriod(): void
    {
        $this->requirePermission('payroll.create');
        $this->verifyCsrf();

        $month = (int)$this->input('month');
        $year  = (int)$this->input('year');

        if ($month < 1 || $month > 12 || $year < 2020) {
            $this->flash('danger', 'Invalid month or year.');
            $this->redirect('/payroll');
            return;
        }

        // Check if period already exists
        $exists = $this->db->fetchColumn(
            "SELECT id FROM payroll_periods WHERE month = ? AND year = ?",
            [$month, $year]
        );

        if ($exists) {
            $this->flash('warning', 'Payroll period for this month already exists.');
            $this->redirect('/payroll');
            return;
        }

        $startDate = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
        $endDate   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        $name      = date('F Y', mktime(0, 0, 0, $month, 1, $year));

        $periodId = $this->db->insert('payroll_periods', [
            'name'       => $name,
            'month'      => $month,
            'year'       => $year,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'draft',
        ]);

        AuditLogger::log('payroll_period_created', 'payroll', $periodId, 'payroll_period', "Created payroll period: {$name}");

        $this->flash('success', "Payroll period '{$name}' created.");
        $this->redirect("/payroll/{$periodId}");
    }

    // --------------------------------------------------------
    // PAYROLL PERIOD DETAIL
    // --------------------------------------------------------
    public function show(string $id): void
    {
        $this->requirePermission('payroll.view');

        $period = $this->db->fetchOne(
            "SELECT pp.*, u1.full_name AS processed_by_name, u2.full_name AS approved_by_name
             FROM payroll_periods pp
             LEFT JOIN users u1 ON pp.processed_by = u1.id
             LEFT JOIN users u2 ON pp.approved_by = u2.id
             WHERE pp.id = ?",
            [$id]
        );

        if (!$period) $this->abort(404);

        $items = $this->db->fetchAll(
            "SELECT pi.*, 
                    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                    e.employee_code, e.basic_salary AS contracted_salary,
                    d.name AS department_name, des.title AS designation
             FROM payroll_items pi
             JOIN employees e ON pi.employee_id = e.id
             JOIN departments d ON e.department_id = d.id
             JOIN designations des ON e.designation_id = des.id
             WHERE pi.payroll_period_id = ?
             ORDER BY d.name, e.first_name",
            [$id]
        );

        $this->view('payroll.show', [
            'title'      => 'Payroll: ' . $period['name'],
            'period'     => $period,
            'items'      => $items,
            'csrf_token' => Session::csrfToken(),
            'can_process' => $this->auth->can('payroll.create'),
            'can_approve' => $this->auth->can('payroll.approve'),
        ]);
    }

    // --------------------------------------------------------
    // PROCESS PAYROLL (Generate payroll items for all active employees)
    // --------------------------------------------------------
    public function process(string $id): void
    {
        $this->requirePermission('payroll.create');
        $this->verifyCsrf();

        $period = $this->db->fetchOne("SELECT * FROM payroll_periods WHERE id = ?", [$id]);
        if (!$period) $this->abort(404);

        if (!in_array($period['status'], ['draft', 'processing'])) {
            $this->flash('warning', 'This payroll period cannot be reprocessed.');
            $this->redirect("/payroll/{$id}");
            return;
        }

        // Update status
        $this->db->update('payroll_periods', ['status' => 'processing'], 'id = ?', [$id]);

        // Get all active employees
        $employees = $this->db->fetchAll(
            "SELECT e.id, e.basic_salary, e.eobi_number, e.pessi_number,
                    e.provident_fund_eligible, e.ntn, e.tax_exempt,
                    e.join_date, e.department_id
             FROM employees e
             WHERE e.employment_status = 'active' AND e.deleted_at IS NULL"
        );

        $this->db->beginTransaction();
        try {
            $totalGross      = 0;
            $totalDeductions = 0;
            $totalNet        = 0;
            $processedCount  = 0;

            foreach ($employees as $emp) {
                $payrollItem = $this->calculateEmployeePayroll($emp, $period);

                // Delete existing draft item if reprocessing
                $this->db->query(
                    "DELETE FROM payroll_items WHERE payroll_period_id = ? AND employee_id = ?",
                    [$id, $emp['id']]
                );

                $itemId = $this->db->insert('payroll_items', [
                    'payroll_period_id'       => $id,
                    'employee_id'             => $emp['id'],
                    'working_days'            => $payrollItem['working_days'],
                    'present_days'            => $payrollItem['present_days'],
                    'leave_days'              => $payrollItem['leave_days'],
                    'absent_days'             => $payrollItem['absent_days'],
                    'overtime_hours'          => $payrollItem['overtime_hours'],
                    'overtime_amount'         => $payrollItem['overtime_amount'],
                    'basic_salary'            => $payrollItem['basic_salary'],
                    'gross_salary'            => $payrollItem['gross_salary'],
                    'total_earnings'          => $payrollItem['total_earnings'],
                    'total_deductions'        => $payrollItem['total_deductions'],
                    'income_tax'              => $payrollItem['income_tax'],
                    'eobi_employee'           => $payrollItem['eobi_employee'],
                    'eobi_employer'           => $payrollItem['eobi_employer'],
                    'pessi_employee'          => $payrollItem['pessi_employee'],
                    'provident_fund_employee' => $payrollItem['provident_fund'],
                    'loan_deduction'          => $payrollItem['loan_deduction'],
                    'advance_deduction'       => $payrollItem['advance_deduction'],
                    'net_salary'              => $payrollItem['net_salary'],
                    'status'                  => 'draft',
                ]);

                // Insert salary component details
                foreach ($payrollItem['components'] as $comp) {
                    $this->db->insert('payroll_item_details', [
                        'payroll_item_id' => $itemId,
                        'component_id'    => $comp['component_id'],
                        'type'            => $comp['type'],
                        'amount'          => $comp['amount'],
                    ]);
                }

                $totalGross      += $payrollItem['gross_salary'];
                $totalDeductions += $payrollItem['total_deductions'];
                $totalNet        += $payrollItem['net_salary'];
                $processedCount++;
            }

            // Update period totals
            $this->db->update('payroll_periods', [
                'status'           => 'processed',
                'total_employees'  => $processedCount,
                'total_gross'      => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net'        => $totalNet,
                'processed_by'     => $this->auth->id(),
                'processed_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            $this->db->commit();

            AuditLogger::log('payroll_processed', 'payroll', (int)$id, 'payroll_period',
                "Payroll processed for {$period['name']}: {$processedCount} employees, Net: PKR " . number_format($totalNet, 2));

            $this->flash('success', "Payroll processed successfully for {$processedCount} employees. Total Net Payable: PKR " . number_format($totalNet, 2));

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('Payroll processing failed: ' . $e->getMessage());
            $this->db->update('payroll_periods', ['status' => 'draft'], 'id = ?', [$id]);
            $this->flash('danger', 'Payroll processing failed. Please try again.');
        }

        $this->redirect("/payroll/{$id}");
    }

    // --------------------------------------------------------
    // APPROVE PAYROLL
    // --------------------------------------------------------
    public function approve(string $id): void
    {
        $this->requirePermission('payroll.approve');
        $this->verifyCsrf();

        $period = $this->db->fetchOne("SELECT * FROM payroll_periods WHERE id = ?", [$id]);
        if (!$period || $period['status'] !== 'processed') {
            $this->flash('warning', 'This payroll period cannot be approved.');
            $this->redirect("/payroll/{$id}");
            return;
        }

        $this->db->update('payroll_periods', [
            'status'      => 'approved',
            'approved_by' => $this->auth->id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->db->update('payroll_items', ['status' => 'finalized'], 'payroll_period_id = ?', [$id]);

        AuditLogger::log('payroll_approved', 'payroll', (int)$id, 'payroll_period',
            "Payroll approved for {$period['name']}", [], [], 'success');

        $this->flash('success', 'Payroll approved. You can now generate payslips.');
        $this->redirect("/payroll/{$id}");
    }

    // --------------------------------------------------------
    // GENERATE PAYSLIP
    // --------------------------------------------------------
    public function payslip(string $periodId, string $employeeId): void
    {
        $this->requirePermission('payroll.view');

        $item = $this->db->fetchOne(
            "SELECT pi.*,
                    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                    e.employee_code, e.cnic, e.designation_id, e.bank_name,
                    e.bank_account, e.ntn,
                    d.name AS department_name,
                    des.title AS designation,
                    pp.name AS period_name, pp.month, pp.year
             FROM payroll_items pi
             JOIN employees e ON pi.employee_id = e.id
             JOIN departments d ON e.department_id = d.id
             JOIN designations des ON e.designation_id = des.id
             JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
             WHERE pi.payroll_period_id = ? AND pi.employee_id = ?",
            [$periodId, $employeeId]
        );

        if (!$item) $this->abort(404);

        // Restrict: Employee can only view own payslip
        if ($this->auth->roleSlug() === 'employee') {
            $myEmpId = $this->db->fetchColumn("SELECT id FROM employees WHERE user_id = ?", [$this->auth->id()]);
            if ($myEmpId != $employeeId) $this->abort(403);
        }

        $components = $this->db->fetchAll(
            "SELECT pid.*, sc.name AS component_name, sc.code
             FROM payroll_item_details pid
             JOIN salary_components sc ON pid.component_id = sc.id
             WHERE pid.payroll_item_id = ?
             ORDER BY sc.type, sc.sort_order",
            [$item['id']]
        );

        $company = $this->db->fetchOne("SELECT * FROM company_settings LIMIT 1");

        AuditLogger::log('payslip_viewed', 'payroll', (int)$employeeId, 'employee', "Payslip viewed: Period {$periodId}");

        $this->view('payroll.payslip', [
            'title'      => 'Payslip - ' . $item['employee_name'],
            'item'       => $item,
            'components' => $components,
            'company'    => $company,
        ], 'print');
    }

    // --------------------------------------------------------
    // PAYROLL CALCULATION ENGINE (Pakistan Compliant)
    // --------------------------------------------------------
    private function calculateEmployeePayroll(array $employee, array $period): array
    {
        $empId      = $employee['id'];
        $basicSalary = (float)$employee['basic_salary'];

        // 1. Get attendance data for the period
        $attendance = $this->db->fetchOne(
            "SELECT 
                COUNT(*) AS total_days,
                SUM(CASE WHEN status IN ('present') THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN status = 'half_day' THEN 0.5 ELSE 0 END) AS half_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) AS leave_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                SUM(overtime_hours) AS overtime_hours
             FROM attendance
             WHERE employee_id = ? AND date BETWEEN ? AND ?",
            [$empId, $period['start_date'], $period['end_date']]
        );

        // Working days in period (exclude weekends & holidays)
        $workingDays = $this->getWorkingDays($period['start_date'], $period['end_date']);
        $presentDays = ($attendance['present_days'] ?? 0) + ($attendance['half_days'] ?? 0);
        $leaveDays   = $attendance['leave_days'] ?? 0;
        $absentDays  = $attendance['absent_days'] ?? 0;

        // If no attendance records, assume full attendance (manual payroll)
        if (($attendance['total_days'] ?? 0) == 0) {
            $presentDays = $workingDays;
        }

        // 2. Calculate per-day salary
        $perDaySalary = $workingDays > 0 ? ($basicSalary / $workingDays) : 0;

        // 3. Prorated basic salary
        $earnedBasic = $perDaySalary * $presentDays;
        $leavePay    = $perDaySalary * $leaveDays; // Approved leave is paid

        // 4. Get salary structure (allowances etc.)
        $salaryComponents = $this->db->fetchAll(
            "SELECT ess.*, sc.name, sc.code, sc.type, sc.calculation_type, 
                    sc.percentage_of, sc.is_taxable, sc.is_statutory, sc.statutory_type, sc.sort_order
             FROM employee_salary_structure ess
             JOIN salary_components sc ON ess.component_id = sc.id
             WHERE ess.employee_id = ? AND ess.is_active = 1
               AND ess.effective_from <= ? 
               AND (ess.effective_to IS NULL OR ess.effective_to >= ?)
             ORDER BY sc.sort_order",
            [$empId, $period['end_date'], $period['start_date']]
        );

        $earnings   = [];
        $deductions = [];
        $components = [];
        $grossSalary = $earnedBasic + $leavePay;

        // Process non-statutory components
        foreach ($salaryComponents as $comp) {
            if ($comp['is_statutory']) continue;

            $amount = $this->calculateComponentAmount($comp, $earnedBasic, $basicSalary);

            if ($comp['type'] === 'earning') {
                $earnings[] = ['name' => $comp['name'], 'amount' => $amount, 'component_id' => $comp['component_id'] ?? $comp['id']];
                $grossSalary += $amount;
            } elseif ($comp['type'] === 'deduction') {
                $deductions[] = ['name' => $comp['name'], 'amount' => $amount, 'component_id' => $comp['component_id'] ?? $comp['id']];
            }

            $components[] = [
                'component_id' => $comp['component_id'] ?? $comp['id'],
                'type'         => $comp['type'],
                'amount'       => $amount,
            ];
        }

        // 5. Overtime calculation
        $overtimeHours  = (float)($attendance['overtime_hours'] ?? 0);
        $hourlyRate     = $basicSalary / 26 / 8; // Monthly / 26 days / 8 hours
        $overtimeAmount = $overtimeHours * $hourlyRate * 1.5; // 1.5x for overtime

        $grossSalary   += $overtimeAmount;
        $totalEarnings  = $grossSalary;

        // 6. Annual gross for tax calculation
        $annualGross = $grossSalary * 12;

        // 7. Calculate Income Tax (FBR Pakistan Slabs)
        $monthlyTax = 0;
        if (!$employee['tax_exempt']) {
            $annualTax  = $this->calculateFBRTax($annualGross);
            $monthlyTax = $annualTax / 12;
        }

        // 8. EOBI (Employees Old Age Benefits Institution)
        // Employee: 1% of minimum wage (PKR 32,000)
        $eobi_min_wage = 32000;
        $eobiEmployee  = $emp['eobi_number'] ? ($eobi_min_wage * 0.01) : 0;   // PKR 320
        $eobiEmployer  = $emp['eobi_number'] ? ($eobi_min_wage * 0.05) : 0;   // PKR 1,600

        // 9. PESSI (Punjab Employees Social Security Institution)
        // For Punjab: employee 1%, employer 6% of basic
        $pessEmployee = !empty($employee['pessi_number']) ? ($basicSalary * 0.01) : 0;
        $pessEmployer = !empty($employee['pessi_number']) ? ($basicSalary * 0.06) : 0;

        // 10. Provident Fund
        $providentFund = 0;
        if ($employee['provident_fund_eligible']) {
            $providentFund = $basicSalary * 0.0833; // 8.33%
        }

        // 11. Loan deductions
        $loanDeduction = $this->db->fetchColumn(
            "SELECT IFNULL(SUM(monthly_installment), 0) FROM loans 
             WHERE employee_id = ? AND status = 'active'",
            [$empId]
        ) ?? 0;

        // 12. Advance deductions
        $advanceDeduction = $this->db->fetchColumn(
            "SELECT IFNULL(SUM(amount), 0) FROM advances 
             WHERE employee_id = ? AND status = 'approved' AND recovered = 0
               AND MONTH(recovery_month) = ? AND YEAR(recovery_month) = ?",
            [$empId, $period['month'], $period['year']]
        ) ?? 0;

        // 13. Total deductions
        $totalStatutory  = $monthlyTax + $eobiEmployee + $pessEmployee + $providentFund;
        $totalOtherDeductions = array_sum(array_column($deductions, 'amount'));
        $totalDeductions = $totalStatutory + $totalOtherDeductions + $loanDeduction + $advanceDeduction;

        // 14. Net salary
        $netSalary = max(0, $totalEarnings - $totalDeductions);

        return [
            'working_days'     => $workingDays,
            'present_days'     => $presentDays,
            'leave_days'       => $leaveDays,
            'absent_days'      => $absentDays,
            'overtime_hours'   => $overtimeHours,
            'overtime_amount'  => $overtimeAmount,
            'basic_salary'     => $earnedBasic,
            'gross_salary'     => $grossSalary,
            'total_earnings'   => $totalEarnings,
            'income_tax'       => $monthlyTax,
            'eobi_employee'    => $eobiEmployee,
            'eobi_employer'    => $eobiEmployer,
            'pessi_employee'   => $pessEmployee,
            'pessi_employer'   => $pessEmployer,
            'provident_fund'   => $providentFund,
            'loan_deduction'   => $loanDeduction,
            'advance_deduction'=> $advanceDeduction,
            'total_deductions' => $totalDeductions,
            'net_salary'       => $netSalary,
            'components'       => $components,
        ];
    }

    // --------------------------------------------------------
    // FBR INCOME TAX CALCULATION (Pakistan 2024-25)
    // --------------------------------------------------------
    private function calculateFBRTax(float $annualIncome): float
    {
        $slabs = $this->db->fetchAll(
            "SELECT * FROM tax_slabs WHERE fiscal_year = '2024-25' AND is_active = 1 ORDER BY slab_order"
        );

        if (empty($slabs)) {
            // Fallback hardcoded slabs
            return $this->calculateFBRTaxFallback($annualIncome);
        }

        foreach ($slabs as $slab) {
            $from = (float)$slab['income_from'];
            $to   = $slab['income_to'] !== null ? (float)$slab['income_to'] : PHP_FLOAT_MAX;

            if ($annualIncome > $from && $annualIncome <= $to) {
                $taxableExcess = $annualIncome - (float)$slab['over_amount'];
                return (float)$slab['fixed_tax'] + ($taxableExcess * $slab['rate_percentage'] / 100);
            }
        }

        return 0;
    }

    private function calculateFBRTaxFallback(float $annualIncome): float
    {
        if ($annualIncome <= 600000)     return 0;
        if ($annualIncome <= 1200000)    return ($annualIncome - 600000)    * 0.05;
        if ($annualIncome <= 2200000)    return 30000  + ($annualIncome - 1200000) * 0.15;
        if ($annualIncome <= 3200000)    return 180000 + ($annualIncome - 2200000) * 0.25;
        if ($annualIncome <= 4100000)    return 430000 + ($annualIncome - 3200000) * 0.30;
        return 700000 + ($annualIncome - 4100000) * 0.35;
    }

    private function calculateComponentAmount(array $comp, float $earnedBasic, float $contractedBasic): float
    {
        switch ($comp['calculation_type']) {
            case 'fixed':
                return (float)$comp['amount'];
            case 'percentage':
                $base = $comp['percentage_of'] === 'basic_salary' ? $earnedBasic : (float)$comp['amount'];
                return ($base * $comp['amount']) / 100;
            case 'formula':
                // Evaluate simple formulas safely
                return (float)$comp['amount'];
            default:
                return (float)$comp['amount'];
        }
    }

    private function getWorkingDays(string $startDate, string $endDate): int
    {
        $holidays = $this->db->fetchAll(
            "SELECT date FROM holidays WHERE date BETWEEN ? AND ? AND is_active = 1",
            [$startDate, $endDate]
        );
        $holidayDates = array_column($holidays, 'date');

        $start   = new \DateTime($startDate);
        $end     = new \DateTime($endDate);
        $count   = 0;
        $current = clone $start;

        while ($current <= $end) {
            $dow = (int)$current->format('N'); // 1=Mon, 7=Sun
            if ($dow < 6 && !in_array($current->format('Y-m-d'), $holidayDates)) {
                $count++;
            }
            $current->modify('+1 day');
        }

        return $count;
    }
    public function disburse(int $id): void
    {
        $this->requirePermission('payroll.approve');
        $this->verifyCsrf();
        $this->db->update('payroll_periods', ['status' => 'paid'], ['id' => $id]);
        $this->flash('success', 'Payroll marked as disbursed.');
        $this->redirect('/payroll');
    }

    public function export(int $id): void
    {
        $this->requirePermission('payroll.view');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payroll_' . $id . '.csv"');
        $rows = $this->db->fetchAll("SELECT * FROM payroll_details WHERE payroll_period_id = ?", [$id]);
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Employee','Basic','Allowances','Gross','Tax','EOBI','Net']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['employee_id'], $r['basic_salary'], $r['total_allowances'] ?? 0, $r['gross_salary'], $r['income_tax'], $r['eobi_employee'], $r['net_salary']]);
        }
        fclose($out); exit;
    }

}
