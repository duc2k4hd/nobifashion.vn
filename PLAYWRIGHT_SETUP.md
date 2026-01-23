# Hướng dẫn cài đặt Playwright Crawler Service

## Bước 1: Cài đặt Node.js và npm

Nếu chưa có Node.js, tải và cài đặt từ: https://nodejs.org/

## Bước 2: Cài đặt dependencies

```bash
npm install
```

Lệnh này sẽ cài:
- `playwright` - Headless browser
- `express` - HTTP server
- `nodemon` (dev) - Auto restart khi code thay đổi

## Bước 3: Cài đặt Playwright browsers

```bash
npx playwright install chromium
```

## Bước 4: Cấu hình Laravel

Thêm vào file `.env`:

```
PLAYWRIGHT_SERVICE_URL=http://localhost:3001
```

## Bước 5: Chạy Playwright service

### Chạy thủ công:
```bash
node playwright-crawler.js
```

### Hoặc dùng nodemon (tự động restart):
```bash
npm run dev
```

Service sẽ chạy trên port `3001` (có thể đổi bằng biến môi trường `PORT`).

## Bước 6: Test service

Mở browser và truy cập: http://localhost:3001

Hoặc test bằng curl:
```bash
curl -X POST http://localhost:3001/crawl -H "Content-Type: application/json" -d "{\"url\":\"https://routine.vn/tin-thoi-trang\"}"
```

## Chạy service tự động khi khởi động máy (Windows)

1. Tạo file `start-playwright.bat`:
```batch
@echo off
cd /d E:\Project\Laravel\nobifashion.vn
node playwright-crawler.js
```

2. Tạo Windows Task Scheduler để chạy file này khi khởi động.

## Lưu ý

- Service phải chạy trước khi dùng crawler trong Laravel
- Nếu thay đổi port, nhớ cập nhật `PLAYWRIGHT_SERVICE_URL` trong `.env`
- Service sẽ tự động đợi Cloudflare challenge hoàn tất (tối đa 60 giây)
