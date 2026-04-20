(function () {
    'use strict';

    if (typeof window.CB_DATA === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-cb-root]').forEach(initRoot);

    function initRoot(root) {
        var state = { date: null, start: null, end: null, label: '' };

        var stepSlots = root.querySelector('.cb-step--slots');
        var stepForm  = root.querySelector('.cb-step--form');
        var status    = root.querySelector('[data-cb-slots-status]');
        var slotList  = root.querySelector('[data-cb-slot-list]');
        var form      = root.querySelector('[data-cb-form]');
        var chosen    = root.querySelector('[data-cb-chosen]');
        var message   = root.querySelector('[data-cb-message]');
        var inputStart = root.querySelector('[data-cb-start]');
        var inputEnd   = root.querySelector('[data-cb-end]');

        root.querySelectorAll('[data-cb-date]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                root.querySelectorAll('[data-cb-date]').forEach(function (b) {
                    b.classList.remove('is-selected');
                });
                btn.classList.add('is-selected');
                state.date = btn.getAttribute('data-cb-date');
                showSlots(state.date);
            });
        });

        root.querySelector('[data-cb-back]').addEventListener('click', function () {
            stepForm.hidden = true;
            stepSlots.hidden = false;
            message.textContent = '';
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            submitBooking();
        });

        function showSlots(date) {
            stepSlots.hidden = false;
            stepForm.hidden  = true;
            slotList.innerHTML = '';
            status.textContent = CB_DATA.i18n.loading;

            var body = new URLSearchParams();
            body.append('action', 'cb_get_slots');
            body.append('nonce', CB_DATA.nonce);
            body.append('date', date);

            fetch(CB_DATA.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || !res.success) {
                        status.textContent = (res && res.data && res.data.message) || CB_DATA.i18n.genericErr;
                        return;
                    }
                    var slots = res.data.slots || [];
                    if (!slots.length) {
                        status.textContent = CB_DATA.i18n.noSlots;
                        return;
                    }
                    status.textContent = '';
                    slots.forEach(function (slot) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'cb-slot';
                        btn.textContent = slot.label;
                        btn.addEventListener('click', function () {
                            chooseSlot(slot);
                        });
                        slotList.appendChild(btn);
                    });
                })
                .catch(function () {
                    status.textContent = CB_DATA.i18n.genericErr;
                });
        }

        function chooseSlot(slot) {
            state.start = slot.start;
            state.end   = slot.end;
            state.label = slot.label;
            inputStart.value = slot.start;
            inputEnd.value   = slot.end;
            chosen.textContent = slot.label + '  (' + state.date + ')';
            stepSlots.hidden = true;
            stepForm.hidden  = false;
            message.textContent = '';
        }

        function submitBooking() {
            message.textContent = CB_DATA.i18n.booking;

            var body = new URLSearchParams();
            body.append('action', 'cb_book_slot');
            body.append('nonce', CB_DATA.nonce);
            body.append('start', state.start);
            body.append('end', state.end);
            body.append('name', form.elements.name.value);
            body.append('email', form.elements.email.value);
            body.append('phone', form.elements.phone.value);
            body.append('notes', form.elements.notes.value);

            fetch(CB_DATA.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (payload) {
                    var data = payload.data;
                    if (data && data.success) {
                        form.innerHTML = '<p class="cb-message cb-message--ok">' + escapeHtml(data.data.message) + '</p>';
                        return;
                    }
                    message.textContent = (data && data.data && data.data.message) || CB_DATA.i18n.genericErr;
                })
                .catch(function () {
                    message.textContent = CB_DATA.i18n.genericErr;
                });
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
            });
        }
    }
}());
