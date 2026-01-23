// Show lọc danh mục sản phẩm
const nobifashion_shop_products_filter = document.querySelector('.nobifashion_shop_products_filter');
const nobifashion_shop_products_filter_categories_title = document.querySelector('.nobifashion_shop_products_filter_categories_title');

nobifashion_shop_products_filter_categories_title.addEventListener('click', function() {
    nobifashion_shop_products_filter.classList.toggle('nobifashion_shop_products_filter_height_full');
});

// Form lọc giá
async function setPrice(min, max) {
    showCustomToast(`Chọn giá từ ${min} đến ${max}`);
    await sleep(1000);
    document.getElementById('minPriceRange').value = min;
    document.getElementById('maxPriceRange').value = max;
    document.getElementById('nobifashion_shop_products_filter_price_content_form').submit();
}
