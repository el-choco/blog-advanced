# Database Migrations

This folder contains database migration files for Blog Advanced.

## Current Schema Version: 2.0.0

### Installation

```bash
# Import the complete schema
mysql -u your_user -p your_database < schema.sql

Tables
users - Multi-user support with roles
comments - Comment system with moderation
backups - Backup management
audit_log - Activity tracking
login_attempts - Brute-force protection
ip_whitelist - IP security
sessions - Session management
notifications - Notification system
scheduled_tasks - Automation tasks
Default Login
Username: admin
Password: admin123
⚠️ CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN!
