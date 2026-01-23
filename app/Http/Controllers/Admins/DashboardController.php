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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Hiển thị trang dashboard
     */
    public function index()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $thisYear = $now->copy()->startOfYear();

        // ========== TỔNG QUAN ==========
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'total_orders' => Order::count(),
            'total_customers' => Account::count(),
            'total_categories' => Category::where('is_active', true)->count(),
        ];

        // ========== DOANH THU ==========
        $revenue = [
            'today' => Order::where('created_at', '>=', $today)
                ->where('status', '!=', 'cancelled')
                ->sum('final_price'),
            'yesterday' => Order::whereBetween('created_at', [$yesterday, $today])
                ->where('status', '!=', 'cancelled')
                ->sum('final_price'),
            'this_month' => Order::where('created_at', '>=', $thisMonth)
                ->where('status', '!=', 'cancelled')
                ->sum('final_price'),
            'last_month' => Order::whereBetween('created_at', [$lastMonth, $thisMonth])
                ->where('status', '!=', 'cancelled')
                ->sum('final_price'),
            'this_year' => Order::where('created_at', '>=', $thisYear)
                ->where('status', '!=', 'cancelled')
                ->sum('final_price'),
            'all_time' => Order::where('status', '!=', 'cancelled')
                ->sum('final_price'),
        ];

        // Tính % thay đổi
        $revenue['today_change'] = $revenue['yesterday'] > 0 
            ? round((($revenue['today'] - $revenue['yesterday']) / $revenue['yesterday']) * 100, 2)
            : 0;
        $revenue['month_change'] = $revenue['last_month'] > 0
            ? round((($revenue['this_month'] - $revenue['last_month']) / $revenue['last_month']) * 100, 2)
            : 0;

        // ========== ĐƠN HÀNG ==========
        $orders = [
            'today' => Order::where('created_at', '>=', $today)->count(),
            'yesterday' => Order::whereBetween('created_at', [$yesterday, $today])->count(),
            'this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];

        $orders['today_change'] = $orders['yesterday'] > 0
            ? round((($orders['today'] - $orders['yesterday']) / $orders['yesterday']) * 100, 2)
            : 0;

        // ========== SẢN PHẨM BÁN CHẠY ==========
        $topProducts = OrderItem::select(
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
            ->map(function($item) {
                $product = Product::find($item->product_id);
                return [
                    'id' => $item->product_id,
                    'name' => $product->name ?? 'N/A',
                    'sku' => $product->sku ?? 'N/A',
                    'total_sold' => $item->total_sold,
                    'total_revenue' => $item->total_revenue,
                ];
            });

        // ========== ĐƠN HÀNG GẦN ĐÂY ==========
        $recentOrders = Order::with(['account', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ========== THỐNG KÊ THEO NGÀY (7 ngày gần nhất) ==========
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->startOfDay();
            $dateEnd = $date->copy()->endOfDay();
            
            $dailyStats[] = [
                'date' => $date->format('d/m'),
                'date_full' => $date->format('Y-m-d'),
                'orders' => Order::whereBetween('created_at', [$date, $dateEnd])->count(),
                'revenue' => Order::whereBetween('created_at', [$date, $dateEnd])
                    ->where('status', '!=', 'cancelled')
                    ->sum('final_price'),
            ];
        }

        // ========== THỐNG KÊ THEO THÁNG (12 tháng gần nhất) ==========
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthlyStats[] = [
                'month' => $month->format('m/Y'),
                'month_full' => $month->format('Y-m'),
                'orders' => Order::whereBetween('created_at', [$month, $monthEnd])->count(),
                'revenue' => Order::whereBetween('created_at', [$month, $monthEnd])
                    ->where('status', '!=', 'cancelled')
                    ->sum('final_price'),
            ];
        }

        // ========== TOP CATEGORIES (bao gồm sản phẩm từ danh mục con) ==========
        $categories = Category::select('id', 'name', 'slug', 'parent_id')
            ->where('is_active', true)
            ->get();

        $parentMap = $categories->pluck('parent_id', 'id')->toArray();
        $categoryCounts = [];

        Product::select('primary_category_id')
            ->whereNotNull('primary_category_id')
            ->where('is_active', true)
            ->chunk(1000, function ($chunk) use (&$categoryCounts, $parentMap) {
                foreach ($chunk as $product) {
                    $categoryId = $product->primary_category_id;
                    $visited = [];

                    while ($categoryId) {
                        if (isset($visited[$categoryId])) {
                            break;
                        }
                        $categoryCounts[$categoryId] = ($categoryCounts[$categoryId] ?? 0) + 1;
                        $visited[$categoryId] = true;
                        $categoryId = $parentMap[$categoryId] ?? null;
                    }
                }
            });

        $topCategories = $categories
            ->map(function ($category) use ($categoryCounts) {
                $category->product_count = $categoryCounts[$category->id] ?? 0;
                return $category;
            })
            ->sortByDesc('product_count')
            ->take(10)
            ->values();

        // ========== TỶ LỆ THANH TOÁN ==========
        $paymentStats = [
            'paid' => Order::where('payment_status', 'paid')->count(),
            'unpaid' => Order::where('payment_status', '!=', 'paid')->where('payment_status', '!=', null)->count(),
            'pending_payment' => Order::where('payment_status', 'pending')->count(),
        ];
        $paymentStats['paid_percentage'] = ($stats['total_orders'] > 0)
            ? round(($paymentStats['paid'] / $stats['total_orders']) * 100, 2)
            : 0;

        // ========== TỶ LỆ GIAO HÀNG ==========
        $deliveryStats = [
            'delivered' => Order::where('delivery_status', 'delivered')->count(),
            'shipping' => Order::where('delivery_status', 'shipping')->count(),
            'pending' => Order::where('delivery_status', 'pending')->orWhereNull('delivery_status')->count(),
        ];
        $deliveryStats['delivered_percentage'] = ($stats['total_orders'] > 0)
            ? round(($deliveryStats['delivered'] / $stats['total_orders']) * 100, 2)
            : 0;

        // ========== VOUCHER & KHUYẾN MÃI ==========
        $voucherStats = [
            'total' => Voucher::count(),
            'active' => Voucher::active()->count(),
            'used' => Order::whereNotNull('voucher_id')->count(),
        ];

        // ========== LIÊN HỆ MỚI ==========
        $newContacts = Contact::where('created_at', '>=', $today)->count();
        $unreadContacts = Contact::whereIn('status', ['new', 'pending'])->count();

        return view('admins.dashboard.index', compact(
            'stats',
            'revenue',
            'orders',
            'topProducts',
            'recentOrders',
            'dailyStats',
            'monthlyStats',
            'topCategories',
            'paymentStats',
            'deliveryStats',
            'voucherStats',
            'newContacts',
            'unreadContacts'
        ));
    }
}

