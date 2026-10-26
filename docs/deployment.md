# AT Loops OS — Production Deployment & Web Server Hardening Guide

## 1. Production Requirements

- **Operating System:** Linux (Ubuntu 22.04 LTS / 24.04 LTS or Debian 12) or Windows Server with IIS/Apache.
- **Web Server:** Nginx 1.22+ or Apache 2.4+.
- **PHP Version:** PHP 8.4+ (CLI & FPM) with extensions:
  `php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd`
- **Database:** MySQL 8.0+ or Percona Server 8.0+ (InnoDB, UTF8MB4).
- **SSL Certificate:** Let's Encrypt or corporate TLS 1.3 certificate.

---

## 2. Directory Structure & Permissions (Linux)

Assuming installation at `/var/www/atloops-os`:

```bash
# Set base permissions
sudo chown -R www-data:www-data /var/www/atloops-os
sudo find /var/www/atloops-os -type d -exec chmod 755 {} \;
sudo find /var/www/atloops-os -type f -exec chmod 644 {} \;

# Writable directories for PHP-FPM
sudo chmod -R 775 /var/www/atloops-os/storage
sudo chmod -R 775 /var/www/atloops-os/storage/logs
sudo chmod -R 775 /var/www/atloops-os/storage/backups
sudo chmod -R 775 /var/www/atloops-os/storage/app/documents

# Protect environment file
chmod 600 /var/www/atloops-os/.env
```

---

## 3. Web Server Configuration

### 3.1 Nginx Server Block (Recommended)

```nginx
server {
    listen 80;
    server_name os.atloops.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name os.atloops.com;

    ssl_certificate /etc/letsencrypt/live/os.atloops.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/os.atloops.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Mandatory: Root points to public/ directory ONLY
    root /var/www/atloops-os/public;
    index index.php;

    charset utf-8;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:;" always;

    # Block direct access to hidden files (.env, .git)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block access to internal directories if misconfigured
    location ~* ^/(app|config|database|docs|scripts|storage|tests|vendor) {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

### 3.2 Apache 2.4 VirtualHost Configuration

```apache
<VirtualHost *:443>
    ServerName os.atloops.com
    DocumentRoot "/var/www/atloops-os/public"

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/os.atloops.com/cert.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/os.atloops.com/privkey.pem
    SSLCertificateChainFile /etc/letsencrypt/live/os.atloops.com/chain.pem

    <Directory "/var/www/atloops-os/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Block sensitive paths
    <DirectoryMatch "^/var/www/atloops-os/(app|config|database|docs|scripts|storage|tests|vendor)">
        Require all denied
    </DirectoryMatch>

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

---

## 4. Production `.env` Settings

Ensure the following variables are strictly set on the production server:

```ini
APP_NAME="AT Loops OS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://os.atloops.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=atloops_os
DB_USERNAME=atloops_user
DB_PASSWORD="STRONG_SECRET_PRODUCTION_PASSWORD"

SESSION_LIFETIME=7200
SESSION_SECURE_COOKIE=true
```

---

## 5. Automated Production Cron Configuration

Add to `/etc/cron.d/atloops-os`:

```cron
# AT Loops OS Automated Background Jobs (every 15 minutes)
*/15 * * * * www-data /usr/bin/php /var/www/atloops-os/scripts/cron.php >> /var/www/atloops-os/storage/logs/cron.log 2>&1

# Daily Automated Database Backup (every night at 02:00 AM)
0 2 * * * www-data /usr/bin/php /var/www/atloops-os/scripts/backup.php --database=mysql >> /var/www/atloops-os/storage/logs/backup.log 2>&1
```

---

## 6. Post-Deployment Verification Checklist

- [ ] Verify `https://os.atloops.com` serves the login page with HTTPS lock.
- [ ] Attempt accessing `https://os.atloops.com/.env` -> Must return **403 Forbidden** or **404 Not Found**.
- [ ] Attempt accessing `https://os.atloops.com/storage/` -> Must return **403/404**.
- [ ] Log in with `admin@atloops.com` and immediately change default credentials.
- [ ] Execute `php tests/run_tests.php` to confirm isolated testing pass.
- [ ] Trigger test backup: `php scripts/backup.php --database=mysql`.
