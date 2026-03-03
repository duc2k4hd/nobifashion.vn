<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GHN Shipping Status Definitions
    |--------------------------------------------------------------------------
    |
    | Mapping of GHN shipping statuses to human readable labels, descriptions
    | and the equivalent delivery bucket within our system.
    |
    */

    'shipping_statuses' => [
        'ready_to_pick' => [
            'label' => 'Chờ lấy hàng',
            'description' => 'Đơn GHN vừa được tạo, chờ shipper tiếp nhận.',
            'delivery_bucket' => 'pending',
        ],
        'picking' => [
            'label' => 'Đang lấy hàng',
            'description' => 'Shipper đang tới địa chỉ người gửi để lấy hàng.',
            'delivery_bucket' => 'shipped',
        ],
        'cancel' => [
            'label' => 'Đã hủy',
            'description' => 'Đơn GHN đã bị hủy.',
            'delivery_bucket' => 'cancelled',
        ],
        'money_collect_picking' => [
            'label' => 'Tương tác với người gửi',
            'description' => 'Shipper đang tương tác với người gửi (thu tiền, xác nhận).',
            'delivery_bucket' => 'shipped',
        ],
        'picked' => [
            'label' => 'Đã lấy hàng',
            'description' => 'Shipper đã pickup thành công.',
            'delivery_bucket' => 'shipped',
        ],
        'storing' => [
            'label' => 'Nhập kho',
            'description' => 'Hàng đã về kho phân loại của GHN.',
            'delivery_bucket' => 'shipped',
        ],
        'transporting' => [
            'label' => 'Đang trung chuyển',
            'description' => 'Hàng đang được vận chuyển giữa các kho.',
            'delivery_bucket' => 'shipped',
        ],
        'sorting' => [
            'label' => 'Đang phân loại',
            'description' => 'Hàng được phân loại tại kho.',
            'delivery_bucket' => 'shipped',
        ],
        'delivering' => [
            'label' => 'Đang giao hàng',
            'description' => 'Shipper đang đi giao cho khách.',
            'delivery_bucket' => 'shipped',
        ],
        'money_collect_delivering' => [
            'label' => 'Tương tác với người nhận',
            'description' => 'Shipper đang tương tác với người nhận (thu tiền, xác nhận).',
            'delivery_bucket' => 'shipped',
        ],
        'delivered' => [
            'label' => 'Đã giao',
            'description' => 'Hàng đã được giao thành công cho khách.',
            'delivery_bucket' => 'delivered',
        ],
        'delivery_fail' => [
            'label' => 'Giao thất bại',
            'description' => 'GHN giao không thành công.',
            'delivery_bucket' => 'shipped',
        ],
        'waiting_to_return' => [
            'label' => 'Chờ hoàn',
            'description' => 'Hàng đang chờ hoàn về sau nhiều lần giao thất bại.',
            'delivery_bucket' => 'returned',
        ],
        'return' => [
            'label' => 'Chờ hoàn về',
            'description' => 'Hàng đang trong trạng thái chuẩn bị hoàn về cho người bán.',
            'delivery_bucket' => 'returned',
        ],
        'return_transporting' => [
            'label' => 'Hoàn về - trung chuyển',
            'description' => 'Hàng đang được trung chuyển về phía người bán.',
            'delivery_bucket' => 'returned',
        ],
        'return_sorting' => [
            'label' => 'Hoàn về - phân loại',
            'description' => 'Hàng hoàn đang phân loại tại kho.',
            'delivery_bucket' => 'returned',
        ],
        'returning' => [
            'label' => 'Đang hoàn về',
            'description' => 'Shipper đang giao trả hàng cho người bán.',
            'delivery_bucket' => 'returned',
        ],
        'return_fail' => [
            'label' => 'Hoàn về thất bại',
            'description' => 'GHN không thể hoàn hàng cho người bán.',
            'delivery_bucket' => 'returned',
        ],
        'returned' => [
            'label' => 'Đã hoàn về',
            'description' => 'Hàng đã được hoàn trả cho người bán.',
            'delivery_bucket' => 'returned',
        ],
        'exception' => [
            'label' => 'Ngoại lệ',
            'description' => 'Đơn gặp sự cố phát sinh (ví dụ khách yêu cầu trả lại sau khi giao).',
            'delivery_bucket' => 'pending',
        ],
        'damage' => [
            'label' => 'Hư hại',
            'description' => 'Hàng hóa bị hư hỏng trong quá trình vận chuyển.',
            'delivery_bucket' => 'pending',
        ],
        'lost' => [
            'label' => 'Thất lạc',
            'description' => 'GHN thông báo hàng bị thất lạc.',
            'delivery_bucket' => 'pending',
        ],
    ],
];

