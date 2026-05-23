<?php

namespace App\Support;

final class ProductWorkbookSchema
{
    public const SHEET_PRODUCTS = 'products';
    public const SHEET_IMAGES = 'images';
    public const SHEET_VARIANTS = 'variants';
    public const SHEET_FAQS = 'faqs';
    public const SHEET_HOW_TOS = 'how_tos';

    public static function productHeaders(): array
    {
        return [
            'sku',
            'name',
            'slug',
            'description',
            'short_description',
            'price',
            'sale_price',
            'cost_price',
            'stock_quantity',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'meta_canonical',
            'primary_category_slug',
            'category_slugs',
            'tag_slugs',
            'is_featured',
            'has_variants',
            'created_by',
            'is_active',
            'brand_slug',
            'link_shopee',
        ];
    }

    public static function imageHeaders(): array
    {
        return ['sku', 'image_key', 'url', 'title', 'notes', 'alt', 'is_primary', 'order'];
    }

    public static function variantHeaders(): array
    {
        return [
            'product_sku',
            'variant_name',
            'variant_sku',
            'price',
            'sale_price',
            'stock_quantity',
            'image_key',
            'attributes_json',
            'is_active',
        ];
    }

    public static function faqHeaders(): array
    {
        return ['sku', 'question', 'answer', 'order'];
    }

    public static function howToHeaders(): array
    {
        return ['sku', 'title', 'description', 'steps', 'supplies', 'is_active'];
    }

    public static function productSheetAliases(): array
    {
        return [self::SHEET_PRODUCTS];
    }

    public static function imageSheetAliases(): array
    {
        return [self::SHEET_IMAGES];
    }

    public static function variantSheetAliases(): array
    {
        return [self::SHEET_VARIANTS, 'product_variants'];
    }

    public static function faqSheetAliases(): array
    {
        return [self::SHEET_FAQS, 'product_faqs'];
    }

    public static function howToSheetAliases(): array
    {
        return [self::SHEET_HOW_TOS, 'product_how_tos'];
    }
}
