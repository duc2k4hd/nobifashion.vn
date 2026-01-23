<!-- 🌐 SCHEMA CHUẨN TRANG DANH MỤC -->
<script type="application/ld+json">
    {
"@context": "https://schema.org",
"@graph": [
  {
    "@type": "Organization",
    "@id": "{{ $settings->site_url }}#organization",
    "name": "{{ renderMeta($settings->site_name) }}",
    "image": "{{ asset('clients/assets/img/banners/' . $settings->site_banner) }}",
    "url": "{{ $settings->site_url }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/business/' . $settings->site_logo) }}",
      "width": 600,
      "height": 200
    },
    "email": "{{ $settings->contact_email }}",
    "telephone": "{{ $settings->contact_phone }}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ $settings->contact_address }}",
      "addressLocality": "{{ $settings->city }}",
      "addressRegion": "{{ $settings->city }}",
      "postalCode": "{{ $settings->postalCode }}",
      "addressCountry": "VN"
    },
    "contactPoint": [{
      "@type": "ContactPoint",
      "telephone": "{{ $settings->contact_phone }}",
      "contactType": "customer service",
      "availableLanguage": ["Vietnamese"],
      "areaServed": "VN"
    }],
    "sameAs": [
      "{{ $settings->facebook_link ?? 'https://www.facebook.com/ducnobi2004' }}",
      "{{ $settings->instagram_link ?? 'https://www.facebook.com/ducnobi2004' }}",
      "{{ $settings->discord_link ?? 'https://www.facebook.com/ducnobi2004' }}"
    ],
    "makesOffer": {
      "@type": "OfferCatalog",
      "name": "Sản phẩm của {{ $settings->site_name }}",
      "itemListElement": {
        "@type": "Offer",
        "itemOffered": {
          "@type": "Thing",
          "name": "Các sản phẩm thời trang nam nữ",
          "url": "{{ $settings->site_url }}/shop"
        }
      }
    }
  },
  {
    "@type": "WebSite",
    "@id": "{{ $settings->site_url }}#website",
    "url": "{{ $settings->site_url }}",
    "name": "{{ renderMeta($settings->site_name) }}",
    "publisher": { "@id": "{{ $settings->site_url }}#organization" },
    "potentialAction": {
      "@type": "SearchAction",
      "target": "{{ $settings->site_url }}/tim-kiem/{search_term_string}",
      "query-input": "required name=search_term_string"
    }
  },
  {
    "@type": "WebPage",
    "@id": "{{ url()->current() }}#webpage",
    "url": "{{ url()->current() }}",
    "name": "{{ renderMeta($category->name ?? $settings->site_name) }}",
    "inLanguage": "vi",
    "isPartOf": { "@id": "{{ $settings->site_url }}#website" },
    "about": { "@id": "{{ $settings->site_url }}#organization" },
    "breadcrumb": { "@id": "{{ $settings->site_url }}#breadcrumb" },
    "primaryImageOfPage": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/business/' . $settings->site_logo) }}"
    },
    "datePublished": "{{ now()->toDateString() }}",
    "dateModified": "{{ now()->toDateString() }}"
  },
  {
    "@type": "LocalBusiness",
    "@id": "{{ $settings->site_url }}#localbusiness",
    "name": "{{ renderMeta($settings->site_name) }}",
    "image": "{{ asset('clients/assets/img/banners/' . $settings->site_banner) }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ asset('clients/assets/img/business/' . $settings->site_logo) }}"
    },
    "url": "{{ $settings->site_url }}",
    "telephone": "{{ $settings->contact_phone }}",
    "email": "{{ $settings->contact_email }}",
    "priceRange": "₫₫",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ $settings->contact_address }}",
      "addressLocality": "{{ $settings->city }}",
      "addressRegion": "{{ $settings->city }}",
      "postalCode": "{{ $settings->postalCode }}",
      "addressCountry": "VN"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": "{{ $settings->latitude ?? 20.86481 }}",
      "longitude": "{{ $settings->longitude ?? 106.68345 }}"
    },
    "openingHoursSpecification": [{
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
      "opens": "08:00",
      "closes": "17:30"
    }],
    "sameAs": [
      "{{ $settings->facebook_link ?? 'https://www.facebook.com/ducnobi2004' }}",
      "{{ $settings->instagram_link ?? 'https://www.facebook.com/ducnobi2004' }}",
      "{{ $settings->discord_link ?? 'https://www.facebook.com/ducnobi2004' }}"
    ]
  },
  {
    "@type": "BreadcrumbList",
    "@id": "{{ $settings->site_url }}#breadcrumb",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "item": {
          "@id": "{{ $settings->site_url }}",
          "name": "Trang chủ"
        }
      },
      {
        "@type": "ListItem",
        "position": 2,
        "item": {
          "@id": "{{ $settings->site_url }}/shop",
          "name": "Cửa hàng"
        }
      }
      @if(!empty($category))
      ,{
        "@type": "ListItem",
        "position": 3,
        "item": {
          "@id": "{{ url()->current() }}",
          "name": "{{ $category->name }}"
        }
      }
      @endif
    ]
  },
  {
    "@type": "ItemList",
    "@id": "{{ url()->current() }}#itemlist",
    "url": "{{ url()->current() }}",
    "name": "Danh sách sản phẩm {{ $category->name ?? '' }}",
    "itemListOrder": "https://schema.org/ItemListOrderDescending",
    "numberOfItems": {{ $products->count() }},
    "itemListElement": [
      @foreach($products as $index => $product)
      {
        "@type": "ListItem",
        "position": {{ $loop->iteration }},
        "url": "{{ $product->canonical_url }}",
        "name": "{{ renderMeta($product->name) }}"
      }@if(!$loop->last),@endif
      @endforeach
    ]
  }
]
}
</script>
<!-- 🌐 END SCHEMA CHUẨN TRANG DANH MỤC -->
