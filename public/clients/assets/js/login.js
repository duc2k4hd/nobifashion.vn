(function () {
    const form = document.querySelector('.nobifashion_form_login');
    if (!form) {
        return;
    }

    const emailInput = form.querySelector('.nobifashion_form_login_email');
    const passwordInput = form.querySelector('.nobifashion_form_login_password');
    const emailMessage = form.querySelector('.nobifashion_message_email');
    const passwordMessage = form.querySelector('.nobifashion_message_password');
    const togglePasswordButton = document.getElementById('togglePassword');

    const showMessage = (element, message = '') => {
        if (!element) {
            return;
        }
        element.textContent = message;
        element.style.display = message ? 'block' : 'none';
    };

    const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    const isValidPassword = (value) => typeof value === 'string' && value.length >= 8;

    const validateForm = () => {
        let valid = true;
        const email = emailInput?.value.trim() ?? '';
        const password = passwordInput?.value.trim() ?? '';

        if (!email) {
            showMessage(emailMessage, 'Vui lòng nhập email');
            valid = false;
        } else if (!isValidEmail(email)) {
            showMessage(emailMessage, 'Email không hợp lệ');
            valid = false;
        } else {
            showMessage(emailMessage, '');
        }

        if (!isValidPassword(password)) {
            showMessage(passwordMessage, 'Mật khẩu phải từ 8 ký tự');
            valid = false;
        } else {
            showMessage(passwordMessage, '');
        }

        return valid;
    };

    emailInput?.addEventListener('input', () => {
        const value = emailInput.value.trim();
        if (!value) {
            showMessage(emailMessage, 'Vui lòng nhập email');
        } else if (!isValidEmail(value)) {
            showMessage(emailMessage, 'Email không hợp lệ');
        } else {
            showMessage(emailMessage, '');
        }
    });

    passwordInput?.addEventListener('input', () => {
        const value = passwordInput.value.trim();
        if (!isValidPassword(value)) {
            showMessage(passwordMessage, 'Mật khẩu phải từ 8 ký tự');
        } else {
            showMessage(passwordMessage, '');
        }
    });

    togglePasswordButton?.addEventListener('click', () => {
        if (!passwordInput) {
            return;
        }
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePasswordButton.classList.toggle('fa-eye');
        togglePasswordButton.classList.toggle('fa-eye-slash');
    });

    form.addEventListener('submit', (event) => {
        if (!validateForm()) {
            event.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Thông báo',
                    text: 'Vui lòng kiểm tra lại email và mật khẩu.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                });
            }
        }
    });
})();
