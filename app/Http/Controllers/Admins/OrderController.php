<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderRequest;
use App\Http\Requests\Admin\OrderShippingStatusRequest;
use App\Http\Requests\Admin\UpdateGhnOrderRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Requests\Admin\CreateGhnTicketRequest;
use App\Http\Requests\Admin\ReplyGhnTicketRequest;
use App\Models\Account;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\GHNService;
use App\Services\VoucherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected GHNService $ghnService;
    protected VoucherService $voucherService;

    public function __construct(OrderService $orderService, GHNService $ghnService, VoucherService $voucherService)
    {
        $this->orderService = $orderService;
        $this->ghnService = $ghnService;
        $this->voucherService = $voucherService;
    }

    public function index(Request $request)
    {
        $query = Order::with(['account', 'items.product.primaryImage', 'items.variant']);

        // Filters
        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('receiver_name', 'like', "%{$keyword}%")
                    ->orWhere('receiver_phone', 'like', "%{$keyword}%")
                    ->orWhere('receiver_email', 'like', "%{$keyword}%")
                    ->orWhereHas('account', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->get('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($deliveryStatus = $request->get('delivery_status')) {
            $query->where('delivery_status', $deliveryStatus);
        }

        if ($paymentMethod = $request->get('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($accountId = $request->get('account_id')) {
            $query->where('account_id', $accountId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.orders.index', compact('orders'));
    }

    public function create()
    {
        $accounts = Account::where('is_active', 1)->orderBy('name')->get();
        return view('admins.orders.create', compact('accounts'));
    }

    public function store(OrderRequest $request)
    {
        try {
            $data = $request->validated();
            $items = $data['items'] ?? [];
            if (empty($items)) {
                return back()
                    ->withInput()
                    ->with('error', 'Đơn hàng phải có ít nhất một sản phẩm.');
            }

            if (isset($data['tax'])) {
                $taxPercent = (float) $data['tax'];
                $itemSubtotal = collect($items)->reduce(function ($carry, $item) {
                    $qty = (float) ($item['quantity'] ?? 0);
                    $price = (float) ($item['price'] ?? 0);
                    return $carry + ($qty * $price);
                }, 0);
                $data['tax'] = $itemSubtotal * ($taxPercent / 100);
            }

            try {
                $this->prepareVoucherData($data, $items);
            } catch (\Throwable $voucherException) {
                return back()
                    ->withInput()
                    ->with('error', $voucherException->getMessage());
            }

            unset($data['items']);

            $order = $this->orderService->createOrder($data, $items);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Đã tạo đơn hàng thành công. Mã đơn: ' . $order->code);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể tạo đơn hàng: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        $order->load([
            'account',
            'items.product.primaryImage',
            'items.variant.primaryVariantImage',
            'voucher',
        ]);

        $shippingStatuses = config('ghn.shipping_statuses', []);

        // Auto sync GHN order status if order has tracking code
        if ($order->shipping_tracking_code && $order->shipping_partner === 'ghn') {
            try {
                $syncStatusResult = $this->ghnService->syncOrderStatus($order);
                if ($syncStatusResult['success']) {
                    // Reload order to get updated delivery_status
                    $order->refresh();
                }
            } catch (\Exception $e) {
                // Log error but don't block page load
                Log::warning('Failed to auto-sync GHN status on show', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Auto sync ticket list from GHN if order has tracking code
        $ticketsFromGhn = [];
        if ($order->shipping_tracking_code && $order->shipping_partner === 'ghn') {
            $syncResult = $this->ghnService->syncTicketList($order);
            if ($syncResult['success']) {
                $ticketsFromGhn = $syncResult['tickets'] ?? [];
            }
        }

        return view('admins.orders.show', [
            'order' => $order,
            'shippingStatuses' => $shippingStatuses,
            'ticketsFromGhn' => $ticketsFromGhn,
        ]);
    }

    public function edit(Order $order)
    {
        // Check if can edit
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'Không thể sửa đơn hàng đã hoàn thành hoặc đã hủy.');
        }

        $order->load(['items.product', 'items.variant']);
        $accounts = Account::where('is_active', 1)->orderBy('name')->get();

        return view('admins.orders.create', [
            'order' => $order,
            'accounts' => $accounts,
            'isEditing' => true,
        ]);
    }

    public function update(OrderRequest $request, Order $order)
    {
        try {
            // Check if can edit
            if (in_array($order->status, ['completed', 'cancelled'])) {
                return back()
                    ->with('error', 'Không thể sửa đơn hàng đã hoàn thành hoặc đã hủy.');
            }

            $data = $request->validated();
            $items = $data['items'] ?? null;
            if (isset($data['tax']) && $data['tax'] !== null) {
                $taxPercent = (float) $data['tax'];
                if ($items) {
                    $subtotal = collect($items)->reduce(function ($carry, $item) {
                        $qty = (float) ($item['quantity'] ?? 0);
                        $price = (float) ($item['price'] ?? 0);
                        return $carry + ($qty * $price);
                    }, 0);
                } else {
                    $subtotal = $order->items()->sum(DB::raw('quantity * price'));
                }
                $data['tax'] = $subtotal * ($taxPercent / 100);
            }
            try {
                $this->prepareVoucherData($data, $items ?? [], $order);
            } catch (\Throwable $voucherException) {
                return back()
                    ->withInput()
                    ->with('error', $voucherException->getMessage());
            }
            unset($data['items']);

            $order = $this->orderService->updateOrder($order, $data, $items);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Đã cập nhật đơn hàng.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật đơn hàng: ' . $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        try {
            // Check if can delete
            if ($order->status === 'completed') {
                return back()
                    ->with('error', 'Không thể xóa đơn hàng đã hoàn thành. Vui lòng hủy đơn hàng thay vì xóa.');
            }

            $code = $order->code;
            $order->delete();

            return redirect()
                ->route('admin.orders.index')
                ->with('success', 'Đã xóa đơn hàng ' . $code);
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể xóa đơn hàng: ' . $e->getMessage());
        }
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        try {
            $data = $request->validated();
            
            $order = $this->orderService->updateOrderStatus(
                $order,
                $data['status'],
                $data['payment_status'] ?? null,
                $data['delivery_status'] ?? null
            );

            return back()
                ->with('success', 'Đã cập nhật trạng thái đơn hàng.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể cập nhật trạng thái: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order)
    {
        try {
            $restoreStock = $request->boolean('restore_stock', true);
            $note = $request->get('note');

            $order = $this->orderService->cancelOrder($order, $note, $restoreStock);

            return back()
                ->with('success', 'Đã hủy đơn hàng.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể hủy đơn hàng: ' . $e->getMessage());
        }
    }

    public function complete(Order $order)
    {
        try {
            $order = $this->orderService->completeOrder($order);

            return back()
                ->with('success', 'Đã đánh dấu đơn hàng là hoàn thành.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể hoàn thành đơn hàng: ' . $e->getMessage());
        }
    }

    public function recalculate(Order $order)
    {
        try {
            $order = $this->orderService->recalculateOrderTotals($order);

            return back()
                ->with('success', 'Đã tính lại tổng tiền đơn hàng.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể tính lại tổng tiền: ' . $e->getMessage());
        }
    }

    /**
     * Tạo đơn hàng GHN
     */
    public function createGHNOrder(Request $request, Order $order)
    {
        try {
            if (!$order->canCreateGhnShipment()) {
                return back()->with('error', 'Không thể tạo vận đơn GHN cho đơn hàng này. Vui lòng kiểm tra: đơn hàng chưa hủy/hoàn thành, đã có đầy đủ thông tin địa chỉ và người nhận.');
            }

            // Kiểm tra đầy đủ thông tin địa chỉ
            if (!$order->shipping_province_id) {
                return back()->with('error', 'Đơn hàng thiếu thông tin tỉnh/thành phố.');
            }
            if (!$order->shipping_district_id) {
                return back()->with('error', 'Đơn hàng thiếu thông tin quận/huyện.');
            }
            if (!$order->shipping_ward_id) {
                return back()->with('error', 'Đơn hàng thiếu thông tin phường/xã.');
            }

            // Kiểm tra thông tin người nhận
            if (!$order->receiver_name) {
                return back()->with('error', 'Đơn hàng thiếu tên người nhận.');
            }
            if (!$order->receiver_phone) {
                return back()->with('error', 'Đơn hàng thiếu số điện thoại người nhận.');
            }
            if (!$order->shipping_address) {
                return back()->with('error', 'Đơn hàng thiếu địa chỉ giao hàng.');
            }

            // Validate pick_shift_id
            $pickShiftId = $request->get('pick_shift_id');
            if (!$pickShiftId) {
                return back()->with('error', 'Vui lòng chọn ca lấy hàng.');
            }

            // Xác định người trả phí vận chuyển
            $shippingPayer = $request->get('shipping_payer');
            // 1: Shop trả, 2: Người nhận trả (theo docs GHN)
            $paymentTypeId = match ($shippingPayer) {
                'seller' => 1,
                'receiver' => 2,
                default => ($order->payment_method === 'cod' ? 2 : 1),
            };

            // Tạo đơn GHN - đảm bảo tất cả các giá trị số được cast đúng
            $result = $this->ghnService->createOrder($order, [
                'required_note' => $request->get('required_note', 'KHONGCHOXEMHANG'),
                'weight' => $request->get('weight') ? (float) $request->get('weight') : null,
                'length' => $request->get('length') ? (float) $request->get('length') : null,
                'width' => $request->get('width') ? (float) $request->get('width') : null,
                'height' => $request->get('height') ? (float) $request->get('height') : null,
                'insurance_value' => $request->get('insurance_value') ? (float) $request->get('insurance_value') : null,
                'pick_shift_id' => (int) $pickShiftId,
                'payment_type_id' => $paymentTypeId,
            ]);

            if ($result['success']) {
                return back()
                    ->with('success', 'Đã tạo đơn GHN thành công. Mã vận đơn: ' . $result['order_code']);
            } else {
                return back()
                    ->with('error', 'Không thể tạo đơn GHN: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to create GHN order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Không thể tạo đơn GHN: ' . $e->getMessage());
        }
    }

    /**
     * Thêm/cập nhật trạng thái giao hàng (GHN)
     */
    public function addShippingStatus(OrderShippingStatusRequest $request, Order $order)
    {
        try {
            if (!$order->shipping_tracking_code) {
                return back()->with('error', 'Đơn hàng chưa có mã vận đơn GHN.');
            }

            $data = $request->validated();
            $status = $data['status'];
            $definitions = config('ghn.shipping_statuses', []);
            $definition = $definitions[$status] ?? [
                'label' => str_replace('_', ' ', ucfirst($status)),
                'description' => null,
                'delivery_bucket' => null,
            ];

            $history = $order->shipping_status_history;
            $history[] = [
                'status' => $status,
                'label' => $definition['label'] ?? $status,
                'description' => $definition['description'] ?? null,
                'note' => $data['note'] ?? null,
                'created_at' => now()->toIso8601String(),
                'created_by' => auth('web')->user()->name ?? 'System',
                'created_by_id' => auth('web')->id(),
            ];

            $raw = $order->shipping_raw_response ?? [];
            $raw['status_history'] = $history;
            $raw['current_status'] = $status;

            $update = [
                'shipping_raw_response' => $raw,
            ];

            if (!empty($definition['delivery_bucket'])) {
                $update['delivery_status'] = $this->ghnService->mapBucketToDeliveryStatus($definition['delivery_bucket']);
            }

            $order->update($update);

            return back()->with('success', 'Đã cập nhật trạng thái giao hàng.');
        } catch (\Exception $e) {
            Log::error('Failed to add shipping status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Không thể cập nhật trạng thái giao hàng: ' . $e->getMessage());
        }
    }

    public function syncGHNStatus(Order $order)
    {
        $result = $this->ghnService->syncOrderStatus($order);

        if ($result['success']) {
            return back()->with('success', 'Đã đồng bộ trạng thái GHN.');
        }

        return back()->with('error', $result['error'] ?? 'Không thể đồng bộ trạng thái GHN.');
    }

    public function editGhnOrder(Order $order)
    {
        if (!$order->shipping_tracking_code) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Đơn hàng chưa có vận đơn GHN.');
        }

        return view('admins.orders.edit-ghn', [
            'order' => $order,
        ]);
    }

    public function updateGhnOrder(UpdateGhnOrderRequest $request, Order $order)
    {
        if (!$order->shipping_tracking_code) {
            return redirect()->route('admin.orders.show', $order)->with('error', 'Đơn hàng chưa có vận đơn GHN.');
        }

        $result = $this->ghnService->updateGhnOrder($order, $request->validated());

        if ($result['success']) {
            return redirect()->route('admin.orders.show', $order)->with('success', 'Đã cập nhật vận đơn GHN.');
        }

        return back()->withInput()->with('error', $result['error'] ?? 'Không thể cập nhật vận đơn GHN.');
    }

    public function invoice(Order $order)
    {
        if (!$this->invoiceReady($order)) {
            return back()->with('error', 'Chỉ có thể in hóa đơn cho đơn hàng đã giao thành công và hoàn tất.');
        }

        $order->load([
            'account',
            'items.product',
            'items.variant',
            'voucher',
        ]);

        return view('admins.orders.invoice', [
            'order' => $order,
            'invoiceNumber' => $this->invoiceNumber($order),
            'printMode' => true,
        ]);
    }

    public function invoicePdf(Order $order)
    {
        if (!$this->invoiceReady($order)) {
            return back()->with('error', 'Chỉ có thể xuất PDF cho đơn hàng đã giao thành công và hoàn tất.');
        }

        $order->load([
            'account',
            'items.product',
            'items.variant',
            'voucher',
        ]);

        $pdf = Pdf::loadView('admins.orders.invoice', [
            'order' => $order,
            'invoiceNumber' => $this->invoiceNumber($order),
            'printMode' => false,
        ])->setPaper('a4');

        return $pdf->download('invoice-' . $order->code . '.pdf');
    }

    public function trackForm(Request $request)
    {
        $trackingCode = $request->get('tracking_code');
        $result = $request->session()->get('tracking_result');

        return view('admins.orders.track', compact('trackingCode', 'result'));
    }

    public function trackLookup(Request $request)
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->ghnService->getOrderInfo($data['tracking_code']);

        return redirect()
            ->route('admin.orders.track', ['tracking_code' => $data['tracking_code']])
            ->with('tracking_result', $result);
    }

    /**
     * Chuẩn hóa dữ liệu voucher trước khi tạo/cập nhật đơn hàng
     */
    private function prepareVoucherData(array &$data, ?array $items = [], ?Order $order = null): void
    {
        $voucherCode = trim($data['voucher_code'] ?? '');

        if ($voucherCode === '') {
            $data['voucher_discount'] = 0;
            $data['voucher_id'] = null;
            return;
        }

        $payload = $this->buildVoucherOrderData($items, $order);
        if (empty($payload['items'])) {
            throw new \RuntimeException('Vui lòng thêm sản phẩm trước khi áp dụng voucher.');
        }
        $payload['shipping_fee'] = $data['shipping_fee'] ?? 0;

        $result = $this->voucherService->checkVoucherEligibility(
            $voucherCode,
            $payload,
            $data['account_id'] ?? null
        );

        if (empty($result['success'])) {
            throw new \RuntimeException($result['message'] ?? 'Voucher không hợp lệ hoặc đã hết lượt.');
        }

        $voucher = $result['voucher'] ?? null;
        $data['voucher_discount'] = $result['discount_amount'] ?? 0;
        $data['voucher_id'] = $voucher?->id;
        $data['voucher_code'] = $voucherCode;
    }

    /**
     * Tạo payload gửi sang VoucherService
     */
    private function buildVoucherOrderData(?array $items, ?Order $order = null): array
    {
        $itemsData = [];
        $productIds = [];

        if (!empty($items)) {
            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);

                if (!$productId || $quantity <= 0) {
                    continue;
                }

                $itemsData[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'total_price' => $quantity * $price,
                ];
                $productIds[] = $productId;
            }
        } elseif ($order) {
            $order->loadMissing('items.product');
            foreach ($order->items as $orderItem) {
                $itemsData[] = [
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'total_price' => $orderItem->quantity * $orderItem->price,
                ];
                $productIds[] = $orderItem->product_id;
            }
        }

        if (empty($itemsData)) {
            return ['items' => []];
        }

        $productCategories = Product::whereIn('id', array_unique($productIds))
            ->pluck('primary_category_id', 'id');

        foreach ($itemsData as &$row) {
            $row['category_id'] = $productCategories[$row['product_id']] ?? null;
        }
        unset($row);

        return [
            'items' => array_values($itemsData),
        ];
    }

    private function invoiceReady(Order $order): bool
    {
        return $order->status === 'completed'
            && $order->delivery_status === 'delivered'
            && $order->payment_status === 'paid';
    }

    private function invoiceNumber(Order $order): string
    {
        return 'INV-' . $order->code;
    }

    /**
     * Tạo ticket hỗ trợ GHN
     */
    public function createGhnTicket(CreateGhnTicketRequest $request, Order $order)
    {
        try {
            if (!$order->shipping_tracking_code) {
                return back()->with('error', 'Đơn hàng chưa có mã vận đơn GHN.');
            }

            $data = $request->validated();
            
            // Add attachment if exists
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment');
            }

            $result = $this->ghnService->createTicket($order, $data);

            if ($result['success']) {
                return back()
                    ->with('success', 'Đã tạo ticket GHN thành công. Mã ticket: ' . $result['ticket_id']);
            } else {
                return back()
                    ->with('error', 'Không thể tạo ticket GHN: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to create GHN ticket', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Không thể tạo ticket GHN: ' . $e->getMessage());
        }
    }

    /**
     * Lấy thông tin chi tiết ticket GHN
     */
    public function getGhnTicket(Request $request, Order $order)
    {
        try {
            $ticketId = $request->get('ticket_id');
            
            if (!$ticketId) {
                return back()->with('error', 'Vui lòng nhập mã ticket.');
            }

            $result = $this->ghnService->getTicket((int) $ticketId);

            if ($result['success']) {
                return back()
                    ->with('ticket_detail', $result['ticket'])
                    ->with('success', 'Đã lấy thông tin ticket thành công.');
            } else {
                return back()
                    ->with('error', 'Không thể lấy thông tin ticket: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to get GHN ticket', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Không thể lấy thông tin ticket: ' . $e->getMessage());
        }
    }

    /**
     * Đồng bộ ticket từ GHN
     */
    public function syncGhnTicket(Request $request, Order $order)
    {
        try {
            $ticketId = $request->get('ticket_id');
            
            if (!$ticketId) {
                return back()->with('error', 'Vui lòng nhập mã ticket.');
            }

            $result = $this->ghnService->syncTicket($order, (int) $ticketId);

            if ($result['success']) {
                return back()
                    ->with('success', 'Đã đồng bộ ticket GHN thành công.');
            } else {
                return back()
                    ->with('error', 'Không thể đồng bộ ticket: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync GHN ticket', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Không thể đồng bộ ticket: ' . $e->getMessage());
        }
    }

    /**
     * Phản hồi ticket GHN
     */
    public function replyGhnTicket(ReplyGhnTicketRequest $request, Order $order)
    {
        try {
            $data = $request->validated();
            $ticketId = (int) $data['ticket_id'];
            
            // Add attachment if exists
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment');
            }

            $result = $this->ghnService->replyTicket($ticketId, $data);

            if ($result['success']) {
                // After replying, sync ticket to get updated conversations
                $syncResult = $this->ghnService->syncTicket($order, $ticketId);
                
                return back()
                    ->with('success', 'Đã gửi phản hồi ticket GHN thành công.');
            } else {
                return back()
                    ->with('error', 'Không thể gửi phản hồi ticket: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to reply GHN ticket', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Không thể gửi phản hồi ticket: ' . $e->getMessage());
        }
    }

    /**
     * Đồng bộ danh sách tickets từ GHN
     */
    public function syncGhnTicketList(Order $order)
    {
        try {
            if (!$order->shipping_tracking_code) {
                return back()->with('error', 'Đơn hàng chưa có mã vận đơn GHN.');
            }

            $result = $this->ghnService->syncTicketList($order);

            if ($result['success']) {
                return back()
                    ->with('success', 'Đã đồng bộ danh sách tickets từ GHN thành công. Tìm thấy ' . count($result['tickets']) . ' ticket(s).');
            } else {
                return back()
                    ->with('error', 'Không thể đồng bộ danh sách tickets: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync GHN ticket list', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Không thể đồng bộ danh sách tickets: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách ca lấy hàng từ GHN (API endpoint)
     */
    public function getPickShifts()
    {
        try {
            $result = $this->ghnService->getPickShifts();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to get pick shifts', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'shifts' => [],
            ], 500);
        }
    }

    /**
     * Tạo link in đơn hàng GHN
     */
    public function printGhnOrder(Order $order)
    {
        try {
            if (!$order->shipping_tracking_code) {
                return back()->with('error', 'Đơn hàng chưa có mã vận đơn GHN.');
            }

            if ($order->shipping_partner !== 'ghn') {
                return back()->with('error', 'Chỉ có thể in đơn hàng GHN.');
            }

            $result = $this->ghnService->generatePrintToken($order->shipping_tracking_code);

            if ($result['success']) {
                // Lưu print URLs vào session để hiển thị trong view
                session([
                    'ghn_print_token' => $result['token'],
                    'ghn_print_urls' => $result['print_urls'],
                ]);

                return back()->with('success', 'Đã tạo link in đơn hàng GHN. Token có hiệu lực trong 30 phút.');
            } else {
                return back()->with('error', 'Không thể tạo link in đơn hàng: ' . ($result['error'] ?? 'Lỗi không xác định'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to print GHN order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Không thể tạo link in đơn hàng: ' . $e->getMessage());
        }
    }
}

