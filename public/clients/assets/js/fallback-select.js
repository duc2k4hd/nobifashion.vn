// Fallback for when SlimSelect is not available
function initFallbackSelects() {
    // Province select
    const provinceSelect = document.querySelector('.nobifashion_main_checkout_flex_province');
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            const value = this.value;
            if (value && value !== '') {
                // Enable district select
                const districtSelect = document.querySelector('.nobifashion_main_checkout_flex_district');
                if (districtSelect) {
                    districtSelect.disabled = false;
                    districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
                    // Load districts
                    loadDistricts(value);
                }
            } else {
                // Reset district and ward
                const districtSelect = document.querySelector('.nobifashion_main_checkout_flex_district');
                const wardSelect = document.querySelector('.nobifashion_main_checkout_flex_ward');
                if (districtSelect) {
                    districtSelect.disabled = true;
                    districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
                }
                if (wardSelect) {
                    wardSelect.disabled = true;
                    wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
                }
            }
        });
    }
    
    // District select
    const districtSelect = document.querySelector('.nobifashion_main_checkout_flex_district');
    if (districtSelect) {
        districtSelect.addEventListener('change', function() {
            const value = this.value;
            if (value && value !== '') {
                // Enable ward select
                const wardSelect = document.querySelector('.nobifashion_main_checkout_flex_ward');
                if (wardSelect) {
                    wardSelect.disabled = false;
                    wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
                    // Load wards
                    loadWards(value);
                }
            } else {
                // Reset ward
                const wardSelect = document.querySelector('.nobifashion_main_checkout_flex_ward');
                if (wardSelect) {
                    wardSelect.disabled = true;
                    wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
                }
            }
        });
    }
}

async function loadDistricts(provinceId) {
    try {
        const response = await fetch(`/api/v1/ghn/district/${provinceId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ province_id: provinceId })
        });
        
        if (response.ok) {
            const data = await response.json();
            const districts = data.data || [];
            const districtSelect = document.querySelector('.nobifashion_main_checkout_flex_district');
            
            if (districtSelect) {
                districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.districtID || district.districtId;
                    option.textContent = district.districtName;
                    districtSelect.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading districts:', error);
    }
}

async function loadWards(districtId) {
    try {
        const response = await fetch(`/api/v1/ghn/ward/${districtId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ district_id: districtId })
        });
        
        if (response.ok) {
            const data = await response.json();
            const wards = data.data || [];
            const wardSelect = document.querySelector('.nobifashion_main_checkout_flex_ward');
            
            if (wardSelect) {
                wards.forEach(ward => {
                    const option = document.createElement('option');
                    option.value = ward.wardCode || ward.WardCode;
                    option.textContent = ward.wardName || ward.WardName;
                    wardSelect.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading wards:', error);
    }
}

// Initialize fallback when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if SlimSelect is not available
    if (typeof SlimSelect === 'undefined') {
        initFallbackSelects();
    }
});
