/**
 * NOBIFASHION Brand Page Script
 * Xử lý giao diện và tải dữ liệu sản phẩm thật từ Database
 */
document.addEventListener('DOMContentLoaded', function () {
  const config = window.nobifashion_brand_config || {};
  const productsApi = config.productsApi || '';
  const brandName = config.brandName || 'NOBIFASHION';

  let currentCategoryId = '';
  let isLoadingProducts = false;

  // 1. Hàm tạo HTML cho 1 thẻ sản phẩm thật
  function createProductCard(product) {
    const oldPriceHtml = product.old_price 
      ? `<span class="nobifashion_brand_product_old">${product.old_price}</span>` 
      : '';
    
    return `
      <article class="nobifashion_brand_product">
        <a href="${product.url}" class="nobifashion_brand_product_visual" style="aspect-ratio: 1/1.05; display: block;">
          <img src="${product.image_url}" alt="${product.name}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
        </a>
        <span class="nobifashion_brand_badge">${brandName}</span>
        <button type="button" class="nobifashion_brand_favorite" data-id="${product.id}" aria-label="Thêm vào yêu thích">♡</button>
        <div class="nobifashion_brand_product_body">
          <p class="nobifashion_brand_product_brand">${brandName}</p>
          <a href="${product.url}" class="nobifashion_brand_product_title" title="${product.name}">${product.name}</a>
          <span class="nobifashion_brand_product_price">${product.price}</span>
          ${oldPriceHtml}
          <div class="nobifashion_brand_product_meta">
            <span class="nobifashion_brand_stars">★★★★★</span>
            <span class="nobifashion_brand_sold">Còn hàng</span>
          </div>
        </div>
      </article>
    `;
  }

  // 2. Gán sự kiện nút yêu thích (Wishlist)
  function initFavoriteButtons(container) {
    if (!container) return;
    container.querySelectorAll('.nobifashion_brand_favorite').forEach(button => {
      if (button.dataset.favBound) return;
      button.dataset.favBound = 'true';
      button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        button.classList.toggle('nobifashion_brand_favorited');
        const isFavorited = button.classList.contains('nobifashion_brand_favorited');
        button.textContent = isFavorited ? '♥' : '♡';
        nobifashion_brand_toast(isFavorited ? 'Đã thêm vào yêu thích' : 'Đã bỏ khỏi yêu thích');
      });
    });
  }

  const bestsellersContainer = document.getElementById('nobifashion_brand_bestsellers');
  const recommendContainer = document.getElementById('nobifashion_brand_recommend');
  const loadMoreBtn = document.getElementById('nobifashion_brand_load_more');

  initFavoriteButtons(bestsellersContainer);
  initFavoriteButtons(recommendContainer);

  // 3. Xử lý nút "Xem thêm sản phẩm" (Tải thêm từ Database)
  if (loadMoreBtn && productsApi) {
    loadMoreBtn.addEventListener('click', function () {
      if (isLoadingProducts) return;

      const nextPage = parseInt(loadMoreBtn.dataset.nextPage || '2', 10);
      isLoadingProducts = true;
      const originalText = loadMoreBtn.textContent;
      loadMoreBtn.textContent = 'Đang tải thêm...';
      loadMoreBtn.disabled = true;

      let fetchUrl = `${productsApi}?page=${nextPage}`;
      if (currentCategoryId) {
        fetchUrl += `&category_id=${encodeURIComponent(currentCategoryId)}`;
      }

      fetch(fetchUrl, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(res => res.json())
      .then(response => {
        if (response.success && Array.isArray(response.data) && response.data.length > 0) {
          const html = response.data.map(createProductCard).join('');
          recommendContainer.insertAdjacentHTML('beforeend', html);
          initFavoriteButtons(recommendContainer);

          if (response.has_more) {
            loadMoreBtn.dataset.nextPage = (response.current_page + 1).toString();
            loadMoreBtn.textContent = originalText;
            loadMoreBtn.disabled = false;
          } else {
            loadMoreBtn.style.display = 'none';
            nobifashion_brand_toast('Đã hiển thị tất cả sản phẩm của thương hiệu');
          }
        } else {
          loadMoreBtn.style.display = 'none';
          nobifashion_brand_toast('Đã hết sản phẩm');
        }
      })
      .catch(err => {
        console.error('Lỗi tải thêm sản phẩm:', err);
        loadMoreBtn.textContent = originalText;
        loadMoreBtn.disabled = false;
        nobifashion_brand_toast('Không thể tải thêm, vui lòng thử lại');
      })
      .finally(() => {
        isLoadingProducts = false;
      });
    });
  }

  // 4. Lọc sản phẩm theo danh mục thật khi click
  const categoryContainer = document.getElementById('nobifashion_brand_categories');
  if (categoryContainer && productsApi) {
    categoryContainer.querySelectorAll('.nobifashion_brand_category').forEach(button => {
      button.addEventListener('click', function () {
        if (isLoadingProducts) return;

        categoryContainer.querySelectorAll('.nobifashion_brand_category').forEach(btn => {
          btn.classList.remove('nobifashion_brand_category_active');
        });
        button.classList.add('nobifashion_brand_category_active');

        currentCategoryId = button.dataset.categoryId || '';
        const categoryName = button.querySelector('b')?.textContent || 'Sản phẩm';
        const titleEl = document.getElementById('nobifashion_brand_recommend_title');
        if (titleEl) {
          titleEl.textContent = currentCategoryId ? `Sản phẩm: ${categoryName}` : 'Gợi ý cho bạn';
        }

        // Tải trang 1 theo danh mục vừa chọn
        isLoadingProducts = true;
        if (recommendContainer) {
          recommendContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 24px 0;">Đang tải danh sách sản phẩm...</p>';
        }
        if (loadMoreBtn) {
          loadMoreBtn.style.display = 'none';
        }

        let fetchUrl = `${productsApi}?page=1`;
        if (currentCategoryId) {
          fetchUrl += `&category_id=${encodeURIComponent(currentCategoryId)}`;
        }

        fetch(fetchUrl, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
        .then(res => res.json())
        .then(response => {
          if (response.success && Array.isArray(response.data) && response.data.length > 0) {
            recommendContainer.innerHTML = response.data.map(createProductCard).join('');
            initFavoriteButtons(recommendContainer);

            if (loadMoreBtn) {
              if (response.has_more) {
                loadMoreBtn.dataset.nextPage = '2';
                loadMoreBtn.style.display = 'block';
                loadMoreBtn.textContent = 'Xem thêm sản phẩm';
                loadMoreBtn.disabled = false;
              } else {
                loadMoreBtn.style.display = 'none';
              }
            }
          } else {
            recommendContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 24px 0;">Không có sản phẩm nào trong danh mục này.</p>';
            if (loadMoreBtn) {
              loadMoreBtn.style.display = 'none';
            }
          }

          const recommendSection = document.querySelector('.nobifashion_brand_recommend_section');
          if (recommendSection) {
            recommendSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        })
        .catch(err => {
          console.error('Lỗi lọc danh mục:', err);
          if (recommendContainer) {
            recommendContainer.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #ef4444; padding: 24px 0;">Lỗi kết nối, vui lòng thử lại.</p>';
          }
        })
        .finally(() => {
          isLoadingProducts = false;
        });
      });
    });
  }

  // 5. Slider banner
  const slides = [...document.querySelectorAll('.nobifashion_brand_slide')];
  const dotsContainer = document.getElementById('nobifashion_brand_dots');
  let slideIndex = 0;
  let slideInterval = null;

  function showSlide(index) {
    slideIndex = index;
    slides.forEach((slide, i) => {
      slide.classList.toggle('nobifashion_brand_slide_active', i === index);
    });
    if (dotsContainer) {
      [...dotsContainer.children].forEach((dot, i) => {
        dot.classList.toggle('nobifashion_brand_dot_active', i === index);
      });
    }
  }

  if (dotsContainer && slides.length > 1) {
    dotsContainer.innerHTML = '';
    slides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.setAttribute('type', 'button');
      dot.setAttribute('aria-label', `Mở banner ${i + 1}`);
      dot.addEventListener('click', () => {
        showSlide(i);
        resetSlideInterval();
      });
      dotsContainer.appendChild(dot);
    });

    showSlide(0);

    function startSlideInterval() {
      slideInterval = setInterval(() => {
        showSlide((slideIndex + 1) % slides.length);
      }, 5000);
    }

    function resetSlideInterval() {
      if (slideInterval) clearInterval(slideInterval);
      startSlideInterval();
    }

    startSlideInterval();
  }

  // 6. Nút "Xem thêm" / "Thu gọn" mô tả giới thiệu
  const moreBtn = document.getElementById('nobifashion_brand_more');
  const intro = document.getElementById('nobifashion_brand_intro') || document.querySelector('.nobifashion_brand_intro');
  if (moreBtn && intro) {
    // Ẩn nút nếu văn bản ngắn không bị tràn 2 dòng
    if (intro.scrollHeight <= intro.clientHeight + 4) {
      moreBtn.style.display = 'none';
    }

    moreBtn.addEventListener('click', () => {
      intro.classList.toggle('nobifashion_brand_expanded');
      const isExpanded = intro.classList.contains('nobifashion_brand_expanded');
      moreBtn.innerHTML = isExpanded
        ? '<span>Thu gọn</span> <i class="fa-solid fa-chevron-up"></i>'
        : '<span>Xem thêm</span> <i class="fa-solid fa-chevron-down"></i>';
    });
  }

  // 7. Nút "Theo dõi" thương hiệu
  const followBtn = document.getElementById('nobifashion_brand_follow');
  if (followBtn) {
    followBtn.addEventListener('click', () => {
      followBtn.classList.toggle('nobifashion_brand_following');
      const isFollowing = followBtn.classList.contains('nobifashion_brand_following');
      followBtn.innerHTML = isFollowing ? '✓ Đang theo dõi' : '<span>♙</span> Theo dõi';
      nobifashion_brand_toast(isFollowing ? `Đã theo dõi ${brandName}` : `Đã bỏ theo dõi`);
    });
  }

  // 8. FAQ Accordion
  const faqContainer = document.getElementById('nobifashion_brand_faq');
  if (faqContainer) {
    faqContainer.querySelectorAll('.nobifashion_brand_faq_question').forEach(button => {
      button.addEventListener('click', () => {
        const item = button.parentElement;
        item.classList.toggle('nobifashion_brand_faq_open');
        const icon = button.querySelector('.nobifashion_brand_faq_icon');
        if (icon) {
          icon.textContent = item.classList.contains('nobifashion_brand_faq_open') ? '⌃' : '⌄';
        }
      });
    });
  }

  // 9. Toast thông báo
  let toastTimer = null;
  function nobifashion_brand_toast(message) {
    const toast = document.getElementById('nobifashion_brand_toast');
    if (!toast) return;

    toast.textContent = message;
    toast.classList.add('nobifashion_brand_toast_visible');
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('nobifashion_brand_toast_visible');
    }, 2000);
  }
});
