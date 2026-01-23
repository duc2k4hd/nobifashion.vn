# ✅ CHECKLIST DEPLOY DỰ ÁN LÊN SERVER

## 📦 CÀI ĐẶT PHẦN MỀM

- [ ] PHP 8.2+ và các extension cần thiết
- [ ] Nginx hoặc Apache
- [ ] MySQL/MariaDB
- [ ] Composer
- [ ] Node.js 18+ và npm
- [ ] Git
- [ ] Supervisor
- [ ] Certbot (cho SSL)

## 🗄️ DATABASE

- [ ] Tạo database
- [ ] Tạo user và cấp quyền
- [ ] Test kết nối database

## 📥 CODE

- [ ] Clone/upload code lên server
- [ ] Cài đặt Composer dependencies (`composer install --no-dev`)
- [ ] Cài đặt npm dependencies (`npm install`)
- [ ] Build assets (`npm run build`)

## ⚙️ CẤU HÌNH

- [ ] Copy `.env.example` thành `.env`
- [ ] Tạo APP_KEY (`php artisan key:generate`)
- [ ] Cấu hình database trong `.env`
- [ ] Cấu hình mail trong `.env`
- [ ] Cấu hình APP_URL, APP_ENV, APP_DEBUG

## 🗃️ DATABASE SETUP

- [ ] Chạy migrations (`php artisan migrate --force`)
- [ ] (Tùy chọn) Chạy seeders

## 🔐 QUYỀN TRUY CẬP

- [ ] Set quyền cho `storage/` và `bootstrap/cache/` (775)
- [ ] Set owner là `www-data` (hoặc user web server)

## 🌐 WEB SERVER

- [ ] Cấu hình Nginx/Apache
- [ ] Test cấu hình web server
- [ ] Reload web server
- [ ] Kiểm tra website hoạt động

## ⚡ PHP-FPM

- [ ] Cấu hình `php.ini` (upload_max_filesize, memory_limit, etc.)
- [ ] Cấu hình OPcache
- [ ] Restart PHP-FPM

## 🔄 QUEUE WORKER

- [ ] Tạo file cấu hình Supervisor
- [ ] Reload Supervisor
- [ ] Start queue worker
- [ ] Kiểm tra queue worker đang chạy

## ⏰ CRON JOB

- [ ] Cấu hình cron job cho Laravel scheduler
- [ ] Kiểm tra cron job hoạt động

## 🚀 TỐI ƯU HÓA

- [ ] Cache config (`php artisan config:cache`)
- [ ] Cache routes (`php artisan route:cache`)
- [ ] Cache views (`php artisan view:cache`)
- [ ] Optimize autoloader

## 🔒 SSL/HTTPS

- [ ] Cài đặt SSL certificate (Let's Encrypt)
- [ ] Cấu hình redirect HTTP → HTTPS
- [ ] Test HTTPS hoạt động

## 🛡️ BẢO MẬT

- [ ] Cấu hình firewall (UFW)
- [ ] Đảm bảo `APP_DEBUG=false`
- [ ] Đảm bảo `APP_ENV=production`
- [ ] Kiểm tra file `.env` không bị public

## ✅ KIỂM TRA CUỐI CÙNG

- [ ] Website truy cập được qua HTTPS
- [ ] Trang admin hoạt động
- [ ] API hoạt động
- [ ] Email gửi được (test gửi email)
- [ ] Queue worker xử lý jobs
- [ ] Upload file hoạt động
- [ ] Database kết nối và query được

## 📊 MONITORING

- [ ] Kiểm tra logs Laravel
- [ ] Kiểm tra logs Nginx
- [ ] Kiểm tra logs PHP-FPM
- [ ] Kiểm tra logs Queue Worker
- [ ] Setup monitoring server resources (tùy chọn)

## 💾 BACKUP

- [ ] Setup backup database tự động
- [ ] Setup backup files tự động
- [ ] Test restore backup

---

**Sau khi hoàn thành tất cả các bước trên, dự án của bạn đã sẵn sàng production! 🎉**


