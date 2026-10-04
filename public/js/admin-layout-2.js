    // ── SGEToast — Sistema global de notificaciones ──────────────────────────
    window.SGEToast = {
        _icons: {
            success: 'bi-check-circle-fill',
            danger:  'bi-exclamation-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info:    'bi-info-circle-fill',
        },
        _bg:   { success:'#dcfce7', danger:'#fee2e2', warning:'#fef3c7', info:'#dbeafe' },
        _text: { success:'#15803d', danger:'#991b1b', warning:'#92400e', info:'#1d4ed8' },

        show(message, type = 'success', duration = 4500) {
            const c   = document.getElementById('sge-toast-container');
            if (!c) return;
            const id  = 'sgt-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            Object.assign(div.style, {
                background: this._bg[type] || this._bg.info,
                color:      this._text[type] || this._text.info,
                borderRadius: '10px',
                padding: '.7rem 1rem',
                display: 'flex', alignItems: 'flex-start', gap: '.7rem',
                boxShadow: '0 4px 20px rgba(0,0,0,.13)',
                fontSize: '.84rem', fontWeight: '600',
                animation: 'toastEnter .3s cubic-bezier(.34,1.56,.64,1) both',
                maxWidth: '340px', wordBreak: 'break-word',
                border: '1px solid ' + (this._text[type] || this._text.info) + '33',
            });
            div.innerHTML = `
                <i class="bi ${this._icons[type] || this._icons.info}" style="font-size:1rem;flex-shrink:0;margin-top:.1rem;"></i>
                <span style="flex:1;line-height:1.4;">${message}</span>
                <button onclick="SGEToast._remove('${id}')" title="Cerrar"
                        style="background:none;border:none;color:inherit;font-size:.95rem;cursor:pointer;opacity:.65;padding:0;line-height:1;flex-shrink:0;margin-top:.05rem;">
                    <i class="bi bi-x-lg"></i>
                </button>`;
            c.appendChild(div);
            if (duration > 0) setTimeout(() => this._remove(id), duration);
        },

        _remove(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.style.animation = 'toastExit .22s ease-in both';
            setTimeout(() => el?.remove(), 230);
        },

        success(msg, d) { this.show(msg, 'success', d); },
        error(msg, d)   { this.show(msg, 'danger',  d); },
        warning(msg, d) { this.show(msg, 'warning', d); },
        info(msg, d)    { this.show(msg, 'info',    d); },
    };
    