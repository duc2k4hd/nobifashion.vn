const { chromium } = require('playwright');
const express = require('express');
const app = express();

app.use(express.json());

/**
 * Crawl URL và trả về HTML đã render
 */
app.post('/crawl', async (req, res) => {
    const { url, waitForSelector } = req.body;
    
    if (!url) {
        return res.status(400).json({ error: 'URL is required' });
    }

    let browser = null;
    
    try {
        // Khởi tạo browser với options tối ưu cho Windows
        browser = await chromium.launch({
            headless: true,
            args: [
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--disable-features=NetworkService',
                '--window-size=1920,1080',
            ],
        });

        const context = await browser.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        });
        
        const page = await context.newPage();
        
        // Chặn request rác (image, font, media, stylesheet) để load nhanh hơn
        await page.route('**/*', route => {
            const type = route.request().resourceType();
            if (['image', 'font', 'media', 'stylesheet'].includes(type)) {
                return route.abort();
            }
            route.continue();
        });
        
        // Navigate và đợi DOM content loaded (KHÔNG dùng networkidle vì site có analytics/tracking chạy liên tục)
        await page.goto(url, {
            waitUntil: 'domcontentloaded',
            timeout: 60000,
        });

        // Nếu caller yêu cầu đợi selector (để đảm bảo DOM đã render đủ), thì đợi tối đa 30s
        if (waitForSelector && typeof waitForSelector === 'string' && waitForSelector.trim().length > 0) {
            try {
                await page.waitForSelector(waitForSelector, { timeout: 30000 });
            } catch (e) {
                // Không fail ngay; vẫn trả HTML để caller tự fallback
                console.warn(`waitForSelector timeout for "${waitForSelector}" on ${url}`);
            }
        }

        // Đợi một chút để JS hydrate nhẹ và Cloudflare challenge hoàn tất
        await page.waitForTimeout(1500);
        
        // Scroll nhẹ để trigger lazy-load nếu có
        await page.evaluate(() => {
            window.scrollTo(0, document.body.scrollHeight / 2);
        });
        await page.waitForTimeout(1000);

        // Kiểm tra xem còn "Just a moment" không
        let html = await page.content();
        let attempts = 0;
        const maxAttempts = 30;

        while ((html.includes('Just a moment') || html.includes('cf-browser-verification')) && attempts < maxAttempts) {
            await page.waitForTimeout(2000);
            html = await page.content();
            attempts++;
        }

        // Lấy HTML cuối cùng
        html = await page.content();

        await context.close();
        await browser.close();
        browser = null;

        res.json({
            success: true,
            html: html,
            length: html.length,
        });

    } catch (error) {
        if (browser) {
            await browser.close();
        }
        
        console.error('Playwright crawl error:', error);
        res.status(500).json({
            success: false,
            error: error.message,
        });
    }
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
    console.log(`Playwright crawler service running on port ${PORT}`);
});
