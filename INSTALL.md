# HRMS Enterprise Platform — Installation Guide

## System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 7.4+ | 8.2+ |
| MySQL | 5.7+ | 8.0+ |
| Apache | 2.4+ | 2.4+ (mod_rewrite enabled) |
| Memory | 128MB | 256MB+ |
| Storage | 1GB | 10GB+ |

### Required PHP Extensions
```
pdo, pdo_mysql, mbstring, json, fileinfo, gd, openssl, curl, zip
```

---

## Step 1 — Clone / Upload Files

```bash
# Option A: Copy files to your web server
cp -r hrms/ /var/www/html/hrms

# Option B: Set document root to /hrms/public in Apache VirtualHost
```

---

## Step 2 — Apache Virtual Host

Add to `/etc/apache2/sites-available/hrms.conf`:

```apache
<VirtualHost *:80>
    ServerName hrms.yourdomain.com
    DocumentRoot /var/www/html/hrms/public

    <Directory /var/www/html/hrms/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Security: Block access outside public/
    <Directory /var/www/html/hrms>
        Require all denied
    </Directory>
    <Directory /var/www/html/hrms/public>
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/hrms_error.log
    CustomLog ${APACHE_LOG_DIR}/hrms_access.log combined
</VirtualHost>
```

```bash
a2ensite hrms.conf
a2enmod rewrite headers
systemctl restart apache2
```

---

## Step 3 — Database Setup

```sql
-- Create database and user
CREATE DATABASE hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hrms_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON hrms_db.* TO 'hrms_user'@'localhost';
FLUSH PRIVILEGES;
```

```bash
# Import schema
mysql -u hrms_user -p hrms_db < /var/www/html/hrms/database/schema.sql
```

---

## Step 4 — Environment Configuration

```bash
cp .env.example .env
nano .env
```

Edit `.env`:

```env
# ─── Application ────────────────────────────────────────────
APP_NAME="HRMS Enterprise"
APP_URL=http://hrms.yourdomain.com
APP_DEBUG=false
APP_ENV=production
APP_KEY=your-32-char-random-key-here

# ─── Database ────────────────────────────────────────────────
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=hrms_db
DB_USER=hrms_user
DB_PASS=StrongPassword123!
DB_CHARSET=utf8mb4

# ─── Mail (SMTP) ─────────────────────────────────────────────
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=noreply@yourdomain.com
MAIL_PASS=your-app-password
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME="HRMS Platform"
MAIL_ENCRYPTION=tls

# ─── Storage ─────────────────────────────────────────────────
UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=../storage/uploads

# ─── Session ─────────────────────────────────────────────────
SESSION_LIFETIME=1800
SESSION_SECURE=false

# ─── Company (will be overridden from DB settings) ───────────
COMPANY_NAME="Your Company Ltd"
COMPANY_TIMEZONE=Asia/Karachi
```

### Generate APP_KEY
```bash
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"
```

---

## Step 5 — File Permissions

```bash
# Storage directories must be writable
chmod -R 775 /var/www/html/hrms/storage
chown -R www-data:www-data /var/www/html/hrms/storage

# Logs
chmod -R 775 /var/www/html/hrms/storage/logs

# Public uploads
chmod -R 775 /var/www/html/hrms/public/assets

# Protect sensitive files
chmod 600 /var/www/html/hrms/.env
```

---

## Step 6 — First Login

| Field | Value |
|-------|-------|
| URL | http://hrms.yourdomain.com/login |
| Username | `admin` |
| Password | `Admin@123` |

> ⚠️ **Change the default password immediately after first login!**

---

## Step 7 — Initial Setup Checklist

After logging in, complete these in **Settings**:

- [ ] Update Company Information (name, NTN, EOBI number, address)
- [ ] Add Departments
- [ ] Add Designations
- [ ] Configure Leave Types and annual allocations
- [ ] Add Public Holidays for the year
- [ ] Verify FBR Tax Slabs (Settings → Tax Slabs)
- [ ] Create additional user roles and permissions
- [ ] Add employees and link to user accounts
- [ ] Configure SMTP for email notifications

---

## Module Overview

| Module | URL | Description |
|--------|-----|-------------|
| Dashboard | `/` | KPIs, charts, quick actions |
| Employees | `/employees` | Full employee lifecycle management |
| Attendance | `/attendance` | Check-in/out, imports, reports |
| Leaves | `/leaves` | Applications, approvals, calendar |
| Payroll | `/payroll` | Monthly processing, FBR tax, payslips |
| Documents | `/documents` | Secure HR document storage |
| Reports | `/reports` | Payroll, tax, EOBI, attendance reports |
| Advances | `/advances` | Salary advance requests and recovery |
| Loans | `/loans` | Employee loan management with EMI |
| Tasks | `/tasks` | Team task and assignment tracking |
| Roles | `/roles` | RBAC role and permission management |
| Settings | `/settings` | Company, departments, tax slabs |
| Audit | `/audit` | Immutable system activity logs |

---

## Pakistan Compliance Features

### FBR Income Tax 2024-25 Slabs (Pre-seeded)

| Annual Income | Tax Rate |
|--------------|----------|
| Up to PKR 600,000 | 0% |
| PKR 600,001 – 1,200,000 | 5% |
| PKR 1,200,001 – 2,200,000 | 15% |
| PKR 2,200,001 – 3,200,000 | 25% |
| PKR 3,200,001 – 4,100,000 | 30% |
| Above PKR 4,100,000 | 35% |

### EOBI Contributions
- **Employee:** PKR 320/month (1% of minimum wage PKR 32,000)
- **Employer:** PKR 1,600/month (5% of minimum wage)

### PESSI (Punjab Social Security)
- **Employee:** 1% of gross salary
- **Employer:** 6% of gross salary

### Provident Fund (Optional)
- Employee & Employer: 8.33% each (configurable)

---

## Security Configuration

### Production Checklist
- [ ] Set `APP_DEBUG=false`
- [ ] Use HTTPS (SSL certificate)
- [ ] Set `SESSION_SECURE=true` on HTTPS
- [ ] Configure firewall (block direct DB access)
- [ ] Set up regular database backups
- [ ] Enable error logging to file (not browser)
- [ ] Review `.htaccess` security headers
- [ ] Remove `.env.example` from production

### .htaccess Key Security Features
```apache
# Enforced in public/.htaccess:
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=()
```

---

## Cron Jobs (Optional but Recommended)

Add to crontab (`crontab -e`):

```bash
# Daily attendance auto-mark (8 PM)
0 20 * * * php /var/www/html/hrms/artisan attendance:auto-mark >> /var/log/hrms_cron.log 2>&1

# Birthday notifications (9 AM daily)
0 9 * * * php /var/www/html/hrms/artisan notify:birthdays >> /var/log/hrms_cron.log 2>&1

# Database backup (2 AM daily)
0 2 * * * mysqldump -u hrms_user -pStrongPassword123! hrms_db | gzip > /backups/hrms_$(date +%Y%m%d).sql.gz

# Clean old logs (weekly)
0 3 * * 0 find /var/www/html/hrms/storage/logs -name "*.log" -mtime +30 -delete
```

---

## Troubleshooting

### White Screen / 500 Error
```bash
# Check PHP error log
tail -50 /var/log/apache2/hrms_error.log

# Enable debug temporarily
# In .env: APP_DEBUG=true
```

### Database Connection Error
```bash
php -r "new PDO('mysql:host=127.0.0.1;dbname=hrms_db', 'hrms_user', 'password');"
```

### Permission Denied on Upload
```bash
chown -R www-data:www-data storage/
chmod -R 775 storage/
```

### Rewrite Rules Not Working
```bash
a2enmod rewrite
# Ensure AllowOverride All in VirtualHost
apachectl -t && systemctl restart apache2
```

### Session Not Persisting
```bash
# Check session directory is writable
php -r "echo session_save_path();"
chmod 777 /tmp  # or your session path
```

---

## Default Roles & Permissions

| Role | Access Level |
|------|-------------|
| Super Admin | All modules — full access |
| HR Manager | Employees, Payroll, Leaves, Reports, Documents |
| Payroll Officer | Payroll processing, tax reports |
| Manager | Team attendance, leave approvals, tasks |
| Employee | Own profile, attendance, leaves, payslips |
| Accountant | Payroll view, financial reports |
| Receptionist | Attendance marking only |
| Auditor | Read-only access to audit logs |

---

## Support & Customization

- **Tech Stack:** Core PHP 7.4+, MySQL 8.0, Bootstrap 5.3, Chart.js
- **Architecture:** MVC (no framework dependency)
- **Database:** 30+ tables with soft deletes and audit trails
- **Security:** CSRF, XSS escaping, bcrypt passwords, IP-bound sessions, brute-force lockout
- **Compliance:** FBR 2024-25, EOBI, PESSI, Provident Fund

---

*HRMS Enterprise Platform — Built for Pakistan's corporate HR compliance needs.*
