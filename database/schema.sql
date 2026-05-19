-- ============================================================
-- HRMS + PAYROLL + PRACTICE MANAGEMENT SYSTEM
-- Complete Database Schema
-- Pakistan Compliant | Enterprise Grade
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:00";

CREATE DATABASE IF NOT EXISTS `hrms_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hrms_db`;

-- ============================================================
-- COMPANY SETTINGS
-- ============================================================

CREATE TABLE `company_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(255) NOT NULL,
  `company_logo` VARCHAR(500) DEFAULT NULL,
  `company_email` VARCHAR(255) NOT NULL,
  `company_phone` VARCHAR(50) DEFAULT NULL,
  `company_address` TEXT DEFAULT NULL,
  `company_ntn` VARCHAR(50) DEFAULT NULL,
  `company_strn` VARCHAR(50) DEFAULT NULL,
  `company_registration` VARCHAR(100) DEFAULT NULL,
  `fiscal_year_start` DATE DEFAULT NULL,
  `fiscal_year_end` DATE DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT 'PKR',
  `timezone` VARCHAR(100) DEFAULT 'Asia/Karachi',
  `date_format` VARCHAR(30) DEFAULT 'd-m-Y',
  `working_days_per_week` TINYINT DEFAULT 5,
  `working_hours_per_day` DECIMAL(4,2) DEFAULT 8.00,
  `allow_overtime` TINYINT(1) DEFAULT 1,
  `probation_period_days` INT DEFAULT 90,
  `eobi_rate` DECIMAL(5,2) DEFAULT 1.00,
  `pessi_rate` DECIMAL(5,2) DEFAULT 2.00,
  `provident_fund_rate` DECIMAL(5,2) DEFAULT 8.33,
  `gratuity_rate` DECIMAL(5,2) DEFAULT 8.33,
  `smtp_host` VARCHAR(255) DEFAULT NULL,
  `smtp_port` INT DEFAULT 587,
  `smtp_user` VARCHAR(255) DEFAULT NULL,
  `smtp_pass` VARCHAR(500) DEFAULT NULL,
  `smtp_encryption` ENUM('tls','ssl','none') DEFAULT 'tls',
  `app_url` VARCHAR(500) DEFAULT NULL,
  `maintenance_mode` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DEPARTMENTS
-- ============================================================

CREATE TABLE `departments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `head_employee_id` INT UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `budget` DECIMAL(15,2) DEFAULT 0.00,
  `cost_center` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_parent_id` (`parent_id`),
  FOREIGN KEY (`parent_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DESIGNATIONS
-- ============================================================

CREATE TABLE `designations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `grade` VARCHAR(50) DEFAULT NULL,
  `min_salary` DECIMAL(15,2) DEFAULT 0.00,
  `max_salary` DECIMAL(15,2) DEFAULT 0.00,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ROLES & PERMISSIONS (RBAC)
-- ============================================================

CREATE TABLE `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `is_system` TINYINT(1) DEFAULT 0 COMMENT '1 = cannot be deleted',
  `hierarchy_level` TINYINT UNSIGNED DEFAULT 99 COMMENT 'Lower = higher authority',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `module` VARCHAR(100) NOT NULL,
  `action` ENUM('view','create','edit','delete','export','approve','import','manage') NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `role_permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  `granted_by` INT UNSIGNED DEFAULT NULL,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_role_perm` (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USERS (SYSTEM ACCOUNTS)
-- ============================================================

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
  `role_id` INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `two_factor_enabled` TINYINT(1) DEFAULT 0,
  `two_factor_secret` VARCHAR(255) DEFAULT NULL,
  `otp_code` VARCHAR(10) DEFAULT NULL,
  `otp_expires_at` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `login_attempts` TINYINT UNSIGNED DEFAULT 0,
  `locked_until` TIMESTAMP NULL DEFAULT NULL,
  `password_changed_at` TIMESTAMP NULL DEFAULT NULL,
  `password_expires_at` TIMESTAMP NULL DEFAULT NULL,
  `force_password_change` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `is_online` TINYINT(1) DEFAULT 0,
  `last_seen_at` TIMESTAMP NULL DEFAULT NULL,
  `allowed_ips` TEXT DEFAULT NULL COMMENT 'JSON array of allowed IPs',
  `session_id` VARCHAR(255) DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role_id`),
  INDEX `idx_department` (`department_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EMPLOYEES
-- ============================================================

CREATE TABLE `employees` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_code` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `department_id` INT UNSIGNED NOT NULL,
  `designation_id` INT UNSIGNED NOT NULL,
  `reporting_to` INT UNSIGNED DEFAULT NULL COMMENT 'Manager employee_id',
  -- Personal Info
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `father_name` VARCHAR(255) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `marital_status` ENUM('single','married','divorced','widowed') DEFAULT NULL,
  `nationality` VARCHAR(100) DEFAULT 'Pakistani',
  `religion` VARCHAR(100) DEFAULT NULL,
  `blood_group` VARCHAR(10) DEFAULT NULL,
  -- CNIC / Documents
  `cnic` VARCHAR(20) DEFAULT NULL UNIQUE,
  `cnic_issue_date` DATE DEFAULT NULL,
  `cnic_expiry_date` DATE DEFAULT NULL,
  `passport_number` VARCHAR(50) DEFAULT NULL,
  `passport_expiry` DATE DEFAULT NULL,
  -- Contact Info
  `personal_email` VARCHAR(255) DEFAULT NULL,
  `official_email` VARCHAR(255) DEFAULT NULL,
  `mobile` VARCHAR(50) DEFAULT NULL,
  `emergency_contact_name` VARCHAR(255) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(50) DEFAULT NULL,
  `emergency_contact_relation` VARCHAR(100) DEFAULT NULL,
  -- Address
  `present_address` TEXT DEFAULT NULL,
  `permanent_address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `province` VARCHAR(100) DEFAULT NULL,
  -- Employment
  `join_date` DATE NOT NULL,
  `confirmation_date` DATE DEFAULT NULL,
  `contract_type` ENUM('permanent','contract','probation','internship','part_time') DEFAULT 'probation',
  `contract_end_date` DATE DEFAULT NULL,
  `employment_status` ENUM('active','inactive','resigned','terminated','retired','deceased') DEFAULT 'active',
  `separation_date` DATE DEFAULT NULL,
  `separation_reason` TEXT DEFAULT NULL,
  -- Salary
  `basic_salary` DECIMAL(15,2) DEFAULT 0.00,
  `salary_mode` ENUM('monthly','daily','hourly') DEFAULT 'monthly',
  `bank_name` VARCHAR(255) DEFAULT NULL,
  `bank_account` VARCHAR(100) DEFAULT NULL,
  `bank_branch` VARCHAR(255) DEFAULT NULL,
  `iban` VARCHAR(50) DEFAULT NULL,
  -- Tax
  `ntn` VARCHAR(50) DEFAULT NULL,
  `tax_exempt` TINYINT(1) DEFAULT 0,
  -- Social Security
  `eobi_number` VARCHAR(50) DEFAULT NULL,
  `pessi_number` VARCHAR(50) DEFAULT NULL,
  `provident_fund_eligible` TINYINT(1) DEFAULT 1,
  `gratuity_eligible` TINYINT(1) DEFAULT 1,
  -- Other
  `profile_photo` VARCHAR(500) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_department` (`department_id`),
  INDEX `idx_designation` (`designation_id`),
  INDEX `idx_status` (`employment_status`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
  FOREIGN KEY (`designation_id`) REFERENCES `designations`(`id`),
  FOREIGN KEY (`reporting_to`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EMPLOYEE EDUCATION
-- ============================================================

CREATE TABLE `employee_education` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `degree` VARCHAR(255) NOT NULL,
  `institution` VARCHAR(255) NOT NULL,
  `major` VARCHAR(255) DEFAULT NULL,
  `passing_year` YEAR DEFAULT NULL,
  `grade_cgpa` VARCHAR(50) DEFAULT NULL,
  `document_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- EMPLOYEE EXPERIENCE
-- ============================================================

CREATE TABLE `employee_experience` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `company` VARCHAR(255) NOT NULL,
  `position` VARCHAR(255) NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE DEFAULT NULL,
  `is_current` TINYINT(1) DEFAULT 0,
  `reason_leaving` TEXT DEFAULT NULL,
  `reference_name` VARCHAR(255) DEFAULT NULL,
  `reference_phone` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PROMOTIONS & TRANSFERS
-- ============================================================

CREATE TABLE `employee_promotions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `old_designation_id` INT UNSIGNED DEFAULT NULL,
  `new_designation_id` INT UNSIGNED NOT NULL,
  `old_department_id` INT UNSIGNED DEFAULT NULL,
  `new_department_id` INT UNSIGNED NOT NULL,
  `old_salary` DECIMAL(15,2) DEFAULT 0.00,
  `new_salary` DECIMAL(15,2) DEFAULT 0.00,
  `effective_date` DATE NOT NULL,
  `type` ENUM('promotion','demotion','transfer','lateral') DEFAULT 'promotion',
  `reason` TEXT DEFAULT NULL,
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `document_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SHIFT MANAGEMENT
-- ============================================================

CREATE TABLE `shifts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `break_duration` INT DEFAULT 60 COMMENT 'minutes',
  `grace_period` INT DEFAULT 15 COMMENT 'late grace in minutes',
  `overtime_after` INT DEFAULT 0 COMMENT 'minutes after shift end',
  `half_day_hours` DECIMAL(4,2) DEFAULT 4.00,
  `is_night_shift` TINYINT(1) DEFAULT 0,
  `working_days` VARCHAR(50) DEFAULT '1,2,3,4,5' COMMENT 'CSV day numbers',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `employee_shifts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `shift_id` INT UNSIGNED NOT NULL,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE DEFAULT NULL,
  `assigned_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ATTENDANCE
-- ============================================================

CREATE TABLE `attendance` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `check_in` TIME DEFAULT NULL,
  `check_out` TIME DEFAULT NULL,
  `check_in_location` VARCHAR(255) DEFAULT NULL,
  `check_out_location` VARCHAR(255) DEFAULT NULL,
  `check_in_ip` VARCHAR(45) DEFAULT NULL,
  `check_out_ip` VARCHAR(45) DEFAULT NULL,
  `shift_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('present','absent','late','half_day','leave','holiday','weekend','work_from_home') DEFAULT 'absent',
  `working_hours` DECIMAL(5,2) DEFAULT 0.00,
  `overtime_hours` DECIMAL(5,2) DEFAULT 0.00,
  `late_minutes` INT DEFAULT 0,
  `early_out_minutes` INT DEFAULT 0,
  `source` ENUM('biometric','manual','system','mobile') DEFAULT 'manual',
  `notes` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) DEFAULT 0,
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_emp_date` (`employee_id`, `date`),
  INDEX `idx_date` (`date`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- HOLIDAYS
-- ============================================================

CREATE TABLE `holidays` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  `type` ENUM('national','optional','company') DEFAULT 'national',
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- LEAVE MANAGEMENT
-- ============================================================

CREATE TABLE `leave_types` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `days_per_year` DECIMAL(5,2) DEFAULT 0.00,
  `carry_forward` TINYINT(1) DEFAULT 0,
  `max_carry_forward` DECIMAL(5,2) DEFAULT 0.00,
  `encashable` TINYINT(1) DEFAULT 0,
  `requires_document` TINYINT(1) DEFAULT 0,
  `min_notice_days` INT DEFAULT 0,
  `max_consecutive_days` INT DEFAULT NULL,
  `gender_specific` ENUM('all','male','female') DEFAULT 'all',
  `is_paid` TINYINT(1) DEFAULT 1,
  `color_code` VARCHAR(10) DEFAULT '#4CAF50',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `leave_balances` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `leave_type_id` INT UNSIGNED NOT NULL,
  `year` YEAR NOT NULL,
  `entitled_days` DECIMAL(5,2) DEFAULT 0.00,
  `used_days` DECIMAL(5,2) DEFAULT 0.00,
  `carried_forward` DECIMAL(5,2) DEFAULT 0.00,
  `remaining_days` DECIMAL(5,2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_emp_leave_year` (`employee_id`, `leave_type_id`, `year`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `leave_applications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `application_number` VARCHAR(50) NOT NULL UNIQUE,
  `employee_id` INT UNSIGNED NOT NULL,
  `leave_type_id` INT UNSIGNED NOT NULL,
  `from_date` DATE NOT NULL,
  `to_date` DATE NOT NULL,
  `days_requested` DECIMAL(5,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `document_path` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `review_remarks` TEXT DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `cancellation_reason` TEXT DEFAULT NULL,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`),
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SALARY STRUCTURE
-- ============================================================

CREATE TABLE `salary_components` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('earning','deduction','benefit') NOT NULL,
  `calculation_type` ENUM('fixed','percentage','formula') DEFAULT 'fixed',
  `percentage_of` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. basic_salary',
  `formula` TEXT DEFAULT NULL,
  `is_taxable` TINYINT(1) DEFAULT 1,
  `is_statutory` TINYINT(1) DEFAULT 0,
  `statutory_type` ENUM('eobi','pessi','income_tax','provident_fund','none') DEFAULT 'none',
  `is_mandatory` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `employee_salary_structure` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `component_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) DEFAULT 0.00,
  `effective_from` DATE NOT NULL,
  `effective_to` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`component_id`) REFERENCES `salary_components`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PAYROLL
-- ============================================================

CREATE TABLE `payroll_periods` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `year` YEAR NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('draft','processing','processed','approved','disbursed') DEFAULT 'draft',
  `total_employees` INT DEFAULT 0,
  `total_gross` DECIMAL(15,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `total_net` DECIMAL(15,2) DEFAULT 0.00,
  `processed_by` INT UNSIGNED DEFAULT NULL,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `disbursed_by` INT UNSIGNED DEFAULT NULL,
  `disbursed_at` TIMESTAMP NULL DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_month_year` (`month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payroll_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payroll_period_id` INT UNSIGNED NOT NULL,
  `employee_id` INT UNSIGNED NOT NULL,
  `working_days` DECIMAL(5,2) DEFAULT 0.00,
  `present_days` DECIMAL(5,2) DEFAULT 0.00,
  `leave_days` DECIMAL(5,2) DEFAULT 0.00,
  `absent_days` DECIMAL(5,2) DEFAULT 0.00,
  `overtime_hours` DECIMAL(7,2) DEFAULT 0.00,
  `overtime_amount` DECIMAL(15,2) DEFAULT 0.00,
  `basic_salary` DECIMAL(15,2) DEFAULT 0.00,
  `gross_salary` DECIMAL(15,2) DEFAULT 0.00,
  `total_earnings` DECIMAL(15,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `income_tax` DECIMAL(15,2) DEFAULT 0.00,
  `eobi_employee` DECIMAL(15,2) DEFAULT 0.00,
  `eobi_employer` DECIMAL(15,2) DEFAULT 0.00,
  `pessi_employee` DECIMAL(15,2) DEFAULT 0.00,
  `pessi_employer` DECIMAL(15,2) DEFAULT 0.00,
  `provident_fund_employee` DECIMAL(15,2) DEFAULT 0.00,
  `provident_fund_employer` DECIMAL(15,2) DEFAULT 0.00,
  `loan_deduction` DECIMAL(15,2) DEFAULT 0.00,
  `advance_deduction` DECIMAL(15,2) DEFAULT 0.00,
  `net_salary` DECIMAL(15,2) DEFAULT 0.00,
  `status` ENUM('draft','finalized','paid') DEFAULT 'draft',
  `payslip_generated` TINYINT(1) DEFAULT 0,
  `payslip_path` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_period_emp` (`payroll_period_id`, `employee_id`),
  FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payroll_item_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payroll_item_id` INT UNSIGNED NOT NULL,
  `component_id` INT UNSIGNED NOT NULL,
  `type` ENUM('earning','deduction','benefit') NOT NULL,
  `amount` DECIMAL(15,2) DEFAULT 0.00,
  FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`component_id`) REFERENCES `salary_components`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ADVANCES & LOANS
-- ============================================================

CREATE TABLE `advances` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `recovery_month` DATE DEFAULT NULL COMMENT 'Month to deduct',
  `recovered` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `loans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `loan_number` VARCHAR(50) NOT NULL UNIQUE,
  `amount` DECIMAL(15,2) NOT NULL,
  `installments` INT NOT NULL,
  `monthly_installment` DECIMAL(15,2) NOT NULL,
  `interest_rate` DECIMAL(5,2) DEFAULT 0.00,
  `purpose` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','active','completed') DEFAULT 'pending',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `paid_installments` INT DEFAULT 0,
  `total_paid` DECIMAL(15,2) DEFAULT 0.00,
  `balance` DECIMAL(15,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TAX SLABS (FBR Pakistan)
-- ============================================================

CREATE TABLE `tax_slabs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fiscal_year` VARCHAR(20) NOT NULL,
  `income_from` DECIMAL(15,2) NOT NULL,
  `income_to` DECIMAL(15,2) DEFAULT NULL COMMENT 'NULL = unlimited',
  `fixed_tax` DECIMAL(15,2) DEFAULT 0.00,
  `rate_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `over_amount` DECIMAL(15,2) DEFAULT 0.00,
  `slab_order` TINYINT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DOCUMENTS
-- ============================================================

CREATE TABLE `document_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `access_roles` TEXT DEFAULT NULL COMMENT 'JSON role IDs',
  `is_confidential` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_number` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(500) NOT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `employee_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = company document',
  `file_name` VARCHAR(500) NOT NULL,
  `file_path` VARCHAR(1000) NOT NULL COMMENT 'Outside public dir',
  `file_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT UNSIGNED DEFAULT NULL COMMENT 'bytes',
  `file_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256',
  `description` TEXT DEFAULT NULL,
  `tags` VARCHAR(500) DEFAULT NULL,
  `is_confidential` TINYINT(1) DEFAULT 0,
  `expiry_date` DATE DEFAULT NULL,
  `uploaded_by` INT UNSIGNED NOT NULL,
  `download_count` INT DEFAULT 0,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `document_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `document_access_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `action` ENUM('view','download','delete') NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TASKS & WORKFLOW
-- ============================================================

CREATE TABLE `tasks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_number` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `assigned_by` INT UNSIGNED DEFAULT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `priority` ENUM('low','medium','high','critical') DEFAULT 'medium',
  `status` ENUM('pending','in_progress','on_hold','completed','cancelled') DEFAULT 'pending',
  `due_date` DATE DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `progress` TINYINT UNSIGNED DEFAULT 0,
  `attachments` TEXT DEFAULT NULL COMMENT 'JSON paths',
  `parent_task_id` INT UNSIGNED DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `task_comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `message` TEXT NOT NULL,
  `data` JSON DEFAULT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  `color` VARCHAR(20) DEFAULT 'primary',
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_read` (`user_id`, `is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AUDIT LOGS (IMMUTABLE)
-- ============================================================

CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(255) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `record_id` INT UNSIGNED DEFAULT NULL,
  `record_type` VARCHAR(100) DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `url` VARCHAR(1000) DEFAULT NULL,
  `method` VARCHAR(10) DEFAULT NULL,
  `status` ENUM('success','warning','error','critical') DEFAULT 'success',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_module` (`module`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `login_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `username_attempt` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('success','failed','blocked','logout') NOT NULL,
  `failure_reason` VARCHAR(255) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `session_id` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_ip` (`ip_address`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SYSTEM SETTINGS / CONFIGS
-- ============================================================

CREATE TABLE `system_configs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(255) NOT NULL UNIQUE,
  `config_value` TEXT DEFAULT NULL,
  `config_group` VARCHAR(100) DEFAULT 'general',
  `is_encrypted` TINYINT(1) DEFAULT 0,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================

ALTER TABLE `employees` ADD INDEX `idx_cnic` (`cnic`);
ALTER TABLE `employees` ADD INDEX `idx_name` (`first_name`, `last_name`);
ALTER TABLE `employees` ADD INDEX `idx_join_date` (`join_date`);
ALTER TABLE `attendance` ADD INDEX `idx_emp_date` (`employee_id`, `date`);
ALTER TABLE `payroll_items` ADD INDEX `idx_period_status` (`payroll_period_id`, `status`);

-- ============================================================
-- VIEWS
-- ============================================================

CREATE VIEW `v_employee_full` AS
SELECT 
  e.*,
  CONCAT(e.first_name, ' ', e.last_name) AS full_name,
  d.name AS department_name,
  d.code AS department_code,
  des.title AS designation_title,
  des.grade AS designation_grade,
  u.username,
  u.email AS user_email,
  u.is_active AS account_active,
  r.name AS role_name,
  CONCAT(m.first_name, ' ', m.last_name) AS manager_name
FROM employees e
LEFT JOIN departments d ON e.department_id = d.id
LEFT JOIN designations des ON e.designation_id = des.id
LEFT JOIN users u ON e.user_id = u.id
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN employees m ON e.reporting_to = m.id
WHERE e.deleted_at IS NULL;

CREATE VIEW `v_attendance_summary` AS
SELECT
  a.employee_id,
  a.date,
  YEAR(a.date) AS year,
  MONTH(a.date) AS month,
  COUNT(*) AS total_days,
  SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
  SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
  SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) AS late_days,
  SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) AS leave_days,
  SUM(a.working_hours) AS total_working_hours,
  SUM(a.overtime_hours) AS total_overtime_hours
FROM attendance a
GROUP BY a.employee_id, YEAR(a.date), MONTH(a.date);

-- ============================================================
-- DEFAULT DATA SEED
-- ============================================================

-- Default Roles
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`, `hierarchy_level`) VALUES
('Super Admin',    'super_admin',    'Full system access',             1, 1),
('HR Manager',     'hr_manager',     'HR module access',               1, 2),
('Payroll Officer','payroll_officer','Payroll processing',             1, 3),
('Finance Manager','finance_manager','Finance and reports access',     1, 4),
('Department Head','dept_head',      'Department management access',   1, 5),
('Employee',       'employee',       'Employee self-service',          1, 99),
('Auditor',        'auditor',        'Read-only audit access',         1, 10),
('Legal Officer',  'legal_officer',  'Legal documents and compliance', 1, 8);

-- Default Permissions
INSERT INTO `permissions` (`name`, `slug`, `module`, `action`) VALUES
-- Employee Module
('View Employees',   'employees.view',   'employees', 'view'),
('Create Employee',  'employees.create', 'employees', 'create'),
('Edit Employee',    'employees.edit',   'employees', 'edit'),
('Delete Employee',  'employees.delete', 'employees', 'delete'),
('Export Employees', 'employees.export', 'employees', 'export'),
-- Payroll Module
('View Payroll',     'payroll.view',     'payroll',   'view'),
('Process Payroll',  'payroll.create',   'payroll',   'create'),
('Approve Payroll',  'payroll.approve',  'payroll',   'approve'),
('Export Payroll',   'payroll.export',   'payroll',   'export'),
-- Attendance Module
('View Attendance',  'attendance.view',  'attendance','view'),
('Manage Attendance','attendance.manage','attendance','manage'),
('Export Attendance','attendance.export','attendance','export'),
-- Leave Module
('View Leaves',      'leaves.view',      'leaves',    'view'),
('Approve Leaves',   'leaves.approve',   'leaves',    'approve'),
('Manage Leaves',    'leaves.manage',    'leaves',    'manage'),
-- Documents Module
('View Documents',   'documents.view',   'documents', 'view'),
('Upload Documents', 'documents.create', 'documents', 'create'),
('Delete Documents', 'documents.delete', 'documents', 'delete'),
-- Reports Module
('View Reports',     'reports.view',     'reports',   'view'),
('Export Reports',   'reports.export',   'reports',   'export'),
-- Audit Module
('View Audit Logs',  'audit.view',       'audit',     'view'),
-- Settings Module
('Manage Settings',  'settings.manage',  'settings',  'manage'),
-- Roles Module
('Manage Roles',     'roles.manage',     'roles',     'manage'),
('Assign Roles',     'roles.assign',     'roles',     'edit'),
-- Users Module
('View Users',       'users.view',       'users',     'view'),
('Manage Users',     'users.manage',     'users',     'manage');

-- Assign ALL permissions to Super Admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Company Settings Default
INSERT INTO `company_settings` (`company_name`, `company_email`, `currency`, `timezone`) 
VALUES ('My Company Ltd', 'admin@company.com', 'PKR', 'Asia/Karachi');

-- Default Super Admin User (password: Admin@123)
INSERT INTO `users` (`username`, `email`, `password`, `role_id`, `full_name`, `is_active`)
VALUES (
  'admin',
  'admin@company.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  1,
  'System Administrator',
  1
);

-- Default Leave Types (Pakistan Standard)
INSERT INTO `leave_types` (`name`, `code`, `days_per_year`, `carry_forward`, `is_paid`) VALUES
('Annual Leave',       'AL',  14.00, 1, 1),
('Sick Leave',         'SL',  10.00, 0, 1),
('Casual Leave',       'CL',  10.00, 0, 1),
('Maternity Leave',    'ML',  90.00, 0, 1),
('Paternity Leave',    'PL',   3.00, 0, 1),
('Unpaid Leave',       'UL',   0.00, 0, 0),
('Hajj Leave',         'HL',  30.00, 0, 1),
('Study Leave',        'STL',  0.00, 0, 1);

-- Default Salary Components
INSERT INTO `salary_components` (`name`, `code`, `type`, `calculation_type`, `is_taxable`, `is_statutory`, `sort_order`) VALUES
('Basic Salary',       'BASIC',    'earning',   'fixed',      1, 0, 1),
('House Rent Allowance','HRA',     'earning',   'percentage', 0, 0, 2),
('Medical Allowance',  'MED',      'earning',   'fixed',      0, 0, 3),
('Conveyance Allowance','CONV',    'earning',   'fixed',      0, 0, 4),
('Special Allowance',  'SPEC',     'earning',   'fixed',      1, 0, 5),
('Performance Bonus',  'BONUS',    'earning',   'fixed',      1, 0, 6),
('Income Tax',         'IT',       'deduction', 'formula',    0, 1, 10),
('EOBI Employee',      'EOBI_EMP', 'deduction', 'percentage', 0, 1, 11),
('EOBI Employer',      'EOBI_EMP2','benefit',   'percentage', 0, 1, 12),
('PESSI Employee',     'PESSI_EMP','deduction', 'percentage', 0, 1, 13),
('Provident Fund',     'PF_EMP',   'deduction', 'percentage', 0, 1, 14),
('Loan Deduction',     'LOAN',     'deduction', 'fixed',      0, 0, 15),
('Advance Deduction',  'ADV',      'deduction', 'fixed',      0, 0, 16);

-- FBR Tax Slabs 2024-25
INSERT INTO `tax_slabs` (`fiscal_year`, `income_from`, `income_to`, `fixed_tax`, `rate_percentage`, `over_amount`, `slab_order`) VALUES
('2024-25',       0.00,   600000.00,     0.00,  0.00,      0.00, 1),
('2024-25',  600001.00,  1200000.00,     0.00,  5.00,  600000.00, 2),
('2024-25', 1200001.00,  2200000.00, 30000.00, 15.00, 1200000.00, 3),
('2024-25', 2200001.00,  3200000.00,180000.00, 25.00, 2200000.00, 4),
('2024-25', 3200001.00,  4100000.00,430000.00, 30.00, 3200000.00, 5),
('2024-25', 4100001.00,        NULL,700000.00, 35.00, 4100000.00, 6);

-- Default Shifts
INSERT INTO `shifts` (`name`, `code`, `start_time`, `end_time`, `break_duration`, `grace_period`, `working_days`) VALUES
('Morning Shift',   'MORNING',  '09:00:00', '17:00:00', 60, 15, '1,2,3,4,5'),
('Evening Shift',   'EVENING',  '14:00:00', '22:00:00', 60, 15, '1,2,3,4,5'),
('Night Shift',     'NIGHT',    '22:00:00', '06:00:00', 60, 15, '1,2,3,4,5'),
('Flexible Shift',  'FLEX',     '08:00:00', '18:00:00', 60, 30, '1,2,3,4,5');

-- Pakistan National Holidays
INSERT INTO `holidays` (`name`, `date`, `type`) VALUES
('New Year Day',       '2025-01-01', 'national'),
('Kashmir Day',        '2025-02-05', 'national'),
('Pakistan Day',       '2025-03-23', 'national'),
('Labour Day',         '2025-05-01', 'national'),
('Independence Day',   '2025-08-14', 'national'),
('Quaid Day',          '2025-12-25', 'national'),
('Iqbal Day',          '2025-11-09', 'national'),
('Christmas Day',      '2025-12-25', 'national');

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER //

CREATE PROCEDURE `sp_calculate_payroll`(IN p_period_id INT, IN p_employee_id INT)
BEGIN
  DECLARE v_basic DECIMAL(15,2);
  DECLARE v_present_days DECIMAL(5,2);
  DECLARE v_working_days INT;
  DECLARE v_monthly_salary DECIMAL(15,2);
  
  -- Get basic salary
  SELECT basic_salary INTO v_basic FROM employees WHERE id = p_employee_id;
  
  -- Get working days in period
  SELECT 
    SUM(CASE WHEN status IN ('present','late','half_day') THEN 1 
             WHEN status = 'half_day' THEN 0.5 ELSE 0 END)
  INTO v_present_days
  FROM attendance 
  WHERE employee_id = p_employee_id
    AND date BETWEEN (SELECT start_date FROM payroll_periods WHERE id = p_period_id)
                 AND (SELECT end_date   FROM payroll_periods WHERE id = p_period_id);
  
  SELECT DATEDIFF(end_date, start_date) + 1 
  INTO v_working_days
  FROM payroll_periods WHERE id = p_period_id;
  
  -- Calculate prorated salary
  SET v_monthly_salary = (v_basic / v_working_days) * v_present_days;
  
  -- Update payroll item
  UPDATE payroll_items 
  SET basic_salary = v_monthly_salary,
      present_days = v_present_days,
      working_days = v_working_days
  WHERE payroll_period_id = p_period_id AND employee_id = p_employee_id;
  
END//

DELIMITER ;

-- ============================================================
-- END OF SCHEMA
-- ============================================================
