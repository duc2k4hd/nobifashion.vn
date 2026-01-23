<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
        <h4 style="margin:0;font-size:16px;font-weight:600;color:#0f172a;">📥 Import sản phẩm từ Excel</h4>
        <a href="{{ route('admin.flash-sales.items.import-template', $flashSale) }}" class="btn btn-secondary" style="flex:0 0 auto;">⬇️ Tải file mẫu</a>
    </div>
    <form action="{{ route('admin.flash-sales.items.import-excel', $flashSale) }}" method="POST" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
        @csrf
        <input type="file"
               name="file"
               accept=".xlsx,.xls,.csv"
               required
               style="flex:1;min-width:250px;border:1px solid #cbd5f5;border-radius:8px;padding:10px;background:#fff;">
        <button type="submit" class="btn btn-success">📤 Import</button>
        @error('file')
            <div style="width:100%;color:#b91c1c;font-size:13px;">{{ $message }}</div>
        @enderror
    </form>
    <div style="margin-top:12px;font-size:13px;color:#64748b;">
        <strong>Yêu cầu cột:</strong> SKU, Original Price, Sale Price, Stock, Max Per User, Is Active (1/0).
        <br>Giá và số lượng có thể để trống để dùng giá trị mặc định.
    </div>
</div>

