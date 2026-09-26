@extends('clients.layouts.master')

@section('title', renderMeta($post->meta_title ?? $post->title) . ' | ' . ($settings->site_name ?? ($settings->subname
    ?? 'NOBI FASHION VIỆT NAM')))

    @push('styles')
        <style>
            /* =========================================
                           ROOT VARIABLES - Clean & Minimal
                           ========================================= */
            :root {
                --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                --text-primary: #111827;
                --text-secondary: #6b7280;
                --text-muted: #9ca3af;
                --border: #e5e7eb;
                --bg-primary: #ffffff;
                --bg-secondary: #fafafa;
            }

            body {
                overflow-x: visible;
            }

            .nobifashion_header_main_nav_links {
                height: 20px !important;
            }

            /* Breadcrumb */
            .blog-breadcrumb {
                background: var(--bg-primary);
                padding: 12px 0;
                border-bottom: 1px solid var(--border, #e5e7eb);
            }

            .breadcrumb-list {
                display: flex;
                align-items: center;
                gap: 8px;
                list-style: none;
                padding: 0;
                margin: 0;
                flex-wrap: wrap;
            }

            .breadcrumb-list * {
                font-size: 12px;
            }

            .breadcrumb-item {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .breadcrumb-item a {
                color: var(--text-muted, #6b7280);
                text-decoration: none;
                transition: color 0.2s;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .breadcrumb-item a:hover {
                color: var(--text-primary, #111827);
            }

            .breadcrumb-item.active span {
                color: var(--text-primary, #111827);
                font-weight: 500;
            }

            .breadcrumb-separator {
                color: var(--text-muted, #9ca3af);
                font-size: 8px !important;
                display: flex;
                align-items: center;
            }

            .breadcrumb-separator i {
                font-size: 8px !important;
                display: flex;
                align-items: flex-end;
                margin-top: 2px;
            }

            /* Hero Section */
            .blog-hero-section {
                background: var(--bg-secondary);
                padding: 20px 0;
            }

            .hero-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 16px;
            }

            .category-badge {
                display: inline-block;
                background: #bcbfc3;
                color: white;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                text-decoration: none;
                margin-bottom: 12px;
            }

            .hero-title {
                font-size: 2rem;
                font-weight: 700;
                color: var(--text-primary);
                margin: 12px 0 16px;
                line-height: 1.3;
            }

            .hero-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                color: var(--text-muted);
                margin-bottom: 16px;
                padding-bottom: 12px;
                border-bottom: 1px solid var(--border);
            }

            .hero-meta * {
                font-size: 13px;
            }

            .hero-meta-item {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .hero-image-container {
                margin-top: 20px;
            }

            .hero-image {
                width: 100%;
                height: auto;
                max-height: 1000px;
                object-fit: cover;
                border-radius: 12px;
            }

            .hero-actions {
                display: flex;
                gap: 12px;
                margin-top: 16px;
            }

            .btn-read-now {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: var(--text-primary);
                color: white;
                padding: 10px 20px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 14px;
                text-decoration: none;
                border: none;
                cursor: pointer;
            }

            #blog-content-section {
                scroll-margin-top: 80px;
            }

            .btn-share {
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border);
                background: white;
                border-radius: 50%;
                color: var(--text-secondary);
                cursor: pointer;
            }

            /* Main Content Layout */
            .blog-content-section {
                background: var(--bg-secondary);
                padding: 20px 0 40px;
            }

            .content-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 16px;
                display: grid;
                grid-template-columns: 1fr 340px;
                gap: 20px;
                align-items: start;
                position: relative;
            }

            .article-wrapper {
                background: var(--bg-primary);
                border-radius: 12px;
                padding: 20px;
                border: 1px solid var(--border);
            }

            /* Mobile TOC */
            .mobile-toc {
                background: #f9fafb;
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 5px 2px;
                margin-bottom: 20px;
            }

            .mobile-toc-title {
                font-weight: 700;
                font-size: 14px;
                margin-bottom: 12px;
                color: var(--text-primary);
            }

            .mobile-toc ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .mobile-toc li {
                margin-bottom: 6px;
            }

            .mobile-toc a {
                color: var(--text-secondary);
                text-decoration: none;
                font-size: 13px;
            }

            /* =========================================
                           MODERN ARTICLE WRAPPER STYLING
                           ========================================= */
            .article-wrapper {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                color: #1a1a1a !important;
                word-wrap: break-word !important;
            }

            /* Content Headings */
            .article-wrapper h1,
            .article-wrapper h2,
            .article-wrapper h3,
            .article-wrapper h4 {
                color: #111827 !important;
                font-weight: 700 !important;
                line-height: 1.3 !important;
            }

            .article-wrapper h1 strong,
            .article-wrapper h2 strong,
            .article-wrapper h3 strong,
            .article-wrapper h4 strong {
                color: #111827 !important;
                font-weight: 700 !important;
                line-height: 1.3 !important;
                margin-top: 1.5rem !important;
                margin-bottom: 0.75rem !important;
            }

            .article-wrapper h1 {
                font-size: 1.6rem !important;
                border-bottom: 2px solid #f3f4f6 !important;
                padding-bottom: 0.75rem !important;
            }

            .article-wrapper h2 {
                font-size: 1.35rem !important;
                border-bottom: 1px solid #f3f4f6 !important;
                padding-bottom: 0.5rem !important;
            }

            .article-wrapper h3 {
                font-size: 1.2rem !important;
            }

            .article-wrapper h4 {
                font-size: 1.1rem !important;
            }

            .article-wrapper h1 strong {
                font-size: 1.6rem !important;
                border-bottom: 2px solid #f3f4f6 !important;
                padding-bottom: 0.75rem !important;
            }

            .article-wrapper h2 strong {
                font-size: 1.35rem !important;
                border-bottom: 1px solid #f3f4f6 !important;
                padding-bottom: 0.5rem !important;
            }

            .article-wrapper h3 strong {
                font-size: 1.2rem !important;
            }

            .article-wrapper h4 strong {
                font-size: 1.1rem !important;
            }

            /* Paragraphs & Text */
            .article-wrapper p {
                font-size: 1rem !important;
                color: #374151 !important;
            }

            .article-wrapper strong:not(h1 > strong, h2 > strong, h3 > strong, h4 > strong),
            .article-wrapper b:not(h1 > b, h2 > b, h3 > b, h4 > b) {
                font-weight: 700 !important;
                color: #111827 !important;
            }

            .article-wrapper em,
            .article-wrapper i {
                font-style: italic !important;
            }

            /* Links */
            .article-wrapper a {
                color: #2563eb !important;
                text-decoration: none !important;
                border-bottom: 1px solid transparent !important;
                transition: all 0.2s !important;
            }

            .article-wrapper a:hover {
                border-bottom-color: #2563eb !important;
                color: #1d4ed8 !important;
            }

            /* Lists */
            .article-wrapper ul,
            .article-wrapper ol {
                padding-left: 1.5rem !important;
                margin-bottom: 1.5rem !important;
            }

            .article-wrapper li {
                margin-bottom: 0.75rem !important;
                position: relative !important;
            }

            .article-wrapper ul li::before {
                content: "•" !important;
                color: #3b82f6 !important;
                font-weight: bold !important;
                display: inline-block !important;
                width: 1rem !important;
                margin-left: -1rem !important;
            }

            /* Blockquote */
            .article-wrapper blockquote {
                margin: 2rem 0 !important;
                padding: 1.5rem 2rem !important;
                background: #f8fafc !important;
                border-left: 4px solid #3b82f6 !important;
                border-radius: 0 8px 8px 0 !important;
                font-style: italic !important;
                color: #475569 !important;
                position: relative !important;
            }

            .article-wrapper blockquote::before {
                content: "\201C" !important;
                font-family: serif !important;
                font-size: 4rem !important;
                color: #cbd5e1 !important;
                position: absolute !important;
                left: 0.5rem !important;
                top: -1rem !important;
                opacity: 0.5 !important;
            }

            /* Images & Figures */
            .article-wrapper img {
                max-width: 100% !important;
                height: auto !important;
                display: block !important;
                margin: 10px auto !important;
                border-radius: 12px !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
                transition: transform 0.3s ease !important;
            }

            .article-wrapper figure {
                margin: 1.5rem auto !important;
                text-align: center !important;
                width: fit-content !important;
            }

            .article-wrapper figure img {
                margin-bottom: 0 !important;
                border-bottom-left-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }

            .article-wrapper figcaption {
                color: #6b7280 !important;
                background-color: #ededed !important;
                font-style: italic !important;
                padding: 10px !important;
                border-bottom-left-radius: 12px !important;
                border-bottom-right-radius: 12px !important;
            }

            .article-wrapper figcaption em {
                font-size: 0.8rem !important;
            }

            /* Tables */
            .article-wrapper table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 2rem 0 !important;
                font-size: 0.95rem !important;
                border: 1px solid #e5e7eb !important;
                border-radius: 8px !important;
                overflow: hidden !important;
            }

            .article-wrapper thead th {
                background-color: #f9fafb !important;
                color: #111827 !important;
                font-weight: 700 !important;
                text-align: left !important;
                padding: 1rem !important;
                border-bottom: 2px solid #e5e7eb !important;
            }

            .article-wrapper td {
                padding: 0.85rem 1rem !important;
                border-bottom: 1px solid #f3f4f6 !important;
                color: #4b5563 !important;
            }

            .article-wrapper tr:last-child td {
                border-bottom: none !important;
            }

            .article-wrapper tr:hover td {
                background-color: #fafafa !important;
            }

            /* HR */
            .article-wrapper hr {
                height: 1px !important;
                background-color: #e5e7eb !important;
                border: none !important;
                margin: 3rem 0 !important;
            }

            /* Code & Pre */
            .article-wrapper code {
                font-family: 'Fira Code', 'Monaco', 'Consolas', monospace !important;
                background-color: #f1f5f9 !important;
                color: #ef4444 !important;
                padding: 0.2rem 0.4rem !important;
                border-radius: 4px !important;
                font-size: 0.9rem !important;
            }

            .article-wrapper pre {
                background-color: #1e293b !important;
                color: #f8fafc !important;
                padding: 1.5rem !important;
                border-radius: 8px !important;
                overflow-x: auto !important;
                margin: 2rem 0 !important;
                font-size: 0.9rem !important;
                line-height: 1.6 !important;
            }

            .article-wrapper pre code {
                background-color: transparent !important;
                color: inherit !important;
                padding: 0 !important;
            }

            /* Rich content backward compatibility */
            .rich-content {
                all: inherit !important;
            }

            /* Post Footer */
            .post-footer {
                border-top: 1px solid var(--border);
                margin-top: 32px;
                padding-top: 20px;
            }

            .tags-section {
                margin-bottom: 20px;
            }

            .tags-label {
                font-weight: 600;
                font-size: 13px;
                margin-right: 8px;
            }

            .tag-pill {
                display: inline-block;
                background: #f3f4f6;
                color: var(--text-primary);
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                text-decoration: none;
                margin-right: 6px;
                margin-bottom: 6px;
            }

            .internal-links-widget {
                background: #f9fafb;
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 16px;
                margin-top: 24px;
            }

            .internal-links-title {
                font-weight: 700;
                font-size: 16px;
                margin-bottom: 16px;
            }

            .internal-links-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            @media (max-width: 600px) {
                .internal-links-grid {
                    grid-template-columns: 1fr;
                }
            }

            .internal-link-item {
                display: block;
                padding: 10px 12px;
                background: white;
                border-radius: 8px;
                text-decoration: none;
                color: var(--text-primary);
                border: 1px solid var(--border);
                font-size: 13px;
                line-height: 1.4;
                height: 100%;
                transition: all 0.2s;
            }

            .internal-link-item:hover {
                border-color: #3b82f6;
                color: #3b82f6;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }

            /* Sidebar */
            .sidebar {
                position: sticky;
                top: 54px;
                align-self: start;
                height: fit-content;
                will-change: transform;
            }

            .sidebar-toc {
                background: var(--bg-primary);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 16px;
            }

            .sidebar-toc-title {
                font-weight: 700;
                font-size: 14px;
                margin-bottom: 12px;
                padding-bottom: 8px;
                border-bottom: 1px solid var(--border);
            }

            .toc-list {
                list-style: none;
                padding: 0;
                margin: 0;
                max-height: 300px;
                overflow-y: auto;
            }

            .toc-list li {
                margin-bottom: 4px;
            }

            .toc-list a {
                display: block;
                padding: 6px 10px;
                font-size: 13px;
                color: var(--text-secondary);
                text-decoration: none;
                border-radius: 6px;
            }

            .toc-list a:hover,
            .toc-list a.active {
                background: #f3f4f6;
                color: var(--text-primary);
            }

            .toc-list li.indent {
                margin-left: 16px;
            }

            /* Mobile Table of Contents (TOC) - Tối giản, gọn gàng, không chấm/gạch */
            .article-wrapper .mobile-toc {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 10px 14px;
                margin-bottom: 18px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            }

            .article-wrapper .mobile-toc-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding-bottom: 6px;
                margin-bottom: 6px;
                border-bottom: 1px solid #edf2f7;
            }

            .article-wrapper .mobile-toc-title {
                font-size: 13.5px;
                font-weight: 700;
                color: #0f172a;
                display: flex;
                align-items: center;
            }

            .article-wrapper .mobile-toc-title i {
                color: var(--primary-color, #ff3366);
                font-size: 13px;
            }

            .article-wrapper .mobile-toc ul,
            .article-wrapper .mobile-toc-list {
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex;
                flex-direction: column;
                gap: 1px;
                max-height: 320px;
                overflow-y: auto;
                scrollbar-width: thin;
            }

            /* Triệt tiêu hoàn toàn chấm tròn ::before mặc định của bài viết */
            .article-wrapper .mobile-toc ul li::before,
            .article-wrapper .mobile-toc li::before {
                content: none !important;
                display: none !important;
                width: 0 !important;
                margin: 0 !important;
            }

            .article-wrapper .mobile-toc-item {
                list-style: none !important;
                margin: 0 !important;
                margin-bottom: 2px !important;
                padding: 0 !important;
            }

            .article-wrapper .mobile-toc-link {
                display: block !important;
                text-decoration: none !important;
                border-bottom: none !important;
                border-radius: 4px;
                transition: background 0.15s ease, color 0.15s ease !important;
            }

            /* Phân cấp Mục lớn H2 */
            .article-wrapper .mobile-toc-item.is-h2 {
                margin-top: 5px !important;
            }

            .article-wrapper .mobile-toc-item.is-h2:first-child {
                margin-top: 0 !important;
            }

            .article-wrapper .mobile-toc-item.is-h2 .mobile-toc-link {
                padding: 2px 4px !important;
                color: #1e293b !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                line-height: 1.4 !important;
            }

            /* Phân cấp Mục con H3 - Thụt lề nhẹ, không gạch, không chấm */
            .article-wrapper .mobile-toc-item.is-h3 {
                margin-left: 14px !important;
                border-left: none !important;
                padding-left: 0 !important;
            }

            .article-wrapper .mobile-toc-item.is-h3 .mobile-toc-link {
                padding: 2px 4px !important;
                color: #64748b !important;
                font-size: 11.5px !important;
                font-weight: 400 !important;
                line-height: 1.4 !important;
            }

            .article-wrapper .mobile-toc-link:hover,
            .article-wrapper .mobile-toc-link:active {
                background: #f1f5f9 !important;
                color: var(--primary-color, #ff3366) !important;
                border-bottom: none !important;
            }

            .sidebar-widget {
                background: var(--bg-primary);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 16px;
            }

            .widget-title {
                font-weight: 700;
                font-size: 14px;
                margin-bottom: 12px;
            }

            .related-post-item {
                display: flex;
                gap: 12px;
                margin-bottom: 12px;
                text-decoration: none;
                color: inherit;
            }

            .related-thumb {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 8px;
                flex-shrink: 0;
            }

            .related-info h4 {
                font-size: 13px;
                font-weight: 600;
                margin: 0 0 4px;
                line-height: 1.4;
                color: var(--text-primary);
            }

            .related-date {
                font-size: 11px;
                color: var(--text-muted);
            }

            .newsletter-widget {
                background: #1f2937;
                color: white;
                border: none;
            }

            .newsletter-widget .widget-title {
                color: white;
            }

            .newsletter-desc {
                color: rgba(255, 255, 255, 0.7);
                font-size: 12px;
                margin-bottom: 12px;
            }

            .newsletter-form input {
                width: 100%;
                padding: 10px 14px;
                border-radius: 20px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                background: rgba(255, 255, 255, 0.1);
                color: white;
                font-size: 13px;
                margin-bottom: 10px;
            }

            .newsletter-form input::placeholder {
                color: rgba(255, 255, 255, 0.5);
            }

            .newsletter-form button {
                width: 100%;
                padding: 10px 20px;
                border-radius: 20px;
                border: none;
                background: #3b82f6;
                color: white;
                font-weight: 600;
                font-size: 13px;
                cursor: pointer;
            }

            .newsletter-message {
                font-size: 12px;
                margin-top: 8px;
            }

            /* Responsive */
            @media (max-width: 1024px) {
                .content-container {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                
                .blog-content-section {
                    padding: 0;
                }

                .blog-hero-section {
                    padding: 10px 0;
                }

                .sidebar {
                    position: static;
                }

                .sidebar-toc {
                    display: none;
                }
                
                .category-badge {
                    margin-bottom: 0;
                }
            }

            @media (max-width: 768px) {
                .hero-container {
                    padding: 0 5px;
                }

                .hero-title {
                    font-size: 1.5rem;
                }

                .content-container {
                    padding: 0 5px;
                }

                .article-wrapper {
                    padding: 5px !important;
                    border: none !important;
                }
            }
        </style>

        <style>
            .comments-section {
                margin-top: 18px;
                padding: 12px 14px;
                border-radius: 12px;
                background: #ffffff;
                border: 1px solid var(--border);
                box-shadow: 0 1px 6px rgba(0, 0, 0, 0.02);
            }

            .comments-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f1f5f9;
            }

            .comments-header-left {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .comments-title {
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
                margin: 0;
            }

            .comments-badge {
                font-size: 11.5px;
                font-weight: 700;
                background: #f1f5f9;
                color: #475569;
                padding: 2px 7px;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
            }

            .comment-info-note {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 11px;
                color: #64748b;
                margin: 0;
            }

            /* Comment Form Card */
            .comment-form-card {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 10px 12px;
                margin-top: 10px;
                background: #f8fafc;
            }

            .comment-form-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
            }

            .comment-form-head h4 {
                font-size: 13.5px;
                font-weight: 700;
                color: #0f172a;
                margin: 0;
            }

            .comment-form-privacy {
                font-size: 11px;
                color: #94a3b8;
            }

            .comment-guest-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 8px;
            }

            .comment-input-wrap input,
            .comment-textarea-wrap textarea {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 6px 10px;
                font-size: 12.5px;
                background: #ffffff;
                color: #0f172a;
                outline: none;
                transition: all 0.2s ease;
                box-sizing: border-box;
            }

            .comment-input-wrap input:focus,
            .comment-textarea-wrap textarea:focus {
                border-color: #0f172a;
                box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.08);
            }

            .comment-textarea-wrap {
                margin-bottom: 8px;
            }

            .comment-textarea-wrap textarea {
                min-height: 52px;
                resize: vertical;
                line-height: 1.45;
                display: block;
            }

            .comment-form-actions {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }

            .comment-form-hint {
                font-size: 11px;
                color: #94a3b8;
            }

            .comment-submit-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #0f172a;
                color: #ffffff;
                border: none;
                border-radius: 6px;
                padding: 6px 14px;
                font-weight: 600;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }

            .comment-submit-btn:hover {
                background: #1e293b;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
                transform: translateY(-1px);
            }

            .reply-wrapper {
                margin-bottom: 6px;
            }

            .reply-indicator {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 3px 8px;
                border-radius: 6px;
                background: #fee2e2;
                color: #991b1b;
                font-size: 11px;
            }

            .reply-indicator button {
                background: transparent;
                border: none;
                color: #b91c1c;
                cursor: pointer;
                font-weight: 700;
                padding: 0 2px;
                font-size: 11px;
            }

            .comments-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .comment-card {
                padding: 10px 12px;
                border-radius: 10px;
                border: 1px solid var(--border);
                background: white;
            }

            .comment-card.reply {
                margin-left: 20px;
            }

            .comment-author {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
            }

            .comment-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #3b82f6;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 14px;
            }

            /* Rating Stars Picker */
            .comment-rating-group {
                margin-bottom: 8px;
                padding: 0;
            }

            .rating-stars-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .rating-label {
                font-size: 12px;
                font-weight: 600;
                color: #475569;
                user-select: none;
            }

            .stars-picker {
                display: inline-flex;
                align-items: center;
                gap: 3px;
            }

            .stars-picker .star-btn {
                background: transparent;
                border: none;
                padding: 1px 2px;
                font-size: 17px;
                color: #cbd5e1;
                cursor: pointer;
                transition: transform 0.15s cubic-bezier(0.175, 0.885, 0.32, 1.275), color 0.15s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }

            .stars-picker .star-btn:hover {
                transform: scale(1.22);
            }

            .stars-picker .star-btn.hovered,
            .stars-picker .star-btn.active {
                color: #f59e0b;
            }

            .stars-picker .star-btn.active {
                filter: drop-shadow(0 1px 2px rgba(245, 158, 11, 0.35));
            }

            .rating-feedback {
                font-size: 11.5px;
                font-weight: 600;
                color: #d97706;
                transition: opacity 0.2s ease;
            }

            @keyframes ratingShake {
                0%, 100% { transform: translateX(0); }
                20%, 60% { transform: translateX(-4px); }
                40%, 80% { transform: translateX(4px); }
            }

            .comment-rating-group.has-error {
                animation: ratingShake 0.4s ease;
            }

            .comment-rating-group.has-error .rating-label {
                color: #dc2626 !important;
            }

            .comment-meta {
                font-size: 12px;
                color: var(--text-muted);
            }

            .comment-rating {
                display: inline-flex;
                align-items: center;
                gap: 2px;
                margin-top: 2px;
                font-size: 11px;
            }

            .comment-rating .star {
                color: #cbd5e1;
                font-size: 11px;
            }

            .comment-rating .star.filled {
                color: #f59e0b;
            }

            .comment-rating .rating-text {
                font-size: 11px;
                color: #64748b;
                margin-left: 4px;
                font-weight: 600;
            }

            .comment-content {
                font-size: 14px;
                line-height: 1.6;
                margin-top: 8px;
            }

            .comment-actions {
                display: flex;
                gap: 12px;
                margin-top: 8px;
                font-size: 12px;
            }

            .comment-actions button {
                border: none;
                background: transparent;
                color: #3b82f6;
                font-weight: 600;
                cursor: pointer;
            }

            .no-comments {
                text-align: center;
                padding: 8px 0;
                font-size: 12.5px;
                color: var(--text-muted);
            }

            .load-more-comments {
                display: block;
                margin: 12px auto 0;
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 6px 16px;
                background: white;
                font-weight: 600;
                font-size: 12px;
                cursor: pointer;
            }

            .comment-status-message {
                margin-top: 6px;
                font-size: 12px;
            }

            .comment-status-message.success {
                color: #16a34a;
            }

            .comment-status-message.error {
                color: #dc2626;
            }

            .reply-indicator {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 8px;
                border-radius: 6px;
                background: #fef2f2;
                color: #b91c1c;
                font-size: 11px;
                margin-bottom: 6px;
            }

            .comments-pagination:empty {
                display: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
            }

            .comments-pagination {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid var(--border);
            }

            .comment-pagination-info {
                font-size: 13px;
                color: var(--text-muted);
                margin-bottom: 12px;
                text-align: center;
            }

            .comment-pagination-buttons {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .comment-page-btn {
                padding: 8px 14px;
                border: 1px solid var(--border);
                background: white;
                color: var(--text-primary);
                border-radius: 8px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                min-width: 40px;
            }

            .comment-page-btn:hover:not(.disabled) {
                background: #f3f4f6;
                border-color: #d1d5db;
            }

            .comment-page-btn.active {
                background: var(--text-primary);
                color: white;
                border-color: var(--text-primary);
            }

            .comment-page-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .comment-page-ellipsis {
                padding: 8px 4px;
                color: var(--text-muted);
            }

            .social-share-group {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            div#article-content * {
                line-height: 1.7 !important;
            }

            @media (max-width: 768px) {
                .blog-breadcrumb {
                    padding: 5px 0;
                    background: #fdfdfd;
                    border-bottom: 1px solid #f1f5f9;
                }

                div#article-content * {
                    line-height: 1.6 !important;
                }

                .hero-actions {
                    justify-content: center;
                    margin-top: 10px;
                }

                .hero-meta {
                    margin-bottom: 0;
                }

                .article-wrapper figcaption em {
                    font-size: 0.8rem !important;
                }

                .article-wrapper figure {
                    margin: 10px 0 !important;
                }

                .breadcrumb-list {
                    display: flex;
                    align-items: center;
                    flex-wrap: nowrap;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                    gap: 8px;
                    padding: 2px 0;
                }

                .breadcrumb-list::-webkit-scrollbar {
                    display: none;
                }

                .breadcrumb-item {
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                }

                .breadcrumb-item a {
                    font-size: 13px !important;
                    font-weight: 500;
                    color: #4b5563;
                    white-space: nowrap;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                }

                .breadcrumb-item a i {
                    font-size: 12px !important;
                    color: #6b7280;
                }

                .breadcrumb-item a:hover {
                    color: var(--primary-color, #ff3366);
                }

                .breadcrumb-separator {
                    color: #cbd5e1 !important;
                    font-size: 10px !important;
                    display: inline-flex;
                    align-items: center;
                    flex-shrink: 0;
                    margin: 0;
                }

                .breadcrumb-separator i {
                    font-size: 10px !important;
                    margin: 0;
                }

                .breadcrumb-item.active {
                    flex-shrink: 0;
                    max-width: 200px;
                }

                .breadcrumb-item.active span {
                    font-size: 13px !important;
                    font-weight: 600;
                    color: #1e293b;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: block;
                }

                .comments-section {
                    padding: 10px 12px;
                    margin-top: 14px;
                }

                .comment-form-card {
                    padding: 8px 10px;
                }

                .comment-guest-row {
                    grid-template-columns: 1fr;
                }

                .comment-form-actions {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }

                .comment-submit-btn {
                    width: 100%;
                    justify-content: center;
                }

                .comment-card.reply {
                    margin-left: 14px;
                }

                .comment-pagination-buttons {
                    gap: 4px;
                }

                .comment-page-btn {
                    padding: 6px 10px;
                    font-size: 12px;
                    min-width: 36px;
                }
            }
        </style>
    @endpush

@section('head')
    {{-- SEO Meta Tags --}}
    <meta name="description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta name="keywords" content="{{ renderMeta($post->meta_keywords) }}">
    <link rel="canonical" href="{{ $post->meta_canonical ?? route('client.blog.show', $post) }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ renderMeta($post->meta_title ?? $post->title) }}">
    <meta property="og:description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta property="og:url" content="{{ route('client.blog.show', $post) }}">
    <meta property="og:image"
        content="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ renderMeta($post->meta_title ?? $post->title) }}">
    <meta name="twitter:description" content="{{ renderMeta($post->meta_description ?? $post->excerpt_text) }}">
    <meta name="twitter:image"
        content="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    <link rel="preload" as="image"
        href="{{ $post->thumbnail ? asset('clients/assets/img/posts/' . $post->thumbnail) : asset('clients/assets/no-image.webp') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    {{-- Load Fonts: Crimson Pro (Elegant Serif) & Inter (Clean Sans) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
@endsection

@section('schema')
    @if (isset($schemaData) && is_array($schemaData))
        @foreach ($schemaData as $schema)
            <script type="application/ld+json">
                {!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
            </script>
        @endforeach
    @endif

    {{-- Comments Widget --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const section = document.getElementById('comments-section');
            if (!section) return;

            const listEl = document.getElementById('comments-list');
            const paginationEl = document.getElementById('comments-pagination');
            const countEl = document.getElementById('comments-count');
            const form = document.getElementById('comment-form');
            const statusEl = document.getElementById('comment-status-message');
            const parentInput = document.getElementById('comment-parent-id');
            const replyIndicator = document.getElementById('reply-indicator');
            const replyToName = document.getElementById('reply-to-name');
            const cancelReplyBtn = document.getElementById('cancel-reply');

            // Rating Stars
            const ratingGroup = document.getElementById('comment-rating-group');
            const ratingInput = document.getElementById('comment-rating-input');
            const starsPicker = document.getElementById('stars-picker');
            const ratingFeedback = document.getElementById('rating-feedback');
            const starBtns = starsPicker ? starsPicker.querySelectorAll('.star-btn') : [];

            const ratingLabels = {
                1: 'Rất tệ (1★)',
                2: 'Tệ (2★)',
                3: 'Bình thường (3★)',
                4: 'Hài lòng (4★)',
                5: 'Tuyệt vời (5★)'
            };

            const updateStarDisplay = (hoverValue = 0) => {
                const currentVal = hoverValue || parseInt(ratingInput?.value, 10) || 0;
                starBtns.forEach(btn => {
                    const starVal = parseInt(btn.dataset.rating, 10);
                    if (hoverValue > 0) {
                        btn.classList.toggle('hovered', starVal <= hoverValue);
                    } else {
                        btn.classList.remove('hovered');
                        btn.classList.toggle('active', starVal <= currentVal);
                    }
                });

                if (hoverValue > 0) {
                    if (ratingFeedback) ratingFeedback.textContent = ratingLabels[hoverValue] || '';
                } else if (currentVal > 0) {
                    if (ratingFeedback) ratingFeedback.textContent = ratingLabels[currentVal] || '';
                } else {
                    if (ratingFeedback) ratingFeedback.textContent = '';
                }
            };

            starBtns.forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    const rating = parseInt(btn.dataset.rating, 10);
                    updateStarDisplay(rating);
                });

                btn.addEventListener('click', () => {
                    const rating = parseInt(btn.dataset.rating, 10);
                    if (ratingInput) ratingInput.value = rating;
                    if (ratingGroup) ratingGroup.classList.remove('has-error');
                    updateStarDisplay();
                });
            });

            if (starsPicker) {
                starsPicker.addEventListener('mouseleave', () => {
                    updateStarDisplay(0);
                });
            }

            const config = {
                commentableId: parseInt(section.dataset.commentableId, 10),
                commentableType: section.dataset.commentableType,
                apiUrl: '{{ url('/api/v1/comments') }}',
                submitUrl: '{{ route('client.comments.store') }}',
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
            };

            let currentPage = 1;
            let lastPage = 1;
            let isLoading = false;

            const sanitize = (text = '') => {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };

            const formatDate = (iso) => {
                const date = new Date(iso);
                return Number.isNaN(date.getTime()) ? '' : date.toLocaleString('vi-VN', {
                    hour12: false
                });
            };

            const renderComment = (comment, depth = 0) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'comment-card' + (depth > 0 ? ' reply' : '');

                const authorName = comment.account?.name || comment.guest_name || 'Khách';
                const rating = Number(comment.rating) || 0;
                const ratingStars = rating > 0 ?
                    `<div class="comment-rating" aria-label="Đánh giá ${rating} sao">
                        ${Array.from({ length: 5 }).map((_, i) => `<span class="star ${i < rating ? 'filled' : ''}"><i class="fa-solid fa-star"></i></span>`).join('')}
                        <span class="rating-text">${rating}/5</span>
                    </div>` : '';
                const avatarText = sanitize(authorName).charAt(0).toUpperCase();

                wrapper.innerHTML = `
                    <div class="comment-author">
                        <div class="comment-avatar">${avatarText}</div>
                        <div>
                            <strong>${sanitize(authorName)}</strong>
                            <div class="comment-meta">${formatDate(comment.created_at)}</div>
                            ${ratingStars}
                        </div>
                    </div>
                    <div class="comment-content">${sanitize(comment.content)}</div>
                    <div class="comment-actions">
                        <button type="button" data-reply-id="${comment.id}" data-reply-name="${sanitize(authorName)}">Trả lời</button>
                        <button type="button" data-report-id="${comment.id}">Báo xấu</button>
                    </div>
                `;

                if (comment.replies && comment.replies.length) {
                    comment.replies.forEach(reply => wrapper.appendChild(renderComment(reply, depth + 1)));
                }

                return wrapper;
            };

            const renderPagination = (meta) => {
                if (!meta || meta.last_page <= 1) {
                    paginationEl.innerHTML = '';
                    return;
                }

                const pages = [];
                const current = meta.current_page;
                const last = meta.last_page;
                const total = meta.total;
                const perPage = meta.per_page;
                const from = meta.from || 0;
                const to = meta.to || 0;

                // Previous button
                pages.push(`<button class="comment-page-btn ${current === 1 ? 'disabled' : ''}" 
                    data-page="${current - 1}" ${current === 1 ? 'disabled' : ''}>‹ Trước</button>`);

                // Page numbers
                let startPage = Math.max(1, current - 2);
                let endPage = Math.min(last, current + 2);

                if (startPage > 1) {
                    pages.push(`<button class="comment-page-btn" data-page="1">1</button>`);
                    if (startPage > 2) {
                        pages.push(`<span class="comment-page-ellipsis">...</span>`);
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    pages.push(`<button class="comment-page-btn ${i === current ? 'active' : ''}" 
                        data-page="${i}">${i}</button>`);
                }

                if (endPage < last) {
                    if (endPage < last - 1) {
                        pages.push(`<span class="comment-page-ellipsis">...</span>`);
                    }
                    pages.push(`<button class="comment-page-btn" data-page="${last}">${last}</button>`);
                }

                // Next button
                pages.push(`<button class="comment-page-btn ${current === last ? 'disabled' : ''}" 
                    data-page="${current + 1}" ${current === last ? 'disabled' : ''}>Sau ›</button>`);

                paginationEl.innerHTML = `
                    <div class="comment-pagination-info">
                        Hiển thị ${from}-${to} trong tổng ${total} bình luận
                    </div>
                    <div class="comment-pagination-buttons">
                        ${pages.join('')}
                    </div>
                `;

                // Attach event listeners
                paginationEl.querySelectorAll('.comment-page-btn:not(.disabled)').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const page = parseInt(btn.dataset.page);
                        if (page && page !== current) {
                            loadComments(page);
                            window.scrollTo({
                                top: listEl.offsetTop - 100,
                                behavior: 'smooth'
                            });
                        }
                    });
                });
            };

            const loadComments = async (page = 1) => {
                if (isLoading) return;
                isLoading = true;
                listEl.innerHTML = '<div class="text-center text-muted">Đang tải bình luận...</div>';
                paginationEl.innerHTML = '';

                try {
                    const url = new URL(config.apiUrl);
                    url.searchParams.set('commentable_id', config.commentableId);
                    url.searchParams.set('commentable_type', config.commentableType);
                    url.searchParams.set('page', page);

                    const res = await fetch(url, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Không thể tải bình luận.');

                    listEl.innerHTML = '';

                    if (data.data && data.data.length) {
                        data.data.forEach(comment => listEl.appendChild(renderComment(comment)));
                    } else {
                        listEl.innerHTML =
                            '<div class="no-comments">Chưa có bình luận nào. Hãy là người đầu tiên!</div>';
                    }

                    // Render pagination
                    if (data.meta) {
                        currentPage = data.meta.current_page || 1;
                        lastPage = data.meta.last_page || 1;
                        renderPagination(data.meta);
                    }
                } catch (error) {
                    console.error(error);
                    listEl.innerHTML =
                        '<div class="no-comments text-danger">Không thể tải bình luận.</div>';
                } finally {
                    isLoading = false;
                }
            };

            listEl.addEventListener('click', (event) => {
                const replyBtn = event.target.closest('button[data-reply-id]');
                const reportBtn = event.target.closest('button[data-report-id]');

                if (replyBtn) {
                    parentInput.value = replyBtn.dataset.replyId;
                    replyToName.textContent = replyBtn.dataset.replyName || '';
                    replyIndicator.style.display = 'block';
                    form.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                if (reportBtn) {
                    reportComment(reportBtn.dataset.reportId);
                }
            });

            cancelReplyBtn?.addEventListener('click', () => {
                parentInput.value = '';
                replyIndicator.style.display = 'none';
            });

            const reportComment = async (commentId) => {
                try {
                    await fetch(`${config.apiUrl}/${commentId}/report`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrf,
                            'Accept': 'application/json',
                        },
                    });
                    showCustomToast('Báo cáo đã được gửi. Cảm ơn bạn!', 'success');
                } catch (error) {
                    showCustomToast('Không thể báo cáo bình luận.', 'error');
                }
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();

                // Honeypot chống bot
                if (form.website?.value) return;

                // Bắt buộc chọn số sao đánh giá (từ 1 đến 5 sao)
                const ratingVal = parseInt(ratingInput?.value || '0', 10);
                if (!ratingVal || ratingVal < 1 || ratingVal > 5) {
                    if (ratingGroup) {
                        ratingGroup.classList.add('has-error');
                        ratingGroup.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                    showCustomToast("Vui lòng chọn số sao đánh giá (1-5 sao) cho bài viết!", "warning");
                    return;
                }

                const formData = new FormData(form);
                formData.append('commentable_id', config.commentableId);
                formData.append('commentable_type', config.commentableType);

                showCustomToast("Đang gửi bình luận...", "info");

                try {
                    const res = await fetch(config.submitUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': config.csrf,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || "Không thể gửi bình luận.");

                    // Reset form
                    form.reset();
                    if (ratingInput) ratingInput.value = '';
                    updateStarDisplay(0);
                    parentInput.value = '';
                    replyIndicator.style.display = 'none';

                    // Cập nhật số lượng bình luận
                    const newCount = parseInt(countEl.textContent || '0', 10) + 1;
                    countEl.textContent = newCount;

                    showCustomToast("Cảm ơn bạn! Bình luận sẽ hiển thị sau khi được duyệt.", "success");

                } catch (error) {
                    console.error(error);
                    showCustomToast(error.message, "error");
                }
            });


            setTimeout(() => {
                loadComments();
            }, 5000);
        });
    </script>
@endsection

@section('content')
    @php
        $shareUrl = urlencode(route('client.blog.show', $post));
        $shareText = urlencode(renderMeta($post->title) . ' - ' . config('app.name'));
    @endphp

    {{-- ========================================= --}}
    {{-- BREADCRUMB --}}
    {{-- ========================================= --}}
    <nav aria-label="breadcrumb" class="blog-breadcrumb">
        <div class="content-container">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item">
                    <a href="{{ route('client.home.index') }}">
                        <i class="fas fa-home"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li class="breadcrumb-separator">
                    <i class="fa-solid fa-angles-right"></i>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('client.blog.index') }}">
                        <span>Tin tức</span>
                    </a>
                </li>
                @if ($post->category)
                    <li class="breadcrumb-separator">
                        <i class="fa-solid fa-angles-right"></i>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('client.blog.category', $post->category) }}">
                            <span>{{ $post->category->name }}</span>
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-separator">
                    <i class="fa-solid fa-angles-right"></i>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <span>{{ renderMeta(Str::limit($post->title, 500)) }}</span>
                </li>
            </ol>
        </div>
    </nav>

    {{-- ========================================= --}}
    {{-- HERO SECTION --}}
    {{-- ========================================= --}}
    <section class="blog-hero-section">
        <div class="hero-container">
            {{-- Category Badge --}}
            @if ($post->category)
                <a href="{{ route('client.blog.category', $post->category) }}" class="category-badge">
                    <i class="fas fa-bookmark"></i>
                    {{ $post->category->name }}
                </a>
            @else
                <span class="category-badge">
                    <i class="fas fa-bookmark"></i>
                    Bài viết
                </span>
            @endif

            {{-- Title --}}
            <h1 class="hero-title">{{ renderMeta($post->title) }}</h1>

            {{-- Meta Info --}}
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ $post->author?->displayName() ?? 'Team Nobi Fashion' }}</span>
                </div>
                <div class="hero-meta-item">
                    <i class="far fa-calendar-alt"></i>
                    <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                </div>
                <div class="hero-meta-item">
                    <i class="far fa-eye"></i>
                    <span>{{ number_format($post->views) }} lượt xem</span>
                </div>
                <div class="hero-meta-item">
                    <i class="far fa-clock"></i>
                    <span>{{ ceil(str_word_count(strip_tags($post->content)) / 250) }} phút đọc</span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="hero-actions">
                <button class="btn-read-now"
                    onclick="const el = document.getElementById('blog-content-section'); if (el) { const y = el.getBoundingClientRect().top + (window.pageYOffset || window.scrollY || document.documentElement.scrollTop) - 110; window.scrollTo({ top: y, behavior: 'smooth' }); }">
                    <span>Bắt đầu đọc</span>
                    <i class="fas fa-arrow-down"></i>
                </button>

                <div class="social-share-group">
                    <button class="btn-share"
                        onclick="window.open('https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}')"
                        title="Chia sẻ Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </button>
                    <button class="btn-share"
                        onclick="window.open('https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareText }}')"
                        title="Chia sẻ Twitter">
                        <i class="fab fa-twitter"></i>
                    </button>
                    <button class="btn-share"
                        onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}')"
                        title="Chia sẻ LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </button>
                </div>
            </div>

            {{-- Featured Image --}}
            <div class="hero-image-container">
                @if ($post->thumbnail)
                    <img src="{{ asset('clients/assets/img/posts/' . $post->thumbnail) }}" class="hero-image"
                        alt="{{ renderMeta($post->thumbnail_alt_text ?? $post->title) }}" loading="eager">
                @else
                    <div class="hero-image"
                        style="background: linear-gradient(135deg, var(--primary-light), var(--accent-light)); display: flex; align-items: center; justify-content: center; min-height: 400px;">
                        <span
                            style="font-size: 120px; font-family: var(--font-heading); color: var(--primary); opacity: 0.3;">
                            {{ strtoupper(Str::substr($post->title, 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ========================================= --}}
    {{-- MAIN CONTENT SECTION --}}
    {{-- ========================================= --}}
    <section id="blog-content-section" class="blog-content-section">
        <div class="content-container">

            {{-- LEFT: ARTICLE CONTENT --}}
            <article class="article-wrapper">

                {{-- Mobile TOC --}}
                @if ($toc->isNotEmpty())
                    <div class="mobile-toc d-lg-none">
                        <div class="mobile-toc-header">
                            <span class="mobile-toc-title">
                                <i class="fas fa-list-ol me-2"></i>Mục lục bài viết
                            </span>
                        </div>
                        <ul class="mobile-toc-list">
                            @foreach ($toc as $item)
                                <li class="mobile-toc-item {{ $item['tag'] === 'h3' ? 'is-h3' : 'is-h2' }}">
                                    <a href="#{{ $item['id'] }}" class="mobile-toc-link">
                                        {{ renderMeta($item['label']) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Rich Content --}}
                <div id="article-content" class="rich-content">
                    {!! renderMeta($contentWithAnchors) !!}
                </div>

                {{-- Post Footer --}}
                <div class="post-footer">
                    {{-- Tags --}}
                    <div class="tags-section">
                        <span class="tags-label">Tags:</span>
                        @forelse($tags as $tag)
                            <a href="{{ route('client.blog.index', ['tag' => $tag->slug]) }}" class="tag-pill">
                                #{{ $tag->name }}
                            </a>
                        @empty
                            <span class="text-muted" style="font-size: 14px;">Chưa có tag</span>
                        @endforelse
                    </div>

                    {{-- Internal Links Widget --}}
                    @if ($internalLinks->isNotEmpty())
                        <div class="internal-links-widget">
                            <h5 class="internal-links-title">
                                💡 Có thể bạn quan tâm
                            </h5>
                            <div class="internal-links-grid">
                                @foreach ($internalLinks as $link)
                                    <a href="{{ route('client.blog.show', $link) }}" class="internal-link-item">
                                        <i class="fas fa-chevron-right me-1 small opacity-50"></i>
                                        {{ renderMeta($link->title) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Comments --}}
                <section class="comments-section" id="comments-section" data-commentable-id="{{ $post->id }}"
                    data-commentable-type="{{ \App\Models\Post::class }}">
                    <div class="comments-header">
                        <div class="comments-header-left">
                            <h3 class="comments-title">Bình luận</h3>
                            <span class="comments-badge"><span id="comments-count">{{ $commentsCount }}</span></span>
                        </div>
                        <span class="comment-info-note">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            Duyệt trước khi hiển thị công khai
                        </span>
                    </div>

                    <div id="comments-list" class="comments-list"></div>
                    <div id="comments-pagination" class="comments-pagination"></div>

                    <div class="comment-form-card" id="comment-form-card">
                        <div class="comment-form-head">
                            <h4>Để lại bình luận</h4>
                            <span class="comment-form-privacy">Bảo mật thông tin 100%</span>
                        </div>
                        <form id="comment-form">
                            @csrf
                            @guest
                                <div class="comment-guest-row">
                                    <div class="comment-input-wrap">
                                        <input type="text" name="guest_name" placeholder="Họ và tên của bạn *" required>
                                    </div>
                                    <div class="comment-input-wrap">
                                        <input type="email" name="guest_email" placeholder="Email nhận phản hồi *" required>
                                    </div>
                                </div>
                            @endguest
                            <div class="reply-wrapper" id="reply-indicator" style="display:none;">
                                <span class="reply-indicator">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 14 4 9 9 4"></polyline><path d="M20 20v-7a4 4 0 0 0-4-4H4"></path></svg>
                                    Đang trả lời <strong id="reply-to-name"></strong>
                                    <button type="button" id="cancel-reply" title="Hủy trả lời">✕</button>
                                </span>
                            </div>
                            {{-- Rating Stars Selection --}}
                            <div class="comment-rating-group" id="comment-rating-group">
                                <div class="rating-stars-wrapper">
                                    <span class="rating-label">Đánh giá: <span style="color:#ef4444;">*</span></span>
                                    <div class="stars-picker" id="stars-picker" role="radiogroup" aria-label="Chọn số sao đánh giá">
                                        <input type="hidden" name="rating" id="comment-rating-input" value="">
                                        <button type="button" class="star-btn" data-rating="1" title="1 sao - Rất tệ" aria-label="1 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="star-btn" data-rating="2" title="2 sao - Tệ" aria-label="2 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="star-btn" data-rating="3" title="3 sao - Bình thường" aria-label="3 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="star-btn" data-rating="4" title="4 sao - Hài lòng" aria-label="4 sao"><i class="fa-solid fa-star"></i></button>
                                        <button type="button" class="star-btn" data-rating="5" title="5 sao - Tuyệt vời" aria-label="5 sao"><i class="fa-solid fa-star"></i></button>
                                    </div>
                                    <span class="rating-feedback" id="rating-feedback"></span>
                                </div>
                            </div>
                            <div class="comment-textarea-wrap">
                                <textarea name="content" placeholder="Chia sẻ suy nghĩ hoặc câu hỏi của bạn về bài viết..." required></textarea>
                            </div>
                            <input type="hidden" name="parent_id" id="comment-parent-id">
                            <input type="text" name="website" autocomplete="off" style="display:none;">
                            <div class="comment-form-actions">
                                <span class="comment-form-hint">Vui lòng bình luận văn minh, tôn trọng lẫn nhau.</span>
                                <button type="submit" class="comment-submit-btn">
                                    <span>Gửi bình luận</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                </button>
                            </div>
                            <div class="comment-status-message" id="comment-status-message"></div>
                        </form>
                    </div>
                </section>

                <div class="blog-editorial-note"
                    style="margin-top: 35px; padding: 18px 20px; background: #f8f8f8; border-left: 3px solid #222; font-size: 14px; line-height: 1.7; color: #555;">
                    <strong style="display: block; margin-bottom: 6px; color: #222;">Về nội dung bài viết</strong>
                    <p style="margin: 0;">
                        Nội dung trên Nobi Fashion được đội ngũ biên tập tổng hợp, tham khảo, đối chiếu và biên tập từ nhiều
                        nguồn thông tin khác nhau nhằm cung cấp kiến thức hữu ích cho người đọc. Nếu bạn phát hiện thông tin
                        chưa chính xác, đã lỗi thời hoặc cần được đính chính, vui lòng để lại bình luận hoặc liên hệ trực
                        tiếp với Nobi Fashion để chúng tôi kiểm tra và cập nhật.
                        <a href="{{ route('client.policy.editorial') }}"
                            style="color: #ff0000; font-weight: 600; text-decoration: underline;">Xem nguyên tắc biên tập của
                            Nobi Fashion</a>.
                    </p>
                </div>
            </article>

            {{-- RIGHT: SIDEBAR --}}
            <aside class="sidebar">

                {{-- Desktop TOC --}}
                @if ($toc->isNotEmpty())
                    <div class="sidebar-toc d-none d-lg-block">
                        <div class="sidebar-toc-title">Mục lục</div>
                        <ul class="toc-list" id="desktop-toc">
                            @foreach ($toc as $item)
                                <li class="{{ $item['tag'] === 'h3' ? 'indent' : '' }}">
                                    <a href="#{{ $item['id'] }}">{{ renderMeta($item['label']) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Related Posts Widget --}}
                @if ($relatedPosts->isNotEmpty())
                    <div class="sidebar-widget">
                        <h5 class="widget-title">Bài viết liên quan</h5>
                        <div>
                            @foreach ($relatedPosts as $related)
                                <a href="{{ route('client.blog.show', $related) }}" class="related-post-item">
                                    <img src="{{ $related->thumbnail ? asset('clients/assets/img/posts/' . $related->thumbnail) : asset('clients/assets/img/clothes/no-image.webp') }}"
                                        alt="{{ renderMeta($related->title) }}" class="related-thumb"
                                        onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/no-image.webp') }}'"
                                        loading="lazy">
                                    <div class="related-info">
                                        <h4>{{ renderMeta($related->title) }}</h4>
                                        <div class="related-date">
                                            <i class="far fa-calendar"></i>
                                            {{ optional($related->published_at)->format('d/m/Y') }}
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Newsletter Widget --}}
                <div class="sidebar-widget newsletter-widget">
                    <h5 class="widget-title">
                        📬 Newsletter
                    </h5>
                    <p class="newsletter-desc">
                        Đăng ký để nhận những bài viết mới nhất và xu hướng nổi bật hàng tuần.
                    </p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="newsletter-form"
                        data-newsletter-form id="newsletter-form-blog">
                        @csrf
                        <input type="email" name="email" id="newsletter-email-blog" placeholder="email@example.com"
                            required>
                        <button type="button" data-submit-newsletter id="newsletter-btn-blog"
                            onclick="handleNewsletterSubmit(event)">Đăng ký ngay</button>
                        <div class="newsletter-message small mt-2 text-muted" id="newsletter-message-blog"></div>
                    </form>
                </div>

            </aside>
        </div>
    </section>
@endsection

@section('foot')
    {{-- Newsletter Form Handler - Phải đặt trước để function được định nghĩa sớm --}}
    <script>
        // Function global để có thể gọi từ inline onclick
        function handleNewsletterSubmit(event) {
            event.preventDefault();
            event.stopPropagation();

            const form = document.getElementById('newsletter-form-blog');
            const btn = document.getElementById('newsletter-btn-blog');
            const msg = document.getElementById('newsletter-message-blog');
            const email = document.getElementById('newsletter-email-blog');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!form || !btn || !msg || !email) {
                console.error('Newsletter elements not found');
                return;
            }

            if (!csrfToken) {
                console.error('CSRF token not found');
                msg.textContent = "Lỗi: CSRF token không tìm thấy";
                msg.className = "newsletter-message small mt-2 text-danger";
                return;
            }

            // Validate email
            if (!email.value.trim()) {
                msg.textContent = "Vui lòng nhập email";
                msg.className = "newsletter-message small mt-2 text-danger";
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value.trim())) {
                msg.textContent = "Email không hợp lệ";
                msg.className = "newsletter-message small mt-2 text-danger";
                return;
            }

            btn.disabled = true;
            btn.textContent = "Đang gửi...";
            msg.textContent = "Đang gửi...";
            msg.className = "newsletter-message small mt-2 text-muted";

            const formData = new FormData(form);

            fetch(form.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json"
                    },
                    body: formData
                })
                .then(async res => {
                    try {
                        const data = await res.json();
                        return {
                            ok: res.ok,
                            data
                        };
                    } catch (e) {
                        return {
                            ok: false,
                            data: {
                                success: false,
                                message: 'Lỗi khi xử lý phản hồi từ server'
                            }
                        };
                    }
                })
                .then(({
                    ok,
                    data
                }) => {
                    if (ok && data.success) {
                        msg.textContent = data.message || "Đăng ký thành công!";
                        msg.className = "newsletter-message small mt-2 text-success";
                        form.reset();
                    } else {
                        let message = data.message || "Có lỗi xảy ra!";

                        // Xử lý validation errors
                        if (data.errors && data.errors.email) {
                            message = Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email;
                        }

                        msg.textContent = message;
                        msg.className = "newsletter-message small mt-2 text-danger";
                    }
                })
                .catch((e) => {
                    console.error('Newsletter error:', e);
                    msg.textContent = "Lỗi kết nối server";
                    msg.className = "newsletter-message small mt-2 text-danger";
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = "Đăng ký ngay";
                });
        }
    </script>

    <script>
        const initBlogShowPage = () => {
            // TOC Active State on Scroll
            const observerOptions = {
                rootMargin: '-100px 0px -66%',
                threshold: 0
            };

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    const id = entry.target.getAttribute('id');
                    const tocLink = document.querySelector(`#desktop-toc a[href="#${id}"]`);

                    if (tocLink) {
                        if (entry.isIntersecting) {
                            document.querySelectorAll('#desktop-toc a').forEach(link => {
                                link.classList.remove('active');
                            });
                            tocLink.classList.add('active');
                        }
                    }
                });
            }, observerOptions);

            // Observe all headings
            document.querySelectorAll('.rich-content h2, .rich-content h3').forEach(heading => {
                observer.observe(heading);
            });

            // Smooth scroll for TOC links
            document.querySelectorAll('.toc-list a, .mobile-toc a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        const offset = 100;
                        const targetPosition = targetElement.getBoundingClientRect().top + window
                            .pageYOffset - offset;

                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Lazy load images in content
            document.querySelectorAll('.rich-content img').forEach(img => {
                img.setAttribute('loading', 'lazy');
            });

            // Add external link icon
            document.querySelectorAll('.rich-content a[href^="http"]').forEach(link => {
                if (!link.hostname.includes(window.location.hostname)) {
                    link.setAttribute('target', '_blank');
                    link.setAttribute('rel', 'noopener noreferrer');
                }
            });

        };

        // Add onerror handler to all images in blog post
        document.addEventListener('DOMContentLoaded', function() {
            const fallbackImage = '{{ asset('clients/assets/img/clothes/no-image.webp') }}';

            // Function to handle image error
            function handleImageError(img) {
                if (img.src !== fallbackImage) {
                    img.onerror = null; // Prevent infinite loop
                    img.src = fallbackImage;
                }
            }

            // Add onerror to hero image
            const heroImage = document.querySelector('.hero-image-container img');
            if (heroImage) {
                heroImage.onerror = function() {
                    handleImageError(this);
                };
            }

            // Add onerror to related posts images
            document.querySelectorAll('.related-post-item img').forEach(img => {
                img.onerror = function() {
                    handleImageError(this);
                };
            });

            // Add onerror to all images in rich content
            document.querySelectorAll('.rich-content img').forEach(img => {
                img.onerror = function() {
                    handleImageError(this);
                };
            });

            // Add onerror to any other images in the article
            document.querySelectorAll('article img, .blog-post img').forEach(img => {
                if (!img.onerror) {
                    img.onerror = function() {
                        handleImageError(this);
                    };
                }
            });
        });
    </script>

    {{-- FontAwesome Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection
