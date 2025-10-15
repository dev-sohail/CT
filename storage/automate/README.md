# CyberTirah Framework Control Center

Unified management script for the CyberTirah Framework.

## Quick Start

### Windows
```bash
cd Storage/automate
ct_control.bat
```

### Linux/Mac
```bash
cd Storage/automate
chmod +x ct_control.sh  # First time only
./ct_control.sh
```

## Features

### 1. WAMP/Apache Server Management
- Start/Stop/Restart services
- Check server status
- Monitor ports (80, 3306)

### 2. Environment Setup
- Create local .env (HTTP development)
- Create production .env (HTTPS)
- Edit .env configuration

### 3. Database Management
- Import database schema
- Setup login system with test accounts
- Backup database
- Import custom SQL files

### 4. Cache Management
- Clear all cache files
- Clear route cache
- Clear view cache
- Clear session cache
- Clear log files

### 5. Complete System Setup
One-command setup that:
- Creates .env file
- Imports database schema
- Clears cache
- Creates necessary directories
- Sets up test accounts

### 6. Development Tools
- Run framework tests
- View code statistics
- Generate modules (via web interface)
- Check permissions (Linux/Mac)

### 7. Backup & Restore
- Backup database
- Backup uploads
- Backup configuration
- Full system backup
- List available backups

### 8. System Information
- Framework version
- OS information
- PHP version
- MySQL version
- Installed modules count
- Disk space

## Default Test Accounts

All test accounts use password: `admin123`

| Role    | Username      | Email                  |
|---------|---------------|------------------------|
| Admin   | admin         | admin@frame.ct.com     |
| Student | john.student  | john@student.com       |
| Teacher | jane.teacher  | jane@teacher.com       |
| Parent  | bob.parent    | bob@parent.com         |
| Staff   | alice.staff   | alice@staff.com        |

## Menu Navigation

### Main Menu
```
[1] WAMP Server Management
[2] Environment Setup
[3] Database Management
[4] Cache Management
[5] Complete System Setup
[6] Development Tools
[7] Backup & Restore
[8] System Information
[0] Exit
```

## Backup Locations

Backups are stored in the `Backups/` directory:

- **Database**: `Backups/db/`
- **Uploads**: `Backups/uploads/`
- **Configs**: `Backups/configs/`

## Common Tasks

### First Time Setup
1. Run the control script
2. Select `[5] Complete System Setup`
3. Follow the prompts
4. Access admin panel at `/admin`

### Daily Development
1. Start WAMP: `[1] -> [1]`
2. Clear cache when needed: `[4] -> [1]`
3. Backup before major changes: `[7] -> [4]`

### Database Operations
1. Regular backups: `[3] -> [3]`
2. Import new schema: `[3] -> [1]`
3. Reset to defaults: `[3] -> [2]`

### Cache Issues
If you experience routing or view issues:
1. Go to `[4] Cache Management`
2. Select `[1] Clear All Cache`
3. Restart your browser

## Troubleshooting

### Script Won't Run (Windows)
- Right-click `ct_control.bat`
- Select "Run as Administrator"

### Script Won't Run (Linux/Mac)
```bash
chmod +x ct_control.sh
./ct_control.sh
```

### Database Import Fails
- Check MySQL is running
- Verify credentials
- Ensure database exists
- Check file path is correct

### Permission Denied (Linux/Mac)
```bash
sudo chmod -R 755 Storage
sudo chmod -R 755 Backups
```

## Integration with Other Scripts

This unified script replaces:
- `START_WAMP.bat`
- `create_local_env.bat`
- `setup_login_system.bat`
- `setup_complete_system.bat`
- `Storage/automate/backup_db.sh`
- `Storage/automate/deploy.sh`
- `Storage/automate/clear_cache.sh`

You can still use individual scripts if needed, but this control center provides all functionality in one place.

## Advanced Usage

### Custom Database Credentials
When prompted, you can specify:
- Custom MySQL host (not just localhost)
- Different username
- Custom database name
- Different port (default: 3306)

### Automated Backups
Schedule the backup commands using:
- **Windows**: Task Scheduler
- **Linux**: Cron jobs

Example cron (daily backup at 2 AM):
```cron
0 2 * * * cd /path/to/Frame/Storage/automate && ./ct_control.sh << EOF
7
1
localhost
root
yourpassword
ct_frame
0
0
EOF
```

## Support

For issues or questions:
1. Check the main README.md
2. Review documentation in Docs/
3. Check admin panel at `/admin`

## Version

Control Center v2.0
Compatible with CyberTirah Framework v2.0+


## Reorganization Utility

Standardize module directory structure (Controllers/Models/Views), normalize `Common` folder casing, ensure `routes.json` standard name, and clear route cache.

- Dry run (preview changes):
```bash
php Storage/automate/reorg.php
```

- Apply changes:
```bash
php Storage/automate/reorg.php --yes
```

Notes:
- Run from the project root.
- After applying, refresh the app; route cache is cleared automatically.
- On Windows, the script safely handles case-only renames via a temporary hop.
