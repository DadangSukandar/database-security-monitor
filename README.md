# Database Security Monitoring Platform

A Laravel-based database security monitoring and assessment platform inspired by enterprise database security solutions such as IBM Guardium.

This project is being developed as an open-source learning and development project for monitoring databases, discovering database assets, analyzing database activity, detecting security risks, performing vulnerability assessments, and generating security alerts.

> **Project Status:** Active Development  
> This project is still under development. Features, database structures, APIs, and user interfaces may change.

---

## Overview

Database Security Monitoring Platform provides a centralized interface for connecting to database servers and performing security-related monitoring and assessment.

The project is designed for:

- Learning database security concepts
- Database security monitoring
- Database discovery
- Database activity analysis
- Vulnerability assessment
- Sensitive data discovery
- Security alert management
- Security policy management
- Security auditing
- Security reporting

The long-term goal is to provide an accessible open-source environment for experimenting with database security monitoring concepts.

---

## Features

### Database Connection Management

Manage database server connections from a centralized interface.

Current database targets include:

- MySQL
- PostgreSQL

Database credentials are handled through Laravel configuration and encrypted model attributes where implemented.

---

### Database Discovery

Discover database structures including:

- Databases
- Schemas
- Tables
- Columns
- Data types
- Estimated rows
- Database metadata

This allows administrators to understand database assets connected to the platform.

---

### Database Explorer

Explore discovered database structures directly from the application.

The explorer provides visibility into database objects such as:

- Database names
- Schemas
- Tables
- Columns
- Table data

---

### Database Activity Monitoring

Collect and analyze database activity information.

Activity records can contain information such as:

- Database
- Schema
- Table
- Database user
- Client IP
- SQL action
- SQL query
- Execution status
- Execution time
- Execution timestamp

This module is intended to provide visibility into database operations and suspicious activity.

---

### SQL Query Tools

The application includes tools for database query testing and analysis.

Features include:

- SQL Query Runner
- Query execution results
- Query history
- Database activity logging

> Be careful when enabling query execution against production databases.

---

### Database Users & Privileges

Inspect database users and their privileges to help identify excessive permissions and risky configurations.

Examples include detecting accounts with:

- Administrative privileges
- SUPER privileges
- GRANT privileges
- Excessive database permissions
- Potentially unsafe account configurations

---

### Vulnerability Assessment

Run security assessments against connected databases.

The vulnerability assessment system can generate findings with:

- Rule code
- Category
- Severity
- Title
- Description
- Recommendation
- Database
- User
- Host
- Evidence
- Resolution status

Severity levels include:

- CRITICAL
- HIGH
- MEDIUM
- LOW

Assessment results are also used to calculate a database security score.

---

### Security Findings

Security findings provide centralized visibility into detected security issues.

Findings can contain:

- Finding type
- Category
- Severity
- Affected database
- Affected object
- Database username
- Description
- Recommendation
- Detection time
- Resolution status

---

### Security Alerts

Security alerts are designed to notify administrators about security events detected by the monitoring system.

The project includes components for:

- Alert generation
- Alert escalation
- Alert history
- Alert notifications
- SLA monitoring
- Alert acknowledgement

---

### Security Policies

Security policies can be used to define and manage security monitoring rules.

This module is being expanded as development continues.

---

### Sensitive Data Discovery

Scan database metadata and content to help identify potentially sensitive information.

The goal of this module is to help identify data that may require additional protection or monitoring.

---

### Security Dashboard

The dashboard provides an overview of the current security posture.

Current metrics include:

- Total database connections
- Active database connections
- Security score
- Critical findings
- High findings
- Medium findings
- Low findings
- Total open findings
- Recent security findings

---

### Teams & Authentication

The project includes authentication and team-based functionality.

Features include:

- User authentication
- Team creation
- Team membership
- Team invitations
- Team switching
- Team permissions
- Profile management
- Security settings

---

## Technology Stack

The project currently uses:

| Technology | Purpose |
|---|---|
| Laravel 13 | Backend framework |
| PHP 8.5+ | Application runtime |
| SQLite | Local development database |
| MySQL | Monitored database target |
| PostgreSQL | Monitored database target |
| Blade | Security monitoring interface |
| React / TypeScript | Authentication and team UI components |
| Vite | Frontend build system |
| PHPUnit / Pest | Automated testing |

---

## Requirements

Before installing the project, make sure your environment has:

- PHP 8.5 or compatible version
- Composer
- Node.js
- npm
- Git
- Required PHP database extensions

For MySQL monitoring:

```bash
php -m
```

Make sure the MySQL/PDO extension is available.

For PostgreSQL monitoring, make sure the PostgreSQL PDO extension is installed.

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/DadangSukandar/Modipkun.git
cd Modipkun
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Create environment configuration

On Linux/macOS:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Configure the application database

For local development, SQLite can be used.

Create:

```text
database/database.sqlite
```

Then configure `.env`:

```env
DB_CONNECTION=sqlite
```

### 7. Run migrations

```bash
php artisan migrate
```

### 8. Build frontend assets

Development:

```bash
npm run dev
```

Or production build:

```bash
npm run build
```

### 9. Start Laravel

```bash
php artisan serve
```

The application will normally be available at:

```text
http://127.0.0.1:8000
```

---

## Connecting a Database

After starting the application:

1. Open the Database Connections page.
2. Add a database connection.
3. Select the database driver.
4. Enter the host and port.
5. Enter the database name.
6. Enter a database username.
7. Enter the database password.
8. Save the connection.
9. Test or scan the database using the available security modules.

For security reasons, use a dedicated database account with only the permissions required for monitoring whenever possible.

---

## Testing

The project includes automated tests for authentication, teams, dashboard functionality, security alerts, findings, routing, pagination, and other application components.

Run all tests:

```bash
php artisan test
```

You can also run individual test files:

```bash
php artisan test tests/Feature/DashboardTest.php
```

Before contributing code, make sure the test suite passes.

---

## Security

This application interacts with database credentials and potentially sensitive database information.

### Never commit secrets

Do not commit:

```text
.env
database/*.sqlite
credentials
API keys
database passwords
private keys
production configuration
```

The repository `.gitignore` is configured to exclude common sensitive and generated files.

### Recommended database permissions

For monitoring and discovery, prefer a dedicated read-only or least-privilege database account.

Avoid connecting using:

```text
root
superuser
administrator
```

unless it is strictly required in an isolated testing environment.

### Production Warning

This project is currently under active development.

Do not deploy it as a production security control without performing your own:

- Security review
- Permission review
- Threat modeling
- Penetration testing
- Database privilege review
- Configuration review

The project should not currently be considered a replacement for commercial database security products.

---

## Project Structure

Important directories:

```text
app/
├── Console/Commands/
├── Http/Controllers/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
└── Services/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
└── views/

routes/
├── console.php
├── settings.php
└── web.php

tests/
├── Feature/
└── Unit/
```

---

## Main Modules

Current development includes:

```text
Dashboard
│
├── Database Connections
├── Database Discovery
├── Database Explorer
├── Database Activity
├── Sensitive Data Discovery
├── Database Users
├── Database Privileges
├── SQL Query
├── Query History
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
└── Vulnerability Assessment
```

---

## Roadmap

Planned areas of development include:

- Improved database activity monitoring
- Additional vulnerability assessment rules
- PostgreSQL security assessment improvements
- Improved security scoring
- Advanced sensitive data classification
- Security policy enforcement
- Alert correlation
- Improved alert notification channels
- Scheduled vulnerability scanning
- Historical security analytics
- Security trend visualization
- Improved security reports
- Role-based access control improvements
- Additional database engines
- REST API
- Docker deployment
- Improved documentation
- Production security hardening

---

## Contributing

Contributions are welcome.

If you want to contribute:

1. Fork the repository.
2. Create a feature branch.

```bash
git checkout -b feature/my-feature
```

3. Make your changes.
4. Run the tests.

```bash
php artisan test
```

5. Commit your changes.

```bash
git add .
git commit -m "Add my feature"
```

6. Push your branch.

```bash
git push origin feature/my-feature
```

7. Open a Pull Request.

When contributing security rules or vulnerability checks, please explain:

- What the rule detects
- Why it is considered a security risk
- Supported database engine
- Severity
- Recommended remediation
- How the rule was tested

---

## Development Status

This project is actively being developed.

Some modules may be experimental or incomplete. Breaking changes may occur while the architecture and security monitoring capabilities are improved.

Bug reports, documentation improvements, testing, security rule contributions, and feature proposals are welcome.

---

## Disclaimer

This project is intended for educational, research, development, and authorized database security testing purposes.

Only connect the application to databases that you own or have explicit authorization to access.

The maintainers and contributors are not responsible for unauthorized use, data loss, service disruption, or damage caused by improper use of this software.

---

## License

A license has not yet been finalized.

Before using this project in commercial or redistributed software, check the repository for the latest licensing information.

---

## Author

Developed and maintained by **DadangSukandar**.

Contributions from the open-source community are welcome.

---

## Acknowledgements

This project is inspired by concepts found in enterprise database security and monitoring platforms, including IBM Guardium.

This project is an independent project and is **not affiliated with, endorsed by, or an official product of IBM**.
