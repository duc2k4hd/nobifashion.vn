<div class="modal fade" id="mediaEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-5 text-center">
                        <img src="" alt="" class="img-fluid rounded media-editor-preview" style="max-height: 320px; object-fit: contain;">
                        <p class="text-muted mt-2 small">Xem ảnh gốc / thumbnail</p>
                    </div>
                    <div class="col-md-7">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề</label>
                                <input type="text" name="title" class="form-control" placeholder="Tên ảnh">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alt text (SEO)</label>
                                <input type="text" name="alt" class="form-control" placeholder="Alt text">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả / caption</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Mô tả cho ảnh"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_primary" id="mediaEditorPrimary">
                                <label class="form-check-label" for="mediaEditorPrimary">
                                    Đặt làm ảnh chính (sản phẩm)
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between flex-wrap">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-danger" onclick="mediaManagerActions.deleteMedia()">Xoá ảnh</button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" id="mediaAssignBtn">Gán ảnh</button>
                    <button type="button" class="btn btn-primary" onclick="mediaManagerActions.saveMediaEditor()">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>
</div>

