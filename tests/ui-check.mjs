// Optional local Chrome DevTools capture; no browser packages required.
import { mkdir, writeFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const targets = await (await fetch('http://127.0.0.1:9333/json')).json();
const target = targets.find((entry) => entry.type === 'page');
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
let sequence = 0;
const pending = new Map();
const events = new Map();
const errors = [];
socket.onmessage = ({ data }) => {
    const message = JSON.parse(data);
    if (message.id) {
        const promise = pending.get(message.id);
        pending.delete(message.id);
        if (!promise) return;
        clearTimeout(promise.timer);
        if (message.error) promise.reject(new Error(message.error.message));
        else promise.resolve(message.result);
    } else {
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
        const listeners = events.get(message.method) || [];
        events.delete(message.method);
        listeners.forEach((callback) => callback(message.params));
    }
};
function send(method, params = {}) {
    const id = ++sequence;
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => { pending.delete(id); reject(new Error('Browser command timed out: ' + method)); }, 15000);
        pending.set(id, { resolve, reject, timer });
        socket.send(JSON.stringify({ id, method, params }));
    });
}
function once(method) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Browser event timed out: ' + method)), 15000);
        const listeners = events.get(method) || [];
        listeners.push((params) => { clearTimeout(timer); resolve(params); });
        events.set(method, listeners);
    });
}
async function evaluate(expression) {
    const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
    return result.result.value;
}
const base = 'http://localhost/vairusuniversity/public/';
async function navigate(path) {
    const loaded = once('Page.loadEventFired');
    await send('Page.navigate', { url: base + path });
    await loaded;
    await evaluate('document.fonts.ready.then(() => true)');
}
async function login(username, password) {
    await send('Network.clearBrowserCookies');
    await navigate('index.php');
    const loaded = once('Page.loadEventFired');
    await evaluate(`document.querySelector('#username').value=${JSON.stringify(username)};document.querySelector('#password').value=${JSON.stringify(password)};document.querySelector('#login-form').requestSubmit();true`);
    await loaded;
    await evaluate('document.fonts.ready.then(() => true)');
}
const out = '.impeccable/review';
await mkdir(out, { recursive: true });
async function viewport(width, height = 1000) {
    await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
}
async function capture(name) {
    const checks = await evaluate(`({
        title:document.title,
        width:innerWidth,
        documentWidth:document.documentElement.scrollWidth,
        images:[...document.images].every(i=>i.complete&&i.naturalWidth>0),
        fonts:[...document.fonts].every(f=>f.status==='loaded'),
        error:document.body.textContent.includes('This page is temporarily unavailable.'),
        nav:document.querySelector('.menu-toggle')?.getAttribute('aria-expanded')
    })`);
    assert.ok(checks.documentWidth <= checks.width, 'No page-wide overflow');
    assert.equal(checks.images, true, 'Images loaded');
    assert.equal(checks.fonts, true, 'Local fonts loaded');
    assert.equal(checks.error, false, 'Page rendered');
    const layout = await send('Page.getLayoutMetrics');
    const size = layout.cssContentSize;
    const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: size.width, height: size.height, scale: 1 } });
    await writeFile(`${out}/${name}.png`, Buffer.from(screenshot.data, 'base64'));
    console.log('PASS: ' + name + ' ' + JSON.stringify(checks));
}
try {
    await send('Page.enable'); await send('Runtime.enable'); await send('Network.enable');
    await send('Network.clearBrowserCookies');
    await navigate('index.php');
    await viewport(1440); await capture('login-desktop');
    await viewport(390,844); await capture('login-mobile');
    await login('admin','incorrect-audit-password');
    assert.equal(await evaluate('document.activeElement.id'), 'form-status', 'Login error receives focus');
    await capture('login-error-mobile');
    await viewport(1440);
    await login('admin', process.env.TEST_ADMIN_PASSWORD);
    await capture('admin-desktop');
    await navigate('admin/index.php?page=reports'); await capture('reports-desktop');
    await viewport(390,844); await capture('reports-mobile');
    await viewport(1440);
    await navigate('admin/report-print.php?report_type=enrollments');
    await send('Emulation.setEmulatedMedia',{media:'print'});
    assert.equal(await evaluate("getComputedStyle(document.querySelector('.report-print-actions')).display"),'none','Print report hides action controls');
    await capture('reports-print');
    await send('Emulation.setEmulatedMedia',{media:''});
    await navigate('admin/index.php?page=students'); await capture('students-desktop');
    await navigate('admin/index.php?page=subjects'); await capture('subjects-catalog-desktop');
    assert.equal(await evaluate("[...document.querySelectorAll('.table-scroll')].every(r=>r.scrollWidth<=r.clientWidth+1 ? !r.hasAttribute('tabindex') : r.tabIndex===0)"),true,'Only overflowing tables add tab stops');
    await navigate('admin/index.php?page=courses');
    assert.equal(await evaluate("!!document.querySelector('.cell-details')"),true,'Long course text can expand');
    await evaluate("document.querySelector('.cell-details').open=true");
    await capture('course-description-desktop');
    await navigate('admin/index.php?page=subjects&new=1'); await capture('subject-form-desktop');
    await navigate('admin/index.php?page=enrollments&status=pending');
    const reviewLink = await evaluate("document.querySelector('td a[href*=view]').getAttribute('href')");
    await navigate('admin/index.php' + reviewLink);
    const submission = await evaluate(`(() => {
        const form=document.querySelector('.decision-form');
        const button=form.querySelector('button[name="decision"]');
        const first=new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button});
        form.dispatchEvent(first);
        const result={busy:form.getAttribute('aria-busy'),value:new FormData(form,button).get('decision'),disabled:button.disabled};
        result.secondAllowed=form.dispatchEvent(new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button}));
        window.dispatchEvent(new PageTransitionEvent('pageshow',{persisted:true}));
        result.reset=!form.hasAttribute('aria-busy')&&!button.hasAttribute('aria-disabled');
        return result;
    })()`);
    assert.deepEqual(submission,{busy:'true',value:'approved',disabled:false,secondAllowed:false,reset:true},'Loading state preserves decision values, blocks duplicates, and resets on back navigation');
    await capture('enrollment-review-desktop');
    await viewport(390, 844);
    await navigate('admin/index.php'); await capture('admin-mobile');
    await evaluate("document.querySelector('.menu-toggle').click()");
    assert.equal(await evaluate("document.querySelector('.sidebar').inert"), false);
    assert.equal(await evaluate("document.activeElement.matches('.sidebar [aria-current]')"),true,'Opening mobile navigation moves focus');
    await capture('navigation-mobile');
    await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Escape',code:'Escape'});
    await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Escape',code:'Escape'});
    assert.equal(await evaluate("document.activeElement.matches('.menu-toggle') && document.querySelector('.sidebar').inert"),true,'Escape closes menu and restores focus');
    await navigate('admin/index.php?page=students&new=1'); await capture('student-form-mobile');
    assert.ok(await evaluate("parseFloat(getComputedStyle(document.querySelector('#field-fname')).fontSize)>=16"),'Mobile inputs avoid automatic zoom');
    await viewport(320,740); await capture('student-form-small-mobile');
    await viewport(768,1024); await navigate('admin/index.php'); await capture('admin-tablet');
    await viewport(390,844);
    await login(process.env.UI_STUDENT_USERNAME, process.env.UI_STUDENT_PASSWORD);
    await capture('student-mobile');
    await navigate('student/index.php?page=enroll'); await capture('enroll-mobile');
    assert.ok(await evaluate("!!document.querySelector('.subject-selection-grid .subject-card')"),'Subject choices render as accessible reference-led cards');
    assert.equal(await evaluate("[...document.querySelectorAll('.table-scroll')].every(r=>r.scrollWidth<=r.clientWidth+1 || (r.tabIndex===0 && !document.getElementById(r.getAttribute('aria-describedby')).hidden))"),true,'Overflowing tables show scroll guidance');
    assert.ok(await evaluate("document.querySelector('.subject-choice').getBoundingClientRect().height>=44"),'Subject selection has a touch target');
    await evaluate("document.querySelector('.subject-checkbox').click()");
    assert.equal(await evaluate("document.querySelector('#selected-count').textContent"), '1');
    assert.equal(await evaluate("document.querySelector('#selected-units').textContent"), '3');
    await viewport(320,740); await capture('enroll-small-mobile');
    await viewport(1440);
    await navigate('student/index.php'); await capture('student-desktop');
    await navigate('student/index.php?page=profile'); await capture('profile-desktop');
    await navigate('student/index.php?page=enroll'); await capture('enroll-desktop');
    await navigate('student/index.php?page=enrollments'); await capture('requests-desktop');
    const detailLink = await evaluate("document.querySelector('td a[href*=view]').getAttribute('href')");
    await navigate('student/index.php' + detailLink); await capture('request-detail-desktop');
    await viewport(390, 844); await capture('request-detail-mobile');
    assert.deepEqual(errors, [], 'No JavaScript runtime errors');
    console.log('All desktop/mobile browser checks passed.');
} finally { socket.close(); }
