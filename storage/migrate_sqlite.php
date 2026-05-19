<?php
/**
 * SQLite schema bootstrap — runs automatically when no MySQL is configured.
 * Creates all tables and seeds admin user + default data.
 */

function migrate(PDO $db): void
{
    $db->exec("PRAGMA journal_mode=WAL");
    $db->exec("PRAGMA foreign_keys=ON");

    $tables = <<<SQL

CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    is_system INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    module TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY(role_id, permission_id)
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT UNIQUE,
    password TEXT NOT NULL,
    role_id INTEGER,
    employee_id INTEGER,
    is_active INTEGER DEFAULT 1,
    is_super_admin INTEGER DEFAULT 0,
    avatar TEXT,
    last_login_at TEXT,
    remember_token TEXT,
    password_reset_token TEXT,
    password_reset_expires TEXT,
    failed_login_attempts INTEGER DEFAULT 0,
    locked_until TEXT,
    dark_mode INTEGER DEFAULT 0,
    language TEXT DEFAULT 'en',
    date_format TEXT DEFAULT 'd M Y',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    deleted_at TEXT
);

CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT,
    description TEXT,
    manager_id INTEGER,
    status TEXT DEFAULT 'active',
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    deleted_at TEXT
);

CREATE TABLE IF NOT EXISTS designations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    department_id INTEGER,
    grade TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    deleted_at TEXT
);

CREATE TABLE IF NOT EXISTS employees (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_code TEXT UNIQUE,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    father_name TEXT,
    cnic TEXT UNIQUE,
    date_of_birth TEXT,
    gender TEXT,
    marital_status TEXT,
    personal_email TEXT,
    personal_phone TEXT,
    present_address TEXT,
    permanent_address TEXT,
    nationality TEXT DEFAULT 'Pakistani',
    department_id INTEGER,
    designation_id INTEGER,
    employment_type TEXT DEFAULT 'permanent',
    joining_date TEXT,
    confirmation_date TEXT,
    status TEXT DEFAULT 'active',
    basic_salary REAL DEFAULT 0,
    bank_name TEXT,
    bank_account TEXT,
    bank_branch TEXT,
    ntn_number TEXT,
    eobi_number TEXT,
    pessi_number TEXT,
    emergency_contact_name TEXT,
    emergency_contact_phone TEXT,
    avatar TEXT,
    notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    deleted_at TEXT
);

CREATE TABLE IF NOT EXISTS leave_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    max_days INTEGER DEFAULT 0,
    carry_forward INTEGER DEFAULT 0,
    is_paid INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS leave_balances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    leave_type_id INTEGER NOT NULL,
    year INTEGER NOT NULL,
    allocated INTEGER DEFAULT 0,
    used INTEGER DEFAULT 0,
    balance INTEGER DEFAULT 0,
    UNIQUE(employee_id, leave_type_id, year)
);

CREATE TABLE IF NOT EXISTS leave_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    leave_type_id INTEGER NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    days INTEGER NOT NULL,
    reason TEXT,
    status TEXT DEFAULT 'pending',
    approved_by INTEGER,
    approved_at TEXT,
    rejection_reason TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    date TEXT NOT NULL,
    check_in TEXT,
    check_out TEXT,
    working_hours REAL,
    overtime_hours REAL DEFAULT 0,
    status TEXT DEFAULT 'present',
    notes TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    UNIQUE(employee_id, date)
);

CREATE TABLE IF NOT EXISTS payroll_periods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_month TEXT NOT NULL,
    period_label TEXT,
    working_days INTEGER,
    status TEXT DEFAULT 'draft',
    total_employees INTEGER DEFAULT 0,
    total_gross REAL DEFAULT 0,
    total_deductions REAL DEFAULT 0,
    total_net REAL DEFAULT 0,
    total_income_tax REAL DEFAULT 0,
    approved_by INTEGER,
    approved_at TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS payroll_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_period_id INTEGER NOT NULL,
    employee_id INTEGER NOT NULL,
    basic_salary REAL DEFAULT 0,
    house_rent REAL DEFAULT 0,
    medical_allowance REAL DEFAULT 0,
    transport_allowance REAL DEFAULT 0,
    other_allowances REAL DEFAULT 0,
    overtime_hours REAL DEFAULT 0,
    overtime_amount REAL DEFAULT 0,
    gross_salary REAL DEFAULT 0,
    income_tax REAL DEFAULT 0,
    eobi_employee REAL DEFAULT 0,
    eobi_employer REAL DEFAULT 0,
    pessi_employee REAL DEFAULT 0,
    pessi_employer REAL DEFAULT 0,
    loan_deduction REAL DEFAULT 0,
    advance_deduction REAL DEFAULT 0,
    total_deductions REAL DEFAULT 0,
    net_salary REAL DEFAULT 0,
    status TEXT DEFAULT 'draft',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    document_type TEXT,
    file_name TEXT,
    file_path TEXT,
    file_size INTEGER,
    file_type TEXT,
    is_confidential INTEGER DEFAULT 0,
    notes TEXT,
    uploaded_by INTEGER,
    created_at TEXT DEFAULT (datetime('now')),
    deleted_at TEXT
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    sender_id INTEGER,
    title TEXT NOT NULL,
    message TEXT,
    type TEXT DEFAULT 'info',
    icon TEXT DEFAULT 'bell',
    url TEXT,
    read_at TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    user_name TEXT,
    action TEXT NOT NULL,
    table_name TEXT,
    record_id INTEGER,
    changes TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS login_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    username TEXT,
    ip_address TEXT,
    user_agent TEXT,
    status TEXT DEFAULT 'success',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS fbr_tax_slabs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fiscal_year TEXT NOT NULL,
    min_income REAL NOT NULL,
    max_income REAL,
    fixed_tax REAL DEFAULT 0,
    rate REAL DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS holidays (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    date TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

SQL;

    $db->exec($tables);

    // Seed roles
    $roles = [
        ['Super Admin','super_admin','Full system access',1],
        ['HR Manager','hr_manager','HR operations',0],
        ['Payroll Officer','payroll_officer','Payroll processing',0],
        ['Employee','employee','Self-service access',0],
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO roles(name,slug,description,is_system) VALUES(?,?,?,?)");
    foreach ($roles as $r) $ins->execute($r);

    // Seed admin user (password: Admin@123)
    $hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost'=>12]);
    $db->exec("INSERT OR IGNORE INTO users(name,username,email,password,is_active,is_super_admin)
               VALUES('Super Admin','admin','admin@hrms.local','$hash',1,1)");

    // Seed FBR 2024-25 tax slabs
    $slabs = [
        ['2024-25',0,600000,0,0],
        ['2024-25',600001,1200000,0,5],
        ['2024-25',1200001,2200000,30000,15],
        ['2024-25',2200001,3200000,180000,25],
        ['2024-25',3200001,4100000,430000,30],
        ['2024-25',4100001,null,700000,35],
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO fbr_tax_slabs(fiscal_year,min_income,max_income,fixed_tax,rate) VALUES(?,?,?,?,?)");
    foreach ($slabs as $s) $ins->execute($s);

    // Seed leave types
    $leaves = [
        ['Annual Leave',21,1,1],
        ['Sick Leave',10,0,1],
        ['Casual Leave',10,0,1],
        ['Maternity Leave',90,0,1],
        ['Unpaid Leave',0,0,0],
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO leave_types(name,max_days,carry_forward,is_paid) VALUES(?,?,?,?)");
    foreach ($leaves as $l) $ins->execute($l);

    // Seed Pakistan holidays 2025
    $hols = [
        ['Kashmir Day','2025-02-05'],
        ['Pakistan Day','2025-03-23'],
        ['Labour Day','2025-05-01'],
        ['Independence Day','2025-08-14'],
        ['Defence Day','2025-09-06'],
        ['Iqbal Day','2025-11-09'],
        ['Christmas','2025-12-25'],
        ['Quaid Day','2025-12-25'],
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO holidays(name,date) VALUES(?,?)");
    foreach ($hols as $h) $ins->execute($h);

    // Seed company settings
    $settings = [
        ['company_name','HRMS Enterprise'],
        ['currency_symbol','PKR'],
        ['fiscal_year_start','7'],
        ['eobi_employee_amount','320'],
        ['eobi_employer_rate','5'],
        ['pessi_employee_rate','1'],
        ['pessi_employer_rate','6'],
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO settings(key,value) VALUES(?,?)");
    foreach ($settings as $s) $ins->execute($s);
}
