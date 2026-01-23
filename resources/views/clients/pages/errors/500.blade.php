<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/clients/assets/img/business/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/clients/assets/img/business/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/clients/assets/img/business/favicon.png') }}">
    <link rel="mask-icon" href="{{ asset('/clients/assets/img/business/favicon.png') }}" color="#5bbad5">
    <link rel="icon" href="{{ asset('/clients/assets/img/business/favicon.png') }}" type="image/x-icon">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="nofollow, noindex"/>
    <link rel="stylesheet" href="{{ asset('clients/assets/css/404.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Lỗi máy chủ - {{ $settings->site_name ?? 'NOBI FASHION' }}</title>
</head>

<body>
    <div class="hamburger-menu">
        <button class="burger" data-state="closed">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
    <nav data-state="closed">
        <ul>
            <li>
                <a href="/">Trang chủ</a>
            </li>
            <li>
                <a href="/shop">Giới thiệu</a>
            </li>
            <li>
                <a href="/contact">Liên hệ</a>
            </li>
        </ul>
    </nav>
    <main>
        <div class="container">
            <div class="row">
                <div class="col-md-6 align-self-center">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        viewBox="0 0 800 600">
                        <g>
                            <defs>
                                <clipPath id="GlassClip">
                                    <path
                                        d="M380.857,346.164c-1.247,4.651-4.668,8.421-9.196,10.06c-9.332,3.377-26.2,7.817-42.301,3.5
                      s-28.485-16.599-34.877-24.192c-3.101-3.684-4.177-8.66-2.93-13.311l7.453-27.798c0.756-2.82,3.181-4.868,6.088-5.13
                      c6.755-0.61,20.546-0.608,41.785,5.087s33.181,12.591,38.725,16.498c2.387,1.682,3.461,4.668,2.705,7.488L380.857,346.164z" /> 
                                </clipPath>
                                <clipPath id="cordClip">
                                    <rect width="800" height="600" />
                                </clipPath>
                            </defs>

                            <g id="planet">
                                <circle fill="none" stroke="#dc3545" stroke-width="3" stroke-miterlimit="10"
                                    cx="572.859" cy="108.803" r="90.788" />

                                <circle id="craterBig" fill="none" stroke="#dc3545" stroke-width="3"
                                    stroke-miterlimit="10" cx="548.891" cy="62.319" r="13.074" />

                                <circle id="craterSmall" fill="none" stroke="#dc3545" stroke-width="3"
                                    stroke-miterlimit="10" cx="591.743" cy="158.918" r="7.989" />
                                <path id="ring" fill="none" stroke="#dc3545" stroke-width="3"
                                    stroke-linecap="round" stroke-miterlimit="10" d="
                  M476.562,101.461c-30.404,2.164-49.691,4.221-49.691,8.007c0,6.853,63.166,12.408,141.085,12.408s141.085-5.555,141.085-12.408     
                  c0-3.378-15.347-4.988-40.243-7.225" />

                                <path id="ringShadow" opacity="0.5" fill="none" stroke="#dc3545" stroke-width="3"
                                    stroke-linecap="round" stroke-miterlimit="10" d="
                  M483.985,127.43c23.462,1.531,52.515,2.436,83.972,2.436c36.069,0,68.978-1.19,93.922-3.149" />
                            </g>
                            <g id="stars">
                                <g id="starsBig">
                                    <g>
                                        <line fill="none" stroke="#dc3545" stroke-width="3" stroke-linecap="round"
                                            stroke-miterlimit="10" x1="518.07" y1="245.375" x2="518.07"
                                            y2="266.581" />
                                        <line fill="none" stroke="#dc3545" stroke-width="3" stroke-linecap="round"
                                            stroke-miterlimit="10" x1="508.129" y1="255.978" x2="528.01"
                                            y2="255.978" />
                                    </g>
                                    <g>
                                        <line fill="none" stroke="#dc3545" stroke-width="3" stroke-linecap="round"
                                            stroke-miterlimit="10" x1="154.55" y1="231.391" x2="154.55"
                                            y2="252.598" />
                                        <line fill="none" stroke="#dc3545" stroke-width="3" stroke-linecap="round"
                                            stroke-miterlimit="10" x1="144.609" y1="241.995" x2="164.49"
                                            y2="241.995" />
                                    </g>
                                    <g>
                                        <line fill="none" stroke="#dc3545" stroke-width="3" stroke-linecap="round"
                                            stroke-miterlimit="10" x1="320.135" y1="132.746" x2="320.135"
                                            y2="153.952" />
                                        <line fill="none" stroke="#dc3545" stroke-width="3"
                                            stroke-linecap="round" stroke-miterlimit="10" x1="310.194"
                                            y1="143.349" x2="330.075" y2="143.349" />
                                    </g>
                                    <g>
                                        <line fill="none" stroke="#dc3545" stroke-width="3"
                                            stroke-linecap="round" stroke-miterlimit="10" x1="200.67"
                                            y1="483.11" x2="200.67" y2="504.316" />
                                        <line fill="none" stroke="#dc3545" stroke-width="3"
                                            stroke-linecap="round" stroke-miterlimit="10" x1="210.611"
                                            y1="493.713" x2="190.73" y2="493.713" />
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>
                </div>
                <div class="col-md-6 align-self-center">
                    <h1>500</h1>
                    <h2>Lỗi máy chủ!</h2>
                    <p>Xin lỗi, đã xảy ra lỗi nội bộ. Vui lòng thử lại sau hoặc liên hệ với chúng tôi nếu vấn đề vẫn tiếp tục.</p>
                    <button class="btn green"><a href="/">Trang chủ</a></button>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        // Hiển thị SVG sau khi tải
        gsap.set("svg", {
            visibility: "visible"
        });

        // Animation cho các element
        gsap.to("#planet", {
            rotation: -2,
            yoyo: true,
            repeat: -1,
            duration: 1,
            ease: "sine.inOut",
            transformOrigin: "50% 50%"
        });

        // Toggle menu
        const burger = document.querySelector('.burger');
        const nav = document.querySelector('nav');

        burger.addEventListener('click', () => {
            const newState = burger.dataset.state === 'closed' ? 'open' : 'closed';
            burger.dataset.state = newState;
            nav.dataset.state = newState;
        });
    </script>
</body>

</html>
