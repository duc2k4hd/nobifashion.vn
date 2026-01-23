(async () => {
    // === SLIDER CHÍNH ===
    const sliderList = document.querySelector(".nobifashion_main_slider_main_slider_track") || document.querySelector(".nobifashion_main_slider_track");
    const slides = document.querySelectorAll(".nobifashion_main_slider_main_slide, .nobifashion_main_slider_item");
    const dots = document.querySelectorAll(".nobifashion_main_slider_main_dots button, .nobifashion_main_slider_dot");
    let currentSlide = 0;

    const updateSlider = () => {
        if (!sliderList || slides.length === 0) return;
        dots.forEach(dot => dot.classList.remove("nobifashion_main_slider_dot_active"));
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add("nobifashion_main_slider_dot_active");
        }
        sliderList.style.transform = `translateX(-${currentSlide * 100}%)`;
    };

    document.querySelector(".nobifashion_main_slider_prev")?.addEventListener("click", () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlider();
    });

    document.querySelector(".nobifashion_main_slider_next")?.addEventListener("click", () => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
    });

    dots.forEach((dot, idx) => {
        dot.addEventListener("click", () => {
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

    // CATEGORY AUTO SROLL
    function autoScrollCategories() {
        const slider = document.querySelector('.nobifashion_main_categories_list');
        if (!slider) return;
        let isDown = false;
        let isDragging = false;
        let startX;
        let scrollLeft;
        
        let autoScrollInterval;
        let scrollDirection = 1; // 1: lướt sang phải, -1: lướt sang trái

        // Lấy chiều rộng của một danh mục để lướt theo từng bước
        let itemWidth = 0;
        const firstItem = slider.querySelector('.nobifashion_main_categories_item');
        if (firstItem) {
            const gap = 20; // Khoảng cách giữa các mục trong CSS
            itemWidth = firstItem.offsetWidth + gap;
        }

        // Bắt đầu tự động lướt
        function startAutoScroll() {
            stopAutoScroll(); // Đảm bảo chỉ có một interval đang chạy
            autoScrollInterval = setInterval(() => {
                const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
                let targetScrollLeft;

                if (scrollDirection === 1) {
                    // Lướt sang phải
                    const nextScroll = slider.scrollLeft + itemWidth;
                    if (nextScroll >= maxScrollLeft) {
                        scrollDirection = -1; // Đổi hướng
                        targetScrollLeft = maxScrollLeft;
                    } else {
                        targetScrollLeft = nextScroll;
                    }
                } else {
                    // Lướt sang trái
                    const nextScroll = slider.scrollLeft - itemWidth;
                    if (nextScroll <= 0) {
                        scrollDirection = 1; // Đổi hướng
                        targetScrollLeft = 0;
                    } else {
                        targetScrollLeft = nextScroll;
                    }
                }
                
                // Dùng requestAnimationFrame để tạo animation cuộn mượt
                const startTime = performance.now();
                const startPosition = slider.scrollLeft;
                const duration = 500; // Thời gian animation cuộn (ms)

                function animateScroll(currentTime) {
                    const elapsedTime = currentTime - startTime;
                    const progress = Math.min(elapsedTime / duration, 1);
                    slider.scrollLeft = startPosition + (targetScrollLeft - startPosition) * progress;

                    if (progress < 1) {
                        requestAnimationFrame(animateScroll);
                    }
                }
                requestAnimationFrame(animateScroll);

            }, 2000); // Chạy mỗi 2 giây
        }

        // Dừng tự động lướt
        function stopAutoScroll() {
            clearInterval(autoScrollInterval);
        }
        
        // --- Xử lý sự kiện kéo và lướt bằng tay ---

        // Xử lý sự kiện khi nhấn chuột hoặc chạm màn hình
        slider.addEventListener('mousedown', (e) => {
            stopAutoScroll(); // Dừng tự động lướt khi người dùng tương tác
            isDown = true;
            isDragging = false;
            slider.classList.add('active-drag');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        // Xử lý sự kiện khi nhả chuột hoặc nhấc ngón tay
        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.classList.remove('active-drag');
            // Tiếp tục tự động lướt sau một giây
            setTimeout(startAutoScroll, 1000);
        });

        // Xử lý sự kiện khi chuột rời khỏi vùng chứa
        slider.addEventListener('mouseleave', () => {
            if (isDown) { // Nếu đang kéo mà chuột rời đi
                isDown = false;
                slider.classList.remove('active-drag');
                setTimeout(startAutoScroll, 1000);
            }
        });

        // Xử lý sự kiện khi di chuyển chuột hoặc vuốt
        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            
            // Ngăn chặn hành vi mặc định (ví dụ: kéo-thả hình ảnh)
            e.preventDefault();
            
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX); // Tốc độ lướt linh hoạt theo chuột

            // Nếu di chuyển đủ xa, coi là đang kéo
            if (Math.abs(walk) > 5) {
                isDragging = true;
            }

            slider.scrollLeft = scrollLeft - walk;
        });
        
        // Xử lý sự kiện chạm cho thiết bị di động
        slider.addEventListener('touchstart', (e) => {
            stopAutoScroll();
            isDown = true;
            isDragging = false;
            slider.classList.add('active-drag');
            startX = e.touches[0].pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        
        slider.addEventListener('touchend', () => {
            isDown = false;
            slider.classList.remove('active-drag');
            setTimeout(startAutoScroll, 1000);
        });
        
        slider.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - slider.offsetLeft;
            const walk = (x - startX);
            if (Math.abs(walk) > 5) {
                isDragging = true;
            }
            slider.scrollLeft = scrollLeft - walk;
        });

        // Xử lý sự kiện khi nhấp chuột vào link để ngăn chặn nếu đó là thao tác kéo
        slider.addEventListener('click', (e) => {
            if (isDragging) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }, true); // Sử dụng capture phase để đảm bảo sự kiện này chạy trước các event listener khác
        
        // Bắt đầu lướt tự động khi trang web tải xong
        startAutoScroll();
    }

    function handleFlashSale() {
    const slider = document.querySelector(".nobifashion_flash_sale_list");
    const btnPrev = document.querySelector(".nobifashion_flash_sale_prev");
    const btnNext = document.querySelector(".nobifashion_flash_sale_next");

    const step = 400; // px mỗi lần bấm nút
    let isDown = false;
    let isDragging = false;
    let startX;
    let scrollLeft;

    // Nút Prev/Next
    btnPrev.addEventListener("click", () => {
        slider.scrollBy({ left: -step, behavior: "smooth" });
    });
    btnNext.addEventListener("click", () => {
        slider.scrollBy({ left: step, behavior: "smooth" });
    });

    // Kéo bằng chuột
    slider.addEventListener("mousedown", (e) => {
        isDown = true;
        isDragging = false;
        slider.classList.add("active-drag");
        // Ngăn chặn hành vi kéo-thả mặc định của trình duyệt
        e.preventDefault();
        // Lưu vị trí chuột và vị trí cuộn ban đầu
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });

    slider.addEventListener("mouseleave", () => {
        isDown = false;
        slider.classList.remove("active-drag");
    });

    slider.addEventListener("mouseup", () => {
        isDown = false;
        slider.classList.remove("active-drag");
    });

    slider.addEventListener("mousemove", (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 1.5; // Tăng tốc độ cuộn cho cảm giác mượt mà hơn
        
        // Nếu di chuyển đủ xa, coi là đang kéo
        if (Math.abs(walk) > 5) {
            isDragging = true;
        }

        slider.scrollLeft = scrollLeft - walk;
    });

    // Vuốt cảm ứng
    slider.addEventListener("touchstart", (e) => {
        isDown = true;
        isDragging = false;
        // Ngăn chặn hành vi kéo-thả mặc định của trình duyệt trên cảm ứng
        e.preventDefault();
        startX = e.touches[0].pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });

    slider.addEventListener("touchend", () => {
        isDown = false;
    });

    slider.addEventListener("touchmove", (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.touches[0].pageX - slider.offsetLeft;
        const walk = (x - startX) * 2; // Tăng tốc độ cuộn
        
        if (Math.abs(walk) > 5) {
            isDragging = true;
        }
        
        slider.scrollLeft = scrollLeft - walk;
    });

    // Ngăn chặn sự kiện click nếu đó là thao tác kéo
    slider.addEventListener("click", (e) => {
        if (isDragging) {
            e.preventDefault();
        }
    }, true);
}

function scrollProductCategories() {
    const slider = document.querySelector(".nobifashion_main_product_category_products");
  let isDown = false;
  let startX;
  let scrollLeft;

  slider.addEventListener("mousedown", (e) => {
    isDown = true;
    slider.classList.add("active");
    startX = e.pageX - slider.offsetLeft;
    scrollLeft = slider.scrollLeft;
  });

  slider.addEventListener("mouseleave", () => {
    isDown = false;
    slider.classList.remove("active");
  });

  slider.addEventListener("mouseup", () => {
    isDown = false;
    slider.classList.remove("active");
  });

  slider.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - slider.offsetLeft;
    const walk = (x - startX); // tốc độ kéo
    slider.scrollLeft = scrollLeft - walk;
  });

  // Hỗ trợ cảm ứng (mobile)
  let touchStartX = 0;
  let touchScrollLeft = 0;

  slider.addEventListener("touchstart", (e) => {
    touchStartX = e.touches[0].pageX - slider.offsetLeft;
    touchScrollLeft = slider.scrollLeft;
  });

  slider.addEventListener("touchmove", (e) => {
    const x = e.touches[0].pageX - slider.offsetLeft;
    const walk = (x - touchStartX);
    slider.scrollLeft = touchScrollLeft - walk;
  });

}

    // Set thời gian kết thúc Flash Sale (ví dụ lấy từ DB)
    const endTime = timeFlashSale;

    // Lấy các phần tử hiển thị
    const daysEl = document.querySelector(".nobifashion_flash_sale_timer_days");
    const hoursEl = document.querySelector(".nobifashion_flash_sale_timer_hours");
    const minutesEl = document.querySelector(".nobifashion_flash_sale_timer_minutes");
    const secondsEl = document.querySelector(".nobifashion_flash_sale_timer_seconds");

    // Lưu giá trị trước đó để so sánh
let prevDays, prevHours, prevMinutes, prevSeconds;

function updateTimer() {
    const now = new Date().getTime();
    let distance = endTime - now;

    if (distance <= 0) {
        daysEl.textContent = "00";
        hoursEl.textContent = "00";
        minutesEl.textContent = "00";
        secondsEl.textContent = "00";
        clearInterval(interval);
        return;
    }

    let days = Math.floor(distance / (1000 * 60 * 60 * 24));
    let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    let seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // Format 2 chữ số
    days = String(days).padStart(2, "0");
    hours = String(hours).padStart(2, "0");
    minutes = String(minutes).padStart(2, "0");
    seconds = String(seconds).padStart(2, "0");

    // Chỉ animate khi giá trị thay đổi
    if (seconds !== prevSeconds) animateFlip(secondsEl, seconds);
    if (minutes !== prevMinutes) animateFlip(minutesEl, minutes);
    if (hours !== prevHours) animateFlip(hoursEl, hours);
    if (days !== prevDays) animateFlip(daysEl, days);

    // Cập nhật giá trị trước đó
    prevDays = days;
    prevHours = hours;
    prevMinutes = minutes;
    prevSeconds = seconds;
}

// Hàm gán text + trigger animation
function animateFlip(el, newValue) {
    el.textContent = newValue;
    el.classList.remove("flip-animate");
    void el.offsetWidth; // reset
    el.classList.add("flip-animate");
}

const interval = setInterval(updateTimer, 1000);
updateTimer();

    scrollProductCategories();

    handleFlashSale();

    setTimeout(autoScrollCategories, 3000); // Đợi 3 giây để đảm bảo DOM đã sẵn sàng
})();
