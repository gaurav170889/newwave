<?php
// Optional alternative to `.env` for PHP-only local/server overrides.
// Copy this file to `includes/variables.local.php` and set your real values.

putenv('APP_BASE_URL=https://your-domain.com/newwave');
putenv('APP_BASE_PATH=/newwave');
putenv('DB_HOST=localhost');
putenv('DB_USER=your_db_user');
putenv('DB_PASS=your_db_password');
putenv('DB_NAME=dialerwave');
putenv('SMTP_HOST=smtp.your-provider.com');
putenv('SMTP_PORT=587');
putenv('SMTP_USERNAME=your_smtp_username');
putenv('SMTP_PASSWORD=your_smtp_password');
putenv('SMTP_SECURE=tls');
putenv('SMTP_FROM_EMAIL=no-reply@your-domain.com');
putenv('SMTP_FROM_NAME=New Wave Dialer');
