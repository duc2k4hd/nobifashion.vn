<!-- Call to action -->
<section class="nobifashion_main_newsletter_banner_section">
    <div class="nobifashion_main_newsletter_banner">
        
        <div class="nobifashion_main_newsletter_banner_content">
            <h2 class="nobifashion_main_newsletter_banner_title">
                Đăng ký nhận bản tin
            </h2>

            <p class="nobifashion_main_newsletter_banner_desc">
                Nhận thông tin mới nhất về sản phẩm, xu hướng thời trang và ưu đãi độc quyền từ NOBI FASHION.
            </p>

            <form class="nobifashion_main_newsletter_banner_form">
                <input type="email" 
                       class="nobifashion_main_newsletter_banner_input"
                       placeholder="Nhập email của bạn..."
                       required>
                <button type="submit" class="nobifashion_main_newsletter_banner_btn">
                    Đăng ký
                </button>
            </form>
        </div>

        <div class="nobifashion_main_newsletter_banner_img">
            <img loading="lazy"
                 src="{{ asset('clients/assets/img/banners/thuong-hieu-NOBI-FASHION-VIET-NAM.jpg') }}"
                 alt="Nhận thông tin mới nhất từ NOBI FASHION">
        </div>

    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const form = document.querySelector(".nobifashion_main_newsletter_banner_form");
        const input = document.querySelector(".nobifashion_main_newsletter_banner_input");

        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const email = input.value.trim();
            if (!email) {
                showCustomToast("Vui lòng nhập email");
                return;
            }

            // Disable button khi đang gửi
            const btn = form.querySelector("button");
            btn.disabled = true;
            btn.innerText = "Đang gửi...";

            try {
                const response = await fetch("{{ route('newsletter.subscribe') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: email,
                        source: "newsletter_banner"
                    }),
                });

                const data = await response.json();

                // ⚠️ Nếu lỗi validate
                if (!response.ok) {
                    showCustomToast(data.message ?? "Có lỗi xảy ra, vui lòng thử lại.");
                } else {
                    showCustomToast(data.message);
                    input.value = "";
                }
            } catch (error) {
                console.error(error);
                showCustomToast("Lỗi kết nối, vui lòng thử lại!");
            }

            // Bật lại button
            btn.disabled = false;
            btn.innerText = "Đăng ký";
        });
    });

</script>
