-- ORBIT HRMS — Compatibility patches
-- Runs after schema.sql to align column names with application code

-- ── users ─────────────────────────────────────────────────────────────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_super_admin TINYINT(1) DEFAULT 0 AFTER is_active;
ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(255) AS (full_name) VIRTUAL AFTER full_name;

-- ── attendance ────────────────────────────────────────────────────────────
ALTER TABLE attendance ADD COLUMN IF NOT EXISTS attendance_date DATE AFTER date;
ALTER TABLE attendance ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;
UPDATE attendance SET attendance_date = date WHERE attendance_date IS NULL;

-- ── leave_balances ────────────────────────────────────────────────────────
ALTER TABLE leave_balances ADD COLUMN IF NOT EXISTS balance INT AS (remaining_days) VIRTUAL;
ALTER TABLE leave_balances ADD COLUMN IF NOT EXISTS allocated INT AS (entitled_days) VIRTUAL;
ALTER TABLE leave_balances ADD COLUMN IF NOT EXISTS `used` INT AS (used_days) VIRTUAL;

-- ── leave_types ───────────────────────────────────────────────────────────
ALTER TABLE leave_types ADD COLUMN IF NOT EXISTS max_days INT AS (days_per_year) VIRTUAL;
ALTER TABLE leave_types ADD COLUMN IF NOT EXISTS color VARCHAR(20) AS (color_code) VIRTUAL;

-- ── leave_applications ────────────────────────────────────────────────────
ALTER TABLE leave_applications ADD COLUMN IF NOT EXISTS days INT AS (days_requested) VIRTUAL;
ALTER TABLE leave_applications ADD COLUMN IF NOT EXISTS start_date DATE AS (from_date) VIRTUAL;
ALTER TABLE leave_applications ADD COLUMN IF NOT EXISTS end_date DATE AS (to_date) VIRTUAL;
ALTER TABLE leave_applications ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;

-- ── payroll_periods ───────────────────────────────────────────────────────
ALTER TABLE payroll_periods ADD COLUMN IF NOT EXISTS payroll_month VARCHAR(7) AS (
    CONCAT(year, '-', LPAD(month, 2, '0'))
) VIRTUAL;
ALTER TABLE payroll_periods ADD COLUMN IF NOT EXISTS period_label VARCHAR(50) AS (
    CONCAT(MONTHNAME(CONCAT(year,'-',month,'-01')), ' ', year)
) VIRTUAL;

-- ── departments ───────────────────────────────────────────────────────────
ALTER TABLE departments ADD COLUMN IF NOT EXISTS status VARCHAR(20) AS (
    CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END
) VIRTUAL;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS manager_id INT AS (head_employee_id) VIRTUAL;

-- ── roles ─────────────────────────────────────────────────────────────────
ALTER TABLE roles ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;

-- ── Table aliases (views for renamed tables) ──────────────────────────────
CREATE OR REPLACE VIEW fbr_tax_slabs AS
    SELECT id, '2024-25' AS fiscal_year,
           income_from AS min_income,
           income_to AS max_income,
           fixed_tax,
           rate_percentage AS rate
    FROM tax_slabs WHERE is_active = 1;

CREATE OR REPLACE VIEW salary_advances AS
    SELECT id, employee_id, amount, amount AS balance_amount,
           reason, 1 AS repay_months, amount AS monthly_deduction,
           status, created_at AS request_date,
           approved_by, approved_at, NULL AS rejection_reason,
           NULL AS requested_by, created_at, NULL AS deleted_at
    FROM advances;

CREATE OR REPLACE VIEW employee_loans AS
    SELECT id, employee_id, amount AS loan_amount, balance AS balance_amount,
           interest_rate, installments AS term_months, monthly_installment,
           purpose, status, approved_by, approved_at,
           start_date AS loan_date, NULL AS requested_by, created_at, NULL AS deleted_at
    FROM loans;

-- ── Ensure admin user is super admin ──────────────────────────────────────
UPDATE users SET is_super_admin = 1 WHERE username = 'admin';
