<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}#organization",
      "name": "{{ renderMeta($settings->site_name ?? 'NOBI FASHION - Shop quần áo & phụ kiện thời trang chính hãng') }}",
      "url": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}",
      "logo": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}",
      "email": "{{ ($settings->contact_email ?? 'support@nobifashion.vn') }}",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ ($settings->contact_address ?? 'Số 123 Đường Thời Trang, Quận Trung Tâm') }}",
        "addressRegion": "{{ ($settings->city ?? 'Hải Phòng') }}",
        "postalCode": "{{ ($settings->postalCode ?? '180000') }}",
        "addressCountry": "{{ ($settings->site_language ?? 'vi') }}",
        "addressLocality": "{{ ($settings->addressLocality ?? 'Kiến An') }}"
      },
      "contactPoint": [
        {
          "@type": "ContactPoint",
          "telephone": "{{ ($settings->contact_phone ?? '0827 786 198') }}",
          "contactType": "customer service"
        }
      ],
      "sameAs": [
        "{{ ($settings->facebook_link ?? 'https://www.facebook.com/nobifashion.vn') }}",
        "{{ ($settings->instagram_link ?? 'https://www.instagram.com/nobifashion.vn') }}",
        "{{ ($settings->discord_link ?? 'https://discord.gg/nobifashion') }}"
      ]
    },
    {
      "@type": "WebPage",
      "@id": "{{ ($product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn')) }}#webpage",
      "url": "{{ ($product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn')) }}",
      "name": "{{ renderMeta($product->meta_title ?? ($product->name ?? 'NOBI FASHION - Mua sắm quần áo & phụ kiện thời trang nam nữ')) }}",
      "description": "{{ renderMeta($product->meta_desc ?? 'Cửa hàng thời trang NOBI FASHION: quần áo nam nữ, áo phông, sơ mi, quần jean, váy, túi xách, thắt lưng, mũ nón. Hàng mới, giá tốt, đổi trả 7 ngày.') }}",
      "inLanguage": "{{ ($settings->site_language ?? 'vi') }}",
      "isPartOf": {
        "@id": "{{ ($product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn')) }}#website"
      }
    },
    {
      "@type": "LocalBusiness",
      "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}#localbusiness",
      "name": "{{ renderMeta($settings->site_name ?? 'NOBI FASHION') }}",
      "logo": {
        "@type": "ImageObject",
        "url": "{{ asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')) }}"
      },
      "image": "{{ asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'no-image.webp')) }}",
      "url": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}",
      "telephone": "{{ ($settings->contact_phone ?? '0827 786 198') }}",
      "email": "{{ ($settings->contact_email ?? 'support@nobifashion.vn') }}",
      "priceRange": "₫₫",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ ($settings->contact_address ?? 'Số 123 Đường Thời Trang, Quận Trung Tâm') }}",
        "addressLocality": "{{ ($settings->city ?? 'Hải Phòng') }}",
        "addressRegion": "{{ ($settings->city ?? 'Hải Phòng') }}",
        "postalCode": "{{ ($settings->postalCode ?? '180000') }}",
        "addressCountry": "VN"
      },
      "geo": {
        "@type": "GeoCoordinates",
        "latitude": {{ ($settings->latitude ?? 20.86481) }},
        "longitude": {{ ($settings->longitude ?? 106.68345) }}
      },
      "openingHoursSpecification": [{
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        "opens": "08:00",
        "closes": "17:30"
      }],
      "sameAs": [
        "{{ ($settings->facebook_link ?? 'https://www.facebook.com/nobifashionvietnam') }}",
        "{{ ($settings->instagram_link ?? 'https://www.instagram.com/nobifashionvietnam') }}",
        "{{ ($settings->discord_link ?? 'https://discord.gg/nobifashion') }}"
      ]
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "item": {
            "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}",
            "name": "Trang chủ"
          }
        }
        @php
          $position = 2;
          $categoryBreadcrumb = $product?->primaryCategory?->first() ?? null;
          $breadcrumbPath = collect();
          while ($categoryBreadcrumb) {
            $breadcrumbPath->prepend($categoryBreadcrumb);
            $categoryBreadcrumb = $categoryBreadcrumb->parent ?? null;
          }
        @endphp
        @foreach ($breadcrumbPath as $breadcrumb)
        ,{
          "@type": "ListItem",
          "position": {{ $position }},
          "item": {
            "@id": "#",
            "name": "{{ $breadcrumb->name }}"
          }
        }
        @php $position++; @endphp
        @endforeach
        @if ($product->primaryCategory)
          @php $lastCategory = $product?->extraCategories()?->last(); @endphp
          @if ($lastCategory && !$breadcrumbPath->contains('id', $lastCategory->id))
          ,{
            "@type": "ListItem",
            "position": {{ $position }},
            "item": {
              "@id": "#",
              "name": "{{ $lastCategory->name }}"
            }
          }
          @php $position++; @endphp
          @endif
        @endif
        ,{
          "@type": "ListItem",
          "position": {{ $position }},
          "item": {
            "@id": "{{ ($product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn')) }}",
            "name": "{{ renderMeta($product->meta_title ?? ($product->name ?? 'Sản phẩm thời trang NOBI FASHION')) }}"
          }
        }
      ]
    },
    {
      "@type": "Product",
      "@id": "{{ ($product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn')) }}/#product",
      "name": "{{ renderMeta($product->meta_title ?? ($product->name ?? 'Sản phẩm thời trang chính hãng - NOBI FASHION')) }}",
      "image": {
        "@type": "ImageObject",
        "url": "{{ asset('clients/assets/img/clothers/' . ($product->primary_image->url ?? 'no-image.jpg')) }}",
        "width": 500,
        "height": 500
      },
      "description": "{{ renderMeta($product->meta_desc ?? 'Mẫu thời trang mới, chất liệu đẹp, form chuẩn, dễ phối đồ. Mua online tại NOBI FASHION, giao nhanh toàn quốc, hỗ trợ đổi size 7 ngày.') }}",
      "sku": "{{ ($product->sku ?? 'SKU-DEFAULT') }}",
      "mpn": "{{ ($product->sku ?? 'SKU-DEFAULT') }}",
      "productID": "sku:{{ ($product->sku ?? 'SKU-DEFAULT') }}",
      "brand": {
        "@type": "Brand",
        "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}#brand-{{ ($product->brand->slug ?? 'nobi-fashion') }}",
        "name": "{{ renderMeta($product->brand->name ?? 'NOBI FASHION') }}"
      },
      "manufacturer": {
        "@type": "Organization",
        "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}#manufacturer-{{ ($product->brand->slug ?? 'nobi-fashion') }}",
        "name": "{{ renderMeta($product->brand->name ?? 'NOBI FASHION') }}"
      },
      "countryOfOrigin": "{{ ($product->countryOfOrigin ?? 'VN') }}",
      "isFamilyFriendly": true,
      "keywords": {!! json_encode($product->meta_keywords ?? [
          'quần áo', 'phụ kiện', 'thời trang nam', 'thời trang nữ',
          'áo phông', 'sơ mi', 'quần jean', 'váy', 'túi xách', 'mũ nón', 'thắt lưng'
      ]) !!},
      "releaseDate": "{{ (($product->created_at ?? null) ? $product->created_at->format('Y-m-d') : now()->format('Y-m-d')) }}",
      "audience": {
        "@type": "PeopleAudience",
        "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}#audience-{{ ($product->brand->slug ?? 'nobi-fashion') }}",
        "audienceType": "Người tiêu dùng yêu thời trang"
      },
      "offers": {
        "@type": "Offer",
        "url": "{{ ($product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn')) }}",
        "priceCurrency": "VND",
        "price": "{{ ($product->price ?? 199000) }}",
        "priceValidUntil": "{{ (\Carbon\Carbon::now()->addMonths(6)->format('Y-m-d')) }}",
        "availability": "{{ ($product->in_stock ?? true) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
        "itemCondition": "https://schema.org/NewCondition",
        "seller": {
          "@type": "Organization",
          "@id": "{{ ($settings->site_url ?? 'https://nobifashion.vn') }}#organization",
          "name": "{{ renderMeta($settings->site_name ?? 'NOBI FASHION') }}"
        },
        "shippingDetails": {
          "@type": "OfferShippingDetails",
          "shippingDestination": { "@type": "DefinedRegion", "addressCountry": "VN" },
          "shippingRate": { "@type": "MonetaryAmount", "value": "{{ ($product->shipping_fee ?? 30000) }}", "currency": "VND" },
          "deliveryTime": {
            "@type": "ShippingDeliveryTime",
            "handlingTime": { "@type": "QuantitativeValue", "minValue": 1, "maxValue": 2, "unitCode": "DAY" },
            "transitTime": { "@type": "QuantitativeValue", "minValue": 1, "maxValue": 3, "unitCode": "DAY" }
          }
        },
        "hasMerchantReturnPolicy": {
          "@type": "MerchantReturnPolicy",
          "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
          "merchantReturnDays": {{ ($settings->return_days ?? 7) }},
          "returnMethod": "https://schema.org/ReturnByMail",
          "returnFees": "https://schema.org/FreeReturn",
          "refundType": "https://schema.org/FullRefund",
          "applicableCountry": "VN",
          "merchantReturnLink": "{{ ($settings->return_link ?? 'https://nobifashion.vn/chinh-sach-doi-tra') }}"
        }
      }
    },
    {
      "@type": "FAQPage",
      "name": "Câu hỏi thường gặp về {{ renderMeta($product->name ?? 'sản phẩm thời trang NOBI FASHION') }}",
      "mainEntity": [
        @if ($product->faqs && $product->faqs->count())
          @foreach ($product->faqs as $faq)
          {
            "@type": "Question",
            "name": "{{ ($faq->question ?? 'Sản phẩm có bền màu, form chuẩn không?') }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ renderMeta($faq->answer ?? 'NOBI FASHION cam kết chất liệu đẹp, form chuẩn, hỗ trợ đổi size 7 ngày.') }}" }
          }{{ !$loop->last ? ',' : '' }}
          @endforeach
        @else
          {
            "@type": "Question",
            "name": "Sản phẩm này chất lượng thế nào vậy shop?",
            "acceptedAnswer": { "@type": "Answer", "text": "Hàng mới chính hãng, chất liệu đẹp, đường may tỉ mỉ, bảo hành đổi size 7 ngày." }
          },
          {
            "@type": "Question",
            "name": "Chất liệu vải của sản phẩm là gì?",
            "acceptedAnswer": { "@type": "Answer", "text": "Tùy mẫu: cotton, poly, kate, thun lạnh... Thông tin chất liệu luôn ghi rõ ở mô tả." }
          },
          {
            "@type": "Question",
            "name": "Size có vừa không nếu đặt online?",
            "acceptedAnswer": { "@type": "Answer", "text": "Có bảng size theo chiều cao/cân nặng. Không vừa hỗ trợ đổi size trong 7 ngày." }
          },
          {
            "@type": "Question",
            "name": "Thời gian giao hàng?",
            "acceptedAnswer": { "@type": "Answer", "text": "Toàn quốc 1–3 ngày làm việc; nội thành nhận nhanh hơn." }
          },
          {
            "@type": "Question",
            "name": "Có đồng kiểm trước khi thanh toán không?",
            "acceptedAnswer": { "@type": "Answer", "text": "Có, hỗ trợ đồng kiểm trước khi thanh toán." }
          }
        @endif
      ]
    }
    @if (optional($product->howtos->first())->steps)
    ,{
      "@type": "HowTo",
      "name": "{{ ($product->howtos->first()->title ?? 'Cách bảo quản và phối đồ đẹp') }}",
      "description": "{{ ($product->howtos->first()->description ?? 'Hướng dẫn phối đồ nhanh gọn, giữ form áo/quần bền đẹp khi giặt sấy.') }}",
      "image": "{{ asset('clients/assets/img/clothers/' . ($product->primary_image->url ?? 'no-image.jpg')) }}",
      "totalTime": "PT15M",
      "estimatedCost": { "@type": "MonetaryAmount", "currency": "VND", "value": "10000" },
      @php
        $howto = data_get($product, 'howtos.0');
        $supplies = collect(data_get($howto, 'supplies', []))->filter()->values();
        $steps    = collect(data_get($howto, 'steps', []))->filter()->values();
      @endphp
      @if($supplies->isNotEmpty())
      "supply": [
        @foreach($supplies as $supply)
          {!! json_encode(['@type'=>'HowToSupply','name'=>is_array($supply)?($supply['name']??'Phụ kiện cơ bản'):($supply ?? 'Phụ kiện cơ bản')], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}{{ !$loop->last ? ',' : '' }}
        @endforeach
      ]@if($steps->isNotEmpty()),@endif
      @endif
      @if($steps->isNotEmpty())
      "step": [
        @foreach($steps as $step)
          {!! json_encode([
            '@type'=>'HowToStep',
            'name'=>is_array($step)?($step['name']??'Bước'):($step ?? 'Bước'),
            'text'=>is_array($step)?($step['text']??'Thực hiện theo hướng dẫn.'):'Thực hiện theo hướng dẫn.'
          ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}{{ !$loop->last ? ',' : '' }}
        @endforeach
      ]
      @endif
    }
    @endif
  ]
}
</script>