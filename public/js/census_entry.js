$(document).ready(function() {
    let autosaveTimer;
    const autosaveDelay = 1000;
    const $form = $('form[action*="census/store"]');
    const $statusIndicator = $('#autosave-status');

    function updateStatus(text, className) {
        $statusIndicator.text(text).removeClass('text-muted text-success text-danger text-warning').addClass(className);
    }

    function doAutosave() {
        const formData = $form.serialize();
        const autosaveUrl = $form.attr('action').replace('store', 'autosave');

        updateStatus('กำลังบันทึก...', 'text-warning');

        $.ajax({
            url: autosaveUrl,
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateStatus('บันทึกอัตโนมัติสำเร็จ', 'text-success');
                    setTimeout(() => updateStatus('พร้อม', 'text-muted'), 3000);
                    loadHistoryDebounced();
                } else {
                    updateStatus('ข้อมูลไม่ผ่านการตรวจสอบ', 'text-danger');
                    console.error('Validation errors:', response.errors);
                }
            },
            error: function(xhr, status, error) {
                updateStatus('เกิดข้อผิดพลาดในการบันทึก', 'text-danger');
                console.error('AJAX error:', error);
            }
        });
    }

    $form.on('input change', 'input, select', function() {
        if ($(this).attr('type') === 'number' && $(this).val() < 0) {
            $(this).val(0);
        }

        clearTimeout(autosaveTimer);

        if ($('#ward_id').val() && $('#record_date').val() && $('#shift').val()) {
            autosaveTimer = setTimeout(doAutosave, autosaveDelay);
        } else {
            updateStatus('เลือกแผนก วันที่ และเวร เพื่อเปิดใช้การบันทึกอัตโนมัติ', 'text-muted');
        }
    });

    // ─── History (ward, who saved, metrics) ─────────────────────────────
    const $root = $('#census-history-root');
    if ($root.length === 0) {
        return;
    }

    const historyUrl = $root.attr('data-history-url');
    const $histWard = $('#history_ward_id');
    const $histFrom = $('#history_date_from');
    const $histTo = $('#history_date_to');
    const $histLoading = $('#census-history-loading');
    const $histEmpty = $('#census-history-empty');
    const $histErr = $('#census-history-error');
    const $tbody = $('#census-history-table tbody');
    const $cards = $('#census-history-cards');

    let historyTimer;

    function escHtml(s) {
        return $('<div>').text(s == null ? '' : String(s)).html();
    }

    function formatThaiDate(ymd) {
        if (!ymd || typeof ymd !== 'string') {
            return '—';
        }
        const p = ymd.split('-');
        if (p.length !== 3) {
            return escHtml(ymd);
        }
        return escHtml(p[2] + '/' + p[1] + '/' + p[0]);
    }

    function formatDt(dt) {
        if (!dt) {
            return '—';
        }
        const s = String(dt).replace('T', ' ');
        return escHtml(s.length > 16 ? s.slice(0, 16) : s);
    }

    function renderHistory(rows) {
        $tbody.empty();
        $cards.empty();

        if (!rows.length) {
            $histEmpty.removeClass('d-none');
            return;
        }
        $histEmpty.addClass('d-none');

        rows.forEach(function(r) {
            const tr = $('<tr>');
            tr.append('<td class="text-nowrap">' + formatThaiDate(r.record_date) + '</td>');
            tr.append('<td><span class="badge rounded-pill bg-primary-subtle text-primary fw-bold">' + escHtml(r.shift_label) + '</span></td>');
            tr.append('<td class="fw-semibold">' + escHtml(r.ward_name) + '</td>');
            tr.append('<td class="text-center">' + escHtml(r.admissions) + '</td>');
            tr.append('<td class="text-center">' + escHtml(r.discharges) + '</td>');
            tr.append('<td class="text-center">' + escHtml(r.deaths) + '</td>');
            tr.append('<td class="text-center">' + escHtml(r.transfers_in) + '</td>');
            tr.append('<td class="text-center">' + escHtml(r.transfers_out) + '</td>');
            tr.append('<td class="text-center fw-bold text-primary">' + escHtml(r.total_remaining) + '</td>');
            tr.append('<td class="text-nowrap">' + escHtml(r.recorder_username) + '</td>');
            tr.append('<td class="text-muted small text-nowrap">' + formatDt(r.updated_at) + '</td>');
            $tbody.append(tr);

            const card = $(
                '<div class="census-history-mobile-card">'
            );
            card.append(
                '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
                '<div><div class="fw-bold">' + escHtml(r.ward_name) + '</div>' +
                '<div class="small text-muted">' + formatThaiDate(r.record_date) + ' · ' + escHtml(r.shift_label) + '</div></div>' +
                '<span class="small text-muted text-end">อัปเดต<br>' + formatDt(r.updated_at) + '</span></div>'
            );
            card.append(
                '<div class="small mb-2"><span class="text-muted">ผู้บันทึกล่าสุด:</span> <strong>' + escHtml(r.recorder_username) + '</strong></div>'
            );
            card.append(
                '<div class="row g-2 small">' +
                '<div class="col-6"><span class="text-muted">รับใหม่</span> <strong>' + escHtml(r.admissions) + '</strong></div>' +
                '<div class="col-6"><span class="text-muted">จำหน่าย</span> <strong>' + escHtml(r.discharges) + '</strong></div>' +
                '<div class="col-6"><span class="text-muted">เสียชีวิต</span> <strong>' + escHtml(r.deaths) + '</strong></div>' +
                '<div class="col-6"><span class="text-muted">ย้ายเข้า</span> <strong>' + escHtml(r.transfers_in) + '</strong></div>' +
                '<div class="col-6"><span class="text-muted">ย้ายออก</span> <strong>' + escHtml(r.transfers_out) + '</strong></div>' +
                '<div class="col-12 mt-1"><span class="text-muted">คงพยาบาล</span> <strong class="text-primary fs-6">' + escHtml(r.total_remaining) + '</strong></div>' +
                '</div>'
            );
            $cards.append(card);
        });
    }

    function loadHistory() {
        if (!historyUrl) {
            return;
        }
        $histErr.addClass('d-none').text('');
        $histLoading.removeClass('d-none');

        const params = {
            date_from: $histFrom.val(),
            date_to: $histTo.val()
        };
        const w = $histWard.val();
        if (w) {
            params.ward_id = w;
        }

        $.getJSON(historyUrl, params)
            .done(function(data) {
                renderHistory(data.rows || []);
            })
            .fail(function(xhr) {
                let msg = 'โหลดประวัติไม่สำเร็จ';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                $histErr.removeClass('d-none').text(msg);
                $tbody.empty();
                $cards.empty();
                $histEmpty.addClass('d-none');
            })
            .always(function() {
                $histLoading.addClass('d-none');
            });
    }

    function loadHistoryDebounced() {
        clearTimeout(historyTimer);
        historyTimer = setTimeout(loadHistory, 400);
    }

    $('#census-history-filters').on('submit', function(e) {
        e.preventDefault();
        loadHistory();
    });

    loadHistory();
});
