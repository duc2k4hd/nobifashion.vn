<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@graph' => [
    [
      '@type' => 'Organization',
      '@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#organization',
      'name' => $settings->subname ?? 'Nobi Fashion',
      'url' => $settings->site_url ?? 'https://nobifashion.vn',
      'logo' => asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp')),
      'email' => $settings->contact_email ?? 'support@nobifashion.vn',
      'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $settings->contact_address ?? '123 Đường Thời Trang, Quận Kiến An',
        'addressLocality' => $settings->city ?? 'Hải Phòng',
        'addressRegion' => $settings->city ?? 'Hải Phòng',
        'postalCode' => $settings->postalCode ?? '180000',
        'addressCountry' => 'VN'
      ],
      'contactPoint' => [[
        '@type' => 'ContactPoint',
        'telephone' => $settings->contact_phone ?? '0382941465',
        'contactType' => 'customer service'
      ]],
      'sameAs' => array_values(array_filter([
        optional($settings)->facebook_link,
        optional($settings)->instagram_link,
        optional($settings)->discord_link,
      ]))
    ],
    [
      '@type' => 'WebSite',
      '@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#website',
      'url' => $settings->site_url ?? 'https://nobifashion.vn',
      'name' => $settings->subname ?? 'Nobi Fashion',
      'publisher' => ['@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#organization'],
      "alternateName" => "Nobi Fashion Việt Nam",
      "inLanguage" => "vi-VN",
      'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => [
          '@type' => 'EntryPoint',
          'urlTemplate' => ($settings->site_url ?? 'https://nobifashion.vn') . '/shop/search?keyword={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string'
      ]
    ],
    [
      '@type' => 'LocalBusiness',
      '@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#localbusiness',
      'name' => $settings->subname ?? 'Nobi Fashion',
      'logo' => [
        '@type' => 'ImageObject',
        'url' => asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp'))
      ],
      'image' => asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'no-image.webp')),
      'url' => $settings->site_url ?? 'https://nobifashion.vn',
      'telephone' => $settings->contact_phone ?? '0382941465',
      'priceRange' => '₫₫',
      'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $settings->contact_address ?? '123 Đường Thời Trang, Quận Kiến An',
        'addressLocality' => $settings->city ?? 'Hải Phòng',
        'addressRegion' => $settings->city ?? 'Hải Phòng',
        'postalCode' => $settings->postalCode ?? '180000',
        'addressCountry' => 'VN'
      ],
      'geo' => [
        '@type' => 'GeoCoordinates',
        'latitude' => $settings->latitude ?? '20.86481',
        'longitude' => $settings->longitude ?? '106.68345'
      ],
      'openingHoursSpecification' => [[
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
        'opens' => '08:00',
        'closes' => '17:30'
      ]],
      'sameAs' => array_values(array_filter([
        optional($settings)->facebook_link,
        optional($settings)->instagram_link,
        optional($settings)->discord_link,
      ]))
    ],
    [
      '@type' => 'WebPage',
      '@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#webpage',
      'url' => $settings->site_url ?? 'https://nobifashion.vn',
      'name' => $settings->subname ?? 'Nobi Fashion - Trang chủ',
      'description' => $settings->site_description ?? 'Nobi Fashion - Shop quần áo & phụ kiện',
      'inLanguage' => $settings->site_language ?? 'vi-VN',
      'isPartOf' => ['@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#website']
    ],
    [
      '@type' => 'BreadcrumbList',
      'itemListElement' => [[
        '@type' => 'ListItem',
        'position' => 1,
        'item' => [
          '@id' => ($settings->site_url ?? 'https://nobifashion.vn') . '#home',
          'name' => 'Trang chủ',
          'image' => asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'no-image.webp'))
        ]
      ]]
    ]
  ]
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) !!}
</script>
