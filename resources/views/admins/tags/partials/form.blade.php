<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên Tag *</label>
                    <input type="text" 
                           name="name" 
                           class="form-control" 
                           value="{{ old('name', $tag->name ?? '') }}" 
                           required
                           placeholder="Ví dụ: Fashion, Style, Trend">
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" 
                           name="slug" 
                           class="form-control" 
                           value="{{ old('slug', $tag->slug ?? '') }}" 
                           placeholder="Tự động tạo từ tên nếu để trống">
                    <small class="text-muted">Slug sẽ được tự động tạo từ tên nếu để trống</small>
                    @error('slug')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả</label>
                    <textarea name="description" 
                              class="form-control" 
                              rows="3"
                              placeholder="Mô tả về tag này...">{{ old('description', $tag->description ?? '') }}</textarea>
                    @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Loại Entity *</label>
                    <select name="entity_type" class="form-select" required>
                        <option value="">-- Chọn loại --</option>
                        @foreach($entityTypes as $type => $label)
                            <option value="{{ $type }}" 
                                    @selected(old('entity_type', $tag->entity_type_display ?? '') === $type)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('entity_type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Entity *</label>
                    <input type="hidden" name="entity_id" id="entity_id" value="{{ old('entity_id', $tag->entity_id ?? '') }}">
                    <input type="text" 
                           id="entity_id_input" 
                           class="form-control" 
                           placeholder="Tìm kiếm và chọn..."
                           data-current-name="{{ $tag->entity_name ?? '' }}"
                           disabled>
                    <small class="text-muted">Chọn entity (sản phẩm/bài viết) để gắn tag</small>
                    @error('entity_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" 
                           type="checkbox" 
                           name="is_active" 
                           value="1" 
                           id="isActiveSwitch"
                           @checked(old('is_active', $tag->is_active ?? true))>
                    <label class="form-check-label" for="isActiveSwitch">Kích hoạt</label>
                </div>

                @if(isset($tag) && $tag->exists)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Thông tin</label>
                        <div class="small text-muted">
                            <div>Usage Count: <strong>{{ $tag->usage_count }}</strong></div>
                            <div>Ngày tạo: {{ $tag->created_at->format('d/m/Y H:i') }}</div>
                            <div>Cập nhật: {{ $tag->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

