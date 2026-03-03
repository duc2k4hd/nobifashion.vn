(async () => {
    // === SLIDER CHÍNH ===
    const sliderList =
        document.querySelector(".nobifashion_main_slider_main_slider_track") ||
        document.querySelector(".nobifashion_main_slider_track");

    const slides = document.querySelectorAll(
        ".nobifashion_main_slider_main_slide, .nobifashion_main_slider_item"
    );

    const dots = document.querySelectorAll(
        ".nobifashion_main_slider_main_dots button, .nobifashion_main_slider_dot"
    );

    let currentSlide = 0;

    const updateSlider = () => {
        if (!sliderList || slides.length === 0) return;

        dots.forEach(dot =>
            dot.classList.remove("nobifashion_main_slider_dot_active")
        );

        if (dots[currentSlide]) {
            dots[currentSlide].classList.add("nobifashion_main_slider_dot_active");
        }

        sliderList.style.transform = `translateX(-${currentSlide * 100}%)`;
    };

    document
        .querySelector(".nobifashion_main_slider_prev")
        ?.addEventListener("click", () => {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateSlider();
        });

    document
        .querySelector(".nobifashion_main_slider_next")
        ?.addEventListener("click", () => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlider();
        });

    dots.forEach((dot, idx) => {
        dot?.addEventListener("click", () => {
            currentSlide = idx;
            updateSlider();
        });
    });

    if (sliderList && slides.length > 1) {
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlider();
        }, 5000);
    }

    // ============================================
    // CATEGORY AUTO SCROLL — CHỐNG NULL 100%
    // ============================================

    function autoScrollCategories() {
        const slider = document.querySelector(".nobifashion_main_categories_list");
        if (!slider) return;

        let isDown = false;
        let isDragging = false;
        let startX;
        let scrollLeft;

        let autoScrollInterval;
        let scrollDirection = 1;

        let itemWidth = 0;
        const firstItem = slider.querySelector(".nobifashion_main_categories_item");

        if (firstItem) {
            const gap = 20;
            itemWidth = firstItem.offsetWidth + gap;
        }

        function startAutoScroll() {
            stopAutoScroll();
            autoScrollInterval = setInterval(() => {
                const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
                let targetScrollLeft;

                if (scrollDirection === 1) {
                    const nextScroll = slider.scrollLeft + itemWidth;
                    if (nextScroll >= maxScrollLeft) {
                        scrollDirection = -1;
                        targetScrollLeft = maxScrollLeft;
                    } else {
                        targetScrollLeft = nextScroll;
                    }
                } else {
                    const nextScroll = slider.scrollLeft - itemWidth;
                    if (nextScroll <= 0) {
                        scrollDirection = 1;
                        targetScrollLeft = 0;
                    } else {
                        targetScrollLeft = nextScroll;
                    }
                }

                const startTime = performance.now();
                const startPosition = slider.scrollLeft;
                const duration = 500;

                function animateScroll(currentTime) {
                    const elapsedTime = currentTime - startTime;
                    const progress = Math.min(elapsedTime / duration, 1);
                    slider.scrollLeft =
                        startPosition +
                        (targetScrollLeft - startPosition) * progress;

                    if (progress < 1) {
                        requestAnimationFrame(animateScroll);
                    }
                }

                requestAnimationFrame(animateScroll);
            }, 2000);
        }

        function stopAutoScroll() {
            clearInterval(autoScrollInterval);
        }

        slider.addEventListener("mousedown", e => {
            stopAutoScroll();
            isDown = true;
            isDragging = false;
            slider.classList.add("active-drag");
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener("mouseup", () => {
            isDown = false;
            slider.classList.remove("active-drag");
            setTimeout(startAutoScroll, 1000);
        });

        slider.addEventListener("mouseleave", () => {
            if (isDown) {
                isDown = false;
                slider.classList.remove("active-drag");
                setTimeout(startAutoScroll, 1000);
            }
        });

        slider.addEventListener("mousemove", e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = x - startX;
            if (Math.abs(walk) > 5) isDragging = true;
            slider.scrollLeft = scrollLeft - walk;
        });

        slider.addEventListener("touchstart", e => {
            stopAutoScroll();
            isDown = true;
            isDragging = false;
            slider.classList.add("active-drag");
            startX = e.touches[0].pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener("touchend", () => {
            isDown = false;
            slider.classList.remove("active-drag");
            setTimeout(startAutoScroll, 1000);
        });

        slider.addEventListener("touchmove", e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - slider.offsetLeft;
            const walk = x - startX;
            if (Math.abs(walk) > 5) isDragging = true;
            slider.scrollLeft = scrollLeft - walk;
        });

        slider.addEventListener(
            "click",
            e => {
                if (isDragging) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            },
            true
        );

        startAutoScroll();
    }

    // ============================================
    // FLASH SALE — CHỐNG NULL
    // ============================================

    function handleFlashSale() {
        const slider = document.querySelector(".nobifashion_flash_sale_list");
        const btnPrev = document.querySelector(".nobifashion_flash_sale_prev");
        const btnNext = document.querySelector(".nobifashion_flash_sale_next");

        if (!slider) return;

        const step = 400;
        let isDown = false;
        let isDragging = false;
        let startX;
        let scrollLeft;

        btnPrev?.addEventListener("click", () => {
            slider.scrollBy({ left: -step, behavior: "smooth" });
        });

        btnNext?.addEventListener("click", () => {
            slider.scrollBy({ left: step, behavior: "smooth" });
        });

        slider.addEventListener("mousedown", e => {
            isDown = true;
            isDragging = false;
            e.preventDefault();
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener("mouseleave", () => {
            isDown = false;
        });

        slider.addEventListener("mouseup", () => {
            isDown = false;
        });

        slider.addEventListener("mousemove", e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 1.5;
            if (Math.abs(walk) > 5) isDragging = true;
            slider.scrollLeft = scrollLeft - walk;
        });

        slider.addEventListener("touchstart", e => {
            isDown = true;
            isDragging = false;
            startX = e.touches[0].pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener("touchend", () => {
            isDown = false;
        });

        slider.addEventListener("touchmove", e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - slider.offsetLeft;
            const walk = (x - startX) * 2;
            if (Math.abs(walk) > 5) isDragging = true;
            slider.scrollLeft = scrollLeft - walk;
        });

        slider.addEventListener(
            "click",
            e => {
                if (isDragging) e.preventDefault();
            },
            true
        );
    }

    // ============================================
    // SCROLL CATEGORY PRODUCTS — CHỐNG NULL
    // ============================================

    function scrollProductCategories() {
        const slider = document.querySelector(
            ".nobifashion_main_product_category_products"
        );
        if (!slider) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener("mousedown", e => {
            isDown = true;
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener("mouseleave", () => {
            isDown = false;
        });

        slider.addEventListener("mouseup", () => {
            isDown = false;
        });

        slider.addEventListener("mousemove", e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = x - startX;
            slider.scrollLeft = scrollLeft - walk;
        });

        slider.addEventListener("touchstart", e => {
            startX = e.touches[0].pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener("touchmove", e => {
            const x = e.touches[0].pageX - slider.offsetLeft;
            const walk = x - startX;
            slider.scrollLeft = scrollLeft - walk;
        });
    }

    // ============================================
    // FLASH SALE TIMER — CHỐNG NULL
    // ============================================

    const endTime = typeof timeFlashSale !== "undefined" ? timeFlashSale : null;

    const daysEl = document.querySelector(".nobifashion_flash_sale_timer_days");
    const hoursEl = document.querySelector(".nobifashion_flash_sale_timer_hours");
    const minutesEl = document.querySelector(
        ".nobifashion_flash_sale_timer_minutes"
    );
    const secondsEl = document.querySelector(
        ".nobifashion_flash_sale_timer_seconds"
    );

    if (endTime && daysEl && hoursEl && minutesEl && secondsEl) {
        let prevDays, prevHours, prevMinutes, prevSeconds;

        function animateFlip(el, newValue) {
            if (!el) return;
            el.textContent = newValue;
            el.classList.remove("flip-animate");
            void el.offsetWidth;
            el.classList.add("flip-animate");
        }

        function updateTimer() {
            const now = new Date().getTime();
            let distance = endTime - now;

            if (distance <= 0) {
                animateFlip(daysEl, "00");
                animateFlip(hoursEl, "00");
                animateFlip(minutesEl, "00");
                animateFlip(secondsEl, "00");
                clearInterval(interval);
                return;
            }

            let days = Math.floor(distance / (1000 * 60 * 60 * 24));
            let hours = Math.floor(
                (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
            );
            let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            let seconds = Math.floor((distance % (1000 * 60)) / 1000);

            days = String(days).padStart(2, "0");
            hours = String(hours).padStart(2, "0");
            minutes = String(minutes).padStart(2, "0");
            seconds = String(seconds).padStart(2, "0");

            if (seconds !== prevSeconds) animateFlip(secondsEl, seconds);
            if (minutes !== prevMinutes) animateFlip(minutesEl, minutes);
            if (hours !== prevHours) animateFlip(hoursEl, hours);
            if (days !== prevDays) animateFlip(daysEl, days);

            prevDays = days;
            prevHours = hours;
            prevMinutes = minutes;
            prevSeconds = seconds;
        }

        const interval = setInterval(updateTimer, 1000);
        updateTimer();
    }

    // ============================================
    // KHỞI CHẠY CHÍNH – TẤT CẢ ĐỀU CHỐNG NULL
    // ============================================

    scrollProductCategories();
    handleFlashSale();

    setTimeout(autoScrollCategories, 3000);
})();
