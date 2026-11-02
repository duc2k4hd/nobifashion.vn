@php
    $siteUrl = rtrim($settings->site_url ?? config('app.url') ?? 'https://nobifashion.vn', '/');
    if (!preg_match('~^https?://~', $siteUrl)) {
        $siteUrl = 'https://' . preg_replace('~^https?:?/?/?~', '', $siteUrl);
    }
    $productUrl = $product->canonical_url ?? ($siteUrl . '/san-pham/' . ($product->slug ?? ''));
    $logoUrl = asset('clients/assets/img/business/' . ($settings->site_logo ?? 'no-image.webp'));
    $bannerUrl = asset('clients/assets/img/banners/' . ($settings->site_banner ?? 'no-image.webp'));
    $productImage = asset('clients/assets/img/clothes/' . ($product->primaryImage->url ?? 'no-image.jpg'));

    // Breadcrumbs chuẩn hóa: loại bỏ placeholder '#'
    $breadcrumbItems = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'item' => [
                '@id' => $siteUrl,
                'name' => 'Trang chủ',
            ],
        ],
    ];

    $pos = 2;
    if (!empty($breadcrumbPath)) {
        foreach ($breadcrumbPath as $breadcrumb) {
            $catSlug = $breadcrumb->slug ?? null;
            $catUrl = $catSlug ? url('/' . $catSlug) : $productUrl;
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'item' => [
                    '@id' => $catUrl,
                    'name' => $breadcrumb->name ?? 'Danh mục',
                ],
            ];
        }
    }

    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => $pos,
        'item' => [
            '@id' => $productUrl,
            'name' => renderMeta($product->meta_title ?? ($product->name ?? 'Sản phẩm thời trang Nobi Fashion')),
        ],
    ];

    // FAQ Entities (Chỉ lấy khi thực sự có trong DB, nếu không có thì bỏ hẳn không tạo câu hỏi ảo)
    $faqItems = [];
    if (!empty($product->faqs) && $product->faqs->count() > 0) {
        foreach ($product->faqs as $faq) {
            $faqItems[] = [
                '@type' => 'Question',
                'name' => $faq->question ?? 'Sản phẩm có bền màu, form chuẩn không?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => renderMeta($faq->answer ?? 'Nobi Fashion cam kết chất liệu đẹp, form chuẩn, hỗ trợ đổi size 15 ngày.'),
                ],
            ];
        }
    }

    // 6. Xây dựng Product Schema (chỉ kèm đánh giá nếu có review thật từ DB)
    $productSchema = [
        '@type' => 'Product',
        '@id' => $productUrl . '#product',
        'name' => renderMeta($product->meta_title ?? ($product->name ?? 'Sản phẩm thời trang chính hãng | ' . $settings->subname)),
        'image' => [
            '@type' => 'ImageObject',
            'url' => $productImage,
            'width' => 500,
            'height' => 500,
        ],
        'description' => renderMeta($product->meta_desc ?? 'Mẫu thời trang mới, chất liệu đẹp, form chuẩn, dễ phối đồ. Mua online tại Nobi Fashion, giao nhanh toàn quốc, hỗ trợ đổi size 15 ngày.'),
        'sku' => $product->sku ?? ('SKU-' . $product->id),
        'mpn' => $product->sku ?? ('MPN-' . $product->id),
        'productID' => 'sku:' . ($product->sku ?? ('SKU-' . $product->id)),
        'brand' => [
            '@type' => 'Brand',
            '@id' => $siteUrl . '#brand-' . ($product->brand->slug ?? 'nobi-fashion'),
            'name' => renderMeta($product->brand->name ?? 'Nobi Fashion'),
        ],
        'manufacturer' => [
            '@type' => 'Organization',
            '@id' => $siteUrl . '#manufacturer-' . ($product->brand->slug ?? 'nobi-fashion'),
            'name' => renderMeta($product->brand->name ?? 'Nobi Fashion'),
        ],
        'countryOfOrigin' => $product->countryOfOrigin ?? 'VN',
        'isFamilyFriendly' => true,
        'keywords' => $product->meta_keywords ?? [
            'quần áo', 'phụ kiện', 'thời trang nam', 'thời trang nữ',
            'áo phông', 'sơ mi', 'quần jean', 'váy', 'túi xách', 'mũ nón', 'thắt lưng',
        ],
        'releaseDate' => (!empty($product->created_at) ? $product->created_at->format('Y-m-d') : now()->format('Y-m-d')),
        'audience' => [
            '@type' => 'PeopleAudience',
            '@id' => $siteUrl . '#audience-' . ($product->brand->slug ?? 'nobi-fashion'),
            'audienceType' => 'Người tiêu dùng yêu thời trang',
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => $productUrl,
            'priceCurrency' => 'VND',
            'price' => (float) ($product->sale_price ?? ($product->price ?? 199000)),
            'priceValidUntil' => now()->addMonths(6)->format('Y-m-d'),
            'availability' => ($product->in_stock ?? true) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => [
                '@type' => 'Organization',
                '@id' => $siteUrl . '#organization',
                'name' => renderMeta($settings->subname ?? 'Nobi Fashion'),
            ],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingDestination' => [
                    '@type' => 'DefinedRegion',
                    'addressCountry' => 'VN',
                ],
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => (float) ($product->shipping_fee ?? 30000),
                    'currency' => 'VND',
                ],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 1,
                        'maxValue' => 2,
                        'unitCode' => 'DAY',
                    ],
                    'transitTime' => [
                        '@type' => 'QuantitativeValue',
                        'minValue' => 1,
                        'maxValue' => 3,
                        'unitCode' => 'DAY',
                    ],
                ],
            ],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => (int) ($settings->return_days ?? 15),
                'returnMethod' => 'https://schema.org/ReturnByMail',
                'returnFees' => 'https://schema.org/FreeReturn',
                'refundType' => 'https://schema.org/FullRefund',
                'applicableCountry' => 'VN',
                'merchantReturnLink' => $siteUrl . '/chinh-sach-doi-tra',
            ],
        ],
    ];

    // CHỈ XUẤT ĐÁNH GIÁ KHI CÓ DỮ LIỆU THẬT TỪ DATABASE - NẾU KHÔNG CÓ THÌ BỎ HẲN CHO ĐỠ RÁC SCHEMA
    $hasRealReviews = !empty($product->productReviews) && $product->productReviews->count() > 0;
    if ($hasRealReviews) {
        $avgScore = round($product->productReviews->avg('rating') ?: 5, 1);
        $reviewTotal = $product->productReviews->count();
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $avgScore,
            'reviewCount' => $reviewTotal,
            'bestRating' => 5,
            'worstRating' => 1,
        ];
        $productSchema['review'] = $product->productReviews->take(5)->map(function ($rev) {
            return [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $rev->rating ?? 5,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $rev->account->name ?? 'Khách hàng',
                ],
                'reviewBody' => $rev->comment ?? 'Sản phẩm đẹp, chất lượng tốt.',
                'datePublished' => !empty($rev->created) ? \Carbon\Carbon::parse($rev->created)->format('Y-m-d') : now()->format('Y-m-d'),
            ];
        })->values()->all();
    }

    $schemaGraph = [
        '@context' => 'https://schema.org',
        '@graph' => [
            // 1. Organization
            [
                '@type' => 'Organization',
                '@id' => $siteUrl . '#organization',
                'name' => renderMeta($settings->site_name ?? 'Nobi Fashion - Shop quần áo & phụ kiện thời trang chính hãng'),
                'url' => $siteUrl,
                'logo' => $logoUrl,
                'email' => $settings->contact_email ?? 'support@nobifashion.vn',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $settings->contact_address ?? 'Số 123 Đường Thời Trang, Quận Trung Tâm',
                    'addressRegion' => $settings->city ?? 'Hải Phòng',
                    'postalCode' => $settings->postalCode ?? '180000',
                    'addressCountry' => 'VN',
                    'addressLocality' => $settings->addressLocality ?? 'Kiến An',
                ],
                'contactPoint' => [
                    [
                        '@type' => 'ContactPoint',
                        'telephone' => $settings->contact_phone ?? '0827 786 198',
                        'contactType' => 'customer service',
                    ],
                ],
                'sameAs' => array_values(array_filter([
                    $settings->facebook_link ?? 'https://www.facebook.com/nobifashion.vn',
                    $settings->instagram_link ?? 'https://www.instagram.com/nobifashion.vn',
                    $settings->discord_link ?? 'https://discord.gg/nobifashion',
                ])),
            ],

            // 2. WebPage
            [
                '@type' => 'WebPage',
                '@id' => $productUrl . '#webpage',
                'url' => $productUrl,
                'name' => renderMeta($product->meta_title ?? ($product->name ?? 'Nobi Fashion - Mua sắm quần áo & phụ kiện thời trang nam nữ')),
                'description' => renderMeta($product->meta_desc ?? 'Cửa hàng thời trang Nobi Fashion: quần áo nam nữ, áo phông, sơ mi, quần jean, váy, túi xách, thắt lưng, mũ nón. Hàng mới, giá tốt, đổi trả 15 ngày.'),
                'inLanguage' => $settings->site_language ?? 'vi-VN',
                'isPartOf' => [
                    '@id' => $siteUrl . '#website',
                ],
            ],

            // 3. WebSite with Valid SearchAction
            [
                '@type' => 'WebSite',
                '@id' => $siteUrl . '#website',
                'url' => $siteUrl,
                'name' => $settings->subname ?? 'Nobi Fashion',
                'alternateName' => 'Nobi Fashion Việt Nam',
                'publisher' => [
                    '@id' => $siteUrl . '#organization',
                ],
                'inLanguage' => 'vi-VN',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => $siteUrl . '/shop/search?keyword={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],

            // 4. LocalBusiness
            [
                '@type' => 'LocalBusiness',
                '@id' => $siteUrl . '#localbusiness',
                'name' => renderMeta($settings->subname ?? 'Nobi Fashion'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                ],
                'image' => $bannerUrl,
                'url' => $siteUrl,
                'telephone' => $settings->contact_phone ?? '0827 786 198',
                'email' => $settings->contact_email ?? 'support@nobifashion.vn',
                'priceRange' => '₫₫',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $settings->contact_address ?? 'Số 123 Đường Thời Trang, Quận Trung Tâm',
                    'addressLocality' => $settings->city ?? 'Hải Phòng',
                    'addressRegion' => $settings->city ?? 'Hải Phòng',
                    'postalCode' => $settings->postalCode ?? '180000',
                    'addressCountry' => 'VN',
                ],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (float) ($settings->latitude ?? 20.86481),
                    'longitude' => (float) ($settings->longitude ?? 106.68345),
                ],
                'openingHoursSpecification' => [
                    [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                        'opens' => '08:00',
                        'closes' => '17:30',
                    ],
                ],
                'sameAs' => array_values(array_filter([
                    $settings->facebook_link ?? 'https://www.facebook.com/nobifashionvietnam',
                    $settings->instagram_link ?? 'https://www.instagram.com/nobifashionvietnam',
                    $settings->discord_link ?? 'https://discord.gg/nobifashion',
                ])),
            ],

            // 5. BreadcrumbList (Clean, no '#')
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbItems,
            ],

            // 6. Product (Đã bao gồm điều kiện: có đánh giá mới xuất, không có thì bỏ cho sạch)
            $productSchema,
        ],
    ];

    // 7. FAQPage (Chỉ đưa vào Schema khi thực sự có FAQs trong database, nếu không có thì bỏ hẳn)
    if (!empty($faqItems)) {
        $schemaGraph['@graph'][] = [
            '@type' => 'FAQPage',
            'name' => 'Câu hỏi thường gặp về ' . renderMeta($product->name ?? 'sản phẩm thời trang Nobi Fashion'),
            'mainEntity' => $faqItems,
        ];
    }
@endphp
<script type="application/ld+json">
{!! json_encode($schemaGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
