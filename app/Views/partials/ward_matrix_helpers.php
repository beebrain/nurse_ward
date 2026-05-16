<script>
window.WardMatrix = (function() {
    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function shortLabel(ward) {
        const code = String(ward?.code ?? '').trim();
        if (code) {
            return code;
        }
        const name = String(ward?.name ?? '');
        if (name.length <= 10) {
            return name;
        }
        return name.slice(0, 9) + '…';
    }

    function headerTh(ward) {
        const title = escapeHtml(ward?.label || ward?.name || '');
        return `<th class="ward-col" title="${title}"><span class="ward-matrix-head">${escapeHtml(shortLabel(ward))}</span></th>`;
    }

    function dateCell(day, labelKey, subKey) {
        const main = day?.[labelKey] ?? day?.day_label ?? '';
        const sub = day?.[subKey] ?? day?.weekday_label ?? '';
        return `<td class="ward-matrix-date-col ps-2">
            <div class="date-main">${escapeHtml(main)}</div>
            ${sub ? `<div class="date-sub">${escapeHtml(sub)}</div>` : ''}
        </td>`;
    }

    function wrapTable(innerHtml) {
        return `<div class="ward-matrix-scroll table-responsive">
            <table class="table ward-matrix-table table-hover table-sm mb-0">${innerHtml}</table>
        </div>`;
    }

    return {
        escapeHtml,
        shortLabel,
        headerTh,
        dateCell,
        wrapTable,
    };
})();
</script>
