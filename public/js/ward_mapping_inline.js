/**
 * Inline API mapping editor on admin/wards pairing list.
 */
(function () {
    const cfg = window.WARD_MAP_INLINE;
    if (!cfg) {
        return;
    }

    const namesByCode = cfg.namesByCode || {};
    const usedNameToWard = cfg.usedNameToWard || {};
    let openRow = null;

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function parseSelected(row) {
        try {
            return JSON.parse(row.dataset.selectedNames || '[]');
        } catch (e) {
            return [];
        }
    }

    function codeOptionLabel(code, names) {
        const groups = [];
        names.forEach(function (row) {
            const g = (row.ward_name_ward || '').trim();
            if (g && !groups.includes(g)) {
                groups.push(g);
            }
        });

        const nameList = names.map(function (n) { return n.ward_name; }).filter(Boolean);
        let label = 'รหัส ' + code;

        if (groups.length) {
            label += ' — ' + groups.join(', ');
        }

        if (nameList.length) {
            const preview = nameList.slice(0, 3).join(', ');
            const suffix = nameList.length > 3 ? '…' : '';
            if (!groups.length) {
                label += ' — ' + preview + suffix;
            } else if (nameList.length === 1) {
                label += ' · ' + preview;
            }
        }

        label += ' (' + names.length + ' ชื่อ)';
        return label;
    }

    function codeOptionsHtml(selectedCode) {
        let html = '<option value="">— เลือกรหัส API —</option>';
        Object.keys(namesByCode).sort(function (a, b) {
            const na = parseInt(a, 10);
            const nb = parseInt(b, 10);
            if (!isNaN(na) && !isNaN(nb) && na !== nb) {
                return na - nb;
            }
            return a.localeCompare(b);
        }).forEach(function (code) {
            const names = namesByCode[code] || [];
            const label = codeOptionLabel(code, names);
            const sel = code === selectedCode ? ' selected' : '';
            html += '<option value="' + escapeHtml(code) + '"' + sel + '>' + escapeHtml(label) + '</option>';
        });
        return html;
    }

    function updateCodeHint(editor, code) {
        const hint = editor?.querySelector('.ward-map-code-hint');
        if (!hint) {
            return;
        }
        const names = namesByCode[code] || [];
        if (!code || names.length === 0) {
            hint.classList.add('d-none');
            hint.textContent = '';
            return;
        }
        const groups = [];
        names.forEach(function (row) {
            const g = (row.ward_name_ward || '').trim();
            if (g && !groups.includes(g)) {
                groups.push(g);
            }
        });
        const allNames = names.map(function (n) { return n.ward_name; }).join(', ');
        let text = 'ชื่อในรหัสนี้: ' + allNames;
        if (groups.length) {
            text = 'กลุ่ม: ' + groups.join(', ') + ' — ' + text;
        }
        hint.textContent = text;
        hint.classList.remove('d-none');
    }

    function isNameDisabled(name, wardId, currentSelected) {
        const owner = usedNameToWard[name];
        if (owner === undefined) {
            return false;
        }
        if (String(owner) === String(wardId)) {
            return false;
        }
        if (currentSelected.includes(name)) {
            return false;
        }
        return true;
    }

    function syncNameSelectionUI(wrap) {
        if (!wrap) {
            return;
        }
        const list = wrap.querySelector('.ward-map-names-list');
        const countEl = wrap.querySelector('.ward-map-names-count');
        const all = list?.querySelectorAll('input[type="checkbox"]:not(:disabled)') || [];
        const checked = list?.querySelectorAll('input[type="checkbox"]:not(:disabled):checked') || [];
        const total = all.length;
        const n = checked.length;
        if (countEl) {
            countEl.textContent = 'เลือก ' + n + ' / ' + total;
            countEl.classList.toggle('has-selection', n > 0);
        }
    }

    function filterNameList(wrap, query) {
        const list = wrap?.querySelector('.ward-map-names-list');
        const empty = wrap?.querySelector('.ward-map-names-empty');
        if (!list) {
            return;
        }
        const q = (query || '').trim().toLowerCase();
        let visible = 0;
        list.querySelectorAll('.ward-map-name-option').forEach(function (label) {
            const hay = (label.dataset.search || '').toLowerCase();
            const show = !q || hay.includes(q);
            label.classList.toggle('is-filter-hidden', !show);
            if (show) {
                visible++;
            }
        });
        empty?.classList.toggle('d-none', visible > 0 || !q);
    }

    function renderNameCheckboxes(panel, code, wardId, selected) {
        panel.innerHTML = '';
        if (!code || !namesByCode[code]) {
            panel.innerHTML = '<p class="text-muted small mb-0">เลือกรหัส API ก่อน</p>';
            return;
        }

        const rows = namesByCode[code];
        const wrap = document.createElement('div');
        wrap.className = 'ward-map-names-wrap';

        const toolbar = document.createElement('div');
        toolbar.className = 'ward-map-names-toolbar';
        toolbar.innerHTML =
            '<input type="search" class="ward-map-names-filter" placeholder="ค้นหาในชื่อนี้…" autocomplete="off" aria-label="ค้นหาชื่อ API">' +
            '<span class="ward-map-names-count">เลือก 0 / 0</span>' +
            '<div class="ward-map-names-tools">' +
            '<button type="button" class="btn btn-outline-primary btn-sm ward-map-pick-all">ทั้งหมด</button>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm ward-map-pick-none">ล้าง</button>' +
            '</div>';

        const list = document.createElement('div');
        list.className = 'ward-map-names-list' + (rows.length >= 4 ? ' is-multi-col' : '');

        rows.forEach(function (row) {
            const name = row.ward_name;
            const disabled = isNameDisabled(name, wardId, selected);
            const id = 'wm-' + wardId + '-' + code + '-' + name.replace(/\W/g, '_');
            const label = document.createElement('label');
            label.className = 'ward-map-name-option' + (disabled ? ' is-disabled' : '');
            label.dataset.search = [name, row.ward_name_ward || ''].join(' ');

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'api_ward_names[]';
            cb.value = name;
            cb.id = id;
            cb.disabled = disabled;
            if (selected.includes(name)) {
                cb.checked = true;
            }

            const body = document.createElement('span');
            body.className = 'ward-map-name-body';

            const title = document.createElement('span');
            title.className = 'ward-map-name-title';
            title.textContent = name;

            const sub = document.createElement('span');
            sub.className = 'ward-map-name-sub';
            if (disabled) {
                sub.classList.add('is-warn');
                sub.textContent = 'ใช้แผนกอื่นแล้ว — ไม่สามารถเลือก';
            } else if (row.ward_name_ward) {
                sub.textContent = 'กลุ่ม: ' + row.ward_name_ward;
            } else {
                sub.textContent = 'รหัส ' + code;
            }

            body.appendChild(title);
            body.appendChild(sub);
            label.appendChild(cb);
            label.appendChild(body);
            list.appendChild(label);

            cb.addEventListener('change', function () {
                syncNameSelectionUI(wrap);
            });
        });

        const empty = document.createElement('p');
        empty.className = 'ward-map-names-empty d-none';
        empty.textContent = 'ไม่พบชื่อตามคำค้น';

        wrap.appendChild(toolbar);
        wrap.appendChild(list);
        wrap.appendChild(empty);
        panel.appendChild(wrap);

        const filterInput = toolbar.querySelector('.ward-map-names-filter');
        filterInput?.addEventListener('input', function () {
            filterNameList(wrap, filterInput.value);
        });

        toolbar.querySelector('.ward-map-pick-all')?.addEventListener('click', function () {
            list.querySelectorAll('.ward-map-name-option:not(.is-filter-hidden):not(.is-disabled) input[type="checkbox"]').forEach(function (cb) {
                cb.checked = true;
            });
            syncNameSelectionUI(wrap);
        });
        toolbar.querySelector('.ward-map-pick-none')?.addEventListener('click', function () {
            list.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                cb.checked = false;
            });
            syncNameSelectionUI(wrap);
        });

        syncNameSelectionUI(wrap);

        if (rows.length >= 3) {
            setTimeout(function () {
                filterInput?.focus();
            }, 50);
        }
    }

    function closeEditor() {
        if (openRow) {
            openRow.classList.remove('is-editing');
            openRow.querySelector('.ward-map-row-editor')?.classList.add('d-none');
            openRow = null;
        }
    }

    function openEditor(row, preset) {
        closeEditor();
        openRow = row;
        row.classList.add('is-editing');
        const editor = row.querySelector('.ward-map-row-editor');
        editor?.classList.remove('d-none');

        const wardId = row.dataset.wardId;
        let code = row.dataset.apiCode || '';
        let selected = parseSelected(row);
        if (preset?.code) {
            code = preset.code;
        }
        if (preset?.names) {
            selected = preset.names.slice();
        }

        const select = editor.querySelector('.ward-map-code-select');
        const panel = editor.querySelector('.ward-map-names-panel');
        if (select) {
            select.innerHTML = codeOptionsHtml(code);
            select.value = code;
            select.onchange = function () {
                renderNameCheckboxes(panel, select.value, wardId, []);
                updateCodeHint(editor, select.value);
            };
        }
        renderNameCheckboxes(panel, code, wardId, selected);
        updateCodeHint(editor, code);

        const msg = editor.querySelector('.ward-map-inline-msg');
        if (msg) {
            msg.textContent = '';
        }
        editor.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function badgeClass(status) {
        if (status === 'ok' || status === 'not_in_snapshot') {
            return 'ward-map-ok';
        }
        if (status === 'duplicate') {
            return 'ward-map-duplicate';
        }
        return 'ward-map-missing';
    }

    function renderApiCell(cell, apis) {
        if (!apis || apis.length === 0) {
            cell.innerHTML = '<span class="ward-map-api-empty">— ยังไม่เชื่อม —</span>';
            return;
        }
        let html = '<ul class="ward-map-api-list list-unstyled mb-0">';
        apis.forEach(function (api) {
            const chipClass = api.link_status === 'duplicate' ? 'is-dup'
                : (api.ghost ? 'is-ghost' : 'is-ok');
            let role = '';
            if (api.is_primary) {
                role = '<span class="ward-map-api-role">หลัก</span>';
            } else if (apis.length > 1) {
                role = '<span class="ward-map-api-role ward-map-api-role--merge">รวมยอด</span>';
            }
            html += '<li class="ward-map-api-item ' + chipClass + '">' +
                '<span class="ward-map-api-item-head">' +
                '<span class="ward-map-api-item-name">' + escapeHtml(api.name) + '</span>' + role +
                '</span><span class="ward-map-api-item-code">' + escapeHtml(api.code) + '</span></li>';
        });
        html += '</ul>';
        cell.innerHTML = html;
    }

    function updateUsedMapFromRow(rowData) {
        const wardId = rowData.ward_id;
        Object.keys(usedNameToWard).forEach(function (name) {
            if (String(usedNameToWard[name]) === String(wardId)) {
                delete usedNameToWard[name];
            }
        });
        (rowData.mapped_names || []).forEach(function (name) {
            if (name) {
                usedNameToWard[name] = wardId;
            }
        });
    }

    function applyRowData(row, data) {
        row.dataset.status = data.status;
        row.dataset.issue = data.is_issue ? '1' : '0';
        row.dataset.ok = data.is_ok ? '1' : '0';
        row.dataset.search = data.search || '';
        row.dataset.apiCode = data.api_code || '';
        row.dataset.selectedNames = JSON.stringify(data.mapped_names || []);

        row.className = 'ward-map-row status-' + data.status + (row.classList.contains('is-editing') ? ' is-editing' : '');

        const dbName = row.querySelector('.ward-map-db-name');
        if (dbName) {
            const nameOnly = row.dataset.wardName || '';
            let badge = '';
            if (data.apis && data.apis.length > 1) {
                badge = '<span class="ward-map-multi-badge" title="รวม ' + data.apis.length + ' ชื่อ API">' +
                    data.apis.length + ' ชื่อ</span>';
            }
            dbName.innerHTML = escapeHtml(nameOnly) + badge;
        }

        const apiCell = row.querySelector('.ward-map-cell--api');
        if (apiCell) {
            renderApiCell(apiCell, data.apis);
        }

        const statusEl = row.querySelector('.ward-map-status-badge');
        if (statusEl) {
            statusEl.className = 'ward-map-status-badge ' + badgeClass(data.status);
            statusEl.textContent = data.status_label || '';
        }

        updateUsedMapFromRow(data);
    }

    async function saveMapping(row, clear) {
        const editor = row.querySelector('.ward-map-row-editor');
        const msg = editor?.querySelector('.ward-map-inline-msg');
        const submitBtn = editor?.querySelector('.ward-map-inline-save');
        const wardId = row.dataset.wardId;

        const fd = new FormData();
        fd.append(cfg.csrfName, cfg.csrfHash);
        if (clear) {
            fd.append('clear_mapping', '1');
        } else {
            const code = editor.querySelector('.ward-map-code-select')?.value || '';
            fd.append('api_ward_code', code);
            editor.querySelectorAll('input[name="api_ward_names[]"]:checked').forEach(function (cb) {
                fd.append('api_ward_names[]', cb.value);
            });
        }

        if (submitBtn) {
            submitBtn.disabled = true;
        }
        if (msg) {
            msg.textContent = 'กำลังบันทึก…';
            msg.className = 'ward-map-inline-msg small text-muted';
        }

        try {
            const resp = await fetch(cfg.saveUrlBase + wardId, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const json = await resp.json();
            if (!json.ok) {
                if (msg) {
                    msg.textContent = json.message || 'บันทึกไม่สำเร็จ';
                    msg.className = 'ward-map-inline-msg small text-danger';
                }
                return;
            }
            if (json.row) {
                applyRowData(row, json.row);
            }
            closeEditor();
            if (window.wardMapFilterRefresh) {
                window.wardMapFilterRefresh();
            }
            showToast(json.message || 'บันทึกแล้ว');
        } catch (e) {
            if (msg) {
                msg.textContent = 'เชื่อมต่อไม่สำเร็จ';
                msg.className = 'ward-map-inline-msg small text-danger';
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    }

    function showToast(text) {
        let el = document.getElementById('ward-map-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'ward-map-toast';
            el.className = 'ward-map-toast';
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
        }
        el.textContent = text;
        el.classList.add('is-visible');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () {
            el.classList.remove('is-visible');
        }, 2800);
    }

    document.addEventListener('click', function (e) {
        const openBtn = e.target.closest('.ward-map-inline-open');
        if (openBtn) {
            const row = openBtn.closest('.ward-map-row');
            if (row) {
                if (openRow === row && row.classList.contains('is-editing')) {
                    closeEditor();
                } else {
                    openEditor(row);
                }
            }
            return;
        }

        const quickBtn = e.target.closest('.ward-map-quick-map');
        if (quickBtn) {
            const wardId = quickBtn.dataset.wardId;
            const row = document.querySelector('.ward-map-row[data-ward-id="' + wardId + '"]');
            if (row) {
                openEditor(row, {
                    code: quickBtn.dataset.apiCode || '',
                    names: [quickBtn.dataset.apiName || ''].filter(Boolean),
                });
                row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
            return;
        }

        if (e.target.closest('.ward-map-inline-cancel')) {
            closeEditor();
        }
    });

    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.ward-map-inline-form');
        if (!form) {
            return;
        }
        e.preventDefault();
        const row = form.closest('.ward-map-row');
        if (row) {
            saveMapping(row, false);
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.ward-map-inline-clear')) {
            const row = e.target.closest('.ward-map-row');
            if (row && confirm('ล้างการเชื่อม API ของแผนกนี้ใช่หรือไม่?')) {
                saveMapping(row, true);
            }
        }
    });
})();
