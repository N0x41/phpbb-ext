-- Cleanup script for Activity Control extension migration reset
-- Run this SQL in phpMyAdmin or mysql CLI after disabling the extension

-- Remove all old migration entries for the linkguarder\activitycontrol extension
DELETE FROM phpbb_migrations 
WHERE migration_name LIKE '%linkguarder\\\\activitycontrol%';

-- Verify cleanup (should return 0 rows)
SELECT * FROM phpbb_migrations 
WHERE migration_name LIKE '%linkguarder\\\\activitycontrol%';

-- After running this script:
-- 1. Go to ACP -> Customise -> Manage extensions
-- 2. Enable the Activity Control extension
-- 3. The install_v1_0_0 migration will run fresh
