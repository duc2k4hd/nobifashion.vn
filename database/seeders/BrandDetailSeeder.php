<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandDetailSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo hoặc cập nhật thương hiệu NOBIFASHION
        Brand::updateOrCreate(
            ['slug' => 'nobifashion'],
            [
                'name' => 'NOBIFASHION',
                'description' => 'Được hình thành trong thời đại 4.0, NOBIFASHION áp dụng sức mạnh của công nghệ vào thời trang để đưa ra giải pháp mua sắm đồ cơ bản cho nam giới với mô hình tiện lợi hơn, tiết kiệm hơn — khách hàng có thể mua cả tủ đồ đảm bảo chất lượng, made in Vietnam, giá tốt, giao hàng nhanh chóng.',
                'campaign' => '#SUMMER 2026',
                'followers_count' => 2000,
                'rating_score' => 4.90,
                'joined_years' => 9,
                'banner_slides' => [
                    ['tag' => "#SUMMER 2026\nLOADING.", 'title' => 'Một bầu trời', 'highlight' => 'NOBIFASHION'],
                    ['tag' => "EVERYDAY\nESSENTIALS.", 'title' => 'Đơn giản để', 'highlight' => 'thoải mái hơn'],
                    ['tag' => "MADE IN\nVIETNAM.", 'title' => 'Chất lượng cho', 'highlight' => 'mỗi ngày'],
                ],
                'faqs' => [
                    ['question' => 'Mua NOBIFASHION chính hãng ở đâu?', 'answer' => 'Bạn có thể mua tại gian hàng chính hãng trên website NOBIFASHION hoặc các gian hàng đã được xác minh trên sàn thương mại điện tử.'],
                    ['question' => 'NOBIFASHION giá bao nhiêu?', 'answer' => 'Sản phẩm có nhiều mức giá, phổ biến từ 33.000 ₫ đến khoảng 900.000 ₫ tùy dòng sản phẩm và chương trình ưu đãi.'],
                    ['question' => 'NOBIFASHION bán những gì?', 'answer' => 'Thương hiệu tập trung vào trang phục nam cơ bản: áo thun, polo, sơ mi, quần dài, quần short, đồ lót và phụ kiện.'],
                    ['question' => 'NOBIFASHION có những loại nào?', 'answer' => 'Các nhóm chính gồm đồ mặc hằng ngày, đồ thể thao, đồ lót, đồ mặc nhà, phụ kiện và các bộ phối sẵn.'],
                    ['question' => 'NOBIFASHION có đang giảm giá không?', 'answer' => 'Ưu đãi thay đổi theo từng thời điểm. Giá khuyến mại và phần trăm giảm được hiển thị trực tiếp trên từng thẻ sản phẩm.']
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // 2. Cập nhật thêm thông tin cho Yody nếu có
        $yody = Brand::where('slug', 'yody')->first();
        if ($yody) {
            $yody->update([
                'campaign' => '#LOOK GOOD FEEL GOOD',
                'followers_count' => 5400,
                'rating_score' => 4.95,
                'joined_years' => 10,
                'description' => $yody->description ?: 'Yody - Thương hiệu thời trang Việt mang đến những sản phẩm chất lượng, trẻ trung và năng động cho cả gia đình.',
                'banner_slides' => [
                    ['tag' => 'YODY FASHION', 'title' => 'Tự hào thời trang', 'highlight' => 'VIỆT NAM'],
                    ['tag' => 'POLO & CASUAL', 'title' => 'Mặc đẹp mỗi ngày', 'highlight' => 'tự tin tỏa sáng']
                ],
                'faqs' => [
                    ['question' => 'Sản phẩm Yody chính hãng mua ở đâu?', 'answer' => 'Bạn có thể mua trực tiếp tại website NOBIFASHION hoặc hệ thống cửa hàng Yody trên toàn quốc.'],
                    ['question' => 'Chính sách bảo hành và đổi trả của Yody?', 'answer' => 'Đổi trả miễn phí trong 15 ngày với các sản phẩm còn nguyên tem mác.']
                ],
            ]);
        }
    }
}
