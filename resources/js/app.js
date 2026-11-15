const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

/* ---------- Gönderimde düğmeyi kilitle ---------- */
document.querySelectorAll('form[data-busy]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const button = form.querySelector('[type="submit"]');
        if (!button) return;
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
        if (button.dataset.busyText) button.textContent = button.dataset.busyText;
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

/* ---------- Kod paneli ---------- */
const esc = (text) => {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
};
const ago = (iso) => {
    if (!iso) return 'az önce';
    const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
    return minutes < 1 ? 'az önce' : `${minutes} dakika önce`;
};
const usesNote = (left) => (left === null || left === undefined ? '' : ` Kalan kod hakkın: ${left}.`);

class CodePanel {
    constructor(game) {
        this.panel = game.querySelector('.code');
        this.buttons = [...game.querySelectorAll('[data-get-code]')];
        this.timer = null;
        this.buttons.forEach((button) => button.addEventListener('click', () => this.load(button)));
    }

    async load(button) {
        this.busy(button, true);
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
        button.textContent = on ? 'Kod alınıyor' : (button.dataset.idleNow ?? button.dataset.idle);
    }

    show(data) {
        clearInterval(this.timer);
        const totp = data.type === '2fa';
        const label = totp ? '2FA kodu' : 'Mail kodu';

        if (data.kind === 'link') {
            this.panel.innerHTML = `
                <p class="code__label">Platformun gönderdiği doğrulama bağlantısı</p>
                <div class="code__link">
                    <a class="btn btn--primary" target="_blank" rel="noopener noreferrer" data-link>Bağlantıyı aç</a>
                    <button type="button" class="btn btn--secondary btn--s" data-copy="${esc(data.code)}">Bağlantıyı kopyala</button>
                </div>
                <p class="code__note">Mail ${ago(data.received_at)} geldi. Bağlantıyı hesabın açık olduğu cihazda aç.${usesNote(data.uses_left)}</p>`;
            const link = this.panel.querySelector('[data-link]');
            if (/^https:\/\//i.test(data.code)) link.href = data.code;
        } else {
            this.panel.innerHTML = `
                <p class="code__label">${label}</p>
                <p class="code__value" aria-live="polite">${esc(data.code)}</p>
                <div class="code__side">
                    ${totp ? '<p class="code__clock"><span data-left>--</span><small>sn</small></p>' : ''}
                    <button type="button" class="btn btn--secondary btn--s" data-copy="${esc(data.code)}">Kodu kopyala</button>
                </div>
                ${totp ? '<div class="ramp" aria-hidden="true"><div class="ramp__heat"></div><div class="ramp__mark"></div></div>' : ''}
                <p class="code__note">${totp
                    ? 'Kod 30 saniyede bir değişir. Süre dolarsa yeni kod al; aynı kodu tekrar görmek hak düşürmez.'
                    : `Kod ${ago(data.received_at)} gelen mailden alındı.`}${usesNote(data.uses_left)}</p>`;
        }

        this.panel.hidden = false;
        if (totp) this.countdown(data.expires_in, data.period);
    }

    countdown(left, period) {
        const label = this.panel.querySelector('[data-left]');
        const heat = this.panel.querySelector('.ramp__heat');
        const mark = this.panel.querySelector('.ramp__mark');
        const track = this.panel.querySelector('.ramp');
        const end = Date.now() + left * 1000;
        if (reduceMotion) heat.style.transition = mark.style.transition = 'none';

        const tick = () => {
            const remaining = Math.max(0, Math.ceil((end - Date.now()) / 1000));
            const used = 1 - remaining / period;
            label.textContent = remaining;
            heat.style.transform = `scaleX(${used})`;
            mark.style.transform = `translateX(${used * track.clientWidth}px)`;
            if (remaining <= 0) {
                clearInterval(this.timer);
                this.panel.querySelector('.code__value')?.classList.add('muted');
                this.panel.querySelector('.code__note').textContent = 'Bu kodun süresi doldu. Yeni kod al.';
            }
        };
        tick();
        this.timer = setInterval(tick, 1000);
    }

    fail(message) {
        clearInterval(this.timer);
        this.panel.innerHTML = `<p class="code__error" role="alert">${esc(message)}</p>`;
        this.panel.hidden = false;
    }
}

document.querySelectorAll('[data-game]').forEach((game) => {
    new CodePanel(game);
    if (game.dataset.open === 'true') game.scrollIntoView({ block: 'center' });
});
