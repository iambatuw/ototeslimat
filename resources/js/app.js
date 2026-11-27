const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
/** Hareket kapalı mı? Karar <head> içindeki küçük betikte verilir, altbilgideki düğme değiştirir. */
const still = () => document.documentElement.hasAttribute('data-still');
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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
        button.classList.add('is-done');
        setTimeout(() => {
            button.textContent = label;
            button.classList.remove('is-done');
        }, 1600);
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

    // Steam seçilince 2FA alanı maFile/shared_secret anlatımına geçer.
    const platform = accountForm.querySelector('[data-platform]');
    const secret = accountForm.querySelector('[data-ph-steam]');
    const syncPlatform = () => {
        accountForm.querySelectorAll('[data-for-platform]').forEach((node) => {
            const rule = node.dataset.forPlatform;
            const match = rule.startsWith('!') ? platform.value !== rule.slice(1) : platform.value === rule;
            node.hidden = !match;
        });
        if (secret) secret.placeholder = platform.value === 'steam' ? secret.dataset.phSteam : secret.dataset.ph;
    };
    platform?.addEventListener('change', syncPlatform);
    if (platform) syncPlatform();
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
        this.run = 0;
        const [interval, attempts] = (root.dataset.poll ?? '7,17').split(',').map(Number);
        this.pollEvery = (interval || 7) * 1000;
        this.pollMax = attempts || 17;
        this.buttons.forEach((button) => button.addEventListener('click', () => this.load(button)));
    }

    async request(url, poll) {
        const response = await fetch(poll ? `${url}?poll=1` : url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (response.status === 429) {
            return { ok: false, data: { message: 'Çok sık istek gönderildi, birkaç saniye bekle.', retry: true } };
        }
        return { ok: response.ok, data };
    }

    /**
     * Mail kodu henüz gelmediyse vazgeçmez: belirli aralıkla yeniden sorar,
     * kod düşünce hücrelere yazar. Müşteri "Beklemeyi bırak" ile durdurabilir.
     */
    async load(button) {
        const run = ++this.run;
        const length = Number(button.dataset.length) || 6;
        this.busy(button, true);
        this.waiting(length);
        try {
            for (let attempt = 0; attempt <= this.pollMax; attempt++) {
                const { ok, data } = await this.request(button.dataset.url, attempt > 0);
                if (run !== this.run) return; // müşteri durdurdu ya da başka kod istedi
                if (ok) {
                    this.show(data);
                    button.dataset.idleNow = 'Yeni kod al';
                    return;
                }
                if (!data.retry || attempt === this.pollMax) {
                    const error = new Error(data.message ?? 'Kod alınamadı. Biraz sonra tekrar dene.');
                    error.data = data;
                    throw error;
                }
                this.pending(length, attempt + 1, button);
                await sleep(this.pollEvery);
                if (run !== this.run) return;
            }
        } catch (error) {
            if (run !== this.run) return;
            this.fail(error.message, error.data);
            button.dataset.idleNow = 'Tekrar dene';
        } finally {
            if (run === this.run) this.busy(button, false);
        }
    }

    pending(length, attempt, button) {
        const cells = el('div', 'cells');
        waitCells(cells, length);
        const left = Math.max(0, Math.round(((this.pollMax - attempt + 1) * this.pollEvery) / 1000));
        const stop = el('button', 'btn btn--ghost btn--s', 'Beklemeyi bırak');
        stop.type = 'button';
        stop.addEventListener('click', () => {
            this.run++;
            this.busy(button, false);
            this.fail('Bekleme durduruldu. Kodu platformda istedikten sonra yeniden dene.');
            button.dataset.idleNow = 'Tekrar dene';
            button.textContent = 'Tekrar dene';
        });
        const note = el('p', 'code__note', `Henüz kod maili yok. Platformda giriş yapıp kodu iste; gelince burada kendiliğinden görünür. En fazla ${left} saniye daha bakılacak.`);
        note.setAttribute('aria-live', 'polite');
        this.panel.replaceChildren(el('p', 'code__label', 'Kod bekleniyor'), cells, stop, note);
        this.panel.hidden = false;
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

        this.panel.replaceChildren(el('p', 'code__label', totp ? (data.steam ? 'Steam Guard kodu' : '2FA kodu') : 'Mail kodu'), cells, side, note);
        if (totp) this.countdown(data.expires_in, data.period, cells, note);
        // Kod ekranın altında kalmasın: görünmüyorsa en yakın yere kaydır.
        requestAnimationFrame(() => this.panel.scrollIntoView({ block: 'nearest', behavior: still() ? 'auto' : 'smooth' }));
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

    fail(message, data = {}) {
        clearInterval(this.timer);
        this.panel.replaceChildren(el('p', 'code__error', message));
        this.panel.firstChild.setAttribute('role', 'alert');
        if (data.request_pending) {
            this.panel.append(el('p', 'code__note', 'Ek hak isteğin satıcında bekliyor. Onaylayınca buradan yeniden kod alabilirsin.'));
        } else if (data.request_url) {
            this.panel.append(this.askButton(data.request_url));
        }
        this.panel.hidden = false;
    }

    /** Hakkı dolan müşteri satıcıdan ek hak ister; sonuç aynı yerde yazılır. */
    askButton(url) {
        const ask = el('button', 'btn btn--secondary btn--s code__ask', 'Satıcıdan hak iste');
        ask.type = 'button';
        ask.addEventListener('click', async () => {
            ask.disabled = true;
            ask.textContent = 'Gönderiliyor';
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const reply = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(reply.message ?? 'İstek gönderilemedi. Biraz sonra tekrar dene.');
                const note = el('p', 'code__note', reply.message);
                note.setAttribute('role', 'status');
                ask.replaceWith(note);
            } catch (error) {
                ask.disabled = false;
                ask.textContent = 'Satıcıdan hak iste';
                window.alert(error.message);
            }
        });
        return ask;
    }
}

/* ---------- Müşteri: Kodlarım ----------
 * Raftaki "Kod al" sayfayı yenilemeden o hesabı üstteki büyük karta getirir; hesabın tek
 * kod türü varsa kodu da hemen ister. JS yoksa bağlantı ?hesap= ile aynı şeyi sunucuda yapar. */
const heroes = document.querySelector('[data-heroes]');
if (heroes) {
    const heroLabel = heroes.querySelector('[data-hero-label]');
    const show = (id, { fetchCode = false } = {}) => {
        const next = heroes.querySelector(`[data-hero="${id}"]`);
        if (!next) return false;
        const current = heroes.querySelector('[data-hero]:not([hidden])');
        if (current !== next) {
            if (current) current.hidden = true;
            next.hidden = false;
            next.classList.remove('is-swapping');
            void next.offsetWidth; // animasyonu yeniden başlat
            next.classList.add('is-swapping');
            heroLabel.textContent = 'Seçtiğin hesap';
            document.querySelectorAll('[data-row]').forEach((row) => {
                const active = row.dataset.row === id;
                row.classList.toggle('is-active', active);
                const last = row.querySelector('.arow__last');
                if (!last) return;
                if (active) last.replaceChildren(el('span', 'arow__here', 'Yukarıda açık'));
                else last.textContent = last.dataset.last;
            });
            history.replaceState(null, '', `?hesap=${encodeURIComponent(id)}`);
        }
        next.scrollIntoView({ block: 'start', behavior: still() ? 'auto' : 'smooth' });
        const buttons = next.querySelectorAll('[data-get-code]');
        if (fetchCode && buttons.length === 1) buttons[0].click();
        else buttons[0]?.focus({ preventScroll: true });
        return true;
    };

    document.addEventListener('click', (event) => {
        const go = event.target.closest('[data-show]');
        if (!go) return;
        if (show(go.dataset.show, { fetchCode: true })) event.preventDefault();
    });
}

/* Arama ve platform hapları: rafları süzer; boşalan raf gizlenir. */
const shelves = document.querySelector('[data-shelves]');
if (shelves) {
    const input = document.querySelector('[data-search-input]');
    const chips = [...document.querySelectorAll('[data-filter]')];
    const none = shelves.querySelector('[data-none]');
    let platform = '';
    const fold = (text) => text.toLocaleLowerCase('tr').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ı/g, 'i');
    const apply = () => {
        const query = fold(input?.value.trim() ?? '');
        let shown = 0;
        shelves.querySelectorAll('[data-shelf]').forEach((shelf) => {
            let visible = 0;
            shelf.querySelectorAll('[data-row]').forEach((row) => {
                const match = (!platform || row.dataset.platform === platform) && (!query || fold(row.dataset.search).includes(query));
                row.hidden = !match;
                if (match) visible++;
            });
            shelf.hidden = visible === 0;
            shown += visible;
        });
        none.hidden = shown > 0;
    };
    input?.addEventListener('input', apply);
    chips.forEach((chip) => chip.addEventListener('click', () => {
        platform = chip.dataset.filter;
        chips.forEach((c) => {
            c.classList.toggle('is-on', c === chip);
            c.setAttribute('aria-pressed', String(c === chip));
        });
        apply();
    }));
}

/* Hesaba kendi adını ver: sayfa yenilenmeden kaydedilir, adın göründüğü her yer güncellenir. */
document.querySelectorAll('[data-label-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message ?? 'Ad kaydedilemedi.');
            // Ad değişince satır alt yazısı ve aranan metin de değişir; en sağlam yol sayfayı tazelemek.
            window.location.reload();
        } catch (error) {
            button.disabled = false;
            window.alert(error.message);
        }
    });
});

document.querySelectorAll('[data-game]').forEach((root) => {
    new CodePanel(root);
    if (root.dataset.open === 'true') root.scrollIntoView({ block: 'center', behavior: still() ? 'auto' : 'smooth' });
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

/* ---------- Kayıt: adım adım ----------
 * JS yoksa form düz çalışır. Varsa her ekranda tek soru gösterilir, Enter sonraki
 * soruya geçer, ilerleme hücreleri dolar, sağdaki özet girilenleri toplar. */
const signup = document.querySelector('[data-signup]');
if (signup) {
    const steps = [...signup.querySelectorAll('[data-step]')];
    const cells = [...signup.querySelectorAll('[data-cell]')];
    const where = signup.querySelector('[data-signup-where]');
    const back = signup.querySelector('[data-back]');
    const backLabel = signup.querySelector('[data-back-label]');
    const submit = signup.querySelector('[type="submit"]');
    const field = (name) => signup.querySelector(`[name="${name}"]`);
    const min = Number(signup.querySelector('[data-rules]')?.dataset.min || 8);
    const greet = document.querySelector('[data-signup-greet]');
    const lead = document.querySelector('[data-signup-lead]');
    const summary = (key) => document.querySelector(`[data-summary="${key}"]`);
    let current = Math.min(steps.length - 1, Number(signup.dataset.start) || 0);

    const checks = [
        () => (field('name').value.trim() ? '' : 'Adını yaz.'),
        () => {
            const email = field('email');
            if (!email.value.trim()) return 'E-posta adresini yaz.';
            return email.checkValidity() ? '' : 'Bu bir e-posta adresine benzemiyor. Örnek: ad@ornek.com';
        },
        () => {
            const value = field('password').value;
            return value.length >= min && /\p{L}/u.test(value) && /\d/.test(value)
                ? '' : `Şifre en az ${min} karakter olmalı, bir harf ve bir rakam içermeli.`;
        },
        () => (field('password_confirmation').value === field('password').value && field('password').value
            ? '' : 'Şifreler aynı değil. İkinci şifreyi yeniden yaz.'),
    ];

    const setError = (i, message) => {
        const step = steps[i];
        const error = step.querySelector('[data-error]');
        const input = step.querySelector('.ask__input');
        error.textContent = message;
        error.toggleAttribute('role', false);
        if (message) error.setAttribute('role', 'alert');
        input.toggleAttribute('aria-invalid', Boolean(message));
        if (message) input.setAttribute('aria-invalid', 'true');
    };

    const firstName = () => field('name').value.trim().split(/\s+/)[0] || '';
    const syncSide = () => {
        const name = firstName();
        summary('name').textContent = field('name').value.trim() || '—';
        summary('email').textContent = field('email').value.trim() || '—';
        const length = field('password').value.length;
        summary('password').textContent = length ? `${'•'.repeat(Math.min(length, 12))} (${length} karakter)` : '—';
        const lines = [
            ['Bir dakikada hesabın hazır.', 'Dört kısa soru. Sonra sana teslim edilen anahtarı ekleyip kodunu alırsın.'],
            [name ? `Merhaba ${name}.` : 'Merhaba.', 'Sırada şifre var; ardından hesabın hazır.'],
            [name ? `${name}, az kaldı.` : 'Az kaldı.', 'Başka bir yerde kullanmadığın bir şifre seç.'],
            [name ? `${name}, neredeyse bitti.` : 'Neredeyse bitti.', `${field('email').value.trim() || 'Bu adres'} ile hesabın açılıyor.`],
        ];
        greet.textContent = lines[current][0];
        lead.textContent = lines[current][1];
    };

    const show = (i, { backwards = false, focus = true } = {}) => {
        current = i;
        steps.forEach((step, n) => {
            step.classList.remove('is-initial');
            step.classList.toggle('is-current', n === i);
            step.classList.toggle('is-back', n === i && backwards);
        });
        cells.forEach((cell, n) => {
            cell.classList.toggle('is-done', n < i);
            cell.classList.toggle('is-now', n === i);
        });
        where.textContent = `${i + 1} / ${steps.length} · ${steps[i].dataset.label}`;
        back.hidden = i === 0;
        if (i > 0) backLabel.textContent = `Geri: ${steps[i - 1].dataset.label}`;
        syncSide();
        if (focus) steps[i].querySelector('.ask__input').focus({ preventScroll: true });
    };

    const advance = () => {
        const message = checks[current]();
        setError(current, message);
        if (message) {
            steps[current].querySelector('.ask__input').focus();
            return;
        }
        if (current < steps.length - 1) show(current + 1);
        else signup.requestSubmit(submit);
    };

    signup.classList.add('is-stepped');
    signup.querySelector('[data-signup-progress]').hidden = false;
    show(current, { focus: true });
    steps[current].classList.add("is-initial"); // ilk soru sayfa açılınca yükselmez; yalnızca adım değişince

    signup.addEventListener('click', (event) => {
        if (event.target.closest('[data-next]')) advance();
        if (event.target.closest('[data-back]') && current > 0) show(current - 1, { backwards: true });
    });

    signup.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !event.target.matches('.ask__input')) return;
        event.preventDefault();
        advance();
    });

    signup.addEventListener('input', (event) => {
        const step = event.target.closest('[data-step]');
        if (step && step.querySelector('[data-error]').textContent) setError(Number(step.dataset.step), '');
        syncSide();
    });

    signup.addEventListener('submit', (event) => {
        const failing = checks.findIndex((check) => check());
        if (failing !== -1) {
            event.preventDefault();
            show(failing, { backwards: failing < current });
            setError(failing, checks[failing]());
            return;
        }
        submit.setAttribute('aria-busy', 'true');
        submit.disabled = true;
    });
}

/* ---------- Kayıt: şifre koşulları yazarken işaretlenir ---------- */
document.querySelectorAll('[data-rules]').forEach((list) => {
    const input = document.querySelector(list.dataset.rules);
    const min = Number(list.dataset.min || 8);
    const checks = {
        length: (value) => value.length >= min,
        letter: (value) => /\p{L}/u.test(value),
        digit: (value) => /\d/.test(value),
    };
    const sync = () => list.querySelectorAll('[data-rule]').forEach((item) => {
        const ok = checks[item.dataset.rule](input.value);
        item.classList.toggle('is-ok', ok);
        item.setAttribute('aria-label', `${item.textContent.trim()}: ${ok ? 'tamam' : 'eksik'}`);
    });
    input.addEventListener('input', sync);
    sync();
});

document.querySelectorAll('[data-match]').forEach((note) => {
    const [first, second] = document.querySelectorAll(note.dataset.match);
    const sync = () => {
        const state = !second.value ? '' : first.value === second.value ? 'ok' : 'bad';
        note.className = `match${state ? ` is-${state}` : ''}`;
        note.textContent = state === 'ok' ? 'Şifreler eşleşiyor.' : state === 'bad' ? 'Şifreler henüz eşleşmiyor.' : '';
    };
    first.addEventListener('input', sync);
    second.addEventListener('input', sync);
});

/* ---------- Rakamlar sıfırdan sayarak gelir ---------- */
document.querySelectorAll('[data-count]').forEach((node) => {
    const target = Number(node.dataset.count);
    if (!target || still()) return;
    const format = new Intl.NumberFormat('tr-TR');
    const start = performance.now();
    const duration = 900;
    const frame = (now) => {
        const t = Math.min(1, (now - start) / duration);
        node.textContent = format.format(Math.round(target * (1 - (1 - t) ** 3)));
        if (t < 1) requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
});

/* ---------- Adet seçici ---------- */
document.querySelectorAll('[data-stepper]').forEach((box) => {
    const input = box.querySelector('input');
    box.querySelectorAll('[data-step]').forEach((button) => button.addEventListener('click', () => {
        const min = Number(input.min || 1);
        const max = Number(input.max || Infinity);
        input.value = Math.min(max, Math.max(min, (Number(input.value) || min) + Number(button.dataset.step)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }));
});

/* ---------- Tema rengi: kaydetmeden önce canlı örnek ---------- */
const themePreview = document.querySelector('[data-theme-preview]');
if (themePreview) {
    document.querySelectorAll('[data-color-sync]').forEach((input) => input.addEventListener('input', () => {
        if (/^#[0-9a-f]{6}$/i.test(input.value)) themePreview.style.setProperty('--color-accent', input.value);
    }));
}

/* ---------- Arka plan hareketini durdur (seçim cihazda hatırlanır) ---------- */
const motionButtons = document.querySelectorAll('[data-motion-toggle]');
if (motionButtons.length) {
    const remember = (on) => { try { localStorage.setItem('still', on ? '1' : '0'); } catch { /* özel pencere */ } };
    const apply = (on) => {
        document.documentElement.toggleAttribute('data-still', on);
        motionButtons.forEach((button) => {
            const label = on ? 'Hareketi başlat' : 'Hareketi durdur';
            button.setAttribute('aria-pressed', String(on));
            button.setAttribute('aria-label', label);
            button.title = label;
        });
    };
    apply(still());
    motionButtons.forEach((button) => button.addEventListener('click', () => {
        const on = !still();
        remember(on);
        apply(on);
    }));
}

/* ---------- Cam: parlama imleci yumuşakça izler (aç/kapa yok, eğilme yok) ---------- */
if (window.matchMedia('(pointer: fine)').matches) {
    let frame = 0;
    let point = null;
    window.addEventListener('pointermove', (event) => {
        point = event;
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            // Yalnızca imlecin üstünde durduğu yüzey güncellenir; sayfadaki bütün camlar değil.
            const surface = point.target instanceof Element ? point.target.closest('.glass, .spot') : null;
            if (!surface) return;
            const box = surface.getBoundingClientRect();
            surface.style.setProperty('--lx', `${point.clientX - box.left}px`);
            surface.style.setProperty('--ly', `${point.clientY - box.top}px`);
        });
    }, { passive: true });
}

/* ---------- Kaydırınca beliren bölümler ---------- */
const reveals = document.querySelectorAll('.reveal');
if (!('IntersectionObserver' in window)) {
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
    if (demo.hasAttribute('data-demo-autoplay')) setTimeout(run, 1600);
});

/* ---------- Oyun kataloğu (/oyunlar) ----------
 * Arama ve platform hapları kartları süzer; karta tıklayınca oyun penceresi açılır. */
const gameGrid = document.querySelector('[data-games]');
if (gameGrid) {
    const fold = (text) => text.toLocaleLowerCase('tr').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ı/g, 'i');
    const items = [...gameGrid.children].map((li) => ({ li, name: fold(li.dataset.name), platform: li.dataset.platform }));
    const search = document.querySelector('[data-games-search]');
    const chips = [...document.querySelectorAll('[data-games-filter]')];
    const count = document.querySelector('[data-games-count]');
    const none = document.querySelector('[data-games-none]');
    let platform = '';

    const apply = () => {
        const words = fold(search?.value ?? '').split(/\s+/).filter(Boolean);
        let shown = 0;
        items.forEach((item) => {
            const match = (!platform || item.platform === platform) && words.every((word) => item.name.includes(word));
            item.li.hidden = !match;
            if (match) shown++;
        });
        if (count) count.textContent = shown;
        if (none) none.hidden = shown > 0;
    };

    search?.addEventListener('input', apply);
    chips.forEach((chip) => chip.addEventListener('click', () => {
        platform = chip.dataset.gamesFilter;
        chips.forEach((other) => {
            other.classList.toggle('is-on', other === chip);
            other.setAttribute('aria-pressed', String(other === chip));
        });
        apply();
    }));

    const dialog = document.querySelector('[data-game-dialog]');
    gameGrid.addEventListener('click', (event) => {
        const card = event.target.closest('[data-game-open]');
        if (!card || !dialog) return;
        const cover = dialog.querySelector('[data-dialog-cover]');
        cover.dataset.p = card.closest('[data-p]').dataset.p;
        cover.replaceChildren();
        if (card.dataset.cover) {
            const img = el('img');
            img.src = card.dataset.cover;
            img.alt = '';
            img.referrerPolicy = 'no-referrer';
            cover.append(img);
        } else {
            cover.append(el('span', 'gcard__blank', card.dataset.platformLabel));
        }
        dialog.querySelector('[data-dialog-platform]').textContent = card.dataset.platformLabel;
        dialog.querySelector('[data-dialog-title]').textContent = card.dataset.title;
        dialog.querySelector('[data-dialog-note]').textContent = card.dataset.note ?? '';
        dialog.showModal();
    });
    // Pencerenin dışına tıklayınca kapanır.
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
}
