# CreatorzHive — Deployment Guide

> Step-by-step instructions for deploying CreatorzHive to development and production environments.

---

## Prerequisites

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 7.4 | 8.1+ |
| MySQL | 8.0 | 8.0 |
| Apache/Nginx | Any recent | Apache 2.4 with mod_rewrite |
| Composer | 2.x | 2.x |
| PHP Extensions | pdo, pdo_mysql, openssl, json, curl | + mbstring, gd/imagick |
| Disk space | 500MB | 2GB+ |

---

## Development Setup

### 1. Clone and Install

```bash
git clone <repo-url> /var/www/html/creatorzhive
cd /var/www/html/creatorzhive
composer install
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your local settings:

```env
APP_NAME=CreatorzHive
APP_URL=http://localhost/creatorzhive
APP_ENV=development
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=creatorz_hive
DB_USERNAME=root
DB_PASSWORD=your_db_password

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_user
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@creatorzhive.com
MAIL_FROM_NAME=CreatorzHive

SESSION_LIFETIME=120
SESSION_SECURE=false

APP_SECRET=any-random-32-char-string-here

SOCIAL_API_MOCK_FALLBACK=true
```

### 3. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE creatorz_hive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Run Migrations

```bash
php scripts/migrate.php
```

This runs `database/schema.sql` which creates all tables, views, and the trigger.

### 5. (Optional) Seed Demo Data

```bash
php scripts/seed.php
```

Runs seed files in `database/seeds/` for sample users, posts, deals, and analytics.

### 6. Start Development Server

```bash
composer serve
# OR
php -S 127.0.0.1:8080 -t . dev-server-router.php
```

Visit: `http://127.0.0.1:8080/?route=login`

### 7. Run Tests

```bash
./vendor/bin/phpunit
# OR with specific suite
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Integration
```

---

## Apache Production Deployment

### 1. Server Preparation

```bash
# Install dependencies (Ubuntu/Debian)
apt-get update
apt-get install -y php8.1 php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml \
                   mysql-server apache2 composer git

# Enable Apache modules
a2enmod rewrite
a2enmod headers
systemctl restart apache2
```

### 2. Deploy Application

```bash
# Deploy to server
git clone <repo-url> /var/www/html/creatorzhive
cd /var/www/html/creatorzhive
composer install --no-dev --optimize-autoloader
```

### 3. Configure Apache VirtualHost

Create `/etc/apache2/sites-available/creatorzhive.conf`:

```apache
<VirtualHost *:80>
    ServerName creatorzhive.yourdomain.com
    DocumentRoot /var/www/html/creatorzhive/public
    
    <Directory /var/www/html/creatorzhive/public>
        AllowOverride All
        Require all granted
        
        # Security: prevent access to PHP files in uploads
        <FilesMatch "\.php$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Block direct access to sensitive directories
    <Directory /var/www/html/creatorzhive/backend>
        Require all denied
    </Directory>
    
    <Directory /var/www/html/creatorzhive/src>
        Require all denied
    </Directory>
    
    <Directory /var/www/html/creatorzhive/vendor>
        Require all denied
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/creatorzhive-error.log
    CustomLog ${APACHE_LOG_DIR}/creatorzhive-access.log combined
</VirtualHost>
```

```bash
a2ensite creatorzhive.conf
systemctl reload apache2
```

### 4. HTTPS with Let's Encrypt

```bash
apt-get install certbot python3-certbot-apache
certbot --apache -d creatorzhive.yourdomain.com
```

After obtaining SSL, update `.env`:
```env
APP_URL=https://creatorzhive.yourdomain.com
SESSION_SECURE=true
```

### 5. Configure Production `.env`

```bash
cp .env.example .env
nano .env
```

Critical production settings:
```env
APP_URL=https://creatorzhive.yourdomain.com
APP_ENV=production
APP_DEBUG=false
APP_SECRET=<generate 32+ char random string: openssl rand -hex 32>
SESSION_SECURE=true
SESSION_LIFETIME=60
SOCIAL_API_MOCK_FALLBACK=false
```

Generate a secure APP_SECRET:
```bash
openssl rand -hex 32
```

### 6. Set File Permissions

```bash
# Web server user (typically www-data on Ubuntu)
chown -R www-data:www-data /var/www/html/creatorzhive
chmod -R 755 /var/www/html/creatorzhive
chmod -R 775 /var/www/html/creatorzhive/public/uploads
chmod -R 775 /var/www/html/creatorzhive/backend/storage

# Protect .env
chmod 600 /var/www/html/creatorzhive/.env
```

### 7. Run Database Migration

```bash
php scripts/migrate.php
```

### 8. Set Up Cron Job

```bash
crontab -e
# Add this line:
* * * * * php /var/www/html/creatorzhive/scripts/cron.php >> /tmp/creatorzhive-cron.log 2>&1
```

### 9. Protect Uploads from PHP Execution

Create `/var/www/html/creatorzhive/public/uploads/.htaccess`:

```apache
php_flag engine off
Options -Indexes
```

---

## Nginx Production Deployment

```nginx
server {
    listen 80;
    server_name creatorzhive.yourdomain.com;
    root /var/www/html/creatorzhive/public;
    index index.php;

    # Deny access to sensitive paths
    location ~ ^/(backend|src|vendor|scripts|tests|database)/ {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block PHP execution in uploads
    location /uploads {
        location ~ \.php$ {
            deny all;
        }
    }
}
```

---

## Environment Variables Reference

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_URL` | YES | Full base URL (no trailing slash) |
| `APP_ENV` | YES | `development` or `production` |
| `APP_DEBUG` | YES | `true` only in development |
| `APP_SECRET` | **CRITICAL** | Encryption key for tokens; must be set in production |
| `DB_HOST` | YES | MySQL host |
| `DB_PORT` | NO | Default 3306 |
| `DB_DATABASE` | YES | Database name |
| `DB_USERNAME` | YES | Database user |
| `DB_PASSWORD` | YES | Database password |
| `DB_DATABASE_TEST` | NO | Test database (PHPUnit) |
| `MAIL_HOST` | YES | SMTP hostname |
| `MAIL_PORT` | YES | SMTP port (587 or 465) |
| `MAIL_USERNAME` | YES | SMTP username |
| `MAIL_PASSWORD` | YES | SMTP password |
| `MAIL_FROM_ADDRESS` | YES | From email address |
| `MAIL_FROM_NAME` | YES | From display name |
| `SESSION_LIFETIME` | NO | Session minutes (default 120) |
| `SESSION_SECURE` | NO | true in production over HTTPS |
| `META_APP_ID` | NO | Meta Developer app ID |
| `META_APP_SECRET` | NO | Meta Developer app secret |
| `META_OAUTH_REDIRECT_URI` | NO | Must match Meta app settings |
| `GOOGLE_CLIENT_ID` | NO | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | NO | Google OAuth client secret |
| `SOCIAL_API_MOCK_FALLBACK` | NO | true = mock API calls (dev) |
| `INSTAGRAM_ACCESS_TOKEN` | NO | Manual token override |
| `FACEBOOK_ACCESS_TOKEN` | NO | Manual token override |
| `TIKTOK_ACCESS_TOKEN` | NO | Manual token override |
| `YOUTUBE_ACCESS_TOKEN` | NO | Manual token override |
| `TWITTER_BEARER_TOKEN` | NO | Twitter/X bearer token |

---

## Post-Deployment Checklist

```
□ APP_DEBUG=false
□ APP_SECRET set to unique 32+ char string
□ SESSION_SECURE=true (with HTTPS)
□ SOCIAL_API_MOCK_FALLBACK=false
□ Database migrated (php scripts/migrate.php)
□ Cron job configured (every minute)
□ public/uploads/ writable by web server
□ backend/storage/ writable by web server
□ .env file not web-accessible (test: curl https://domain/creatorzhive/.env)
□ vendor/ not web-accessible
□ PHP error display off (php.ini: display_errors = Off)
□ HTTPS enabled
□ Uploads .htaccess in place (php_flag engine off)
□ Admin account created and email verified
□ Platform credentials configured (Admin → Platform Credentials)
```

---

## Database Backup

```bash
# Backup
mysqldump -u root -p creatorz_hive > /backup/creatorz_hive_$(date +%Y%m%d).sql

# Restore
mysql -u root -p creatorz_hive < /backup/creatorz_hive_20260610.sql
```

Set up automated daily backups:
```bash
# crontab -e
0 2 * * * mysqldump -u root -p'password' creatorz_hive > /backup/creatorz_hive_$(date +\%Y\%m\%d).sql
```

---

## Upgrading

```bash
cd /var/www/html/creatorzhive
git pull origin main
composer install --no-dev --optimize-autoloader

# Run any new migrations
ls database/migrations/  # check for new .sql files
mysql -u root -p creatorz_hive < database/migrations/YYYYMMDD_description.sql
```
