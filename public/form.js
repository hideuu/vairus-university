// Shared enhancement for normal PHP POST forms; native validation runs first.
const pendingForms = new Map();
document.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
    form.addEventListener('invalid', (event) => {
        let group = event.target.closest('details');
        while (group) {
            group.open = true;
            group = group.parentElement?.closest('details');
        }
    }, true);
    form.addEventListener('submit', (event) => {
        if (pendingForms.has(form)) {
            event.preventDefault();
            return;
        }
        const button = event.submitter;
        pendingForms.set(form, button);
        form.setAttribute('aria-busy', 'true');
        if (button) {
            button.classList.add('is-submitting');
            button.setAttribute('aria-disabled', 'true');
        }
        // Do not disable named submit buttons: PHP needs their decision value.
    });
});
window.addEventListener('pageshow', () => {
    pendingForms.forEach((button, form) => {
        form.removeAttribute('aria-busy');
        button?.classList.remove('is-submitting');
        button?.removeAttribute('aria-disabled');
    });
    pendingForms.clear();
});
document.querySelector('[data-form-feedback]')?.focus();
