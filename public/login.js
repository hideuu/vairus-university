const passwordInput = document.querySelector('#password');
const passwordToggle = document.querySelector('.password-toggle');
passwordToggle.hidden = false;
passwordToggle.addEventListener('click', () => {
    const showPassword = passwordInput.type === 'password';
    passwordInput.type = showPassword ? 'text' : 'password';
    passwordToggle.textContent = showPassword ? 'Hide' : 'Show';
    passwordToggle.setAttribute('aria-pressed', String(showPassword));
});
