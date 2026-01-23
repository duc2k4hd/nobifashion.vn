@extends('clients.layouts.master')


@section('head')
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }

        .deal-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            animation: pulse 2s infinite;
        }

        .countdown {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .discount-ribbon {
            position: absolute;
            top: 0;
            left: -5px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .discount-ribbon::before {
            content: attr(data-discount);
            position: absolute;
            width: 150%;
            height: 15px;
            background: #ef4444;
            transform: rotate(-45deg) translateY(-10px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 10px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
    <title>Săn khuyến mãi HOT | Giảm giá sốc, ưu đãi lớn trong ngày -
        {{ renderMeta(data_get($settings ?? [], 'site_name', config('app.name'))) }}
    </title>
    <link rel="alternate" hreflang="vi" href="{{ url('/deals') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/deals') }}">
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Khuyến mãi HOT | Ưu đãi lớn, mã giảm giá, sản phẩm sale sốc hôm nay",
          "description": "Khám phá các chương trình khuyến mãi hot, mã giảm giá, sản phẩm sale sốc, ưu đãi lớn nhất hôm nay tại {{ renderMeta(data_get($settings ?? [], 'site_name', config('app.name'))) }}.",
      "url": "{{ url('/deals-hot') }}",
      "breadcrumb": {
        "@type": "BreadcrumbList",
        "itemListElement": [
          {
            "@type": "ListItem",
            "position": 1,
            "name": "Trang chủ",
            "item": "{{ url('/') }}"
          },
          {
            "@type": "ListItem",
            "position": 2,
            "name": "Khuyến mãi hot",
              "item": "{{ url('/deals') }}"
          }
        ]
      },
      "publisher": {
        "@type": "Organization",
            "name": "{{ renderMeta(data_get($settings ?? [], 'site_name', config('app.name'))) }}",
        "url": "{{ url('/') }}",
        "logo": {
          "@type": "ImageObject",
              "url": "{{ asset('/clients/assets/img/business/' . (data_get($settings ?? [], 'site_logo', 'logo-nobi-fashion.png'))) }}"
        },
            "email": "{{ data_get($settings ?? [], 'contact_email', '') }}",
            "telephone": "{{ data_get($settings ?? [], 'contact_phone', '') }}",
        "address": {
          "@type": "PostalAddress",
              "streetAddress": "{{ data_get($settings ?? [], 'contact_address', '') }}",
          "addressCountry": "VN"
        },
        "sameAs": [
              @if(data_get($settings ?? [], 'facebook_link')) "{{ data_get($settings ?? [], 'facebook_link') }}", @endif
              @if(data_get($settings ?? [], 'instagram_link')) "{{ data_get($settings ?? [], 'instagram_link') }}", @endif
              @if(data_get($settings ?? [], 'twitter_link')) "{{ data_get($settings ?? [], 'twitter_link') }}" @endif
        ]
      }
    }
    </script>
    @if ($items && $items->count() > 0)
        @foreach ($items as $product)
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Product",
          "name": "{{ renderMeta($product->name ?? '') }}",
          "image": "{{ $product->primaryImage ? asset('/clients/assets/img/clothers/' . $product->primaryImage->url) : '' }}",
          "description": "{{ renderMeta($product->meta_desc ?? $product->name ?? '') }}",
          "sku": "{{ $product->sku ?? $product->id ?? 0 }}",
          "brand": {
            "@type": "Brand",
                        "name": "{{ renderMeta(data_get($settings ?? [], 'site_name', config('app.name'))) }}"
          },
          "offers": {
            "@type": "Offer",
            "priceCurrency": "VND",
            "price": "{{ $product->price ?? 0 }}",
            "availability": "https://schema.org/InStock",
            "url": "{{ url('/san-pham/' . $product->slug ?? '') }}"
          }
        }
        </script>
      @endforeach
    @endif
    <style>
        .deals_hero {
            position: relative;
            margin: 16px auto;
            max-width: 1200px;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(120deg, #ff416c 0%, #ff4b2b 100%);
            color: #fff;
            padding: 28px
        }

        .deals_hero_inner {
            display: flex;
            gap: 18px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap
        }

        .deals_hero_title {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: .5px;
            margin: 0
        }

        .deals_countdown {
            display: flex;
            gap: 10px;
            align-items: center
        }

        .deals_cd_box {
            background: #fff;
            color: #d70040;
            border-radius: 10px;
            min-width: 56px;
            text-align: center;
            padding: 6px 8px
        }

        .deals_cd_num {
            font-size: 20px;
            font-weight: 800;
            line-height: 1
        }

        .deals_cd_label {
            font-size: 11px;
            opacity: .75
        }

        .deals_grid {
            max-width: 1200px;
            margin: 18px auto;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px
        }

        @media (max-width:1024px) {
            .deals_grid {
                grid-template-columns: repeat(3, 1fr)
            }
        }

        @media (max-width:680px) {
            .deals_grid {
                grid-template-columns: repeat(2, 1fr)
            }

            .deals_hero_title {
                font-size: 22px
            }
        }

        .deals_empty {
            max-width: 800px;
            margin: 24px auto;
            text-align: center;
            background: #fff;
            border: 1px dashed #ffd1db;
            border-radius: 14px;
            padding: 28px
        }

        .deals_empty h3 {
            margin: 6px 0 4px 0;
            color: #ff416c
        }

        .deals_btn {
            display: inline-block;
            background: #fff;
            color: #ff416c;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 700;
            border: none
        }

        .deals_section_title {
            max-width: 1200px;
            margin: 8px auto 0 auto;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #d70040
        }

        .deals_section_title svg {
            width: 22px;
            height: 22px
        }

        /* Card grid modern */
        .deals_grid {
            max-width: 1200px;
            margin: 18px auto;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px
        }

        @media (max-width:1200px) {
            .deals_grid {
                grid-template-columns: repeat(4, 1fr)
            }
        }

        @media (max-width:992px) {
            .deals_grid {
                grid-template-columns: repeat(3, 1fr)
            }
        }

        @media (max-width:680px) {
            .deals_grid {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        .deal-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
            display: flex;
            flex-direction: column
        }

        .deal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .1)
        }

        .deal-thumb {
            display: block;
            position: relative;
            aspect-ratio: 1/1;
            background: #fafafa
        }

        .deal-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block
        }

        .deal-ribbon {
            position: absolute;
            background: #ff2e63;
            color: #fff;
            font-weight: 800;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 8px 0 0 0;
            box-shadow: 0 4px 10px rgba(255, 46, 99, .3);
            z-index: 999;
        }

        .deal-info {
            padding: 12px
        }

        .deal-title {
            display: block;
            font-weight: 700;
            color: #222;
            line-height: 1.3;
            margin-bottom: 8px;
            text-decoration: none
        }

        .deal-title:hover {
            color: #ff416c
        }

        .deal-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px
        }

        .deal-stars {
            position: relative;
            width: 80px;
            height: 14px;
            background: linear-gradient(90deg, #eee 50%, #eee 50%);
            mask: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%270 0 110 20%27><defs><path id=%22s%22 d=%27M10 0l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z%27/></defs><use href=%22#s%22 x=%220%22/><use href=%22#s%22 x=%2222%22/><use href=%22#s%22 x=%2244%22/><use href=%22#s%22 x=%2266%22/><use href=%22#s%22 x=%2288%22/></svg>') center/contain no-repeat
        }

        .deal-stars-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 14px;
            background: #ffc400
        }

        .deal-rating {
            font-size: 12px;
            color: #777
        }

        .deal-price-row {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: space-between
        }

        .deal-price {
            color: #e60039;
            font-weight: 800;
            font-size: 18px
        }

        .deal-old {
            color: #999;
            text-decoration: line-through;
            font-size: 12px
        }

        .deal-cart {
            margin-left: auto;
            background: #fff0f3;
            color: #ff2e63;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ffd1db
        }

        .deal-cart:hover {
            background: #ffe6eb
        }

        .deal-progress {
            height: 6px;
            background: #f3f4f6;
            border-radius: 99px;
            margin-top: 10px;
            overflow: hidden
        }

        .deal-progress span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #ff416c, #ff7b2f)
        }

        .deal-sold {
            margin-top: 6px;
            color: #888;
            font-size: 12px
        }

        /* Pagination */
        .deals_pagination {
            max-width: 1200px;
            margin: 18px auto
        }

        .deals_pagination nav {
            display: flex;
            justify-content: center
        }

        .pagination {
            display: flex;
            gap: 8px
        }

        .page-item .page-link {
            border: none;
            background: #fff;
            color: #ff416c;
            font-weight: 700;
            border-radius: 10px;
            padding: 8px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06)
        }

        .page-item .page-link:hover {
            background: #fff0f3;
            color: #ff2e63
        }

        .page-item.active .page-link {
            background: #ff416c;
            color: #fff;
            box-shadow: 0 4px 12px rgba(255, 65, 108, .35)
        }

        .page-item.disabled .page-link {
            background: #f3f4f6;
            color: #bbb
        }
    </style>
@endsection

@section('title')
    Săn khuyến mãi HOT | Giảm giá sốc, ưu đãi lớn trong ngày -
    {{ renderMeta(data_get($settings ?? [], 'site_name', config('app.name'))) }}
@endsection

@section('content')
    @php
        $normalizeTs = function ($v) {
            if ($v instanceof \Illuminate\Support\Carbon) {
                return $v->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))->timestamp;
            }
            if (is_numeric($v)) {
                $n = (int) $v;
                return $n > 2147483647 ? (int) floor($n / 1000) : $n;
            }
            if (!empty($v)) {
                return \Illuminate\Support\Carbon::parse($v, config('app.timezone', 'Asia/Ho_Chi_Minh'))->timestamp;
            }
            return null;
        };

        $end = null;
        if (!empty($flashSale)) {
            $endRaw = $flashSale->end_time
                ?? ($flashSale->ends_at ?? ($flashSale->endAt ?? null));
            $end = $normalizeTs($endRaw);
        }
    @endphp

    @if ($flashSale)
        <section class="deals_hero">
            <div class="deals_hero_inner">
                <h1 class="deals_hero_title">DEALS HOT – Flash Sale đang diễn ra</h1>
                <div class="deals_countdown" data-end="{{ $end ?? now()->addHours(6)->timestamp }}">
                    <div class="deals_cd_box">
                        <div class="deals_cd_num" id="cd-days">00</div>
                        <div class="deals_cd_label">Ngày</div>
                            </div>
                    <div class="deals_cd_box">
                        <div class="deals_cd_num" id="cd-hours">00</div>
                        <div class="deals_cd_label">Giờ</div>
                            </div>
                    <div class="deals_cd_box">
                        <div class="deals_cd_num" id="cd-minutes">00</div>
                        <div class="deals_cd_label">Phút</div>
                    </div>
                    <div class="deals_cd_box">
                        <div class="deals_cd_num" id="cd-seconds">00</div>
                        <div class="deals_cd_label">Giây</div>
                    </div>
                </div>
            </div>
            <div
                style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 160px at 70% 0%, rgba(255,255,255,.15), transparent 60%)">
        </div>
            <div
                style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 160px at 20% 100%, rgba(255,255,255,.08), transparent 60%)">
            </div>
            <script>
                (function () {
                    const wrap = document.querySelector('.deals_countdown');
                    if (!wrap) return;
                    const end = parseInt(wrap.getAttribute('data-end')) * 1000;

                    function t() {
                        const now = Date.now();
                        let d = Math.max(0, Math.floor((end - now) / 1000));
                        const dd = Math.floor(d / 86400);
                        d %= 86400;
                        const hh = Math.floor(d / 3600);
                        d %= 3600;
                        const mm = Math.floor(d / 60);
                        const ss = d % 60;
                        const z = n => ('' + n).padStart(2, '0');
                        document.getElementById('cd-days').textContent = z(dd);
                        document.getElementById('cd-hours').textContent = z(hh);
                        document.getElementById('cd-minutes').textContent = z(mm);
                        document.getElementById('cd-seconds').textContent = z(ss);
                    }
                    t();
                    setInterval(t, 1000);
                })();
            </script>
    </section>
    @else
        <section class="deals_hero" style="background:linear-gradient(120deg,#94a3b8,#475569)">
            <div class="deals_hero_inner">
                <div>
                    <h1 class="deals_hero_title">Chưa có chương trình Flash Sale</h1>
                    <p style="margin:8px 0 0;font-weight:500;">Vui lòng quay lại sau hoặc xem các sản phẩm nổi bật khác.</p>
                </div>
            </div>
        </section>
    @endif

    <div class="deals_section_title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
            <path fill="#ffcc00"
                d="M256 32l58.7 133.7L456 182.3 336 288l28.7 160L256 382.6 147.3 448 176 288 56 182.3l141.3-16.6L256 32z" />
        </svg>
        <h2 style="margin:0;font-weight:800;">Săn Deal Giá Sốc</h2>
        <span style="color:#999;font-size:13px;">(giảm sâu – số lượng có hạn)</span>
                </div>

    @if (isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->count())
        <div class="deals_grid">
            @foreach ($items as $product)
                            @include('clients.pages.deals.product_box', ['product' => $product])
            @endforeach
        </div>
        <div class="deals_pagination">
            {{ $items->appends(request()->query())->links('pagination::bootstrap-4') }}
        </div>
                @else
        <div class="deals_empty">
            <img src="{{ asset('clients/assets/img/other/no-flash-sale.jpg') }}" alt="No deals"
                onerror="this.style.display='none'" style="width:120px;height:120px;object-fit:contain;opacity:.9">
            <h3>Hiện chưa có chương trình Flash Sale</h3>
            <p>Hãy quay lại sau hoặc khám phá các sản phẩm đang được yêu thích.</p>
            <a href="{{ route('client.home.index') }}" class="deals_btn">Về trang chủ</a>
        </div>
                        @endif
@endsection