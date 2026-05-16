<style>
    .ward-matrix-scroll {
        max-height: min(70vh, calc(100dvh - 16rem));
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 0.375rem 0.375rem;
    }

    .ward-matrix-table {
        margin-bottom: 0;
        font-size: 0.8rem;
    }

    .ward-matrix-table th,
    .ward-matrix-table td {
        vertical-align: middle;
        padding: 0.28rem 0.2rem;
    }

    .ward-matrix-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
        box-shadow: 0 1px 0 #dee2e6;
    }

    .ward-matrix-date-col {
        position: sticky;
        left: 0;
        z-index: 1;
        background: #fff;
        min-width: 3.75rem;
        max-width: 4.25rem;
        box-shadow: 2px 0 4px rgba(15, 23, 42, 0.06);
        white-space: nowrap;
    }

    .ward-matrix-table thead th.ward-matrix-date-col {
        z-index: 4;
        background-color: #f8f9fa;
    }

    .ward-matrix-table thead th.ward-col {
        min-width: 2.1rem;
        max-width: 2.35rem;
        text-align: center;
        vertical-align: bottom;
        padding-bottom: 0.35rem;
    }

    .ward-matrix-head {
        display: inline-block;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 1.05;
        max-height: 4.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ward-matrix-table tbody td.ward-cell {
        text-align: center;
        font-weight: 600;
        font-size: 0.76rem;
        min-width: 2.1rem;
    }

    .ward-matrix-table .date-main {
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.15;
    }

    .ward-matrix-table .date-sub {
        font-size: 0.62rem;
        color: #64748b;
        line-height: 1.1;
    }

    .matrix-page-kpi {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        background: #fbfcfe;
    }

    .matrix-page-kpi-label {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .matrix-page-kpi-value {
        font-size: clamp(1.25rem, 4vw, 1.75rem);
        font-weight: 800;
        color: #1e3a8a;
        line-height: 1.15;
    }

    @media (max-width: 575.98px) {
        .matrix-filter-actions .btn {
            width: 100%;
        }
    }
</style>
