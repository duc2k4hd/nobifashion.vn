<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            [
                'name' => 'Thời trang nam',
                'slug' => 'thoi-trang-nam',
                'children' => [
                    [
                        'name' => 'Áo khoác nam',
                        'slug' => 'ao-khoac-nam',
                        'children' => [
                            ['name' => 'Áo phao nam', 'slug' => 'ao-phao-nam'],
                            ['name' => 'Áo gió nam', 'slug' => 'ao-gio-nam'],
                            ['name' => 'Áo chống nắng nam', 'slug' => 'ao-chong-nang-nam'],
                            ['name' => 'Áo vest nam', 'slug' => 'ao-vest-nam'],
                        ],
                    ],
                    [
                        'name' => 'Áo giữ nhiệt nam',
                        'slug' => 'ao-giu-nhiet-nam',
                        'children' => [],
                    ],
                    [
                        'name' => 'Áo nam',
                        'slug' => 'ao-nam',
                        'children' => [
                            ['name' => 'Áo polo nam', 'slug' => 'ao-polo-nam'],
                            ['name' => 'Áo sơ mi nam', 'slug' => 'ao-so-mi-nam'],
                            ['name' => 'Áo thun nam', 'slug' => 'ao-thun-nam'],
                            ['name' => 'Áo len nam', 'slug' => 'ao-len-nam'],
                            ['name' => 'Áo hoodie - Áo nỉ nam', 'slug' => 'ao-hoodie-ao-ni-nam'],
                        ],
                    ],
                    [
                        'name' => 'Quần nam',
                        'slug' => 'quan-nam',
                        'children' => [
                            ['name' => 'Quần âu nam', 'slug' => 'quan-au-nam'],
                            ['name' => 'Quần jeans nam', 'slug' => 'quan-jeans-nam'],
                            ['name' => 'Quần short nam', 'slug' => 'quan-short-nam'],
                            ['name' => 'Quần kaki nam', 'slug' => 'quan-kaki-nam'],
                        ],
                    ],
                    [
                        'name' => 'Đồ bộ nam',
                        'slug' => 'do-bo-nam',
                        'children' => [
                            ['name' => 'Đồ bộ dài tay nam', 'slug' => 'do-bo-dai-tay-nam'],
                            ['name' => 'Đồ bộ ngắn tay nam', 'slug' => 'do-bo-ngan-tay-nam'],
                            ['name' => 'Bộ thể thao nam', 'slug' => 'bo-the-thao-nam'],
                        ],
                    ],
                    [
                        'name' => 'Đồ thể thao nam',
                        'slug' => 'do-the-thao-nam',
                        'children' => [
                            ['name' => 'Áo polo thể thao nam', 'slug' => 'ao-polo-the-thao-nam'],
                            ['name' => 'Áo thun thể thao nam', 'slug' => 'ao-thun-the-thao-nam'],
                            ['name' => 'Quần thể thao nam', 'slug' => 'quan-the-thao-nam'],
                        ],
                    ],
                    [
                        'name' => 'Đồ mặc trong & Đồ lót nam',
                        'slug' => 'do-mac-trong-nam',
                        'children' => [
                            ['name' => 'Áo ba lỗ nam', 'slug' => 'ao-ba-lo-nam'],
                            ['name' => 'Quần lót nam', 'slug' => 'quan-lot-nam'],
                        ],
                    ],
                    [
                        'name' => 'Phụ kiện nam',
                        'slug' => 'phu-kien-nam',
                        'children' => [
                            ['name' => 'Giày nam', 'slug' => 'giay-nam'],
                            ['name' => 'Thắt lưng nam', 'slug' => 'that-lung-nam'],
                            ['name' => 'Túi xách nam', 'slug' => 'tui-xach-nam'],
                            ['name' => 'Tất nam', 'slug' => 'tat-nam'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Thời trang nữ',
                'slug' => 'thoi-trang-nu',
                'children' => [
                    [
                        'name' => 'Áo khoác nữ',
                        'slug' => 'ao-khoac-nu',
                        'children' => [
                            ['name' => 'Áo phao nữ', 'slug' => 'ao-phao-nu'],
                            ['name' => 'Áo gió nữ', 'slug' => 'ao-gio'],
                            ['name' => 'Áo chống nắng nữ', 'slug' => 'ao-chong-nang-nu'],
                            ['name' => 'Áo vest nữ', 'slug' => 'ao-vest-nu'],
                            ['name' => 'Áo măng tô nữ', 'slug' => 'ao-mang-to-nu'],
                        ],
                    ],
                    [
                        'name' => 'Áo giữ nhiệt nữ',
                        'slug' => 'ao-giu-nhiet-nu',
                        'children' => [],
                    ],
                    [
                        'name' => 'Áo nữ',
                        'slug' => 'ao-nu',
                        'children' => [
                            ['name' => 'Áo thun nữ', 'slug' => 'ao-thun-nu'],
                            ['name' => 'Áo sơ mi nữ', 'slug' => 'ao-so-mi-nu'],
                            ['name' => 'Áo polo nữ', 'slug' => 'ao-polo-nu'],
                            ['name' => 'Áo len nữ', 'slug' => 'ao-len-nu'],
                            ['name' => 'Áo hoodie - Áo nỉ nữ', 'slug' => 'ao-hoodie-ao-ni-nu'],
                        ],
                    ],
                    [
                        'name' => 'Quần nữ',
                        'slug' => 'quan-nu',
                        'children' => [
                            ['name' => 'Quần âu nữ', 'slug' => 'quan-au-nu'],
                            ['name' => 'Quần jeans nữ', 'slug' => 'quan-jeans-nu'],
                            ['name' => 'Quần short nữ', 'slug' => 'quan-short-nu'],
                            ['name' => 'Quần kaki nữ', 'slug' => 'quan-kaki-nu'],
                            ['name' => 'Quần nỉ nữ', 'slug' => 'quan-ni-nu'],
                        ],
                    ],
                    [
                        'name' => 'Đồ bộ nữ',
                        'slug' => 'do-bo-nu',
                        'children' => [
                            ['name' => 'Đồ bộ dài tay nữ', 'slug' => 'do-bo-dai-tay-nu'],
                            ['name' => 'Đồ bộ ngắn tay nữ', 'slug' => 'do-bo-ngan-tay-nu'],
                            ['name' => 'Bộ thể thao nữ', 'slug' => 'bo-the-thao-nu'],
                        ],
                    ],
                    [
                        'name' => 'Đồ thể thao nữ',
                        'slug' => 'do-the-thao-nu',
                        'children' => [
                            ['name' => 'Áo polo thể thao nữ', 'slug' => 'ao-polo-the-thao-nu'],
                            ['name' => 'Áo thun thể thao nữ', 'slug' => 'ao-thun-the-thao-nu'],
                        ],
                    ],
                    [
                        'name' => 'Đồ mặc trong & Đồ lót nữ',
                        'slug' => 'do-mac-trong-nu',
                        'children' => [
                            ['name' => 'Áo ba lỗ - Áo hai dây nữ', 'slug' => 'ao-hai-day-ba-lo-nu'],
                            ['name' => 'Quần lót nữ', 'slug' => 'quan-lot-nu'],
                            ['name' => 'Áo bra nữ', 'slug' => 'ao-bra-nu'],
                        ],
                    ],
                    [
                        'name' => 'Đầm và chân váy nữ',
                        'slug' => 'dam-va-chan-vay-nu',
                        'children' => [
                            ['name' => 'Chân váy nữ', 'slug' => 'chan-vay-nu'],
                            ['name' => 'Đầm nữ', 'slug' => 'dam-nu'],
                        ],
                    ],
                    [
                        'name' => 'Phụ kiện nữ',
                        'slug' => 'phu-kien-nu',
                        'children' => [
                            ['name' => 'Giày nữ', 'slug' => 'giay-nu'],
                            ['name' => 'Túi xách nữ', 'slug' => 'tui-xach-nu'],
                            ['name' => 'Tất nữ', 'slug' => 'tat-nu'],
                            ['name' => 'Phụ kiện khác nữ', 'slug' => 'phu-kien-khac-nu'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Trẻ em',
                'slug' => 'tre-em',
                'children' => [
                    [
                        'name' => 'Áo khoác trẻ em',
                        'slug' => 'ao-khoac-tre-em',
                        'children' => [
                            ['name' => 'Áo phao trẻ em', 'slug' => 'ao-phao-tre-em'],
                            ['name' => 'Áo gió trẻ em', 'slug' => 'ao-gio-tre-em'],
                        ],
                    ],
                    [
                        'name' => 'Áo giữ nhiệt trẻ em',
                        'slug' => 'ao-giu-nhiet-tre-em',
                        'children' => [],
                    ],
                    [
                        'name' => 'Áo trẻ em',
                        'slug' => 'ao-tre-em',
                        'children' => [
                            ['name' => 'Áo sơ mi trẻ em', 'slug' => 'ao-so-mi-tre-em'],
                            ['name' => 'Áo polo trẻ em', 'slug' => 'ao-polo-tre-em'],
                            ['name' => 'Áo thun trẻ em', 'slug' => 'ao-thun-tre-em'],
                            ['name' => 'Áo len trẻ em', 'slug' => 'ao-len-tre-em'],
                            ['name' => 'Áo hoodie - Áo nỉ trẻ em', 'slug' => 'ao-hoodie-ao-ni-tre-em'],
                        ],
                    ],
                    [
                        'name' => 'Quần trẻ em',
                        'slug' => 'quan-tre-em',
                        'children' => [
                            ['name' => 'Quần jeans trẻ em', 'slug' => 'quan-jeans-tre-em'],
                            ['name' => 'Quần short trẻ em', 'slug' => 'quan-short-tre-em'],
                            ['name' => 'Quần nỉ trẻ em', 'slug' => 'quan-ni-tre-em'],
                            ['name' => 'Quần kaki trẻ em', 'slug' => 'quan-kaki-tre-em'],
                            ['name' => 'Quần dài trẻ em', 'slug' => 'quan-dai-tre-em'],
                        ],
                    ],
                    [
                        'name' => 'Đồ bộ trẻ em',
                        'slug' => 'do-bo-tre-em',
                        'children' => [
                            ['name' => 'Đồ bộ dài tay trẻ em', 'slug' => 'do-bo-dai-tay-tre-em'],
                            ['name' => 'Đồ bộ ngắn tay trẻ em', 'slug' => 'do-bo-ngan-tay-tre-em'],
                        ],
                    ],
                    [
                        'name' => 'Đồ mặc trong trẻ em',
                        'slug' => 'do-mac-trong-tre-em',
                        'children' => [],
                    ],
                    [
                        'name' => 'Đầm và chân váy bé gái',
                        'slug' => 'dam-va-chan-vay-be-gai',
                        'children' => [
                            ['name' => 'Chân váy bé gái', 'slug' => 'chan-vay-be-gai'],
                            ['name' => 'Đầm bé gái', 'slug' => 'dam-be-gai'],
                        ],
                    ],
                    [
                        'name' => 'Phụ kiện trẻ em',
                        'slug' => 'phu-kien-tre-em',
                        'children' => [
                            ['name' => 'Tất trẻ em', 'slug' => 'tat-tre-em'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Đồ gia dụng',
                'slug' => 'do-gia-dung',
                'children' => [
                    [
                        'name' => 'Đồ bếp',
                        'slug' => 'do-bep',
                        'children' => [
                            ['name' => 'Nồi chảo', 'slug' => 'noi-chao'],
                            ['name' => 'Dụng cụ nấu ăn', 'slug' => 'dung-cu-nau-an'],
                            ['name' => 'Máy xay', 'slug' => 'may-xay'],
                            ['name' => 'Ấm siêu tốc', 'slug' => 'am-sieu-toc'],
                        ],
                    ],
                    [
                        'name' => 'Thiết bị điện gia dụng',
                        'slug' => 'thiet-bi-dien-gia-dung',
                        'children' => [
                            ['name' => 'Quạt điện', 'slug' => 'quat-dien'],
                            ['name' => 'Máy sấy tóc', 'slug' => 'may-say-toc'],
                            ['name' => 'Bàn ủi', 'slug' => 'ban-ui'],
                            ['name' => 'Máy hút bụi', 'slug' => 'may-hut-bui'],
                        ],
                    ],
                    [
                        'name' => 'Đồ dùng gia đình',
                        'slug' => 'do-dung-gia-dinh',
                        'children' => [
                            ['name' => 'Chăn ga gối', 'slug' => 'chan-ga-goi'],
                            ['name' => 'Đồ vệ sinh', 'slug' => 'do-ve-sinh'],
                            ['name' => 'Đồ trang trí', 'slug' => 'do-trang-tri'],
                        ],
                    ],
                    [
                        'name' => 'Đồ lưu trữ',
                        'slug' => 'do-luu-tru',
                        'children' => [
                            ['name' => 'Hộp đựng đồ', 'slug' => 'hop-dung-do'],
                            ['name' => 'Kệ', 'slug' => 'ke-do-dung'],
                            ['name' => 'Tủ mini', 'slug' => 'tu-mini'],
                        ],
                    ],
                    [
                        'name' => 'Dụng cụ vệ sinh',
                        'slug' => 'dung-cu-ve-sinh',
                        'children' => [
                            ['name' => 'Cây lau nhà', 'slug' => 'cay-lau-nha'],
                            ['name' => 'Chổi', 'slug' => 'choi-quet-nha'],
                            ['name' => 'Dụng cụ làm sạch', 'slug' => 'dung-cu-lam-sach'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($categories as $sortOrder => $category) {
            $this->createCategoryTree($category, null, $sortOrder);
        }
    }

    private function createCategoryTree(array $data, ?int $parentId = null, int $sortOrder = 0): void
    {
        $category = Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'parent_id' => $parentId,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        foreach ($data['children'] ?? [] as $childSortOrder => $child) {
            $this->createCategoryTree($child, $category->id, $childSortOrder);
        }
    }
}
