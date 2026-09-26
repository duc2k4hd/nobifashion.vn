/* Header & Modals Controller - Nobi Fashion (Dùng chung cho toàn bộ website) */
(() => {
  "use strict";

  const prefix = "nobifashion_home_";
  const get = (name) => document.getElementById(prefix + name);
  const all = (selector, root = document) => root ? [...root.querySelectorAll(selector)] : [];
  const className = (name) => prefix + name;
  const normalize = (value) =>
    (value || "")
      .normalize("NFD")
      .replace(/\p{Diacritic}/gu, "")
      .replace(/[đĐ]/g, "d")
      .toLowerCase()
      .trim();
  const formatMoney = (price) =>
    `${new Intl.NumberFormat("vi-VN").format(price || 0)} VND`;

  const storage = {
    read(key, fallback) {
      try {
        const data = JSON.parse(localStorage.getItem(prefix + key));
        return Array.isArray(data)
          ? data.filter((item) => typeof item === "string" || typeof item === "number" || typeof item === "object")
          : fallback;
      } catch {
        return fallback;
      }
    },
    write(key, value) {
      try {
        localStorage.setItem(prefix + key, JSON.stringify(value));
      } catch {}
    },
  };

  let returnFocus = null;
  let toastTimer = null;
  let searchDebounceTimer = null;
  let history = storage.read("search_history", []).slice(0, 8);

  function element(tag, cls, text) {
    const node = document.createElement(tag);
    if (cls) node.className = className(cls);
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function notify(message) {
    const toast = get("toast");
    if (!toast) return;
    clearTimeout(toastTimer);
    toast.textContent = message;
    toast.hidden = false;
    toastTimer = setTimeout(() => {
      toast.hidden = true;
    }, 2500);
  }

  // --- WISHLIST MANAGEMENT ---
  function getFavorites() {
    return new Set(storage.read("favorites", []));
  }

  function updateWishlistBadge() {
    const favorites = getFavorites();
    const countElem = get("wishlist_count");
    if (countElem) {
      countElem.textContent = String(favorites.size);
      countElem.hidden = favorites.size === 0;
    }
  }

  function renderWishlist() {
    const target = get("wishlist_content");
    if (!target) return;
    target.replaceChildren();

    const favorites = getFavorites();
    if (!favorites.size) {
      const empty = element("div", "empty");
      empty.style.cssText = "padding: 40px 16px; text-align: center; color: #666;";
      empty.append(element("p", "", "Bạn chưa có sản phẩm yêu thích nào."));
      const close = element("button", "pill", "Tiếp tục mua sắm");
      close.type = "button";
      close.dataset.nobifashionClose = "";
      close.style.cssText = "margin-top: 16px; padding: 10px 24px; background: #000; color: #fff; border: none; border-radius: 9999px; cursor: pointer; font-weight: 600;";
      empty.append(close);
      target.append(empty);
      return;
    }

    const grid = element("div", "wishlist_grid");
    favorites.forEach((item) => {
      const isObj = typeof item === "object" && item !== null;
      const id = isObj ? item.id : item;
      const name = isObj ? item.name : `Sản phẩm ${id}`;
      const imgUrl = isObj ? item.image : "/clients/assets/img/clothes/no-image.webp";
      const price = isObj ? item.price : 0;
      const slug = isObj ? item.slug : id;

      const card = element("article", "wishlist_item");
      const link = document.createElement("a");
      link.href = `/san-pham/${slug}`;

      const img = document.createElement("img");
      img.src = imgUrl;
      img.alt = name;
      img.loading = "lazy";

      const title = document.createElement("p");
      title.textContent = name;

      link.append(img, title);
      if (price > 0) {
        const priceTag = document.createElement("p");
        priceTag.style.color = "#d00";
        priceTag.textContent = formatMoney(price);
        link.append(priceTag);
      }

      const remove = document.createElement("button");
      remove.type = "button";
      remove.setAttribute("aria-label", `Xóa khỏi yêu thích: ${name}`);
      remove.dataset.nobifashionRemoveFavorite = String(id);
      remove.textContent = "✕";
      remove.style.cssText = "position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: 50%; background: #fff; border: 1px solid #ccc; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center;";

      card.append(link, remove);
      grid.append(card);
    });
    target.append(grid);
  }

  function toggleFavorite(id, name, image, price, slug) {
    if (!id) return;
    const favorites = storage.read("favorites", []);
    const existingIndex = favorites.findIndex((item) => {
      const currentId = typeof item === "object" && item !== null ? item.id : item;
      return String(currentId) === String(id);
    });

    let isAdded = false;
    if (existingIndex > -1) {
      favorites.splice(existingIndex, 1);
      notify("Đã bỏ khỏi sản phẩm yêu thích.");
    } else {
      favorites.push({
        id,
        name: name || `Sản phẩm ${id}`,
        image: image || "",
        price: price || 0,
        slug: slug || id,
      });
      isAdded = true;
      notify("Đã thêm vào sản phẩm yêu thích.");
    }

    storage.write("favorites", favorites);
    updateWishlistBadge();

    const wishlistDialog = get("wishlist");
    if (wishlistDialog && wishlistDialog.open) {
      renderWishlist();
    }
  }

  // --- DIALOG MODAL MANAGEMENT ---
  function openDialog(name, trigger) {
    const dialog = get(name);
    if (!dialog || dialog.open) return;

    all("dialog[open]").forEach((opened) => {
      try { opened.close(); } catch {}
    });

    returnFocus = trigger || document.activeElement;
    if (name === "wishlist") renderWishlist();
    if (name === "search") {
      history = storage.read(isBlogSearch() ? "blog_search_history" : "search_history", []).slice(0, 8);
      renderSearchHistory();
      renderSearchResults();
    }
    if (name === "menu") {
      const activeTab = document.querySelector("[data-nobifashion-menu-tab][aria-selected='true']") 
        || document.querySelector("[data-nobifashion-menu-tab]");
      if (activeTab) {
        setMenuTab(activeTab.dataset.nobifashionMenuTab);
      }
      all("details", dialog).forEach((group) => {
        group.open = false;
      });
      all(".nobifashion_home_menu_tabs, .nobifashion_menu_tabs_bar", dialog).forEach(enableDragToScroll);
    }

    if (typeof dialog.showModal === "function") {
      dialog.showModal();
    } else {
      dialog.setAttribute("open", "");
    }

    document.body.classList.add(className("locked"));
    all(`[data-nobifashion-open="${name}"]`).forEach((btn) =>
      btn.setAttribute("aria-expanded", "true")
    );

    if (name === "search") {
      const searchInput = get("search_input");
      if (searchInput) {
        setTimeout(() => searchInput.focus(), 60);
      }
    }
  }

  function closeAllDialogs() {
    all("dialog[open]").forEach((dialog) => {
      if (typeof dialog.close === "function") {
        dialog.close();
      } else {
        dialog.removeAttribute("open");
      }
    });
  }

  all("dialog").forEach((dialog) => {
    dialog.addEventListener("click", (event) => {
      if (
        event.target === dialog ||
        event.target.closest("[data-nobifashion-close]")
      ) {
        if (typeof dialog.close === "function") {
          dialog.close();
        } else {
          dialog.removeAttribute("open");
        }
      }
    });

    dialog.addEventListener("close", () => {
      all(`[aria-controls="${dialog.id}"][data-nobifashion-open]`).forEach(
        (btn) => btn.setAttribute("aria-expanded", "false")
      );
      if (!all("dialog[open]").length) {
        document.body.classList.remove(className("locked"));
        if (returnFocus && returnFocus.isConnected) {
          try { returnFocus.focus({ preventScroll: true }); } catch {}
        }
      }
    });
  });

  // --- SEARCH DIALOG MANAGEMENT (LIVE SEARCH & AJAX) ---
  const isBlogSearch = () => {
    const dialog = get("search");
    if (dialog && dialog.dataset.searchMode) {
      return dialog.dataset.searchMode === "blog";
    }
    const path = window.location.pathname;
    return path === "/blog" || path.startsWith("/blog/");
  };

  function rememberSearch(term) {
    if (!term) return;
    const historyKey = isBlogSearch() ? "blog_search_history" : "search_history";
    history = [
      term,
      ...history.filter((value) => normalize(value) !== normalize(term)),
    ].slice(0, 8);
    storage.write(historyKey, history);
  }

  function renderSearchHistory() {
    const container = get("search_history");
    if (!container) return;
    container.replaceChildren();
    if (!history.length) return;

    history.forEach((term) => {
      const button = element("button", "history_button", term);
      button.type = "button";
      button.addEventListener("click", () => {
        const searchInput = get("search_input");
        if (searchInput) {
          searchInput.value = term;
          renderSearchResults();
          searchInput.focus();
        }
      });
      container.append(button);
    });
  }

  async function fetchLiveSearch(query) {
    if (!query) return;
    const list = get("search_results");
    const searchStatus = get("search_status");
    if (!list) return;

    const isBlog = isBlogSearch();
    const searchForm = get("search_form");
    const defaultApi = isBlog ? "/blog/api/search" : "/shop/search";
    const apiEndpoint = (searchForm && searchForm.dataset.searchApi) ? searchForm.dataset.searchApi : defaultApi;
    const finalUrl = apiEndpoint.includes("?")
      ? `${apiEndpoint}&keyword=${encodeURIComponent(query)}`
      : `${apiEndpoint}?keyword=${encodeURIComponent(query)}`;

    try {
      const res = await fetch(finalUrl, {
        headers: {
          "Accept": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        }
      });
      if (!res.ok) return;
      const data = await res.json();
      
      if (Array.isArray(data) && data.length) {
        list.replaceChildren();
        if (searchStatus) {
          searchStatus.textContent = `${data.length} kết quả phù hợp cho “${query}”`;
        }
        data.forEach((itemData) => {
          const item = document.createElement("li");
          const link = document.createElement("a");
          link.className = `${prefix}search_result`;
          link.addEventListener("click", () => rememberSearch(query));

          if (isBlog) {
            link.href = itemData.url || `/blog/${itemData.slug}`;

            const img = document.createElement("img");
            img.src = itemData.thumbnail_url || "/clients/assets/img/clothes/no-image.webp";
            img.alt = itemData.title || "";
            img.loading = "lazy";

            const copy = document.createElement("span");
            const titleSpan = document.createElement("span");
            titleSpan.className = `${prefix}result_name`;
            titleSpan.textContent = itemData.title || itemData.name;

            const categorySpan = document.createElement("span");
            categorySpan.className = `${prefix}result_detail`;
            categorySpan.style.cssText = "color: #ff3366; font-size: 12px; font-weight: 500;";
            categorySpan.textContent = itemData.category_name ? `${itemData.category_name} • ${itemData.published_at || 'Bài viết'}` : "Bài viết Blog";

            copy.append(titleSpan, categorySpan);
            link.append(img, copy);
          } else {
            link.href = `/san-pham/${itemData.slug}`;

            const img = document.createElement("img");
            const imgUrl = itemData.primary_image ? itemData.primary_image.url : "";
            img.src = imgUrl ? (imgUrl.startsWith("http") ? imgUrl : `/clients/assets/img/clothes/${imgUrl}`) : "/clients/assets/img/clothes/no-image.webp";
            img.alt = itemData.name || "";
            img.loading = "lazy";

            const copy = document.createElement("span");
            const nameSpan = document.createElement("span");
            nameSpan.className = `${prefix}result_name`;
            nameSpan.textContent = itemData.name;

            const priceSpan = document.createElement("span");
            priceSpan.className = `${prefix}result_detail`;
            priceSpan.textContent = formatMoney(itemData.sale_price || itemData.price);

            copy.append(nameSpan, priceSpan);
            link.append(img, copy);
          }

          item.append(link);
          list.append(item);
        });
      } else {
        if (searchStatus) {
          searchStatus.textContent = isBlog
            ? `Không tìm thấy bài viết nào cho “${query}”. Nhấn Enter để xem kết quả chi tiết.`
            : `Không tìm thấy sản phẩm nào cho “${query}”. Nhấn Enter để xem kết quả chi tiết.`;
        }
      }
    } catch (e) {}
  }

  function renderSearchResults() {
    const searchInput = get("search_input");
    if (!searchInput) return;
    const query = searchInput.value.trim();
    const searchClear = get("search_clear");
    if (searchClear) searchClear.hidden = !query;
    const searchHistory = get("search_history");
    if (searchHistory) searchHistory.hidden = Boolean(query);
    const searchMore = get("search_more");
    const searchForm = get("search_form");
    if (searchMore && searchForm) {
      searchMore.hidden = !query;
      try {
        const remote = new URL(searchForm.action, window.location.origin);
        remote.searchParams.set("keyword", query);
        searchMore.href = remote.href;
      } catch {}
    }

    clearTimeout(searchDebounceTimer);
    if (query) {
      searchDebounceTimer = setTimeout(() => {
        fetchLiveSearch(query);
      }, 250);
    } else {
      const list = get("search_results");
      if (list) list.replaceChildren();
      const searchStatus = get("search_status");
      if (searchStatus) {
        searchStatus.textContent = isBlogSearch()
          ? "Tìm theo tiêu đề bài viết hoặc chủ đề blog."
          : "Tìm theo tên sản phẩm hoặc danh mục.";
      }
    }
  }

  const searchInputElem = get("search_input");
  if (searchInputElem) {
    searchInputElem.addEventListener("input", renderSearchResults);
  }

  const searchClearElem = get("search_clear");
  if (searchClearElem) {
    searchClearElem.addEventListener("click", () => {
      if (searchInputElem) {
        searchInputElem.value = "";
        renderSearchResults();
        searchInputElem.focus();
      }
    });
  }

  const searchFormElem = get("search_form");
  if (searchFormElem) {
    searchFormElem.addEventListener("submit", (event) => {
      const term = searchInputElem ? searchInputElem.value.trim() : "";
      if (!term) {
        event.preventDefault();
        if (searchInputElem) searchInputElem.focus();
        return;
      }
      rememberSearch(term);
    });
  }

  // --- MENU TABS MANAGEMENT ---
  function setMenuTab(slug, focus = false) {
    if (!slug) return;
    all("[data-nobifashion-menu-tab]").forEach((button) => {
      const selected = button.dataset.nobifashionMenuTab === slug;
      button.setAttribute("aria-selected", String(selected));
      button.tabIndex = selected ? 0 : -1;
      if (selected && focus) button.focus();
    });
    all("[data-nobifashion-menu-panel]").forEach((panel) => {
      const isCurrent = panel.dataset.nobifashionMenuPanel === slug;
      if (isCurrent) {
        panel.removeAttribute("hidden");
        panel.hidden = false;
        panel.style.removeProperty("display");
      } else {
        panel.setAttribute("hidden", "");
        panel.hidden = true;
        panel.style.setProperty("display", "none", "important");
      }
    });
  }

  const menuDialog = get("menu");
  if (menuDialog) {
    menuDialog.addEventListener("keydown", (event) => {
      const tab = event.target.closest("[data-nobifashion-menu-tab]");
      if (!tab || !["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key))
        return;
      event.preventDefault();
      const tabs = all("[data-nobifashion-menu-tab]", menuDialog);
      let index = tabs.indexOf(tab);
      if (event.key === "Home") index = 0;
      else if (event.key === "End") index = tabs.length - 1;
      else
        index =
          (index + (event.key === "ArrowRight" ? 1 : -1) + tabs.length) %
          tabs.length;
      if (tabs[index]) {
        setMenuTab(tabs[index].dataset.nobifashionMenuTab, true);
      }
    });
  }

  // --- GLOBAL CLICK DELEGATE ---
  document.addEventListener("click", (event) => {
    // 1. Mở Dialog (Search, Menu, Wishlist)
    const openBtn = event.target.closest("[data-nobifashion-open]");
    if (openBtn) {
      event.preventDefault();
      openDialog(openBtn.dataset.nobifashionOpen, openBtn);
      return;
    }

    // 2. Chuyển Tab Menu Danh mục
    const menuTabBtn = event.target.closest("[data-nobifashion-menu-tab]");
    if (menuTabBtn) {
      event.preventDefault();
      setMenuTab(menuTabBtn.dataset.nobifashionMenuTab);
      return;
    }

    // 3. Xóa sản phẩm trong Wishlist Dialog
    const removeFavBtn = event.target.closest("[data-nobifashion-remove-favorite]");
    if (removeFavBtn) {
      event.preventDefault();
      toggleFavorite(removeFavBtn.dataset.nobifashionRemoveFavorite);
      return;
    }

    // 4. Nút cuộn lên đầu trang
    const backToTopBtn = event.target.closest(".nobifashion_back_to_top, #nobifashion_home_back_top");
    if (backToTopBtn) {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }
  });

  // --- SCROLL VISIBILITY FOR BACK TO TOP ---
  function handleBackToTopScroll() {
    const backToTopElems = all(".nobifashion_back_to_top, #nobifashion_home_back_top");
    const isVisible = window.scrollY > 300;
    backToTopElems.forEach((el) => {
      el.style.display = isVisible ? "flex" : "none";
    });
  }
  window.addEventListener("scroll", handleBackToTopScroll, { passive: true });

  // --- DRAG TO SCROLL (KÉO TRƯỢT BẰNG CHUỘT VÀ TAY) ---
  function enableDragToScroll(el) {
    if (!el || el.dataset.dragEnabled) return;
    el.dataset.dragEnabled = "true";
    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;
    let hasDragged = false;

    el.addEventListener("mousedown", (e) => {
      if (e.button !== 0) return;
      isDown = true;
      hasDragged = false;
      startX = e.pageX - el.offsetLeft;
      scrollLeft = el.scrollLeft;
      el.style.cursor = "grabbing";
    });

    window.addEventListener("mouseup", () => {
      if (isDown) {
        isDown = false;
        if (el) el.style.cursor = "grab";
        setTimeout(() => { hasDragged = false; }, 50);
      }
    });

    window.addEventListener("mousemove", (e) => {
      if (!isDown) return;
      const x = e.pageX - el.offsetLeft;
      const walk = (x - startX) * 1.5;
      if (Math.abs(walk) > 4) {
        hasDragged = true;
        e.preventDefault();
      }
      el.scrollLeft = scrollLeft - walk;
    });

    el.addEventListener(
      "click",
      (e) => {
        if (hasDragged) {
          e.preventDefault();
          e.stopPropagation();
        }
      },
      true
    );
  }

  function initDragScroll() {
    all(".nobifashion_home_mobile_tabs, .nobifashion_home_menu_tabs, .nobifashion_menu_tabs_bar").forEach(enableDragToScroll);
  }

  // Init
  document.addEventListener("DOMContentLoaded", () => {
    updateWishlistBadge();
    handleBackToTopScroll();
    initDragScroll();
  });
  updateWishlistBadge();
  initDragScroll();
})();
