(() => {
'use strict';
// --- assets/js/reels-data.js ---
/**
 * NOBI FASHION REELS — DATA STORE & LOCAL STORAGE SERVICE
 * Provides structured mock data for 9 fashion editorial reels, tagged products,
 * comments, notifications, and safe localStorage persistence.
 */

const STORAGE_KEY = 'nobifashion_reels_state_v1';

const ANIMAL_ICONS = {
  cat: '🐱', dog: '🐶', bear: '🐻', panda: '🐼', rabbit: '🐰',
  fox: '🦊', tiger: '🐯', lion: '🦁', pig: '🐷', monkey: '🐵',
  penguin: '🐧', octopus: '🐙', dolphin: '🐬', shark: '🦈',
  duck: '🦆', squirrel: '🐿️', owl: '🦉', capybara: '🦫', animal: '🐾'
};


const INITIAL_REELS_DATA = Array.isArray(window.__INITIAL_REELS__) && window.__INITIAL_REELS__.length > 0
  ? window.__INITIAL_REELS__
  : [];

class ReelsDataStore {
  constructor() {
    this.reels = [];
    this.cart = [];
    this.hiddenReelIds = new Set();
    this.theme = 'light';
    this.muted = false; // Mặc định mở tiếng khi xem video
    this.playbackRate = 1;
    this.activeFilter = 'all'; // 'all' | 'trending' | 'new' | 'liked' | 'saved'
    this.searchQuery = '';
    this.nextCursor = window.__REELS_NEXT_CURSOR__ || null;
    this.hasMore = Boolean(window.__REELS_HAS_MORE__);
    this.isLoadingMore = false;

    // Smart Debounce maps cho Like/Unlike chống spam server
    this.likeDebounceTimers = new Map();
    this.likeInitialStates = new Map();
    this.commentLikeTimers = new Map();
    this.commentLikeInitialStates = new Map();
    this.initFlushOnUnload();

    this.loadState();
  }

  initFlushOnUnload() {
    const handleUnload = () => {
      this.flushAllPendingLikes();
    };
    window.addEventListener('beforeunload', handleUnload);
    window.addEventListener('pagehide', handleUnload);
  }

  flushAllPendingLikes() {
    this.likeDebounceTimers.forEach((timer, reelId) => {
      clearTimeout(timer);
      this.sendReelLikeRequest(reelId, true);
    });
    this.likeDebounceTimers.clear();

    this.commentLikeTimers.forEach((timer, commentId) => {
      clearTimeout(timer);
      this.sendCommentLikeRequest(commentId, true);
    });
    this.commentLikeTimers.clear();
  }

  loadState() {
    // Deep clone initial data
    this.reels = JSON.parse(JSON.stringify(INITIAL_REELS_DATA));
    this.followedAuthors = new Set();
    try {
      const rawFollowed = window.localStorage.getItem('nobifashion_reels_followed_authors');
      if (rawFollowed) {
        const parsed = JSON.parse(rawFollowed);
        if (Array.isArray(parsed)) {
          this.followedAuthors = new Set(parsed);
        }
      }
    } catch (_) {}

    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const saved = JSON.parse(raw);
        if (saved.theme === 'dark' || saved.theme === 'light') {
          this.theme = saved.theme;
        }
        if (typeof saved.muted === 'boolean') {
          this.muted = saved.muted;
        }
        if (Array.isArray(saved.cart)) {
          this.cart = saved.cart;
        }
      }
    } catch (err) {
      // Safe fallback when localStorage is unavailable or restricted
    }

    // Luôn áp dụng trạng thái theo dõi vĩnh viễn (không cần đăng nhập)
    this.reels.forEach((reel) => {
      const keyId = reel.author?.id;
      const keyName = reel.author?.name;
      if ((keyId && this.followedAuthors.has(keyId)) || (keyName && this.followedAuthors.has(keyName))) {
        reel.isFollowing = true;
      }
    });
  }

  saveState() {
    try {
      const payload = {
        theme: this.theme,
        muted: this.muted,
        cart: this.cart
      };
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch (err) {
      // Ignore storage write errors in restricted iframes
    }
  }

  async fetchNextPage() {
    if (this.isLoadingMore || !this.hasMore) return false;
    this.isLoadingMore = true;

    try {
      let url = `/api/v1/reels?limit=10`;
      if (this.nextCursor) {
        url += `&cursor=${encodeURIComponent(this.nextCursor)}`;
      }
      if (this.activeFilter && this.activeFilter !== 'all') {
        url += `&category=${encodeURIComponent(this.activeFilter)}`;
      }
      if (this.searchQuery) {
        url += `&search=${encodeURIComponent(this.searchQuery)}`;
      }

      const res = await fetch(url);
      if (res.ok) {
        const json = await res.json();
        const newReels = json.data || [];
        this.nextCursor = json.pagination?.next_cursor || null;
        this.hasMore = Boolean(json.pagination?.has_more);

        // Chống thêm video trùng id
        const existingIds = new Set(this.reels.map((r) => r.id));
        newReels.forEach((r) => {
          if (!existingIds.has(r.id)) {
            const keyId = r.author?.id;
            const keyName = r.author?.name;
            if ((keyId && this.followedAuthors.has(keyId)) || (keyName && this.followedAuthors.has(keyName))) {
              r.isFollowing = true;
            }
            this.reels.push(r);
          }
        });
        return true;
      }
    } catch (err) {
      console.warn('Lỗi khi tải trang video tiếp theo:', err);
    } finally {
      this.isLoadingMore = false;
    }
    return false;
  }

  getFilteredReels() {
    const q = this.searchQuery.trim().toLowerCase();
    return this.reels.filter((r) => {
      if (this.hiddenReelIds.has(r.id)) return false;
      if (this.activeFilter === 'trending' && r.category !== 'trending') return false;
      if (this.activeFilter === 'new' && r.category !== 'new') return false;
      if (this.activeFilter === 'liked' && !r.isLiked) return false;
      if (this.activeFilter === 'saved' && !r.isSaved) return false;

      if (!q) return true;
      const inTitle = (r.title || '').toLowerCase().includes(q);
      const inCaption = (r.caption || '').toLowerCase().includes(q);
      const inAuthor = (r.author?.name || '').toLowerCase().includes(q) || (r.author?.handle || '').toLowerCase().includes(q);
      const inTags = Array.isArray(r.hashtags) && r.hashtags.some((t) => t.toLowerCase().includes(q));
      const inProducts = Array.isArray(r.products) && r.products.some((p) => (p.name || '').toLowerCase().includes(q));
      return inTitle || inCaption || inAuthor || inTags || inProducts;
    });
  }

  getReelById(id) {
    return this.reels.find((r) => r.id === id) || null;
  }

  getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  async sendReelLikeRequest(reelId, isKeepAlive = false) {
    const reel = this.getReelById(reelId);
    if (!reel) return;
    const initialLiked = this.likeInitialStates.get(reelId);
    this.likeInitialStates.delete(reelId);

    // Nếu trạng thái cuối cùng giống hệt ban đầu (bấm Like rồi bấm Unlike ngay) -> HỦY, KHÔNG GỬI GÌ
    if (initialLiked !== undefined && reel.isLiked === initialLiked) {
      return;
    }

    try {
      const fetchOptions = {
        method: reel.isLiked ? 'POST' : 'DELETE',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
      };
      if (isKeepAlive && 'keepalive' in Request.prototype) {
        fetchOptions.keepalive = true;
      }
      const res = await fetch(`/api/v1/reels/${reel.id}/like`, fetchOptions);
      if (res.ok) {
        const json = await res.json();
        if (json.success) {
          reel.isLiked = json.isLiked;
          reel.likesCount = json.likesCount;
        }
      }
    } catch (_) {}
  }

  async toggleLike(reelId, forceLike = null) {
    const reel = this.getReelById(reelId);
    if (!reel) return null;
    const nextState = forceLike !== null ? forceLike : !reel.isLiked;
    if (nextState === reel.isLiked) return reel;

    // Ghi nhận trạng thái ban đầu của chuỗi click nếu chưa có
    if (!this.likeInitialStates.has(reelId)) {
      this.likeInitialStates.set(reelId, reel.isLiked);
    }

    // 1. Optimistic UI update: Thay đổi tức thì (0ms) cho người dùng
    reel.isLiked = nextState;
    reel.likesCount = Math.max(0, reel.likesCount + (nextState ? 1 : -1));

    // 2. Debounce: Hủy timer cũ, tạo timer mới (300ms)
    if (this.likeDebounceTimers.has(reelId)) {
      clearTimeout(this.likeDebounceTimers.get(reelId));
    }

    const timer = setTimeout(() => {
      this.likeDebounceTimers.delete(reelId);
      this.sendReelLikeRequest(reelId);
    }, 300);

    this.likeDebounceTimers.set(reelId, timer);
    return reel;
  }

  async toggleSave(reelId) {
    const reel = this.getReelById(reelId);
    if (!reel) return null;
    const prevSaved = reel.isSaved;
    const prevCount = reel.savesCount;
    reel.isSaved = !reel.isSaved;
    reel.savesCount = Math.max(0, (reel.savesCount || 0) + (reel.isSaved ? 1 : -1));

    try {
      const res = await fetch(`/api/v1/reels/${reel.id}/save`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success) {
          reel.isSaved = json.isSaved;
          reel.savesCount = json.savesCount;
        }
      } else {
        reel.isSaved = prevSaved;
        reel.savesCount = prevCount;
      }
    } catch (e) {
      reel.isSaved = prevSaved;
      reel.savesCount = prevCount;
    }
    return reel;
  }

  async recordShare(reelId) {
    const reel = this.getReelById(reelId);
    if (!reel) return null;
    reel.sharesCount = (reel.sharesCount || 0) + 1;
    try {
      const res = await fetch(`/api/v1/reels/${reel.id}/share`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && typeof json.sharesCount === 'number') {
          reel.sharesCount = json.sharesCount;
        }
      }
    } catch (e) {}
    return reel;
  }

  async fetchComments(reelId, page = 1, append = false, sort = 'popular') {
    const reel = this.getReelById(reelId);
    if (!reel) return [];
    try {
      const res = await fetch(`/api/v1/reels/${reel.id}/comments?limit=20&page=${page}&sort=${sort}`, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          reel.commentsPage = page;
          reel.commentsHasMore = Boolean(json.pagination?.has_more);
          if (append && Array.isArray(reel.comments)) {
            const existingIds = new Set(reel.comments.map((c) => String(c.id)));
            const newItems = json.data.filter((c) => !existingIds.has(String(c.id)));
            reel.comments.push(...newItems);
          } else {
            reel.comments = json.data;
          }
          if (typeof json.total === 'number') {
            reel.commentsCount = json.total;
          }
          return reel.comments;
        }
      }
    } catch (e) {}
    return reel.comments || [];
  }

  async fetchMoreCommentReplies(reelId, commentId) {
    const reel = this.getReelById(reelId);
    if (!reel || !Array.isArray(reel.comments)) return [];

    const parent = reel.comments.find((c) => String(c.id) === String(commentId));
    if (!parent) return [];

    parent.replies = parent.replies || [];
    const offset = parent.replies.length;

    try {
      const res = await fetch(`/api/v1/reels/comments/${commentId}/replies?offset=${offset}&limit=10`, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      if (res.ok) {
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
          const existingIds = new Set(parent.replies.map((r) => String(r.id)));
          const newReplies = json.data.filter((r) => !existingIds.has(String(r.id)));
          parent.replies.push(...newReplies);
          if (typeof json.pagination?.total_replies === 'number') {
            parent.replies_count = json.pagination.total_replies;
          }
          return parent.replies;
        }
      }
    } catch (e) {}
    return parent.replies;
  }

  toggleFollow(authorIdentifier) {
    if (!authorIdentifier) return false;
    if (!this.followedAuthors) {
      this.followedAuthors = new Set();
    }

    const matchedReel = this.reels.find(
      (r) => r.author?.id === authorIdentifier || r.author?.name === authorIdentifier
    );
    const authorId = matchedReel?.author?.id || authorIdentifier;
    const authorName = matchedReel?.author?.name || authorIdentifier;

    let isNowFollowing = false;
    if (this.followedAuthors.has(authorId) || this.followedAuthors.has(authorName)) {
      this.followedAuthors.delete(authorId);
      this.followedAuthors.delete(authorName);
      isNowFollowing = false;
    } else {
      this.followedAuthors.add(authorId);
      this.followedAuthors.add(authorName);
      isNowFollowing = true;
    }

    try {
      window.localStorage.setItem(
        'nobifashion_reels_followed_authors',
        JSON.stringify(Array.from(this.followedAuthors))
      );
    } catch (_) {}

    this.reels.forEach((r) => {
      if (r.author?.id === authorId || r.author?.name === authorName) {
        r.isFollowing = isNowFollowing;
      }
    });

    this.saveState();
    return isNowFollowing;
  }

  hideReel(reelId) {
    this.hiddenReelIds.add(reelId);
  }

  resetFilters() {
    this.activeFilter = 'all';
    this.searchQuery = '';
    this.hiddenReelIds.clear();
  }

  async addComment(reelId, text, replyToCommentId = null) {
    const reel = this.getReelById(reelId);
    if (!reel || !text.trim()) return null;
    const cleanText = text.trim();

    const res = await fetch(`/api/v1/reels/${reel.id}/comments`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.getCsrfToken()
      },
      body: JSON.stringify({
        content: cleanText,
        parent_id: replyToCommentId ? parseInt(replyToCommentId, 10) : null
      })
    });

    const json = await res.json();
    if (!res.ok || !json.success) {
      throw new Error(json.message || 'Không thể đăng bình luận. Vui lòng thử lại!');
    }

    const newComment = json.data;
    newComment.isMyNewComment = true; // Đánh dấu comment của chính mình để luôn ghim lên đầu
    if (typeof json.commentsCount === 'number') {
      reel.commentsCount = json.commentsCount;
    } else {
      reel.commentsCount = (reel.commentsCount || 0) + 1;
    }

    reel.comments = reel.comments || [];
    if (replyToCommentId) {
      // Tìm root comment trực tiếp, hoặc tìm cha chứa comment con đó
      let parent = reel.comments.find((c) => String(c.id) === String(replyToCommentId));
      if (!parent) {
        parent = reel.comments.find((c) =>
          c.replies && c.replies.some((r) => String(r.id) === String(replyToCommentId))
        );
      }

      if (parent) {
        parent.replies = parent.replies || [];
        parent.replies.push(newComment);
        parent.replies_count = (parent.replies_count || 0) + 1;
      } else {
        reel.comments.unshift(newComment);
      }
    } else {
      reel.comments.unshift(newComment);
    }

    return { reel, newComment };
  }

  async sendCommentLikeRequest(commentId, isKeepAlive = false) {
    const initialLiked = this.commentLikeInitialStates.get(commentId);
    this.commentLikeInitialStates.delete(commentId);

    // Tìm comment trong toàn bộ danh sách
    let targetComment = null;
    if (this.reels) {
      const findItem = (items) => {
        for (const item of items) {
          if (String(item.id) === String(commentId)) return item;
          if (item.replies && item.replies.length > 0) {
            const found = findItem(item.replies);
            if (found) return found;
          }
        }
        return null;
      };
      for (const r of this.reels) {
        if (!r.comments) continue;
        targetComment = findItem(r.comments);
        if (targetComment) break;
      }
    }

    if (!targetComment) return;

    // Nếu trạng thái cuối cùng giống hệt ban đầu -> HỦY, KHÔNG GỬI GÌ
    if (initialLiked !== undefined && targetComment.isLiked === initialLiked) {
      return;
    }

    try {
      const fetchOptions = {
        method: targetComment.isLiked ? 'POST' : 'DELETE',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': this.getCsrfToken()
        }
      };
      if (isKeepAlive && 'keepalive' in Request.prototype) {
        fetchOptions.keepalive = true;
      }
      const res = await fetch(`/api/v1/reels/comments/${commentId}/like`, fetchOptions);
      if (res.ok) {
        const json = await res.json();
        if (json.success) {
          targetComment.isLiked = json.isLiked;
          targetComment.likes = json.likesCount;
        }
      }
    } catch (_) {}
  }

  async toggleCommentLike(reelId, commentId, reactionType = null) {
    const reel = this.getReelById(reelId);
    if (!reel) return null;

    const findItem = (items) => {
      for (const item of items) {
        if (String(item.id) === String(commentId)) return item;
        if (item.replies && item.replies.length > 0) {
          const found = findItem(item.replies);
          if (found) return found;
        }
      }
      return null;
    };

    const targetComment = findItem(reel.comments || []);
    if (!targetComment) return null;

    const nextState = !targetComment.isLiked;

    // Ghi nhận trạng thái ban đầu của chuỗi click nếu chưa có
    if (!this.commentLikeInitialStates.has(commentId)) {
      this.commentLikeInitialStates.set(commentId, targetComment.isLiked);
    }

    // 1. Optimistic UI: Nhảy ngay lập tức (0ms) cho người dùng
    targetComment.isLiked = nextState;
    targetComment.likes = Math.max(0, (targetComment.likes || 0) + (nextState ? 1 : -1));

    // 2. Debounce: Hủy timer cũ, tạo timer mới (300ms)
    if (this.commentLikeTimers.has(commentId)) {
      clearTimeout(this.commentLikeTimers.get(commentId));
    }

    const timer = setTimeout(() => {
      this.commentLikeTimers.delete(commentId);
      this.sendCommentLikeRequest(commentId);
    }, 300);

    this.commentLikeTimers.set(commentId, timer);
    return targetComment;
  }

  addToCart(product, size, color) {
    const key = `${product.id}__${size}__${color}`;
    const existing = this.cart.find((item) => item.key === key);
    if (existing) {
      existing.qty += 1;
    } else {
      this.cart.push({
        key,
        productId: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        url: product.url,
        size,
        color,
        qty: 1
      });
    }
    this.saveState();
    return this.cart;
  }

  updateCartQty(key, delta) {
    const idx = this.cart.findIndex((item) => item.key === key);
    if (idx === -1) return this.cart;
    this.cart[idx].qty += delta;
    if (this.cart[idx].qty <= 0) {
      this.cart.splice(idx, 1);
    }
    this.saveState();
    return this.cart;
  }

  getCartTotalCount() {
    return this.cart.reduce((sum, item) => sum + item.qty, 0);
  }

  getCartTotalPrice() {
    return this.cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  }
}

function formatCount(num) {
  if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
  return String(num);
}

function formatCurrency(amount) {
  return Number(amount || 0).toLocaleString('vi-VN') + '₫';
}


// --- assets/js/reels-gestures.js ---
/**
 * NOBI FASHION REELS — WHEEL & TOUCH GESTURE CONTROLLER
 *
 * CRITICAL REQUIREMENT (Section 5):
 * ONE SCROLL OR SWIPE GESTURE = EXACTLY ONE VIDEO TRANSITION.
 *
 * Implements a strict finite state machine:
 * - IDLE: Ready to accept a new wheel or touch gesture.
 * - NAVIGATING: Currently animating to the target slide (navigation lock active).
 * - WAITING_FOR_GESTURE_END: Transition animation finished, but trackpad/mouse
 *   momentum or high-speed wheel stream from the SAME gesture is still emitting
 *   events. Unlocks back to IDLE only when:
 *     1) A quiet gap (e.g. >= 160ms with zero wheel events) occurs, OR
 *     2) The user explicitly reverses wheel direction, OR
 *     3) A distinct physical spike after decaying trackpad inertia is detected.
 */

const GESTURE_STATE = {
  IDLE: 'IDLE',
  NAVIGATING: 'NAVIGATING',
  WAITING_FOR_GESTURE_END: 'WAITING_FOR_GESTURE_END'
};

class WheelGestureController {
  /**
   * @param {Object} options
   * @param {HTMLElement} options.stageElement - The stage area listening for wheel events
   * @param {Function} options.onNavigate - Callback(direction: 1 | -1) returning boolean if navigation started
   * @param {Function} options.shouldIgnoreEvent - Predicate(event) returning true if inside scrollable panel/modal
   */
  constructor({ stageElement, onNavigate, shouldIgnoreEvent }) {
    this.stageElement = stageElement;
    this.onNavigate = onNavigate;
    this.shouldIgnoreEvent = shouldIgnoreEvent;

    this.state = GESTURE_STATE.IDLE;
    this.accumulatedDelta = 0;
    this.lastDirection = 0;
    this.lastAbsDelta = 0;
    this.lastWheelTime = 0;
    this.gestureSilenceTimer = null;
    this.safetyUnlockTimer = null;

    // Tuned thresholds for standard mice, high-speed gaming wheels, and MacBook trackpads
    this.DELTA_TRIGGER_THRESHOLD = 18; // Normalized px threshold to filter accidental micro-jitters
    this.GESTURE_QUIET_GAP_MS = 160;   // Silence duration that marks the end of a wheel/trackpad gesture

    this.handleWheel = this.handleWheel.bind(this);
    this.init();
  }

  init() {
    if (!this.stageElement) return;
    this.stageElement.addEventListener('wheel', this.handleWheel, { passive: false });
  }

  /**
   * Normalizes wheel delta across deltaMode 0 (pixels), 1 (lines), and 2 (pages).
   * Never maps large deltaY to multiple video jumps.
   */
  normalizeDeltaY(event) {
    let dy = event.deltaY;
    if (event.deltaMode === 1) {
      dy *= 32; // Line mode -> px
    } else if (event.deltaMode === 2) {
      dy *= 360; // Page mode -> px
    }
    return dy;
  }

  handleWheel(event) {
    // Do not intercept if user is scrolling inside comments, drawers, modals, or search dropdown
    if (this.shouldIgnoreEvent && this.shouldIgnoreEvent(event)) {
      return;
    }

    // Ignore horizontal pan gestures on trackpads
    if (Math.abs(event.deltaX) > Math.abs(event.deltaY) * 1.2) {
      return;
    }

    // Prevent native page scrolling inside the Reels stage
    event.preventDefault();

    const now = performance.now();
    const normDelta = this.normalizeDeltaY(event);
    const absDelta = Math.abs(normDelta);
    if (absDelta < 1.5) return; // Ignore sub-pixel trackpad noise

    const direction = normDelta > 0 ? 1 : -1;
    const timeSinceLastWheel = now - this.lastWheelTime;
    this.lastWheelTime = now;

    // Reset the silence timer on every incoming wheel event in the stream
    if (this.gestureSilenceTimer) {
      clearTimeout(this.gestureSilenceTimer);
    }
    this.gestureSilenceTimer = setTimeout(() => {
      this.onGestureStreamSilence();
    }, this.GESTURE_QUIET_GAP_MS);

    // Check if the stream was silent long enough before this event to count as a fresh gesture
    if (
      this.state === GESTURE_STATE.WAITING_FOR_GESTURE_END &&
      timeSinceLastWheel >= this.GESTURE_QUIET_GAP_MS
    ) {
      this.resetToIdle();
    }

    // If waiting for momentum tail to finish, check if user deliberately reversed direction
    // or initiated a sharp new flick (acceleration spike after deceleration)
    if (this.state === GESTURE_STATE.WAITING_FOR_GESTURE_END) {
      const reversedDirection = direction !== this.lastDirection;
      const sharpNewFlick =
        timeSinceLastWheel > 65 &&
        this.lastAbsDelta > 0 &&
        absDelta > this.lastAbsDelta * 2.2 &&
        absDelta >= 28;

      if (reversedDirection || sharpNewFlick) {
        this.resetToIdle();
      } else {
        this.lastAbsDelta = absDelta;
        return; // Swallow momentum events belonging to the current gesture
      }
    }

    // While actively transitioning (NAVIGATING), strictly lock and swallow all wheel events
    if (this.state === GESTURE_STATE.NAVIGATING) {
      this.lastAbsDelta = absDelta;
      return;
    }

    // State is IDLE: accumulate delta until threshold is crossed, then trigger EXACTLY 1 video transition
    if (this.lastDirection !== 0 && direction !== this.lastDirection) {
      this.accumulatedDelta = 0;
    }

    this.lastDirection = direction;
    this.lastAbsDelta = absDelta;
    this.accumulatedDelta += absDelta;

    if (this.accumulatedDelta >= this.DELTA_TRIGGER_THRESHOLD) {
      this.accumulatedDelta = 0;
      this.state = GESTURE_STATE.NAVIGATING;

      const didStart = this.onNavigate(direction);
      if (!didStart) {
        // E.g. at first video scrolling up or at last video scrolling down
        this.state = GESTURE_STATE.WAITING_FOR_GESTURE_END;
      } else {
        // Fallback safety unlock in case transitionend doesn't fire (e.g., hidden tab or reduced motion)
        if (this.safetyUnlockTimer) clearTimeout(this.safetyUnlockTimer);
        this.safetyUnlockTimer = setTimeout(() => {
          this.notifyNavigationComplete();
        }, 520);
      }
    }
  }

  /**
   * Called by NavigationController when the slide transition finishes.
   * Transitions from NAVIGATING -> WAITING_FOR_GESTURE_END (or IDLE if wheel stream already stopped).
   */
  notifyNavigationComplete() {
    if (this.safetyUnlockTimer) {
      clearTimeout(this.safetyUnlockTimer);
      this.safetyUnlockTimer = null;
    }
    if (this.state === GESTURE_STATE.NAVIGATING) {
      const elapsedSinceLastWheel = performance.now() - this.lastWheelTime;
      if (elapsedSinceLastWheel >= this.GESTURE_QUIET_GAP_MS) {
        this.resetToIdle();
      } else {
        this.state = GESTURE_STATE.WAITING_FOR_GESTURE_END;
      }
    }
  }

  onGestureStreamSilence() {
    if (this.state === GESTURE_STATE.WAITING_FOR_GESTURE_END) {
      this.resetToIdle();
    } else if (this.state === GESTURE_STATE.IDLE) {
      this.accumulatedDelta = 0;
      this.lastDirection = 0;
      this.lastAbsDelta = 0;
    }
  }

  resetToIdle() {
    this.state = GESTURE_STATE.IDLE;
    this.accumulatedDelta = 0;
    this.lastDirection = 0;
    this.lastAbsDelta = 0;
  }

  destroy() {
    if (this.stageElement) {
      this.stageElement.removeEventListener('wheel', this.handleWheel);
    }
    if (this.gestureSilenceTimer) clearTimeout(this.gestureSilenceTimer);
    if (this.safetyUnlockTimer) clearTimeout(this.safetyUnlockTimer);
  }
}

/**
 * TOUCH / POINTER SWIPE GESTURE CONTROLLER (Mobile & Tablet)
 * Guarantees 1 swipe = at most 1 video transition, regardless of swipe velocity.
 */
class TouchGestureController {
  constructor({ stageElement, onNavigate, shouldIgnoreEvent }) {
    this.stageElement = stageElement;
    this.onNavigate = onNavigate;
    this.shouldIgnoreEvent = shouldIgnoreEvent;

    this.touchActive = false;
    this.hasTriggeredInCurrentSwipe = false;
    this.startX = 0;
    this.startY = 0;
    this.startTime = 0;
    this.SWIPE_DISTANCE_THRESHOLD = 42; // Minimum vertical px to count as intentional navigation swipe

    this.onTouchStart = this.onTouchStart.bind(this);
    this.onTouchMove = this.onTouchMove.bind(this);
    this.onTouchEnd = this.onTouchEnd.bind(this);
    this.init();
  }

  init() {
    if (!this.stageElement) return;
    this.stageElement.addEventListener('touchstart', this.onTouchStart, { passive: true });
    this.stageElement.addEventListener('touchmove', this.onTouchMove, { passive: false });
    this.stageElement.addEventListener('touchend', this.onTouchEnd, { passive: true });
    this.stageElement.addEventListener('touchcancel', this.onTouchEnd, { passive: true });
  }

  onTouchStart(event) {
    if (event.touches.length !== 1) return;
    if (this.shouldIgnoreEvent && this.shouldIgnoreEvent(event)) {
      this.touchActive = false;
      return;
    }
    const touch = event.touches[0];
    this.touchActive = true;
    this.hasTriggeredInCurrentSwipe = false;
    this.startX = touch.clientX;
    this.startY = touch.clientY;
    this.startTime = performance.now();
  }

  onTouchMove(event) {
    if (!this.touchActive || event.touches.length !== 1) return;
    const touch = event.touches[0];
    const dx = touch.clientX - this.startX;
    const dy = touch.clientY - this.startY;

    // Allow horizontal swipes if predominantly horizontal
    if (Math.abs(dx) > Math.abs(dy) * 1.15) {
      return;
    }

    // Prevent browser pull-to-refresh / overscroll in vertical video stage
    if (Math.abs(dy) > 10 && event.cancelable) {
      event.preventDefault();
    }
  }

  onTouchEnd(event) {
    if (!this.touchActive || this.hasTriggeredInCurrentSwipe) {
      this.touchActive = false;
      return;
    }
    this.touchActive = false;

    const changed = event.changedTouches && event.changedTouches[0];
    if (!changed) return;

    const dx = changed.clientX - this.startX;
    const dy = changed.clientY - this.startY;

    // Must be primarily vertical and exceed minimum swipe threshold
    if (Math.abs(dy) < this.SWIPE_DISTANCE_THRESHOLD || Math.abs(dx) > Math.abs(dy)) {
      return;
    }

    this.hasTriggeredInCurrentSwipe = true;
    // Swipe UP (dy < 0) moves to NEXT video (+1); Swipe DOWN (dy > 0) moves to PREV video (-1)
    const direction = dy < 0 ? 1 : -1;
    this.onNavigate(direction);
  }

  destroy() {
    if (!this.stageElement) return;
    this.stageElement.removeEventListener('touchstart', this.onTouchStart);
    this.stageElement.removeEventListener('touchmove', this.onTouchMove);
    this.stageElement.removeEventListener('touchend', this.onTouchEnd);
    this.stageElement.removeEventListener('touchcancel', this.onTouchEnd);
  }
}


// --- assets/js/reels-player.js ---
/**
 * NOBI FASHION REELS — CUSTOM VIDEO PLAYBACK CONTROLLER
 * Manages single-video playback, autoplay promise handling, mute persistence,
 * playback speed (0.5x, 1x, 1.5x, 2x), progress scrubbing, object-fit mode,
 * fullscreen, visibility pause, and error fallback recovery.
 */

class VideoPlaybackController {
  constructor({ store, onStateChange, showToast, onEndedNext }) {
    this.store = store;
    this.onStateChange = onStateChange;
    this.showToast = showToast;
    this.onEndedNext = onEndedNext;

    this.activeVideoEl = null;
    this.activeSlideEl = null;
    this.activeReel = null;
    this.isScrubbing = false;
    this.fitModes = new Map(); // reelId -> 'cover' | 'contain'
    this.watchedTimes = new Map(); // reelId -> seconds (ghi nhớ vị trí xem dở nếu >= 5s)
    this.currentSpeed = 1; // Tốc độ chỉ áp dụng tạm thời cho video đang xem, lướt video khác reset về 1.0x

    this.boundTimeUpdate = this.handleTimeUpdate.bind(this);
    this.boundWaiting = this.handleWaiting.bind(this);
    this.boundPlaying = this.handlePlaying.bind(this);
    this.boundError = this.handleError.bind(this);
    this.boundEnded = this.handleEnded.bind(this);
    this.boundVisibilityChange = this.handleVisibilityChange.bind(this);

    document.addEventListener('visibilitychange', this.boundVisibilityChange);
  }

  /**
   * Attaches controller to the newly active slide's <video> and pauses all others.
   */
  attachToSlide(slideEl, reel, allSlideElements = []) {
    // Pause and detach listeners from previous video
    this.detachCurrent();

    allSlideElements.forEach((el) => {
      if (el !== slideEl) {
        const v = el.querySelector('.nobifashion_reels_video_el');
        if (v) {
          try {
            v.pause();
          } catch (_) {}
        }
      }
    });

    if (!slideEl || !reel) return;
    this.activeSlideEl = slideEl;
    this.activeReel = reel;
    this.loadStartTime = Date.now(); // Ghi nhớ mốc bắt đầu tải video để giữ skeleton ít nhất 0.5s

    const loader = slideEl.querySelector('.nobifashion_reels_video_loader');
    if (loader) {
      loader.classList.add('nobifashion_reels_is_loading');
      slideEl.classList.add('nobifashion_reels_slide_loading');
    }

    const videoEl = slideEl.querySelector('.nobifashion_reels_video_el');
    if (!videoEl) return;

    this.activeVideoEl = videoEl;

    // Apply stored preferences (Tốc độ mặc định 1.0x, chỉ áp dụng riêng lẻ cho video đang xem)
    videoEl.muted = this.store.muted;
    this.currentSpeed = 1;
    videoEl.playbackRate = 1;
    const fitMode = this.fitModes.get(reel.id) || reel.defaultFit || 'cover';
    videoEl.setAttribute('data-fit', fitMode);

    // Khôi phục vị trí phát: nếu xem >= 5s thì tiếp tục từ đoạn đó, ngược lại phát từ đầu
    const savedTime = this.watchedTimes.get(reel.id);
    if (typeof savedTime === 'number' && savedTime >= 5) {
      try {
        if (typeof videoEl.fastSeek === 'function') {
          videoEl.fastSeek(savedTime);
        } else {
          videoEl.currentTime = savedTime;
        }
      } catch (_) {
        videoEl.currentTime = savedTime;
      }
    } else {
      videoEl.currentTime = 0;
    }

    // Attach events
    videoEl.addEventListener('timeupdate', this.boundTimeUpdate);
    videoEl.addEventListener('loadedmetadata', this.boundTimeUpdate);
    videoEl.addEventListener('waiting', this.boundWaiting);
    videoEl.addEventListener('playing', this.boundPlaying);
    videoEl.addEventListener('canplay', this.boundPlaying);
    videoEl.addEventListener('error', this.boundError);
    videoEl.addEventListener('ended', this.boundEnded);

    this.playActiveVideo();
    this.notifyUI();
  }

  detachCurrent() {
    if (!this.activeVideoEl) return;
    const v = this.activeVideoEl;

    // Ghi nhớ vị trí phát của video hiện tại:
    // Xem >= 5s và chưa hết video thì lưu lại để khi lướt quay lại phát tiếp
    // Xem < 5s thì xóa để quay lại phát từ đầu
    if (this.activeReel) {
      const curTime = v.currentTime || 0;
      const duration = v.duration || 0;
      if (curTime >= 5 && (duration <= 0 || curTime < duration - 0.5)) {
        this.watchedTimes.set(this.activeReel.id, curTime);
      } else {
        this.watchedTimes.delete(this.activeReel.id);
      }
    }

    v.removeEventListener('timeupdate', this.boundTimeUpdate);
    v.removeEventListener('loadedmetadata', this.boundTimeUpdate);
    v.removeEventListener('waiting', this.boundWaiting);
    v.removeEventListener('playing', this.boundPlaying);
    v.removeEventListener('canplay', this.boundPlaying);
    v.removeEventListener('error', this.boundError);
    v.removeEventListener('ended', this.boundEnded);
    try {
      v.pause();
    } catch (_) {}
    this.currentSpeed = 1;
    this.activeVideoEl = null;
  }

  hideLoaderWithMinDelay() {
    clearTimeout(this.loaderTimeout);
    const loader = this.activeSlideEl?.querySelector('.nobifashion_reels_video_loader');
    if (!loader) return;

    // Đảm bảo Skeleton Loader hiển thị tối thiểu 0.5s (500ms) khi tải trang / chuyển video
    const elapsed = Date.now() - (this.loadStartTime || 0);
    const minDuration = 500;
    const remainingDelay = Math.max(0, minDuration - elapsed);

    clearTimeout(this.hideLoaderTimer);
    this.hideLoaderTimer = setTimeout(() => {
      loader.classList.remove('nobifashion_reels_is_loading');
      this.activeSlideEl?.classList.remove('nobifashion_reels_slide_loading');
    }, remainingDelay);
  }

  playActiveVideo() {
    if (!this.activeVideoEl) return;
    const v = this.activeVideoEl;
    v.muted = this.store.muted;
    v.playbackRate = this.store.playbackRate || 1;

    const loader = this.activeSlideEl?.querySelector('.nobifashion_reels_video_loader');
    const errBox = this.activeSlideEl?.querySelector('.nobifashion_reels_video_error');
    if (errBox) errBox.classList.remove('nobifashion_reels_has_error');

    const playPromise = v.play();
    if (playPromise !== undefined) {
      playPromise
        .then(() => {
          this.hideLoaderWithMinDelay();
          this.notifyUI();
        })
        .catch((err) => {
          // Khi trình duyệt chặn unmuted autoplay lần đầu, tạm mute để video chạy mượt mà
          if (!v.muted) {
            v.muted = true;
            v.play().catch(() => {});

            // Tự động bật tiếng ngay khi người dùng tương tác lần đầu (click, chạm, phím)
            const unmuteOnFirstInteract = (e) => {
              // Nếu người dùng click trực tiếp vào nút loa, để toggleMute tự xử lý, tránh xung đột
              if (
                e &&
                e.target &&
                (e.target.closest('.nobifashion_reels_video_mute_btn') ||
                  e.target.closest('[data-action="toggle-mute"]'))
              ) {
                window.removeEventListener('pointerdown', unmuteOnFirstInteract, true);
                window.removeEventListener('keydown', unmuteOnFirstInteract, true);
                return;
              }

              if (this.activeVideoEl && !this.store.muted && this.activeVideoEl.muted) {
                this.activeVideoEl.muted = false;
                this.notifyUI();
              }
              window.removeEventListener('pointerdown', unmuteOnFirstInteract, true);
              window.removeEventListener('keydown', unmuteOnFirstInteract, true);
            };
            window.addEventListener('pointerdown', unmuteOnFirstInteract, true);
            window.addEventListener('keydown', unmuteOnFirstInteract, true);
          }
          this.hideLoaderWithMinDelay();
          this.notifyUI();
        });
    }
  }

  togglePlayPause() {
    if (!this.activeVideoEl) return false;
    if (this.activeVideoEl.paused) {
      this.playActiveVideo();
      this.flashCenterFeedback('play');
      return true;
    } else {
      this.activeVideoEl.pause();
      this.flashCenterFeedback('pause');
      this.notifyUI();
      return false;
    }
  }

  toggleMute() {
    // Xác định theo trạng thái thực tế của video đang chạy
    const currentMuted = this.activeVideoEl ? this.activeVideoEl.muted : this.store.muted;
    const nextMuted = !currentMuted;

    this.store.muted = nextMuted;
    this.store.saveState();
    if (this.activeVideoEl) {
      this.activeVideoEl.muted = nextMuted;
      if (!nextMuted && this.activeVideoEl.paused) {
        this.activeVideoEl.play().catch(() => {});
      }
    }
    this.notifyUI();
    if (this.showToast) {
      this.showToast(nextMuted ? 'Đã tắt tiếng' : 'Đã bật tiếng');
    }
    return nextMuted;
  }

  setPlaybackSpeed(speed) {
    const val = parseFloat(speed) || 1;
    this.currentSpeed = val;
    if (this.activeVideoEl) {
      this.activeVideoEl.playbackRate = val;
    }
    this.notifyUI();
    if (this.showToast) {
      this.showToast(`Tốc độ phát: ${val === 1 ? '1.0' : val}x`);
    }
    return val;
  }

  cyclePlaybackSpeed() {
    const speeds = [0.75, 1, 1.25, 1.5, 2];
    const current = this.currentSpeed || 1;
    const nextIdx = (speeds.indexOf(current) + 1) % speeds.length;
    return this.setPlaybackSpeed(speeds[nextIdx]);
  }

  toggleObjectFit() {
    if (!this.activeVideoEl || !this.activeReel) return 'cover';
    const current = this.activeVideoEl.getAttribute('data-fit') || 'cover';
    const next = current === 'cover' ? 'contain' : 'cover';
    this.activeVideoEl.setAttribute('data-fit', next);
    this.fitModes.set(this.activeReel.id, next);
    this.notifyUI();
    if (this.showToast) {
      this.showToast(
        next === 'contain'
          ? 'Khung hiển thị: Nguyên bản (Contain — không cắt hình)'
          : 'Khung hiển thị: Lấp đầy 9:16 (Cover)'
      );
    }
    return next;
  }

  seekToRatio(ratio, isFinal = true) {
    if (!this.activeVideoEl || !Number.isFinite(this.activeVideoEl.duration) || this.activeVideoEl.duration <= 0) return;
    const clamped = Math.max(0, Math.min(1, ratio));
    const targetTime = clamped * this.activeVideoEl.duration;

    // 1. Phản hồi tức thì ngay trên giao diện 0ms (Optimistic UI Update)
    const fill = this.activeSlideEl?.querySelector('.nobifashion_reels_progress_fill');
    if (fill) {
      fill.style.width = `${clamped * 100}%`;
    }
    const timeLabel = this.activeSlideEl?.querySelector('.nobifashion_reels_time_display');
    if (timeLabel) {
      timeLabel.textContent = `${this.formatSeconds(targetTime)} / ${this.formatSeconds(this.activeVideoEl.duration)}`;
    }

    // 2. Nhảy video cực nhanh với fastSeek (keyframe seek), fallback về currentTime
    try {
      if (typeof this.activeVideoEl.fastSeek === 'function') {
        this.activeVideoEl.fastSeek(targetTime);
      } else {
        this.activeVideoEl.currentTime = targetTime;
      }
    } catch (_) {
      this.activeVideoEl.currentTime = targetTime;
    }

    // 3. Đảm bảo video tiếp tục phát ngay không bị dừng ngầm
    if (isFinal && this.activeVideoEl.paused) {
      this.activeVideoEl.play().catch(() => {});
    }
  }

  retryCurrentVideo() {
    if (!this.activeVideoEl) return;
    const errBox = this.activeSlideEl?.querySelector('.nobifashion_reels_video_error');
    if (errBox) errBox.classList.remove('nobifashion_reels_has_error');
    this.activeVideoEl.load();
    this.playActiveVideo();
  }

  toggleFullscreen(containerEl) {
    const target = containerEl || this.activeSlideEl;
    if (!target) return;
    if (!document.fullscreenElement) {
      if (target.requestFullscreen) {
        target.requestFullscreen().catch(() => {});
      }
    } else if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {});
    }
  }

  flashCenterFeedback(state) {
    if (!this.activeSlideEl) return;
    const fb = this.activeSlideEl.querySelector('.nobifashion_reels_center_feedback');
    if (!fb) return;
    fb.innerHTML =
      state === 'pause'
        ? `<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>`
        : `<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>`;
    fb.classList.add('nobifashion_reels_is_visible');
    clearTimeout(this.feedbackTimer);
    this.feedbackTimer = setTimeout(() => {
      fb.classList.remove('nobifashion_reels_is_visible');
    }, 520);
  }

  handleTimeUpdate() {
    if (!this.activeVideoEl || !this.activeSlideEl || this.isScrubbing) return;
    const cur = this.activeVideoEl.currentTime || 0;
    const dur = Number.isFinite(this.activeVideoEl.duration) && this.activeVideoEl.duration > 0
      ? this.activeVideoEl.duration
      : 5;
    const pct = Math.min(100, Math.max(0, (cur / dur) * 100));

    const fill = this.activeSlideEl.querySelector('.nobifashion_reels_progress_fill');
    if (fill) {
      fill.style.width = `${pct}%`;
    }
    const timeLabel = this.activeSlideEl.querySelector('.nobifashion_reels_time_display');
    if (timeLabel) {
      timeLabel.textContent = `${this.formatSeconds(cur)} / ${this.formatSeconds(dur)}`;
    }
  }

  handleWaiting() {
    // Tránh chớp nháy spinner khi chỉ seek nhanh trong vài mili-giây
    clearTimeout(this.loaderTimeout);
    this.loaderTimeout = setTimeout(() => {
      const loader = this.activeSlideEl?.querySelector('.nobifashion_reels_video_loader');
      if (loader && this.activeVideoEl && (this.activeVideoEl.seeking || this.activeVideoEl.readyState < 3)) {
        loader.classList.add('nobifashion_reels_is_loading');
        this.activeSlideEl?.classList.add('nobifashion_reels_slide_loading');
      }
    }, 250);
  }

  handlePlaying() {
    this.hideLoaderWithMinDelay();
    this.notifyUI();
  }

  handleError() {
    const loader = this.activeSlideEl?.querySelector('.nobifashion_reels_video_loader');
    const errBox = this.activeSlideEl?.querySelector('.nobifashion_reels_video_error');
    if (loader) loader.classList.remove('nobifashion_reels_is_loading');
    this.activeSlideEl?.classList.remove('nobifashion_reels_slide_loading');
    if (errBox) errBox.classList.add('nobifashion_reels_has_error');
  }

  handleEnded() {
    if (!this.activeVideoEl) return;
    // Khi video đã xem hết: Xóa vị trí lưu để nếu quay lại sẽ phát lại từ đầu
    if (this.activeReel) {
      this.watchedTimes.delete(this.activeReel.id);
    }
    // Khi video và thanh progress chạy hết: Tự động chuyển lướt sang video tiếp theo
    if (typeof this.onEndedNext === 'function') {
      const moved = this.onEndedNext();
      if (moved) return;
    }
    // Fallback: nếu là video cuối cùng thì replay
    this.activeVideoEl.currentTime = 0;
    this.playActiveVideo();
  }

  handleVisibilityChange() {
    if (!this.activeVideoEl) return;
    if (document.hidden) {
      this.activeVideoEl.pause();
    } else {
      this.playActiveVideo();
    }
  }

  formatSeconds(sec) {
    const s = Math.max(0, Math.floor(sec));
    const m = Math.floor(s / 60);
    const rem = s % 60;
    return `${m}:${rem < 10 ? '0' : ''}${rem}`;
  }

  notifyUI() {
    if (this.onStateChange) {
      const isActualMuted = this.activeVideoEl ? this.activeVideoEl.muted : this.store.muted;
      this.onStateChange({
        muted: isActualMuted,
        paused: this.activeVideoEl ? this.activeVideoEl.paused : true,
        playbackRate: this.currentSpeed || 1,
        fitMode: this.activeVideoEl ? this.activeVideoEl.getAttribute('data-fit') || 'cover' : 'cover'
      });
    }
  }

  destroy() {
    this.detachCurrent();
    document.removeEventListener('visibilitychange', this.boundVisibilityChange);
  }
}


// --- assets/js/reels-renderer.js ---
/**
 * NOBI FASHION REELS — VIRTUALIZED SLIDE & ACTION RAIL RENDERER
 * Renders only a windowed DOM pool (prev, active, next) inside the 9:16 track
 * so even hundreds of reels maintain 60fps and minimal memory usage.
 */

class ReelsRenderer {
  constructor({ trackEl, actionRailEl, emptyStateEl, store }) {
    this.trackEl = trackEl;
    this.actionRailEl = actionRailEl;
    this.emptyStateEl = emptyStateEl;
    this.store = store;
    this.slideMap = new Map(); // reelId -> HTMLElement
  }

  /**
   * Renders windowed slides around activeIndex: [activeIndex - 1, activeIndex, activeIndex + 1]
   */
  renderWindow(reelsList, activeIndex, immediate = false) {
    if (!this.trackEl) return;

    if (!reelsList || reelsList.length === 0) {
      this.trackEl.innerHTML = '';
      this.slideMap.clear();
      if (this.emptyStateEl) {
        this.emptyStateEl.classList.add('nobifashion_reels_is_visible');
      }
      if (this.actionRailEl) {
        this.actionRailEl.style.visibility = 'hidden';
      }
      return;
    }

    if (this.emptyStateEl) {
      this.emptyStateEl.classList.remove('nobifashion_reels_is_visible');
    }
    if (this.actionRailEl) {
      this.actionRailEl.style.visibility = 'visible';
    }

    const validIndices = new Set([activeIndex - 1, activeIndex, activeIndex + 1]);
    const activeIds = new Set();
    validIndices.forEach((idx) => {
      if (idx >= 0 && idx < reelsList.length) {
        activeIds.add(reelsList[idx].id);
      }
    });

    // Remove slides no longer in the [prev, active, next] window to release video decoding memory
    for (const [reelId, slideEl] of this.slideMap.entries()) {
      if (!activeIds.has(reelId)) {
        const v = slideEl.querySelector('video');
        if (v) {
          try {
            v.pause();
            v.removeAttribute('src');
            v.load();
          } catch (_) {}
        }
        slideEl.remove();
        this.slideMap.delete(reelId);
      }
    }

    // Create or update the 3 windowed slides
    validIndices.forEach((idx) => {
      if (idx < 0 || idx >= reelsList.length) return;
      const reel = reelsList[idx];
      const pos = idx < activeIndex ? 'prev' : idx === activeIndex ? 'active' : 'next';
      let slideEl = this.slideMap.get(reel.id);

      if (!slideEl) {
        slideEl = this.createSlideElement(reel, pos, immediate);
        this.trackEl.appendChild(slideEl);
        this.slideMap.set(reel.id, slideEl);
      } else {
        if (immediate) {
          slideEl.setAttribute('data-no-anim', 'true');
        } else {
          slideEl.removeAttribute('data-no-anim');
        }
        slideEl.setAttribute('data-pos', pos);
        // Ensure preload attribute is set appropriately
        const v = slideEl.querySelector('.nobifashion_reels_video_el');
        if (v) {
          v.preload = pos === 'active' ? 'auto' : 'metadata';
        }
        this.updateSlideOverlayData(slideEl, reel);
        if (immediate) {
          requestAnimationFrame(() => {
            slideEl.removeAttribute('data-no-anim');
          });
        }
      }
    });

    // Update the right-hand Action Rail for the active reel
    const activeReel = reelsList[activeIndex];
    if (activeReel) {
      this.renderActionRail(activeReel);
    }
  }

  createSlideElement(reel, pos, immediate) {
    const slide = document.createElement('article');
    slide.className = 'nobifashion_reels_slide nobifashion_reels_slide_loading';
    slide.setAttribute('data-reel-id', reel.id);
    slide.setAttribute('data-pos', pos);
    if (immediate) {
      slide.setAttribute('data-no-anim', 'true');
      requestAnimationFrame(() => slide.removeAttribute('data-no-anim'));
    }

    const firstProduct = reel.products && reel.products[0];
    const hashtagsHtml = reel.hashtags
      .map(
        (tag) =>
          `<button type="button" class="nobifashion_reels_hashtag_btn" data-action="filter-hashtag" data-hashtag="${tag}">${tag}</button>`
      )
      .join('');

    slide.innerHTML = `
      <div class="nobifashion_reels_slide_backdrop" style="background-image: url('${reel.posterUrl}')"></div>
      <div class="nobifashion_reels_video_wrapper" data-action="video-surface">
        <video
          class="nobifashion_reels_video_el"
          src="${reel.videoUrl}"
          poster="${reel.posterUrl}"
          preload="${pos === 'active' ? 'auto' : 'metadata'}"
          playsinline
          webkit-playsinline
          muted
          data-fit="${reel.defaultFit || 'cover'}"
        ></video>
      </div>

      <!-- Nút Bật/Tắt âm thanh ở góc trên bên trái video -->
      <button
        type="button"
        class="nobifashion_reels_video_mute_btn ${this.store.muted ? 'nobifashion_reels_is_muted' : ''}"
        data-action="toggle-mute"
        aria-label="${this.store.muted ? 'Bật âm thanh' : 'Tắt âm thanh'}"
        title="${this.store.muted ? 'Bật âm thanh' : 'Tắt âm thanh'}"
      >
        ${this.getMuteIconSvg(this.store.muted)}
      </button>

      <!-- Center Play/Pause Feedback & Double-Tap Heart -->
      <div class="nobifashion_reels_center_feedback" aria-hidden="true"></div>
      <div class="nobifashion_reels_doubletap_heart" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="currentColor" width="100%" height="100%">
          <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
        </svg>
      </div>

      <!-- Loading Skeleton Layout-Aware 9:16 (Code tay thuần MUI Wave Animation) -->
      <div class="nobifashion_reels_video_loader nobifashion_reels_is_loading">
        <div class="nobifashion_reels_skeleton_screen">
          <!-- Top-Left Mute Button Skeleton -->
          <div class="nobifashion_reels_skel_mute"></div>

          <!-- Right Action Rail Skeleton -->
          <div class="nobifashion_reels_skel_rail">
            <div class="nobifashion_reels_skel_rail_item">
              <div class="nobifashion_reels_skel_circle"></div>
              <div class="nobifashion_reels_skel_count"></div>
            </div>
            <div class="nobifashion_reels_skel_rail_item">
              <div class="nobifashion_reels_skel_circle"></div>
              <div class="nobifashion_reels_skel_count"></div>
            </div>
            <div class="nobifashion_reels_skel_rail_item">
              <div class="nobifashion_reels_skel_circle"></div>
              <div class="nobifashion_reels_skel_count"></div>
            </div>
            <div class="nobifashion_reels_skel_rail_item">
              <div class="nobifashion_reels_skel_circle"></div>
              <div class="nobifashion_reels_skel_count"></div>
            </div>
            <div class="nobifashion_reels_skel_rail_item">
              <div class="nobifashion_reels_skel_circle"></div>
            </div>
          </div>

          <!-- Bottom Left Overlay Skeleton -->
          <div class="nobifashion_reels_skel_bottom">
            <!-- Product Tag Card Skeleton -->
            <div class="nobifashion_reels_skel_product_card">
              <div class="nobifashion_reels_skel_product_thumb"></div>
              <div class="nobifashion_reels_skel_product_lines">
                <div class="nobifashion_reels_skel_line" style="width: 130px; height: 11px;"></div>
                <div class="nobifashion_reels_skel_line" style="width: 90px; height: 9px;"></div>
              </div>
            </div>

            <!-- Author Row Skeleton -->
            <div class="nobifashion_reels_skel_author_row">
              <div class="nobifashion_reels_skel_avatar"></div>
              <div class="nobifashion_reels_skel_author_text">
                <div class="nobifashion_reels_skel_author_top">
                  <div class="nobifashion_reels_skel_line" style="width: 105px; height: 13px;"></div>
                  <div class="nobifashion_reels_skel_pill" style="width: 65px; height: 20px;"></div>
                </div>
                <div class="nobifashion_reels_skel_line" style="width: 125px; height: 10px; margin-top: 3px;"></div>
              </div>
            </div>

            <!-- Caption Skeleton -->
            <div class="nobifashion_reels_skel_caption">
              <div class="nobifashion_reels_skel_line" style="width: 90%; height: 12px;"></div>
              <div class="nobifashion_reels_skel_line" style="width: 55%; height: 12px; margin-top: 4px;"></div>
            </div>
          </div>

          <!-- Bottom Progress Bar Skeleton -->
          <div class="nobifashion_reels_skel_progress_bar"></div>

          <!-- Sóng ánh sáng Shimmer phủ lên trên cùng tất cả các thành phần -->
          <div class="nobifashion_reels_skeleton_wave"></div>
        </div>
      </div>

      <!-- Error Fallback -->
      <div class="nobifashion_reels_video_error">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p style="font-size:13px;font-weight:600;">Không thể tải luồng video này</p>
        <button type="button" class="nobifashion_reels_glass_btn" data-action="retry-video">Thử tải lại</button>
      </div>

      <!-- Bottom Creator & Product Overlay (TikTok Minimal Style) -->
      <div class="nobifashion_reels_video_overlay">
        ${
          firstProduct
            ? `
          <div class="nobifashion_reels_tiktok_product_bar" data-action="open-products" data-reel-id="${reel.id}">
            <div class="nobifashion_reels_tiktok_product_icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/>
              </svg>
            </div>
            <div class="nobifashion_reels_tiktok_product_info">
              <span class="nobifashion_reels_tiktok_product_name">${firstProduct.name}</span>
              <span class="nobifashion_reels_tiktok_product_sub">Chính hãng • Giá tốt: ${formatCurrency(firstProduct.price)}</span>
            </div>
            <svg class="nobifashion_reels_tiktok_product_arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </div>
        `
            : ''
        }

        <div class="nobifashion_reels_author_card_row">
          <div class="nobifashion_reels_avatar_circle_wrap">
            <img class="nobifashion_reels_avatar_circle_img" src="${reel.author.avatar}" alt="${reel.author.name}" />
          </div>
          <div class="nobifashion_reels_author_text_col">
            <div class="nobifashion_reels_author_top_line">
              <span class="nobifashion_reels_author_main_name">${reel.author.name}</span>
              <span class="nobifashion_reels_earth_badge" title="Toàn cầu / Công khai">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
              </span>
              <button type="button" class="nobifashion_reels_subscribe_text_btn ${reel.isFollowing ? 'nobifashion_reels_is_following' : ''}" data-action="toggle-follow" data-author-id="${reel.author.id || reel.author.name}">
                ${reel.isFollowing ? '✓ Đang theo dõi' : '+ Theo dõi'}
              </button>
            </div>
            <div class="nobifashion_reels_author_audio_line">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
              </svg>
              <span>${reel.author.name} • Âm thanh gốc</span>
            </div>
          </div>
        </div>

        <div class="nobifashion_reels_overlay_caption_wrap">
          <p class="nobifashion_reels_overlay_caption nobifashion_reels_clamp_2" data-action="toggle-caption">${reel.caption}</p>
          <button type="button" class="nobifashion_reels_caption_toggle_btn" data-action="toggle-caption">... thêm</button>
        </div>
      </div>

      <!-- Custom Progress Bar with Hover Thumbnail & Time Tooltip -->
      <div class="nobifashion_reels_progress_container" data-action="scrub-progress" role="slider" aria-label="Thanh tiến trình video" tabindex="0">
        <!-- Hover Thumbnail & Time Tooltip (Ảnh thu nhỏ chuẩn 9:16 dạng TikTok / Facebook Reels) -->
        <div class="nobifashion_reels_progress_tooltip" aria-hidden="true">
          <div class="nobifashion_reels_preview_thumb_box" style="width: 108px !important; height: 192px !important; aspect-ratio: 9/16 !important;">
            <!-- Skeleton 9:16 nền khi frame chưa sẵn sàng -->
            <div class="nobifashion_reels_preview_skeleton">
              <div class="nobifashion_reels_skeleton_wave"></div>
              <div class="nobifashion_reels_skel_mini_bottom">
                <div class="nobifashion_reels_skel_mini_avatar"></div>
                <div class="nobifashion_reels_skel_line" style="width: 48px; height: 5px;"></div>
                <div class="nobifashion_reels_skel_line" style="width: 68px; height: 5px;"></div>
              </div>
            </div>
            <video
              class="nobifashion_reels_preview_thumb_video"
              src="${reel.videoUrl}"
              poster="${reel.posterUrl}"
              muted
              playsinline
              preload="metadata"
              style="width: 100% !important; height: 100% !important; object-fit: cover !important;"
            ></video>
            <div class="nobifashion_reels_preview_time_badge">0:00 / 0:00</div>
          </div>
        </div>

        <div class="nobifashion_reels_progress_track">
          <div class="nobifashion_reels_progress_fill">
            <div class="nobifashion_reels_progress_thumb"></div>
          </div>
        </div>
      </div>

      <!-- Video Speed Pill & Vertical Popup (Góc dưới bên phải khung video) -->
      <div class="nobifashion_reels_speed_wrapper">
        <div class="nobifashion_reels_speed_popup" role="menu" aria-label="Tốc độ phát">
          <button type="button" class="nobifashion_reels_speed_opt" data-action="select-speed" data-speed="0.75">0.75x</button>
          <button type="button" class="nobifashion_reels_speed_opt nobifashion_reels_is_active" data-action="select-speed" data-speed="1">1.0x</button>
          <button type="button" class="nobifashion_reels_speed_opt" data-action="select-speed" data-speed="1.25">1.25x</button>
          <button type="button" class="nobifashion_reels_speed_opt" data-action="select-speed" data-speed="1.5">1.5x</button>
          <button type="button" class="nobifashion_reels_speed_opt" data-action="select-speed" data-speed="2">2.0x</button>
        </div>
        <button
          type="button"
          class="nobifashion_reels_speed_btn"
          data-action="toggle-speed-menu"
          aria-label="Tốc độ phát"
          title="Chọn tốc độ phát video"
        >
          <span class="nobifashion_reels_speed_btn_text">1.0x</span>
        </button>
      </div>
    `;

    return slide;
  }

  getMuteIconSvg(isMuted) {
    return isMuted
      ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>`
      : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>`;
  }

  updateSlideOverlayData(slideEl, reel) {
    if (!slideEl || !reel) return;
    const followBtn = slideEl.querySelector('[data-action="toggle-follow"]');
    if (followBtn) {
      followBtn.textContent = reel.isFollowing ? '✓ Đang theo dõi' : '+ Theo dõi';
      followBtn.classList.toggle('nobifashion_reels_is_following', Boolean(reel.isFollowing));
    }
    const muteBtn = slideEl.querySelector('.nobifashion_reels_video_mute_btn');
    if (muteBtn) {
      muteBtn.innerHTML = this.getMuteIconSvg(this.store.muted);
      muteBtn.classList.toggle('nobifashion_reels_is_muted', Boolean(this.store.muted));
      muteBtn.setAttribute('aria-label', this.store.muted ? 'Bật âm thanh' : 'Tắt âm thanh');
      muteBtn.setAttribute('title', this.store.muted ? 'Bật âm thanh' : 'Tắt âm thanh');
    }
    const speedBtnText = slideEl.querySelector('.nobifashion_reels_speed_btn_text');
    if (speedBtnText) {
      speedBtnText.textContent = '1.0x';
    }
    const speedOpts = slideEl.querySelectorAll('.nobifashion_reels_speed_opt');
    speedOpts.forEach((opt) => {
      opt.classList.toggle('nobifashion_reels_is_active', opt.getAttribute('data-speed') === '1');
    });
  }

  renderActionRail(reel) {
    if (!this.actionRailEl || !reel) return;
    const productCount = reel.products ? reel.products.length : 0;

    this.actionRailEl.innerHTML = `
      <div class="nobifashion_reels_action_item">
        <button
          type="button"
          class="nobifashion_reels_action_btn ${reel.isLiked ? 'nobifashion_reels_is_liked' : ''}"
          data-action="toggle-like"
          data-reel-id="${reel.id}"
          data-tooltip="${reel.isLiked ? 'Bỏ thích' : 'Yêu thích'}"
          aria-label="Thích video"
        >
          <svg width="21" height="21" viewBox="0 0 24 24" fill="${reel.isLiked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.9">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
        </button>
        <span class="nobifashion_reels_action_count" data-role="like-count">${formatCount(reel.likesCount)}</span>
      </div>

      <div class="nobifashion_reels_action_item">
        <button
          type="button"
          class="nobifashion_reels_action_btn"
          data-action="toggle-comments-panel"
          data-reel-id="${reel.id}"
          data-tooltip="Bình luận"
          aria-label="Mở bình luận"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
          </svg>
        </button>
        <span class="nobifashion_reels_action_count" data-role="comment-count">${formatCount(reel.commentsCount)}</span>
      </div>

      <div class="nobifashion_reels_action_item">
        <button
          type="button"
          class="nobifashion_reels_action_btn ${reel.isSaved ? 'nobifashion_reels_is_saved' : ''}"
          data-action="toggle-save"
          data-reel-id="${reel.id}"
          data-tooltip="${reel.isSaved ? 'Đã lưu' : 'Lưu video'}"
          aria-label="Lưu video"
        >
          <svg width="19" height="19" viewBox="0 0 24 24" fill="${reel.isSaved ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.9">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
          </svg>
        </button>
        <span class="nobifashion_reels_action_count">${reel.isSaved ? 'Đã lưu' : 'Lưu'}</span>
      </div>

      <div class="nobifashion_reels_action_item">
        <button
          type="button"
          class="nobifashion_reels_action_btn"
          data-action="open-products"
          data-reel-id="${reel.id}"
          data-tooltip="Sản phẩm trong video (${productCount})"
          aria-label="Xem sản phẩm liên quan"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </button>
        <span class="nobifashion_reels_action_count">Mua (${productCount})</span>
      </div>

      <div class="nobifashion_reels_action_item">
        <button
          type="button"
          class="nobifashion_reels_action_btn"
          data-action="open-share"
          data-reel-id="${reel.id}"
          data-tooltip="Chia sẻ"
          aria-label="Chia sẻ video"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
            <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
          </svg>
        </button>
        <span class="nobifashion_reels_action_count" data-role="share-count">${formatCount(reel.sharesCount)}</span>
      </div>

      <div class="nobifashion_reels_action_item">
        <button
          type="button"
          class="nobifashion_reels_action_btn"
          data-action="open-more-menu"
          data-reel-id="${reel.id}"
          data-tooltip="Tùy chọn khác"
          aria-label="Menu tùy chọn"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
          </svg>
        </button>
      </div>
    `;
  }

  getActiveSlideElement(reelId) {
    return this.slideMap.get(reelId) || null;
  }

  getAllSlideElements() {
    return Array.from(this.slideMap.values());
  }
}


// --- assets/js/reels-navigation.js ---
/**
 * NOBI FASHION REELS — CENTRAL NAVIGATION CONTROLLER
 * Coordinates all navigation inputs (Mouse Wheel, Trackpad, Touch Swipe,
 * Keyboard ArrowUp/ArrowDown, Prev/Next Buttons, and Search/Notification Jump)
 * through a single unified lock so transitions never collide or skip videos.
 */

class ReelsNavigationController {
  constructor({ store, renderer, player, onReelChanged }) {
    this.store = store;
    this.renderer = renderer;
    this.player = player;
    this.onReelChanged = onReelChanged;

    this.activeIndex = 0;
    this.isTransitioning = false;
    this.transitionTimer = null;
    this.wheelController = null;

    this.prevBtn = document.getElementById('nobifashion_reels_prev_btn');
    this.nextBtn = document.getElementById('nobifashion_reels_next_btn');
  }

  setWheelController(wheelController) {
    this.wheelController = wheelController;
  }

  getReelsList() {
    return this.store.getFilteredReels();
  }

  getActiveReel() {
    const list = this.getReelsList();
    if (list.length === 0) return null;
    const clamped = Math.max(0, Math.min(this.activeIndex, list.length - 1));
    this.activeIndex = clamped;
    return list[clamped];
  }

  /**
   * Moves by exactly +1 (next) or -1 (previous) video.
   * Returns true if a transition was initiated, false if blocked by lock or bounds.
   */
  step(direction) {
    if (this.isTransitioning) return false;

    const list = this.getReelsList();
    if (list.length === 0) return false;

    const delta = direction > 0 ? 1 : -1;
    const targetIndex = this.activeIndex + delta;

    if (targetIndex < 0 || targetIndex >= list.length) {
      this.updateNavButtons(list.length);
      return false;
    }

    this.isTransitioning = true;
    this.activeIndex = targetIndex;

    // Tự động tải tiếp trang khi người dùng cuộn gần tới cuối danh sách video
    if (this.activeIndex >= list.length - 3 && this.store.hasMore && !this.store.isLoadingMore) {
      this.store.fetchNextPage().then((loaded) => {
        if (loaded) {
          const updatedList = this.getReelsList();
          this.updateNavButtons(updatedList.length);
        }
      });
    }

    this.renderer.renderWindow(list, this.activeIndex, false);
    this.syncActivePlayback(list);
    this.updateNavButtons(list.length);

    if (this.onReelChanged) {
      this.onReelChanged(list[this.activeIndex], this.activeIndex, list.length);
    }

    // Release transition lock after slide transition completes (420ms)
    if (this.transitionTimer) clearTimeout(this.transitionTimer);
    this.transitionTimer = setTimeout(() => {
      this.isTransitioning = false;
      if (this.wheelController) {
        this.wheelController.notifyNavigationComplete();
      }
    }, 430);

    return true;
  }

  /**
   * Jumps directly to a specific reel ID (e.g. from search suggestion or notification)
   * Tự động nạp video cũ/mới từ Database nếu chưa có trong bộ nhớ cục bộ
   */
  async jumpToReelId(reelId, fallbackReel = null) {
    const targetId = Number(reelId);
    let list = this.getReelsList();
    let idx = list.findIndex((r) => Number(r.id) === targetId);

    if (idx === -1) {
      let targetReel = fallbackReel;
      if (!targetReel) {
        try {
          const res = await fetch(`/api/v1/reels/${targetId}`);
          if (res.ok) {
            const json = await res.json();
            if (json.success && json.data) {
              targetReel = json.data;
            }
          }
        } catch (_) {}
      }

      if (targetReel) {
        const exists = this.store.reels.some((r) => Number(r.id) === targetId);
        if (!exists) {
          this.store.reels.unshift(targetReel);
        }
        list = this.getReelsList();
        idx = list.findIndex((r) => Number(r.id) === targetId);
      }
    }

    if (idx === -1) return false;

    this.isTransitioning = false;
    if (this.transitionTimer) clearTimeout(this.transitionTimer);
    this.activeIndex = idx;

    this.renderer.renderWindow(list, this.activeIndex, true);
    this.syncActivePlayback(list);
    this.updateNavButtons(list.length);

    if (this.onReelChanged) {
      this.onReelChanged(list[this.activeIndex], this.activeIndex, list.length);
    }
    return true;
  }

  /**
   * Re-renders current list after filter/search/hide changes
   */
  refreshList(resetIndex = false) {
    const list = this.getReelsList();
    if (resetIndex || this.activeIndex >= list.length) {
      this.activeIndex = 0;
    }
    this.isTransitioning = false;
    if (this.transitionTimer) clearTimeout(this.transitionTimer);

    this.renderer.renderWindow(list, this.activeIndex, true);
    this.syncActivePlayback(list);
    this.updateNavButtons(list.length);

    if (this.onReelChanged) {
      this.onReelChanged(list[this.activeIndex] || null, this.activeIndex, list.length);
    }
  }

  syncActivePlayback(list) {
    const activeReel = list[this.activeIndex];
    if (!activeReel) {
      this.player.detachCurrent();
      return;
    }
    const activeSlide = this.renderer.getActiveSlideElement(activeReel.id);
    const allSlides = this.renderer.getAllSlideElements();
    this.player.attachToSlide(activeSlide, activeReel, allSlides);
  }

  updateNavButtons(total) {
    if (this.prevBtn) {
      this.prevBtn.disabled = total === 0 || this.activeIndex <= 0;
    }
    if (this.nextBtn) {
      this.nextBtn.disabled = total === 0 || this.activeIndex >= total - 1;
    }
  }
}


// --- assets/js/reels-comments.js ---
/**
 * NOBI FASHION REELS — COMMENTS SIDEBAR & BOTTOM SHEET CONTROLLER
 * Renders creator header, tagged product preview card, comment sorting,
 * nested replies, comment liking, reply banner, and emoji picker.
 */

const EMOJI_LIST = ['🖤', '🤍', '✨', '🔥', '👏', '😍', '🥂', '💎', '🧥', '👗', '👜', '💯'];

const FACEBOOK_REACTIONS = [
  {
    type: 'like',
    label: 'Thích',
    emoji: '👍',
    color: '#1877f2',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#1877F2" cx="18" cy="18" r="18"/><path fill="#FFFFFF" d="M11 16h3v10h-3zm14 2.5c0-.8-.6-1.5-1.5-1.5h-4.6c.5-1 .7-2.1.7-3.2 0-2.5-1.9-3.8-2.9-3.8-.7 0-1.2.5-1.2 1.2v2.3c0 1.6-1 3.1-2.5 3.7V26h9.8c1 0 1.9-.8 2-1.8l.8-5c.2-.4.1-.9-.1-1.3z"/></svg>`
  },
  {
    type: 'love',
    label: 'Yêu thích',
    emoji: '❤️',
    color: '#f33e5b',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#F33E5B" cx="18" cy="18" r="18"/><path fill="#FFFFFF" d="M18 27.5l-1.5-1.4C10.8 21 7 17.6 7 13.3 7 9.8 9.7 7 13.2 7c2 0 3.9 1 4.8 2.5C18.9 8 20.8 7 22.8 7 26.3 7 29 9.8 29 13.3c0 4.3-3.8 7.7-9.5 12.8L18 27.5z"/></svg>`
  },
  {
    type: 'care',
    label: 'Thương thương',
    emoji: '🥰',
    color: '#f7b125',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#F7B125" cx="18" cy="18" r="18"/><path fill="none" stroke="#583518" stroke-width="2" stroke-linecap="round" d="M10 13c1.5-1.5 4-1.5 5.5 0M20.5 13c1.5-1.5 4-1.5 5.5 0"/><circle fill="#FF9A76" cx="10" cy="19" r="2.2"/><circle fill="#FF9A76" cx="26" cy="19" r="2.2"/><path fill="#F33E5B" d="M18 28.5c-4 0-7.2-3.2-7.2-7.2 0-2.2 1.8-4 4-4 1.5 0 2.5.8 3.2 1.8.7-1 1.7-1.8 3.2-1.8 2.2 0 4 1.8 4 4 0 4-3.2 7.2-7.2 7.2z"/><path fill="none" stroke="#583518" stroke-width="1.8" stroke-linecap="round" d="M15 22.5c.8.8 2.2.8 3 0"/></svg>`
  },
  {
    type: 'haha',
    label: 'Haha',
    emoji: '😆',
    color: '#f7b125',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#F7B125" cx="18" cy="18" r="18"/><path fill="none" stroke="#583518" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M9.5 13.5l4 2.5-4 2.5M26.5 13.5l-4 2.5 4 2.5"/><path fill="#583518" d="M10 20.5c0 4.8 3.6 8 8 8s8-3.2 8-8H10z"/><path fill="#FFFFFF" d="M11.5 20.5h13c-.3 2-2.5 3.5-6.5 3.5s-6.2-1.5-6.5-3.5z"/><path fill="#F33E5B" d="M14.5 24.5c.9 1.8 2.1 2.5 3.5 2.5s2.6-.7 3.5-2.5c-.9-.6-2.2-.9-3.5-.9s-2.6.3-3.5.9z"/></svg>`
  },
  {
    type: 'wow',
    label: 'Wow',
    emoji: '😮',
    color: '#f7b125',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#F7B125" cx="18" cy="18" r="18"/><path fill="none" stroke="#663E10" stroke-width="2" stroke-linecap="round" d="M10 9.5c1.5-1 3.8-1 5.2 0M20.8 9.5c1.4-1 3.7-1 5.2 0"/><circle fill="#583518" cx="13" cy="14.5" r="3"/><circle fill="#583518" cx="23" cy="14.5" r="3"/><circle fill="#FFFFFF" cx="12" cy="13.5" r="1.1"/><circle fill="#FFFFFF" cx="22" cy="13.5" r="1.1"/><ellipse fill="#583518" cx="18" cy="24" rx="4.8" ry="6.8"/></svg>`
  },
  {
    type: 'sad',
    label: 'Buồn',
    emoji: '😢',
    color: '#f7b125',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#F7B125" cx="18" cy="18" r="18"/><path fill="none" stroke="#663E10" stroke-width="2" stroke-linecap="round" d="M10 12c1.8 1 4 1 5.5 0M20.5 12c1.5 1 3.7 1 5.5 0"/><circle fill="#583518" cx="13.5" cy="16.5" r="2.8"/><circle fill="#583518" cx="22.5" cy="16.5" r="2.8"/><circle fill="#FFFFFF" cx="12.7" cy="15.8" r="1"/><circle fill="#FFFFFF" cx="21.7" cy="15.8" r="1"/><path fill="none" stroke="#583518" stroke-width="2.5" stroke-linecap="round" d="M14 25c1.8-1.5 6.2-1.5 8 0"/><path fill="#18ACFE" d="M10 18.5c0 2.2-2.2 5-2.2 5s-2.2-2.8-2.2-5c0-1.3 1-2.3 2.2-2.3s2.2 1 2.2 2.3z"/></svg>`
  },
  {
    type: 'angry',
    label: 'Phẫn nộ',
    emoji: '😡',
    color: '#eb5a36',
    svg: `<svg viewBox="0 0 36 36" width="32" height="32"><circle fill="#EB5A36" cx="18" cy="18" r="18"/><path fill="none" stroke="#583518" stroke-width="2.8" stroke-linecap="round" d="M9.5 12.5l6 3.2M26.5 12.5l-6 3.2"/><circle fill="#583518" cx="13.5" cy="18.5" r="2.4"/><circle fill="#583518" cx="22.5" cy="18.5" r="2.4"/><ellipse fill="#583518" cx="18" cy="25.5" rx="3.8" ry="4.8"/></svg>`
  }
];

class CommentsController {
  constructor({ store, sidebarEl, onHashtagClick, onOpenProducts, onFollowToggle, showToast }) {
    this.store = store;
    this.sidebarEl = sidebarEl;
    this.onHashtagClick = onHashtagClick;
    this.onOpenProducts = onOpenProducts;
    this.onFollowToggle = onFollowToggle;
    this.showToast = showToast;

    this.currentReel = null;
    this.sortBy = 'popular'; // 'popular' | 'newest'
    this.replyToComment = null; // { id, author }
    this.expandedReplies = new Set();
    this.isLoadingMoreComments = false;

    this.init();
  }

  init() {
    if (!this.sidebarEl) return;

    // Render static shell inside sidebar
    this.sidebarEl.innerHTML = `
      <div class="nobifashion_reels_bottom_sheet_handle" style="display:none;" data-action="close-mobile-comments" role="button" tabindex="0" aria-label="Đóng bảng bình luận">
        <div class="nobifashion_reels_bottom_sheet_bar"></div>
      </div>

      <div class="nobifashion_reels_sidebar_header" id="nobifashion_reels_sidebar_creator_box"></div>

      <div class="nobifashion_reels_sidebar_stats_bar">
        <div id="nobifashion_reels_sidebar_stats_text" class="nobifashion_reels_sidebar_stats_text">
          <span><strong class="nobifashion_reels_stat_num">0</strong> lượt thích</span>
          <span class="nobifashion_reels_stat_sep">•</span>
          <span><strong class="nobifashion_reels_stat_num">0</strong> bình luận</span>
        </div>
        <div class="nobifashion_reels_sort_wrap">
          <label class="nobifashion_reels_sr_only" for="nobifashion_reels_comment_sort">Sắp xếp bình luận</label>
          <select id="nobifashion_reels_comment_sort" class="nobifashion_reels_sort_select" aria-label="Sắp xếp bình luận">
            <option value="popular">Phù hợp nhất</option>
            <option value="newest">Mới nhất</option>
          </select>
          <svg class="nobifashion_reels_sort_arrow" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
      </div>

      <div class="nobifashion_reels_comments_list nobifashion_reels_scrollable" id="nobifashion_reels_comments_list"></div>

      <div class="nobifashion_reels_comment_composer">
        <div class="nobifashion_reels_reply_banner" id="nobifashion_reels_reply_banner">
          <span id="nobifashion_reels_reply_label">Đang trả lời...</span>
          <button type="button" data-action="cancel-reply" aria-label="Hủy trả lời" style="font-weight:700;">✕</button>
        </div>

        <div class="nobifashion_reels_emoji_picker" id="nobifashion_reels_emoji_picker">
          ${EMOJI_LIST.map(
            (em) => `<button type="button" class="nobifashion_reels_emoji_btn" data-action="insert-emoji" data-emoji="${em}">${em}</button>`
          ).join('')}
        </div>

        <form id="nobifashion_reels_comment_form" class="nobifashion_reels_composer_row" autocomplete="off">
          <div style="width:22px;height:22px;border-radius:50%;background:${(window.__ANON_USER__ && window.__ANON_USER__.avatar_color) || '#3b82f6'};display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;" title="Biệt danh: ${(window.__ANON_USER__ && window.__ANON_USER__.nickname) || 'Bạn'}">
            ${(window.__ANON_USER__ && window.__ANON_USER__.avatar_key && ANIMAL_ICONS[window.__ANON_USER__.avatar_key]) || '🐱'}
          </div>
          <input
            type="text"
            id="nobifashion_reels_comment_input"
            class="nobifashion_reels_composer_input"
            placeholder="Bình luận với tên ${(window.__ANON_USER__ && window.__ANON_USER__.nickname) || 'bạn'}..."
            maxlength="400"
            aria-label="Nhập nội dung bình luận"
          />
          <button type="button" class="nobifashion_reels_emoji_trigger" data-action="toggle-emoji" aria-label="Chọn biểu tượng cảm xúc" style="font-size:13px;">
            ☺
          </button>
          <button type="submit" class="nobifashion_reels_send_btn" aria-label="Gửi bình luận">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
          </button>
        </form>
      </div>
    `;

    this.creatorBoxEl = this.sidebarEl.querySelector('#nobifashion_reels_sidebar_creator_box');
    this.statsTextEl = this.sidebarEl.querySelector('#nobifashion_reels_sidebar_stats_text');
    this.sortSelectEl = this.sidebarEl.querySelector('#nobifashion_reels_comment_sort');
    this.commentsListEl = this.sidebarEl.querySelector('#nobifashion_reels_comments_list');
    this.replyBannerEl = this.sidebarEl.querySelector('#nobifashion_reels_reply_banner');
    this.replyLabelEl = this.sidebarEl.querySelector('#nobifashion_reels_reply_label');
    this.emojiPickerEl = this.sidebarEl.querySelector('#nobifashion_reels_emoji_picker');
    this.commentFormEl = this.sidebarEl.querySelector('#nobifashion_reels_comment_form');
    this.commentInputEl = this.sidebarEl.querySelector('#nobifashion_reels_comment_input');

    this.bindEvents();
  }

  bindEvents() {
    this.sortSelectEl.addEventListener('change', async (e) => {
      this.sortBy = e.target.value;
      if (this.currentReel) {
        try {
          await this.store.fetchComments(this.currentReel.id, 1, false, this.sortBy);
        } catch (_) {}
        this.renderCommentsList();
      }
    });

    // Infinite scroll tự động tải thêm comment cha khi cuộn gần hết
    this.commentsListEl.addEventListener('scroll', () => {
      if (this.isLoadingMoreComments || !this.currentReel) return;
      if (this.currentReel.commentsHasMore === false) return;
      const { scrollTop, scrollHeight, clientHeight } = this.commentsListEl;
      if (scrollHeight - scrollTop - clientHeight < 160) {
        this.loadMoreParentComments();
      }
    });

    this.commentFormEl.addEventListener('submit', (e) => {
      e.preventDefault();
      this.submitComment();
    });

    this.sidebarEl.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const action = btn.getAttribute('data-action');

      if (action === 'like-comment') {
        const commentId = btn.getAttribute('data-comment-id');
        if (this.currentReel && commentId) {
          this.store.toggleCommentLike(this.currentReel.id, commentId).then(() => {
            this.renderCommentsList();
          });
        }
      } else if (action === 'react-comment') {
        const commentId = btn.getAttribute('data-comment-id');
        const reactionType = btn.getAttribute('data-reaction-type');
        if (this.currentReel && commentId && reactionType) {
          this.store.toggleCommentLike(this.currentReel.id, commentId, reactionType).then(() => {
            this.renderCommentsList();
          });
        }
      } else if (action === 'reply-comment') {
        const commentId = btn.getAttribute('data-comment-id');
        const rootId = btn.getAttribute('data-root-id') || commentId;
        const author = btn.getAttribute('data-author');
        this.setReplyTarget(commentId, author, rootId);
      } else if (action === 'cancel-reply') {
        this.clearReplyTarget();
      } else if (action === 'toggle-replies') {
        const commentId = btn.getAttribute('data-comment-id');
        if (this.expandedReplies.has(commentId)) {
          this.expandedReplies.delete(commentId);
        } else {
          this.expandedReplies.add(commentId);
        }
        this.renderCommentsList(true);
      } else if (action === 'load-more-replies') {
        const commentId = btn.getAttribute('data-comment-id');
        if (this.currentReel && commentId) {
          btn.disabled = true;
          btn.innerHTML = '<span>↳ Đang tải thêm câu trả lời...</span>';
          this.store.fetchMoreCommentReplies(this.currentReel.id, commentId).then(() => {
            this.renderCommentsList(true);
          }).catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<span>↳ Thử lại xem thêm...</span>';
          });
        }
      } else if (action === 'toggle-emoji') {
        this.emojiPickerEl.classList.toggle('nobifashion_reels_is_open');
      } else if (action === 'insert-emoji') {
        const emoji = btn.getAttribute('data-emoji') || '';
        this.commentInputEl.value += emoji;
        this.commentInputEl.focus();
      } else if (action === 'sidebar-hashtag') {
        const tag = btn.getAttribute('data-hashtag');
        if (tag && this.onHashtagClick) this.onHashtagClick(tag);
      } else if (action === 'sidebar-open-products') {
        if (this.currentReel && this.onOpenProducts) {
          this.onOpenProducts(this.currentReel.id);
        }
      } else if (action === 'sidebar-follow') {
        if (this.currentReel && this.onFollowToggle) {
          this.onFollowToggle(this.currentReel.author.id);
        }
      } else if (action === 'close-mobile-comments') {
        const ws = document.querySelector('.nobifashion_reels_workspace');
        if (ws) ws.classList.remove('nobifashion_reels_mobile_comments_open');
      }
    });
  }

  async renderForReel(reel) {
    this.currentReel = reel;
    this.clearReplyTarget();
    if (this.emojiPickerEl) {
      this.emojiPickerEl.classList.remove('nobifashion_reels_is_open');
    }

    if (!reel) {
      this.creatorBoxEl.innerHTML = '';
      this.statsTextEl.textContent = '0 lượt thích • 0 bình luận';
      this.commentsListEl.innerHTML = `<p style="color:var(--nobifashion_reels_text_muted);font-size:13px;">Không có video nào đang chọn.</p>`;
      return;
    }

    this.renderCreatorHeader(reel);
    this.updateStats(reel);

    // Hiển thị trạng thái đang tải trước khi có comment thật từ server
    this.commentsListEl.innerHTML = `
      <div style="text-align:center;padding:36px 16px;color:var(--nobifashion_reels_text_muted);font-size:13px;">
        <span style="font-size:18px;">⏳</span><br>Đang tải bình luận...
      </div>
    `;

    try {
      await this.store.fetchComments(reel.id, 1, false, this.sortBy);
      this.updateStats(reel);
      this.renderCommentsList();
      const railCount = document.querySelector('[data-role="comment-count"]');
      if (railCount) {
        railCount.textContent = formatCount(reel.commentsCount);
      }
    } catch (e) {
      this.renderCommentsList();
    }
  }

  async loadMoreParentComments() {
    if (this.isLoadingMoreComments || !this.currentReel) return;
    if (this.currentReel.commentsHasMore === false) return;

    this.isLoadingMoreComments = true;
    const nextPage = (this.currentReel.commentsPage || 1) + 1;

    let loader = this.commentsListEl.querySelector('.nobifashion_reels_comments_infinite_loader');
    if (!loader) {
      loader = document.createElement('div');
      loader.className = 'nobifashion_reels_comments_infinite_loader';
      loader.style.cssText = 'text-align:center;padding:10px 0;font-size:11px;color:var(--nobifashion_reels_text_muted);';
      loader.innerHTML = '<span>⏳ Đang tải thêm bình luận...</span>';
      this.commentsListEl.appendChild(loader);
    }

    try {
      await this.store.fetchComments(this.currentReel.id, nextPage, true, this.sortBy);
      this.renderCommentsList(true);
    } catch (_) {
    } finally {
      this.isLoadingMoreComments = false;
      const curLoader = this.commentsListEl.querySelector('.nobifashion_reels_comments_infinite_loader');
      if (curLoader) curLoader.remove();
    }
  }

  renderCreatorHeader(reel) {
    const firstProd = reel.products && reel.products[0];
    const tagsHtml = reel.hashtags
      .map(
        (t) =>
          `<button type="button" class="nobifashion_reels_sidebar_tag" data-action="sidebar-hashtag" data-hashtag="${t}">${t}</button>`
      )
      .join('');

    this.creatorBoxEl.innerHTML = `
      <div class="nobifashion_reels_sidebar_creator_row">
        <div class="nobifashion_reels_sidebar_creator_info">
          <img class="nobifashion_reels_sidebar_avatar" src="${reel.author.avatar}" alt="${reel.author.name}" />
          <div class="nobifashion_reels_sidebar_creator_text">
            <div class="nobifashion_reels_sidebar_creator_name">
              <span>${reel.author.name}</span>
              ${
                reel.author.verified
                  ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>`
                  : ''
              }
            </div>
            <div class="nobifashion_reels_sidebar_creator_sub">${reel.author.handle} • ${reel.createdAt}</div>
          </div>
        </div>
      </div>

      <div class="nobifashion_reels_sidebar_desc_wrap">
        <p class="nobifashion_reels_sidebar_desc nobifashion_reels_clamp_2" data-action="toggle-sidebar-caption">${reel.caption}</p>
        <button type="button" class="nobifashion_reels_sidebar_toggle_btn" data-action="toggle-sidebar-caption">... thêm</button>
      </div>
      <div class="nobifashion_reels_sidebar_hashtags">${tagsHtml}</div>

      ${
        firstProd
          ? `
        <div class="nobifashion_reels_sidebar_product_card" data-action="sidebar-open-products" role="button" tabindex="0">
          <div class="nobifashion_reels_sidebar_product_left">
            <img class="nobifashion_reels_sidebar_product_img" src="${firstProd.image}" alt="${firstProd.name}" />
            <div style="min-width:0;">
              <div class="nobifashion_reels_sidebar_product_title">${firstProd.name}</div>
              <div class="nobifashion_reels_sidebar_product_price">${formatCurrency(firstProd.price)} • ${reel.products.length} sản phẩm</div>
            </div>
          </div>
          <span class="nobifashion_reels_btn_primary" style="padding:5px 10px;font-size:11px;">Xem chi tiết</span>
        </div>
      `
          : ''
      }
    `;
  }

  updateStats(reel) {
    if (!this.statsTextEl || !reel) return;
    this.statsTextEl.innerHTML = `
      <span><strong class="nobifashion_reels_stat_num">${formatCount(reel.likesCount)}</strong> lượt thích</span>
      <span class="nobifashion_reels_stat_sep">•</span>
      <span><strong class="nobifashion_reels_stat_num">${formatCount(reel.commentsCount)}</strong> bình luận</span>
    `;
  }

  getReactionsPopupHtml(commentId) {
    return `
      <div class="nobifashion_reels_reactions_popup" role="toolbar" aria-label="Lựa chọn cảm xúc">
        ${FACEBOOK_REACTIONS.map(
          (r) => `
          <button
            type="button"
            class="nobifashion_reels_reaction_btn"
            data-action="react-comment"
            data-comment-id="${commentId}"
            data-reaction-type="${r.type}"
            data-reaction-label="${r.label}"
            aria-label="${r.label}"
          >
            ${r.svg}
          </button>
        `
        ).join('')}
      </div>
    `;
  }

  getCommentLikeBtnContent(c) {
    if (c.isLiked && c.userReaction) {
      if (c.userReaction === 'love') {
        return `<svg width="14" height="14" viewBox="0 0 24 24" fill="#f33e5b" stroke="#f33e5b" stroke-width="1"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
      }
      const matched = FACEBOOK_REACTIONS.find((r) => r.type === c.userReaction);
      if (matched && matched.emoji) {
        return `<span style="font-size:13px;line-height:1;">${matched.emoji}</span>`;
      }
      return `<svg width="14" height="14" viewBox="0 0 24 24" fill="#1877f2" stroke="#1877f2" stroke-width="1.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>`;
    }

    if (c.isLiked) {
      return `<svg width="14" height="14" viewBox="0 0 24 24" fill="#1877f2" stroke="#1877f2" stroke-width="1.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>`;
    }

    return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>`;
  }

  getCommentReactionsBadge(c) {
    if (c.userReaction) {
      const matched = FACEBOOK_REACTIONS.find((r) => r.type === c.userReaction);
      if (matched) {
        return matched.type === 'like' ? '👍❤️' : `${matched.emoji}👍`;
      }
    }
    return '👍❤️';
  }

  getKnownAuthors() {
    const set = new Set();
    if (this.currentReel) {
      if (this.currentReel.author) set.add(this.currentReel.author);
      if (this.currentReel.brand_name) set.add(this.currentReel.brand_name);
      if (this.currentReel.author_name) set.add(this.currentReel.author_name);
      if (Array.isArray(this.currentReel.comments)) {
        this.currentReel.comments.forEach((cm) => {
          if (cm.nickname) set.add(cm.nickname);
          if (cm.author) set.add(cm.author);
          if (Array.isArray(cm.replies)) {
            cm.replies.forEach((rp) => {
              if (rp.nickname) set.add(rp.nickname);
              if (rp.author) set.add(rp.author);
            });
          }
        });
      }
    }
    return Array.from(set);
  }

  formatCommentText(rawText, authors = null) {
    if (!rawText) return '';
    let text = this.escapeHtml(rawText);
    const knownAuthors = authors || this.getKnownAuthors();

    // 1. Khớp chính xác tên tác giả đã biết (ưu tiên tên dài trước)
    const sortedAuthors = [...new Set(knownAuthors.filter(Boolean))].sort((a, b) => b.length - a.length);
    for (const author of sortedAuthors) {
      const escaped = this.escapeHtml(author).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp('(^|\\s)@(' + escaped + ')(?=\\s|$|[.,:!?])', 'g');
      text = text.replace(regex, '$1<span class="nobifashion_reels_mention">@$2</span>');
    }

    // 2. Mẫu tổng quát cho nickname Nobi (ví dụ @Heo Lắc Lư 759, @Cá Voi Mơ Mộng 38)
    text = text.replace(/(^|\s)@([\p{L}][\p{L}\s]{1,30}?\s+\d{1,4})(?=\s|$|[.,:!?])/gu, (match, prefix, name) => {
      if (match.includes('class="nobifashion_reels_mention"')) return match;
      return prefix + '<span class="nobifashion_reels_mention">@' + name.trim() + '</span>';
    });

    // 3. Mẫu tổng quát cho username/brand viết liền (ví dụ @Coolmate, @admin, @mixigaming)
    text = text.replace(/(^|\s)@([\p{L}\p{N}_.\-]+)(?=\s|$|[.,:!?])/gu, (match, prefix, name) => {
      if (match.includes('class="nobifashion_reels_mention"')) return match;
      return prefix + '<span class="nobifashion_reels_mention">@' + name + '</span>';
    });

    return text;
  }

  renderCommentItem(c, depth = 0, rootId = null) {
    const currentRootId = depth === 0 ? c.id : rootId;
    const replies = c.replies || [];
    const isExpanded = this.expandedReplies.has(String(c.id)) || this.expandedReplies.has(c.id);
    const hasReplies = replies.length > 0;
    const isAuthor = Boolean(c.isAuthor);
    const isVerified = Boolean(c.isVerified);
    const reactionClass = c.userReaction ? `nobifashion_reels_reaction_${c.userReaction}` : '';

    const animalIcon = (c.avatar_key && ANIMAL_ICONS[c.avatar_key]) ? ANIMAL_ICONS[c.avatar_key] : (c.initials || '🐱');
    const avatarBg = c.avatar_color || '#3b82f6';
    const authorName = c.nickname || c.author || 'Khách Nobi';

    const stickerHtml = c.sticker && c.sticker.asset_url
      ? `<div style="margin-top:4px;"><img src="${c.sticker.asset_url}" alt="${this.escapeHtml(c.sticker.name || 'Sticker')}" style="width:42px;height:42px;object-fit:contain;display:block;" /></div>`
      : '';

    return `
      <div class="nobifashion_reels_comment_item ${depth > 0 ? 'nobifashion_reels_comment_reply_item' : ''}" data-comment-id="${c.id}" data-depth="${depth}">
        <div class="nobifashion_reels_comment_avatar" style="background:${avatarBg};color:#ffffff;display:flex;align-items:center;justify-content:center;" title="${this.escapeHtml(authorName)}">${animalIcon}</div>
        <div class="nobifashion_reels_comment_body">
          <div class="nobifashion_reels_comment_header">
            <span class="nobifashion_reels_comment_author">${this.escapeHtml(authorName)}</span>
            ${
              isVerified
                ? `<span class="nobifashion_reels_comment_verified" title="Đã xác minh">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="#0ea5e9"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                   </span>`
                : ''
            }
            <span class="nobifashion_reels_comment_time">• ${c.time}</span>
            ${
              isAuthor
                ? `<span class="nobifashion_reels_comment_author_tag">🎤 Tác giả</span>`
                : ''
            }
          </div>
          <p class="nobifashion_reels_comment_text">${this.formatCommentText(c.text)}</p>
          ${stickerHtml}
          <div class="nobifashion_reels_comment_actions">
            <!-- Nút Like kèm thanh chọn cảm xúc Facebook khi hover -->
            <div class="nobifashion_reels_comment_like_wrapper">
              <button
                type="button"
                class="nobifashion_reels_comment_like_btn ${c.isLiked ? 'nobifashion_reels_is_liked' : ''} ${reactionClass}"
                data-action="like-comment"
                data-comment-id="${c.id}"
                aria-label="${c.isLiked ? 'Bỏ thích bình luận' : 'Thích bình luận'}"
              >
                ${this.getCommentLikeBtnContent(c)}
              </button>
              ${this.getReactionsPopupHtml(c.id)}
            </div>

            <div class="nobifashion_reels_comment_likes_badge">
              <span class="nobifashion_reels_comment_reactions">${this.getCommentReactionsBadge(c)}</span>
              <span class="nobifashion_reels_comment_likes_num">${(c.likes || 0).toLocaleString('vi-VN')}</span>
            </div>
            <button
              type="button"
              class="nobifashion_reels_comment_reply_text_btn"
              data-action="reply-comment"
              data-comment-id="${c.id}"
              data-root-id="${currentRootId}"
              data-author="${this.escapeHtml(authorName)}"
            >
              Trả lời
            </button>
          </div>

          ${
            hasReplies
              ? `
            <div class="nobifashion_reels_replies_wrapper">
              <button
                type="button"
                class="nobifashion_reels_view_replies_btn"
                data-action="toggle-replies"
                data-comment-id="${c.id}"
              >
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="transform:${isExpanded ? 'rotate(180deg)' : 'rotate(0deg)'};transition:transform 0.2s ease;">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
                <span>${isExpanded ? 'Ẩn câu trả lời' : `Xem ${replies.length} câu trả lời`}</span>
              </button>

              ${
                isExpanded
                  ? `<div class="nobifashion_reels_nested_replies_box">
                      ${replies.map((r) => this.renderCommentItem(r, depth + 1, currentRootId)).join('')}
                      ${(c.replies_count > replies.length)
                        ? `<button type="button" class="nobifashion_reels_load_more_replies_btn" data-action="load-more-replies" data-comment-id="${c.id}" style="background:none;border:none;color:#0284c7;font-size:10.5px;font-weight:600;padding:4px 0 6px 24px;cursor:pointer;display:flex;align-items:center;gap:4px;">
                             <span>↳ Xem thêm ${Math.min(10, c.replies_count - replies.length)} câu trả lời...</span>
                           </button>`
                        : ''}
                    </div>`
                  : ''
              }
            </div>
          `
              : ''
          }
        </div>
      </div>
    `;
  }

  renderCommentsList(preserveScroll = false) {
    if (!this.commentsListEl || !this.currentReel) return;
    const comments = [...(this.currentReel.comments || [])];

    // Sắp xếp: Ưu tiên bình luận mới của mình lên đầu -> Nhiều tim nhất -> Nhiều trả lời nhất -> Mới nhất
    comments.sort((a, b) => {
      if (a.isMyNewComment && !b.isMyNewComment) return -1;
      if (!a.isMyNewComment && b.isMyNewComment) return 1;

      if (this.sortBy === 'popular') {
        return (b.likes || 0) - (a.likes || 0) ||
               (b.replies_count || 0) - (a.replies_count || 0) ||
               (b.timestamp || 0) - (a.timestamp || 0);
      }
      return (b.timestamp || 0) - (a.timestamp || 0);
    });

    if (comments.length === 0) {
      this.commentsListEl.innerHTML = `
        <div style="text-align:center;padding:32px 16px;color:var(--nobifashion_reels_text_muted);font-size:13px;">
          Chưa có bình luận nào. Hãy là người đầu tiên chia sẻ cảm nhận về thiết kế này!
        </div>
      `;
      return;
    }

    const prevScrollTop = preserveScroll ? this.commentsListEl.scrollTop : null;

    this.commentsListEl.innerHTML = comments
      .map((c) => this.renderCommentItem(c, 0))
      .join('');

    if (preserveScroll && prevScrollTop !== null) {
      this.commentsListEl.scrollTop = prevScrollTop;
    }
    return;
    this.commentsListEl.innerHTML_disabled = comments
      .map((c) => {
        const replies = c.replies || [];
        const isExpanded = this.expandedReplies.has(c.id);
        const visibleReplies = isExpanded ? replies : replies.slice(0, 1);
        const hiddenCount = replies.length - visibleReplies.length;

        return `
          <div class="nobifashion_reels_comment_item" data-comment-id="${c.id}">
            <div class="nobifashion_reels_comment_avatar">${c.initials || 'NB'}</div>
            <div class="nobifashion_reels_comment_body">
              <div class="nobifashion_reels_comment_header">
                <span class="nobifashion_reels_comment_author">${this.escapeHtml(c.author)}</span>
                <span class="nobifashion_reels_comment_time">${c.time}</span>
              </div>
              <p class="nobifashion_reels_comment_text">${this.formatCommentText(c.text)}</p>
              <div class="nobifashion_reels_comment_actions">
                <button
                  type="button"
                  class="nobifashion_reels_comment_action_btn ${c.isLiked ? 'nobifashion_reels_is_liked' : ''}"
                  data-action="like-comment"
                  data-comment-id="${c.id}"
                >
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                  </svg>
                  <span>Thích (${c.likes || 0})</span>
                </button>
                <button
                  type="button"
                  class="nobifashion_reels_comment_action_btn"
                  data-action="reply-comment"
                  data-comment-id="${c.id}"
                  data-author="${this.escapeHtml(c.author)}"
                >
                  Trả lời
                </button>
              </div>

              ${
                visibleReplies.length > 0
                  ? `
                <div class="nobifashion_reels_replies_wrap">
                  ${visibleReplies
                    .map(
                      (r) => `
                    <div class="nobifashion_reels_comment_item">
                      <div class="nobifashion_reels_comment_avatar" style="width:26px;height:26px;font-size:10px;">${r.initials || 'NB'}</div>
                      <div class="nobifashion_reels_comment_body">
                        <div class="nobifashion_reels_comment_header">
                          <span class="nobifashion_reels_comment_author">${this.escapeHtml(r.author)}</span>
                          <span class="nobifashion_reels_comment_time">${r.time}</span>
                        </div>
                        <p class="nobifashion_reels_comment_text">${this.formatCommentText(r.text)}</p>
                        <div class="nobifashion_reels_comment_actions">
                          <button
                            type="button"
                            class="nobifashion_reels_comment_action_btn ${r.isLiked ? 'nobifashion_reels_is_liked' : ''}"
                            data-action="like-comment"
                            data-comment-id="${r.id}"
                          >
                            Thích (${r.likes || 0})
                          </button>
                        </div>
                      </div>
                    </div>
                  `
                    )
                    .join('')}
                </div>
              `
                  : ''
              }

              ${
                replies.length > 1
                  ? `
                <button type="button" class="nobifashion_reels_toggle_replies_btn" data-action="toggle-replies" data-comment-id="${c.id}">
                  ${isExpanded ? 'Ẩn bớt phản hồi' : `Xem thêm ${hiddenCount} phản hồi`}
                </button>
              `
                  : ''
              }
            </div>
          </div>
        `;
      })
      .join('');
  }

  setReplyTarget(commentId, author, rootId = null) {
    this.replyToComment = { id: commentId, author, rootId: rootId || commentId };
    if (this.replyBannerEl && this.replyLabelEl) {
      this.replyLabelEl.textContent = `Đang trả lời @${author}`;
      this.replyBannerEl.classList.add('nobifashion_reels_is_active');
    }
    if (this.commentInputEl) {
      if (rootId && String(commentId) !== String(rootId) && !this.commentInputEl.value.startsWith(`@${author}`)) {
        this.commentInputEl.value = `@${author} `;
      }
      this.commentInputEl.focus();
    }
  }

  clearReplyTarget() {
    this.replyToComment = null;
    if (this.replyBannerEl) {
      this.replyBannerEl.classList.remove('nobifashion_reels_is_active');
    }
  }

  async submitComment() {
    if (!this.currentReel) return;
    const text = this.commentInputEl.value.trim();
    if (!text) return;

    const replyId = this.replyToComment ? this.replyToComment.id : null;
    const rootId = (this.replyToComment && this.replyToComment.rootId) ? this.replyToComment.rootId : replyId;
    const sendBtn = this.commentFormEl ? this.commentFormEl.querySelector('.nobifashion_reels_send_btn') : null;
    if (sendBtn) sendBtn.disabled = true;

    try {
      const result = await this.store.addComment(this.currentReel.id, text, replyId);
      this.commentInputEl.value = '';
      this.clearReplyTarget();
      if (this.emojiPickerEl) this.emojiPickerEl.classList.remove('nobifashion_reels_is_open');

      if (rootId) {
        this.expandedReplies.add(String(rootId));
        this.expandedReplies.add(parseInt(rootId, 10));
      }

      this.updateStats(this.currentReel);
      this.renderCommentsList();
      if (!replyId && this.commentsListEl) {
        this.commentsListEl.scrollTop = 0;
      }

      const railCount = document.querySelector('[data-role="comment-count"]');
      if (railCount) {
        railCount.textContent = formatCount(this.currentReel.commentsCount);
      }
      if (this.showToast) {
        this.showToast('Đã đăng bình luận thành công');
      }
    } catch (err) {
      if (this.showToast) {
        this.showToast(err.message || 'Không thể đăng bình luận. Vui lòng thử lại!');
      } else {
        alert(err.message);
      }
    } finally {
      if (sendBtn) sendBtn.disabled = false;
    }
  }

  escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
}


// --- assets/js/reels-products.js ---
/**
 * NOBI FASHION REELS — TAGGED PRODUCTS & SHOPPING CART DRAWER CONTROLLER
 * Handles tagged fashion products per Reel, size/color variant selection,
 * adding to cart, cart quantity management, and external product link navigation.
 */

class ProductController {
  constructor({ store, productDrawerEl, cartDrawerEl, backdropEl, cartBadgeEl, showToast }) {
    this.store = store;
    this.productDrawerEl = productDrawerEl;
    this.cartDrawerEl = cartDrawerEl;
    this.backdropEl = backdropEl;
    this.cartBadgeEl = cartBadgeEl;
    this.showToast = showToast;

    this.selectedVariants = new Map(); // productId -> { size, color }
    this.currentReel = null;
    this.init();
  }

  init() {
    this.updateCartBadge();

    if (this.productDrawerEl) {
      this.productDrawerEl.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.getAttribute('data-action');

        if (action === 'close-drawer') {
          this.closeAllDrawers();
        } else if (action === 'select-size') {
          const prodId = btn.getAttribute('data-product-id');
          const size = btn.getAttribute('data-size');
          const cur = this.selectedVariants.get(prodId) || {};
          this.selectedVariants.set(prodId, { ...cur, size });
          this.renderProductsForReel(this.currentReel);
        } else if (action === 'select-color') {
          const prodId = btn.getAttribute('data-product-id');
          const color = btn.getAttribute('data-color');
          const cur = this.selectedVariants.get(prodId) || {};
          this.selectedVariants.set(prodId, { ...cur, color });
          this.renderProductsForReel(this.currentReel);
        } else if (action === 'add-to-cart') {
          const prodId = btn.getAttribute('data-product-id');
          this.handleAddToCart(prodId);
        } else if (action === 'switch-to-cart') {
          this.openCartDrawer();
        }
      });
    }

    if (this.cartDrawerEl) {
      this.cartDrawerEl.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.getAttribute('data-action');

        if (action === 'close-drawer') {
          this.closeAllDrawers();
        } else if (action === 'cart-qty-minus') {
          const key = btn.getAttribute('data-key');
          this.store.updateCartQty(key, -1);
          this.renderCartDrawer();
          this.updateCartBadge();
        } else if (action === 'cart-qty-plus') {
          const key = btn.getAttribute('data-key');
          this.store.updateCartQty(key, 1);
          this.renderCartDrawer();
          this.updateCartBadge();
        } else if (action === 'checkout-demo') {
          if (this.store.cart.length === 0) return;
          if (this.showToast) {
            this.showToast('Đã chuyển sang trang thanh toán Nobi Fashion (Chế độ Demo)');
          }
          this.closeAllDrawers();
        }
      });
    }
  }

  openProductsForReel(reel) {
    if (!reel || !this.productDrawerEl) return;
    this.currentReel = reel;
    this.renderProductsForReel(reel);
    if (this.cartDrawerEl) this.cartDrawerEl.classList.remove('nobifashion_reels_is_open');
    this.productDrawerEl.classList.add('nobifashion_reels_is_open');
    if (this.backdropEl) this.backdropEl.classList.add('nobifashion_reels_is_open');
  }

  renderProductsForReel(reel) {
    if (!reel || !this.productDrawerEl) return;
    const products = reel.products || [];

    const bodyHtml = products
      .map((prod) => {
        const defaults = {
          size: (prod.sizes && prod.sizes[0]) || 'Freesize',
          color: (prod.colors && prod.colors[0]) || 'Tiêu chuẩn'
        };
        const chosen = { ...defaults, ...(this.selectedVariants.get(prod.id) || {}) };
        this.selectedVariants.set(prod.id, chosen);

        return `
          <div class="nobifashion_reels_product_detail_card">
            <div class="nobifashion_reels_product_detail_top">
              <img class="nobifashion_reels_product_detail_img" src="${prod.image}" alt="${prod.name}" />
              <div class="nobifashion_reels_product_detail_info">
                <div class="nobifashion_reels_product_detail_name">${prod.name}</div>
                <div class="nobifashion_reels_product_detail_price">
                  ${formatCurrency(prod.price)}
                  ${
                    prod.originalPrice
                      ? `<span style="text-decoration:line-through;color:var(--nobifashion_reels_text_muted);font-weight:400;font-size:12px;margin-left:6px;">${formatCurrency(prod.originalPrice)}</span>`
                      : ''
                  }
                </div>
                <p class="nobifashion_reels_product_detail_desc">${prod.description}</p>
              </div>
            </div>

            <div class="nobifashion_reels_variant_group">
              <span class="nobifashion_reels_variant_label">Màu sắc: ${chosen.color}</span>
              <div class="nobifashion_reels_variant_options">
                ${(prod.colors || [])
                  .map(
                    (c) => `
                  <button
                    type="button"
                    class="nobifashion_reels_variant_chip ${chosen.color === c ? 'nobifashion_reels_is_selected' : ''}"
                    data-action="select-color"
                    data-product-id="${prod.id}"
                    data-color="${c}"
                  >${c}</button>
                `
                  )
                  .join('')}
              </div>
            </div>

            <div class="nobifashion_reels_variant_group">
              <span class="nobifashion_reels_variant_label">Kích cỡ: ${chosen.size}</span>
              <div class="nobifashion_reels_variant_options">
                ${(prod.sizes || [])
                  .map(
                    (s) => `
                  <button
                    type="button"
                    class="nobifashion_reels_variant_chip ${chosen.size === s ? 'nobifashion_reels_is_selected' : ''}"
                    data-action="select-size"
                    data-product-id="${prod.id}"
                    data-size="${s}"
                  >${s}</button>
                `
                  )
                  .join('')}
              </div>
            </div>

            <div class="nobifashion_reels_product_actions_row">
              <button type="button" class="nobifashion_reels_btn_block" data-action="add-to-cart" data-product-id="${prod.id}">
                Thêm vào giỏ hàng
              </button>
              <a
                href="${prod.url}"
                target="_blank"
                rel="noopener noreferrer"
                class="nobifashion_reels_btn_outline"
                title="Mở trang chi tiết sản phẩm Nobi Fashion"
              >
                Chi tiết ↗
              </a>
            </div>
          </div>
        `;
      })
      .join('');

    this.productDrawerEl.innerHTML = `
      <div class="nobifashion_reels_drawer_header">
        <span class="nobifashion_reels_drawer_title">Sản phẩm trong video (${products.length})</span>
        <button type="button" class="nobifashion_reels_icon_btn" data-action="close-drawer" aria-label="Đóng">✕</button>
      </div>
      <div class="nobifashion_reels_drawer_body nobifashion_reels_scrollable">
        ${bodyHtml || '<p>Không có sản phẩm đính kèm.</p>'}
      </div>
      <div class="nobifashion_reels_drawer_footer">
        <button type="button" class="nobifashion_reels_btn_outline" style="width:100%;" data-action="switch-to-cart">
          Xem giỏ hàng hiện tại (${this.store.getCartTotalCount()})
        </button>
      </div>
    `;
  }

  handleAddToCart(productId) {
    if (!this.currentReel) return;
    const prod = (this.currentReel.products || []).find((p) => p.id === productId);
    if (!prod) return;

    const chosen = this.selectedVariants.get(productId) || {
      size: (prod.sizes && prod.sizes[0]) || 'Freesize',
      color: (prod.colors && prod.colors[0]) || 'Tiêu chuẩn'
    };

    this.store.addToCart(prod, chosen.size, chosen.color);
    this.updateCartBadge();
    this.renderProductsForReel(this.currentReel);
    if (this.showToast) {
      this.showToast(`Đã thêm "${prod.name}" (${chosen.size}) vào giỏ hàng`);
    }
  }

  openCartDrawer() {
    if (!this.cartDrawerEl) return;
    this.renderCartDrawer();
    if (this.productDrawerEl) this.productDrawerEl.classList.remove('nobifashion_reels_is_open');
    this.cartDrawerEl.classList.add('nobifashion_reels_is_open');
    if (this.backdropEl) this.backdropEl.classList.add('nobifashion_reels_is_open');
  }

  renderCartDrawer() {
    if (!this.cartDrawerEl) return;
    const items = this.store.cart || [];
    const totalCount = this.store.getCartTotalCount();
    const totalPrice = this.store.getCartTotalPrice();

    const itemsHtml =
      items.length === 0
        ? `
          <div style="text-align:center;padding:48px 16px;color:var(--nobifashion_reels_text_muted);">
            <p style="font-size:14px;font-weight:600;margin-bottom:6px;">Giỏ hàng của bạn đang trống</p>
            <p style="font-size:12px;">Khám phá các thiết kế gắn thẻ trực tiếp trên từng video Nobi Reels.</p>
          </div>
        `
        : items
            .map(
              (item) => `
          <div class="nobifashion_reels_cart_item">
            <img class="nobifashion_reels_cart_item_img" src="${item.image}" alt="${item.name}" />
            <div class="nobifashion_reels_cart_item_info">
              <div>
                <div style="font-size:13px;font-weight:600;">${item.name}</div>
                <div style="font-size:11px;color:var(--nobifashion_reels_text_muted);margin-top:2px;">
                  ${item.color} • Size ${item.size}
                </div>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
                <span style="font-size:13px;font-weight:700;">${formatCurrency(item.price * item.qty)}</span>
                <div class="nobifashion_reels_qty_control">
                  <button type="button" class="nobifashion_reels_qty_btn" data-action="cart-qty-minus" data-key="${item.key}" aria-label="Giảm số lượng">−</button>
                  <span class="nobifashion_reels_qty_val">${item.qty}</span>
                  <button type="button" class="nobifashion_reels_qty_btn" data-action="cart-qty-plus" data-key="${item.key}" aria-label="Tăng số lượng">+</button>
                </div>
              </div>
            </div>
          </div>
        `
            )
            .join('');

    this.cartDrawerEl.innerHTML = `
      <div class="nobifashion_reels_drawer_header">
        <span class="nobifashion_reels_drawer_title">Giỏ hàng Nobi (${totalCount})</span>
        <button type="button" class="nobifashion_reels_icon_btn" data-action="close-drawer" aria-label="Đóng">✕</button>
      </div>
      <div class="nobifashion_reels_drawer_body nobifashion_reels_scrollable">
        ${itemsHtml}
      </div>
      <div class="nobifashion_reels_drawer_footer">
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:14px;font-weight:700;">
          <span>Tạm tính</span>
          <span>${formatCurrency(totalPrice)}</span>
        </div>
        <button
          type="button"
          class="nobifashion_reels_btn_block"
          data-action="checkout-demo"
          ${items.length === 0 ? 'disabled' : ''}
        >
          Tiến hành đặt hàng
        </button>
      </div>
    `;
  }

  updateCartBadge() {
    if (!this.cartBadgeEl) return;
    const count = this.store.getCartTotalCount();
    this.cartBadgeEl.textContent = String(count);
    this.cartBadgeEl.style.display = count > 0 ? 'inline-flex' : 'none';
  }

  closeAllDrawers() {
    if (this.productDrawerEl) this.productDrawerEl.classList.remove('nobifashion_reels_is_open');
    if (this.cartDrawerEl) this.cartDrawerEl.classList.remove('nobifashion_reels_is_open');
    if (this.backdropEl) this.backdropEl.classList.remove('nobifashion_reels_is_open');
  }

  isAnyDrawerOpen() {
    return (
      (this.productDrawerEl && this.productDrawerEl.classList.contains('nobifashion_reels_is_open')) ||
      (this.cartDrawerEl && this.cartDrawerEl.classList.contains('nobifashion_reels_is_open'))
    );
  }
}


// --- assets/js/reels-interactions.js ---
/**
 * NOBI FASHION REELS — SOCIAL INTERACTIONS CONTROLLER
 * Coordinates Like/Unlike, Double-tap/Double-click Heart Burst without colliding
 * with single-tap Play/Pause, Save/Bookmark, Follow, Share modal, Copy Link,
 * Report simulation, and Hide video from feed.
 */

class InteractionController {
  constructor({
    store,
    renderer,
    player,
    commentsController,
    productController,
    navigationController,
    modalEl,
    backdropEl,
    showToast
  }) {
    this.store = store;
    this.renderer = renderer;
    this.player = player;
    this.commentsController = commentsController;
    this.productController = productController;
    this.navigationController = navigationController;
    this.modalEl = modalEl;
    this.backdropEl = backdropEl;
    this.showToast = showToast;

    this.lastTapTime = 0;
    this.singleTapTimer = null;
  }

  /**
   * Handles single tap (play/pause) vs double tap/click (like + heart burst) on the 9:16 video surface
   */
  handleVideoSurfaceTap(event, reel) {
    if (!reel) return;
    const now = performance.now();
    const gap = now - this.lastTapTime;

    if (gap > 0 && gap < 260) {
      // Double tap / double click detected!
      if (this.singleTapTimer) {
        clearTimeout(this.singleTapTimer);
        this.singleTapTimer = null;
      }
      this.lastTapTime = 0;
      this.triggerDoubleTapLike(reel);
    } else {
      this.lastTapTime = now;
      this.singleTapTimer = setTimeout(() => {
        this.player.togglePlayPause();
        this.singleTapTimer = null;
      }, 240);
    }
  }

  triggerDoubleTapLike(reel) {
    const slideEl = this.renderer.getActiveSlideElement(reel.id);
    if (slideEl) {
      const heart = slideEl.querySelector('.nobifashion_reels_doubletap_heart');
      if (heart) {
        heart.classList.remove('nobifashion_reels_animate');
        void heart.offsetWidth; // Force reflow to restart animation cleanly
        heart.classList.add('nobifashion_reels_animate');
      }
    }
    if (!reel.isLiked) {
      this.handleToggleLike(reel.id, true);
    }
  }

  async handleToggleLike(reelId, forceLike = null) {
    const updated = await this.store.toggleLike(reelId, forceLike);
    if (!updated) return;
    this.renderer.renderActionRail(updated);
    this.commentsController.updateStats(updated);
    if (this.store.activeFilter === 'liked' && !updated.isLiked) {
      this.navigationController.refreshList(false);
    }
  }

  async handleToggleSave(reelId) {
    const updated = await this.store.toggleSave(reelId);
    if (!updated) return;
    this.renderer.renderActionRail(updated);
    if (this.showToast) {
      this.showToast(updated.isSaved ? 'Đã lưu vào bộ sưu tập của bạn' : 'Đã bỏ lưu video');
    }
    if (this.store.activeFilter === 'saved' && !updated.isSaved) {
      this.navigationController.refreshList(false);
    }
  }

  handleToggleFollow(authorId) {
    const isNowFollowing = this.store.toggleFollow(authorId);
    const activeReel = this.navigationController.getActiveReel();
    if (activeReel) {
      const slideEl = this.renderer.getActiveSlideElement(activeReel.id);
      this.renderer.updateSlideOverlayData(slideEl, activeReel);
      this.commentsController.renderCreatorHeader(activeReel);
    }
    const authorName = activeReel?.author?.name || 'nhà sáng tạo';
    if (this.showToast) {
      this.showToast(isNowFollowing ? `Đã theo dõi ${authorName}` : `Đã hủy theo dõi ${authorName}`);
    }
  }

  openShareModal(reel) {
    if (!reel || !this.modalEl) return;
    const shareUrl = `https://nobifashion.vn/reels/${reel.id}`;

    this.modalEl.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:15px;font-weight:700;">Chia sẻ Nobi Reel</h3>
        <button type="button" class="nobifashion_reels_icon_btn" data-action="close-modal" aria-label="Đóng">✕</button>
      </div>
      <p style="font-size:12px;color:var(--nobifashion_reels_text_secondary);margin-top:4px;">
        ${reel.author.name} — ${reel.hashtags.join(' ')}
      </p>
      <div class="nobifashion_reels_modal_list">
        <button type="button" class="nobifashion_reels_modal_option" data-action="copy-reel-link" data-url="${shareUrl}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
          </svg>
          <span>Sao chép liên kết video</span>
        </button>
        <button type="button" class="nobifashion_reels_modal_option" data-action="share-channel" data-channel="Tin nhắn Nobi Chat">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <span>Gửi qua Tin nhắn Nobi</span>
        </button>
        <button type="button" class="nobifashion_reels_modal_option" data-action="share-channel" data-channel="Facebook / Instagram Story">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
          </svg>
          <span>Chia sẻ lên Story</span>
        </button>
      </div>
    `;

    this.modalEl.classList.add('nobifashion_reels_is_open');
    if (this.backdropEl) this.backdropEl.classList.add('nobifashion_reels_is_open');
  }

  openMoreOptionsModal(reel) {
    if (!reel || !this.modalEl) return;
    const shareUrl = `https://nobifashion.vn/reels/${reel.id}`;

    this.modalEl.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:15px;font-weight:700;">Tùy chọn video</h3>
        <button type="button" class="nobifashion_reels_icon_btn" data-action="close-modal" aria-label="Đóng">✕</button>
      </div>
      <div class="nobifashion_reels_modal_list">
        <button type="button" class="nobifashion_reels_modal_option" data-action="copy-reel-link" data-url="${shareUrl}">
          <span>Sao chép đường dẫn (${reel.id})</span>
        </button>
        <button type="button" class="nobifashion_reels_modal_option" data-action="toggle-fit-modal">
          <span>Chuyển đổi chế độ khung hình (Cover / Contain)</span>
        </button>
        <button type="button" class="nobifashion_reels_modal_option" data-action="hide-reel" data-reel-id="${reel.id}">
          <span>Ẩn video này khỏi danh sách hiện tại</span>
        </button>
        <button type="button" class="nobifashion_reels_modal_option nobifashion_reels_is_danger" data-action="report-reel" data-reel-id="${reel.id}">
          <span>Báo cáo nội dung video</span>
        </button>
      </div>
    `;

    this.modalEl.classList.add('nobifashion_reels_is_open');
    if (this.backdropEl) this.backdropEl.classList.add('nobifashion_reels_is_open');
  }

  closeModal() {
    if (this.modalEl) this.modalEl.classList.remove('nobifashion_reels_is_open');
    if (!this.productController.isAnyDrawerOpen() && this.backdropEl) {
      this.backdropEl.classList.remove('nobifashion_reels_is_open');
    }
  }

  copyTextToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard
        .writeText(text)
        .then(() => {
          if (this.showToast) this.showToast('Đã sao chép liên kết video vào bộ nhớ tạm');
        })
        .catch(() => {
          this.fallbackCopy(text);
        });
    } else {
      this.fallbackCopy(text);
    }
  }

  fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      if (this.showToast) this.showToast('Đã sao chép liên kết video');
    } catch (_) {}
    ta.remove();
  }
}


// --- assets/js/reels-ui.js ---
/**
 * NOBI FASHION REELS — UI, THEME, SEARCH, FILTERS & POPOVER CONTROLLER
 * Manages Light/Dark mode toggle, live search suggestions (by video, creator,
 * or hashtag), category filter pills, notifications popover, profile menu,
 * responsive sidebar toggle, and toast alerts.
 */

class UIController {
  constructor({
    appEl,
    workspaceEl,
    store,
    navigationController,
    player,
    productController,
    interactionController
  }) {
    this.appEl = appEl;
    this.workspaceEl = workspaceEl;
    this.store = store;
    this.navigationController = navigationController;
    this.player = player;
    this.productController = productController;
    this.interactionController = interactionController;

    this.toastEl = document.getElementById('nobifashion_reels_toast');
    this.searchInput = document.getElementById('nobifashion_reels_search_input');
    this.searchClearBtn = document.getElementById('nobifashion_reels_search_clear');
    this.searchDropdown = document.getElementById('nobifashion_reels_search_dropdown');
    this.popoverEl = document.getElementById('nobifashion_reels_popover');
    this.toastTimer = null;

    this.applyTheme(this.store.theme);
    this.bindUIEvents();
  }

  applyTheme(theme) {
    this.store.theme = theme;
    if (this.appEl) {
      this.appEl.setAttribute('data-theme', theme);
    }
    const themeBtn = document.getElementById('nobifashion_reels_theme_btn');
    if (themeBtn) {
      themeBtn.innerHTML =
        theme === 'dark'
          ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`
          : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
    }
    this.store.saveState();
  }

  toggleTheme() {
    const next = this.store.theme === 'light' ? 'dark' : 'light';
    this.applyTheme(next);
    this.showToast(next === 'dark' ? 'Đã bật giao diện Tối (Dark Mode)' : 'Đã bật giao diện Sáng (Light Mode)');
  }

  showToast(message) {
    if (!this.toastEl) return;
    this.toastEl.textContent = message;
    this.toastEl.classList.add('nobifashion_reels_is_visible');
    clearTimeout(this.toastTimer);
    this.toastTimer = setTimeout(() => {
      this.toastEl.classList.remove('nobifashion_reels_is_visible');
    }, 2400);
  }

  setFilter(filterName) {
    this.store.activeFilter = filterName;
    // Sync filter pills and header tabs
    document.querySelectorAll('[data-filter]').forEach((el) => {
      el.classList.toggle('nobifashion_reels_is_active', el.getAttribute('data-filter') === filterName);
    });
    this.navigationController.refreshList(true);
  }

  setSearchQuery(query) {
    this.store.searchQuery = query;
    if (this.searchInput && this.searchInput.value !== query) {
      this.searchInput.value = query;
    }
    if (this.searchClearBtn) {
      this.searchClearBtn.classList.toggle('nobifashion_reels_is_visible', Boolean(query));
    }
    this.navigationController.refreshList(true);
  }

  async performUltraFastSearch(query) {
    if (!this.searchDropdown) return;
    const q = (query || '').trim();
    if (!q) {
      this.renderSearchSuggestions('');
      return;
    }

    try {
      const res = await fetch(`/api/v1/reels?search=${encodeURIComponent(q)}&limit=20`);
      if (res.ok) {
        const json = await res.json();
        const results = json.data || [];
        this.lastSearchResults = new Map();

        // Đồng bộ video khớp tìm kiếm vào store để hiển thị đầy đủ ngay cả video cũ trong DB
        const existingIds = new Set(this.store.reels.map((r) => Number(r.id)));
        results.forEach((r) => {
          this.lastSearchResults.set(Number(r.id), r);
          if (!existingIds.has(Number(r.id))) {
            this.store.reels.push(r);
          }
        });

        this.renderRemoteSearchResults(q, results);
        this.navigationController.refreshList(false);
        return;
      }
    } catch (err) {
      // Fallback local search if offline
    }
    this.renderSearchSuggestions(q);
  }

  renderRemoteSearchResults(query, results) {
    if (!this.searchDropdown) return;
    this.searchDropdown.innerHTML = `
      <div class="nobifashion_reels_search_section_label">Kết quả tìm kiếm cho "${query}" (${results.length})</div>
      ${
        results.length === 0
          ? `<div style="padding:12px;font-size:12.5px;color:var(--nobifashion_reels_text_muted);text-align:center;">Không tìm thấy video nào phù hợp với "${query}"</div>`
          : results
              .map(
                (r) => `
            <button type="button" class="nobifashion_reels_search_item" data-action="select-search-reel" data-reel-id="${r.id}">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">${r.title || r.caption}</span>
              <span class="nobifashion_reels_search_item_tag">${(r.hashtags && r.hashtags[0]) || '#NobiReels'}</span>
            </button>
          `
              )
              .join('')
      }
    `;
  }

  renderSearchSuggestions(query) {
    if (!this.searchDropdown) return;
    const q = (query || '').trim().toLowerCase();

    const matchingReels = this.store.reels.filter(
      (r) =>
        !q ||
        r.caption.toLowerCase().includes(q) ||
        r.author.name.toLowerCase().includes(q) ||
        r.hashtags.some((h) => h.toLowerCase().includes(q))
    );

    const popularHashtags = ['#NobiFashion', '#AW26', '#SilkDrape', '#SavileRow', '#TokyoStreet', '#BridalCouture'];

    this.searchDropdown.innerHTML = `
      <div class="nobifashion_reels_search_section_label">Gợi ý thiết kế & người sáng tạo</div>
      ${
        matchingReels.length === 0
          ? `<div style="padding:8px 10px;font-size:12px;color:var(--nobifashion_reels_text_muted);">Không tìm thấy kết quả khớp "${query}"</div>`
          : matchingReels
              .slice(0, 5)
              .map(
                (r) => `
            <button type="button" class="nobifashion_reels_search_item" data-action="select-search-reel" data-reel-id="${r.id}">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r.author.name} — ${r.caption.slice(0, 42)}...</span>
              <span class="nobifashion_reels_search_item_tag">${r.hashtags[0]}</span>
            </button>
          `
              )
              .join('')
      }
      <div class="nobifashion_reels_search_section_label" style="margin-top:6px;">Hashtag xu hướng</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;padding:4px 8px 8px;">
        ${popularHashtags
          .map(
            (tag) => `
          <button type="button" class="nobifashion_reels_sidebar_tag" data-action="select-search-tag" data-hashtag="${tag}">${tag}</button>
        `
          )
          .join('')}
      </div>
    `;
  }

  openNotificationsPopover() {
    if (!this.popoverEl) return;
    const isSameOpen =
      this.popoverEl.classList.contains('nobifashion_reels_is_open') &&
      this.popoverEl.getAttribute('data-mode') === 'notifications';

    if (isSameOpen) {
      this.popoverEl.classList.remove('nobifashion_reels_is_open');
      return;
    }

    this.popoverEl.setAttribute('data-mode', 'notifications');
    this.popoverEl.innerHTML = `
      <div class="nobifashion_reels_popover_header">
        <span>Thông báo Nobi Reels</span>
        <span style="font-size:11px;color:var(--nobifashion_reels_text_muted);">Mới nhất</span>
      </div>
      ${INITIAL_NOTIFICATIONS.map(
        (n) => `
        <div class="nobifashion_reels_notif_item" data-action="jump-notif-reel" data-reel-id="${n.reelId}" role="button" tabindex="0">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--nobifashion_reels_accent);margin-top:5px;flex-shrink:0;"></div>
          <div>
            <div style="font-weight:600;color:var(--nobifashion_reels_text_primary);">${n.title}</div>
            <div style="color:var(--nobifashion_reels_text_secondary);margin-top:2px;">${n.desc}</div>
            <div style="font-size:10px;color:var(--nobifashion_reels_text_muted);margin-top:3px;">${n.time}</div>
          </div>
        </div>
      `
      ).join('')}
    `;
    this.popoverEl.classList.add('nobifashion_reels_is_open');
  }

  openProfilePopover() {
    if (!this.popoverEl) return;
    const isSameOpen =
      this.popoverEl.classList.contains('nobifashion_reels_is_open') &&
      this.popoverEl.getAttribute('data-mode') === 'profile';

    if (isSameOpen) {
      this.popoverEl.classList.remove('nobifashion_reels_is_open');
      return;
    }

    const likedCount = this.store.reels.filter((r) => r.isLiked).length;
    const savedCount = this.store.reels.filter((r) => r.isSaved).length;

    this.popoverEl.setAttribute('data-mode', 'profile');
    this.popoverEl.innerHTML = `
      <div class="nobifashion_reels_popover_header">
        <div>
          <div style="font-size:13px;font-weight:700;">Nobi VIP Member</div>
          <div style="font-size:11px;color:var(--nobifashion_reels_text_muted);font-weight:400;">member@nobifashion.vn</div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:4px;">
        <button type="button" class="nobifashion_reels_search_item" data-action="profile-filter" data-filter="liked">
          <span>Video đã thích</span>
          <span class="nobifashion_reels_search_item_tag">${likedCount}</span>
        </button>
        <button type="button" class="nobifashion_reels_search_item" data-action="profile-filter" data-filter="saved">
          <span>Bộ sưu tập đã lưu</span>
          <span class="nobifashion_reels_search_item_tag">${savedCount}</span>
        </button>
        <button type="button" class="nobifashion_reels_search_item" data-action="profile-open-cart">
          <span>Giỏ hàng Nobi Fashion</span>
          <span class="nobifashion_reels_search_item_tag">${this.store.getCartTotalCount()}</span>
        </button>
        <button type="button" class="nobifashion_reels_search_item" data-action="profile-reset-data">
          <span>Khôi phục tất cả bộ lọc</span>
        </button>
      </div>
    `;
    this.popoverEl.classList.add('nobifashion_reels_is_open');
  }

  toggleCommentsSidebar() {
    if (!this.workspaceEl) return;
    if (window.innerWidth <= 1023) {
      this.workspaceEl.classList.toggle('nobifashion_reels_mobile_comments_open');
    } else {
      this.workspaceEl.classList.toggle('nobifashion_reels_sidebar_collapsed');
    }
  }

  bindUIEvents() {
    if (this.searchInput) {
      this.searchInput.addEventListener('focus', () => {
        this.renderSearchSuggestions(this.searchInput.value);
        this.searchDropdown?.classList.add('nobifashion_reels_is_open');
      });

      this.searchDebounceTimer = null;
      this.searchInput.addEventListener("input", (e) => {
        const val = e.target.value.trim();
        clearTimeout(this.searchDebounceTimer);
        this.searchDebounceTimer = setTimeout(async () => {
          this.setSearchQuery(val);
          await this.performUltraFastSearch(val);
          this.searchDropdown?.classList.add("nobifashion_reels_is_open");
        }, 220);
      });
    }

    if (this.searchClearBtn) {
      this.searchClearBtn.addEventListener('click', () => {
        this.setSearchQuery('');
        this.searchDropdown?.classList.remove('nobifashion_reels_is_open');
      });
    }
  }
}


// --- assets/js/main.js ---
/**
 * NOBI FASHION REELS — APPLICATION BOOTSTRAP (main.js)
 * Wires together Data Store, Renderer, Video Playback Controller,
 * Central Navigation Controller, Wheel/Touch Gesture State Machines,
 * Comments Sidebar, Tagged Products & Cart Drawer, and Global Keyboard/Click Delegation.
 */









class NobiFashionReelsApp {
  constructor() {
    this.appEl = document.getElementById('nobifashion_reels_app');
    this.workspaceEl = document.getElementById('nobifashion_reels_workspace');
    this.stageEl = document.getElementById('nobifashion_reels_stage');
    this.trackEl = document.getElementById('nobifashion_reels_track');
    this.actionRailEl = document.getElementById('nobifashion_reels_action_rail');
    this.emptyStateEl = document.getElementById('nobifashion_reels_empty_state');
    this.sidebarEl = document.getElementById('nobifashion_reels_sidebar');
    this.productDrawerEl = document.getElementById('nobifashion_reels_product_drawer');
    this.cartDrawerEl = document.getElementById('nobifashion_reels_cart_drawer');
    this.modalEl = document.getElementById('nobifashion_reels_modal');
    this.backdropEl = document.getElementById('nobifashion_reels_backdrop');
    this.cartBadgeEl = document.getElementById('nobifashion_reels_cart_badge');

    this.store = new ReelsDataStore();
    this.initControllers();
    this.bindGlobalDelegation();
    this.bindKeyboardNavigation();

    // Initial render
    this.navigationController.refreshList(true);

    // Expose controller instance for automated verification & external integration
    window.__NOBI_REELS_APP__ = this;
  }

  initControllers() {
    this.renderer = new ReelsRenderer({
      trackEl: this.trackEl,
      actionRailEl: this.actionRailEl,
      emptyStateEl: this.emptyStateEl,
      store: this.store
    });

    this.player = new VideoPlaybackController({
      store: this.store,
      onStateChange: (state) => this.syncPlayerTopbarUI(state),
      showToast: (msg) => this.uiController?.showToast(msg),
      onEndedNext: () => {
        // Không tự cuộn nếu chuột đang ở ô comment hoặc thanh sidebar đang bật
        if (!this.canAutoScrollNext()) {
          return false;
        }
        return this.navigationController.step(1);
      }
    });

    this.commentsController = new CommentsController({
      store: this.store,
      sidebarEl: this.sidebarEl,
      onHashtagClick: (tag) => {
        this.uiController.setSearchQuery(tag);
        this.uiController.showToast(`Đang lọc theo hashtag ${tag}`);
      },
      onOpenProducts: (reelId) => {
        const reel = this.store.getReelById(reelId);
        if (reel) this.productController.openProductsForReel(reel);
      },
      onFollowToggle: (authorId) => {
        this.interactionController.handleToggleFollow(authorId);
      },
      showToast: (msg) => this.uiController?.showToast(msg)
    });

    this.productController = new ProductController({
      store: this.store,
      productDrawerEl: this.productDrawerEl,
      cartDrawerEl: this.cartDrawerEl,
      backdropEl: this.backdropEl,
      cartBadgeEl: this.cartBadgeEl,
      showToast: (msg) => this.uiController?.showToast(msg)
    });

    this.navigationController = new ReelsNavigationController({
      store: this.store,
      renderer: this.renderer,
      player: this.player,
      onReelChanged: (activeReel) => {
        this.commentsController.renderForReel(activeReel);
      }
    });

    this.interactionController = new InteractionController({
      store: this.store,
      renderer: this.renderer,
      player: this.player,
      commentsController: this.commentsController,
      productController: this.productController,
      navigationController: this.navigationController,
      modalEl: this.modalEl,
      backdropEl: this.backdropEl,
      showToast: (msg) => this.uiController?.showToast(msg)
    });

    this.uiController = new UIController({
      appEl: this.appEl,
      workspaceEl: this.workspaceEl,
      store: this.store,
      navigationController: this.navigationController,
      player: this.player,
      productController: this.productController,
      interactionController: this.interactionController
    });

    // Predicate that prevents wheel/touch navigation when user interacts inside comments, drawers, or modals
    const shouldIgnoreGesture = (event) => {
      const target = event.target;
      if (!target || typeof target.closest !== 'function') return false;
      if (
        target.closest('#nobifashion_reels_sidebar') ||
        target.closest('.nobifashion_reels_drawer') ||
        target.closest('.nobifashion_reels_modal') ||
        target.closest('.nobifashion_reels_search_dropdown') ||
        target.closest('.nobifashion_reels_popover') ||
        target.closest('.nobifashion_reels_progress_container')
      ) {
        return true;
      }
      if (
        this.productController.isAnyDrawerOpen() ||
        this.modalEl?.classList.contains('nobifashion_reels_is_open')
      ) {
        return true;
      }
      return false;
    };

    this.wheelController = new WheelGestureController({
      stageElement: this.stageEl,
      onNavigate: (dir) => this.navigationController.step(dir),
      shouldIgnoreEvent: shouldIgnoreGesture
    });

    this.navigationController.setWheelController(this.wheelController);

    this.touchController = new TouchGestureController({
      stageElement: this.stageEl,
      onNavigate: (dir) => this.navigationController.step(dir),
      shouldIgnoreEvent: shouldIgnoreGesture
    });
  }

  syncPlayerTopbarUI(state) {
    const activeReel = this.navigationController.getActiveReel();
    if (!activeReel) return;
    const slideEl = this.renderer.getActiveSlideElement(activeReel.id);
    if (!slideEl) return;

    const muteIconSlot = slideEl.querySelector('.nobifashion_reels_mute_icon_slot');
    const muteLabelSlot = slideEl.querySelector('.nobifashion_reels_mute_label_slot');
    const speedLabelSlot = slideEl.querySelector('.nobifashion_reels_speed_label_slot');
    const fitLabelSlot = slideEl.querySelector('.nobifashion_reels_fit_label_slot');
    const videoMuteBtn = slideEl.querySelector('.nobifashion_reels_video_mute_btn');

    if (videoMuteBtn) {
      videoMuteBtn.innerHTML = this.renderer.getMuteIconSvg(state.muted);
      videoMuteBtn.classList.toggle('nobifashion_reels_is_muted', Boolean(state.muted));
      videoMuteBtn.setAttribute('aria-label', state.muted ? 'Bật âm thanh' : 'Tắt âm thanh');
      videoMuteBtn.setAttribute('title', state.muted ? 'Bật âm thanh' : 'Tắt âm thanh');
    }

    if (muteIconSlot) {
      muteIconSlot.innerHTML = state.muted
        ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>`
        : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>`;
    }
    if (muteLabelSlot) {
      muteLabelSlot.textContent = state.muted ? 'Bật tiếng' : 'Có tiếng';
    }
    if (speedLabelSlot) {
      speedLabelSlot.textContent = `${state.playbackRate}x`;
    }
    const speedBtnText = slideEl.querySelector('.nobifashion_reels_speed_btn_text');
    if (speedBtnText) {
      const spd = state.playbackRate || 1;
      speedBtnText.textContent = `${spd === 1 ? '1.0' : spd}x`;
    }
    const speedOpts = slideEl.querySelectorAll('.nobifashion_reels_speed_opt');
    speedOpts.forEach((opt) => {
      const val = parseFloat(opt.getAttribute('data-speed'));
      opt.classList.toggle('nobifashion_reels_is_active', Math.abs(val - (state.playbackRate || 1)) < 0.01);
    });
    if (fitLabelSlot) {
      fitLabelSlot.textContent = state.fitMode === 'contain' ? 'Nguyên bản' : '9:16 Đầy';
    }
  }

  bindGlobalDelegation() {
    this.appEl.addEventListener('click', async (e) => {
      // Close popovers/dropdowns if clicking outside
      if (!e.target.closest('.nobifashion_reels_search_box')) {
        this.uiController.searchDropdown?.classList.remove('nobifashion_reels_is_open');
      }
      if (
        !e.target.closest('#nobifashion_reels_popover') &&
        !e.target.closest('[data-action="open-notifications"]') &&
        !e.target.closest('[data-action="open-profile"]')
      ) {
        this.uiController.popoverEl?.classList.remove('nobifashion_reels_is_open');
      }
      if (!e.target.closest('.nobifashion_reels_speed_wrapper')) {
        document.querySelectorAll('.nobifashion_reels_speed_wrapper.nobifashion_reels_is_open').forEach((el) => {
          el.classList.remove('nobifashion_reels_is_open');
        });
      }

      // Check for filter buttons
      const filterBtn = e.target.closest('[data-filter]');
      if (filterBtn && !filterBtn.hasAttribute('data-action')) {
        const filterName = filterBtn.getAttribute('data-filter');
        this.uiController.setFilter(filterName);
        return;
      }

      const actionEl = e.target.closest('[data-action]');
      if (!actionEl) return;

      const action = actionEl.getAttribute('data-action');
      const activeReel = this.navigationController.getActiveReel();

      switch (action) {
        case 'reset-home':
          this.store.resetFilters();
          this.uiController.setSearchQuery('');
          this.uiController.setFilter('all');
          break;

        case 'toggle-theme':
          this.uiController.toggleTheme();
          break;

        case 'open-notifications':
          this.uiController.openNotificationsPopover();
          break;

        case 'open-profile':
          this.uiController.openProfilePopover();
          break;

        case 'open-cart':
        case 'profile-open-cart':
          this.uiController.popoverEl?.classList.remove('nobifashion_reels_is_open');
          this.productController.openCartDrawer();
          break;

        case 'profile-filter': {
          const f = actionEl.getAttribute('data-filter') || 'all';
          this.uiController.popoverEl?.classList.remove('nobifashion_reels_is_open');
          this.uiController.setFilter(f);
          break;
        }

        case 'profile-reset-data':
          this.uiController.popoverEl?.classList.remove('nobifashion_reels_is_open');
          this.store.resetFilters();
          this.uiController.setSearchQuery('');
          this.uiController.setFilter('all');
          this.uiController.showToast('Đã hiển thị toàn bộ danh sách video');
          break;

        case 'jump-notif-reel':
        case 'select-search-reel': {
          const reelId = actionEl.getAttribute('data-reel-id');
          const fallbackReel = this.uiController.lastSearchResults?.get(Number(reelId)) || null;
          this.store.resetFilters();
          this.uiController.setFilter('all');
          this.uiController.searchDropdown?.classList.remove('nobifashion_reels_is_open');
          this.uiController.popoverEl?.classList.remove('nobifashion_reels_is_open');
          if (reelId) await this.navigationController.jumpToReelId(reelId, fallbackReel);
          break;
        }

        case 'select-search-tag':
        case 'filter-hashtag': {
          const tag = actionEl.getAttribute('data-hashtag') || '';
          this.uiController.searchDropdown?.classList.remove('nobifashion_reels_is_open');
          this.uiController.setSearchQuery(tag);
          this.uiController.showToast(`Đang lọc theo ${tag}`);
          break;
        }

        case 'nav-prev':
          this.navigationController.step(-1);
          break;

        case 'nav-next':
          this.navigationController.step(1);
          break;

        case 'video-surface':
          this.interactionController.handleVideoSurfaceTap(e, activeReel);
          break;

        case 'toggle-mute':
          this.player.toggleMute();
          break;

        case 'toggle-speed-menu': {
          const wrapper = actionEl.closest('.nobifashion_reels_speed_wrapper');
          if (wrapper) {
            const isOpen = wrapper.classList.contains('nobifashion_reels_is_open');
            document.querySelectorAll('.nobifashion_reels_speed_wrapper.nobifashion_reels_is_open').forEach((el) => {
              el.classList.remove('nobifashion_reels_is_open');
            });
            if (!isOpen) {
              wrapper.classList.add('nobifashion_reels_is_open');
            }
          }
          break;
        }

        case 'select-speed': {
          const speedVal = actionEl.getAttribute('data-speed');
          this.player.setPlaybackSpeed(speedVal);
          const wrapper = actionEl.closest('.nobifashion_reels_speed_wrapper');
          if (wrapper) {
            wrapper.classList.remove('nobifashion_reels_is_open');
          }
          break;
        }

        case 'cycle-speed':
          this.player.cyclePlaybackSpeed();
          break;

        case 'toggle-fit':
          this.player.toggleObjectFit();
          break;

        case 'toggle-fit-modal':
          this.player.toggleObjectFit();
          this.interactionController.closeModal();
          break;

        case 'toggle-fullscreen':
          this.player.toggleFullscreen(document.getElementById('nobifashion_reels_viewport'));
          break;

        case 'retry-video':
          this.player.retryCurrentVideo();
          break;

        case 'toggle-caption': {
          const wrap = actionEl.closest('.nobifashion_reels_overlay_caption_wrap');
          if (wrap) {
            const p = wrap.querySelector('.nobifashion_reels_overlay_caption');
            const btn = wrap.querySelector('.nobifashion_reels_caption_toggle_btn');
            if (p) {
              const currentlyClamped = p.classList.contains('nobifashion_reels_clamp_2');
              if (currentlyClamped) {
                p.classList.remove('nobifashion_reels_clamp_2');
                if (btn) btn.textContent = 'Thu gọn';
              } else {
                p.classList.add('nobifashion_reels_clamp_2');
                if (btn) btn.textContent = '... thêm';
              }
            }
          }
          break;
        }

        case 'toggle-sidebar-caption': {
          const wrap = actionEl.closest('.nobifashion_reels_sidebar_desc_wrap');
          if (wrap) {
            const p = wrap.querySelector('.nobifashion_reels_sidebar_desc');
            const btn = wrap.querySelector('.nobifashion_reels_sidebar_toggle_btn, .nobifashion_reels_caption_toggle_btn');
            if (p) {
              const currentlyClamped = p.classList.contains('nobifashion_reels_clamp_2');
              if (currentlyClamped) {
                p.classList.remove('nobifashion_reels_clamp_2');
                if (btn) btn.textContent = 'Thu gọn';
              } else {
                p.classList.add('nobifashion_reels_clamp_2');
                if (btn) btn.textContent = '... thêm';
              }
            }
          }
          break;
        }

        case 'toggle-like':
          if (activeReel) this.interactionController.handleToggleLike(activeReel.id);
          break;

        case 'toggle-save':
          if (activeReel) this.interactionController.handleToggleSave(activeReel.id);
          break;

        case 'toggle-follow': {
          const authorId = actionEl.getAttribute('data-author-id') || activeReel?.author.id;
          if (authorId) this.interactionController.handleToggleFollow(authorId);
          break;
        }

        case 'toggle-comments-panel':
          this.uiController.toggleCommentsSidebar();
          break;

        case 'open-products':
          if (activeReel) this.productController.openProductsForReel(activeReel);
          break;

        case 'open-share':
          if (activeReel) this.interactionController.openShareModal(activeReel);
          break;

        case 'open-more-menu':
          if (activeReel) this.interactionController.openMoreOptionsModal(activeReel);
          break;

        case 'close-modal':
          this.interactionController.closeModal();
          break;

        case 'copy-reel-link': {
          const url = actionEl.getAttribute('data-url') || window.location.href;
          this.interactionController.copyTextToClipboard(url);
          this.interactionController.closeModal();
          if (activeReel) {
            this.store.recordShare(activeReel.id).then((updated) => {
              const shareSpan = document.querySelector('[data-role="share-count"]');
              if (shareSpan && updated) shareSpan.textContent = formatCount(updated.sharesCount);
            });
          }
          break;
        }

        case 'share-channel': {
          const channel = actionEl.getAttribute('data-channel') || 'Mạng xã hội';
          this.interactionController.closeModal();
          this.uiController.showToast(`Đã mở chia sẻ tới ${channel}`);
          if (activeReel) {
            this.store.recordShare(activeReel.id).then((updated) => {
              const shareSpan = document.querySelector('[data-role="share-count"]');
              if (shareSpan && updated) shareSpan.textContent = formatCount(updated.sharesCount);
            });
          }
          break;
        }

        case 'hide-reel': {
          const rId = actionEl.getAttribute('data-reel-id') || activeReel?.id;
          if (rId) {
            this.store.hideReel(rId);
            this.interactionController.closeModal();
            this.navigationController.refreshList(false);
            this.uiController.showToast('Đã ẩn video khỏi danh sách hiện tại');
          }
          break;
        }

        case 'report-reel':
          this.interactionController.closeModal();
          this.uiController.showToast('Cảm ơn bạn. Đội ngũ Nobi Fashion đã ghi nhận báo cáo.');
          break;

        case 'scrub-progress': {
          // Fallback cho click nếu chưa qua pointerdown
          const rect = actionEl.getBoundingClientRect();
          if (rect.width > 0) {
            const ratio = (e.clientX - rect.left) / rect.width;
            this.player.seekToRatio(ratio, true);
          }
          break;
        }

        default:
          break;
      }
    });

    // Tua video tức thì trong 0ms khi vừa chạm / nhấn chuột (PointerDown) kèm hỗ trợ kéo mượt mà
    this.appEl.addEventListener('pointerdown', (e) => {
      const progressContainer = e.target.closest('.nobifashion_reels_progress_container');
      if (!progressContainer) return;

      e.preventDefault();
      e.stopPropagation();

      const computeRatio = (evt) => {
        const rect = progressContainer.getBoundingClientRect();
        if (rect.width <= 0) return 0;
        return Math.max(0, Math.min(1, (evt.clientX - rect.left) / rect.width));
      };

      this.player.isScrubbing = true;
      progressContainer.classList.add('nobifashion_reels_is_scrubbing');

      const initialRatio = computeRatio(e);
      // Seek ngay lập tức tại thời điểm bấm 0ms
      this.player.seekToRatio(initialRatio, true);

      let scrubRaf = null;
      const onPointerMove = (evt) => {
        evt.preventDefault();
        if (scrubRaf) cancelAnimationFrame(scrubRaf);
        scrubRaf = requestAnimationFrame(() => {
          const r = computeRatio(evt);
          this.player.seekToRatio(r, false);
        });
      };

      const onPointerUp = (evt) => {
        if (scrubRaf) cancelAnimationFrame(scrubRaf);
        const finalRatio = computeRatio(evt);
        this.player.seekToRatio(finalRatio, true);
        this.player.isScrubbing = false;
        progressContainer.classList.remove('nobifashion_reels_is_scrubbing');

        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        window.removeEventListener('pointercancel', onPointerUp);
      };

      window.addEventListener('pointermove', onPointerMove, { passive: false });
      window.addEventListener('pointerup', onPointerUp);
      window.addEventListener('pointercancel', onPointerUp);
    });

    // Hover xem trước Thumbnail & Thời gian video trên Desktop
    let hoverPreviewRaf = null;
    this.appEl.addEventListener('pointermove', (e) => {
      if (e.pointerType === 'touch') return; // Bỏ qua trên màn hình cảm ứng di động

      const progressContainer = e.target.closest('.nobifashion_reels_progress_container');
      if (!progressContainer) return;

      const tooltip = progressContainer.querySelector('.nobifashion_reels_progress_tooltip');
      const previewVideo = progressContainer.querySelector('.nobifashion_reels_preview_thumb_video');
      const timeBadge = progressContainer.querySelector('.nobifashion_reels_preview_time_badge');
      if (!tooltip || !timeBadge) return;

      const rect = progressContainer.getBoundingClientRect();
      if (rect.width <= 0) return;

      const rawX = e.clientX - rect.left;
      const ratio = Math.max(0, Math.min(1, rawX / rect.width));

      const dur =
        this.player.activeVideoEl &&
        Number.isFinite(this.player.activeVideoEl.duration) &&
        this.player.activeVideoEl.duration > 0
          ? this.player.activeVideoEl.duration
          : 10;
      const hoverTime = ratio * dur;

      // Giữ tooltip 9:16 không bị tràn lề trái / phải của khung video (width 108px -> halfWidth = 58px)
      const halfWidth = 58;
      const clampedX = Math.max(halfWidth + 4, Math.min(rect.width - halfWidth - 4, rawX));
      tooltip.style.left = `${clampedX}px`;
      tooltip.classList.add('nobifashion_reels_is_visible');

      // Cập nhật nhãn thời gian trỏ và tổng thời gian
      timeBadge.textContent = `${this.player.formatSeconds(hoverTime)} / ${this.player.formatSeconds(dur)}`;

      // Cập nhật frame ảnh thu nhỏ tại thời điểm đang trỏ tới
      if (previewVideo) {
        if (hoverPreviewRaf) cancelAnimationFrame(hoverPreviewRaf);
        hoverPreviewRaf = requestAnimationFrame(() => {
          try {
            if (typeof previewVideo.fastSeek === 'function') {
              previewVideo.fastSeek(hoverTime);
            } else {
              previewVideo.currentTime = hoverTime;
            }
          } catch (_) {
            previewVideo.currentTime = hoverTime;
          }
        });
      }
    });

    this.appEl.addEventListener('pointerout', (e) => {
      const progressContainer = e.target.closest('.nobifashion_reels_progress_container');
      if (!progressContainer) return;

      if (!progressContainer.contains(e.relatedTarget)) {
        const tooltip = progressContainer.querySelector('.nobifashion_reels_progress_tooltip');
        if (tooltip) {
          tooltip.classList.remove('nobifashion_reels_is_visible');
        }
      }
    });

    // Backdrop click closes drawers and modals
    if (this.backdropEl) {
      this.backdropEl.addEventListener('click', () => {
        this.productController.closeAllDrawers();
        this.interactionController.closeModal();
      });
    }
  }

  bindKeyboardNavigation() {
    window.addEventListener('keydown', (e) => {
      const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
      const isTyping =
        activeTag === 'input' ||
        activeTag === 'textarea' ||
        activeTag === 'select' ||
        document.activeElement?.isContentEditable;

      if (e.key === 'Escape') {
        this.productController.closeAllDrawers();
        this.interactionController.closeModal();
        this.uiController.searchDropdown?.classList.remove('nobifashion_reels_is_open');
        this.uiController.popoverEl?.classList.remove('nobifashion_reels_is_open');
        this.workspaceEl?.classList.remove('nobifashion_reels_mobile_comments_open');
        return;
      }

      if (isTyping) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        this.navigationController.step(1);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        this.navigationController.step(-1);
      } else if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        this.player.togglePlayPause();
      } else if (e.key === 'm' || e.key === 'M') {
        e.preventDefault();
        this.player.toggleMute();
      } else if (e.key === 'l' || e.key === 'L') {
        const activeReel = this.navigationController.getActiveReel();
        if (activeReel) this.interactionController.handleToggleLike(activeReel.id);
      }
    });
  }

  canAutoScrollNext() {
    // 1. Kiểm tra nếu thanh sidebar (bình luận / chi tiết) đang bật: Không tự cuộn
    if (this.sidebarEl) {
      if (window.innerWidth <= 1023) {
        if (this.workspaceEl?.classList.contains('nobifashion_reels_mobile_comments_open')) {
          return false;
        }
      } else {
        // Trên Desktop: Nếu không có class nobifashion_reels_sidebar_collapsed tức là sidebar ĐANG BẬT
        if (!this.workspaceEl?.classList.contains('nobifashion_reels_sidebar_collapsed')) {
          return false;
        }
      }
    }

    // 2. Kiểm tra nếu chuột đang ở ô comment, hoặc đang gõ/focus vào ô comment
    const commentInput = document.getElementById('nobifashion_reels_comment_input');
    if (commentInput && document.activeElement === commentInput) {
      return false;
    }

    try {
      if (commentInput && commentInput.matches(':hover')) {
        return false;
      }
      const commentForm = document.getElementById('nobifashion_reels_comment_form');
      if (commentForm && commentForm.matches(':hover')) {
        return false;
      }
      const commentsList = document.getElementById('nobifashion_reels_comments_list');
      if (commentsList && commentsList.matches(':hover')) {
        return false;
      }
      if (this.sidebarEl && this.sidebarEl.matches(':hover')) {
        return false;
      }
    } catch (_) {}

    // 3. Nếu đang mở modal (Share) hoặc drawer (Sản phẩm, Giỏ hàng)
    if (
      document.querySelector('.nobifashion_reels_drawer.nobifashion_reels_is_open') ||
      document.querySelector('.nobifashion_reels_modal.nobifashion_reels_is_open')
    ) {
      return false;
    }

    return true;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new NobiFashionReelsApp();
});

})();