<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Account;
use App\Models\Category;
use App\Models\Voucher;
use App\Models\Contact;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Hiển thị trang dashboard với dữ liệu phân tích toàn diện, tốc độ cao
     */
    public function index()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();
        $thisMonth = $now->copy()->startOfMonth();

        // 1. ==================== KPI TỔNG THỂ & TÀI CHÍNH ====================
        $totalOrdersCount = Order::count();
        $completedOrdersCount = Order::where('status', 'completed')->count();
        $processingOrdersCount = Order::where('status', 'processing')->count();
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $cancelledOrdersCount = Order::where('status', 'cancelled')->count();

        // Doanh thu
        $allTimeRevenue = (float) Order::where('status', '!=', 'cancelled')->sum('final_price');
        $completedRevenue = (float) Order::where('status', 'completed')->sum('final_price');
        $processingRevenue = (float) Order::where('status', 'processing')->sum('final_price');
        $cancelledRevenue = (float) Order::where('status', 'cancelled')->sum('final_price');
        $totalShippingFee = (float) Order::where('status', '!=', 'cancelled')->sum('shipping_fee');
        $totalDiscount = (float) Order::where('status', '!=', 'cancelled')->sum('discount');

        $activeOrderCount = $totalOrdersCount - $cancelledOrdersCount;
        $aov = $activeOrderCount > 0 ? round($allTimeRevenue / $activeOrderCount, 0) : 0;
        $completionRate = $totalOrdersCount > 0 ? round(($completedOrdersCount / $totalOrdersCount) * 100, 1) : 0;

        // Doanh thu hôm nay và tháng này
        $revenueToday = (float) Order::where('created_at', '>=', $today)->where('status', '!=', 'cancelled')->sum('final_price');
        $revenueThisMonth = (float) Order::where('created_at', '>=', $thisMonth)->where('status', '!=', 'cancelled')->sum('final_price');
        $ordersToday = Order::where('created_at', '>=', $today)->count();
        $ordersThisMonth = Order::where('created_at', '>=', $thisMonth)->count();

        // Kho hàng & Sản phẩm
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $totalStockUnits = (int) Product::sum('stock_quantity');
        $totalInventoryValue = (float) Product::selectRaw('SUM(price * stock_quantity) as total_val')->value('total_val') ?: 0;
        $totalCategories = Category::where('is_active', true)->count();

        // Khách hàng, Bài viết, SEO, Liên hệ, Voucher
        $totalCustomers = Account::count();
        $totalPosts = Post::count();
        $totalPostViews = (int) Post::sum('views');
        $totalRedirects = Redirect::count();
        $totalRedirectHits = (int) Redirect::sum('hits');
        $totalContacts = Contact::count();
        $unreadContacts = Contact::whereIn('status', ['new', 'pending'])->count();
        $totalVouchers = Voucher::count();
        $activeVouchers = Voucher::where('status', 'active')->count();

        $kpis = [
            'total_orders' => $totalOrdersCount,
            'completed_orders' => $completedOrdersCount,
            'processing_orders' => $processingOrdersCount,
            'pending_orders' => $pendingOrdersCount,
            'cancelled_orders' => $cancelledOrdersCount,
            'total_revenue' => $allTimeRevenue,
            'completed_revenue' => $completedRevenue,
            'processing_revenue' => $processingRevenue,
            'cancelled_revenue' => $cancelledRevenue,
            'shipping_fee' => $totalShippingFee,
            'discount' => $totalDiscount,
            'aov' => $aov,
            'completion_rate' => $completionRate,
            'revenue_today' => $revenueToday,
            'revenue_this_month' => $revenueThisMonth,
            'orders_today' => $ordersToday,
            'orders_this_month' => $ordersThisMonth,
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'total_stock' => $totalStockUnits,
            'inventory_value' => $totalInventoryValue,
            'total_categories' => $totalCategories,
            'total_customers' => $totalCustomers,
            'total_posts' => $totalPosts,
            'total_post_views' => $totalPostViews,
            'total_redirects' => $totalRedirects,
            'total_redirect_hits' => $totalRedirectHits,
            'total_contacts' => $totalContacts,
            'unread_contacts' => $unreadContacts,
            'total_vouchers' => $totalVouchers,
            'active_vouchers' => $activeVouchers,
        ];

        // 2. ==================== CHUỖI THỜI GIAN BIỂU ĐỒ (TIME SERIES) ====================
        // Lấy toàn bộ ngày có phát sinh đơn hàng thực tế
        $actualDaily = Order::selectRaw('DATE(created_at) as date, count(*) as total_orders, 
                SUM(CASE WHEN status != "cancelled" THEN final_price ELSE 0 END) as revenue,
                SUM(CASE WHEN status = "completed" THEN final_price ELSE 0 END) as completed_revenue,
                SUM(CASE WHEN status = "cancelled" THEN final_price ELSE 0 END) as cancelled_revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $trendDailyLabels = [];
        $trendDailyRevenue = [];
        $trendDailyOrders = [];
        $trendDailyCompleted = [];

        foreach ($actualDaily as $row) {
            $dateFormatted = Carbon::parse($row->date)->format('d/m/Y');
            $trendDailyLabels[] = $dateFormatted;
            $trendDailyRevenue[] = (float) $row->revenue;
            $trendDailyOrders[] = (int) $row->total_orders;
            $trendDailyCompleted[] = (float) $row->completed_revenue;
        }

        // Nếu ít hơn 3 ngày dữ liệu, bổ sung 7 ngày gần nhất để biểu đồ luôn đầy đặn
        if (count($trendDailyLabels) === 0) {
            for ($i = 6; $i >= 0; $i--) {
                $d = $now->copy()->subDays($i);
                $trendDailyLabels[] = $d->format('d/m');
                $trendDailyRevenue[] = 0;
                $trendDailyOrders[] = 0;
                $trendDailyCompleted[] = 0;
            }
        }

        // Thống kê theo tháng (12 tháng gần nhất hoặc các tháng có dữ liệu)
        $monthlyData = Order::selectRaw('DATE_FORMAT(created_at, "%m/%Y") as month_label, DATE_FORMAT(created_at, "%Y-%m") as sort_month, count(*) as total_orders, 
                SUM(CASE WHEN status != "cancelled" THEN final_price ELSE 0 END) as revenue')
            ->groupBy('month_label', 'sort_month')
            ->orderBy('sort_month', 'asc')
            ->get();

        $trendMonthLabels = [];
        $trendMonthRevenue = [];
        $trendMonthOrders = [];
        foreach ($monthlyData as $row) {
            $trendMonthLabels[] = $row->month_label;
            $trendMonthRevenue[] = (float) $row->revenue;
            $trendMonthOrders[] = (int) $row->total_orders;
        }

        // 3. ==================== PHÂN BỔ TRẠNG THÁI ĐƠN HÀNG ====================
        $orderStatusDist = [
            'labels' => ['Hoàn thành', 'Đang xử lý', 'Chờ xử lý', 'Đã hủy'],
            'data' => [$completedOrdersCount, $processingOrdersCount, $pendingOrdersCount, $cancelledOrdersCount],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
        ];

        // 4. ==================== PHƯƠNG THỨC & TRẠNG THÁI THANH TOÁN ====================
        $paymentStatusCounts = [
            'paid' => Order::where('payment_status', 'paid')->count(),
            'pending' => Order::where('payment_status', 'pending')->count(),
            'failed' => Order::where('payment_status', 'failed')->count(),
        ];
        $paymentMethods = Order::selectRaw('payment_method, count(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method')
            ->toArray();

        // 5. ==================== VẬN CHUYỂN & GIAO HÀNG ====================
        $deliveryCounts = [
            'delivered' => Order::where('delivery_status', 'delivered')->count(),
            'shipping' => Order::where('delivery_status', 'shipping')->count(),
            'pending' => Order::where(function($q) {
                $q->where('delivery_status', 'pending')->orWhereNull('delivery_status');
            })->count(),
            'cancelled' => Order::where('delivery_status', 'cancelled')->count(),
        ];

        // 6. ==================== SẢN PHẨM & KHO HÀNG ====================
        // Toàn bộ sản phẩm trong kho (catalog)
        $catalogProducts = Product::with('primaryCategory')
            ->select('id', 'name', 'sku', 'price', 'cost_price', 'stock_quantity', 'primary_category_id', 'is_active', 'created_at')
            ->orderBy('stock_quantity', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'price' => (float) $p->price,
                    'cost_price' => (float) ($p->cost_price ?? 0),
                    'stock_quantity' => (int) $p->stock_quantity,
                    'category_name' => $p->primaryCategory->name ?? 'Chung',
                    'inventory_value' => (float) ($p->price * $p->stock_quantity),
                    'is_active' => (bool) $p->is_active,
                ];
            });

        // Top sản phẩm theo OrderItem (Doanh số bán)
        $topSoldItems = OrderItem::select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $prod = Product::find($item->product_id);
                return [
                    'id' => $item->product_id,
                    'name' => $prod ? $prod->name : 'Sản phẩm thời trang #' . $item->product_id,
                    'sku' => $prod ? $prod->sku : 'NBI-' . $item->product_id,
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => (float) $item->total_revenue,
                    'stock_quantity' => $prod ? (int) $prod->stock_quantity : 0,
                ];
            });

        // Nếu bảng OrderItem có sản phẩm bán chạy, đưa lên biểu đồ, nếu không thì lấy các sản phẩm catalog
        $chartProductLabels = [];
        $chartProductSold = [];
        $chartProductRevenue = [];

        if ($topSoldItems->isNotEmpty()) {
            foreach ($topSoldItems as $p) {
                $chartProductLabels[] = mb_strimwidth($p['name'], 0, 24, '...');
                $chartProductSold[] = $p['total_sold'];
                $chartProductRevenue[] = $p['total_revenue'];
            }
        } else {
            foreach ($catalogProducts as $p) {
                $chartProductLabels[] = mb_strimwidth($p['name'], 0, 24, '...');
                $chartProductSold[] = $p['stock_quantity'];
                $chartProductRevenue[] = $p['inventory_value'];
            }
        }

        // Danh mục hàng đầu
        $topCategories = Category::select('id', 'name', 'slug')
            ->where('is_active', true)
            ->withCount('primaryProducts')
            ->orderBy('primary_products_count', 'desc')
            ->limit(8)
            ->get();

        // 7. ==================== TOP KHÁCH HÀNG & THÀNH VIÊN ====================
        $topCustomers = Order::selectRaw('receiver_name, receiver_phone, receiver_email, count(*) as order_count, 
                SUM(CASE WHEN status != "cancelled" THEN final_price ELSE 0 END) as total_spent, 
                MAX(created_at) as last_order_at')
            ->whereNotNull('receiver_name')
            ->groupBy('receiver_name', 'receiver_phone', 'receiver_email')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'name' => $c->receiver_name ?: 'Khách hàng',
                    'phone' => $c->receiver_phone ?: '---',
                    'email' => $c->receiver_email ?: '---',
                    'order_count' => (int) $c->order_count,
                    'total_spent' => (float) $c->total_spent,
                    'last_order' => Carbon::parse($c->last_order_at)->format('d/m/Y H:i'),
                ];
            });

        // 8. ==================== ĐƠN HÀNG GẦN ĐÂY VỚI FULL DETAIL CHO MODAL ====================
        $recentOrders = Order::with(['account', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($o) {
                $items = $o->items->map(function ($it) {
                    return [
                        'name' => $it->product->name ?? ('Sản phẩm #' . $it->product_id),
                        'sku' => $it->product->sku ?? ('SKU-' . $it->product_id),
                        'price' => (float) $it->price,
                        'quantity' => (int) $it->quantity,
                        'total_price' => (float) $it->total_price,
                    ];
                });

                return [
                    'id' => $o->id,
                    'code' => $o->code,
                    'receiver_name' => $o->receiver_name ?: ($o->account->name ?? 'Khách vãng lai'),
                    'receiver_phone' => $o->receiver_phone ?: '---',
                    'receiver_email' => $o->receiver_email ?: '---',
                    'shipping_address' => $o->shipping_address ?: 'Tại cửa hàng',
                    'payment_method' => $o->payment_method,
                    'payment_status' => $o->payment_status,
                    'delivery_status' => $o->delivery_status ?: 'pending',
                    'status' => $o->status,
                    'total_price' => (float) $o->total_price,
                    'shipping_fee' => (float) $o->shipping_fee,
                    'discount' => (float) $o->discount,
                    'voucher_discount' => (float) $o->voucher_discount,
                    'voucher_code' => $o->voucher_code,
                    'final_price' => (float) $o->final_price,
                    'shipping_partner' => $o->shipping_partner ?: '---',
                    'shipping_tracking_code' => $o->shipping_tracking_code ?: '---',
                    'customer_note' => $o->customer_note ?: 'Không có ghi chú',
                    'admin_note' => $o->admin_note ?: '---',
                    'created_at_formatted' => Carbon::parse($o->created_at)->format('d/m/Y H:i'),
                    'created_at_relative' => Carbon::parse($o->created_at)->diffForHumans(),
                    'items' => $items,
                    'items_count' => $items->count(),
                ];
            });

        // 9. ==================== NỘI DUNG, LIÊN HỆ & SEO ====================
        $topPosts = Post::select('id', 'title', 'slug', 'views', 'status', 'created_at')
            ->orderBy('views', 'desc')
            ->limit(7)
            ->get();

        $recentContacts = Contact::orderBy('created_at', 'desc')->limit(5)->get();
        $recentVouchers = Voucher::orderBy('created_at', 'desc')->limit(5)->get();

        return view('admins.dashboard.index', compact(
            'kpis',
            'trendDailyLabels',
            'trendDailyRevenue',
            'trendDailyOrders',
            'trendDailyCompleted',
            'trendMonthLabels',
            'trendMonthRevenue',
            'trendMonthOrders',
            'orderStatusDist',
            'paymentStatusCounts',
            'paymentMethods',
            'deliveryCounts',
            'catalogProducts',
            'topSoldItems',
            'chartProductLabels',
            'chartProductSold',
            'chartProductRevenue',
            'topCategories',
            'topCustomers',
            'recentOrders',
            'topPosts',
            'recentContacts',
            'recentVouchers'
        ));
    }
}


