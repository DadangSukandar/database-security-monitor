@once
<style>
    :root {
        color-scheme: dark;
        --g-bg: #080d16;
        --g-surface: #101827;
        --g-surface-raised: #162235;
        --g-surface-soft: #1d2b40;
        --g-border: #34445b;
        --g-border-soft: #243247;
        --g-text: #f4f7fb;
        --g-muted: #a6b4c8;
        --g-blue: #0f62fe;
        --g-cyan: #33b1ff;
        --g-success: #42be65;
        --g-warning: #f1c21b;
        --g-danger: #fa4d56;
    }

    html, body {
        min-height: 100%;
        background: var(--g-bg) !important;
        color: var(--g-text) !important;
        font-family: Inter, "IBM Plex Sans", "Segoe UI", Arial, sans-serif !important;
    }

    body::before {
        position: fixed;
        inset: 0;
        z-index: -1;
        background:
            radial-gradient(circle at 82% 8%, rgba(15, 98, 254, .13), transparent 30%),
            linear-gradient(145deg, #080d16 0%, #0d1522 55%, #101a2a 100%);
        content: "";
    }

    .app, body > .d-flex {
        min-height: 100vh;
        background: transparent !important;
    }

    .main, .main-content {
        min-width: 0;
        background: transparent !important;
        color: var(--g-text) !important;
    }

    .topbar {
        min-height: 58px !important;
        border-bottom: 1px solid var(--g-border-soft) !important;
        background: rgba(9, 15, 25, .96) !important;
        color: var(--g-text) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
    }

    .topbar-title, .topbar strong {
        color: var(--g-text) !important;
        letter-spacing: .01em;
    }

    .topbar-status, .topbar .text-muted {
        color: var(--g-muted) !important;
    }

    .content, .main-content > .p-4 {
        padding: 28px !important;
    }

    h1, h2, h3, h4, h5, h6, strong, label {
        color: var(--g-text);
    }

    p, small, .muted, .text-muted {
        color: var(--g-muted) !important;
    }

    a:not(.nav-item):not(.btn):not(.page-link) {
        color: var(--g-cyan);
    }

    .card,
    .panel,
    .stat,
    .stat-card,
    .audit-card,
    .risk-card,
    .score-card,
    .score-panel,
    .detail-card,
    .information-card,
    .content-card,
    .data-card,
    .chart-card,
    .metric-card,
    .info-card,
    .form-card,
    .filter-card,
    .filter-box,
    .table-box,
    .table-card,
    .list-card,
    .summary-card,
    .padded-card,
    .modal-content,
    .offcanvas,
    .dropdown-menu,
    .bg-white,
    [style*="background:#fff"],
    [style*="background: #fff"],
    [style*="background:#ffffff"],
    [style*="background: #ffffff"],
    [style*="background:white"],
    [style*="background: white"] {
        border-color: var(--g-border) !important;
        background: linear-gradient(145deg, rgba(26, 39, 59, .98), rgba(15, 24, 38, .98)) !important;
        color: var(--g-text) !important;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .17);
    }

    .card-header,
    .card-footer,
    .card-body,
    .panel-header,
    .panel-body,
    .modal-header,
    .modal-footer {
        border-color: var(--g-border-soft) !important;
        background: transparent !important;
        color: var(--g-text) !important;
    }

    .list-group-item,
    .accordion-item,
    .accordion-button {
        border-color: var(--g-border-soft) !important;
        background: var(--g-surface) !important;
        color: var(--g-text) !important;
    }

    .bg-light,
    .alert-light {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
    }

    .border,
    .border-top,
    .border-end,
    .border-bottom,
    .border-start {
        border-color: var(--g-border-soft) !important;
    }

    .progress {
        background: #2b3950 !important;
    }

    .progress-bar {
        color: #fff !important;
    }

    .table-light,
    .table-light > tr,
    .table-light > tr > th,
    .table-light > tr > td {
        --bs-table-bg: #25344a;
        --bs-table-color: var(--g-text);
        border-color: var(--g-border) !important;
        background: #25344a !important;
        color: var(--g-text) !important;
    }

    [style*="background:#f8f9fa"],
    [style*="background: #f8f9fa"],
    [style*="background:#f8fafc"],
    [style*="background: #f8fafc"],
    [style*="background:#f1f5f9"],
    [style*="background: #f1f5f9"],
    [style*="background:#eff6ff"],
    [style*="background: #eff6ff"],
    [style*="background:#e9ecef"],
    [style*="background: #e9ecef"],
    [style*="background:#e2e8f0"],
    [style*="background: #e2e8f0"] {
        border-color: var(--g-border-soft) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
    }

    .critical-stat,
    .sla-breached,
    .severity-critical,
    .status-open,
    .badge-critical,
    .badge-danger,
    [style*="background:#f8d7da"],
    [style*="background: #f8d7da"],
    [style*="background:#fee2e2"],
    [style*="background: #fee2e2"],
    [style*="background:#fff1f2"],
    [style*="background: #fff1f2"],
    [style*="background:#fff7f7"],
    [style*="background: #fff7f7"],
    [style*="background:#fef2f2"],
    [style*="background: #fef2f2"],
    [style*="background:#fff5f5"],
    [style*="background: #fff5f5"] {
        border-color: rgba(250, 77, 86, .5) !important;
        background: rgba(218, 30, 40, .16) !important;
        color: #ffb3b8 !important;
    }

    .high-stat,
    .sla-warning,
    .severity-high,
    .severity-medium,
    .badge-high,
    .badge-medium,
    [style*="background:#ffe5d0"],
    [style*="background: #ffe5d0"],
    [style*="background:#fff3cd"],
    [style*="background: #fff3cd"],
    [style*="background:#fffbeb"],
    [style*="background: #fffbeb"],
    [style*="background:#fff8e5"],
    [style*="background: #fff8e5"] {
        border-color: rgba(241, 194, 27, .5) !important;
        background: rgba(241, 194, 27, .12) !important;
        color: #fddc69 !important;
    }

    .resolved-stat,
    .severity-low,
    .status-resolved,
    .status-completed,
    .badge-low,
    .badge-success,
    [style*="background:#d1e7dd"],
    [style*="background: #d1e7dd"],
    [style*="background:#d1fae5"],
    [style*="background: #d1fae5"],
    [style*="background:#dcfce7"],
    [style*="background: #dcfce7"],
    [style*="background:#f0fdf4"],
    [style*="background: #f0fdf4"],
    [style*="background:#f0fff4"],
    [style*="background: #f0fff4"] {
        border-color: rgba(66, 190, 101, .5) !important;
        background: rgba(25, 128, 56, .16) !important;
        color: #a7f0ba !important;
    }

    [style*="background:#cff4fc"],
    [style*="background: #cff4fc"],
    .status-scanning,
    .badge-info,
    .icon-box.info {
        border-color: rgba(51, 177, 255, .5) !important;
        background: rgba(15, 98, 254, .16) !important;
        color: #a6c8ff !important;
    }

    [style*="background:#e2e3e5"],
    [style*="background: #e2e3e5"],
    .status-ignored,
    .badge-secondary,
    .icon-box.neutral {
        border-color: var(--g-border) !important;
        background: #26364b !important;
        color: #dce5f2 !important;
    }

    [style*="color:#212529"], [style*="color: #212529"],
    [style*="color:#0f172a"], [style*="color: #0f172a"],
    [style*="color:#495057"], [style*="color: #495057"] {
        color: var(--g-text) !important;
    }

    [style*="color:#6c757d"], [style*="color: #6c757d"],
    [style*="color:#64748b"], [style*="color: #64748b"] {
        color: var(--g-muted) !important;
    }

    .text-dark,
    .card-title,
    .card-text,
    .stat-label,
    .stat-title,
    .panel-title,
    .section-title {
        color: var(--g-text) !important;
    }

    table {
        width: 100%;
        color: var(--g-text) !important;
        border-color: var(--g-border-soft) !important;
    }

    thead, thead tr, th {
        border-color: var(--g-border) !important;
        background: #25344a !important;
        color: #f7f9fc !important;
    }

    td, tbody tr {
        border-color: var(--g-border-soft) !important;
        background: rgba(14, 23, 37, .72) !important;
        color: #dce5f2 !important;
    }

    tbody tr:hover td {
        background: #1b2b42 !important;
    }

    input, select, textarea {
        border: 1px solid var(--g-border) !important;
        background: #0b1320 !important;
        color: var(--g-text) !important;
        box-shadow: none !important;
    }

    input::placeholder, textarea::placeholder {
        color: #7889a0 !important;
    }

    select option,
    select optgroup {
        background: #0b1320 !important;
        color: var(--g-text) !important;
    }

    select option:checked {
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    select option:disabled {
        color: #7889a0 !important;
    }

    input:focus, select:focus, textarea:focus {
        border-color: var(--g-cyan) !important;
        outline: 2px solid rgba(51, 177, 255, .18) !important;
    }

    input[type="checkbox"]:checked,
    input[type="radio"]:checked,
    .form-check-input:checked {
        border-color: var(--g-blue) !important;
        background-color: var(--g-blue) !important;
    }

    .btn-primary, [style*="background:#2563eb"], [style*="background: #2563eb"] {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    .btn:not(.btn-primary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info):not(.btn-dark) {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
    }

    .btn:not(.btn-primary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info):not(.btn-dark):hover,
    .btn:not(.btn-primary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info):not(.btn-dark):focus-visible {
        border-color: var(--g-cyan) !important;
        background: #263a56 !important;
        color: #fff !important;
    }

    .btn-primary:hover {
        filter: brightness(1.12);
    }

    .btn-light,
    .btn-outline-secondary {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
    }

    .main-content a[style*="background:#212529"],
    .main-content a[style*="background: #212529"] {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    .main-content a[style*="background:#fff"],
    .main-content a[style*="background: #fff"],
    .main-content a[style*="background:#ffffff"],
    .main-content a[style*="background: #ffffff"] {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
        box-shadow: none !important;
    }

    .main-content a[style*="background:#fff"]:hover,
    .main-content a[style*="background: #fff"]:hover,
    .main-content a[style*="background:#ffffff"]:hover,
    .main-content a[style*="background: #ffffff"]:hover {
        border-color: var(--g-cyan) !important;
        background: #263a56 !important;
        color: #fff !important;
    }

    .main-content button[style*="background:#fff"],
    .main-content button[style*="background: #fff"],
    .main-content button[style*="background:#ffffff"],
    .main-content button[style*="background: #ffffff"],
    .main-content a[style*="border:1px solid #dee2e6"],
    .main-content a[style*="border: 1px solid #dee2e6"] {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
        box-shadow: none !important;
    }

    .main-content button[style*="background:#fff"]:hover,
    .main-content button[style*="background: #fff"]:hover,
    .main-content button[style*="background:#ffffff"]:hover,
    .main-content button[style*="background: #ffffff"]:hover,
    .main-content a[style*="border:1px solid #dee2e6"]:hover,
    .main-content a[style*="border: 1px solid #dee2e6"]:hover {
        border-color: var(--g-cyan) !important;
        background: #263a56 !important;
        color: #fff !important;
    }

    .main-content button[style*="background:#212529"],
    .main-content button[style*="background: #212529"] {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    .page-header, .dashboard-header, .section-heading {
        border-bottom-color: var(--g-border-soft) !important;
    }

    .page-title, .dashboard-header h1 {
        color: var(--g-text) !important;
        font-weight: 500 !important;
        letter-spacing: -.02em;
    }

    .eyebrow {
        color: var(--g-cyan) !important;
        letter-spacing: .12em !important;
    }

    .score-progress, .bar-track {
        background: #2b3950 !important;
    }

    pre, code {
        border-color: var(--g-border) !important;
        background: #070c14 !important;
        color: #d7e7ff !important;
    }

    .alert-success {
        border-color: rgba(66, 190, 101, .5) !important;
        background: rgba(25, 128, 56, .2) !important;
        color: #a7f0ba !important;
    }

    .alert-danger {
        border-color: rgba(250, 77, 86, .5) !important;
        background: rgba(218, 30, 40, .2) !important;
        color: #ffb3b8 !important;
    }

    .alert-warning {
        border-color: rgba(241, 194, 27, .5) !important;
        background: rgba(241, 194, 27, .12) !important;
        color: #fddc69 !important;
    }

    .alert-info {
        border-color: rgba(51, 177, 255, .5) !important;
        background: rgba(15, 98, 254, .16) !important;
        color: #a6c8ff !important;
    }

    .security-audit-page [style*="border:1px solid #ddd"],
    .security-audit-page [style*="border: 1px solid #ddd"],
    .security-audit-page [style*="border-bottom:1px solid #ddd"],
    .security-audit-page [style*="border-bottom: 1px solid #ddd"],
    .security-audit-page [style*="border-bottom:1px solid #eee"],
    .security-audit-page [style*="border-bottom: 1px solid #eee"] {
        border-color: var(--g-border-soft) !important;
    }

    .security-audit-page [style*="color:#333"],
    .security-audit-page [style*="color: #333"],
    .security-audit-page [style*="color:#475569"],
    .security-audit-page [style*="color: #475569"] {
        color: var(--g-text) !important;
    }

    .security-audit-page a[style*="border:1px solid #aaa"],
    .security-audit-page a[style*="border: 1px solid #aaa"] {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
        box-shadow: none !important;
    }

    .security-audit-page a[style*="border:1px solid #aaa"]:hover,
    .security-audit-page a[style*="border: 1px solid #aaa"]:hover {
        border-color: var(--g-cyan) !important;
        background: #263a56 !important;
        color: #fff !important;
    }

    .security-audit-page .status {
        border: 1px solid var(--g-border) !important;
        background: #26364b !important;
        color: #dce5f2 !important;
    }

    .security-audit-page hr {
        border-color: var(--g-border-soft) !important;
        opacity: 1;
    }

    .security-alerts-page {
        color: var(--g-text) !important;
    }

    .security-alerts-page [style*="border:1px solid #dee2e6"],
    .security-alerts-page [style*="border: 1px solid #dee2e6"],
    .security-alerts-page [style*="border-top:1px solid #dee2e6"],
    .security-alerts-page [style*="border-top: 1px solid #dee2e6"],
    .security-alerts-page [style*="border-top:1px solid #eee"],
    .security-alerts-page [style*="border-top: 1px solid #eee"] {
        border-color: var(--g-border-soft) !important;
    }

    .security-alerts-page .status-acknowledged,
    .security-alerts-page .sla-due_soon {
        border: 1px solid rgba(241, 194, 27, .45) !important;
        background: rgba(241, 194, 27, .12) !important;
        color: #fddc69 !important;
    }

    .security-alerts-page .sla-breached {
        border: 1px solid rgba(250, 77, 86, .45) !important;
        background: rgba(218, 30, 40, .16) !important;
        color: #ffb3b8 !important;
    }

    .security-alerts-page .sla-met {
        border: 1px solid rgba(66, 190, 101, .45) !important;
        background: rgba(25, 128, 56, .16) !important;
        color: #a7f0ba !important;
    }

    .security-alerts-page .sla-not_applicable {
        border: 1px solid rgba(51, 177, 255, .45) !important;
        background: rgba(15, 98, 254, .16) !important;
        color: #a6c8ff !important;
    }

    .dashboard-page {
        color: var(--g-text) !important;
    }

    .dashboard-page .score-circle {
        background: var(--g-surface) !important;
        box-shadow: inset 0 0 32px rgba(51, 177, 255, .08), 0 12px 30px rgba(0, 0, 0, .18);
    }

    .dashboard-page .score-number,
    .dashboard-page .score-level,
    .dashboard-page .risk-card .number,
    .dashboard-page .panel-title {
        color: var(--g-text) !important;
    }

    .dashboard-page .score-max,
    .dashboard-page .card-label,
    .dashboard-page .card-description,
    .dashboard-page .panel-subtitle,
    .dashboard-page .empty {
        color: var(--g-muted) !important;
    }

    .dashboard-page .risk-critical {
        border-color: rgba(250, 77, 86, .42) !important;
        background: rgba(218, 30, 40, .14) !important;
    }

    .dashboard-page .risk-high {
        border-color: rgba(255, 131, 137, .36) !important;
        background: rgba(218, 30, 40, .09) !important;
    }

    .dashboard-page .risk-medium {
        border-color: rgba(241, 194, 27, .42) !important;
        background: rgba(241, 194, 27, .11) !important;
    }

    .dashboard-page .risk-low {
        border-color: rgba(66, 190, 101, .42) !important;
        background: rgba(25, 128, 56, .13) !important;
    }

    .dashboard-page .badge-active {
        border: 1px solid rgba(66, 190, 101, .42) !important;
        background: rgba(25, 128, 56, .16) !important;
        color: #a7f0ba !important;
    }

    .dashboard-page .panel-header,
    .dashboard-page td {
        border-color: var(--g-border-soft) !important;
    }

    .pagination .page-link {
        border-color: var(--g-border) !important;
        background: var(--g-surface) !important;
        color: var(--g-cyan) !important;
    }

    .pagination .active > .page-link {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background: #0b111c; }
    ::-webkit-scrollbar-thumb { border: 2px solid #0b111c; border-radius: 8px; background: #41516a; }

    @media (max-width: 760px) {
        .content, .main-content > .p-4 { padding: 18px !important; }
    }
</style>
@endonce
