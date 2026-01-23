# HƯỚNG DẪN DEPLOY DỰ ÁN LARAVEL LÊN SERVER

## 📋 YÊU CẦU HỆ THỐNG

### 1. Phần mềm cần cài đặt trên server

#### A. PHP và các Extension cần thiết
- **PHP 8.2 hoặc cao hơn** (khuyến nghị PHP 8.3)
- **PHP Extensions bắt buộc:**
  ```bash
  php8.2-fpm (hoặc php8.3-fpm)
  php8.2-cli
  php8.2-mysql (hoặc php8.2-mysqli)
  php8.2-mbstring
  php8.2-xml
  php8.2-curl
  php8.2-zip
  php8.2-gd
  php8.2-bcmath
  php8.2-intl
  php8.2-opcache
  php8.2-fileinfo
  php8.2-tokenizer
  ```

#### B. Web Server
- **Nginx** (khuyến nghị) hoặc **Apache**
- **PHP-FPM** để xử lý PHP

#### C. Database
- **MySQL 8.0+** hoặc **MariaDB 10.6+**

#### D. Composer
- **Composer 2.x** (package manager cho PHP)

#### E. Node.js và npm
- **Node.js 18.x hoặc cao hơn**
- **npm** (đi kèm với Node.js)

#### F. Công cụ khác
- **Git** (để clone/pull code)
- **Supervisor** (để chạy queue worker)
- **Cron** (để chạy scheduled tasks)

---

## 🚀 CÁC BƯỚC CÀI ĐẶT VÀ DEPLOY

### BƯỚC 1: Cài đặt các phần mềm cần thiết

#### Trên Ubuntu/Debian:

```bash
# Cập nhật hệ thống
sudo apt update && sudo apt upgrade -y

# Cài đặt PHP 8.2 và các extension
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-gd \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-opcache \
    php8.2-fileinfo \
    php8.2-tokenizer

# Cài đặt Nginx
sudo apt install -y nginx

# Cài đặt MySQL
sudo apt install -y mysql-server

# Cài đặt Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Cài đặt Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Cài đặt Git
sudo apt install -y git

# Cài đặt Supervisor
sudo apt install -y supervisor
```

#### Trên CentOS/RHEL:

```bash
# Cài đặt EPEL và Remi repository
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Cài đặt PHP 8.2
sudo yum install -y php82-php-fpm \
    php82-php-cli \
    php82-php-mysqlnd \
    php82-php-mbstring \
    php82-php-xml \
    php82-php-curl \
    php82-php-zip \
    php82-php-gd \
    php82-php-bcmath \
    php82-php-intl \
    php82-php-opcache \
    php82-php-fileinfo \
    php82-php-tokenizer

# Cài đặt Nginx, MySQL, Node.js, Git, Supervisor
sudo yum install -y nginx mysql-server nodejs git supervisor
```

---

### BƯỚC 2: Cấu hình MySQL

```bash
# Đăng nhập MySQL
sudo mysql -u root -p

# Tạo database và user
CREATE DATABASE nobifashion_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nobifashion_user'@'localhost' IDENTIFIED BY 'mat_khau_manh_va_an_toan';
GRANT ALL PRIVILEGES ON nobifashion_db.* TO 'nobifashion_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Lưu ý:** Thay `mat_khau_manh_va_an_toan` bằng mật khẩu mạnh của bạn.

---

### BƯỚC 3: Clone code lên server

```bash
# Tạo thư mục cho dự án
sudo mkdir -p /var/www/nobifashion.vn
sudo chown -R $USER:$USER /var/www/nobifashion.vn

# Clone code (thay URL bằng repository của bạn)
cd /var/www/nobifashion.vn
git clone https://github.com/your-username/nobifashion.git .

# Hoặc nếu đã có code, upload lên server và giải nén vào /var/www/nobifashion.vn
```

---

### BƯỚC 4: Cài đặt dependencies

```bash
cd /var/www/nobifashion.vn

# Cài đặt PHP dependencies
composer install --optimize-autoloader --no-dev

# Cài đặt Node.js dependencies
npm install

# Build assets cho production
npm run build
```

---

### BƯỚC 5: Cấu hình file .env

```bash
# Copy file .env.example thành .env
cp .env.example .env

# Tạo application key
php artisan key:generate

# Chỉnh sửa file .env
nano .env
```

**Cấu hình file .env:**

```env
APP_NAME="NOBI FASHION"
APP_ENV=production
APP_KEY=base64:... (đã được tạo bởi key:generate)
APP_DEBUG=false
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_URL=https://nobifashion.vn

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nobifashion_db
DB_USERNAME=nobifashion_user
DB_PASSWORD=mat_khau_manh_va_an_toan

# Mail (cấu hình theo email account trong admin)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Lưu ý quan trọng:**
- Đặt `APP_DEBUG=false` trong production
- Đặt `APP_ENV=production`
- Cấu hình đúng thông tin database
- Cấu hình email SMTP (hoặc sử dụng email accounts trong admin panel)

---

### BƯỚC 6: Chạy migrations và seeders

```bash
# Chạy migrations
php artisan migrate --force

# (Tùy chọn) Chạy seeders nếu cần dữ liệu mẫu
# php artisan db:seed --force
```

---

### BƯỚC 7: Cấu hình quyền truy cập file

```bash
cd /var/www/nobifashion.vn

# Set quyền cho storage và bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Set quyền cho toàn bộ dự án (tùy chọn, nếu cần)
sudo chown -R www-data:www-data /var/www/nobifashion.vn
sudo find /var/www/nobifashion.vn -type f -exec chmod 644 {} \;
sudo find /var/www/nobifashion.vn -type d -exec chmod 755 {} \;
```

---

### BƯỚC 8: Cấu hình Nginx

Tạo file cấu hình Nginx:

```bash
sudo nano /etc/nginx/sites-available/nobifashion.vn
```

**Nội dung file cấu hình:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name nobifashion.vn www.nobifashion.vn;
    
    # Redirect HTTP to HTTPS (sau khi cài SSL)
    # return 301 https://$server_name$request_uri;
    
    # Hoặc tạm thời dùng HTTP (bỏ comment dòng trên khi đã có SSL)
    root /var/www/nobifashion.vn/public;
    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Tăng upload size
    client_max_body_size 100M;
}
```

**Kích hoạt site:**

```bash
# Tạo symbolic link
sudo ln -s /etc/nginx/sites-available/nobifashion.vn /etc/nginx/sites-enabled/

# Test cấu hình Nginx
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

### BƯỚC 9: Cấu hình PHP-FPM

```bash
# Chỉnh sửa file cấu hình PHP-FPM
sudo nano /etc/php/8.2/fpm/php.ini
```

**Các thông số quan trọng cần chỉnh:**

```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
```

**Restart PHP-FPM:**

```bash
sudo systemctl restart php8.2-fpm
```

---

### BƯỚC 10: Cấu hình Queue Worker với Supervisor

Tạo file cấu hình Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/nobifashion-queue.conf
```

**Nội dung:**

```ini
[program:nobifashion-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/nobifashion.vn/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/nobifashion.vn/storage/logs/queue-worker.log
stopwaitsecs=3600
```

**Khởi động Supervisor:**

```bash
# Reload cấu hình
sudo supervisorctl reread
sudo supervisorctl update

# Start queue worker
sudo supervisorctl start nobifashion-queue-worker:*

# Kiểm tra trạng thái
sudo supervisorctl status
```

---

### BƯỚC 11: Cấu hình Cron Job

```bash
# Mở crontab
sudo crontab -e -u www-data
```

**Thêm dòng sau:**

```cron
* * * * * cd /var/www/nobifashion.vn && php artisan schedule:run >> /dev/null 2>&1
```

**Hoặc nếu dùng root:**

```bash
sudo crontab -e
```

**Thêm:**

```cron
* * * * * cd /var/www/nobifashion.vn && php artisan schedule:run >> /dev/null 2>&1
```

---

### BƯỚC 12: Tối ưu hóa Laravel cho Production

```bash
cd /var/www/nobifashion.vn

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events (nếu có)
php artisan event:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

**Lưu ý:** Sau khi chạy các lệnh cache, nếu bạn thay đổi config/routes/views, cần chạy lại các lệnh tương ứng hoặc `php artisan optimize:clear` để xóa cache.

---

### BƯỚC 13: Cài đặt SSL Certificate (Let's Encrypt)

```bash
# Cài đặt Certbot
sudo apt install -y certbot python3-certbot-nginx

# Lấy SSL certificate
sudo certbot --nginx -d nobifashion.vn -d www.nobifashion.vn

# Certbot sẽ tự động cấu hình Nginx và renew certificate tự động
```

**Sau khi cài SSL, cập nhật file Nginx để redirect HTTP sang HTTPS:**

```bash
sudo nano /etc/nginx/sites-available/nobifashion.vn
```

**Bỏ comment dòng redirect:**

```nginx
return 301 https://$server_name$request_uri;
```

**Reload Nginx:**

```bash
sudo systemctl reload nginx
```

---

### BƯỚC 14: Cấu hình Firewall

```bash
# Cho phép HTTP, HTTPS, và SSH
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable

# Kiểm tra trạng thái
sudo ufw status
```

---

## 🔧 CẤU HÌNH BỔ SUNG

### 1. Cấu hình OPcache (tăng hiệu suất PHP)

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

**Tìm và chỉnh sửa:**

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

**Restart PHP-FPM:**

```bash
sudo systemctl restart php8.2-fpm
```

### 2. Cấu hình Redis (tùy chọn, để cache và session)

```bash
# Cài đặt Redis
sudo apt install -y redis-server

# Cấu hình Redis
sudo nano /etc/redis/redis.conf
```

**Tìm và chỉnh:**

```conf
supervised systemd
```

**Start Redis:**

```bash
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

**Cài đặt PHP Redis extension:**

```bash
sudo apt install -y php8.2-redis
sudo systemctl restart php8.2-fpm
```

**Cập nhật .env:**

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 📝 KIỂM TRA SAU KHI DEPLOY

### 1. Kiểm tra các service đang chạy

```bash
# Kiểm tra Nginx
sudo systemctl status nginx

# Kiểm tra PHP-FPM
sudo systemctl status php8.2-fpm

# Kiểm tra MySQL
sudo systemctl status mysql

# Kiểm tra Queue Worker
sudo supervisorctl status

# Kiểm tra Redis (nếu có)
sudo systemctl status redis-server
```

### 2. Kiểm tra logs

```bash
# Log Laravel
tail -f /var/www/nobifashion.vn/storage/logs/laravel.log

# Log Nginx
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Log PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Log Queue Worker
tail -f /var/www/nobifashion.vn/storage/logs/queue-worker.log
```

### 3. Kiểm tra website

- Truy cập: `https://nobifashion.vn`
- Kiểm tra trang admin: `https://nobifashion.vn/admin`
- Kiểm tra API: `https://nobifashion.vn/api/...`

---

## 🔄 QUY TRÌNH UPDATE CODE SAU NÀY

```bash
cd /var/www/nobifashion.vn

# Pull code mới
git pull origin main

# Cài đặt dependencies mới (nếu có)
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Chạy migrations mới (nếu có)
php artisan migrate --force

# Clear và rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
sudo supervisorctl restart nobifashion-queue-worker:*
```

---

## ⚠️ LƯU Ý QUAN TRỌNG

1. **Bảo mật:**
   - Luôn đặt `APP_DEBUG=false` trong production
   - Không commit file `.env` lên Git
   - Sử dụng mật khẩu mạnh cho database
   - Cấu hình firewall đúng cách
   - Sử dụng SSL/HTTPS

2. **Backup:**
   - Backup database định kỳ
   - Backup file storage (ảnh, documents)
   - Backup file `.env`

3. **Monitoring:**
   - Theo dõi logs thường xuyên
   - Monitor server resources (CPU, RAM, Disk)
   - Setup alerts cho các service quan trọng

4. **Performance:**
   - Sử dụng OPcache
   - Cân nhắc dùng Redis cho cache
   - Tối ưu database queries
   - Sử dụng CDN cho static assets

---

## 🆘 XỬ LÝ SỰ CỐ THƯỜNG GẶP

### Lỗi 502 Bad Gateway
- Kiểm tra PHP-FPM có đang chạy không: `sudo systemctl status php8.2-fpm`
- Kiểm tra socket path trong Nginx config
- Kiểm tra quyền file

### Lỗi 500 Internal Server Error
- Kiểm tra logs: `tail -f storage/logs/laravel.log`
- Kiểm tra quyền storage và bootstrap/cache
- Clear cache: `php artisan optimize:clear`

### Queue không chạy
- Kiểm tra Supervisor: `sudo supervisorctl status`
- Kiểm tra logs queue worker
- Restart: `sudo supervisorctl restart nobifashion-queue-worker:*`

### Email không gửi được
- Kiểm tra cấu hình SMTP trong `.env`
- Kiểm tra SPF/DKIM records (xem file `HUONG_DAN_FIX_SPF_DKIM.md`)
- Kiểm tra logs: `tail -f storage/logs/laravel.log`

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề, kiểm tra:
1. Logs Laravel: `storage/logs/laravel.log`
2. Logs Nginx: `/var/log/nginx/error.log`
3. Logs PHP-FPM: `/var/log/php8.2-fpm.log`
4. Status các service: `systemctl status`

---

**Chúc bạn deploy thành công! 🚀**


