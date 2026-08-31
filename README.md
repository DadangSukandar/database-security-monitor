# Database Security Monitor

**Database Security Monitor** is an open-source database security monitoring and assessment platform built with Laravel.

The project provides a centralized interface for discovering database assets, monitoring database activity, identifying security risks, detecting sensitive data, performing vulnerability assessments, managing security findings, and generating security alerts.

The project is inspired by concepts commonly found in enterprise database security platforms while remaining an independent open-source project.

> **Project Status: Active Development**
>
> Database Security Monitor is currently under active development. Features, database schemas, security rules, APIs, and user interfaces may change as development continues.

---

## About the Project

Modern applications often depend on multiple databases containing important or sensitive information.

Database administrators and developers need visibility into:

- What databases are available
- What tables and columns exist
- Who has access to the database
- What privileges database users have
- What queries are being executed
- What sensitive information may exist
- What security vulnerabilities are present
- What security events require attention

Database Security Monitor is being developed to provide these capabilities through a centralized web-based dashboard.

The long-term goal is to create an accessible open-source platform for learning, experimenting with, and implementing database security monitoring concepts.

---

## Key Features

### Database Connection Management

Manage monitored database servers from one interface.

Current database targets include:

- MySQL
- PostgreSQL

Connection information can include:

- Connection name
- Database driver
- Host
- Port
- Database name
- Schema
- Username
- Password
- Connection status
- Last connection time
- Last scan time

---

## Database Discovery

Discover database structures automatically.

The discovery system can collect information about:

- Databases
- Schemas
- Tables
- Columns
- Data types
- Table types
- Estimated row counts
- Database versions

This provides visibility into database assets connected to the monitoring platform.

---

## Database Explorer

Browse discovered databases directly from the application.

The Database Explorer provides visibility into:

- Databases
- Schemas
- Tables
- Columns
- Column types
- Table content

This feature is intended to help administrators understand the structure of monitored databases.

---

## Database Activity Monitoring

Database Security Monitor includes functionality for recording and analyzing database activity.

Activity information can include:

- Database connection
- Database name
- Schema
- Table
- Database username
- Client IP address
- SQL action
- SQL query
- Execution status
- Error information
- Execution time
- Execution timestamp

The goal is to provide visibility into database operations and potentially suspicious behavior.

---

## SQL Query Tools

The project includes database query tools for development, testing, and analysis.

Current functionality includes:

- SQL Query Runner
- Query results
- Query history
- Query execution tracking
- Database activity logging

> **Warning**
>
> Query execution should only be enabled for authorized users and databases.
> Extra care should be taken when connecting Database Security Monitor to production systems.

---

## Database Users

Inspect database accounts discovered from monitored database servers.

The system is being developed to provide visibility into:

- Database usernames
- Account hosts
- Authentication configuration
- Account status
- Administrative accounts
- Potentially risky database accounts

---

## Database Privileges

Analyze database privileges to help identify excessive or potentially dangerous permissions.

Examples include detection of accounts with:

- Administrative privileges
- SUPER privileges
- GRANT privileges
- Database creation privileges
- User creation privileges
- File privileges
- Process privileges
- Other elevated database permissions

The exact checks depend on the database engine.

---

## Vulnerability Assessment

Database Security Monitor can perform security assessments against connected databases.

Assessment results may include:

- Rule code
- Category
- Severity
- Finding title
- Description
- Recommendation
- Database name
- Username
- Host
- Evidence
- Resolution status

Supported severity levels include:

```text
CRITICAL
HIGH
MEDIUM
LOW
```

Assessment results contribute to the database security score.

---

## Security Score

The Security Score provides a simplified representation of the current security posture.

The score is calculated using detected security findings and their severity.

Security scoring will continue to evolve as additional security rules and assessment capabilities are implemented.

---

## Security Findings

Security findings provide centralized visibility into detected security issues.

A finding may contain:

- Database connection
- Database name
- Finding type
- Category
- Severity
- Title
- Description
- Object type
- Object name
- Database username
- Recommendation
- Status
- Detection timestamp
- Resolution timestamp

Findings can be used by other modules such as security dashboards, alerts, reports, and assessments.

---

## Security Alerts

Security alerts help administrators identify security events that require attention.

Current and planned alert functionality includes:

- Automatic alert generation
- Alert history
- Alert acknowledgement
- Alert escalation
- SLA monitoring
- Alert notifications
- Severity classification
- Security event tracking

The application also includes console commands and services for security alert processing.

---

## Security Policies

Security policies provide a foundation for defining database security monitoring rules.

The policy system is being developed to support:

- Security rule configuration
- Monitoring policies
- Severity configuration
- Alert conditions
- Security enforcement workflows

This module will continue to expand during development.

---

## Security Risk Analysis

Database Security Monitor includes functionality for identifying and managing database security risks.

Security risks can be generated from database configuration, users, privileges, activity, or other security assessment results.

---

## Sensitive Data Discovery

The Sensitive Data Discovery module is designed to identify potentially sensitive information stored in monitored databases.

The system can analyze database metadata and data patterns to help identify information that may require additional protection.

Future development will expand classification capabilities for areas such as:

- Personal information
- Contact information
- Authentication information
- Financial information
- Confidential business information
- Custom sensitive-data patterns

---

## Security Audit

Security Audit functionality provides structured database security assessments.

The goal of this module is to provide administrators with repeatable security checks and historical assessment information.

---

## Security Reports

Security reports provide a consolidated view of database security information.

Current and planned reporting functionality includes:

- Security assessment reports
- Finding reports
- Security comparison reports
- Printable reports
- Historical security information
- Security score information

---

## Dashboard

The main dashboard provides an overview of the monitored environment.

Current dashboard metrics include:

- Total database connections
- Active database connections
- Security score
- Critical findings
- High findings
- Medium findings
- Low findings
- Total open findings
- Recent security findings
- Recent database connections

---

## Authentication and Teams

Database Security Monitor includes user authentication and team functionality.

Features include:

- User authentication
- User profiles
- Security settings
- Team creation
- Team membership
- Team invitations
- Team switching
- Team permissions
- Team-aware application navigation

This architecture provides a foundation for multi-user and multi-team environments.

---

# Technology Stack

Database Security Monitor currently uses:

| Technology | Purpose |
|---|---|
| Laravel 13 | Backend application framework |
| PHP 8.5+ | Application runtime |
| SQLite | Local application database |
| MySQL | Supported monitored database |
| PostgreSQL | Supported monitored database |
| Blade | Database security monitoring UI |
| React | Interactive frontend components |
| TypeScript | Frontend development |
| Vite | Frontend build system |
| Pest / PHPUnit | Automated testing |
| Git | Version control |

---

# Requirements

Before installing Database Security Monitor, make sure the following software is available:

- PHP
- Composer
- Node.js
- npm
- Git
- SQLite or another supported application database
- Required PHP PDO database extensions

Recommended development environment:

```text
PHP 8.5+
Laravel 13
Node.js
Composer
Git
```

---

# Installation

## 1. Clone the Repository

Clone Database Security Monitor from GitHub:

```bash
git clone https://github.com/DadangSukandar/database-security-monitor.git
```

Enter the project directory:

```bash
cd database-security-monitor
```

---

## 2. Install PHP Dependencies

Run:

```bash
composer install
```

---

## 3. Install Frontend Dependencies

Run:

```bash
npm install
```

---

## 4. Create Environment File

### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

### Linux / macOS

```bash
cp .env.example .env
```

---

## 5. Generate Application Key

Run:

```bash
php artisan key:generate
```

---

## 6. Configure the Application Database

SQLite is convenient for local development.

Create:

```text
database/database.sqlite
```

On Windows PowerShell:

```powershell
New-Item database/database.sqlite -ItemType File
```

Configure `.env`:

```env
DB_CONNECTION=sqlite
```

---

## 7. Run Database Migrations

Run:

```bash
php artisan migrate
```

> Do not use `php artisan migrate:fresh` on an existing installation containing data you want to keep.

---

## 8. Build Frontend Assets

For development:

```bash
npm run dev
```

For a production build:

```bash
npm run build
```

---

## 9. Start Laravel

Run:

```bash
php artisan serve
```

By default, the application should be available at:

```text
http://127.0.0.1:8000
```

---

# Development Setup

For local development, it is usually useful to run Laravel and Vite in separate terminals.

### Terminal 1

```bash
php artisan serve
```

### Terminal 2

```bash
npm run dev
```

Then open:

```text
http://127.0.0.1:8000
```

---

# Connecting a Database

After starting the application:

1. Open **Database Connections**.
2. Add a new database connection.
3. Select the database driver.
4. Enter the database host.
5. Enter the database port.
6. Enter the database name.
7. Enter the database username.
8. Enter the database password.
9. Save the connection.
10. Run the available discovery or security scanning functionality.

---

## MySQL

A typical MySQL connection uses:

```text
Driver: mysql
Host: 127.0.0.1
Port: 3306
Database: your_database
Username: monitoring_user
Password: ********
```

Make sure PHP has the MySQL PDO extension enabled.

You can inspect PHP modules with:

```bash
php -m
```

---

## PostgreSQL

A typical PostgreSQL connection uses:

```text
Driver: pgsql
Host: 127.0.0.1
Port: 5432
Database: your_database
Username: monitoring_user
Password: ********
```

Make sure PHP has the PostgreSQL PDO extension enabled.

---

# Database Account Security

Whenever possible, create a dedicated monitoring account.

Use the principle of least privilege.

Avoid connecting Database Security Monitor using accounts such as:

```text
root
postgres
administrator
superuser
```

unless elevated access is specifically required for a security assessment in an authorized testing environment.

The permissions required depend on which monitoring and assessment features are being used.

---

# Testing

Database Security Monitor includes automated tests for application functionality.

Run the complete test suite:

```bash
php artisan test
```

Run an individual test:

```bash
php artisan test tests/Feature/DashboardTest.php
```

The project currently includes tests covering areas such as:

- Authentication
- Dashboard
- Teams
- Team invitations
- Team members
- Profile settings
- Security settings
- Route integrity
- Navigation consistency
- Pagination
- Security findings
- Security alerts
- Alert history
- Alert notifications
- Alert SLA behavior
- Security dashboard analytics

Some tests may be skipped when optional features such as two-factor authentication are disabled.

---

# Useful Artisan Commands

Clear Laravel caches:

```bash
php artisan optimize:clear
```

View application routes:

```bash
php artisan route:list
```

Check migration status:

```bash
php artisan migrate:status
```

Run tests:

```bash
php artisan test
```

Open Laravel Tinker:

```bash
php artisan tinker
```

---

# Project Structure

Important project directories include:

```text
database-security-monitor/
│
├── app/
│   ├── Actions/
│   ├── Console/
│   │   └── Commands/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Notifications/
│   ├── Observers/
│   ├── Policies/
│   ├── Providers/
│   └── Services/
│
├── bootstrap/
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── public/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│
├── routes/
│   ├── console.php
│   ├── settings.php
│   └── web.php
│
├── storage/
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── .env.example
├── artisan
├── composer.json
├── package.json
└── README.md
```

---

# Application Modules

The current application architecture includes:

```text
Database Security Monitor
│
├── Dashboard
│
├── Database Management
│   ├── Database Connections
│   ├── Database Discovery
│   ├── Database Explorer
│   ├── Database Users
│   └── Database Privileges
│
├── Activity Monitoring
│   ├── Database Activity
│   ├── SQL Query
│   └── Query History
│
├── Data Security
│   └── Sensitive Data Discovery
│
├── Security
│   ├── Security Dashboard
│   ├── Security Findings
│   ├── Security Risks
│   ├── Security Alerts
│   ├── Security Policies
│   ├── Security Audit
│   └── Security Reports
│
├── Assessment
│   └── Vulnerability Assessment
│
└── Administration
    ├── Authentication
    ├── Teams
    ├── Team Members
    ├── Team Invitations
    └── User Settings
```

---

# Security Architecture

The project currently contains dedicated services and components for database security functionality.

Examples include:

```text
DatabaseConnectorService
DatabaseDiscoveryService
DatabaseActivityLogger
SensitiveDataDiscoveryService
SecurityRiskScannerService
SecurityAuditScanner
SecurityScoreService
SecurityAlertService
SecurityAlertDetectionService
SecurityAlertNotificationService
```

This separation allows security scanning and monitoring logic to evolve independently from controllers and presentation logic.

---

# Security

Because this application works with database systems and database credentials, security must be treated carefully.

## Never Commit Secrets

Never commit:

```text
.env
database passwords
API keys
private keys
production credentials
database dumps
production SQLite databases
authentication tokens
```

Files such as these should remain outside version control.

---

## Environment Configuration

`.env.example` may be committed because it should contain only example configuration.

The real:

```text
.env
```

must never contain credentials that are pushed to a public repository.

---

## SQLite

Local SQLite databases may contain:

- Users
- Password hashes
- Database connection information
- Security findings
- Alerts
- Query history
- Other application information

For that reason, actual SQLite database files should not be committed to a public repository.

---

# Production Usage

Database Security Monitor is currently an actively developed project.

Before using it in a production environment, perform your own:

- Security review
- Code review
- Authentication review
- Authorization review
- Database privilege review
- Threat modeling
- Penetration testing
- Dependency review
- Configuration review
- Backup planning
- Logging review
- Network security review

This project should not currently be considered a drop-in replacement for a commercial enterprise database security product.

---

# Roadmap

Planned areas of development include:

- [ ] Improved real-time database activity monitoring
- [ ] Additional MySQL vulnerability rules
- [ ] Expanded PostgreSQL vulnerability assessment
- [ ] Improved database security scoring
- [ ] Advanced sensitive data classification
- [ ] Custom sensitive-data detection patterns
- [ ] Security policy enforcement
- [ ] Advanced alert correlation
- [ ] Additional notification channels
- [ ] Scheduled security scanning
- [ ] Historical security analytics
- [ ] Security trend visualization
- [ ] Improved security reports
- [ ] Role-based access control improvements
- [ ] Multi-team security isolation improvements
- [ ] Additional database engines
- [ ] REST API
- [ ] API authentication
- [ ] Docker support
- [ ] Deployment documentation
- [ ] Improved user documentation
- [ ] Security rule documentation
- [ ] Production security hardening

---

# Contributing

Contributions are welcome.

You can contribute by:

- Reporting bugs
- Improving documentation
- Adding tests
- Improving the UI
- Adding database support
- Creating vulnerability rules
- Improving sensitive-data detection
- Improving security reports
- Improving performance
- Reviewing security-related code

---

## Contribution Workflow

### 1. Fork the repository

Fork the project to your GitHub account.

### 2. Clone your fork

```bash
git clone https://github.com/YOUR-USERNAME/database-security-monitor.git
```

### 3. Create a feature branch

```bash
git checkout -b feature/my-feature
```

### 4. Make your changes

Implement and test the feature.

### 5. Run tests

```bash
php artisan test
```

### 6. Commit

```bash
git add .
git commit -m "Add my feature"
```

### 7. Push

```bash
git push origin feature/my-feature
```

### 8. Open a Pull Request

Open a Pull Request explaining the purpose of the change.

---

# Contributing Security Rules

When contributing vulnerability or security detection rules, please document:

- Rule code
- Database engine
- Security category
- Severity
- What the rule detects
- Why the condition is a security risk
- Detection logic
- Possible false positives
- Recommended remediation
- How the rule was tested

Security rules should avoid destructive database operations.

---

# Bug Reports

When reporting a bug, please provide information such as:

```text
Operating System:
PHP Version:
Laravel Version:
Database Engine:
Database Version:

Steps to reproduce:

Expected behavior:

Actual behavior:

Error message:
```

Do **not** include passwords, private keys, API tokens, or other secrets in bug reports.

---

# Development Status

Database Security Monitor is currently under active development.

Some functionality may be:

- Experimental
- Incomplete
- Changed between versions
- Intended primarily for development and learning environments

Breaking changes may occur while the architecture continues to evolve.

---

# Responsible Use

Database Security Monitor is intended for:

- Educational use
- Database security research
- Development
- Security laboratories
- Authorized database assessments
- Authorized security testing

Only connect this application to databases and systems that you own or have explicit authorization to access.

---

# Disclaimer

Database Security Monitor is an independent open-source project.

It is **not affiliated with, endorsed by, sponsored by, or an official product of IBM**.

References to database security concepts or enterprise database security products are provided only to explain the type of security capabilities this project is exploring.

The maintainers and contributors are not responsible for unauthorized access, data loss, service disruption, security incidents, or damage resulting from improper use of this software.

---

# License

A project license has not yet been finalized.

Until a license is added, users should not assume that all forms of redistribution, modification, or commercial use are automatically permitted.

A formal open-source license should be added before wider distribution of the project.

---

# Author

Developed and maintained by **DadangSukandar**.

Community contributions are welcome.

---

# Support the Project

If you find Database Security Monitor useful, you can support development by:

- Testing the project
- Reporting bugs
- Suggesting improvements
- Improving documentation
- Contributing code
- Adding security assessment rules
- Reviewing existing functionality
- Sharing the project with other developers

---

## Database Security Monitor

**Open-source database visibility, monitoring, and security assessment for learning, development, and authorized security environments.**
