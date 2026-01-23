@extends('admins.layouts.master')

@section('title', 'Quản lý Sitemap')
@section('page-title', '🗺️ Quản lý Sitemap')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/sitemap-icon.ico') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        /* ============================================
           MAIN CONTAINER & CARDS
           ============================================ */
        .sitemap-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 0 0 1px rgba(0, 0, 0, 0.05);
            margin-bottom: 28px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(226, 232, 240, 0.8);
            position: relative;
            overflow: hidden;
        }
        
        .sitemap-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sitemap-card:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04),
                0 0 0 1px rgba(102, 126, 234, 0.1);
        }
        
        .sitemap-card:hover::before {
            opacity: 1;
        }
        
        .sitemap-card h3 {
            margin-top: 0;
            margin-bottom: 24px;
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            padding-bottom: 16px;
        }
        
        .sitemap-card h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        /* ============================================
           STATS GRID - Premium Design
           ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 
                0 10px 15px -3px rgba(102, 126, 234, 0.3),
                0 4px 6px -2px rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        
        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 
                0 20px 25px -5px rgba(102, 126, 234, 0.4),
                0 10px 10px -5px rgba(102, 126, 234, 0.3);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 
                0 10px 15px -3px rgba(17, 153, 142, 0.3),
                0 4px 6px -2px rgba(17, 153, 142, 0.2);
        }
        
        .stat-card.success:hover {
            box-shadow: 
                0 20px 25px -5px rgba(17, 153, 142, 0.4),
                0 10px 10px -5px rgba(17, 153, 142, 0.3);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 
                0 10px 15px -3px rgba(240, 147, 251, 0.3),
                0 4px 6px -2px rgba(240, 147, 251, 0.2);
        }
        
        .stat-card.warning:hover {
            box-shadow: 
                0 20px 25px -5px rgba(240, 147, 251, 0.4),
                0 10px 10px -5px rgba(240, 147, 251, 0.3);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 
                0 10px 15px -3px rgba(79, 172, 254, 0.3),
                0 4px 6px -2px rgba(79, 172, 254, 0.2);
        }
        
        .stat-card.info:hover {
            box-shadow: 
                0 20px 25px -5px rgba(79, 172, 254, 0.4),
                0 10px 10px -5px rgba(79, 172, 254, 0.3);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -1px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.95;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }
        
        /* ============================================
           FORM ELEMENTS - Modern Design
           ============================================ */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
            letter-spacing: 0.3px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 14px;
            background: #ffffff;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 14px;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .form-check:hover {
            background: #f8fafc;
        }
        
        .form-check input[type="checkbox"] {
            width: 22px;
            height: 22px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #667eea;
            border-radius: 6px;
        }
        
        .form-check label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            color: #475569;
            font-size: 14px;
        }
        
        /* ============================================
           BUTTONS - Premium Style
           ============================================ */
        .btn-group-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(17, 153, 142, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(17, 153, 142, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(240, 147, 251, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(240, 147, 251, 0.4);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(79, 172, 254, 0.3);
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 172, 254, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(100, 116, 139, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(100, 116, 139, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.4);
        }
        
        /* ============================================
           TABLE - Elegant Design
           ============================================ */
        .exclude-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .exclude-table th,
        .exclude-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .exclude-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-weight: 700;
            color: #1e293b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .exclude-table tbody tr {
            transition: all 0.2s ease;
            background: #ffffff;
        }
        
        .exclude-table tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.01);
        }
        
        .exclude-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .exclude-table code {
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            color: #475569;
            font-family: 'Monaco', 'Courier New', monospace;
        }
        
        /* ============================================
           BADGES - Modern Style
           ============================================ */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .badge-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        
        /* ============================================
           LIST GROUP - Links
           ============================================ */
        .list-group {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .list-group-item {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .list-group-item:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
            padding-left: 24px;
        }
        
        .list-group-item strong {
            color: #1e293b;
            font-weight: 600;
            min-width: 120px;
        }
        
        .list-group-item::before {
            content: '🔗';
            font-size: 18px;
        }
        
        /* ============================================
           ALERTS - Enhanced
           ============================================ */
        .alert {
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .sitemap-card {
                padding: 20px;
            }
            
            .btn-group-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        /* ============================================
           ANIMATIONS
           ============================================ */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .sitemap-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        .stat-card {
            animation: fadeIn 0.6s ease-out;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ number_format($stats['total_urls']) }}</div>
                <div class="stat-label">Tổng số URL</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value">{{ $stats['cache_enabled'] ? 'Bật' : 'Tắt' }}</div>
                <div class="stat-label">Cache</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value">
                    @if($stats['last_generated'])
                        {{ \Carbon\Carbon::parse($stats['last_generated'])->format('d/m/Y H:i') }}
                    @else
                        Chưa tạo
                    @endif
                </div>
                <div class="stat-label">Lần tạo cuối</div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="sitemap-card">
            <h3>⚙️ Cấu hình Sitemap</h3>
            <form action="{{ route('admin.sitemap.config.update') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="enabled" id="enabled" value="1" 
                               {{ ($configs['enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="enabled">Bật sitemap tổng</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Bật/tắt từng loại sitemap:</label>
                    <div class="form-check">
                        <input type="checkbox" name="posts_enabled" id="posts_enabled" value="1"
                               {{ ($configs['posts_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="posts_enabled">Bài viết (Posts)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="products_enabled" id="products_enabled" value="1"
                               {{ ($configs['products_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="products_enabled">Sản phẩm (Products)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="categories_enabled" id="categories_enabled" value="1"
                               {{ ($configs['categories_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="categories_enabled">Danh mục (Categories)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="tags_enabled" id="tags_enabled" value="1"
                               {{ ($configs['tags_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="tags_enabled">Tags</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="pages_enabled" id="pages_enabled" value="1"
                               {{ ($configs['pages_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="pages_enabled">Trang tĩnh (Pages)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="images_enabled" id="images_enabled" value="1"
                               {{ ($configs['images_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="images_enabled">Hình ảnh (Images)</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="urls_per_file">Số URL mỗi file (mặc định: 10,000)</label>
                    <input type="number" name="urls_per_file" id="urls_per_file" 
                           class="form-control" value="{{ $configs['urls_per_file'] ?? 10000 }}"
                           min="1" max="50000">
                </div>

                <div class="form-group">
                    <label>Ping Search Engines:</label>
                    <div class="form-check">
                        <input type="checkbox" name="ping_google_enabled" id="ping_google_enabled" value="1"
                               {{ ($configs['ping_google_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="ping_google_enabled">Ping Google</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="ping_bing_enabled" id="ping_bing_enabled" value="1"
                               {{ ($configs['ping_bing_enabled'] ?? true) ? 'checked' : '' }}>
                        <label for="ping_bing_enabled">Ping Bing</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
            </form>
        </div>

        <!-- Actions -->
        <div class="sitemap-card">
            <h3>🔧 Thao tác</h3>
            <div class="btn-group-actions">
                <form action="{{ route('admin.sitemap.rebuild') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Bạn có chắc muốn tạo lại sitemap?')">
                        🔄 Tạo lại Sitemap
                    </button>
                </form>
                <form action="{{ route('admin.sitemap.clear-cache') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Bạn có chắc muốn xóa cache?')">
                        🗑️ Xóa Cache
                    </button>
                </form>
                <form action="{{ route('admin.sitemap.ping') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        📡 Ping Search Engines
                    </button>
                </form>
                <a href="{{ route('admin.sitemap.preview', ['type' => 'index']) }}" target="_blank" class="btn btn-secondary">
                    👁️ Xem trước Sitemap Index
                </a>
            </div>
        </div>

        <!-- Exclude Rules -->
        <div class="sitemap-card">
            <h3>🚫 Quy tắc loại trừ</h3>
            
            <form action="{{ route('admin.sitemap.excludes.store') }}" method="POST" class="mb-4">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="exclude_type">Loại</label>
                            <select name="type" id="exclude_type" class="form-control" required>
                                <option value="url">URL</option>
                                <option value="post_id">Post ID</option>
                                <option value="product_id">Product ID</option>
                                <option value="category_id">Category ID</option>
                                <option value="pattern">Pattern (Regex)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exclude_value">Giá trị</label>
                            <input type="text" name="value" id="exclude_value" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exclude_description">Mô tả</label>
                            <input type="text" name="description" id="exclude_description" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Thêm</button>
                        </div>
                    </div>
                </div>
            </form>

            @if($excludes->count() > 0)
                <table class="exclude-table">
                    <thead>
                        <tr>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Mô tả</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excludes as $exclude)
                            <tr>
                                <td>
                                    <span class="badge badge-info">{{ $exclude->type }}</span>
                                </td>
                                <td><code>{{ $exclude->value }}</code></td>
                                <td>{{ $exclude->description ?? '-' }}</td>
                                <td>
                                    @if($exclude->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.sitemap.excludes.toggle', $exclude->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            {{ $exclude->is_active ? 'Tắt' : 'Bật' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.sitemap.excludes.delete', $exclude->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Xóa quy tắc này?')">
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">Chưa có quy tắc loại trừ nào.</p>
            @endif
        </div>

        <!-- Preview Links -->
        <div class="sitemap-card">
            <h3>🔗 Liên kết Sitemap</h3>
            <div class="list-group">
                <a href="{{ url('/sitemap.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Sitemap Index:</strong> {{ url('/sitemap.xml') }}
                </a>
                <a href="{{ url('/sitemap-posts.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Posts:</strong> {{ url('/sitemap-posts.xml') }}
                </a>
                <a href="{{ url('/sitemap-products.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Products:</strong> {{ url('/sitemap-products.xml') }}
                </a>
                <a href="{{ url('/sitemap-categories.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Categories:</strong> {{ url('/sitemap-categories.xml') }}
                </a>
                <a href="{{ url('/sitemap-tags.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Tags:</strong> {{ url('/sitemap-tags.xml') }}
                </a>
                <a href="{{ url('/sitemap-pages.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Pages:</strong> {{ url('/sitemap-pages.xml') }}
                </a>
                <a href="{{ url('/sitemap-images.xml') }}" target="_blank" class="list-group-item list-group-item-action">
                    <strong>Images:</strong> {{ url('/sitemap-images.xml') }}
                </a>
            </div>
        </div>
    </div>
@endsection

