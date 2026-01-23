<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    
    <xsl:template match="/">
        <html lang="vi">
            <head>
                <meta charset="UTF-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                
                <title>Sitemap – NOBI FASHION VIỆT NAM</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
                        min-height: 100vh;
                        padding: 40px 20px;
                        line-height: 1.6;
                    }
                    
                    .container {
                        max-width: 1200px;
                        margin: 0 auto;
                        background: white;
                        border-radius: 20px;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        overflow: hidden;
                    }
                    
                    .header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 40px;
                        text-align: center;
                    }
                    
                    .header h1 {
                        font-size: 36px;
                        font-weight: 800;
                        margin-bottom: 10px;
                        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                    }
                    
                    .header p {
                        font-size: 16px;
                        opacity: 0.95;
                    }
                    
                    .stats {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 20px;
                        padding: 30px 40px;
                        background: #f8fafc;
                        border-bottom: 2px solid #e2e8f0;
                    }
                    
                    .stat-item {
                        text-align: center;
                    }
                    
                    .stat-value {
                        font-size: 32px;
                        font-weight: 700;
                        color: #667eea;
                        margin-bottom: 5px;
                    }
                    
                    .stat-label {
                        font-size: 14px;
                        color: #64748b;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    .content {
                        padding: 40px;
                    }
                    
                    .url-list {
                        list-style: none;
                    }
                    
                    .url-item {
                        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
                        border-radius: 12px;
                        padding: 20px;
                        margin-bottom: 16px;
                        border: 2px solid #e2e8f0;
                        transition: all 0.3s ease;
                    }
                    
                    .url-item:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
                        border-color: #667eea;
                    }
                    
                    .url-link {
                        display: block;
                        color: #667eea;
                        text-decoration: none;
                        font-size: 16px;
                        font-weight: 600;
                        margin-bottom: 12px;
                        word-break: break-all;
                    }
                    
                    .url-link:hover {
                        color: #764ba2;
                        text-decoration: underline;
                    }
                    
                    .url-meta {
                        display: flex;
                        gap: 20px;
                        flex-wrap: wrap;
                        font-size: 13px;
                        color: #64748b;
                    }
                    
                    .url-meta-item {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    }
                    
                    .url-meta-item strong {
                        color: #475569;
                        font-weight: 600;
                    }
                    
                    .badge {
                        display: inline-block;
                        padding: 4px 12px;
                        border-radius: 20px;
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    .badge-changefreq {
                        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
                        color: #1e40af;
                    }
                    
                    .badge-priority {
                        background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
                        color: #9f1239;
                    }
                    
                    .images-list {
                        margin-top: 12px;
                        padding-top: 12px;
                        border-top: 1px solid #e2e8f0;
                    }
                    
                    .image-item {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 10px;
                        background: #f8fafc;
                        border-radius: 8px;
                        margin-bottom: 8px;
                    }
                    
                    .image-item img {
                        width: 60px;
                        height: 60px;
                        object-fit: cover;
                        border-radius: 8px;
                        border: 2px solid #e2e8f0;
                    }
                    
                    .image-info {
                        flex: 1;
                    }
                    
                    .image-title {
                        font-weight: 600;
                        color: #1e293b;
                        margin-bottom: 4px;
                    }
                    
                    .image-url {
                        font-size: 12px;
                        color: #64748b;
                        word-break: break-all;
                    }
                    
                    .footer {
                        background: #f8fafc;
                        padding: 30px 40px;
                        text-align: center;
                        border-top: 2px solid #e2e8f0;
                    }
                    
                    .footer p {
                        color: #64748b;
                        font-size: 14px;
                    }
                    
                    .footer a {
                        color: #667eea;
                        text-decoration: none;
                        font-weight: 600;
                    }
                    
                    .footer a:hover {
                        text-decoration: underline;
                    }
                    
                    @media (max-width: 768px) {
                        body {
                            padding: 20px 10px;
                        }
                        
                        .header {
                            padding: 30px 20px;
                        }
                        
                        .header h1 {
                            font-size: 28px;
                        }
                        
                        .stats {
                            grid-template-columns: 1fr;
                            padding: 20px;
                        }
                        
                        .content {
                            padding: 20px;
                        }
                        
                        .url-meta {
                            flex-direction: column;
                            gap: 8px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🗺️ Sitemap XML - NOBI FASHION VIỆT NAM</h1>
                        <p>Danh sách tất cả các URL được lập chỉ mục trên website</p>
                    </div>
                    
                    <xsl:choose>
                        <xsl:when test="sitemap:urlset">
                            <xsl:call-template name="urlset"/>
                        </xsl:when>
                        <xsl:when test="sitemap:sitemapindex">
                            <xsl:call-template name="sitemapindex"/>
                        </xsl:when>
                    </xsl:choose>
                    
                    <div class="footer">
                        <p>
                            Generated by <a href="/">NOBI FASHION VIỆT NAM</a> | 
                            <a href="/sitemap">Xem sitemap HTML</a>
                        </p>
                    </div>
                </div>
            </body>
        </html>
    </xsl:template>
    
    <xsl:template name="urlset">
        <xsl:variable name="urlCount" select="count(sitemap:urlset/sitemap:url)"/>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><xsl:value-of select="$urlCount"/></div>
                <div class="stat-label">Tổng số URL</div>
            </div>
        </div>
        <div class="content">
            <ul class="url-list">
                <xsl:for-each select="sitemap:urlset/sitemap:url">
                    <li class="url-item">
                        <a href="{sitemap:loc}" class="url-link" target="_blank">
                            <xsl:value-of select="sitemap:loc"/>
                        </a>
                        <div class="url-meta">
                            <xsl:if test="sitemap:lastmod">
                                <div class="url-meta-item">
                                    <strong>📅 Cập nhật:</strong>
                                    <span><xsl:value-of select="sitemap:lastmod"/></span>
                                </div>
                            </xsl:if>
                            <xsl:if test="sitemap:changefreq">
                                <div class="url-meta-item">
                                    <strong>🔄 Tần suất:</strong>
                                    <span class="badge badge-changefreq"><xsl:value-of select="sitemap:changefreq"/></span>
                                </div>
                            </xsl:if>
                            <xsl:if test="sitemap:priority">
                                <div class="url-meta-item">
                                    <strong>⭐ Độ ưu tiên:</strong>
                                    <span class="badge badge-priority"><xsl:value-of select="sitemap:priority"/></span>
                                </div>
                            </xsl:if>
                        </div>
                        <xsl:if test="image:image">
                            <div class="images-list">
                                <xsl:for-each select="image:image">
                                    <div class="image-item">
                                        <img src="{image:loc}" alt="{image:title}" onerror="this.style.display='none'"/>
                                        <div class="image-info">
                                            <div class="image-title"><xsl:value-of select="image:title"/></div>
                                            <div class="image-url"><xsl:value-of select="image:loc"/></div>
                                        </div>
                                    </div>
                                </xsl:for-each>
                            </div>
                        </xsl:if>
                    </li>
                </xsl:for-each>
            </ul>
        </div>
    </xsl:template>
    
    <xsl:template name="sitemapindex">
        <xsl:variable name="sitemapCount" select="count(sitemap:sitemapindex/sitemap:sitemap)"/>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value"><xsl:value-of select="$sitemapCount"/></div>
                <div class="stat-label">Số lượng Sitemap</div>
            </div>
        </div>
        <div class="content">
            <ul class="url-list">
                <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                    <li class="url-item">
                        <a href="{sitemap:loc}" class="url-link" target="_blank">
                            <xsl:value-of select="sitemap:loc"/>
                        </a>
                        <div class="url-meta">
                            <xsl:if test="sitemap:lastmod">
                                <div class="url-meta-item">
                                    <strong>📅 Cập nhật:</strong>
                                    <span><xsl:value-of select="sitemap:lastmod"/></span>
                                </div>
                            </xsl:if>
                        </div>
                    </li>
                </xsl:for-each>
            </ul>
        </div>
    </xsl:template>
</xsl:stylesheet>

