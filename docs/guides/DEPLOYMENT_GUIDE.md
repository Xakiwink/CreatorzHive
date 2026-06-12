# CreatorzHive Deployment Guide

Complete guide for deploying CreatorzHive to production environments.

---

## Quick Start (Local Development)

```bash
# 1. Clone repository
git clone https://github.com/Xakiwink/CreatorzHive.git
cd creatorzhive

# 2. Install dependencies
composer install

# 3. Create .env from example
cp .env.example .env

# 4. Generate secrets
php -r 'echo "APP_SECRET=" . bin2hex(random_bytes(32));'  # Copy to .env

# 5. Run migrations
php scripts/migrate.php

# 6. Seed demo data (optional)
php scripts/seed.php --fresh

# 7. Start dev server
php -S localhost:8000 -t public/

# 8. Open browser
# http://localhost:8000
# Login: david@creatorzhive.com / Creator@1234
```

---

## Deployment Targets

### InfinityFree (Free Hosting)
**Best for**: Getting started, testing, small projects  
**Pros**: Free, no credit card, easy setup  
**Cons**: Limited resources, shared hosting

See: [INFINITYFREE_SETUP.md](INFINITYFREE_SETUP.md)

### Traditional Shared Hosting (cPanel/Plesk)
**Best for**: Production at scale, custom domain, good uptime  
**Pros**: Affordable, professional support  
**Cons**: Limited control, shared resources

See: Shared Hosting section below

### VPS (DigitalOcean, Linode, etc.)
**Best for**: Full control, scaling, performance  
**Pros**: Root access, high performance, auto-scaling possible  
**Cons**: Higher cost, requires DevOps knowledge

See: VPS Deployment section below

### Dedicated Server
**Best for**: Enterprise, millions of users  
**Pros**: Maximum performance and control  
**Cons**: Highest cost, complex setup

---

## Shared Hosting (cPanel/Plesk)

### 1. Prepare Locally

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate secrets
php -r 'echo bin2hex(random_bytes(32));'
```

### 2. Create Control Panel Database

In cPanel:
- Go to MySQL Databases
- Create new database
- Create new MySQL user
- Add user to database with ALL privileges

### 3. Upload via FTP/SFTP

```
public_html/
└── creatorzhive/
    ├── public/
    ├── src/
    ├── vendor/
    ├── backend/
    ├── .env          ← Create this
    └── ...
```

### 4. Create .env

```env
APP_NAME=CreatorzHive
APP_URL=https://yourdomain.com/creatorzhive
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_DATABASE=username_dbname
DB_USERNAME=username_dbuser
DB_PASSWORD=your_db_password

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=app_password
MAIL_FROM_ADDRESS=your@gmail.com

APP_SECRET=<generated_secret>
WEBHOOK_SECRET=<generated_webhook_secret>
SESSION_SECURE=true
```

### 5. Configure Document Root

In cPanel:
- Go to Addon Domains (or Document Root settings)
- Point domain to `public_html/creatorzhive/public/`

### 6. Set Up Cron

In cPanel → Cron Jobs:

```
* * * * * /usr/bin/php /home/username/public_html/creatorzhive/scripts/cron.php
```

Or use external service (UptimeRobot) if cron unavailable:

```
https://yourdomain.com/webhook/process-jobs.php?secret=YOUR_WEBHOOK_SECRET
```

### 7. Run Setup

```
https://yourdomain.com/creatorzhive/setup.php
```

Follow wizard to:
- Run migrations
- Seed data (optional)
- Create admin user

---

## VPS Deployment (Ubuntu 20.04+)

### 1. Initial Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y php7.4-fpm php7.4-mysql php7.4-curl php7.4-gd \
  php7.4-mbstring php7.4-xml php7.4-zip \
  mysql-server nginx git composer

# Start services
sudo systemctl start php7.4-fpm
sudo systemctl start mysql
sudo systemctl start nginx
```

### 2. Create Application User

```bash
sudo useradd -m -s /bin/bash creatorzhive
sudo usermod -aG www-data creatorzhive
```

### 3. Clone Repository

```bash
sudo -u creatorzhive git clone https://github.com/Xakiwink/CreatorzHive.git \
  /var/www/creatorzhive

cd /var/www/creatorzhive
sudo -u creatorzhive composer install --no-dev --optimize-autoloader
```

### 4. Configure Database

```bash
sudo mysql

# In MySQL prompt:
CREATE DATABASE creatorzhive;
CREATE USER 'creatorzhive'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON creatorzhive.* TO 'creatorzhive'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Create .env

```bash
sudo -u creatorzhive cp .env.example .env
sudo -u creatorzhive nano .env
```

Set:
```env
DB_HOST=localhost
DB_DATABASE=creatorzhive
DB_USERNAME=creatorzhive
DB_PASSWORD=strong_password
APP_SECRET=<generated>
WEBHOOK_SECRET=<generated>
SESSION_SECURE=true
```

### 6. Configure Nginx

Create `/etc/nginx/sites-available/creatorzhive`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/creatorzhive/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    client_max_body_size 10M;
}
```

Enable:
```bash
sudo ln -s /etc/nginx/sites-available/creatorzhive \
  /etc/nginx/sites-enabled/

sudo nginx -t
sudo systemctl reload nginx
```

### 7. Set Permissions

```bash
sudo chown -R creatorzhive:www-data /var/www/creatorzhive
sudo chmod -R 755 /var/www/creatorzhive
sudo chmod -R 755 /var/www/creatorzhive/public/uploads
```

### 8. Run Migrations

```bash
cd /var/www/creatorzhive
sudo -u creatorzhive php scripts/migrate.php
sudo -u creatorzhive php scripts/seed.php --fresh
```

### 9. Set Up Cron

```bash
sudo crontab -u www-data -e
```

Add:
```
* * * * * /usr/bin/php /var/www/creatorzhive/scripts/cron.php
```

### 10. Install SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## Post-Deployment Checklist

- [ ] Application loads without errors
- [ ] Login works (email/password)
- [ ] Google OAuth configured and working
- [ ] Dashboard displays with data
- [ ] Can create posts and upload media
- [ ] Social integrations connected
- [ ] Background jobs running (check admin panel)
- [ ] Emails sending (password reset test)
- [ ] SSL certificate installed and valid
- [ ] Backups configured
- [ ] Monitoring/alerting set up

---

## Maintenance

### Daily
- Monitor application error logs
- Check background job status
- Verify integrations are active

### Weekly
- Review analytics
- Check for failed jobs
- Update any social platform tokens

### Monthly
- Rotate APP_SECRET and WEBHOOK_SECRET
- Review security audit logs
- Update PHP/MySQL versions if available

### Quarterly
- Full database backup
- Security audit
- Performance review

---

## Troubleshooting

### Application won't start
- Check PHP version (require 7.4+)
- Verify database credentials in .env
- Check file permissions (755 dirs, 644 files)

### Database connection error
- Test MySQL connection: `mysql -h host -u user -p`
- Verify credentials in .env match
- Check MySQL is running: `systemctl status mysql`

### Background jobs not running
- Check cron job exists: `crontab -l`
- Test manually: `php scripts/cron.php`
- Check logs: `tail backend/storage/logs/*.log`

### Email not sending
- Test SMTP credentials separately
- Check MAIL_* variables in .env
- Verify firewall allows outbound SMTP (port 587)

### High memory usage
- Reduce jobs per cron call (edit `scripts/cron.php` line 46)
- Enable query caching in MySQL
- Consider upgrading to larger instance

---

## Security Best Practices

1. **Secrets Management**
   - Rotate APP_SECRET every 6 months
   - Never commit .env to git
   - Use strong random values

2. **Database**
   - Enable MySQL SSL
   - Regular backups (daily)
   - Restrict DB access by IP

3. **File Permissions**
   - Web server cannot write source code
   - Only `public/uploads/` is writable
   - Logs go to database or system error_log

4. **HTTPS**
   - Use Let's Encrypt (free SSL)
   - Redirect HTTP to HTTPS
   - Set HSTS header

5. **Monitoring**
   - Set up error logging to external service
   - Monitor job queue for failures
   - Alert on security events

---

## Upgrading

To upgrade to a new version:

```bash
# Pull latest code
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php scripts/migrate.php

# Clear caches
php scripts/clear-cache.php  # if exists

# Restart application (on VPS)
sudo systemctl reload nginx
sudo systemctl reload php7.4-fpm
```

---

## Support

For issues:
- Check error logs in `backend/storage/logs/`
- Review `.env` configuration
- Test database connection manually
- Verify file permissions

---

**Need help?** Check the troubleshooting section or review SYSTEM_OVERVIEW.md for architecture details.
