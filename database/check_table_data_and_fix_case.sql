-- Check which table has data
SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL
SELECT 'Users', COUNT(*) FROM Users;

-- If 'users' has data and 'Users' is empty, run:
-- DROP TABLE `Users`;
-- RENAME TABLE `users` TO `Users`;

-- If 'Users' has data and 'users' is empty, run:
-- DROP TABLE `users`;

-- If both have data, export both tables for backup, then merge as needed before dropping/renaming.

-- Repeat this process for any other table with a similar case issue.
