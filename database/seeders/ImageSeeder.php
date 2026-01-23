<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('images')->truncate(); // Xóa dữ liệu cũ

        $faker = Faker::create();

        // Danh sách các URL ảnh mẫu để tạo dữ liệu ngẫu nhiên
        $imageUrls = [
            "ao-polo-nam-torano-basic-estp038-cotton-tron-thoang-khi-4534frf34reg.webp",
            "ao-polo-nam-torano-basic-estp038-cotton-tron-thoang-khi-54trijbf834hfuerh.webp",
            "ao-polo-nam-torano-basic-estp038-cotton-tron-thoang-khi-5645673734.webp",
            "ao-polo-nam-torano-basic-estp038-cotton-tron-thoang-khi-5ghrth454y46.webp",
            "ao-polo-nam-torano-basic-estp038-cotton-tron-thoang-khi-6g4t563gerg45645yhteg.webp",
            "ao-polo-nam-torano-gstp051-bo-ke-cotton-thoang-khi-56uy56h445.webp",
            "ao-polo-nam-torano-gstp051-bo-ke-cotton-thoang-khi-674y4y4yh.webp",
            "ao-polo-nam-torano-gstp051-bo-ke-cotton-thoang-khi-686756u4hh.webp",
            "ao-polo-nam-torano-gstp051-bo-ke-cotton-thoang-khi-68683254grfvxfb.webp",
            "ao-so-mi-dai-tay-unisex-teelab-chat-vai-oxford-68bfa8f3b7661.webp",
            "ao-so-mi-dai-tay-unisex-teelab-chat-vai-oxford-primary-68bfa8f3ace89.webp",
            "ao-so-mi-dai-tay-unisex-teelab-vai-oxford-94387598-56746w74.webp",
            "ao-so-mi-dai-tay-unisex-teelab-vai-oxford-94387598.webp",
            "ao-so-mi-dai-tay-unisex-teelab-vai-oxford-945674657.webp",
            "ao-so-mi-dai-tay-unisex-teelab-vai-oxford-gfrt43543534.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aece7bc8258.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aece7c0a7e7.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aecedcebd00.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aecedd3d95e.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aecfdac3b6f.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aecfdb1ddda.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed000c25ee.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed00101169.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed007d0073.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0082b2ef.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed015cdb19.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0160f681.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed02c4e146.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed02c80542.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0369517f.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed036d58f8.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed046caac6.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0470a86d.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed04cc718e.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed04d04b04.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0544aea9.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0547bb0b.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed0668ab96.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed066bee38.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed137b6322.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed137ee8d4.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed14f4b539.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed14f80415.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed15fb6fd5.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed15fed6a2.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed191e86ec.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed1922aee2.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed1bde8e26.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed1be44b41.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed20fac4cc.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed21014c29.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed29673e6e.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed296eb939.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed2e162784.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed2e1b31bd.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed34cc39d4.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68aed34cf1da4.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68b66421f1179.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68b66422413a2.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-68b6642271dff.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aece7b9cde7.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aecedcc24e2.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aecfda9d1ad.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed00098db5.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed0079fd6c.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed015a3e35.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed02c2963b.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed03657849.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed046a2101.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed04ca5037.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed05429abb.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed066679b2.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed1378b8f1.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed14f1dd0a.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed15f89308.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed191bfa6b.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed1bda2052.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed20f7ba11.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed2963a48f.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed2e12d5f3.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68aed34c983ae.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-primary-68b66421dfe10.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-variant-68aed29705fc0.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-variant-68aed29710751.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-variant-68aed29729589.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-variant-68aed2e1c2e84.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-variant-68aed2e1c9e2f.webp",
            "ao-so-mi-oxford-unisex-thoang-khi-giu-phom-tot-variant-68aed2e1f1e4c.webp",
            "bo-do-doi-nam-nu-unisex-mua-he-zenkonu-1cfnu32rnj.webp",
            "bo-do-doi-nam-nu-unisex-mua-he-zenkonu-20cvjew9uh9.webp",
            "bo-do-doi-nam-nu-unisex-mua-he-zenkonu-30dnuhrjnj66.webp",
            "bo-do-doi-nam-nu-unisex-mua-he-zenkonu-436456yh.webp",
            "bo-do-doi-nam-nu-unisex-mua-he-zenkonu-fk43hrejk8.webp",
            "bw20-48-kich-thuoc_1756794569_fSF3HSHj.png",
            "chan-vay-3-tang-xoe-phong-co-quan-bao-ho-3dvdfg65y63.webp",
            "chan-vay-3-tang-xoe-phong-co-quan-bao-ho-655645ythg3.webp",
            "chan-vay-3-tang-xoe-phong-co-quan-bao-ho-6z01jt3a.webp",
            "chan-vay-3-tang-xoe-phong-co-quan-bao-ho-7g435yhgt5.webp",
            "chan-vay-3-tang-xoe-phong-co-quan-bao-ho.webp",
            "dich-vu-quang-cao-digital-adsvertising-68bfa9d39795c.webp",
            "dich-vu-quang-cao-digital-adsvertising-68bfa9d3cc0e5.webp",
            "dich-vu-quang-cao-digital-adsvertising-68bfaa80b3f9b.webp",
            "dich-vu-quang-cao-digital-adsvertising-68bfaa80d5811.webp",
            "dich-vu-quang-cao-digital-adsvertising-primary-68bfa9d3928e1.webp",
            "dich-vu-quang-cao-digital-adsvertising-primary-68bfaa80ad36a.webp",
            "dich-vu-quang-cao-digital-adsvertisingg-primary-68bff4f1b421f.webp",
            "dich-vu-quang-cao-digital-adsvertisingg-primary-68bff69801f40.webp",
            "khai-truong-cua-hang-nobi-fashion-viet-nam_1756792863_G4uUdwGB.png",
            "quan-short-nam-unisex-stussy-chinh-hang-gia-tot-3498fuh2ufer.webp",
            "quan-short-nam-unisex-stussy-chinh-hang-gia-tot-38yuewu8f23.webp",
            "quan-short-nam-unisex-stussy-chinh-hang-gia-tot-4edfwe823f.webp",
            "quan-short-nam-unisex-stussy-chinh-hang-gia-tot-84ffu434.webp",
            "quan-short-nam-unisex-stussy-chinh-hang-gia-tot-9834yryr7.webp",
            "quan-short-nam-unisex-stussy-chinh-hang-gia-tot.webp",
        ];

        // Giả sử products đã seed từ 1 → 100
        $productIds = range(1, 100);

        foreach ($productIds as $productId) {
            // Ảnh chính (bắt buộc)
            $randomUrl = $faker->randomElement($imageUrls);
            DB::table('images')->insert([
                'product_id'    => $productId,
                'title'         => "Ảnh chính sản phẩm $productId",
                'notes'         => $faker->sentence(),
                'alt'           => "Ảnh chính của sản phẩm $productId",
                'url'           => $randomUrl,
                'thumbnail_url' => str_replace('.webp', '-thumb.webp', $randomUrl),
                'medium_url'    => str_replace('.webp', '-medium.webp', $randomUrl),
                'is_primary'    => true,
                'order'         => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Các ảnh phụ (0–4 cái ngẫu nhiên)
            $extraCount = rand(0, 4);
            for ($i = 1; $i <= $extraCount; $i++) {
                $randomUrl = $faker->randomElement($imageUrls);
                DB::table('images')->insert([
                    'product_id'    => $productId,
                    'title'         => "Ảnh phụ $i của sản phẩm $productId",
                    'notes'         => $faker->sentence(),
                    'alt'           => "Ảnh phụ của sản phẩm $productId",
                    'url'           => $randomUrl,
                    'thumbnail_url' => str_replace('.webp', '-thumb.webp', $randomUrl),
                    'medium_url'    => str_replace('.webp', '-medium.webp', $randomUrl),
                    'is_primary'    => false,
                    'order'         => $i,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}