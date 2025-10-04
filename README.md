# HR & Attendance Management System

A comprehensive HR and attendance management system built with Laravel 12 and Filament v3.

## 🚀 Features

### Employee Management
- Complete employee profiles with contact information
- Role-based access control (Admin, HR, Manager, Employee)
- Department management
- Profile photo uploads
- Employee status tracking (Active/Inactive)

### Attendance Management
- Clock in/out functionality with GPS tracking
- Multiple location support (Office, Remote, Field)
- Late arrival and early departure detection
- Automatic deduction calculation based on configurable rules
- Manual attendance adjustments by managers/HR
- Comprehensive attendance history and reports

### Leave Management
- Multiple leave types (Annual, Sick, Casual, Maternity, etc.)
- Leave application and approval workflow
- Leave balance tracking with accrual rules
- Leave encashment support
- Carry-forward rules configuration
- Leave approval notifications

### Reporting & Analytics
- Real-time dashboard with key metrics
- Attendance reports (monthly, employee-wise, team-wise)
- Leave reports and analytics
- Export functionality (CSV, PDF)
- Comprehensive audit logs

### API Integration
- RESTful API for mobile app integration
- GPS-based attendance tracking
- Real-time attendance statistics
- Secure authentication with Sanctum

## 🛠️ Technology Stack

- **Backend**: Laravel 12
- **Admin Panel**: Filament v3
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Authentication**: Laravel Sanctum
- **Frontend**: Livewire + Alpine.js
- **Icons**: Heroicons

## 📋 Requirements

- PHP 8.2+
- Composer
- Node.js & NPM
- SQLite/MySQL/PostgreSQL

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd hr
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

## 🚀 System Access

- **Main URL**: http://localhost:8000 (redirects to login page)
- **Admin Panel**: http://localhost:8000/admin
- **API Base URL**: http://localhost:8000/api

## 🔐 Default Login Credentials

- **Admin**: admin@hr.com / password
- **Supervisor**: hr@hr.com / password (Jane Supervisor)
- **Employee**: john@hr.com / password (John Doe)
- **Employee**: sarah@hr.com / password (Sarah Smith)
- **Employee**: mike@hr.com / password (Mike Johnson)

## 👥 Role-Based Access Control

### Admin
- Full system access
- Can manage all employees, departments, roles
- Can approve/reject any leave application
- Access to all reports and analytics

### Supervisor
- Can view and manage their team members
- Can approve/reject leave applications from their team
- Access to team reports and attendance
- Can view team leave applications in dedicated panel

### Employee
- Can view their own profile and data
- Can apply for leave (goes to supervisor for approval)
- Can view their own leave applications and status
- Can clock in/out for attendance
- Access to personal dashboard with leave status

## 📱 API Endpoints

### Authentication
All API endpoints require authentication using Laravel Sanctum.

### Attendance API
- `POST /api/attendance/clock-in` - Clock in with GPS coordinates
- `POST /api/attendance/clock-out` - Clock out with GPS coordinates
- `GET /api/attendance/today` - Get today's attendance
- `GET /api/attendance/history` - Get attendance history
- `GET /api/attendance/statistics` - Get attendance statistics
- `GET /api/attendance/locations` - Get available locations

### Example API Usage
```bash
# Clock in
curl -X POST http://localhost:8000/api/attendance/clock-in \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"latitude": 40.7128, "longitude": -74.0060, "location_id": 1}'

# Get today's attendance
curl -X GET http://localhost:8000/api/attendance/today \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🗂️ Project Structure

```
hr/
├── app/
│   ├── Filament/
│   │   ├── Resources/          # Filament resources
│   │   └── Widgets/            # Dashboard widgets
│   ├── Http/Controllers/Api/   # API controllers
│   └── Models/                 # Eloquent models
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── routes/
│   ├── api.php                 # API routes
│   └── web.php                 # Web routes
└── public/                     # Public assets
```

## 📊 Database Schema

### Core Tables
- `users` - Employee information
- `departments` - Department data
- `roles` - User roles and permissions
- `attendance` - Daily attendance records
- `leave_types` - Leave type configurations
- `leave_applications` - Leave requests
- `leave_balances` - Employee leave balances
- `locations` - Office/remote locations
- `deduction_rules` - Lateness deduction rules
- `audit_logs` - System audit trail

## 🎯 Key Features Implementation

### Attendance System
- GPS-based location tracking
- Automatic late/early detection
- Configurable deduction rules
- Multi-location support
- Real-time status updates

### Leave Management
- Flexible leave type configuration
- Approval workflow with notifications
- Leave balance calculations
- Accrual rules (monthly/yearly)
- Encashment and carry-forward support

### Role-Based Access Control
- Admin: Full system access
- HR: Employee and leave management
- Manager: Team oversight and approvals
- Employee: Self-service features

## 🔧 Configuration

### Leave Types
Configure leave types in the admin panel:
- Annual Leave (18 days/year, encashable)
- Sick Leave (12 days/year)
- Casual Leave (6 days/year)
- Maternity Leave (90 days/year)
- And more...

### Deduction Rules
Set up lateness penalties:
- 4 minutes late → 0.5 hours deduction
- 5 minutes late → 1 hour deduction
- 6 minutes late → 1.5 hours deduction
- 7 minutes late → 2 hours deduction

### Locations
Configure office and remote locations:
- Main Office (GPS coordinates)
- Branch Office (GPS coordinates)
- Remote Work (no GPS required)
- Field Office (GPS coordinates)

## 📈 Reporting

### Available Reports
- Monthly attendance summary
- Employee job cards
- Leave balance reports
- Attendance analysis with trends
- Leave approval history
- Deduction reports

### Export Options
- CSV format for data analysis
- PDF format for official records
- Date range filtering
- Department/employee filtering

## 🚀 Deployment

### Production Setup
1. Configure production database
2. Set up file storage (S3 recommended)
3. Configure email settings
4. Set up SSL certificate
5. Configure queue workers
6. Set up monitoring

### Environment Variables
```env
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=hr_system
DB_USERNAME=your-username
DB_PASSWORD=your-password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=your-region
AWS_BUCKET=your-bucket
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Contact the development team

## 🔄 Version History

- **v1.0.0** - Initial release with core HR and attendance features
- **v1.1.0** - Added API endpoints and mobile support
- **v1.2.0** - Enhanced reporting and analytics
- **v1.3.0** - Improved leave management workflow

---

Built with ❤️ using Laravel and Filament