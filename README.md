[![Why PHP](https://img.shields.io/badge/Why_PHP-in_2026-7A86E8?style=flat-square&labelColor=18181b)](https://whyphp.dev)

# School Timetable Management System - Complete Deployment Guide

## 📋 Project Overview

A complete Laravel 12 + Filament PHP 4.x school timetable management system with:
- Classes 1-10 with sections A/B
- Automatic timetable generation with constraint handling
- Teacher management with availability tracking
- Combined period handling (martial arts, etc.)
- Conflict detection and resolution
- PDF export capabilities
- SQLite database (single file, no server needed)

---

## 🖥️ System Requirements

### Minimum Requirements
- **OS**: Windows 7, 8, 10, or 11
- **PHP**: 8.2 or higher
- **RAM**: 2GB minimum (4GB recommended)
- **Disk Space**: 500MB
- **Composer**: Latest version
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher

### PHP Extensions Required
```
php_fileinfo
php_mbstring
php_openssl
php_pdo
php_sqlite3
php_tokenizer
php_xml
php_ctype
php_json
php_bcmath
```

---

## 🚀 Quick Installation (Step-by-Step)

### Step 1: Install PHP (if not already installed)

**Option A: Using XAMPP (Recommended for beginners)**
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP (includes PHP, Apache)
3. Add PHP to PATH:
   - Open System Properties → Environment Variables
   - Add `C:\xampp\php` to PATH variable

**Option B: Standalone PHP**
1. Download PHP 8.2+ from https://windows.php.net/download/
2. Extract to `C:\php`
3. Copy `php.ini-development` to `php.ini`
4. Enable required extensions in `php.ini`
5. Add `C:\php` to PATH

### Step 2: Install Composer
1. Download from https://getcomposer.org/download/
2. Run installer (it will find PHP automatically)
3. Verify: Open Command Prompt, run `composer --version`

### Step 3: Install Node.js & NPM
1. Download from https://nodejs.org/ (LTS version)
2. Run installer
3. Verify: `node --version` and `npm --version`

### Step 4: Create Laravel Project
```batch
:: Open Command Prompt as Administrator
cd C:\

:: Create project
composer create-project laravel/laravel school-timetable

:: Navigate to project
cd school-timetable
```

### Step 5: Install Filament
```batch
composer require filament/filament:"^4.0"
php artisan filament:install --panels
```

### Step 6: Install Additional Dependencies
```batch
composer require barryvdh/laravel-dompdf
npm install
npm run build
```

### Step 7: Configure Database
1. Create `.env` file if not exists (copy from `.env.example`)
2. Edit `.env`:
```env
DB_CONNECTION=sqlite
# Comment out or remove these lines:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

3. Create SQLite database:
```batch
type nul > database\database.sqlite
```

### Step 8: Copy Project Files

Copy all the files I've provided into their respective locations:

**Migrations** → `database/migrations/`
- `xxxx_create_class_rooms_table.php`
- `xxxx_create_subjects_table.php`
- `xxxx_create_teachers_table.php`
- `xxxx_create_academic_terms_table.php`
- `xxxx_create_timetable_slots_table.php`
- `xxxx_create_combined_periods_table.php`
- `xxxx_create_holidays_and_constraints_tables.php`

**Models** → `app/Models/`
- `ClassRoom.php`
- `Subject.php`
- `Teacher.php`
- `AcademicTerm.php`
- `TimetableSlot.php`
- `CombinedPeriod.php`
- `Holiday.php`
- `Constraint.php`

**Services** → `app/Services/`
- `TimetableGeneratorService.php`

**Filament Resources** → `app/Filament/Resources/`
- `ClassRoomResource.php`
- `SubjectResource.php`
- `TeacherResource.php`

**Filament Pages** → `app/Filament/Pages/`
- `TimetableGenerator.php`
- `TimetableViewer.php`
- `TeacherSchedule.php`
- `ConflictChecker.php`
- `PrintCenter.php`

**Views** → `resources/views/filament/`
Create folders: `pages/` and `components/`
- `pages/timetable-generator.blade.php`
- `pages/timetable-viewer.blade.php`
- `pages/teacher-schedule.blade.php`
- `pages/conflict-checker.blade.php`
- `pages/print-center.blade.php`
- `components/generation-summary.blade.php`

**Seeder** → `database/seeders/`
- `DatabaseSeeder.php`

### Step 9: Create Filament Resource Page Classes

For each resource, create the page classes:

```batch
php artisan make:filament-page ListClassRooms --resource=ClassRoomResource --type=List
php artisan make:filament-page CreateClassRoom --resource=ClassRoomResource --type=Create
php artisan make:filament-page EditClassRoom --resource=ClassRoomResource --type=Edit

php artisan make:filament-page ListSubjects --resource=SubjectResource --type=List
php artisan make:filament-page CreateSubject --resource=SubjectResource --type=Create
php artisan make:filament-page EditSubject --resource=SubjectResource --type=Edit

php artisan make:filament-page ListTeachers --resource=TeacherResource --type=List
php artisan make:filament-page CreateTeacher --resource=TeacherResource --type=Create
php artisan make:filament-page EditTeacher --resource=TeacherResource --type=Edit
```

### Step 10: Run Migrations & Seed Database
```batch
php artisan migrate
php artisan db:seed
```

### Step 11: Create Admin User
```batch
php artisan make:filament-user
```
Enter when prompted:
- Name: Admin
- Email: admin@school.com
- Password: password (or your choice)

### Step 12: Start Development Server
```batch
php artisan serve
```

Visit: **http://localhost:8000/admin**

Login with credentials created in Step 11.

---

## 📁 Complete File Structure

```
school-timetable/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   │   ├── TimetableGenerator.php
│   │   │   ├── TimetableViewer.php
│   │   │   ├── TeacherSchedule.php
│   │   │   ├── ConflictChecker.php
│   │   │   └── PrintCenter.php
│   │   └── Resources/
│   │       ├── ClassRoomResource/
│   │       │   └── Pages/
│   │       │       ├── ListClassRooms.php
│   │       │       ├── CreateClassRoom.php
│   │       │       └── EditClassRoom.php
│   │       ├── SubjectResource/
│   │       ├── TeacherResource/
│   │       ├── ClassRoomResource.php
│   │       ├── SubjectResource.php
│   │       └── TeacherResource.php
│   ├── Models/
│   │   ├── ClassRoom.php
│   │   ├── Subject.php
│   │   ├── Teacher.php
│   │   ├── AcademicTerm.php
│   │   ├── TimetableSlot.php
│   │   ├── CombinedPeriod.php
│   │   ├── Holiday.php
│   │   └── Constraint.php
│   └── Services/
│       └── TimetableGeneratorService.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_class_rooms_table.php
│   │   ├── 2024_01_01_000002_create_subjects_table.php
│   │   ├── 2024_01_01_000003_create_teachers_table.php
│   │   ├── 2024_01_01_000004_create_academic_terms_table.php
│   │   ├── 2024_01_01_000005_create_timetable_slots_table.php
│   │   ├── 2024_01_01_000006_create_combined_periods_table.php
│   │   └── 2024_01_01_000007_create_holidays_and_constraints_tables.php
│   ├── seeders/
│   │   └── DatabaseSeeder.php
│   └── database.sqlite
├── resources/
│   └── views/
│       └── filament/
│           ├── pages/
│           │   ├── timetable-generator.blade.php
│           │   ├── timetable-viewer.blade.php
│           │   ├── teacher-schedule.blade.php
│           │   ├── conflict-checker.blade.php
│           │   └── print-center.blade.php
│           └── components/
│               └── generation-summary.blade.php
├── .env
├── composer.json
└── package.json
```

---

## 🎯 Usage Guide

### Initial Setup After Installation

1. **Login to Admin Panel**
   - URL: http://localhost:8000/admin
   - Email: admin@school.com
   - Password: (what you set during user creation)

2. **Set Up Academic Term**
   - Navigate to: **Academic Terms** (sidebar)
   - Click **New Academic Term**
   - Fill in:
     - Name: "2024-2025 Term 1"
     - Start Date: Select start date
     - End Date: Select end date
     - Check "Is Current"
   - Click **Create**

3. **Review Pre-loaded Data**
   The seeder has created:
   - 20 Classes (Class 1-10, Sections A & B)
   - 15+ Subjects across all levels
   - 10 Sample teachers with assigned subjects

4. **Generate Your First Timetable**
   - Go to: **Timetable Generator**
   - Step 1: Select classes (try 2-3 classes first)
   - Step 2: Configure settings (keep defaults)
   - Step 3: Click **Generate Timetable**
   - Review results

5. **View Generated Timetable**
   - Go to: **View Timetable**
   - Select Term and Class
   - View the grid layout

6. **Check for Conflicts**
   - Go to: **Conflict Checker**
   - Review any teacher scheduling conflicts

---

## 🔧 Customization

### Adding More Classes
```php
// In DatabaseSeeder.php or through admin panel
ClassRoom::create([
    'name' => 'Class 11',
    'section' => 'A',
    'level' => 'secondary_9_10',
    'weekly_periods' => 35,
    'status' => 'active',
]);
```

### Adding Custom Subjects
Admin Panel → Subjects → New Subject

### Setting Teacher Availability
Admin Panel → Teachers → Edit Teacher → Availability Section

### Creating Combined Periods
```php
CombinedPeriod::create([
    'name' => 'Martial Arts - Class 1 to 3',
    'subject_id' => $martialArtsSubject->id,
    'teacher_id' => $teacher->id,
    'class_room_ids' => [1, 2, 3, 4, 5, 6], // Class 1A, 1B, 2A, 2B, 3A, 3B
    'day' => 2, // Tuesday
    'period' => 3, // Third period
    'frequency' => 'weekly',
    'academic_term_id' => $currentTerm->id,
]);
```

---

## 🐛 Troubleshooting

### Common Issues

**1. "Class 'SQLite3' not found"**
```
Solution: Enable sqlite3 extension in php.ini
- Open php.ini
- Find ;extension=sqlite3
- Remove semicolon: extension=sqlite3
- Restart server
```

**2. "Permission denied" errors**
```batch
:: Run as Administrator
icacls "C:\school-timetable\storage" /grant Everyone:F /T
icacls "C:\school-timetable\bootstrap\cache" /grant Everyone:F /T
```

**3. "npm run build" fails**
```batch
:: Clear cache and reinstall
rd /s /q node_modules
del package-lock.json
npm install
npm run build
```

**4. Filament pages not showing**
```batch
php artisan filament:upgrade
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
```

**5. Database locked errors**
```
Solution: Close all connections to database
- Stop `php artisan serve`
- Close any DB browser tools
- Restart server
```

---

## 📊 Performance Optimization

### For Production Use

1. **Enable Caching**
```batch
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Optimize Autoloader**
```batch
composer install --optimize-autoloader --no-dev
```

3. **Set Environment to Production**
```env
APP_ENV=production
APP_DEBUG=false
```

---

## 🔒 Security Recommendations

1. **Change Default Credentials**
   - Change admin password immediately
   - Use strong passwords

2. **Secure .env File**
   - Never commit .env to version control
   - Set proper file permissions

3. **Generate New Application Key**
```batch
php artisan key:generate
```

---

## 📱 Accessing from Other Devices

To access from other computers on the same network:

```batch
php artisan serve --host=0.0.0.0 --port=8000
```

Then access via: `http://[YOUR-PC-IP]:8000/admin`

To find your IP:
```batch
ipconfig
:: Look for IPv4 Address
```

---

## 💾 Backup & Restore

### Backup
```batch
:: Backup database
copy database\database.sqlite database\backup\database_backup.sqlite

:: Backup entire project
xcopy /E /I /Y school-timetable school-timetable-backup
```

### Restore
```batch
:: Restore database
copy database\backup\database_backup.sqlite database\database.sqlite
```

---

## 📞 Support

For issues or questions:
1. Check this guide first
2. Review Laravel documentation: https://laravel.com/docs
3. Review Filament documentation: https://filamentphp.com/docs
4. Check error logs: `storage/logs/laravel.log`

---

## ✅ Post-Installation Checklist

- [ ] PHP 8.2+ installed and in PATH
- [ ] Composer installed
- [ ] Node.js & NPM installed
- [ ] Project created via composer
- [ ] Filament installed
- [ ] Database configured (SQLite)
- [ ] All migrations run successfully
- [ ] Database seeded with sample data
- [ ] Admin user created
- [ ] Can access admin panel
- [ ] Can view classes, subjects, teachers
- [ ] Can generate timetable
- [ ] Can view generated timetable

---

## 🎉 You're All Set!

Your School Timetable Management System is now ready to use. Explore the features, generate timetables, and customize it to your school's needs!