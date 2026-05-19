<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payslip - <?= e(($payslip['employee_name'] ?? '') . ' ' . ($payslip['period_label'] ?? '')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root { --primary: #4f46e5; }
  body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
  .payslip-wrapper { max-width: 860px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.1); }
  .payslip-header { background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%); color: #fff; padding: 30px 40px; }
  .payslip-header h2 { font-size: 22px; font-weight: 700; margin: 0; }
  .payslip-header .subtitle { opacity: .8; font-size: 13px; }
  .payslip-body { padding: 30px 40px; }
  .info-table td { padding: 5px 8px; font-size: 13px; }
  .info-table td:first-child { color: #6c757d; width: 140px; }
  .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--primary); border-bottom: 2px solid var(--primary); padding-bottom: 5px; margin-bottom: 12px; }
  .earnings-row, .deductions-row { font-size: 13px; padding: 5px 0; border-bottom: 1px solid #f0f0f0; }
  .total-row { font-weight: 700; font-size: 14px; background: #f8f9fa; padding: 8px 0; border-radius: 4px; }
  .net-box { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-radius: 10px; padding: 20px; text-align: center; }
  .net-box .amount { font-size: 28px; font-weight: 800; }
  .net-box .label { font-size: 12px; opacity: .85; }
  .compliance-tag { font-size: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 3px 8px; border-radius: 20px; display: inline-block; margin: 2px; }
  .print-bar { background: #fff; padding: 15px 40px; border-top: 1px solid #e5e7eb; }
  @media print {
    body { background: #fff; }
    .payslip-wrapper { box-shadow: none; margin: 0; border-radius: 0; }
    .print-bar { display: none !important; }
    .payslip-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .net-box { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<!-- Print / Back Bar -->
<div class="print-bar d-flex justify-content-between align-items-center">
  <a href="/payroll/<?= $payslip['payroll_period_id'] ?? '' ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
  <div class="d-flex gap-2">
    <a href="/payroll/payslip/<?= $payslip['id'] ?>/download" class="btn btn-outline-success btn-sm"><i class="fas fa-download me-1"></i>Download PDF</a>
    <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Print</button>
  </div>
</div>

<div class="payslip-wrapper">
  <!-- Header -->
  <div class="payslip-header d-flex justify-content-between align-items-start">
    <div>
      <div style="font-size:24px;font-weight:800;letter-spacing:-0.5px"><?= e($companySettings['company_name'] ?? 'Company Name') ?></div>
      <div class="subtitle mt-1"><?= e($companySettings['company_address'] ?? '') ?></div>
      <div class="subtitle"><?= e($companySettings['company_phone'] ?? '') ?> | <?= e($companySettings['company_email'] ?? '') ?></div>
    </div>
    <div class="text-end">
      <div style="font-size:18px;font-weight:700">PAYSLIP</div>
      <div class="subtitle mt-1"><?= e($payslip['period_label'] ?? '') ?></div>
      <div style="background:rgba(255,255,255,.2);border-radius:6px;padding:4px 12px;margin-top:8px;font-size:12px">
        Ref: PS-<?= str_pad($payslip['id'] ?? 0, 6, '0', STR_PAD_LEFT) ?>
      </div>
    </div>
  </div>

  <div class="payslip-body">
    <!-- Employee Info -->
    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="section-title">Employee Information</div>
        <table class="info-table w-100">
          <tr><td>Name</td><td class="fw-semibold"><?= e($payslip['employee_name'] ?? '—') ?></td></tr>
          <tr><td>Employee Code</td><td><?= e($payslip['employee_code'] ?? '—') ?></td></tr>
          <tr><td>Department</td><td><?= e($payslip['department_name'] ?? '—') ?></td></tr>
          <tr><td>Designation</td><td><?= e($payslip['designation_title'] ?? '—') ?></td></tr>
          <tr><td>CNIC</td><td><?= e($payslip['cnic'] ?? '—') ?></td></tr>
          <tr><td>NTN</td><td><?= e($payslip['ntn_number'] ?? '—') ?></td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <div class="section-title">Employment Details</div>
        <table class="info-table w-100">
          <tr><td>Pay Period</td><td class="fw-semibold"><?= e($payslip['period_label'] ?? '—') ?></td></tr>
          <tr><td>Payment Date</td><td><?= formatDate($payslip['payment_date'] ?? date('Y-m-d')) ?></td></tr>
          <tr><td>Working Days</td><td><?= $payslip['working_days'] ?? '—' ?></td></tr>
          <tr><td>Days Worked</td><td><?= $payslip['days_worked'] ?? '—' ?></td></tr>
          <tr><td>Overtime Hrs</td><td><?= number_format($payslip['overtime_hours'] ?? 0, 1) ?> hrs</td></tr>
          <tr><td>Bank Account</td><td><?= e($payslip['bank_account'] ?? '—') ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Earnings & Deductions -->
    <div class="row g-4 mb-4">
      <!-- Earnings -->
      <div class="col-md-6">
        <div class="section-title">Earnings</div>
        <div class="earnings-row d-flex justify-content-between"><span>Basic Salary</span><span class="fw-semibold"><?= formatCurrency($payslip['basic_salary'] ?? 0) ?></span></div>
        <?php if(($payslip['house_rent'] ?? 0) > 0): ?>
        <div class="earnings-row d-flex justify-content-between"><span>House Rent Allowance</span><span><?= formatCurrency($payslip['house_rent']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['medical_allowance'] ?? 0) > 0): ?>
        <div class="earnings-row d-flex justify-content-between"><span>Medical Allowance</span><span><?= formatCurrency($payslip['medical_allowance']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['transport_allowance'] ?? 0) > 0): ?>
        <div class="earnings-row d-flex justify-content-between"><span>Transport Allowance</span><span><?= formatCurrency($payslip['transport_allowance']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['other_allowances'] ?? 0) > 0): ?>
        <div class="earnings-row d-flex justify-content-between"><span>Other Allowances</span><span><?= formatCurrency($payslip['other_allowances']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['overtime_amount'] ?? 0) > 0): ?>
        <div class="earnings-row d-flex justify-content-between"><span>Overtime (<?= number_format($payslip['overtime_hours'] ?? 0, 1) ?> hrs @ 1.5x)</span><span class="text-success"><?= formatCurrency($payslip['overtime_amount']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['bonus'] ?? 0) > 0): ?>
        <div class="earnings-row d-flex justify-content-between"><span>Bonus</span><span class="text-success"><?= formatCurrency($payslip['bonus']) ?></span></div>
        <?php endif; ?>
        <div class="total-row d-flex justify-content-between px-2 mt-2"><span>Gross Salary</span><span class="text-success"><?= formatCurrency($payslip['gross_salary'] ?? 0) ?></span></div>
      </div>

      <!-- Deductions -->
      <div class="col-md-6">
        <div class="section-title">Deductions</div>
        <?php if(($payslip['income_tax'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>Income Tax (FBR)</span><span class="text-danger"><?= formatCurrency($payslip['income_tax']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['eobi_employee'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>EOBI (Employee)</span><span class="text-danger"><?= formatCurrency($payslip['eobi_employee']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['pessi_employee'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>PESSI (Employee 1%)</span><span class="text-danger"><?= formatCurrency($payslip['pessi_employee']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['pf_employee'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>Provident Fund (Employee)</span><span class="text-danger"><?= formatCurrency($payslip['pf_employee']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['loan_deduction'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>Loan Deduction</span><span class="text-danger"><?= formatCurrency($payslip['loan_deduction']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['advance_deduction'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>Advance Recovery</span><span class="text-danger"><?= formatCurrency($payslip['advance_deduction']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['late_deduction'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>Late Attendance</span><span class="text-danger"><?= formatCurrency($payslip['late_deduction']) ?></span></div>
        <?php endif; ?>
        <?php if(($payslip['absent_deduction'] ?? 0) > 0): ?>
        <div class="deductions-row d-flex justify-content-between"><span>Absence Deduction</span><span class="text-danger"><?= formatCurrency($payslip['absent_deduction']) ?></span></div>
        <?php endif; ?>
        <div class="total-row d-flex justify-content-between px-2 mt-2"><span>Total Deductions</span><span class="text-danger"><?= formatCurrency($payslip['total_deductions'] ?? 0) ?></span></div>
      </div>
    </div>

    <!-- Net Salary -->
    <div class="row g-3 mb-4">
      <div class="col-md-5">
        <div class="net-box">
          <div class="label">NET TAKE-HOME SALARY</div>
          <div class="amount mt-1"><?= formatCurrency($payslip['net_salary'] ?? 0) ?></div>
          <div class="label mt-1"><?= e($payslip['period_label'] ?? '') ?></div>
        </div>
      </div>
      <div class="col-md-7">
        <div class="section-title">Employer Contributions (Not Deducted)</div>
        <table class="info-table w-100">
          <tr><td>EOBI (Employer 5%)</td><td class="fw-semibold"><?= formatCurrency($payslip['eobi_employer'] ?? 0) ?></td></tr>
          <tr><td>PESSI (Employer 6%)</td><td class="fw-semibold"><?= formatCurrency($payslip['pessi_employer'] ?? 0) ?></td></tr>
          <?php if(($payslip['pf_employer'] ?? 0) > 0): ?>
          <tr><td>Provident Fund (Employer)</td><td class="fw-semibold"><?= formatCurrency($payslip['pf_employer']) ?></td></tr>
          <?php endif; ?>
        </table>
        <div class="mt-2">
          <span class="compliance-tag">FBR Compliant</span>
          <span class="compliance-tag">EOBI: On Min Wage PKR 32,000</span>
          <?php if(($payslip['pessi_employer'] ?? 0) > 0): ?><span class="compliance-tag">PESSI Enrolled</span><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Tax Breakdown -->
    <?php if(($payslip['income_tax'] ?? 0) > 0 && !empty($payslip['tax_details'])): ?>
    <div class="mb-3">
      <div class="section-title">FBR Tax Calculation (2024-25 Slabs)</div>
      <div class="bg-light rounded p-3 small">
        <div class="row">
          <div class="col-md-4"><span class="text-muted">Annual Taxable Income:</span><br><strong><?= formatCurrency(($payslip['gross_salary'] ?? 0) * 12) ?></strong></div>
          <div class="col-md-4"><span class="text-muted">Applicable Slab Rate:</span><br><strong><?= $payslip['tax_rate'] ?? '—' ?>%</strong></div>
          <div class="col-md-4"><span class="text-muted">Monthly Tax:</span><br><strong class="text-danger"><?= formatCurrency($payslip['income_tax'] ?? 0) ?></strong></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <hr>
    <div class="row">
      <div class="col-md-6 small text-muted">
        <i class="fas fa-shield-alt me-1"></i>This is a computer-generated payslip and does not require a signature.
        <br>Generated on <?= date('d M Y H:i') ?>
      </div>
      <div class="col-md-6 text-end">
        <div class="small text-muted mb-3">Authorized Signatory</div>
        <div style="border-top:1px solid #ccc;width:160px;margin-left:auto;padding-top:4px;font-size:12px"><?= e($companySettings['company_name'] ?? '') ?></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
