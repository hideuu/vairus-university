const sidebar = document.querySelector('.sidebar');
const toggle = document.querySelector('.menu-toggle');
const mobile = window.matchMedia('(max-width: 900px)');
const navigationBackdrop = document.querySelector('.navigation-backdrop');
document.body.classList.add('nav-ready');

function setMenu(open) {
    const wasInside = sidebar.contains(document.activeElement);
    sidebar.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    sidebar.inert = mobile.matches && !open;
    navigationBackdrop.hidden = !mobile.matches || !open;
    document.body.classList.toggle('nav-open', mobile.matches && open);
    if (mobile.matches && open) {
        (sidebar.querySelector('[aria-current="page"]') || sidebar.querySelector('a')).focus({ preventScroll: true });
    } else if (mobile.matches && wasInside) {
        toggle.focus({ preventScroll: true });
    }
}
function updateNavigation() {
    toggle.hidden = !mobile.matches;
    const more = sidebar.querySelector('.nav-more');
    if (more) more.open = mobile.matches;
    setMenu(false);
}
toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
navigationBackdrop.addEventListener('mousedown', (event) => event.preventDefault());
navigationBackdrop.addEventListener('click', () => { setMenu(false); toggle.focus({ preventScroll: true }); });
mobile.addEventListener('change', updateNavigation);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setMenu(false);
        toggle.focus();
    }
    if (event.key === 'Escape' && !mobile.matches) {
        const more = sidebar.querySelector('.nav-more[open]');
        if (more) { more.open = false; more.querySelector('summary').focus(); }
    }
});
document.addEventListener('click', (event) => {
    if (mobile.matches && !sidebar.contains(event.target) && !toggle.contains(event.target)) setMenu(false);
    if (!mobile.matches) {
        const more = sidebar.querySelector('.nav-more');
        if (more && !more.contains(event.target)) more.open = false;
    }
});
document.addEventListener('focusin', (event) => {
    if (mobile.matches && toggle.getAttribute('aria-expanded') === 'true'
        && !sidebar.contains(event.target) && !toggle.contains(event.target)) setMenu(false);
});
updateNavigation();

// Make table overflow discoverable without adding unnecessary tab stops.
document.querySelectorAll('.table-scroll').forEach((region, index) => {
    const hint = document.createElement('p');
    hint.id = `table-scroll-hint-${index}`;
    hint.className = 'table-scroll-hint';
    hint.textContent = 'Scroll horizontally to see all columns.';
    hint.hidden = true;
    region.before(hint);
    region.setAttribute('role', 'region');
    if (!region.hasAttribute('aria-label')) {
        const heading = region.closest('section')?.querySelector('h2')?.textContent || 'Data table';
        region.setAttribute('aria-label', heading.trim());
    }
    const updateOverflow = () => {
        const overflow = region.scrollWidth > region.clientWidth + 1;
        hint.hidden = !overflow;
        if (overflow) {
            region.tabIndex = 0;
            region.setAttribute('aria-describedby', hint.id);
        } else {
            region.removeAttribute('tabindex');
            region.removeAttribute('aria-describedby');
        }
    };
    const observer = new ResizeObserver(updateOverflow);
    observer.observe(region);
    const table = region.querySelector('table');
    if (table) observer.observe(table);
    updateOverflow();
});

const subjects = document.querySelectorAll('.subject-checkbox');
function updateSelection() {
    let count = 0;
    let units = 0;
    subjects.forEach((input) => {
        if (input.checked) {
            count += 1;
            units += Number(input.dataset.units) || 0;
        }
    });
    const countLabel = document.querySelector('#selected-count');
    const unitsLabel = document.querySelector('#selected-units');
    if (countLabel) countLabel.textContent = String(count);
    if (unitsLabel) unitsLabel.textContent = String(Math.round(units * 10) / 10);
}
subjects.forEach((input) => input.addEventListener('change', updateSelection));
updateSelection();
document.querySelectorAll('.print-button').forEach((button) => button.addEventListener('click', () => window.print()));
