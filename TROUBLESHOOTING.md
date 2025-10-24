# Troubleshooting Guide - Activity Control Extension

## Migration Error: "failed to open stream: No such file or directory"

### Problem
After consolidating migrations from v1.1.0-v1.4.0 into v1.0.0, phpBB still references old migration files in its database, causing errors like:

```
[phpBB Debug] PHP Warning: in file [ROOT]/phpbb/class_loader.php on line 160: 
require(./../ext/linkguarder/activitycontrol/migrations/v1_1_0/initial_migration.php): 
failed to open stream: No such file or directory
```

### Root Cause
phpBB tracks installed migrations in the `phpbb_migrations` table. When you delete migration files but don't clean the database, phpBB tries to load non-existent files.

## Solution

### Option 1: Clean Database (Recommended for Development)

1. **Disable the extension** in ACP (if enabled)
   - Go to: ACP → Customise → Manage extensions
   - Find "Activity Control" and click "Disable"

2. **Run the cleanup SQL script**
   ```bash
   # Using mysql CLI
   mysql -u your_username -p your_database < cleanup_migrations.sql
   
   # Or copy/paste into phpMyAdmin SQL tab
   ```
   
   The script will delete all migration entries for this extension.

3. **Re-enable the extension**
   - Go back to ACP → Customise → Manage extensions
   - Find "Activity Control" and click "Enable"
   - The `install_v1_0_0` migration will run fresh

### Option 2: Complete Reinstall (Recommended for Production)

1. **Backup your data** (if you have important logs)
   ```bash
   # Backup reported IPs
   cp data/reported_ips.json data/reported_ips.json.backup
   
   # Export logs from database
   mysqldump -u user -p database phpbb_ac_logs > ac_logs_backup.sql
   ```

2. **Disable and delete data** in ACP
   - ACP → Customise → Manage extensions
   - Click "Disable" for Activity Control
   - Click "Delete Data" (this removes tables and config)

3. **Remove old migration references**
   ```sql
   DELETE FROM phpbb_migrations 
   WHERE migration_name LIKE '%linkguarder\\activitycontrol%';
   ```

4. **Enable the extension**
   - Click "Enable" in ACP
   - Fresh install with consolidated migration

### Option 3: Manual Database Cleanup

If you can't access phpMyAdmin or mysql CLI:

1. Go to phpMyAdmin
2. Select your phpBB database
3. Browse the `phpbb_migrations` table
4. Search for rows containing `linkguarder\activitycontrol`
5. Delete all matching rows
6. Re-enable the extension in ACP

## Prevention

This issue occurred because of a version reset and migration consolidation. To prevent similar issues:

1. **Always disable extensions** before pulling major updates
2. **Follow semantic versioning** - don't reset to 1.0.0 on production
3. **Test migrations** on a staging database first
4. **Document breaking changes** in CHANGELOG.md

## Verification

After following any solution above, verify the extension is working:

```bash
# Check migration status
cd /home/nox/Documents/NiMP/var/www/forum
mysql -u user -p -e "SELECT migration_name FROM phpbb_migrations WHERE migration_name LIKE '%activitycontrol%';"

# Should show only:
# \linkguarder\activitycontrol\migrations\install_v1_0_0
```

Test in phpBB:
1. Go to ACP → Extensions → Activity Control → Settings
2. Configure settings (should load without errors)
3. View Logs tab (should show empty or existing logs)
4. Check error logs: `[ROOT]/store/` should have no PHP warnings

## Additional Issues

### OpenSSL Extension Missing

**Error**: Extension cannot be enabled
**Solution**: Install PHP OpenSSL extension
```bash
sudo apt-get install php-openssl
sudo systemctl restart apache2  # or php-fpm
```

### Permission Denied on data/reported_ips.json

**Error**: Cannot write to JSON file
**Solution**: Fix permissions
```bash
cd /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol
chmod 666 data/reported_ips.json
chmod 755 data/
```

### Private Key Not Found

**Error**: IP reporting fails silently
**Solution**: Copy private key to correct location
```bash
cp /home/nox/Documents/roguebb/private_key.pem \
   /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/data/
chmod 600 /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/data/private_key.pem
chown www-data:www-data /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/data/private_key.pem
```

## Support

If issues persist:

1. Check phpBB error logs: `/path/to/phpbb/store/`
2. Enable debug mode in `config.php`: `@define('DEBUG', true);`
3. Review Apache/Nginx error logs
4. Check PHP version: `php -v` (requires 7.4+)

## Rolling Back

If you need to revert to old migrations:

```bash
cd /home/nox/Documents/phpbb-ext
git log --oneline  # Find commit before reset
git checkout <commit-hash> migrations/
git commit -m "Revert to fragmented migrations"
```

Then follow "Complete Reinstall" procedure above.
