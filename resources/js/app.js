const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, reduceMotion ? 0 : ms));

/* ---------- Kopyalama ---------- */
async function copyText(text, button) {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const area = document.createElement('textarea');
        area.value = text;
        document.body.append(area);
        area.select();
        document.execCommand('copy');
        area.remove();
    }
    if (button) {
        const label = button.dataset.label ?? button.textContent;
        button.dataset.label = label;
        button.textContent = 'Kopyalandı';
        setTimeout(() => (button.textContent = label), 1600);
    }
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    const value = button.dataset.copy;
    const source = value.startsWith('#') ? document.querySelector(value) : null;
    copyText(source ? (source.value ?? source.textContent).trim() : value, button);
});

/* ---------- Anahtar alanı: yazarken büyük harf ---------- */
document.querySelectorAll('[data-key-input]').forEach((input) => {
    input.addEventListener('input', () => {
        const atEnd = input.selectionStart === input.value.length;
        input.value = input.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
        if (atEnd) input.setSelectionRange(input.value.length, input.value.length);
    });
});

/* ---------- Gönderimde düğmeyi kilitle (spinner CSS'te) ---------- */
document.querySelectorAll('form[data-busy]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const button = form.querySelector('[type="submit"]');
        if (!button) return;
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
    });
});

/* ---------- Onay isteyen işlemler ---------- */
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    }, { capture: true });
});

/* ---------- Seçince süz ---------- */
document.querySelectorAll('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form.submit()));

/* ---------- Aç/kapa alanlar (hesap formu) ---------- */
document.querySelectorAll('[data-toggle]').forEach((box) => {
    const target = document.querySelector(box.dataset.toggle);
    const sync = () => {
        target.toggleAttribute('data-off', !box.checked);
        target.querySelectorAll('input, select, textarea').forEach((el) => (el.disabled = !box.checked));
    };
    box.addEventListener('change', sync);
    sync();
});

/* ---------- Bilinen posta sağlayıcısında IMAP bilgisini doldur ---------- */
const accountForm = document.querySelector('[data-account-form]');
if (accountForm) {
    const presets = JSON.parse(accountForm.dataset.presets);
    const user = accountForm.querySelector('[name="mail_username"]');
    const host = accountForm.querySelector('[name="mail_host"]');
    const port = accountForm.querySelector('[name="mail_port"]');
    const enc = accountForm.querySelector('[name="mail_encryption"]');
    user?.addEventListener('change', () => {
        const preset = presets[user.value.split('@')[1]?.toLowerCase()];
        if (preset && !host.value) {
            host.value = preset.host;
            port.value = preset.port;
            enc.value = preset.encryption;
        }
    });
}

/* ---------- Renk seçici ile metin alanını eşle ---------- */
document.querySelectorAll('[data-color-sync]').forEach((el) => {
    el.addEventListener('input', () => {
        const other = document.querySelector(el.dataset.colorSync);
        if (/^#[0-9a-f]{6}$/i.test(el.value)) other.value = el.value.toLowerCase();
    });
});

/* ---------- İmza öğe: kod hücreleri ---------- */
const el = (tag, className, text) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
};

/** Kodu hücrelere yazar: karakterler önce döner, sonra soldan sağa sırayla yerine oturur. */
function fillCells(container, code) {
    const chars = [...code];
    const pool = /^\d+$/.test(code) ? '0123456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    const cells = chars.map(() => {
        const cell = el('span', 'cell is-roll');
        cell.setAttribute('aria-hidden', 'true');
        cell.append(el('span', '', ''));
        return cell;
    });
    const settle = (cell, i) => {
        cell.className = 'cell is-in';
        cell.firstChild.textContent = chars[i];
    };

    container.classList.remove('cells--wait', 'cells--expired');
    container.replaceChildren(...cells);
    container.setAttribute('role', 'img');
    container.setAttribute('aria-label', `Kod: ${chars.join(' ')}`);

    if (reduceMotion) {
        cells.forEach(settle);
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        const start = performance.now();
        let lastSpin = 0;
        const frame = (now) => {
            const settled = Math.min(cells.length, Math.max(0, Math.floor((now - start - 380) / 140) + 1));
            const spin = now - lastSpin > 45;
            if (spin) lastSpin = now;
            cells.forEach((cell, i) => {
                if (i < settled) {
                    if (cell.classList.contains('is-roll')) settle(cell, i);
                } else if (spin) {
                    cell.firstChild.textContent = pool[Math.floor(Math.random() * pool.length)];
                }
            });
            if (settled >= cells.length) resolve();
            else requestAnimationFrame(frame);
        };
        requestAnimationFrame(frame);
    });
}

/** Beklerken boş, parıldayan hücreler. */
function waitCells(container, count) {
    container.classList.add('cells--wait');
    container.replaceChildren(...Array.from({ length: count }, () => el('span', 'cell', '')));
}

const RING = 2 * Math.PI * 22;

function ringEl() {
    const ring = el('div', 'ring');
    ring.innerHTML = `<svg viewBox="0 0 52 52" aria-hidden="true"><circle class="ring__track" cx="26" cy="26" r="22"/><circle class="ring__bar" cx="26" cy="26" r="22" stroke-dasharray="${RING}" stroke-dashoffset="0"/></svg><span class="ring__num" aria-live="off"></span>`;
    return ring;
}

/* ---------- Müşteri: kod paneli ---------- */
const ago = (iso) => {
    if (!iso) return 'az önce';
    const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
    return minutes < 1 ? 'az önce' : `${minutes} dakika önce`;
};
const usesNote = (left) => (left === null || left === undefined ? '' : ` Kalan kod hakkın: ${left}.`);

class CodePanel {
    constructor(root) {
        this.panel = root.querySelector('.code');
        this.buttons = [...root.querySelectorAll('[data-get-code]')];
        this.timer = null;
        this.buttons.forEach((button) => button.addEventListener('click', () => this.load(button)));
    }

    async load(button) {
        this.busy(button, true);
        this.waiting(button.dataset.url.endsWith('/2fa') ? 6 : 4);
        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message ?? 'Kod alınamadı. Biraz sonra tekrar dene.');
            this.show(data);
            button.dataset.idleNow = 'Yeni kod al';
        } catch (error) {
            this.fail(error.message);
            button.dataset.idleNow = 'Tekrar dene';
        } finally {
            this.busy(button, false);
        }
    }

    busy(button, on) {
        this.buttons.forEach((b) => (b.disabled = on));
        button.setAttribute('aria-busy', on ? 'true' : 'false');
        if (!on) button.textContent = button.dataset.idleNow ?? button.dataset.idle;
    }

    waiting(count) {
        clearInterval(this.timer);
        const cells = el('div', 'cells');
        waitCells(cells, count);
        this.panel.replaceChildren(el('p', 'code__label', 'Kod getiriliyor'), cells);
        this.panel.hidden = false;
    }

    show(data) {
        clearInterval(this.timer);
        const totp = data.type === '2fa';

        if (data.kind === 'link') {
            const link = el('a', 'btn btn--primary', 'Bağlantıyı aç');
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            if (/^https:\/\//i.test(data.code)) link.href = data.code;
            const copy = el('button', 'btn btn--secondary btn--s', 'Bağlantıyı kopyala');
            copy.type = 'button';
            copy.dataset.copy = data.code;
            const row = el('div', 'code__link');
            row.append(link, copy);
            this.panel.replaceChildren(
                el('p', 'code__label', 'Platformun gönderdiği doğrulama bağlantısı'),
                row,
                el('p', 'code__note', `Mail ${ago(data.received_at)} geldi. Bağlantıyı hesabın açık olduğu cihazda aç.${usesNote(data.uses_left)}`),
            );
            return;
        }

        const cells = el('div', 'cells');
        fillCells(cells, data.code);
        const side = el('div', 'code__side');
        if (totp) side.append(ringEl());
        const copy = el('button', 'btn btn--secondary btn--s', 'Kopyala');
        copy.type = 'button';
        copy.dataset.copy = data.code;
        side.append(copy);

        const note = el('p', 'code__note', totp
            ? `Kod 30 saniyede bir yenilenir; süre dolunca yeni kod al.${usesNote(data.uses_left)}`
            : `Kod ${ago(data.received_at)} gelen mailden alındı.${usesNote(data.uses_left)}`);

        this.panel.replaceChildren(el('p', 'code__label', totp ? '2FA kodu' : 'Mail kodu'), cells, side, note);
        if (totp) this.countdown(data.expires_in, data.period, cells, note);
    }

    countdown(left, period, cells, note) {
        const ring = this.panel.querySelector('.ring');
        const bar = ring.querySelector('.ring__bar');
        const num = ring.querySelector('.ring__num');
        const end = Date.now() + left * 1000;

        const tick = () => {
            const remaining = Math.max(0, Math.ceil((end - Date.now()) / 1000));
            num.textContent = remaining;
            bar.style.strokeDashoffset = String(RING * (1 - remaining / period));
            ring.classList.toggle('is-low', remaining <= 5);
            if (remaining <= 0) {
                clearInterval(this.timer);
                cells.classList.add('cells--expired');
                note.textContent = 'Bu kodun süresi doldu. Yeni kod al.';
            }
        };
        tick();
        this.timer = setInterval(tick, 1000);
    }

    fail(message) {
        clearInterval(this.timer);
        this.panel.replaceChildren(el('p', 'code__error', message));
        this.panel.firstChild.setAttribute('role', 'alert');
        this.panel.hidden = false;
    }
}

document.querySelectorAll('[data-game]').forEach((root) => {
    new CodePanel(root);
    if (root.dataset.open === 'true') root.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
});

/* ---------- Şifreyi göster / gizle ---------- */
document.querySelectorAll('[data-pass-toggle]').forEach((button) => {
    const inputs = document.querySelectorAll(button.dataset.passToggle);
    button.addEventListener('click', () => {
        const show = button.getAttribute('aria-pressed') !== 'true';
        inputs.forEach((input) => (input.type = show ? 'text' : 'password'));
        button.setAttribute('aria-pressed', String(show));
        button.textContent = show ? 'Gizle' : 'Göster';
    });
});

/* ---------- Arka plan hareketini durdur (seçim cihazda hatırlanır) ---------- */
const stillButton = document.querySelector('[data-still]');
if (stillButton && !reduceMotion) {
    const store = { get: () => { try { return localStorage.getItem('still') === '1'; } catch { return false; } }, set: (on) => { try { localStorage.setItem('still', on ? '1' : '0'); } catch { /* özel pencere */ } } };
    const apply = (on) => {
        document.documentElement.toggleAttribute('data-still', on);
        stillButton.textContent = on ? 'Hareketi başlat' : 'Hareketi durdur';
        stillButton.setAttribute('aria-pressed', String(on));
    };
    stillButton.hidden = false;
    apply(store.get());
    stillButton.addEventListener('click', () => {
        const on = !document.documentElement.hasAttribute('data-still');
        store.set(on);
        apply(on);
    });
}

/* ---------- Cam: parlama imleci yumuşakça izler (aç/kapa yok, eğilme yok) ---------- */
if (!reduceMotion && window.matchMedia('(pointer: fine)').matches) {
    let frame = 0;
    let point = null;
    window.addEventListener('pointermove', (event) => {
        point = event;
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            document.querySelectorAll('.glass').forEach((glass) => {
                const box = glass.getBoundingClientRect();
                glass.style.setProperty('--lx', `${point.clientX - box.left}px`);
                glass.style.setProperty('--ly', `${point.clientY - box.top}px`);
            });
        });
    }, { passive: true });
}

/* ---------- Başlık: kelime kelime gelir ---------- */
document.querySelectorAll('.words').forEach((heading) => {
    const words = heading.textContent.trim().split(/\s+/);
    heading.setAttribute('aria-label', words.join(' '));
    heading.replaceChildren(...words.flatMap((word, i) => {
        const span = el('span', 'w', word);
        span.setAttribute('aria-hidden', 'true');
        span.style.setProperty('--i', i);
        return i ? [' ', span] : [span];
    }));
});

/* ---------- Kaydırınca beliren bölümler ---------- */
const reveals = document.querySelectorAll('.reveal');
if (reduceMotion || !('IntersectionObserver' in window)) {
    reveals.forEach((node) => node.classList.add('is-in'));
} else {
    const seen = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-in');
            seen.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    reveals.forEach((node) => seen.observe(node));
}

/* ---------- Ana sayfa ve giriş vitrini: denenebilen örnek ---------- */
document.querySelectorAll('[data-demo]').forEach((demo) => {
    const button = demo.querySelector('[data-demo-run]');
    const cells = demo.querySelector('[data-demo-cells]');
    const note = demo.querySelector('[data-demo-note]');
    const mail = demo.querySelector('[data-demo-mail]');
    const title = demo.querySelector('[data-demo-title]');
    const time = demo.querySelector('[data-demo-time]');
    const length = Number(cells.dataset.length || 4);
    let running = false;

    // Sıra: posta kutusu okunur, platformun maili düşer, kod hücrelere oturur.
    const run = async () => {
        if (running) return;
        running = true;
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
        waitCells(cells, length);
        mail.className = 'mail is-reading';
        title.textContent = 'Posta kutusu';
        note.textContent = 'Yeni mail bekleniyor…';
        time.textContent = '';
        await sleep(1200);
        mail.className = 'mail is-new';
        title.textContent = 'Netflix';
        note.textContent = 'Giriş kodun geldi';
        time.textContent = 'şimdi';
        await sleep(650);
        const code = Array.from({ length }, () => Math.floor(Math.random() * 10)).join('');
        await fillCells(cells, code);
        button.setAttribute('aria-busy', 'false');
        button.disabled = false;
        button.textContent = 'Tekrar dene';
        running = false;
    };

    button.addEventListener('click', run);

    // Giriş vitrininde bir kez kendiliğinden oynar; döngü yok.
    if (demo.hasAttribute('data-demo-autoplay')) setTimeout(run, reduceMotion ? 0 : 900);
});
