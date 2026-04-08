// Image Lightbox Gallery
(function() {
    const lightbox = document.getElementById('nobifashion_image_lightbox');
    const lightboxImage = document.getElementById('nobifashion_lightbox_image');
    const lightboxClose = document.querySelector('.nobifashion_lightbox_close');
    const lightboxPrev = document.querySelector('.nobifashion_lightbox_prev');
    const lightboxNext = document.querySelector('.nobifashion_lightbox_next');
    const lightboxOverlay = document.querySelector('.nobifashion_lightbox_overlay');
    const lightboxThumbnails = document.querySelectorAll('.nobifashion_lightbox_thumbnail');
    const lightboxDownload = document.getElementById('nobifashion_lightbox_download');
    const zoomInBtn = document.querySelector('.nobifashion_lightbox_zoom_in');
    const zoomOutBtn = document.querySelector('.nobifashion_lightbox_zoom_out');
    const resetBtn = document.querySelector('.nobifashion_lightbox_reset');
    
    let currentIndex = 0;
    let images = [];
    let currentScale = 1;
    let currentTranslateX = 0;
    let currentTranslateY = 0;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let initialDistance = 0;
    let initialScale = 1;
    
    // Swipe/Drag variables
    let swipeStartX = 0;
    let swipeStartY = 0;
    let swipeCurrentX = 0;
    let swipeCurrentY = 0;
    let isSwiping = false;
    let swipeOffset = 0;
    let isChangingImage = false;

    // Collect all images
    function initImages() {
        images = [];
        const mainImage = document.querySelector('.nobifashion_single_info_images_main_image');
        const galleryImages = document.querySelectorAll('.nobifashion_single_info_images_gallery_image');
        
        if (mainImage) {
            images.push({
                src: mainImage.getAttribute('src') || mainImage.getAttribute('data-default-src'),
                alt: mainImage.getAttribute('alt') || ''
            });
        }
        
        galleryImages.forEach(img => {
            const src = img.getAttribute('src') || img.getAttribute('data-src');
            if (src && !images.some(i => i.src === src)) {
                images.push({
                    src: src,
                    alt: img.getAttribute('alt') || ''
                });
            }
        });
    }

    // Open lightbox
    function openLightbox(index = 0) {
        if (images.length === 0) return;
        
        currentIndex = Math.max(0, Math.min(index, images.length - 1));
        updateImage();
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
        resetZoom();
    }

    // Close lightbox
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
        resetZoom();
    }

    // Update displayed image with smooth transition
    function updateImage(animate = true) {
        if (!lightboxImage || !images[currentIndex] || isChangingImage) return;
        
        isChangingImage = true;
        
        if (animate && lightboxImage.src) {
            // Fade out
            lightboxImage.style.opacity = '0';
            lightboxImage.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                // Change image
                lightboxImage.src = images[currentIndex].src;
                lightboxImage.alt = images[currentIndex].alt || '';
                if (lightboxDownload) {
                    lightboxDownload.href = images[currentIndex].src;
                    lightboxDownload.download = images[currentIndex].src.split('/').pop();
                }
                
                // Update thumbnails
                lightboxThumbnails.forEach((thumb, index) => {
                    thumb.classList.toggle('active', index === currentIndex);
                });
                
                resetZoom();
                
                // Fade in
                setTimeout(() => {
                    lightboxImage.style.opacity = '1';
                    lightboxImage.style.transform = 'scale(1)';
                    isChangingImage = false;
                }, 50);
            }, 150);
        } else {
            // No animation for first load
            lightboxImage.src = images[currentIndex].src;
            lightboxImage.alt = images[currentIndex].alt || '';
            if (lightboxDownload) {
                lightboxDownload.href = images[currentIndex].src;
                lightboxDownload.download = images[currentIndex].src.split('/').pop();
            }
            
            lightboxThumbnails.forEach((thumb, index) => {
                thumb.classList.toggle('active', index === currentIndex);
            });
            
            resetZoom();
            isChangingImage = false;
        }
    }

    // Navigate
    function prevImage() {
        if (images.length === 0) return;
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        updateImage();
    }

    function nextImage() {
        if (images.length === 0) return;
        currentIndex = (currentIndex + 1) % images.length;
        updateImage();
    }

    // Zoom functions
    function zoomIn() {
        currentScale = Math.min(currentScale * 1.5, 5);
        applyTransform();
    }

    function zoomOut() {
        currentScale = Math.max(currentScale / 1.5, 1);
        applyTransform();
    }

    function resetZoom() {
        currentScale = 1;
        currentTranslateX = 0;
        currentTranslateY = 0;
        applyTransform();
    }

    function applyTransform() {
        if (!lightboxImage) return;
        requestAnimationFrame(() => {
            lightboxImage.style.transform = `translate(${currentTranslateX}px, ${currentTranslateY}px) scale(${currentScale})`;
        });
    }
    
    function applySwipeTransform(offset) {
        if (!lightboxImage || isChangingImage) return;
        requestAnimationFrame(() => {
            lightboxImage.style.transform = `translate(${offset}px, 0) scale(${currentScale})`;
            lightboxImage.style.opacity = Math.max(0.3, 1 - Math.abs(offset) / window.innerWidth);
        });
    }

    // Drag to pan
    function startDrag(e) {
        if (currentScale <= 1) return;
        isDragging = true;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        startX = clientX - currentTranslateX;
        startY = clientY - currentTranslateY;
    }

    function drag(e) {
        if (!isDragging || currentScale <= 1) return;
        e.preventDefault();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        currentTranslateX = clientX - startX;
        currentTranslateY = clientY - startY;
        applyTransform();
    }

    function endDrag() {
        isDragging = false;
    }

    // Pinch zoom for mobile
    function handlePinch(e) {
        if (e.touches.length !== 2) return;
        e.preventDefault();
        
        const touch1 = e.touches[0];
        const touch2 = e.touches[1];
        const distance = Math.hypot(
            touch2.clientX - touch1.clientX,
            touch2.clientY - touch1.clientY
        );
        
        if (initialDistance === 0) {
            initialDistance = distance;
            initialScale = currentScale;
        } else {
            const scale = initialScale * (distance / initialDistance);
            currentScale = Math.max(1, Math.min(scale, 5));
            applyTransform();
        }
    }

    function endPinch() {
        initialDistance = 0;
    }

    // Event listeners
    if (lightbox) {
        // Click main image to open
        const mainImage = document.querySelector('.nobifashion_single_image_clickable');
        if (mainImage) {
            mainImage.addEventListener('click', function() {
                initImages();
                const mainSrc = this.getAttribute('src') || this.getAttribute('data-default-src');
                const index = images.findIndex(img => img.src === mainSrc);
                openLightbox(index >= 0 ? index : 0);
            });
        }

        // Close buttons
        if (lightboxClose) {
            lightboxClose.addEventListener('click', closeLightbox);
        }
        if (lightboxOverlay) {
            lightboxOverlay.addEventListener('click', closeLightbox);
        }

        // Navigation
        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', (e) => {
                e.stopPropagation();
                prevImage();
            });
        }
        if (lightboxNext) {
            lightboxNext.addEventListener('click', (e) => {
                e.stopPropagation();
                nextImage();
            });
        }

        // Thumbnails
        lightboxThumbnails.forEach((thumb, index) => {
            thumb.addEventListener('click', (e) => {
                e.stopPropagation();
                currentIndex = index;
                updateImage();
            });
        });

        // Zoom controls
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                zoomIn();
            });
        }
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                zoomOut();
            });
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                resetZoom();
            });
        }

        // Image interactions
        if (lightboxImage) {
            const imageWrapper = lightboxImage.parentElement;
            
            // Click to zoom (desktop) - only if not swiping
            lightboxImage.addEventListener('click', function(e) {
                if (!isSwiping && currentScale === 1) {
                    zoomIn();
                } else if (!isSwiping && currentScale > 1) {
                    resetZoom();
                }
            });

            // Drag to pan (only when zoomed)
            imageWrapper.addEventListener('mousedown', function(e) {
                if (currentScale > 1 && e.button === 0) {
                    startDrag(e);
                }
            });
            
            document.addEventListener('mousemove', function(e) {
                if (isDragging && currentScale > 1) {
                    drag(e);
                }
            });
            
            document.addEventListener('mouseup', function(e) {
                if (isDragging) {
                    endDrag();
                }
            });

            // Touch events for zoom/pan (only when zoomed)
            imageWrapper.addEventListener('touchstart', function(e) {
                if (e.touches.length === 1 && currentScale > 1) {
                    startDrag(e);
                } else if (e.touches.length === 2) {
                    endDrag();
                    handlePinch(e);
                }
            });
            
            imageWrapper.addEventListener('touchmove', function(e) {
                if (e.touches.length === 1 && currentScale > 1) {
                    drag(e);
                } else if (e.touches.length === 2) {
                    handlePinch(e);
                }
            });
            
            imageWrapper.addEventListener('touchend', function(e) {
                endDrag();
                if (e.touches.length < 2) {
                    endPinch();
                }
            });
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('active')) return;
            
            switch(e.key) {
                case 'Escape':
                    closeLightbox();
                    break;
                case 'ArrowLeft':
                    prevImage();
                    break;
                case 'ArrowRight':
                    nextImage();
                    break;
                case '+':
                case '=':
                    zoomIn();
                    break;
                case '-':
                    zoomOut();
                    break;
                case '0':
                    resetZoom();
                    break;
            }
        });

        // Smooth swipe/drag gestures for both touch and mouse
        if (lightboxImage) {
            const imageWrapper = lightboxImage.parentElement;
            let swipeStartTime = 0;
            
            // Touch swipe events (only when not zoomed)
            imageWrapper.addEventListener('touchstart', function(e) {
                if (e.touches.length === 1 && currentScale === 1 && !isChangingImage) {
                    isSwiping = true;
                    swipeStartX = e.touches[0].clientX;
                    swipeStartY = e.touches[0].clientY;
                    swipeCurrentX = swipeStartX;
                    swipeOffset = 0;
                    swipeStartTime = Date.now();
                    lightboxImage.style.transition = 'none';
                }
            }, { passive: true });

            imageWrapper.addEventListener('touchmove', function(e) {
                if (isSwiping && e.touches.length === 1 && currentScale === 1 && !isChangingImage) {
                    e.preventDefault();
                    swipeCurrentX = e.touches[0].clientX;
                    swipeCurrentY = e.touches[0].clientY;
                    
                    // Only swipe horizontally if horizontal movement is greater
                    const deltaX = swipeCurrentX - swipeStartX;
                    const deltaY = Math.abs(swipeCurrentY - swipeStartY);
                    
                    if (Math.abs(deltaX) > deltaY || Math.abs(deltaX) > 10) {
                        swipeOffset = deltaX;
                        applySwipeTransform(swipeOffset);
                    }
                }
            }, { passive: false });

            imageWrapper.addEventListener('touchend', function(e) {
                if (isSwiping && currentScale === 1 && !isChangingImage) {
                    isSwiping = false;
                    lightboxImage.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    
                    const swipeTime = Date.now() - swipeStartTime;
                    const swipeThreshold = window.innerWidth * 0.15;
                    const velocity = Math.abs(swipeOffset) / Math.max(swipeTime, 1);
                    
                    if (Math.abs(swipeOffset) > swipeThreshold || (velocity > 0.3 && Math.abs(swipeOffset) > 30)) {
                        if (swipeOffset > 0) {
                            prevImage();
                        } else {
                            nextImage();
                        }
                    } else {
                        // Snap back
                        swipeOffset = 0;
                        applySwipeTransform(0);
                        setTimeout(() => {
                            lightboxImage.style.opacity = '1';
                        }, 300);
                    }
                }
            }, { passive: true });

            // Mouse drag swipe events (only when not zoomed)
            imageWrapper.addEventListener('mousedown', function(e) {
                if (currentScale === 1 && !isChangingImage && e.button === 0) {
                    isSwiping = true;
                    swipeStartX = e.clientX;
                    swipeStartY = e.clientY;
                    swipeCurrentX = swipeStartX;
                    swipeOffset = 0;
                    swipeStartTime = Date.now();
                    lightboxImage.style.transition = 'none';
                    lightboxImage.style.cursor = 'grabbing';
                    e.preventDefault();
                }
            });

            document.addEventListener('mousemove', function(e) {
                if (isSwiping && currentScale === 1 && !isChangingImage) {
                    swipeCurrentX = e.clientX;
                    swipeCurrentY = e.clientY;
                    
                    const deltaX = swipeCurrentX - swipeStartX;
                    const deltaY = Math.abs(swipeCurrentY - swipeStartY);
                    
                    if (Math.abs(deltaX) > deltaY || Math.abs(deltaX) > 10) {
                        swipeOffset = deltaX;
                        applySwipeTransform(swipeOffset);
                    }
                }
            });

            document.addEventListener('mouseup', function(e) {
                if (isSwiping && currentScale === 1 && !isChangingImage) {
                    isSwiping = false;
                    lightboxImage.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    lightboxImage.style.cursor = 'grab';
                    
                    const swipeTime = Date.now() - swipeStartTime;
                    const swipeThreshold = window.innerWidth * 0.15;
                    const velocity = Math.abs(swipeOffset) / Math.max(swipeTime, 1);
                    
                    if (Math.abs(swipeOffset) > swipeThreshold || (velocity > 0.3 && Math.abs(swipeOffset) > 30)) {
                        if (swipeOffset > 0) {
                            prevImage();
                        } else {
                            nextImage();
                        }
                    } else {
                        swipeOffset = 0;
                        applySwipeTransform(0);
                        setTimeout(() => {
                            lightboxImage.style.opacity = '1';
                        }, 300);
                    }
                }
            });

            imageWrapper.addEventListener('mouseleave', function(e) {
                if (isSwiping && currentScale === 1 && !isChangingImage) {
                    isSwiping = false;
                    lightboxImage.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    lightboxImage.style.cursor = 'grab';
                    swipeOffset = 0;
                    applySwipeTransform(0);
                    setTimeout(() => {
                        lightboxImage.style.opacity = '1';
                    }, 300);
                }
            });
        }
    }
})();

// Tabs mĂ´ táº£
const tabButtons = document.querySelectorAll(
    ".nobifashion_single_desc_button button"
);
const tabContents = document.querySelectorAll(
    ".nobifashion_single_desc_tabs > div"
);

tabButtons.forEach((btn, i) => {
    btn.addEventListener("click", () => {
        tabButtons.forEach((b) =>
            b.classList.remove("nobifashion_single_desc_button_active")
        );
        tabContents.forEach((tab) =>
            tab.classList.remove("nobifashion_single_desc_tabs_active")
        );
        btn.classList.add("nobifashion_single_desc_button_active");
        tabContents[i].classList.add("nobifashion_single_desc_tabs_active");
    });
});
if (tabButtons[0]) {
    tabButtons[0]?.click();
}

function tabReview() {
    if (tabButtons[2]) {
        tabButtons[2]?.click();
    }
}

function tabSizeGuide() {
    if (tabButtons[1]) {
        tabButtons[1]?.click();
    }
}

// Click áº£nh con => áº£nh chĂ­nh
const galleryImages = document.querySelectorAll(
    ".nobifashion_single_info_images_gallery_image"
);
const mainIMG = document.querySelector(".nobifashion_single_info_images_main_image");
const galleryArrow = document.querySelector(".nobifashion_single_info_gallery_arrow");
const galleryThumbsContainer = document.querySelector(".nobifashion_single_info_gallery_thumbs");

function scrollGalleryThumbIntoView(activeThumb, options = {}) {
    if (!activeThumb || !galleryThumbsContainer) {
        return;
    }

    const behavior = options.behavior || "smooth";
    const align = options.align || "center";

    if (window.matchMedia("(max-width: 767px)").matches) {
        activeThumb.scrollIntoView({
            behavior,
            block: "nearest",
            inline: align === "start" ? "start" : "center",
        });
        return;
    }

    let targetTop = activeThumb.offsetTop - 10;

    if (align !== "start") {
        targetTop = activeThumb.offsetTop - (galleryThumbsContainer.clientHeight - activeThumb.offsetHeight) / 2;
    }

    galleryThumbsContainer.scrollTo({
        top: Math.max(0, targetTop),
        behavior,
    });
}

function setActiveGalleryImage(activeThumb, options = {}) {
    if (!activeThumb) {
        return;
    }

    const newSrc = activeThumb.dataset.src || activeThumb.src;
    if (newSrc && mainIMG) {
        mainIMG.setAttribute("src", newSrc);
        mainIMG.setAttribute("alt", activeThumb.getAttribute("alt") || mainIMG.getAttribute("alt") || "");
        mainIMG.setAttribute("title", activeThumb.getAttribute("title") || mainIMG.getAttribute("title") || "");
    }

    galleryImages.forEach((img) =>
        img.classList.remove("nobifashion_single_info_images_gallery_image_active")
    );

    activeThumb.classList.add("nobifashion_single_info_images_gallery_image_active");
    scrollGalleryThumbIntoView(activeThumb, options);
}

galleryImages.forEach((img) => {
    img.addEventListener("click", () => {
        setActiveGalleryImage(img, { behavior: "smooth", align: "center" });
    });
});

if (galleryArrow && galleryImages.length > 1) {
    galleryArrow.addEventListener("click", () => {
        const items = Array.from(galleryImages);
        const currentIndex = items.findIndex((img) =>
            img.classList.contains("nobifashion_single_info_images_gallery_image_active")
        );
        const nextIndex = currentIndex >= 0
            ? (currentIndex + 1) % items.length
            : 0;

        setActiveGalleryImage(items[nextIndex], { behavior: "smooth", align: "start" });
    });
}

const initialActiveGalleryImage = Array.from(galleryImages).find((img) =>
    img.classList.contains("nobifashion_single_info_images_gallery_image_active")
);

if (initialActiveGalleryImage) {
    scrollGalleryThumbIntoView(initialActiveGalleryImage, { behavior: "auto", align: "start" });
}

document
    .querySelectorAll(".nobifashion_single_info_voucher_code_item, .nobifashion_single_info_voucher_tag[data-code]")
    ?.forEach((item) => {
        item.addEventListener("click", () => {
            const voucherCode = item.dataset.code || item.textContent.trim();

            navigator.clipboard
                .writeText(voucherCode)
                .then(() =>
                    showCustomToast(
                        "M\u00e3 voucher \u0111\u00e3 \u0111\u01b0\u1ee3c sao ch\u00e9p v\u00e0o clipboard!",
                        "info"
                    )
                )
                .catch((error) => {
                    console.error("Error:", error);
                    showCustomToast(
                        "C\u00f3 l\u1ed7i x\u1ea3y ra khi sao ch\u00e9p m\u00e3 voucher.",
                        "error"
                    );
                });
        });
    });

const requiredAttrKeys = Array.from(
    new Set((variants || []).flatMap((variant) => Object.keys(variant.attrs || {})))
);
let maxStock = variants.length > 0
    ? Math.max(...variants.map((variant) => parseInt(variant.stock, 10) || 0))
    : 0;

function increaseQty() {
    if (!hasSelection() || !findVariant()) {
        showCustomToast("Vui l\u00f2ng ch\u1ecdn \u0111\u1ee7 bi\u1ebfn th\u1ec3 tr\u01b0\u1edbc khi ch\u1ecdn s\u1ed1 l\u01b0\u1ee3ng.", "warning");
        return;
    }
    const valueEl = document.querySelector(".nobifashion_single_info_specifications_actions_value");
    let current = parseInt(valueEl.textContent, 10) || 0;

    if (current < maxStock) {
        current++;
        valueEl.textContent = current;
        document.querySelector("input[name='quantity']").value = current;
    } else {
        showCustomToast(`S\u1ed1 l\u01b0\u1ee3ng t\u1ed1i \u0111a trong kho l\u00e0 ${maxStock}`, "warning");
    }
}

function decreaseQty() {
    if (!hasSelection() || !findVariant()) {
        showCustomToast("Vui l\u00f2ng ch\u1ecdn \u0111\u1ee7 bi\u1ebfn th\u1ec3 tr\u01b0\u1edbc khi ch\u1ecdn s\u1ed1 l\u01b0\u1ee3ng.", "warning");
        return;
    }
    const valueEl = document.querySelector(".nobifashion_single_info_specifications_actions_value");
    let current = parseInt(valueEl.textContent, 10) || 0;

    if (current > 1) {
        current--;
        valueEl.textContent = current;

        const input = document.querySelector("input[name='quantity']");
        if (input) input.value = current;
    } else {
        showCustomToast("S\u1ed1 l\u01b0\u1ee3ng t\u1ed1i thi\u1ec3u l\u00e0 1", "warning");
    }
}

function countDownFlashSale(endTimestamp) {
    const daysEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_days"
    );
    const hoursEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_house"
    );
    const minutesEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_minute"
    );
    const secondsEl = document.querySelector(
        ".nobifashion_single_info_specifications_box_second"
    );
    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;

    const endTime = new Date(endTimestamp); // timestamp ms

    function updateCountdown() {
        const now = new Date();
        const distance = endTime.getTime() - now.getTime();

        if (distance <= 0) {
            // Háº¿t háº¡n â†’ reload 1 láº§n
            location.reload();
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor(
            (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        updateBox(daysEl, days);
        updateBox(hoursEl, hours);
        updateBox(minutesEl, minutes);
        updateBox(secondsEl, seconds);
    }

    function updateBox(el, newValue) {
        const oldValue = el.textContent;
        const formatted = newValue.toString().padStart(2, "0");

        if (oldValue !== formatted) {
            el.textContent = formatted;
            el.classList.remove("animate");
            void el.offsetWidth; // trigger reflow
            el.classList.add("animate");
        }
    }

    // âœ… cháº¡y ngay khi load
    updateCountdown();

    // Sau Ä‘Ă³ láº·p láº¡i má»—i giĂ¢y
    setInterval(updateCountdown, 1000);
}


if(typeof endTime  !== "undefined") {
    // Truyá»n timestamp ms
    countDownFlashSale(endTime);
}

function showPopupVoucher() {
    const popup = document.querySelector(
        ".nobifashion_main_show_popup_voucher_overlay"
    );
    const closeBtn = document.querySelector(
        ".nobifashion_main_show_popup_voucher_close"
    );
    const codeEl = document.querySelectorAll(
        ".nobifashion_main_show_popup_voucher_code"
    );

    if (!popup || !closeBtn || codeEl.length === 0) return;

    popup.style.display = "flex";

    closeBtn.addEventListener("click", () => {
        popup.style.display = "none";
    });

    popup.addEventListener("click", (e) => {
        if (e.target === popup) {
            popup.style.display = "none";
        }
    });

    codeEl.forEach((el) => {
        el.addEventListener("click", () => {
            if (el.dataset.copied === "true") return;

            const originalText = el.textContent.trim();

            navigator.clipboard
                .writeText(originalText)
                .then(() => {
                    showCustomToast("M\u00e3 voucher \u0111\u00e3 \u0111\u01b0\u1ee3c sao ch\u00e9p!", "info");
                    el.textContent = "\u0110\u00e3 sao ch\u00e9p!";
                    el.dataset.copied = "true";

                    setTimeout(() => {
                        el.textContent = originalText;
                        el.dataset.copied = "false";
                    }, 5000);
                })
                .catch((err) => {
                    console.error("Copy th?t b?i:", err);
                });
        });
    });
}

setTimeout(() => {
    showPopupVoucher();
}, 10000);



let selectedAttrs = {};
let hasInteracted = false;

function hasSelection() {
    return requiredAttrKeys.length > 0
        && requiredAttrKeys.every((key) => !!selectedAttrs[key]);
}

function findVariant() {
    if (!hasSelection()) return null;

    return variants.find((variant) =>
        requiredAttrKeys.every((key) => variant.attrs?.[key] === selectedAttrs[key])
    );
}

function refreshButtons() {
    document.querySelectorAll("[data-attr-key]").forEach(btn => {
        const key = btn.dataset.attrKey;
        const val = btn.dataset.attrValue;

        let temp = { ...selectedAttrs, [key]: val };

        let exists = variants.some(v =>
            Object.entries(temp).every(([k, vval]) => v.attrs[k] === vval)
        );

        btn.disabled = !exists;
        btn.classList.toggle("disabled", !exists);
    });
}

function toggleActionButtons() {
    const cartBtn = document.querySelector(".nobifashion_single_info_specifications_actions_cart");
    const buyBtn  = document.querySelector(".nobifashion_single_info_specifications_actions_buy");
    const form    = document.querySelector(".nobifashion_single_info_specifications_actions"); // form tháº­t

    if (!cartBtn || !buyBtn || !form) return;

    const variant = findVariant();

    if (!hasSelection() || !variant) {
        setDisabled([cartBtn, buyBtn], true);
        removeVariantInput(form);
        return;
    }

    if (variant.stock > 0) {
        setDisabled([cartBtn, buyBtn], false);
        createOrUpdateVariantInput(form, variant);
    } else {
        setDisabled([cartBtn, buyBtn], true);
        removeVariantInput(form);
    }
}

function setDisabled(btns, disabled) {
    btns.forEach(btn => {
        btn.disabled = disabled;
        btn.classList.toggle("disabled", disabled);
    });
}

function createOrUpdateVariantInput(form, variant) { 
    let input = form.querySelector("input[name='variant_id']");
    if (!input) { input = document.createElement("input"); 
        input.type = "hidden"; 
        input.name = "variant_id"; 
        form.appendChild(input); 
    } 
    input.value = variant.id; 
} 

function removeVariantInput(form) {
    const input = form.querySelector("input[name='variant_id']");
    if (input) { 
        input.remove(); 
    } 
}

function getMinPriceVariant() {
    if (!variants || variants.length === 0) return null;
    return variants.reduce((min, v) =>
        parseFloat(v.price) < parseFloat(min.price) ? v : min
    );
}

function formatCurrency(value) {
    const numericValue = Number(value) || 0;
    return new Intl.NumberFormat("vi-VN").format(numericValue);
}

const CURRENCY_SUFFIX = "\u0111";

function updatePriceDisplay(currentPrice, originalPrice = 0) {
    const priceEl = document.querySelector(".nobifashion_single_info_specifications_price");
    if (!priceEl) return;

    const currentEl = priceEl.querySelector(".nobifashion_single_info_price_current");
    const originalEl = priceEl.querySelector(".nobifashion_single_info_price_original");
    const badgeEl = priceEl.querySelector(".nobifashion_single_info_price_badge");
    const savingEl = priceEl.querySelector(".nobifashion_single_info_saving");
    const savingAmountEl = priceEl.querySelector(".save-amount");
    const hasDiscount = originalPrice > currentPrice && currentPrice > 0;
    const savedAmount = hasDiscount ? originalPrice - currentPrice : 0;

    if (currentEl) {
        currentEl.textContent = currentPrice > 0 ? `${formatCurrency(currentPrice)}${CURRENCY_SUFFIX}` : "-";
    }

    if (originalEl) {
        originalEl.textContent = hasDiscount ? `${formatCurrency(originalPrice)}${CURRENCY_SUFFIX}` : "";
        originalEl.classList.toggle("is-hidden", !hasDiscount);
    }

    if (badgeEl) {
        badgeEl.textContent = hasDiscount
            ? `-${Math.round(((originalPrice - currentPrice) / originalPrice) * 100)}%`
            : "";
        badgeEl.classList.toggle("is-hidden", !hasDiscount);
    }

    if (savingAmountEl) {
        savingAmountEl.textContent = `${formatCurrency(savedAmount)}${CURRENCY_SUFFIX}`;
    }

    if (savingEl) {
        savingEl.classList.toggle("is-hidden", !hasDiscount);
    }
}

function updateStockDisplay({ text, note, percent }) {
    const stockEl = document.querySelector("#product-stock");
    const noteEl = document.querySelector("#product-stock-note");
    const progressEl = document.querySelector("#product-stock-progress");

    if (stockEl && typeof text === "string") {
        stockEl.innerHTML = text;
    }

    if (noteEl && typeof note === "string") {
        noteEl.textContent = note;
    }

    if (progressEl) {
        const fallbackWidth = parseInt(progressEl.dataset.baseWidth || "0", 10);
        const nextWidth = Number.isFinite(percent) ? percent : fallbackWidth;
        progressEl.style.width = `${Math.max(0, Math.min(nextWidth, 100))}%`;
    }
}

function resetSelectedLabels() {
    document.querySelectorAll("[id^='selected-']").forEach((element) => {
        const key = element.id.replace("selected-", "");
        element.textContent = selectedAttrs[key] || "-";
    });
}


function updateInfo() {
    const variant = findVariant();
    const minPriceVariant = getMinPriceVariant();
    const valueEl = document.querySelector(
        ".nobifashion_single_info_specifications_actions_value"
    );
    const priceEl = document.querySelector(".nobifashion_single_info_specifications_price");
    const form = document.querySelector(".nobifashion_single_info_specifications_actions");
    const baseCurrentPrice = parseInt(priceEl?.dataset.baseCurrentPrice || "0", 10);
    const baseOriginalPrice = parseInt(priceEl?.dataset.baseOriginalPrice || "0", 10);
    const stockNoteEl = document.querySelector("#product-stock-note");
    const baseStockNote = stockNoteEl?.dataset.defaultNote || "";
    const progressEl = document.querySelector("#product-stock-progress");
    const baseStockWidth = parseInt(progressEl?.dataset.baseWidth || "0", 10);
    const highestStock = variants.length > 0
        ? Math.max(...variants.map((item) => parseInt(item.stock, 10) || 0))
        : 0;

    resetSelectedLabels();

    if (hasSelection() && variant) {
        const displayPrice = parseInt(variant.price, 10) || parseInt(minPriceVariant?.price, 10) || 0;
        const referenceOriginalPrice = baseOriginalPrice > displayPrice ? baseOriginalPrice : 0;
        const stockCount = parseInt(variant.stock, 10) || 0;

        updatePriceDisplay(displayPrice, referenceOriginalPrice);
        maxStock = stockCount;
        updateStockDisplay({
            text: stockCount > 0
                ? `C\u00f2n <span style="color: green;">${stockCount}</span> s\u1ea3n ph\u1ea9m`
                : "<span style='color:red;'>H\u1ebft h\u00e0ng</span>",
            note: stockCount > 0
                ? (stockCount < 5 ? "S\u1ed1 l\u01b0\u1ee3ng c\u00f2n r\u1ea5t \u00edt, n\u00ean ch\u1ed1t \u0111\u01a1n s\u1edbm." : "S\u1ea3n ph\u1ea9m \u0111ang s\u1eb5n h\u00e0ng, c\u00f3 th\u1ec3 \u0111\u1eb7t mua ngay.")
                : "Bi\u1ebfn th\u1ec3 n\u00e0y hi\u1ec7n \u0111ang t\u1ea1m h\u1ebft h\u00e0ng.",
            percent: stockCount > 0 && highestStock > 0
                ? Math.max(8, Math.round((stockCount / highestStock) * 100))
                : 0,
        });

        const mainImage = document.querySelector('.nobifashion_single_info_images_main_image');
        if (mainImage && variant.image_url) {
            const imagePath = `/clients/assets/img/clothes/${variant.image_url}`;
            const matchingThumb = Array.from(galleryImages).find((img) =>
                img.src.includes(variant.image_url) || img.dataset.src?.includes(variant.image_url)
            );

            if (matchingThumb) {
                setActiveGalleryImage(matchingThumb, { behavior: "smooth", align: "start" });
            } else {
                mainImage.src = imagePath;
            }
        } else if (mainImage && !variant.image_url) {
            const defaultImage = mainImage.dataset.defaultSrc || '/clients/assets/img/clothes/no-image.webp';
            mainImage.src = defaultImage;
        }

        let qtyInput = form?.querySelector('input[name="quantity"]');
        let currentQty = parseInt(qtyInput?.value || valueEl?.textContent || 1, 10);
        currentQty = Math.min(Math.max(currentQty, 1), Math.max(stockCount, 1));

        if (valueEl) {
            valueEl.textContent = currentQty;
        }

        if (form && !qtyInput) {
            qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = 'quantity';
            form.appendChild(qtyInput);
        }

        if (qtyInput) {
            qtyInput.value = currentQty;
        }
    } else {
        updatePriceDisplay(baseCurrentPrice, baseOriginalPrice);
        updateStockDisplay({
            text: requiredAttrKeys.length > 0
                ? 'Ch\u1ecdn bi\u1ebfn th\u1ec3 \u0111\u1ec3 xem t\u1ed3n kho'
                : (maxStock > 0 ? `C\u00f2n ${maxStock} s\u1ea3n ph\u1ea9m` : 'T\u1ea1m h\u1ebft h\u00e0ng'),
            note: baseStockNote,
            percent: baseStockWidth,
        });

        if (valueEl) {
            valueEl.textContent = '1';
        }
    }

    refreshButtons();
    toggleActionButtons();
}


// Click ch?n attribute
document.querySelectorAll("[data-attr-key]").forEach(btn => {
    btn.addEventListener("click", () => {
        if (btn.classList.contains("disabled")) return;

        const key = btn.dataset.attrKey;
        const value = btn.dataset.attrValue;

        if (btn.classList.contains("active")) {
            btn.classList.remove("active");
            delete selectedAttrs[key];
        } else {
            document.querySelectorAll(`[data-attr-key="${key}"]`).forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            selectedAttrs[key] = value;
        }

        hasInteracted = true;
        updateInfo();
    });
});

// Submit (Add to cart / Buy now)
[".nobifashion_single_info_specifications_actions_cart", 
 ".nobifashion_single_info_specifications_actions_buy"].forEach(selector => {
    const btn = document.querySelector(selector);
    if (!btn) return;

    btn.addEventListener("click", e => {
        const variant = findVariant();

        if (!hasSelection() || !variant) {
            e.preventDefault();
            showCustomToast("Vui l\u00f2ng ch\u1ecdn \u0111\u1ea7y \u0111\u1ee7 c\u00e1c thu\u1ed9c t\u00ednh s\u1ea3n ph\u1ea9m.", "warning");
            return;
        }

        if (variant.stock <= 0) {
            e.preventDefault();
            showCustomToast("Bi\u1ebfn th\u1ec3 n\u00e0y \u0111\u00e3 h\u1ebft h\u00e0ng.", "error");
            return;
        }

        showCustomToast("\u0110ang g\u1eedi request...", "success");
    });
});

// init
updateInfo();
