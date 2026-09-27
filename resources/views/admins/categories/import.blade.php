@extends('admins.layouts.master')

@section('title', 'Nhập danh mục từ CSV/Excel')
@section('page-title', 'Nhập danh mục sản phẩm (Batch Ultra Fast)')

@section('content')
<div class="container-fluid pb-5">
    <div class="mb-4">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary btn-sm border-0">
            <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách danh mục
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">
            <!-- Premium Header Card -->
            <div class="card border-0 shadow-lg overflow-hidden mb-4" style="border-radius: 20px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0369a1 100%);">
                <div class="card-body p-4 p-md-5 text-white position-relative">
                    <div class="position-absolute top-0 end-0 p-4 opacity-10">
                        <i class="fas fa-folder-tree fa-6x"></i>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-2">
                                <i class="fas fa-bolt me-1"></i> ULTRA FAST ENGINE
                            </span>
                            <h2 class="fw-bold mb-2">🚀 Nhập Danh Mục Sản Phẩm Tốc Độ Cao</h2>
                            <p class="text-white-50 mb-0">Hỗ trợ hàng chục nghìn đến hàng trăm nghìn danh mục, chia mẻ đa luồng, khớp cha-con chuẩn xác không lo gián đoạn.</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <a href="{{ route('admin.categories.sample') }}" class="btn btn-outline-light rounded-pill px-4">
                                <i class="fas fa-download me-2"></i> Tải file mẫu CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Interaction Area -->
            <div class="row g-4">
                <!-- Cột trái: Tải file & Tùy chọn cột -->
                <div class="col-lg-5">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-upload text-primary me-2"></i> Bước 1: Chọn Tệp Dữ Liệu</h5>

                            <div id="dropZone" class="drop-zone border-2 border-dashed rounded-4 p-4 text-center mb-3 transition-all" style="background: #f8fafc; border-color: #cbd5e1; cursor: pointer;">
                                <input type="file" id="excelFile" class="d-none" accept=".csv, .xlsx, .xls">
                                <div class="drop-zone-content">
                                    <div class="icon-circle bg-white shadow-sm mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 50%;">
                                        <i class="fas fa-cloud-upload-alt text-primary fa-lg"></i>
                                    </div>
                                    <p class="mb-1 fw-semibold text-dark">Kéo thả file CSV/Excel vào đây</p>
                                    <p class="text-muted small mb-0">hoặc nhấn để duyệt từ máy tính (.csv, .xlsx, .xls)</p>
                                </div>
                                <div id="fileInfo" class="d-none mt-2 text-start p-3 bg-white rounded-3 shadow-sm">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center overflow-hidden">
                                            <i class="fas fa-file-excel text-success me-3 fa-2x"></i>
                                            <div class="overflow-hidden">
                                                <div id="fileName" class="fw-bold text-truncate" style="max-width: 180px;">file_name.xlsx</div>
                                                <div id="fileSize" class="text-muted small">0 KB</div>
                                            </div>
                                        </div>
                                        <button type="button" id="btnChangeFile" class="btn btn-sm btn-outline-secondary">Đổi file</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Quy tắc khớp thông minh -->
                            <div class="alert alert-info py-2 px-3 mb-3 small border-0" style="background: #f0f9ff; border-left: 4px solid #0284c7 !important;">
                                <div class="fw-bold mb-1 text-primary"><i class="fas fa-shield-alt me-1"></i> Quy tắc khớp thông minh:</div>
                                <ul class="mb-0 ps-3">
                                    <li><strong>Có ID:</strong> Bắt buộc là cập nhật danh mục theo ID. (Lỗi nếu ID không có trong hệ thống).</li>
                                    <li><strong>Không có ID:</strong> Khớp theo <code>Slug</code> (hoặc sinh từ Tên). Nếu có -> Cập nhật; nếu chưa -> Tạo mới.</li>
                                    <li><strong>Danh mục cha:</strong> Tự động ánh xạ qua ID, Slug hoặc Tên cha. Tự tạo danh mục cha nếu chưa tồn tại.</li>
                                </ul>
                            </div>

                            <!-- Chọn cột cần nhập (Hiển thị sau khi đọc file) -->
                            <div id="columnsSelectionArea" class="d-none mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-bold text-uppercase small text-muted mb-0">Bước 2: Chọn các cột cần nhập</label>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" id="btnSelectAllCols" class="btn btn-outline-secondary py-0">Chọn hết</button>
                                        <button type="button" id="btnDeselectAllCols" class="btn btn-outline-secondary py-0">Bỏ hết</button>
                                        <button type="button" id="btnDefaultCols" class="btn btn-outline-primary py-0">Mặc định</button>
                                    </div>
                                </div>
                                <div class="p-3 bg-light rounded-3 border" style="max-height: 200px; overflow-y: auto;">
                                    <div class="row g-2" id="columnsList">
                                        <!-- Checkboxes render tự động -->
                                    </div>
                                </div>
                                <div class="text-muted small mt-1 fst-italic">* Cột bỏ chọn sẽ giữ nguyên giá trị cũ đối với danh mục cập nhật.</div>
                            </div>

                            <!-- Cấu hình Batch & Luồng -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Kích thước mẻ (Batch Size):</label>
                                    <select id="batchSizeSelect" class="form-select form-select-sm rounded-3">
                                        <option value="50">50 danh mục / mẻ</option>
                                        <option value="100" selected>100 danh mục / mẻ (Khuyên dùng)</option>
                                        <option value="200">200 danh mục / mẻ</option>
                                        <option value="500">500 danh mục / mẻ</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted mb-1">Số luồng song song:</label>
                                    <select id="concurrencySelect" class="form-select form-select-sm rounded-3">
                                        <option value="1">1 luồng (Tuần tự)</option>
                                        <option value="2">2 luồng song song</option>
                                        <option value="3" selected>3 luồng song song (Nhanh)</option>
                                        <option value="5">5 luồng song song (Siêu tốc)</option>
                                    </select>
                                </div>
                            </div>

                            <button id="startImport" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center" disabled>
                                <i class="fas fa-rocket me-2"></i> BẮT ĐẦU NHẬP DỮ LIỆU
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Tiến trình & Console Log -->
                <div class="col-lg-7">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><i class="fas fa-tasks text-success me-2"></i> Trạng thái & Tiến trình</h5>
                                <span id="currentActionBadge" class="badge bg-light text-dark border rounded-pill px-3 py-2">Chờ chọn file</span>
                            </div>

                            <!-- Progress Tracker -->
                            <div class="progress-section mb-3 d-none" id="progressArea">
                                <div class="d-flex justify-content-between mb-1 small fw-bold">
                                    <span id="progressText">Đang xử lý: 0/0</span>
                                    <span id="percentText">0%</span>
                                </div>
                                <div class="progress rounded-pill bg-light" style="height: 12px; border: 1px solid #f1f5f9;">
                                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary rounded-pill" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Stats Grid 4 Ô -->
                            <div class="row g-2 mb-3">
                                <div class="col-3">
                                    <div class="p-2 text-center rounded-3 bg-light border h-100">
                                        <div class="text-muted small" style="font-size: 0.75rem;">Tổng số</div>
                                        <div id="statTotal" class="h5 fw-bold mb-0 text-dark">0</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 text-center rounded-3 h-100" style="background: #ecfdf5; border: 1px dashed #10b981;">
                                        <div class="text-success small" style="font-size: 0.75rem;">Thành công</div>
                                        <div id="statSuccess" class="h5 fw-bold mb-0 text-success">0</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 text-center rounded-3 h-100" style="background: #eff6ff; border: 1px dashed #3b82f6;">
                                        <div class="text-primary small" style="font-size: 0.75rem;">Tạo mới</div>
                                        <div id="statCreated" class="h5 fw-bold mb-0 text-primary">0</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 text-center rounded-3 h-100" style="background: #fef2f2; border: 1px dashed #ef4444;">
                                        <div class="text-danger small" style="font-size: 0.75rem;">Lỗi</div>
                                        <div id="statError" class="h5 fw-bold mb-0 text-danger">0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Process Activity Log Console -->
                            <div class="rounded-4 bg-dark overflow-hidden position-relative">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-secondary" style="background: #1e293b;">
                                    <div class="small text-white-50 fw-bold"><i class="fas fa-terminal me-2"></i>ACTIVITY LOG TERMINAL</div>
                                    <button type="button" id="btnClearLog" class="btn btn-sm text-white-50 py-0 px-2" style="font-size: 0.75rem;">Xóa log</button>
                                </div>
                                <div id="logArea" class="p-3 font-monospace overflow-auto" style="height: 290px; background: #0f172a; font-size: 0.8rem;">
                                    <div class="text-success small opacity-75 mb-1">> Hệ thống sẵn sàng nhập danh mục.</div>
                                    <div class="text-white-50 small opacity-50 mb-1">> Vui lòng chọn hoặc kéo thả file CSV / Excel vào khung bên trái.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.drop-zone:hover { border-color: #3b82f6 !important; background: #eff6ff !important; }
.drop-zone.active { border-color: #3b82f6 !important; background: #eff6ff !important; }
.transition-all { transition: all 0.3s ease; }
#logArea::-webkit-scrollbar { width: 6px; }
#logArea::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('excelFile');
    const btnChangeFile = document.getElementById('btnChangeFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameText = document.getElementById('fileName');
    const fileSizeText = document.getElementById('fileSize');
    const dropContent = document.querySelector('.drop-zone-content');

    const columnsSelectionArea = document.getElementById('columnsSelectionArea');
    const columnsList = document.getElementById('columnsList');
    const btnSelectAllCols = document.getElementById('btnSelectAllCols');
    const btnDeselectAllCols = document.getElementById('btnDeselectAllCols');
    const btnDefaultCols = document.getElementById('btnDefaultCols');

    const batchSizeSelect = document.getElementById('batchSizeSelect');
    const concurrencySelect = document.getElementById('concurrencySelect');
    const startBtn = document.getElementById('startImport');

    const progressArea = document.getElementById('progressArea');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const percentText = document.getElementById('percentText');
    const currentActionBadge = document.getElementById('currentActionBadge');

    const statTotal = document.getElementById('statTotal');
    const statSuccess = document.getElementById('statSuccess');
    const statCreated = document.getElementById('statCreated');
    const statError = document.getElementById('statError');
    const logArea = document.getElementById('logArea');
    const btnClearLog = document.getElementById('btnClearLog');

    let parsedRows = [];
    let detectedHeaders = [];

    const addLog = (msg, type = 'info') => {
        const div = document.createElement('div');
        div.className = `mb-1 ${type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : (type === 'warn' ? 'text-warning' : 'text-white-50'))}`;
        div.innerHTML = `<span class="opacity-50">></span> [${new Date().toLocaleTimeString()}] ${msg}`;
        logArea.appendChild(div);
        logArea.scrollTop = logArea.scrollHeight;
    };

    if (btnClearLog) {
        btnClearLog.addEventListener('click', () => {
            logArea.innerHTML = '';
        });
    }

    const updateProgress = (current, total) => {
        const percent = total > 0 ? Math.round((current / total) * 100) : 0;
        progressBar.style.width = `${percent}%`;
        progressText.innerText = `Đang xử lý: ${current}/${total}`;
        percentText.innerText = `${percent}%`;
    };

    // File selection handling
    dropZone.addEventListener('click', (e) => {
        if (e.target !== btnChangeFile) fileInput.click();
    });

    if (btnChangeFile) {
        btnChangeFile.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.click();
        });
    }

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('active');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('active'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('active');
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) handleFile(e.target.files[0]);
    });

    function handleFile(file) {
        if (!file.name.match(/\.(csv|xlsx|xls)$/i)) {
            addLog('Định dạng không hợp lệ. Vui lòng chọn tệp CSV hoặc Excel (.xlsx, .xls).', 'error');
            return;
        }

        fileNameText.innerText = file.name;
        fileSizeText.innerText = `${Math.round(file.size / 1024)} KB`;
        fileInfo.classList.remove('d-none');
        dropContent.classList.add('d-none');

        addLog(`Đang đọc tệp ${file.name} (${Math.round(file.size / 1024)} KB)...`, 'info');
        currentActionBadge.innerText = 'Đang phân tích tệp...';

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];

                parsedRows = XLSX.utils.sheet_to_json(firstSheet);
                const rawRows = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                detectedHeaders = (rawRows && rawRows.length > 0) ? rawRows[0] : [];

                statTotal.innerText = parsedRows.length;
                statSuccess.innerText = '0';
                statCreated.innerText = '0';
                statError.innerText = '0';

                if (parsedRows.length === 0) {
                    addLog('Tệp không có dữ liệu danh mục hợp lệ.', 'error');
                    startBtn.disabled = true;
                    columnsSelectionArea.classList.add('d-none');
                    currentActionBadge.innerText = 'Tệp rỗng';
                    return;
                }

                // Render dynamic column checkboxes
                columnsList.innerHTML = '';
                detectedHeaders.forEach((col, idx) => {
                    if (!col || String(col).trim() === '') return;
                    const colName = String(col).trim();
                    const lower = colName.toLowerCase();
                    const isDefault = (
                        lower === 'id' ||
                        lower.includes('tên') || lower.includes('name') ||
                        lower === 'slug' ||
                        lower.includes('cha') || lower.includes('parent') ||
                        lower.includes('thứ tự') || lower.includes('sort') ||
                        lower.includes('trạng thái') || lower.includes('status')
                    );

                    const colDiv = document.createElement('div');
                    colDiv.className = 'col-sm-6';
                    colDiv.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input col-checkbox" type="checkbox" value="${colName}" id="chk_col_${idx}" ${isDefault ? 'checked' : ''} data-default="${isDefault ? '1' : '0'}">
                            <label class="form-check-label small text-truncate" for="chk_col_${idx}" title="${colName}">
                                ${colName}
                            </label>
                        </div>
                    `;
                    columnsList.appendChild(colDiv);
                });

                columnsSelectionArea.classList.remove('d-none');
                startBtn.disabled = false;
                currentActionBadge.innerText = `Sẵn sàng (${parsedRows.length} dòng)`;
                addLog(`Đọc tệp thành công! Tìm thấy ${parsedRows.length} danh mục và ${detectedHeaders.length} cột.`, 'success');
            } catch (err) {
                console.error(err);
                addLog('Lỗi khi đọc tệp: ' + err.message, 'error');
                currentActionBadge.innerText = 'Lỗi tệp';
                startBtn.disabled = true;
            }
        };
        reader.readAsArrayBuffer(file);
    }

    if (btnSelectAllCols) {
        btnSelectAllCols.addEventListener('click', () => {
            document.querySelectorAll('.col-checkbox').forEach(cb => cb.checked = true);
        });
    }
    if (btnDeselectAllCols) {
        btnDeselectAllCols.addEventListener('click', () => {
            document.querySelectorAll('.col-checkbox').forEach(cb => cb.checked = false);
        });
    }
    if (btnDefaultCols) {
        btnDefaultCols.addEventListener('click', () => {
            document.querySelectorAll('.col-checkbox').forEach(cb => {
                cb.checked = cb.dataset.default === '1';
            });
        });
    }

    // Ultra Fast Concurrent Batch Import Engine
    startBtn.addEventListener('click', async () => {
        if (!parsedRows.length) return;

        const selectedCols = Array.from(document.querySelectorAll('.col-checkbox:checked')).map(cb => cb.value);
        if (selectedCols.length === 0) {
            alert('Vui lòng chọn ít nhất một cột cần nhập.');
            return;
        }

        const batchSize = parseInt(batchSizeSelect.value) || 100;
        const concurrency = parseInt(concurrencySelect.value) || 3;

        startBtn.disabled = true;
        startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ĐANG XỬ LÝ...';
        progressArea.classList.remove('d-none');
        currentActionBadge.innerText = 'Đang nhập dữ liệu...';

        let totalProcessed = 0;
        let totalSuccess = 0;
        let totalCreated = 0;
        let totalError = 0;

        addLog(`Bắt đầu nhập ${parsedRows.length} danh mục với ${concurrency} luồng song song (mỗi mẻ ${batchSize} danh mục)...`, 'info');

        // Chia dữ liệu thành các mẻ (chunks)
        const chunks = [];
        for (let i = 0; i < parsedRows.length; i += batchSize) {
            chunks.push({
                index: Math.floor(i / batchSize) + 1,
                items: parsedRows.slice(i, i + batchSize)
            });
        }

        const totalBatches = chunks.length;
        let chunkIndex = 0;

        // Worker function cho từng luồng chạy độc lập
        async function runWorker(workerId) {
            while (chunkIndex < totalBatches) {
                const currentBatch = chunks[chunkIndex++];
                if (!currentBatch) break;

                addLog(`[Luồng ${workerId}] Bắt đầu mẻ ${currentBatch.index}/${totalBatches} (${currentBatch.items.length} danh mục)...`);

                try {
                    const response = await fetch("{{ route('admin.categories.import-batch') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            items: currentBatch.items,
                            selected_columns: selectedCols
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        totalProcessed += currentBatch.items.length;
                        totalSuccess += (result.success_count || 0);
                        totalCreated += (result.created_count || 0);

                        if (result.errors && result.errors.length) {
                            totalError += result.errors.length;
                            result.errors.forEach(err => addLog(`⚠️ [Mẻ ${currentBatch.index}]: ${err}`, 'error'));
                        }

                        statSuccess.innerText = totalSuccess;
                        statCreated.innerText = totalCreated;
                        statError.innerText = totalError;
                        updateProgress(totalProcessed, parsedRows.length);
                        addLog(`[Luồng ${workerId}] Mẻ ${currentBatch.index}/${totalBatches} hoàn tất (+${result.success_count} thành công).`, 'success');
                    } else {
                        throw new Error(result.message || 'Lỗi server');
                    }
                } catch (err) {
                    console.error(err);
                    totalError += currentBatch.items.length;
                    statError.innerText = totalError;
                    addLog(`❌ [Luồng ${workerId}] Lỗi mẻ ${currentBatch.index}: ${err.message}`, 'error');
                }
            }
        }

        // Khởi động các worker song song
        const workers = [];
        const activeThreads = Math.min(concurrency, totalBatches);
        for (let w = 1; w <= activeThreads; w++) {
            workers.push(runWorker(w));
        }

        await Promise.all(workers);

        currentActionBadge.innerText = 'Hoàn tất!';
        currentActionBadge.classList.replace('bg-light', 'bg-success');
        currentActionBadge.classList.replace('text-dark', 'text-white');

        addLog(`🎉 QUÁ TRÌNH NHẬP HOÀN TẤT! Thành công: ${totalSuccess}, Tạo mới: ${totalCreated}, Lỗi: ${totalError}`, 'success');
        startBtn.innerHTML = '<i class="fas fa-check me-2"></i> HOÀN TẤT NHẬP DỮ LIỆU';
        startBtn.classList.replace('btn-primary', 'btn-success');

        if (confirm(`Quá trình nhập dữ liệu hoàn tất!\n- Thành công: ${totalSuccess}\n- Tạo mới: ${totalCreated}\n- Lỗi: ${totalError}\n\nBạn có muốn chuyển về trang quản lý danh mục ngay bây giờ không?`)) {
            window.location.href = "{{ route('admin.categories.index') }}";
        }
    });
});
</script>
@endpush
