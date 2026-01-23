@php($assignTargets = $uploadTargets ?? [])
<div class="modal fade" id="mediaAssignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gán ảnh vào đối tượng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="mediaAssignForm">
                    @csrf
                    <input type="hidden" name="media_id">
                    <input type="hidden" name="source">
                    <div class="mb-3">
                        <label class="form-label">Gán cho</label>
                        <select name="target_type" class="form-select" required>
                            @foreach($assignTargets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID đối tượng</label>
                        <input type="number" name="target_id" class="form-control" placeholder="Nhập ID cần gán" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary" id="mediaAssignSubmitBtn">Gán ảnh</button>
            </div>
        </div>
    </div>
</div>

