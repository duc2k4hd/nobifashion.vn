@extends('admins.layouts.master')

@section('title', 'Thùng rác hệ thống')
@section('page-title', '🗑️ Thùng rác')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/trash-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .trash-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 24px;
        }
        .trash-sidebar {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
            height: fit-content;
        }
        .trash-sidebar h4 {
            margin-bottom: 16px;
            font-size: 15px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .trash-type-btn {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            background: #fff;
            color: #0f172a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .trash-type-btn.active {
            border-color: #4f46e5;
            background: #eef2ff;
            color: #312e81;
            font-weight: 600;
        }
        .trash-type-btn span {
            background: #e2e8f0;
            color: #475569;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .trash-type-btn.active span {
            background: #c7d2fe;
            color: #3730a3;
        }
        .trash-main {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }
        .trash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .trash-header form {
            display: flex;
            gap: 8px;
        }
        .trash-header input[type="text"] {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #cbd5f5;
            min-width: 260px;
        }
        table.trash-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.trash-table th,
        table.trash-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        table.trash-table th {
            text-transform: uppercase;
            font-size: 12px;
            color: #475569;
            letter-spacing: 0.05em;
        }
        table.trash-table tr:hover td {
            background: #f8fafc;
        }
        .trash-empty {
            text-align: center;
            color: #94a3b8;
            padding: 40px 20px;
        }
        .trash-actions {
            display: flex;
            gap: 8px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 999px;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        @media (max-width: 992px) {
            .trash-container {
                grid-template-columns: 1fr;
            }
            .trash-header form {
                width: 100%;
            }
            .trash-header input[type="text"] {
                flex: 1;
            }
            table.trash-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="trash-container">
        <aside class="trash-sidebar">
            <h4>Danh mục dữ liệu</h4>
            @foreach ($trashables as $key => $config)
                <a href="{{ route('admin.trash.index', ['type' => $key]) }}"
                   class="trash-type-btn {{ $key === $currentType ? 'active' : '' }}">
                    <span>{{ $config['label'] }}</span>
                    <span>{{ $stats[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </aside>

        <section class="trash-main">
            <div class="trash-header">
                <div>
                    <h3 style="margin:0;">{{ $trashables[$currentType]['label'] ?? 'Dữ liệu' }} đã xóa</h3>
                    <p style="margin:4px 0 0;color:#94a3b8;font-size:13px;">
                        Có thể tìm kiếm, khôi phục hoặc xóa vĩnh viễn từng bản ghi.
                    </p>
                </div>

                <form method="GET" action="{{ route('admin.trash.index') }}">
                    <input type="hidden" name="type" value="{{ $currentType }}">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Tìm theo từ khóa...">
                    <button class="btn btn-primary" type="submit">🔍 Tìm</button>
                    @if($search)
                        <a href="{{ route('admin.trash.index', ['type' => $currentType]) }}" class="btn btn-secondary">✖️ Xóa lọc</a>
                    @endif
                </form>
            </div>

            @if ($items->isEmpty())
                <div class="trash-empty">
                    Không có bản ghi nào trong thùng rác cho danh mục này.
                </div>
            @else
                <div class="table-responsive">
                    <table class="trash-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                @foreach (($trashables[$currentType]['columns'] ?? []) as $field => $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th>Ngày xóa</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    @foreach (($trashables[$currentType]['columns'] ?? []) as $field => $label)
                                        @php
                                            $value = data_get($item, $field);
                                            if ($value instanceof \Illuminate\Support\Carbon) {
                                                $value = $value->timezone(config('app.timezone'))->format('d/m/Y H:i');
                                            }
                                        @endphp
                                        <td>{!! $value !== null && $value !== '' ? e($value) : '<span style="color:#94a3b8;">-</span>' !!}</td>
                                    @endforeach
                                    <td>
                                        {{ optional($item->deleted_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                    </td>
                                    <td>
                                        <div class="trash-actions">
                                            <form action="{{ route('admin.trash.restore', [$currentType, $item->id]) }}" method="POST" onsubmit="return confirm('Khôi phục bản ghi này?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm">↩️ Khôi phục</button>
                                            </form>
                                            <form action="{{ route('admin.trash.force-delete', [$currentType, $item->id]) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn bản ghi này? Hành động không thể hoàn tác.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Xóa hẳn</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:20px;">
                    {{ $items->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection

