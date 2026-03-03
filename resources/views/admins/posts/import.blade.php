@extends('admins.layouts.master')
 
 @section('page-title', 'Nhập bài viết từ CSV/Excel (Batch)')
 
 @section('content')
 <div class="container-fluid pb-5">
     <div class="mb-4">
         <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary btn-sm border-0">
             <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
         </a>
     </div>
 
     <div class="row justify-content-center">
         <div class="col-xl-9 col-lg-10">
             <!-- Premium Header -->
             <div class="card border-0 shadow-lg overflow-hidden mb-4" style="border-radius: 20px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                 <div class="card-body p-4 p-md-5 text-white position-relative">
                     <div class="position-absolute top-0 end-0 p-4 opacity-10">
                         <i class="fas fa-file-excel fa-6x"></i>
                     </div>
                     <div class="row align-items-center">
                         <div class="col-md-8">
                             <h2 class="fw-bold mb-2">🚀 Batch Import CSV/Excel</h2>
                             <p class="text-white-50 mb-0">Hệ thống xử lý thông minh, hỗ trợ hàng nghìn bài viết mà không lo Timeout.</p>
                         </div>
                         <div class="col-md-4 text-md-end mt-3 mt-md-0">
                             <button id="downloadSample" class="btn btn-outline-light rounded-pill px-4">
                                 <i class="fas fa-download me-2"></i> Tải file mẫu
                             </button>
                         </div>
                     </div>
                 </div>
             </div>
 
             <!-- Main Interaction Area -->
             <div class="row g-4">
                 <div class="col-md-5">
                     <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                         <div class="card-body p-4">
                             <h5 class="fw-bold mb-3"><i class="fas fa-upload text-primary me-2"></i> Bước 1: Chọn Tệp</h5>
                             
                             <div id="dropZone" class="drop-zone border-2 border-dashed rounded-4 p-5 text-center mb-4 transition-all" style="background: #f8fafc; border-color: #cbd5e1; cursor: pointer;">
                                 <input type="file" id="excelFile" class="d-none" accept=".csv, .xlsx, .xls">
                                 <div class="drop-zone-content">
                                     <div class="icon-circle bg-white shadow-sm mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; border-radius: 50%;">
                                         <i class="fas fa-cloud-upload-alt text-primary fa-lg"></i>
                                     </div>
                                     <p class="mb-1 fw-semibold text-dark">Kéo thả file CSV/Excel vào đây</p>
                                     <p class="text-muted small">hoặc nhấn để chọn từ máy tính</p>
                                 </div>
                                 <div id="fileInfo" class="d-none mt-2 text-start p-3 bg-white rounded-3 shadow-sm">
                                     <div class="d-flex align-items-center">
                                         <i class="fas fa-file-excel text-success me-3 fa-2x"></i>
                                         <div class="overflow-hidden">
                                             <div id="fileName" class="fw-bold text-truncate">file_name.xlsx</div>
                                             <div id="fileSize" class="text-muted small">0 KB</div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
 
                             <div class="instructions mb-4">
                                 <h6 class="fw-bold small text-uppercase text-muted mb-3 ls-wide">Quy tắc thông minh:</h6>
                                 <div class="d-flex mb-2">
                                     <div class="me-2 text-primary mt-1"><i class="fas fa-check-circle small"></i></div>
                                     <div class="small text-secondary"><strong>Tự động map:</strong> Hệ thống tự tìm ID bài viết để cập nhật hoặc tạo mới.</div>
                                 </div>
                                 <div class="d-flex mb-2">
                                     <div class="me-2 text-primary mt-1"><i class="fas fa-check-circle small"></i></div>
                                     <div class="small text-secondary"><strong>Xử lý Tags:</strong> Tự động tách tags theo dấu phẩy và đồng bộ polymorphic.</div>
                                 </div>
                                 <div class="d-flex">
                                     <div class="me-2 text-primary mt-1"><i class="fas fa-check-circle small"></i></div>
                                     <div class="small text-secondary"><strong>Tối ưu SEO:</strong> Tự động sinh Slug và Tóm tắt nếu bạn để trống.</div>
                                 </div>
                             </div>
                             
                             <button id="startImport" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center" disabled>
                                 <i class="fas fa-rocket me-2"></i> BẮT ĐẦU NHẬP DỮ LIỆU
                             </button>
                         </div>
                     </div>
                 </div>
 
                 <div class="col-md-7">
                     <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                         <div class="card-body p-4">
                             <div class="d-flex justify-content-between align-items-center mb-4">
                                 <h5 class="fw-bold mb-0"><i class="fas fa-tasks text-success me-2"></i> Trạng thái xử lý</h5>
                                 <span id="batchSizeInfo" class="badge bg-light text-dark border rounded-pill px-3">Batch size: 100 items</span>
                             </div>
 
                             <!-- Progress Tracker -->
                             <div class="progress-section mb-4 d-none" id="progressArea">
                                 <div class="d-flex justify-content-between mb-2 small fw-bold">
                                     <span id="progressText">Đang xử lý: 0/0</span>
                                     <span id="percentText">0%</span>
                                 </div>
                                 <div class="progress rounded-pill bg-light" style="height: 12px; border: 1px solid #f1f5f9;">
                                     <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary rounded-pill" role="progressbar" style="width: 0%"></div>
                                 </div>
                             </div>
 
                             <!-- Stats Grid -->
                             <div class="row g-3 mb-4">
                                 <div class="col-4">
                                     <div class="p-3 text-center rounded-3 bg-light border border-dashed h-100">
                                         <div class="text-muted small mb-1">Tổng cộng</div>
                                         <div id="statTotal" class="h4 fw-bold mb-0 text-dark">0</div>
                                     </div>
                                 </div>
                                 <div class="col-4">
                                     <div class="p-3 text-center rounded-3 h-100" style="background: #ecfdf5; border: 1px dashed #10b981;">
                                         <div class="text-success small mb-1">Thành công</div>
                                         <div id="statSuccess" class="h4 fw-bold mb-0 text-success">0</div>
                                     </div>
                                 </div>
                                 <div class="col-4">
                                     <div class="p-3 text-center rounded-3 h-100" style="background: #fef2f2; border: 1px dashed #ef4444;">
                                         <div class="text-danger small mb-1">Lỗi</div>
                                         <div id="statError" class="h4 fw-bold mb-0 text-danger">0</div>
                                     </div>
                                 </div>
                             </div>
 
                             <!-- Process Log -->
                             <div class="log-container rounded-4 bg-dark overflow-hidden position-relative">
                                 <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-secondary" style="background: #334155;">
                                     <div class="small text-white-50 fw-bold">ACTIVITY LOG</div>
                                     <div class="small text-white-50" id="currentAction">Waiting...</div>
                                 </div>
                                 <div id="logArea" class="p-3 fs-7 text-white-50 font-monospace overflow-auto" style="height: 250px; background: #0f172a;">
                                     <div class="text-success small opacity-50 mb-1">> Ready to start batch operation.</div>
                                     <div class="text-white-50 small opacity-50 mb-1">> Please select a valid Excel file.</div>
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
 .ls-wide { letter-spacing: 0.05em; }
 .fs-7 { font-size: 0.85rem; }
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
 document.addEventListener('DOMContentLoaded', function() {
     const dropZone = document.getElementById('dropZone');
     const fileInput = document.getElementById('excelFile');
     const startBtn = document.getElementById('startImport');
     const fileInfo = document.getElementById('fileInfo');
     const fileNameText = document.getElementById('fileName');
     const fileSizeText = document.getElementById('fileSize');
     const logArea = document.getElementById('logArea');
     const progressArea = document.getElementById('progressArea');
     const progressBar = document.getElementById('progressBar');
     const progressText = document.getElementById('progressText');
     const percentText = document.getElementById('percentText');
     const currentAction = document.getElementById('currentAction');
     const downloadSample = document.getElementById('downloadSample');
 
     const statTotal = document.getElementById('statTotal');
     const statSuccess = document.getElementById('statSuccess');
     const statError = document.getElementById('statError');
 
     let jsonData = [];
     const BATCH_SIZE = 100;
 
     // UI Utils
     const addLog = (msg, type = 'info') => {
         const div = document.createElement('div');
         div.className = `mb-1 small ${type === 'success' ? 'text-success' : (type === 'error' ? 'text-danger' : 'text-white-50')}`;
         div.innerHTML = `<span class="opacity-50">></span> [${new Date().toLocaleTimeString()}] ${msg}`;
         logArea.appendChild(div);
         logArea.scrollTop = logArea.scrollHeight;
     };
 
     const updateProgress = (current, total) => {
         const percent = Math.round((current / total) * 100);
         progressBar.style.width = `${percent}%`;
         progressText.innerText = `Đang xử lý: ${current}/${total}`;
         percentText.innerText = `${percent}%`;
     };
 
     // Handle File selection
     dropZone.addEventListener('click', () => fileInput.click());
     
     dropZone.addEventListener('dragover', (e) => {
         e.preventDefault();
         dropZone.classList.add('active');
     });
 
     dropZone.addEventListener('dragleave', () => dropZone.classList.remove('active'));
 
     dropZone.addEventListener('drop', (e) => {
         e.preventDefault();
         dropZone.classList.remove('active');
         if (e.dataTransfer.files.length) {
             handleFile(e.dataTransfer.files[0]);
         }
     });
 
     fileInput.addEventListener('change', (e) => {
         if (e.target.files.length) {
             handleFile(e.target.files[0]);
         }
     });
 
     const handleFile = (file) => {
         if (!file.name.match(/\.(csv|xlsx|xls)$/)) {
             addLog('File không đúng định dạng. Vui lòng chọn file CSV hoặc Excel.', 'error');
             return;
         }
 
         fileNameText.innerText = file.name;
         fileSizeText.innerText = `${Math.round(file.size / 1024)} KB`;
         fileInfo.classList.remove('d-none');
         document.querySelector('.drop-zone-content').classList.add('d-none');
 
         const reader = new FileReader();
         reader.onload = (e) => {
             try {
                 const data = new Uint8Array(e.target.result);
                 const workbook = XLSX.read(data, { type: 'array' });
                 const firstSheetName = workbook.SheetNames[0];
                 const worksheet = workbook.Sheets[firstSheetName];
                 
                 jsonData = XLSX.utils.sheet_to_json(worksheet);
                 statTotal.innerText = jsonData.length;
                 startBtn.disabled = jsonData.length === 0;
                 
                 addLog(`Đã tải file thành công. Tìm thấy ${jsonData.length} dòng dữ liệu.`, 'success');
             } catch (err) {
                 addLog('Lỗi khi đọc file Excel: ' + err.message, 'error');
             }
         };
         reader.readAsArrayBuffer(file);
     };
 
     // Batch Import Logic
     startBtn.addEventListener('click', async () => {
         if (!jsonData.length) return;
 
         startBtn.disabled = true;
         startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> ĐANG XỬ LÝ...';
         progressArea.classList.remove('d-none');
         logArea.innerHTML = '';
         
         let processedCount = 0;
         let successCount = 0;
         let errorCount = 0;
 
         addLog(`Bắt đầu xử lý ${jsonData.length} bài viết...`, 'info');
         currentAction.innerText = 'Processing batches...';
 
         for (let i = 0; i < jsonData.length; i += BATCH_SIZE) {
             const chunk = jsonData.slice(i, i + BATCH_SIZE);
             addLog(`Đang gửi batch ${Math.floor(i/BATCH_SIZE) + 1} (${chunk.length} bài viết)...`);
 
             try {
                 const response = await fetch("{{ route('admin.posts.import-batch') }}", {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': "{{ csrf_token() }}"
                     },
                     body: JSON.stringify({ items: chunk })
                 });
 
                 const result = await response.json();
 
                 if (result.success) {
                     processedCount += chunk.length;
                     successCount += result.success_count;
                     
                     if (result.errors && result.errors.length) {
                         errorCount += result.errors.length;
                         result.errors.forEach(err => addLog(`Lỗi: ${err}`, 'error'));
                     }
 
                     statSuccess.innerText = successCount;
                     statError.innerText = errorCount;
                     updateProgress(processedCount, jsonData.length);
                 } else {
                     throw new Error(result.message || 'Lỗi server không xác định');
                 }
             } catch (err) {
                 addLog(`Lỗi nghiêm trọng tại batch ${Math.floor(i/BATCH_SIZE) + 1}: ${err.message}`, 'error');
                 errorCount += chunk.length;
                 statError.innerText = errorCount;
             }
         }
 
         addLog('Hoàn thành quá trình nhập dữ liệu!', 'success');
         currentAction.innerText = 'Completed';
         currentAction.classList.add('text-success');
         startBtn.innerHTML = '<i class="fas fa-check me-2"></i> HOÀN TẤT';
         
         if (errorCount === 0) {
             setTimeout(() => {
                 window.location.href = "{{ route('admin.posts.index') }}";
             }, 2000);
         }
     });
 
     // Export Sample Logic (Client-side)
     downloadSample.addEventListener('click', async () => {
         downloadSample.disabled = true;
         downloadSample.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang chuẩn bị...';
         
         try {
             const response = await fetch("{{ route('admin.posts.export-data') }}");
             const result = await response.json();
             
             if (result.success) {
                 const worksheet = XLSX.utils.json_to_sheet(result.data);
                 const workbook = XLSX.utils.book_new();
                 XLSX.utils.book_append_sheet(workbook, worksheet, "Posts");
                 
                 // Generate file and trigger download (CSV)
                 const date = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                 XLSX.writeFile(workbook, `posts_sample_${date}.csv`, { bookType: 'csv' });
                 
                 addLog('Đã tải file mẫu thành công.', 'success');
             }
         } catch (err) {
             addLog('Lỗi khi tải mẫu: ' + err.message, 'error');
         } finally {
             downloadSample.disabled = false;
             downloadSample.innerHTML = '<i class="fas fa-download me-2"></i> Tải file mẫu';
         }
     });
 });
 </script>
 @endpush
