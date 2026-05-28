<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<style>
  /* ═══════════════════════════════════════════════════════════════════════
   PROFESSIONAL HEALTHCARE PALETTE
   Calm muted tones, soft tints, off-white surfaces (no pure white)
   ═══════════════════════════════════════════════════════════════════════ */
  :root {
    /* App surfaces */
    --bg-page: #eef2f7;
    /* soft cool gray-blue, easy on eyes */
    --bg-card: #fbfcfe;
    /* near-white card surface */
    --bg-input: #fafbfd;
    /* off-white inputs */
    --border-soft: #d8dee8;
    --text-primary: #1f2937;
    --text-muted: #64748b;

    /* Severity (clinical, muted but distinguishable) */
    --c-l5: #9c2942;
    --c-l5-bg: #fbeef1;
    --c-l4: #a85c1f;
    --c-l4-bg: #fbf2e8;
    --c-l3: #1f6f8b;
    --c-l3-bg: #ebf3f7;
    --c-l2: #3f7a4f;
    --c-l2-bg: #ecf3ee;
    --c-l1: #5a4eb6;
    --c-l1-bg: #efedf8;
    --c-total: #2c3e50;

    /* Movements (soft accent tones) */
    --c-admit: #2e7d56;
    --c-admit-bg: #e8f1ec;
    --c-dc: #8c3a5e;
    --c-dc-bg: #f5e8ee;
    --c-tin: #3d5fa3;
    --c-tin-bg: #ebeff7;
    --c-tout: #8a6d2c;
    --c-tout-bg: #f6f0e2;
    --c-death: #2c3e50;
    --c-death-bg: #2c3e50;

    /* Nurses */
    --c-rn: #9c2942;
    --c-rn-bg: #fbeef1;
    --c-other: #4d6485;
    --c-other-bg: #eaeef5;
  }

  /* Page background — stop "bright white" glare */
  body {
    background: var(--bg-page) !important;
  }

  /* ── Cards & section headers ───────────────────────────────────────── */
  .card {
    border-radius: 12px;
    border: 1px solid var(--border-soft);
    background: var(--bg-card);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
  }

  .card-header {
    padding: 12px 18px !important;
    font-size: .98rem !important;
    font-weight: 700 !important;
    letter-spacing: .2px;
    border-bottom: none !important;
    color: #7388ff !important;
  }

  .card-header .material-symbols-outlined,
  .card-header i {
    font-size: 1.1rem;
    vertical-align: -2px;
    opacity: .9;
  }

  .sh-blue {
    background: linear-gradient(135deg, #1e3a8a, #3b5fb8) !important;
  }

  .sh-teal {
    background: linear-gradient(135deg, #0f5e58, #2a8a82) !important;
  }

  .sh-purple {
    background: linear-gradient(135deg, #3f1d8a, #6b46b8) !important;
  }

  .sh-red {
    background: linear-gradient(135deg, #7a1f33, #9c2942) !important;
  }

  .sh-gray {
    background: linear-gradient(135deg, #1f2937, #475569) !important;
  }

  /* ── Generic stat cards (used by levels / movements / nurses) ─────── */
  .stat-card {
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
    border: 1px solid;
    transition: box-shadow .15s, transform .1s;
    height: 100%;
  }

  .stat-card:hover {
    box-shadow: 0 3px 8px rgba(15, 23, 42, .12);
  }

  .stat-label {
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .2px;
  }

  .stat-sub {
    font-size: .68rem;
    color: var(--text-muted);
    line-height: 1.25;
    min-height: 1.6em;
  }

  .stat-input {
    font-size: 1.55rem;
    font-weight: 700;
    border: 1.5px solid;
    border-radius: 8px;
    text-align: center;
    width: 100%;
    padding: 6px 4px;
    background: var(--bg-input);
    transition: border-color .15s, box-shadow .15s;
    margin-top: 6px;
    min-height: 48px;
    appearance: textfield;
    -moz-appearance: textfield;
  }

  .stat-input::-webkit-inner-spin-button,
  .stat-input::-webkit-outer-spin-button {
    opacity: .35;
  }

  .stat-input:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(31, 41, 55, .12);
  }

  /* ── Level Cards (severity 5→1) ───────────────────────────────────── */
  .lv5 {
    background: var(--c-l5-bg);
    border-color: var(--c-l5);
  }

  .lv5 .stat-label,
  .lv5 .stat-input {
    color: var(--c-l5);
    border-color: var(--c-l5);
  }

  .lv5 .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(156, 41, 66, .18);
  }

  .lv4 {
    background: var(--c-l4-bg);
    border-color: var(--c-l4);
  }

  .lv4 .stat-label,
  .lv4 .stat-input {
    color: var(--c-l4);
    border-color: var(--c-l4);
  }

  .lv4 .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(168, 92, 31, .18);
  }

  .lv3 {
    background: var(--c-l3-bg);
    border-color: var(--c-l3);
  }

  .lv3 .stat-label,
  .lv3 .stat-input {
    color: var(--c-l3);
    border-color: var(--c-l3);
  }

  .lv3 .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(31, 111, 139, .18);
  }

  .lv2 {
    background: var(--c-l2-bg);
    border-color: var(--c-l2);
  }

  .lv2 .stat-label,
  .lv2 .stat-input {
    color: var(--c-l2);
    border-color: var(--c-l2);
  }

  .lv2 .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(63, 122, 79, .18);
  }

  .lv1 {
    background: var(--c-l1-bg);
    border-color: var(--c-l1);
  }

  .lv1 .stat-label,
  .lv1 .stat-input {
    color: var(--c-l1);
    border-color: var(--c-l1);
  }

  .lv1 .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(90, 78, 182, .18);
  }

  .lv-total {
    background: var(--c-total);
    border-color: var(--c-total);
    color: #fff;
  }

  .lv-total .stat-label {
    color: #fff;
  }

  .lv-total .stat-input {
    background: rgba(255, 255, 255, .08);
    color: #fff;
    border-color: rgba(255, 255, 255, .25);
  }

  /* Larger numbers for patient levels (key data) */
  .lv5 .stat-input,
  .lv4 .stat-input,
  .lv3 .stat-input,
  .lv2 .stat-input,
  .lv1 .stat-input,
  .lv-total .stat-input {
    font-size: 1.85rem;
    min-height: 56px;
  }

  /* ── Movement Cards ───────────────────────────────────────────────── */
  .mc-admit {
    background: var(--c-admit-bg);
    border-color: var(--c-admit);
  }

  .mc-admit .stat-label,
  .mc-admit .stat-input {
    color: var(--c-admit);
    border-color: var(--c-admit);
  }

  .mc-admit .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(46, 125, 86, .18);
  }

  .mc-dc {
    background: var(--c-dc-bg);
    border-color: var(--c-dc);
  }

  .mc-dc .stat-label,
  .mc-dc .stat-input {
    color: var(--c-dc);
    border-color: var(--c-dc);
  }

  .mc-dc .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(140, 58, 94, .18);
  }

  .mc-in {
    background: var(--c-tin-bg);
    border-color: var(--c-tin);
  }

  .mc-in .stat-label,
  .mc-in .stat-input {
    color: var(--c-tin);
    border-color: var(--c-tin);
  }

  .mc-in .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(61, 95, 163, .18);
  }

  .mc-out {
    background: var(--c-tout-bg);
    border-color: var(--c-tout);
  }

  .mc-out .stat-label,
  .mc-out .stat-input {
    color: var(--c-tout);
    border-color: var(--c-tout);
  }

  .mc-out .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(138, 109, 44, .18);
  }

  .mc-death {
    background: var(--c-death-bg);
    border-color: var(--c-death);
    color: #fff;
  }

  .mc-death .stat-label {
    color: #fff;
  }

  .mc-death .stat-input {
    background: rgba(255, 255, 255, .1);
    color: #fff;
    border-color: rgba(255, 255, 255, .3);
  }

  /* ── Nurse Cards ──────────────────────────────────────────────────── */
  .nc-calc {
    background: var(--c-rn-bg);
    border-color: var(--c-rn);
  }

  .nc-calc .stat-label,
  .nc-calc .stat-input {
    color: var(--c-rn);
    border-color: var(--c-rn);
  }

  .nc-calc .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(156, 41, 66, .18);
  }

  .nc-other {
    background: var(--c-other-bg);
    border-color: var(--c-other);
  }

  .nc-other .stat-label,
  .nc-other .stat-input {
    color: var(--c-other);
    border-color: var(--c-other);
  }

  .nc-other .stat-input:focus {
    box-shadow: 0 0 0 3px rgba(77, 100, 133, .18);
  }

  /* ── Productivity preview ─────────────────────────────────────────── */
  .prod-preview {
    background: linear-gradient(135deg, #1f2937 0%, #334155 100%);
    border-radius: 10px;
    color: #fff;
    padding: 14px;
    margin-top: 14px;
  }

  .prod-stat-label {
    font-size: .72rem;
    opacity: .75;
    letter-spacing: .4px;
    text-transform: uppercase;
  }

  .prod-stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.2;
  }

  .prod-divider {
    border-color: rgba(255, 255, 255, .18) !important;
  }

  /* ── QI inputs ────────────────────────────────────────────────────── */
  .qi-card {
    border: 1px solid var(--border-soft);
    background: var(--bg-input);
    border-radius: 8px;
    padding: 8px 6px;
    text-align: center;
    height: 100%;
  }

  .qi-card.qi-hai {
    border-color: #c08191;
    background: #fbeef1;
  }

  .qi-card.qi-special {
    border-color: #8da4c4;
    background: #ebeff7;
  }

  .qi-card-label {
    font-size: .72rem;
    font-weight: 700;
  }

  .qi-hai .qi-card-label {
    color: var(--c-l5);
  }

  .qi-special .qi-card-label {
    color: var(--c-tin);
  }

  .qi-input {
    font-size: 1.2rem;
    font-weight: 700;
    border: 1.5px solid;
    border-radius: 6px;
    text-align: center;
    width: 100%;
    padding: 4px;
    background: var(--bg-input);
    margin-top: 4px;
    min-height: 40px;
    appearance: textfield;
    -moz-appearance: textfield;
  }

  .qi-input::-webkit-inner-spin-button,
  .qi-input::-webkit-outer-spin-button {
    opacity: .35;
  }

  .qi-input:focus {
    outline: none;
  }

  .qi-hai .qi-input {
    color: var(--c-l5);
    border-color: var(--c-l5);
  }

  .qi-hai .qi-input:focus {
    box-shadow: 0 0 0 3px rgba(156, 41, 66, .18);
  }

  .qi-special .qi-input {
    color: var(--c-tin);
    border-color: var(--c-tin);
  }

  .qi-special .qi-input:focus {
    box-shadow: 0 0 0 3px rgba(61, 95, 163, .18);
  }

  /* ── Unified input controls ───────────────────────────────────────── */
  .form-control,
  .form-select,
  textarea,
  .stat-input,
  .qi-input {
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%) !important;
    border: 1px solid #d0d5dd !important;
    border-radius: 12px !important;
    color: var(--text-primary) !important;
    box-shadow:
      inset 0 1px 2px rgba(16, 24, 40, .06),
      0 1px 0 rgba(255, 255, 255, .9) !important;
    transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
  }

  .form-control:focus,
  .form-select:focus,
  textarea:focus,
  .stat-input:focus,
  .qi-input:focus {
    background: #ffffff !important;
    border-color: #0a84ff !important;
    box-shadow:
      0 0 0 4px rgba(10, 132, 255, .16),
      inset 0 1px 2px rgba(16, 24, 40, .05) !important;
    outline: none !important;
  }

  .form-control:hover,
  .form-select:hover,
  textarea:hover,
  .stat-input:hover,
  .qi-input:hover {
    border-color: #b8c0cc !important;
  }

  .form-label {
    color: var(--text-primary);
  }

  /* ── Touch device tweaks ──────────────────────────────────────────── */
  @media (hover: none) and (pointer: coarse) {
    .stat-input {
      font-size: 1.65rem !important;
      min-height: 54px;
    }

    .qi-input {
      min-height: 46px;
      font-size: 1.3rem;
    }
  }

  /* ── Small-screen layout ──────────────────────────────────────────── */
  @media (max-width: 575.98px) {
    .stat-input {
      font-size: 1.4rem;
      min-height: 44px;
    }

    .stat-sub {
      display: none;
    }

    /* hide subtitle on phones */
    .card-header {
      padding: 10px 14px !important;
      font-size: .9rem !important;
    }

    .card-body {
      padding: .85rem !important;
    }
  }

  /* ── Daily census template adapted from Stitch export ───────────────── */
  .daily-census-template {
    --template-surface: #f9f9ff;
    --template-surface-low: #f2f3fc;
    --template-surface-card: #ffffff;
    --template-surface-high: #e6e8f0;
    --template-primary: #005dac;
    --template-primary-strong: #1976d2;
    --template-secondary: #006e1c;
    --template-text: #181c21;
    --template-muted: #414752;
    --template-outline: #c1c6d4;
    --template-shadow: 0 12px 32px rgba(0, 95, 175, 0.06);
    color: var(--template-text);
    font-family: 'Inter', sans-serif;
  }

  .daily-census-template *,
  .daily-census-template .btn,
  .daily-census-template .form-control,
  .daily-census-template .form-select {
    font-family: 'Inter', sans-serif !important;
  }

  .daily-census-template .material-symbols-outlined {
    font-family: 'Material Symbols Outlined' !important;
    font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
  }

  .daily-page-title {
    font-size: clamp(1.65rem, 2.4vw, 2.3rem);
    font-weight: 800;
    letter-spacing: -0.04em;
  }

  .daily-page-subtitle {
    color: var(--template-muted);
    font-weight: 600;
  }

  .daily-toolbar {
    background: var(--template-surface-low);
    border-radius: 1.4rem;
    box-shadow: var(--template-shadow);
    padding: .55rem;
  }

  .toolbar-field {
    padding: .45rem .8rem;
    min-width: 160px;
  }

  .toolbar-field label {
    display: block;
    color: #64748b;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: .15rem;
  }

  .toolbar-field .form-control,
  .toolbar-field .form-select {
    min-height: 44px;
    border: 1px solid #d0d5dd !important;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%) !important;
    border-radius: .75rem;
    color: var(--template-text) !important;
    font-size: .9rem;
    font-weight: 800;
    padding: .5rem .75rem;
    box-shadow:
      inset 0 1px 2px rgba(16, 24, 40, .06),
      0 1px 0 rgba(255, 255, 255, .9) !important;
  }

  .toolbar-divider {
    width: 1px;
    align-self: stretch;
    background: rgba(193, 198, 212, .75);
  }

  .daily-census-template .card {
    border: 1px solid rgba(193, 198, 212, .48);
    border-radius: 1.35rem;
    background: var(--template-surface-card);
    box-shadow: var(--template-shadow);
    overflow: hidden;
  }

  .daily-census-template .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: transparent !important;
    color: var(--template-text) !important;
    padding: 1rem 1.25rem .35rem !important;
    font-size: 1.1rem !important;
    font-weight: 800 !important;
  }

  .daily-census-template .card-header small {
    color: var(--template-muted);
  }

  .daily-census-template .card-body {
    padding: 1rem 1.25rem 1.25rem !important;
  }

  .daily-census-template .stat-card {
    background: var(--template-surface-card);
    border-color: rgba(193, 198, 212, .6);
    border-radius: .9rem;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
    padding: .95rem .85rem;
    text-align: left;
  }

  .daily-census-template .stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(0, 95, 175, .08);
  }

  .daily-census-template .stat-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .68rem;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .daily-census-template .stat-sub {
    display: none;
  }

  .field-help {
    color: #8a93a3;
    cursor: help;
    font-size: 1rem;
    line-height: 1;
  }

  .field-help:hover {
    color: var(--template-primary);
  }

  .mini-field-label {
    color: #667085;
    display: block;
    font-size: .68rem;
    font-weight: 800;
    margin-bottom: .25rem;
  }

  .level-total-line {
    color: var(--template-muted);
    font-size: .76rem;
    font-weight: 700;
    margin-top: .55rem;
    text-align: right;
  }

  .movement-balance-panel {
    background: var(--template-surface-low);
    border: 1px solid rgba(193, 198, 212, .55);
    border-radius: 1rem;
    padding: .9rem;
  }

  .movement-balance-label {
    color: #667085;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .movement-balance-value {
    font-size: 1.25rem;
    font-weight: 800;
  }

  .carry-forward-note {
    align-items: center;
    background: rgba(0, 93, 172, .08);
    border: 1px solid rgba(0, 93, 172, .12);
    border-radius: 999px;
    color: var(--template-primary);
    display: inline-flex;
    font-size: .82rem;
    font-weight: 700;
    gap: .35rem;
    padding: .45rem .8rem;
  }

  .daily-census-template .stat-input,
  .daily-census-template .qi-input {
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%) !important;
    border: 1px solid #d0d5dd !important;
    color: var(--template-text) !important;
    border-radius: 12px !important;
    box-shadow:
      inset 0 1px 2px rgba(16, 24, 40, .06),
      0 1px 0 rgba(255, 255, 255, .9) !important;
  }

  .daily-census-template .lv5 .stat-label,
  .daily-census-template .lv5 .stat-input {
    color: #ba1a1a;
  }

  .daily-census-template .lv4 .stat-label,
  .daily-census-template .lv4 .stat-input,
  .daily-census-template .lv3 .stat-label,
  .daily-census-template .lv3 .stat-input {
    color: var(--template-primary);
  }

  .daily-census-template .lv2 .stat-label,
  .daily-census-template .lv2 .stat-input,
  .daily-census-template .lv1 .stat-label,
  .daily-census-template .lv1 .stat-input {
    color: var(--template-secondary);
  }

  .daily-census-template .lv-total {
    background: linear-gradient(135deg, var(--template-primary), var(--template-primary-strong));
    border-color: transparent;
  }

  .daily-census-template .movement-card {
    border-left-width: 4px;
  }

  .total-pill {
    background: rgba(0, 93, 172, .1);
    color: var(--template-primary);
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 800;
    padding: .35rem .8rem;
  }

  .form-actions-bar {
    padding: 1.5rem 0 2.5rem;
  }

  .quick-save-fab {
    position: fixed;
    right: 2rem;
    bottom: 2rem;
    z-index: 1031;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 999px;
    border: 0;
    background: #006e1c;
    color: #fff;
    box-shadow: 0 18px 36px rgba(0, 110, 28, .22);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform .15s ease;
  }

  .quick-save-fab:hover {
    color: #fff;
    transform: scale(1.06);
  }

  @media (max-width: 767.98px) {
    .daily-toolbar {
      display: block !important;
      padding: .9rem;
    }

    .toolbar-field {
      padding: .45rem .25rem;
    }

    .toolbar-divider {
      display: none;
    }

    .quick-save-fab {
      right: 1rem;
      bottom: 5.75rem;
    }
  }
</style>

<div class="container-fluid px-md-3 px-2 daily-census-template" style="max-width: 1400px;">

  <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-4 mb-4">
    <div>
      <h4 class="daily-page-title mb-1">บันทึกยอดผู้ป่วยรายวัน</h4>
      <p class="daily-page-subtitle mb-0">Record and track patient volume, nursing level, movement, and staffing by shift.</p>
    </div>
    <a href="<?= base_url('census/history') ?>" class="btn btn-outline-secondary align-self-start align-self-xl-end">
      <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">history</span>
      ประวัติการบันทึก
    </a>
  </div>

  <?php if ($isNurse && empty($wards)): ?>
    <div class="alert alert-danger">
      <span class="material-symbols-outlined align-middle me-1">warning</span>
      บัญชีของคุณยังไม่ได้รับการกำหนด Ward กรุณาติดต่อผู้ดูแลระบบ
    </div>
  <?php elseif ($isNurse): ?>
    <div class="alert alert-info d-flex align-items-start gap-2 py-2">
      <span class="material-symbols-outlined mt-1" style="font-size:1.1rem;">lock</span>
      <div class="small">
        คุณสามารถบันทึกได้เฉพาะ Ward ที่รับผิดชอบ (<strong><?= count($wards) ?> Ward</strong>)
      </div>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= session()->getFlashdata('message') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form id="censusForm" action="<?= base_url('census/store') ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <!-- ── Header: Ward / Date / Shift ──────────────────────────────── -->
    <div class="daily-toolbar d-flex flex-wrap align-items-center gap-2 mb-4">
      <div class="toolbar-field flex-grow-1">
        <label>Ward <span class="text-danger">*</span></label>
        <select name="ward_id" id="ward_id" class="form-select" required>
          <option value="">— เลือก Ward —</option>
          <?php
          $currentDept = '';
          foreach ($wards as $w):
            $dept = $w['department_name'] ?? '';
            if ($dept !== $currentDept):
              if ($currentDept !== '') echo '</optgroup>';
              echo '<optgroup label="' . esc($dept ?: 'ไม่ระบุกลุ่มงาน') . '">';
              $currentDept = $dept;
            endif;
          ?>
            <option value="<?= $w['id'] ?>" <?= (int)old('ward_id', $defaultWardId ?? 0) === (int)$w['id'] ? 'selected' : '' ?>>
              <?= esc($w['code'] ? $w['code'] . ' — ' . $w['name'] : $w['name']) ?>
            </option>
          <?php endforeach; ?>
          <?php if ($currentDept !== '') echo '</optgroup>'; ?>
        </select>
      </div>
      <div class="toolbar-divider"></div>
      <div class="toolbar-field">
        <label>วันที่ <span class="text-danger">*</span></label>
        <input type="date" name="record_date" id="record_date" class="form-control"
          value="<?= old('record_date', date('Y-m-d')) ?>" required>
      </div>
      <div class="toolbar-divider"></div>
      <div class="toolbar-field">
        <label>เวร <span class="text-danger">*</span></label>
        <select name="shift" id="shift" class="form-select" required>
          <option value="">— เลือก —</option>
          <option value="Night" <?= old('shift') === 'Night'     ? 'selected' : '' ?>>ดึก (Night)</option>
          <option value="Morning" <?= old('shift') === 'Morning'   ? 'selected' : '' ?>>เช้า (Morning)</option>
          <option value="Afternoon" <?= old('shift') === 'Afternoon' ? 'selected' : '' ?>>บ่าย (Afternoon)</option>
        </select>
      </div>
    </div>

    <div id="carry_forward_note" class="carry-forward-note mb-3 d-none">
      <span class="material-symbols-outlined" style="font-size:1rem;">update</span>
      <span id="carry_forward_note_text">ยอดยกมาจากเวรก่อนหน้า 0 คน</span>
    </div>

    <!-- ── Handover Guidelines Widget ────────────────────────────────── -->
    <div id="handover_guidelines_card" class="card shadow-sm mb-4 d-none">
      <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a8a, #0d9488) !important; color: #fff !important; padding: 14px 18px !important;">
        <span class="d-flex align-items-center gap-2">
          <span class="material-symbols-outlined" style="font-size:1.3rem;">clinical_notes</span>
          <span class="fw-bold">แนวทางการส่งเวรรายชั่วโมง (Handover Guidelines)</span>
        </span>
        <button type="button" id="btn_apply_hourly" class="btn btn-sm btn-light fw-bold text-primary d-flex align-items-center gap-1 py-1 px-2 border-0">
          <span class="material-symbols-outlined" style="font-size:1rem;">install_desktop</span>
          นำเข้าข้อมูลไปยังฟอร์มบันทึก
        </button>
      </div>
      <div class="card-body">
        <div class="row align-items-stretch">
          <!-- Stats Summary -->
          <div class="col-lg-5 col-12 mb-3 mb-lg-0">
            <h6 class="fw-bold text-muted mb-2 small text-uppercase" style="letter-spacing: .05em;">สรุปข้อมูลการเคลื่อนไหวระหว่างเวรจากระบบ API</h6>
            <div class="row row-cols-3 g-2">
              <div class="col">
                <div class="p-2 rounded text-center" style="background: var(--c-admit-bg); border: 1.5px solid var(--c-admit);">
                  <div class="small fw-semibold text-success">รับใหม่ (Admit)</div>
                  <div class="h5 fw-bold text-success mb-0 mt-1" id="hourly_admit_val">0</div>
                </div>
              </div>
              <div class="col">
                <div class="p-2 rounded text-center" style="background: var(--c-dc-bg); border: 1.5px solid var(--c-dc);">
                  <div class="small fw-semibold text-danger">จำหน่าย (Discharge)</div>
                  <div class="h5 fw-bold text-danger mb-0 mt-1" id="hourly_dc_val">0</div>
                </div>
              </div>
              <div class="col">
                <div class="p-2 rounded text-center" style="background: var(--c-tin-bg); border: 1.5px solid var(--c-tin);">
                  <div class="small fw-semibold text-primary">ย้ายเข้า (Transfer In)</div>
                  <div class="h5 fw-bold text-primary mb-0 mt-1" id="hourly_tin_val">0</div>
                </div>
              </div>
              <div class="col">
                <div class="p-2 rounded text-center" style="background: var(--c-tout-bg); border: 1.5px solid var(--c-tout);">
                  <div class="small fw-semibold" style="color: var(--c-tout);">ย้ายออก (Transfer Out)</div>
                  <div class="h5 fw-bold mb-0 mt-1" style="color: var(--c-tout);" id="hourly_tout_val">0</div>
                </div>
              </div>
              <div class="col">
                <div class="p-2 rounded text-center" style="background: #f1f5f9; border: 1.5px solid #64748b;">
                  <div class="small fw-semibold text-secondary">เสียชีวิต (Death)</div>
                  <div class="h5 fw-bold text-secondary mb-0 mt-1" id="hourly_death_val">0</div>
                </div>
              </div>
              <div class="col">
                <div class="p-2 rounded text-center" style="background: #fbeef1; border: 1.5px solid #ba1a1a;">
                  <div class="small fw-semibold" style="color: #ba1a1a;">ผู้ป่วยคงพยาบาล</div>
                  <div class="h5 fw-bold mb-0 mt-1" style="color: #ba1a1a;" id="hourly_patient_val">0</div>
                </div>
              </div>
            </div>
          </div>
          <!-- Chart / Timeline -->
          <div class="col-lg-7 col-12 d-flex flex-column">
            <h6 class="fw-bold text-muted mb-2 small text-uppercase" style="letter-spacing: .05em;">ไทม์ไลน์ยอดผู้ป่วยรายชั่วโมง (Hourly Patient Census Timeline)</h6>
            <div class="flex-grow-1 p-2 rounded" style="background: #fafbfd; border: 1px solid var(--border-soft); min-height: 120px;">
              <div id="hourly_timeline_chart_container" class="position-relative h-100 w-100 d-flex align-items-end justify-content-between px-3 pt-4" style="height: 120px !important;">
                <!-- Timeline bars will be dynamically rendered here -->
              </div>
              <div id="hourly_timeline_labels" class="d-flex justify-content-between px-3 mt-1 text-muted" style="font-size: .65rem; font-weight: bold;">
                <!-- Labels will be dynamically rendered here -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Section 1: Patient Movements ──────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">swap_horiz</span>
          การเคลื่อนไหวผู้ป่วย
        </span>
      </div>
      <div class="card-body">
        <div class="movement-balance-panel mb-3">
          <div class="row g-2 align-items-center">
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ยอดยกมา</div>
              <div class="movement-balance-value" id="carried_forward_display">0</div>
            </div>
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ยอดคาดการณ์</div>
              <div class="movement-balance-value" id="movement_expected_display">0</div>
            </div>
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ยอดคงอยู่จริง</div>
              <div class="movement-balance-value" id="movement_actual_display">0</div>
            </div>
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ส่วนต่าง</div>
              <div class="movement-balance-value" id="movement_variance_display">0</div>
            </div>
          </div>
          <div id="movement_balance_status" class="small text-muted mt-2">
            เลือก Ward / วันที่ / เวร เพื่อดึงยอดยกมาจากเวรก่อนหน้า
          </div>
        </div>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-2">

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">รับใหม่ <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Admission">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_admissions" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="admissions" id="admissions" class="stat-input movement-input" min="0" value="<?= old('admissions', 0) ?>" data-field="admissions">
              <div id="reason_container_admissions" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[admissions]" id="reason_admissions" class="form-control form-control-sm discrepancy-reason" placeholder="เหตุผล" style="font-size: 0.7rem; padding: 2px 4px; min-height: 24px;">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">จำหน่าย <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Discharge">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_discharges" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="discharges" id="discharges" class="stat-input movement-input" min="0" value="<?= old('discharges', 0) ?>" data-field="discharges">
              <div id="reason_container_discharges" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[discharges]" id="reason_discharges" class="form-control form-control-sm discrepancy-reason" placeholder="เหตุผล" style="font-size: 0.7rem; padding: 2px 4px; min-height: 24px;">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">ย้ายเข้า <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Transfer In">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_transfers_in" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="transfers_in" id="transfers_in" class="stat-input movement-input" min="0" value="<?= old('transfers_in', 0) ?>" data-field="transfers_in">
              <div id="reason_container_transfers_in" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[transfers_in]" id="reason_transfers_in" class="form-control form-control-sm discrepancy-reason" placeholder="เหตุผล" style="font-size: 0.7rem; padding: 2px 4px; min-height: 24px;">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">ย้ายออก <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Transfer Out">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_transfers_out" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="transfers_out" id="transfers_out" class="stat-input movement-input" min="0" value="<?= old('transfers_out', 0) ?>" data-field="transfers_out">
              <div id="reason_container_transfers_out" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[transfers_out]" id="reason_transfers_out" class="form-control form-control-sm discrepancy-reason" placeholder="เหตุผล" style="font-size: 0.7rem; padding: 2px 4px; min-height: 24px;">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">เสียชีวิต <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Death">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_deaths" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="deaths" id="deaths" class="stat-input movement-input" min="0" value="<?= old('deaths', 0) ?>" data-field="deaths">
              <div id="reason_container_deaths" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[deaths]" id="reason_deaths" class="form-control form-control-sm discrepancy-reason" placeholder="เหตุผล" style="font-size: 0.7rem; padding: 2px 4px; min-height: 24px;">
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── Section 2: Patient Levels ────────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">clinical_notes</span>
          ผู้ป่วยคงอยู่ แยกตาม Level การพยาบาล
          <small class="ms-2 fw-normal">(สามัญ + พิเศษ)</small>
        </span>
        <span class="total-pill">TOTAL: <span id="total_patients_badge">0</span></span>
      </div>
      <div class="card-body">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-2">

          <?php
          $levelMeta = [
            5 => ['class' => 'lv5', 'help' => 'วิกฤต · 12 ชม./คน'],
            4 => ['class' => 'lv4', 'help' => 'หนัก · 7.5 ชม./คน'],
            3 => ['class' => 'lv3', 'help' => 'ปานกลาง · 5.5 ชม./คน'],
            2 => ['class' => 'lv2', 'help' => 'น้อย · 3.5 ชม./คน'],
            1 => ['class' => 'lv1', 'help' => 'ช่วยตัวเองได้ · 1.5 ชม./คน'],
          ];
          foreach ($levelMeta as $level => $meta):
            $generalField = "patients_general_level_{$level}";
            $specialField = "patients_special_level_{$level}";
            $totalField   = "patients_level_{$level}";
            $generalValue = old($generalField, old($totalField, 0));
            $specialValue = old($specialField, 0);
          ?>
            <div class="col">
              <div class="stat-card <?= $meta['class'] ?>">
                <div class="stat-label">Level <?= $level ?> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="<?= esc($meta['help']) ?>">info</span></div>
                <div class="row g-2 align-items-end mt-1">
                  <div class="col-6">
                    <label class="mini-field-label" for="<?= $generalField ?>">สามัญ</label>
                    <input type="number" name="<?= $generalField ?>" id="<?= $generalField ?>"
                      class="stat-input patient-level-type"
                      min="0" value="<?= $generalValue ?>">
                  </div>
                  <div class="col-6">
                    <label class="mini-field-label" for="<?= $specialField ?>">พิเศษ</label>
                    <input type="number" name="<?= $specialField ?>" id="<?= $specialField ?>"
                      class="stat-input patient-level-type"
                      min="0" value="<?= $specialValue ?>">
                  </div>
                </div>
                <div class="level-total-line">รวม <strong id="<?= $totalField ?>_total">0</strong></div>
                <input type="hidden" name="<?= $totalField ?>" id="<?= $totalField ?>"
                  class="patient-level" value="<?= old($totalField, 0) ?>">
              </div>
            </div>
          <?php endforeach; ?>

          <div class="col">
            <div class="stat-card lv-total">
              <div class="stat-label">รวมทั้งหมด</div>
              <input type="text" id="total_patients_display"
                class="stat-input" readonly value="0">
            </div>
            <input type="hidden" name="total_patients" id="total_patients" value="0">
          </div>

        </div>

        <!-- Productivity preview (Afternoon shift only) -->
        <div id="care_hours_preview" class="prod-preview d-none">
          <div class="small text-center opacity-75 mb-2">
            Preview Productivity (คำนวณเมื่อบันทึกครบ 3 เวร)
          </div>
          <div class="row text-center g-0">
            <div class="col border-end prod-divider">
              <div class="prod-stat-label">ชั่วโมงดูแลที่ต้องการ</div>
              <div class="prod-stat-value" style="color: #7dd3fc;" id="preview_care_hrs">0</div>
              <div class="prod-stat-label">ชม.</div>
            </div>
            <div class="col border-end prod-divider">
              <div class="prod-stat-label">ชั่วโมงทำงาน (RN+TN+PN×7)</div>
              <div class="prod-stat-value" style="color: #86efac;" id="preview_work_hrs">0</div>
              <div class="prod-stat-label">ชม.</div>
            </div>
            <div class="col">
              <div class="prod-stat-label">Productivity (shift นี้)</div>
              <div class="prod-stat-value" id="preview_productivity">—</div>
              <div class="prod-stat-label">%</div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Section 3: Nursing Staff ───────────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">medical_services</span>
          บุคลากรพยาบาล
          <small class="ms-2 fw-normal">
            <span class="material-symbols-outlined align-middle" style="font-size:1rem;">calculate</span>
            RN / TN / PN ใช้คำนวณ Productivity
          </small>
        </span>
      </div>
      <div class="card-body">
        <div class="row row-cols-3 row-cols-md-6 g-2">

          <div class="col">
            <div class="stat-card nc-other">
              <div class="stat-label">HW <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="หัวหน้าเวร">info</span></div>
              <input type="number" name="nurses_hw" id="nurses_hw"
                class="stat-input" min="0" value="<?= old('nurses_hw', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-calc">
              <div class="stat-label">RN <span><span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ใช้คำนวณ Productivity">calculate</span> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="พยาบาลวิชาชีพ">info</span></span></div>
              <input type="number" name="nurses_rn" id="nurses_rn"
                class="stat-input nurse-calc" min="0" value="<?= old('nurses_rn', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-calc">
              <div class="stat-label">TN <span><span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ใช้คำนวณ Productivity">calculate</span> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="พยาบาลเทคนิค">info</span></span></div>
              <input type="number" name="nurses_tn" id="nurses_tn"
                class="stat-input nurse-calc" min="0" value="<?= old('nurses_tn', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-calc">
              <div class="stat-label">PN <span><span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ใช้คำนวณ Productivity">calculate</span> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="เจ้าหน้าที่ช่วยพยาบาล">info</span></span></div>
              <input type="number" name="nurses_pn" id="nurses_pn"
                class="stat-input nurse-calc" min="0" value="<?= old('nurses_pn', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other">
              <div class="stat-label">Aide <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ผู้ช่วยเหลือคนไข้">info</span></div>
              <input type="number" name="nurses_aide" id="nurses_aide"
                class="stat-input" min="0" value="<?= old('nurses_aide', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other">
              <div class="stat-label">W <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="พนักงานผู้ป่วย">info</span></div>
              <input type="number" name="nurses_ward" id="nurses_ward"
                class="stat-input" min="0" value="<?= old('nurses_ward', 0) ?>">
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── Section 4: Patients using ward equipment ───────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">medical_information</span>
          ผู้ป่วยที่ใช้อุปกรณ์
        </span>
        <small class="text-muted d-block mt-1">บันทึกเป็นจำนวนผู้ป่วย (คน) ไม่ใช่จำนวนเครื่อง</small>
      </div>
      <div class="card-body">
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-2">
          <?php
          $equipmentFields = [
            'equipment_ventilator' => ['label' => 'เครื่องช่วยหายใจ', 'help' => 'จำนวนผู้ป่วยที่ใช้เครื่องช่วยหายใจในเวรนี้'],
            'equipment_hfnc'       => ['label' => 'High Flow O₂', 'help' => 'จำนวนผู้ป่วยที่ใช้ High Flow O₂ ในเวรนี้ (บันทึกที่เดียว)'],
          ];
          foreach ($equipmentFields as $field => $meta):
          ?>
            <div class="col">
              <div class="stat-card nc-other">
                <div class="stat-label"><?= esc($meta['label']) ?> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="<?= esc($meta['help']) ?>">info</span></div>
                <input type="number" name="<?= $field ?>" id="<?= $field ?>"
                  class="stat-input"
                  min="0" value="<?= old($field, 0) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Section 5: Quality Indicators ─────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">monitor_heart</span>
          ตัวชี้วัดคุณภาพ (Quality Indicators)
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleQI">แสดง/ซ่อน</button>
      </div>
      <div class="card-body" id="qiSection" style="display:none">

        <p class="fw-semibold mb-2 small" style="color: var(--c-l5);">
          <i class="bi bi-virus me-1"></i> Hospital-Acquired Infections (HAI)
        </p>
        <div class="row row-cols-4 row-cols-md-8 g-2 mb-3">
          <?php
          $haiFields = [
            'hai_vap'    => 'VAP',
            'hai_hap'    => 'HAP',
            'hai_uti'    => 'UTI',
            'hai_cauti'  => 'CAUTI',
            'hai_clabsi' => 'CLABSI',
            'hai_ssi'    => 'SSI',
            'hai_bsi'    => 'BSI',
            'hai_mdr'    => 'MDR',
          ];
          foreach ($haiFields as $field => $label):
          ?>
            <div class="col">
              <div class="qi-card qi-hai">
                <div class="qi-card-label"><?= $label ?></div>
                <input type="number" name="qi[<?= $field ?>]"
                  class="qi-input"
                  min="0" value="<?= old("qi[$field]", 0) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <p class="fw-semibold mb-2 small" style="color: var(--c-tin);">
          <i class="bi bi-clipboard-pulse me-1"></i> ภาวะพิเศษ
        </p>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-2">
          <?php
          $specialFields = [
            'new_sepsis'            => 'New Sepsis',
            'end_of_life'           => 'End of Life',
            'palliative_care'       => 'Palliative Care',
            'critical_care_support' => 'Critical Care',
          ];
          foreach ($specialFields as $field => $label):
          ?>
            <div class="col">
              <div class="qi-card qi-special">
                <div class="qi-card-label"><?= $label ?></div>
                <input type="number" name="qi[<?= $field ?>]"
                  class="qi-input"
                  min="0" value="<?= old("qi[$field]", 0) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>

    <!-- ── Notes & Submit ─────────────────────────────────────────────── -->
    <div class="mb-3">
      <label class="form-label small">หมายเหตุ</label>
      <textarea name="notes" class="form-control form-control-sm" rows="2"><?= old('notes') ?></textarea>
    </div>

    <div id="autosave_status" class="text-muted small text-end mb-2" style="min-height:1.2em"></div>

    <div class="d-flex gap-2 justify-content-end form-actions-bar">
      <button type="button" id="btnAutosave" class="btn btn-outline-secondary">
        บันทึกชั่วคราว
      </button>
      <button type="submit" class="btn btn-primary px-4">
        <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">check_circle</span>
        บันทึกถาวร
      </button>
    </div>

    <button type="button" id="btnAutosaveFab" class="quick-save-fab" title="บันทึกชั่วคราว">
      <span class="material-symbols-outlined">save</span>
    </button>

  </form>
</div>

<script>
  const LEVEL_HOURS = {
    5: 12,
    4: 7.5,
    3: 5.5,
    2: 3.5,
    1: 1.5
  };
  const SHIFT_HOURS = 7;
  let carriedForwardPatients = 0;
  let hasCarryForwardSource = false;

  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  function getInt(id) {
    return parseInt(document.getElementById(id)?.value) || 0;
  }

  function hasEnteredPatientLevels() {
    return [5, 4, 3, 2, 1].some(lvl => {
      return getInt(`patients_general_level_${lvl}`) > 0 || getInt(`patients_special_level_${lvl}`) > 0;
    });
  }

  function applyPreviousShiftSnapshot(snapshot, force = false) {
    if (!snapshot || (!force && hasEnteredPatientLevels())) {
      return;
    }

    [5, 4, 3, 2, 1].forEach(lvl => {
      const general = document.getElementById(`patients_general_level_${lvl}`);
      const special = document.getElementById(`patients_special_level_${lvl}`);
      if (general) {
        general.value = parseInt(snapshot[`patients_general_level_${lvl}`], 10) || 0;
      }
      if (special) {
        special.value = parseInt(snapshot[`patients_special_level_${lvl}`], 10) || 0;
      }
    });

    ['equipment_ventilator', 'equipment_hfnc'].forEach(id => {
      const input = document.getElementById(id);
      if (input) {
        input.value = parseInt(snapshot[id], 10) || 0;
      }
    });

    updateTotals();
  }

  function resetMovementInputs() {
    ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(id => {
      const input = document.getElementById(id);
      if (input) {
        input.value = 0;
      }
    });
  }

  function applyCurrentRecord(record) {
    if (!record) {
      return;
    }

    Object.entries(record).forEach(([field, value]) => {
      if (field === 'qi') {
        return;
      }
      const input = document.querySelector(`[name="${field}"]`);
      if (input) {
        input.value = value ?? '';
      }
    });

    if (record.api_discrepancy_reasons) {
        Object.entries(record.api_discrepancy_reasons).forEach(([field, value]) => {
            const input = document.getElementById('reason_' + field);
            if (input) {
                input.value = value ?? '';
            }
        });
    }

    if (record.qi) {
      Object.entries(record.qi).forEach(([field, value]) => {
        const input = document.querySelector(`[name="qi[${field}]"]`);
        if (input) {
          input.value = value ?? 0;
        }
      });
    }

    updateTotals();
  }

  function updateMovementBalance() {
    const expected = Math.max(0, carriedForwardPatients + getInt('admissions') + getInt('transfers_in') - getInt('discharges') - getInt('transfers_out') - getInt('deaths'));
    const actual = getInt('total_patients');
    const variance = actual - expected;

    document.getElementById('carried_forward_display').textContent = carriedForwardPatients;
    document.getElementById('movement_expected_display').textContent = expected;
    document.getElementById('movement_actual_display').textContent = actual;
    document.getElementById('movement_variance_display').textContent = variance;

    const status = document.getElementById('movement_balance_status');
    status.className = 'small mt-2 ' + (variance === 0 ? 'text-success' : 'text-warning');
    if (!hasCarryForwardSource) {
      status.className = 'small text-muted mt-2';
      status.textContent = 'ยังไม่พบเวรก่อนหน้า ระบบใช้ยอดยกมา 0 และถือเป็นข้อมูลตั้งต้น';
    } else if (variance === 0) {
      status.textContent = 'ยอดคงอยู่สัมพันธ์กับยอดยกมาและการเคลื่อนไหวผู้ป่วย';
    } else {
      status.textContent = `ยอดคงอยู่ต่างจากยอดคาดการณ์ ${variance > 0 ? '+' : ''}${variance} คน กรุณาตรวจสอบ movement หรือยอด Level`;
    }
  }

  function updateCarryForwardNote(context = null) {
    const note = document.getElementById('carry_forward_note');
    const text = document.getElementById('carry_forward_note_text');
    if (!note || !text) {
      return;
    }

    if (!context || !context.success) {
      note.classList.add('d-none');
      return;
    }

    note.classList.remove('d-none');
    if (context.has_previous) {
      text.textContent = `ยกยอดมาจากเวร ${context.previous_shift} วันที่ ${context.previous_date} จำนวน ${carriedForwardPatients} คน`;
    } else {
      text.textContent = 'ยังไม่มีเวรก่อนหน้า เวรนี้เริ่มต้นด้วยยอดยกมา 0 คน';
    }
  }

  async function loadMovementContext(forcePopulate = false) {
    const wardId = document.getElementById('ward_id').value;
    const date = document.getElementById('record_date').value;
    const shift = document.getElementById('shift').value;
    const status = document.getElementById('movement_balance_status');

    if (!wardId || !date || !shift) {
      carriedForwardPatients = 0;
      hasCarryForwardSource = false;
      updateCarryForwardNote(null);
      status.className = 'small text-muted mt-2';
      status.textContent = 'เลือก Ward / วันที่ / เวร เพื่อดึงยอดยกมาจากเวรก่อนหน้า';
      updateMovementBalance();
      return;
    }

    status.className = 'small text-muted mt-2';
    status.textContent = 'กำลังดึงยอดยกมา...';
    try {
      const params = new URLSearchParams({
        ward_id: wardId,
        record_date: date,
        shift
      });
      const resp = await fetch(`<?= base_url('census/movement-context') ?>?${params.toString()}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
      });
      const json = await resp.json();
      carriedForwardPatients = json.success ? parseInt(json.carried_forward_patients, 10) || 0 : 0;
      hasCarryForwardSource = Boolean(json.success && json.has_previous);
      updateCarryForwardNote(json);

      if (json.success && json.has_current) {
        applyCurrentRecord(json.current_record);
      }

      if (json.success && !json.has_current && json.has_previous) {
        applyPreviousShiftSnapshot(json.previous_snapshot, forcePopulate);
        if (forcePopulate) {
          resetMovementInputs();
        }
      }
    } catch (e) {
      carriedForwardPatients = 0;
      hasCarryForwardSource = false;
      updateCarryForwardNote(null);
      status.className = 'small text-danger mt-2';
      status.textContent = 'ดึงยอดยกมาไม่สำเร็จ';
      return;
    }
    updateMovementBalance();
  }

  function updateTotals() {
    let total = 0,
      care = 0;
    [5, 4, 3, 2, 1].forEach(lvl => {
      const n = getInt(`patients_general_level_${lvl}`) + getInt(`patients_special_level_${lvl}`);
      const levelInput = document.getElementById(`patients_level_${lvl}`);
      const levelTotal = document.getElementById(`patients_level_${lvl}_total`);
      if (levelInput) {
        levelInput.value = n;
      }
      if (levelTotal) {
        levelTotal.textContent = n;
      }
      total += n;
      care += n * LEVEL_HOURS[lvl];
    });
    document.getElementById('total_patients_display').value = total;
    document.getElementById('total_patients').value = total;
    document.getElementById('total_patients_badge').textContent = total;
    document.getElementById('preview_care_hrs').textContent = care.toFixed(1);
    updateMovementBalance();

    const rn = getInt('nurses_rn'),
      tn = getInt('nurses_tn'),
      pn = getInt('nurses_pn');
    const work = (rn + tn + pn) * SHIFT_HOURS;
    document.getElementById('preview_work_hrs').textContent = work.toFixed(1);

    const shift = document.getElementById('shift').value;
    const preEl = document.getElementById('care_hours_preview');
    if (shift === 'Afternoon') {
      preEl.classList.remove('d-none');
      const pEl = document.getElementById('preview_productivity');
      const prod = work > 0 ? (care * 100 / work) : 0;
      pEl.textContent = work > 0 ? prod.toFixed(2) : '—';
      pEl.className = 'prod-stat-value ' +
        (prod > 100 ? 'text-danger' : prod >= 80 ? 'text-warning' : 'text-success');
    } else {
      preEl.classList.add('d-none');
    }
  }

  // ── Handover Guidelines Logic ──────────────────────────────────
  function drawTimelineChart(timeline) {
    const chartContainer = document.getElementById('hourly_timeline_chart_container');
    const labelsContainer = document.getElementById('hourly_timeline_labels');
    if (!chartContainer || !labelsContainer) return;

    chartContainer.innerHTML = '';
    labelsContainer.innerHTML = '';

    const counts = timeline.map(r => parseInt(r.patient_count) || 0);
    const maxVal = Math.max(...counts, 10);

    timeline.forEach(record => {
      const timeStr = record.record_time || '';
      const hourPart = timeStr.substring(11, 16) || '--:--';
      const patientCount = parseInt(record.patient_count) || 0;

      const heightPct = maxVal > 0 ? (patientCount / maxVal) * 80 : 0;

      const barWrapper = document.createElement('div');
      barWrapper.className = 'd-flex flex-column align-items-center flex-grow-1 h-100 justify-content-end';
      barWrapper.style.minWidth = '0';
      barWrapper.style.padding = '0 2px';

      const valueLabel = document.createElement('span');
      valueLabel.className = 'fw-bold mb-1';
      valueLabel.style.fontSize = '0.7rem';
      valueLabel.style.color = '#1e3a8a';
      valueLabel.textContent = patientCount;

      const bar = document.createElement('div');
      bar.className = 'rounded-top shadow-sm';
      bar.style.width = '70%';
      bar.style.maxWidth = '30px';
      bar.style.height = `${heightPct}%`;
      bar.style.background = 'linear-gradient(to top, #0d9488, #3b82f6)';
      bar.style.transition = 'height 0.4s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s';
      bar.style.cursor = 'pointer';
      
      const details = `รับใหม่: +${record.admissions_today}, จำหน่าย: -${record.discharges_today}, ย้ายเข้า: +${record.moves_in_today}, ย้ายออก: -${record.moves_out_today}, เสียชีวิต: -${record.deaths_today}`;
      bar.title = `เวลา ${hourPart}น.\nผู้ป่วยคงพยาบาล: ${patientCount} คน\n(${details})`;

      bar.addEventListener('mouseenter', () => {
        bar.style.background = 'linear-gradient(to top, #14b8a6, #60a5fa)';
      });
      bar.addEventListener('mouseleave', () => {
        bar.style.background = 'linear-gradient(to top, #0d9488, #3b82f6)';
      });

      barWrapper.appendChild(valueLabel);
      barWrapper.appendChild(bar);
      chartContainer.appendChild(barWrapper);

      const label = document.createElement('span');
      label.className = 'text-center flex-grow-1';
      label.style.minWidth = '0';
      label.style.fontSize = '0.65rem';
      label.textContent = hourPart;
      labelsContainer.appendChild(label);
    });
  }

  function checkDiscrepancy(fieldId) {
    const input = document.getElementById(fieldId);
    const badge = document.getElementById('gl_' + fieldId);
    const container = document.getElementById('reason_container_' + fieldId);
    const reasonInput = document.getElementById('reason_' + fieldId);
    
    if (!input || !badge || !container) return;
    
    if (badge.style.display !== 'none' && badge.dataset.apiValue !== undefined) {
        if (input.value !== '' && parseInt(input.value) !== parseInt(badge.dataset.apiValue)) {
            container.style.display = 'block';
            reasonInput.setAttribute('required', 'required');
        } else {
            container.style.display = 'none';
            reasonInput.removeAttribute('required');
        }
    } else {
        container.style.display = 'none';
        reasonInput.removeAttribute('required');
    }
  }

  function checkAllDiscrepancies() {
      ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(checkDiscrepancy);
  }

  async function loadHourlyGuidelines() {
    const wardId = document.getElementById('ward_id').value;
    const date = document.getElementById('record_date').value;
    const shift = document.getElementById('shift').value;
    const card = document.getElementById('handover_guidelines_card');

    if (!wardId || !date || !shift) {
      if (card) card.classList.add('d-none');
      return;
    }

    try {
      const params = new URLSearchParams({
        ward_id: wardId,
        record_date: date,
        shift
      });
      const resp = await fetch(`<?= base_url('census/hourly-guidelines') ?>?${params.toString()}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
      });
      const json = await resp.json();
      if (json.success && json.timeline && json.timeline.length > 0) {
        if (card) card.classList.remove('d-none');
        
        document.getElementById('hourly_admit_val').textContent = json.totals.admissions;
        document.getElementById('hourly_dc_val').textContent = json.totals.discharges;
        document.getElementById('hourly_tin_val').textContent = json.totals.transfers_in;
        document.getElementById('hourly_tout_val').textContent = json.totals.transfers_out;
        document.getElementById('hourly_death_val').textContent = json.totals.deaths;
        document.getElementById('hourly_patient_val').textContent = json.totals.patient_count;

        ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(f => {
            const badge = document.getElementById('gl_' + f);
            if (badge) {
                badge.textContent = json.totals[f] !== undefined ? json.totals[f] : '-';
                badge.style.display = 'inline-block';
                badge.dataset.apiValue = json.totals[f];
            }
        });
        checkAllDiscrepancies();

        document.getElementById('btn_apply_hourly').dataset.totals = JSON.stringify(json.totals);

        drawTimelineChart(json.timeline);
      } else {
        if (card) card.classList.add('d-none');
        ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(f => {
            const badge = document.getElementById('gl_' + f);
            if (badge) badge.style.display = 'none';
        });
        checkAllDiscrepancies();
      }
    } catch (e) {
      console.error('Failed to load hourly guidelines:', e);
      if (card) card.classList.add('d-none');
    }
  }

  document.getElementById('btn_apply_hourly')?.addEventListener('click', function() {
    const totalsData = this.dataset.totals;
    if (!totalsData) return;

    try {
      const totals = JSON.parse(totalsData);
      
      const inputs = {
        'admissions': totals.admissions,
        'discharges': totals.discharges,
        'transfers_in': totals.transfers_in,
        'transfers_out': totals.transfers_out,
        'deaths': totals.deaths
      };

      Object.entries(inputs).forEach(([id, val]) => {
        const input = document.getElementById(id);
        if (input) {
          input.value = val;
          const parent = input.closest('.stat-card');
          if (parent) {
            parent.style.transition = 'none';
            parent.style.transform = 'scale(1.05)';
            parent.style.boxShadow = '0 0 10px rgba(59, 130, 246, 0.5)';
            parent.style.borderColor = 'rgba(59, 130, 246, 0.8)';
            setTimeout(() => {
              parent.style.transition = 'all 0.3s ease';
              parent.style.transform = '';
              parent.style.boxShadow = '';
              parent.style.borderColor = '';
            }, 800);
          }
        }
      });

      updateMovementBalance();
      
      const form = document.getElementById('censusForm');
      if (form) {
        form.dispatchEvent(new Event('input'));
      }
    } catch (e) {
      console.error('Error applying hourly totals:', e);
    }
  });

  document.querySelectorAll('.patient-level-type, .nurse-calc').forEach(el => {
    el.addEventListener('input', updateTotals);
  });
  document.getElementById('shift').addEventListener('change', updateTotals);
  document.querySelectorAll('.movement-input').forEach(el => {
    el.addEventListener('input', () => {
        updateMovementBalance();
        checkDiscrepancy(el.dataset.field);
    });
  });
  ['ward_id', 'record_date', 'shift'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', () => {
      loadMovementContext(true);
      loadHourlyGuidelines();
    });
  });
  loadMovementContext(false);
  loadHourlyGuidelines();
  updateTotals();

  document.getElementById('toggleQI').addEventListener('click', () => {
    const s = document.getElementById('qiSection');
    s.style.display = s.style.display === 'none' ? '' : 'none';
  });

  document.getElementById('censusForm').addEventListener('submit', function(e) {
    const variance = parseInt(document.getElementById('movement_variance_display')?.textContent || '0', 10) || 0;
    if (hasCarryForwardSource && variance !== 0 && !confirm('ยอดคงอยู่ไม่ตรงกับยอดคาดการณ์จากยอดยกมาและ movement ต้องการบันทึกต่อหรือไม่?')) {
      e.preventDefault();
      return;
    }

  });

  // ── Auto-save ──────────────────────────────────────────────────────────────
  let saveTimer = null;
  const statusEl = document.getElementById('autosave_status');

  async function doAutosave() {
    const wardId = document.getElementById('ward_id').value;
    const date = document.getElementById('record_date').value;
    const shift = document.getElementById('shift').value;
    if (!wardId || !date || !shift) {
      statusEl.textContent = 'กรุณาเลือก Ward / วันที่ / เวร ก่อน';
      return;
    }
    statusEl.textContent = 'กำลังบันทึก…';
    try {
      const resp = await fetch('<?= base_url('census/autosave') ?>', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(document.getElementById('censusForm')),
      });
      const json = await resp.json();
      statusEl.textContent = json.success ?
        `บันทึกอัตโนมัติ ${new Date().toLocaleTimeString('th-TH')}` :
        `ผิดพลาด: ${json.message ?? 'Unknown'}`;
    } catch (e) {
      statusEl.textContent = 'Auto-save ล้มเหลว';
    }
  }

  document.getElementById('censusForm').addEventListener('input', () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(doAutosave, 1800);
  });
  document.getElementById('btnAutosave').addEventListener('click', doAutosave);
  document.getElementById('btnAutosaveFab').addEventListener('click', doAutosave);
</script>

<?= $this->endSection() ?>