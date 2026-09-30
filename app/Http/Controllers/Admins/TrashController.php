<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TrashController extends Controller
{
    /**
     * Danh sách model hỗ trợ khôi phục trong thùng rác.
     *
     * @var array<string, array>
     */
    protected array $trashables = [
        'posts' => [
            'label' => 'Bài viết',
            'model' => Post::class,
            'searchable' => ['title', 'slug'],
            'columns' => [
                'title' => 'Tiêu đề',
                'slug' => 'Đường dẫn',
                'views' => 'Lượt xem',
            ],
        ],
        'products' => [
            'label' => 'Sản phẩm',
            'model' => Product::class,
            'searchable' => ['name', 'sku', 'slug'],
            'columns' => [
                'name' => 'Tên sản phẩm',
                'sku' => 'Mã SKU',
                'price' => 'Giá bán',
                'stock_quantity' => 'Tồn kho',
            ],
        ],
    ];

    /**
     * Trang danh sách thùng rác.
     */
    public function index(Request $request)
    {
        $type = $request->get('type', array_key_first($this->trashables));
        $trashable = $this->getTrashableByType($type);

        $query = $trashable['model']::onlyTrashed();

        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $columns = $trashable['searchable'] ?? [];
            if (!empty($columns)) {
                $query->where(function ($q) use ($columns, $keyword) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', "%{$keyword}%");
                    }
                });
            }
        }

        $items = $query->latest('deleted_at')
            ->paginate(15)
            ->appends($request->only('type', 'q'));

        $stats = collect($this->trashables)->mapWithKeys(function ($config, $key) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = $config['model'];
            return [$key => $model::onlyTrashed()->count()];
        });

        return view('admins.trash.index', [
            'trashables' => $this->trashables,
            'stats' => $stats,
            'currentType' => $type,
            'items' => $items,
            'search' => $request->q,
        ]);
    }

    /**
     * Khôi phục một bản ghi.
     */
    public function restore(Request $request, string $type, int $id)
    {
        $trashable = $this->getTrashableByType($type);

        $model = $trashable['model']::withTrashed()->findOrFail($id);
        $model->restore();

        return back()->with('success', "{$trashable['label']} đã được khôi phục.");
    }

    /**
     * Xóa vĩnh viễn một bản ghi.
     */
    public function forceDelete(Request $request, string $type, int $id)
    {
        $trashable = $this->getTrashableByType($type);

        $model = $trashable['model']::withTrashed()->findOrFail($id);
        $model->forceDelete();

        return back()->with('success', "{$trashable['label']} đã bị xóa vĩnh viễn.");
    }

    /**
     * Lấy cấu hình trashable theo type.
     */
    protected function getTrashableByType(?string $type): array
    {
        $type = $type ?? array_key_first($this->trashables);
        $trashable = Arr::get($this->trashables, $type);

        abort_if(!$trashable, 404);

        return $trashable + ['type' => $type];
    }
}

