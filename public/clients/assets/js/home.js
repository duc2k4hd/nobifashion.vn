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
})();
