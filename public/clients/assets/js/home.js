/* Home Page Specialized Controller - Nobi Fashion (Chỉ xử lý banner & gallery sản phẩm trang chủ) */
(() => {
  "use strict";

  const prefix = "nobifashion_home_";
  let page = document.querySelector(`.${prefix}page`);
  if (!page) {
    document.body.classList.add(`${prefix}page`);
    page = document.body;
  }

  const get = (name) => document.getElementById(prefix + name);
  const all = (selector, root = page) => root ? [...root.querySelectorAll(selector)] : [];
  const normalize = (value) =>
    (value || "")
      .normalize("NFD")
      .replace(/\p{Diacritic}/gu, "")
      .replace(/[đĐ]/g, "d")
      .toLowerCase()
      .trim();

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const mobileMedia = window.matchMedia("(max-width: 959px)");

  const safeJsonParse = (str, fallback = []) => {
    try {
      const parsed = JSON.parse(str);
      return Array.isArray(parsed) ? parsed : fallback;
    } catch {
      return fallback;
    }
  };

  const products = new Map();
  try {
    all("[data-nobifashion-product]").forEach((card) => {
      try {
        const id = card.dataset.nobifashionProduct || "";
        if (!id) return;
        const nameElem = card.querySelector(`.${prefix}product_name`);
        const name = nameElem ? nameElem.textContent.trim() : "";
        const galleryLink = card.querySelector(`.${prefix}product_gallery_link`);
        const href = galleryLink ? galleryLink.href : "#";
        const imgElem = card.querySelector(`.${prefix}product_image`);
        const image = imgElem ? imgElem.getAttribute("src") : "";
        const images = safeJsonParse(card.dataset.nobifashionImages, image ? [image] : []);
        const price = Number(card.dataset.nobifashionPrice) || 0;

        products.set(id, {
          id,
          name,
          card,
          price,
          href,
          image,
          images: images.length ? images : (image ? [image] : []),
          imageIndex: 0,
        });
      } catch (err) {}
    });
  } catch (err) {}

  // --- PRODUCT GALLERY & SWATCHES ---
  function updateGallery(product, index) {
    if (!product || !product.images || !product.images.length) return;
    const total = product.images.length;
    product.imageIndex = (index + total) % total;
    const image = product.card.querySelector(`.${prefix}product_image`);
    if (image && product.images[product.imageIndex]) {
      image.src = product.images[product.imageIndex];
    }
    const gallery = product.card.querySelector(`.${prefix}product_gallery`);
    if (gallery) {
      gallery.setAttribute(
        "aria-label",
        `${product.name}, ảnh ${product.imageIndex + 1} trên ${total}`,
      );
    }
    const dots = product.card.querySelector(`.${prefix}gallery_dots`);
    if (dots) {
      dots.replaceChildren();
      product.images.forEach((_, position) => {
        const dot = document.createElement("span");
        dot.className = `${prefix}gallery_dot`;
        dot.setAttribute("aria-current", String(position === product.imageIndex));
        dots.append(dot);
      });
      dots.hidden = total < 2;
    }
    all("[data-nobifashion-gallery]", product.card).forEach((button) => {
      button.hidden = total < 2;
    });
  }

  products.forEach((product) => {
    try {
      const gallery = product.card.querySelector(`.${prefix}product_gallery`);
      const swatches = product.card.querySelector(`.${prefix}swatches`);
      if (swatches) {
        const updateSwatches = () => {
          const prevArrow = product.card.querySelector('[data-nobifashion-swatch-scroll="-1"]');
          const nextArrow = product.card.querySelector('[data-nobifashion-swatch-scroll="1"]');
          if (prevArrow) prevArrow.hidden = swatches.scrollLeft < 2;
          if (nextArrow) {
            nextArrow.hidden =
              swatches.scrollLeft + swatches.clientWidth >= swatches.scrollWidth - 2;
          }
        };
        swatches.addEventListener("scroll", updateSwatches, { passive: true });
        if ("ResizeObserver" in window) {
          new ResizeObserver(updateSwatches).observe(swatches);
        } else {
          window.addEventListener("resize", updateSwatches, { passive: true });
        }
        updateSwatches();
      }

      let touchStart;
      let swiped = false;
      updateGallery(product, 0);

      if (gallery) {
        gallery.addEventListener("keydown", (event) => {
          if (
            event.target !== gallery ||
            !["ArrowLeft", "ArrowRight"].includes(event.key)
          )
            return;
          event.preventDefault();
          updateGallery(
            product,
            product.imageIndex + (event.key === "ArrowRight" ? 1 : -1),
          );
        });
        gallery.addEventListener(
          "touchstart",
          (event) => {
            if (event.touches?.[0]) {
              touchStart = {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
              };
            }
            swiped = false;
          },
          { passive: true },
        );
        gallery.addEventListener(
          "touchend",
          (event) => {
            if (!touchStart || !event.changedTouches?.[0]) return;
            const dx = event.changedTouches[0].clientX - touchStart.x;
            const dy = event.changedTouches[0].clientY - touchStart.y;
            if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
              updateGallery(product, product.imageIndex + (dx < 0 ? 1 : -1));
              swiped = true;
            }
            touchStart = null;
          },
          { passive: true },
        );
        gallery.addEventListener(
          "click",
          (event) => {
            if (swiped) {
              event.preventDefault();
              swiped = false;
            }
          },
          true,
        );
      }
    } catch (err) {}
  });

  // --- BANNER VIDEO CONTROLS ---
  const videos = all(`.${prefix}banner_video`);
  function syncVideoButton(video) {
    if (!video || !video.id) return;
    const button = page.querySelector(
      `[data-nobifashion-video-toggle="${video.id}"]`,
    );
    if (!button) return;
    button.setAttribute(
      "aria-label",
      video.paused ? "Phát video" : "Tạm dừng video",
    );
    button.setAttribute("aria-pressed", String(!video.paused));
    const path = button.querySelector("path");
    if (path) {
      path.setAttribute(
        "d",
        video.paused ? "m9 5 10 7-10 7Z" : "M9 6v12M15 6v12",
      );
    }
  }
  function videoSource(video) {
    if (!video) return;
    const src = mobileMedia.matches
      ? video.dataset.nobifashionVideoMobile
      : video.dataset.nobifashionVideoDesktop;
    if (src && video.getAttribute("src") !== src) {
      video.src = src;
      video.load();
    }
  }
  function playVideo(video) {
    if (!video) return;
    videoSource(video);
    video.muted = true;
    try {
      const playPromise = video.play();
      if (playPromise) playPromise.catch(() => syncVideoButton(video));
    } catch {}
  }
  function updateVideos() {
    const canPlay = !document.hidden && !all("dialog[open]").length;
    videos.forEach((video) => {
      if (
        canPlay &&
        video.dataset.nobifashionVisible === "true" &&
        video.dataset.nobifashionPaused !== "true" &&
        !reducedMotion.matches &&
        !navigator.connection?.saveData
      )
        playVideo(video);
      else {
        try { video.pause(); } catch {}
      }
    });
  }
  videos.forEach((video) => {
    video.addEventListener("play", () => syncVideoButton(video));
    video.addEventListener("pause", () => syncVideoButton(video));
    video.addEventListener("error", () => {
      video.hidden = true;
      syncVideoButton(video);
    });
    video.addEventListener("loadeddata", () => {
      video.hidden = false;
    });
  });
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(({ target, isIntersecting }) => {
          target.dataset.nobifashionVisible = String(isIntersecting);
        });
        updateVideos();
      },
      { threshold: 0.2 },
    );
    videos.forEach((video) => observer.observe(video));
  }
  mobileMedia.addEventListener("change", () => {
    videos.forEach((video) => {
      if (video.hasAttribute("src")) videoSource(video);
    });
    updateVideos();
  });
  reducedMotion.addEventListener("change", updateVideos);
  document.addEventListener("visibilitychange", updateVideos);

  // --- HEADER TONE ADAPTATION ON HOME ---
  let scrollPending = false;
  function updateHeaderTone() {
    scrollPending = false;
    const header = get("header");
    if (header) {
      const offset = header.offsetHeight / 2;
      const section = all("[data-nobifashion-tone]").find((item) => {
        const bounds = item.getBoundingClientRect();
        return bounds.height && bounds.top <= offset && bounds.bottom > offset;
      });
      header.dataset.tone = section?.dataset.nobifashionTone || "light";
    }
  }
  function scheduleHeaderTone() {
    if (!scrollPending) {
      scrollPending = true;
      requestAnimationFrame(updateHeaderTone);
    }
  }
  window.addEventListener("scroll", scheduleHeaderTone, { passive: true });
  window.addEventListener("resize", scheduleHeaderTone, { passive: true });
  scheduleHeaderTone();

  // --- HOME DELEGATE FOR GALLERY, SWATCH, VIDEO ---
  page.addEventListener("click", (event) => {
    const swatchArrow = event.target.closest(
      "[data-nobifashion-swatch-scroll]",
    );
    if (swatchArrow) {
      const swatches = swatchArrow.parentElement?.querySelector(
        `.${prefix}swatches`,
      );
      if (swatches) {
        swatches.scrollBy({
          left:
            swatches.clientWidth *
            0.75 *
            Number(swatchArrow.dataset.nobifashionSwatchScroll),
          behavior: reducedMotion.matches ? "instant" : "smooth",
        });
      }
      return;
    }
    const arrow = event.target.closest("[data-nobifashion-gallery]");
    if (arrow) {
      const prodCard = arrow.closest("[data-nobifashion-product]");
      if (prodCard) {
        const product = products.get(prodCard.dataset.nobifashionProduct);
        if (product) {
          updateGallery(
            product,
            product.imageIndex + Number(arrow.dataset.direction || 0),
          );
        }
      }
      return;
    }
    const color = event.target.closest("[data-nobifashion-color]");
    if (color) {
      const prodCard = color.closest("[data-nobifashion-product]");
      if (prodCard) {
        const product = products.get(prodCard.dataset.nobifashionProduct);
        if (product) {
          all("[data-nobifashion-color]", product.card).forEach((button) =>
            button.setAttribute("aria-pressed", String(button === color)),
          );
          if (color.dataset.nobifashionImages) {
            product.images = safeJsonParse(color.dataset.nobifashionImages, product.images);
          }
          all(
            `.${prefix}product_gallery_link, .${prefix}product_name a`,
            product.card,
          ).forEach((link) => {
            try {
              const destination = new URL(product.href, window.location.origin);
              if (color.dataset.nobifashionColor) {
                destination.searchParams.set(
                  "colorDisplayCode",
                  color.dataset.nobifashionColor,
                );
              }
              link.href = destination.href;
            } catch {}
          });
          updateGallery(product, 0);
        }
      }
      return;
    }
    const toggle = event.target.closest("[data-nobifashion-video-toggle]");
    if (toggle) {
      const video = document.getElementById(
        toggle.dataset.nobifashionVideoToggle,
      );
      if (video) {
        if (video.paused) {
          video.dataset.nobifashionPaused = "false";
          playVideo(video);
        } else {
          video.dataset.nobifashionPaused = "true";
          video.pause();
        }
      }
      return;
    }
  });

  /* ====================================================================
     NOBI FASHION - 3D COVERFLOW HERO BANNER MAIN CONTROLLER
     Class Prefix: nobifashion_home_banner_main_
     Pure Vanilla JS, Large Dots Pagination, GPU-accelerated
     ==================================================================== */
  function initNobifashionHomeBannerMain() {
      const carouselEl = document.getElementById('nobifashion_home_banner_main_carousel');
      if (!carouselEl) return;

      const stageEl = carouselEl.querySelector('.nobifashion_home_banner_main_stage');
      const cards = Array.from(carouselEl.querySelectorAll('.nobifashion_home_banner_main_card'));
      const prevBtn = document.getElementById('nobifashion_home_banner_main_prev_btn');
      const nextBtn = document.getElementById('nobifashion_home_banner_main_next_btn');
      const paginationDots = Array.from(carouselEl.parentElement.querySelectorAll('.nobifashion_home_banner_main_pagination_dot'));
      const ambientGlowEl = document.getElementById('nobifashion_home_banner_main_ambient_glow');

      if (!cards.length) return;

      let currentIndex = 0;
      let autoplayTimer = null;
      const total = cards.length;
      const AUTOPLAY_INTERVAL = 5000;

      const ambientColors = [
          'radial-gradient(circle, rgba(56, 189, 248, 0.35) 0%, rgba(14, 165, 233, 0.12) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(251, 146, 60, 0.35) 0%, rgba(249, 115, 22, 0.12) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(244, 114, 182, 0.35) 0%, rgba(236, 72, 153, 0.12) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(167, 139, 250, 0.35) 0%, rgba(139, 92, 246, 0.12) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(52, 211, 153, 0.35) 0%, rgba(16, 185, 129, 0.12) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(250, 204, 21, 0.35) 0%, rgba(234, 179, 8, 0.12) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(148, 163, 184, 0.40) 0%, rgba(100, 116, 139, 0.15) 50%, transparent 80%)',
          'radial-gradient(circle, rgba(225, 29, 72, 0.32) 0%, rgba(190, 18, 60, 0.12) 50%, transparent 80%)',
      ];

      function getResponsiveMetrics() {
          const width = window.innerWidth;
          if (width < 768) {
              return {
                  mode: 'mobile',
                  stepX: 140,
                  scaleStep: 0.15,
                  zStep: 80,
                  rotY: 8,
                  maxVisible: 1,
              };
          }
          if (width < 992) {
              return {
                  mode: 'tablet',
                  stepX: 160,
                  scaleStep: 0.14,
                  zStep: 90,
                  rotY: 12,
                  maxVisible: 2,
              };
          }
          if (width < 1200) {
              return {
                  mode: 'laptop',
                  stepX: 185,
                  scaleStep: 0.13,
                  zStep: 100,
                  rotY: 14,
                  maxVisible: 2,
              };
          }
          return {
              mode: 'desktop',
              stepX: 210,
              scaleStep: 0.13,
              zStep: 110,
              rotY: 16,
              maxVisible: 3,
          };
      }

      function update3DStage() {
          const metrics = getResponsiveMetrics();

          cards.forEach((card, index) => {
              let offset = index - currentIndex;
              while (offset > total / 2) offset -= total;
              while (offset < -total / 2) offset += total;

              const absOffset = Math.abs(offset);
              const isActive = offset === 0;

              if (isActive) {
                  card.classList.add('nobifashion_home_banner_main_card_active');
                  card.setAttribute('aria-hidden', 'false');
                  card.style.transform = 'translate3d(-50%, -50%, 0px) scale(1) rotateY(0deg)';
                  card.style.zIndex = '20';
                  card.style.opacity = '1';
                  card.style.filter = 'brightness(1) contrast(1)';
                  card.style.pointerEvents = 'auto';
              } else if (absOffset <= metrics.maxVisible) {
                  card.classList.remove('nobifashion_home_banner_main_card_active');
                  card.setAttribute('aria-hidden', 'true');

                  const dir = offset > 0 ? 1 : -1;
                  const posX = dir * (absOffset * metrics.stepX + (absOffset === 1 ? 35 : 15));
                  const posZ = -absOffset * metrics.zStep;
                  const scale = Math.max(0.45, 1 - absOffset * metrics.scaleStep);
                  const rotY = -dir * (metrics.rotY + (absOffset - 1) * 3);
                  const opacity = Math.max(0.3, 1 - absOffset * 0.22);
                  const brightness = Math.max(0.4, 0.85 - (absOffset - 1) * 0.18);

                  card.style.transform = `translate3d(calc(-50% + ${posX}px), -50%, ${posZ}px) scale(${scale}) rotateY(${rotY}deg)`;
                  card.style.zIndex = String(20 - absOffset);
                  card.style.opacity = String(opacity);
                  card.style.filter = `brightness(${brightness})`;
                  card.style.pointerEvents = 'auto';
              } else {
                  card.classList.remove('nobifashion_home_banner_main_card_active');
                  card.setAttribute('aria-hidden', 'true');
                  const dir = offset > 0 ? 1 : -1;
                  const posX = dir * (metrics.maxVisible * metrics.stepX + 160);
                  card.style.transform = `translate3d(calc(-50% + ${posX}px), -50%, -300px) scale(0.5)`;
                  card.style.zIndex = '0';
                  card.style.opacity = '0';
                  card.style.pointerEvents = 'none';
              }
          });

          // Update Large Dots Pagination
          paginationDots.forEach((dot, index) => {
              if (index === currentIndex) {
                  dot.classList.add('nobifashion_home_banner_main_pagination_dot_active');
                  dot.setAttribute('aria-current', 'true');
              } else {
                  dot.classList.remove('nobifashion_home_banner_main_pagination_dot_active');
                  dot.setAttribute('aria-current', 'false');
              }
          });

          // Update Ambient Glow
          if (ambientGlowEl) {
              const colorIdx = currentIndex % ambientColors.length;
              ambientGlowEl.style.background = ambientColors[colorIdx];
          }
      }

      function goToSlide(targetIndex) {
          if (targetIndex === currentIndex) return;
          currentIndex = (targetIndex + total) % total;
          update3DStage();
          resetAutoplay();
      }

      function nextSlide() {
          goToSlide(currentIndex + 1);
      }

      function prevSlide() {
          goToSlide(currentIndex - 1);
      }

      function startAutoplay() {
          stopAutoplay();
          autoplayTimer = setInterval(() => {
              if (!document.hidden) {
                  nextSlide();
              }
          }, AUTOPLAY_INTERVAL);
      }

      function stopAutoplay() {
          if (autoplayTimer) {
              clearInterval(autoplayTimer);
              autoplayTimer = null;
          }
      }

      function resetAutoplay() {
          stopAutoplay();
          startAutoplay();
      }

      // Card clicks: clicking any background slide switches it to the main slide
      cards.forEach((card, index) => {
          function selectThisCard(e) {
              if (index !== currentIndex) {
                  e.preventDefault();
                  e.stopPropagation();
                  goToSlide(index);
              }
          }

          // Use capture phase to intercept click before child elements / link navigation
          card.addEventListener('click', selectThisCard, true);

          const link = card.querySelector('.nobifashion_home_banner_main_card_link');
          if (link) {
              link.addEventListener('click', selectThisCard);
          }
      });

      // Prev / Next button clicks
      if (prevBtn) {
          prevBtn.addEventListener('click', function (e) {
              e.preventDefault();
              prevSlide();
          });
      }

      if (nextBtn) {
          nextBtn.addEventListener('click', function (e) {
              e.preventDefault();
              nextSlide();
          });
      }

      // Large Dots clicks
      paginationDots.forEach((dot) => {
          dot.addEventListener('click', function (e) {
              e.preventDefault();
              const targetIdx = parseInt(this.getAttribute('data-nobifashion-index'), 10);
              if (!isNaN(targetIdx)) {
                  goToSlide(targetIdx);
              }
          });
      });

      // Keyboard navigation
      carouselEl.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowLeft') {
              e.preventDefault();
              prevSlide();
          } else if (e.key === 'ArrowRight') {
              e.preventDefault();
              nextSlide();
          }
      });

      // Touch & Swipe Support
      let touchStartX = 0;
      let touchStartY = 0;
      let touchEndX = 0;
      let touchEndY = 0;

      stageEl.addEventListener('touchstart', function (e) {
          if (e.touches.length === 1) {
              touchStartX = e.touches[0].clientX;
              touchStartY = e.touches[0].clientY;
              touchEndX = touchStartX;
              touchEndY = touchStartY;
              stopAutoplay();
          }
      }, { passive: true });

      stageEl.addEventListener('touchmove', function (e) {
          if (e.touches.length === 1) {
              touchEndX = e.touches[0].clientX;
              touchEndY = e.touches[0].clientY;
          }
      }, { passive: true });

      stageEl.addEventListener('touchend', function () {
          const diffX = touchEndX - touchStartX;
          const diffY = touchEndY - touchStartY;
          const SWIPE_THRESHOLD = 45;

          if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > SWIPE_THRESHOLD) {
              if (diffX < 0) {
                  nextSlide();
              } else {
                  prevSlide();
              }
          }
          startAutoplay();
      }, { passive: true });

      // Hover pause
      carouselEl.addEventListener('mouseenter', stopAutoplay);
      carouselEl.addEventListener('mouseleave', startAutoplay);
      carouselEl.addEventListener('focusin', stopAutoplay);
      carouselEl.addEventListener('focusout', startAutoplay);

      // Resize throttle
      let resizeTimeout = null;
      window.addEventListener('resize', function () {
          if (resizeTimeout) clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(() => {
              update3DStage();
          }, 100);
      }, { passive: true });

      // Visibility change
      document.addEventListener('visibilitychange', function () {
          if (document.hidden) {
              stopAutoplay();
          } else {
              startAutoplay();
          }
      });

      // Initial setup
      update3DStage();
      startAutoplay();
  }

  /* ====================================================================
     CATEGORY EXPLORER DEPARTMENT TABS CONTROLLER
     ==================================================================== */
  function initNobifashionCategoryTabs() {
      const tabsContainer = document.querySelector('.nobifashion_home_categories_tabs');
      if (!tabsContainer) return;

      const tabs = tabsContainer.querySelectorAll('.nobifashion_home_categories_tab_btn');
      const panels = document.querySelectorAll('.nobifashion_home_categories_panel');

      tabs.forEach(tab => {
          tab.addEventListener('click', function (e) {
              e.preventDefault();
              if (this.classList.contains('nobifashion_home_categories_tab_active')) return;

              const targetKey = this.getAttribute('data-nobifashion-tab');
              if (!targetKey) return;

              tabs.forEach(t => {
                  t.classList.remove('nobifashion_home_categories_tab_active');
                  t.setAttribute('aria-selected', 'false');
              });
              this.classList.add('nobifashion_home_categories_tab_active');
              this.setAttribute('aria-selected', 'true');

              panels.forEach(panel => {
                  if (panel.id === 'nobifashion_cat_panel_' + targetKey) {
                      panel.style.display = 'block';
                      // Force DOM reflow so staggered animation triggers cleanly
                      void panel.offsetWidth;
                      panel.classList.add('nobifashion_home_categories_panel_active');
                  } else {
                      panel.style.display = 'none';
                      panel.classList.remove('nobifashion_home_categories_panel_active');
                  }
              });
          });
      });
  }

  if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () {
          initNobifashionHomeBannerMain();
          initNobifashionCategoryTabs();
      });
  } else {
      initNobifashionHomeBannerMain();
      initNobifashionCategoryTabs();
  }
})();

