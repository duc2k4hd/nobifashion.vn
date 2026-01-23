<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\FlashSale;
use App\Models\NewsletterSubscription;
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
        'flash_sales' => [
            'label' => 'Flash Sale',
            'model' => FlashSale::class,
            'searchable' => ['title', 'tag', 'description'],
            'columns' => [
                'title' => 'Tiêu đề',
                'tag' => 'Tag',
                'start_time' => 'Bắt đầu',
                'end_time' => 'Kết thúc',
            ],
        ],
        'contacts' => [
            'label' => 'Liên hệ',
            'model' => Contact::class,
            'searchable' => ['name', 'email', 'phone', 'subject'],
            'columns' => [
                'name' => 'Họ tên',
                'email' => 'Email',
                'phone' => 'Số điện thoại',
                'subject' => 'Chủ đề',
            ],
        ],
        'newsletter' => [
            'label' => 'Đăng ký nhận tin',
            'model' => NewsletterSubscription::class,
            'searchable' => ['email', 'status', 'note'],
            'columns' => [
                'email' => 'Email',
                'status' => 'Trạng thái',
                'verified_at' => 'Xác thực',
                'source' => 'Nguồn',
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

