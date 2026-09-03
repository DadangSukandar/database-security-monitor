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
    /* =========================================================
    SECURITY INCIDENTS
    ========================================================= */

    .guard-page {
        padding: 22px;
    }

    .guard-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
    }

    .guard-page-header h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #f4f7fb;
    }

    .guard-page-header p {
        margin: 5px 0 0;
        font-size: 12px;
        color: #9fb1c8;
    }

    .guard-count {
        padding: 7px 12px;
        border: 1px solid #263a55;
        border-radius: 20px;
        background: #17263a;
        color: #d9e7f7;
        font-size: 11px;
        font-weight: 700;
    }

    .guard-card {
        background: #142238;
        border: 1px solid #20344f;
        border-radius: 8px;
        overflow: hidden;
    }

    .guard-section {
        margin-top: 16px;
    }

    .guard-card-header {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 16px 18px;
        border-bottom: 1px solid #20344f;
    }

    .guard-card-header h2 {
        margin: 0;
        font-size: 15px;
        color: #f4f7fb;
    }

    .guard-card-header p {
        margin: 4px 0 0;
        color: #8fa5bf;
        font-size: 11px;
    }

    .guard-table-wrap {
        overflow-x: auto;
    }

    .guard-table {
        width: 100%;
        border-collapse: collapse;
    }

    .guard-table th {
        padding: 11px 14px;
        background: #101d30;
        border-bottom: 1px solid #263a55;
        color: #8fa5bf;
        font-size: 10px;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .guard-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #20344f;
        color: #d8e3ef;
        font-size: 12px;
        vertical-align: middle;
    }

    .guard-table tbody tr:hover {
        background: #182a43;
    }

    .guard-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .guard-primary {
        color: #f4f7fb;
        font-weight: 600;
    }

    .guard-link {
        color: #58b8ff;
        text-decoration: none;
    }

    .guard-link:hover {
        text-decoration: underline;
    }

    .guard-incident-number {
        font-weight: 700;
        white-space: nowrap;
    }

    .guard-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
    }

    .severity-critical {
        background: rgba(218, 30, 40, .18);
        color: #ff8389;
    }

    .severity-high {
        background: rgba(255, 131, 43, .16);
        color: #ffb784;
    }

    .severity-medium {
        background: rgba(241, 194, 27, .15);
        color: #f1c21b;
    }

    .severity-low {
        background: rgba(66, 190, 101, .15);
        color: #6fdc8c;
    }

    .status-open {
        background: rgba(218, 30, 40, .15);
        color: #ff8389;
    }

    .status-acknowledged {
        background: rgba(69, 137, 255, .15);
        color: #78a9ff;
    }

    .status-investigating {
        background: rgba(190, 149, 255, .15);
        color: #be95ff;
    }

    .status-contained {
        background: rgba(0, 157, 154, .16);
        color: #3ddbd9;
    }

    .status-resolved,
    .status-closed {
        background: rgba(66, 190, 101, .15);
        color: #6fdc8c;
    }

    .guard-breadcrumb {
        display: flex;
        gap: 7px;
        margin-bottom: 8px;
        color: #8fa5bf;
        font-size: 11px;
    }

    .guard-breadcrumb a {
        color: #58b8ff;
        text-decoration: none;
    }

    .guard-header-badges {
        display: flex;
        gap: 7px;
    }

    .guard-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .guard-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .guard-info-item {
        padding: 14px 18px;
        border-bottom: 1px solid #20344f;
    }

    .guard-info-item:nth-child(odd) {
        border-right: 1px solid #20344f;
    }

    .guard-info-item span,
    .guard-label {
        display: block;
        margin-bottom: 5px;
        color: #8fa5bf;
        font-size: 10px;
        text-transform: uppercase;
    }

    .guard-info-item strong {
        color: #f4f7fb;
        font-size: 12px;
    }

    .guard-description {
        padding: 18px;
        color: #d8e3ef;
        font-size: 12px;
        line-height: 1.7;
        white-space: pre-wrap;
    }

    .guard-ownership {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 18px;
    }

    .guard-owner-name {
        color: #f4f7fb;
        font-size: 14px;
        font-weight: 700;
    }

    .guard-muted {
        margin-top: 4px;
        color: #8fa5bf;
        font-size: 11px;
    }

    .guard-lifecycle {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        padding: 20px 18px;
    }

    .guard-lifecycle-step {
        position: relative;
        padding-left: 18px;
    }

    .guard-lifecycle-step::before {
        content: "";
        position: absolute;
        top: 5px;
        left: 5px;
        right: -5px;
        height: 1px;
        background: #304762;
    }

    .guard-lifecycle-step:last-child::before {
        display: none;
    }

    .guard-lifecycle-dot {
        position: absolute;
        z-index: 2;
        top: 1px;
        left: 0;
        width: 11px;
        height: 11px;
        border: 2px solid #60758f;
        border-radius: 50%;
        background: #142238;
    }

    .guard-lifecycle-step.complete .guard-lifecycle-dot {
        border-color: #42be65;
        background: #42be65;
    }

    .guard-lifecycle-step strong {
        display: block;
        color: #e5edf7;
        font-size: 10px;
    }

    .guard-lifecycle-step span {
        display: block;
        margin-top: 4px;
        color: #8fa5bf;
        font-size: 9px;
    }

    .guard-empty {
        padding: 35px !important;
        text-align: center !important;
        color: #8fa5bf !important;
    }

    .guard-pagination {
        padding: 14px 18px;
        border-top: 1px solid #20344f;
    }

    @media (max-width: 1000px) {
        .guard-grid {
            grid-template-columns: 1fr;
        }

        .guard-lifecycle {
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
    }

    @media (max-width: 650px) {
        .guard-page {
            padding: 14px;
        }

        .guard-page-header,
        .guard-ownership {
            flex-direction: column;
        }

        .guard-info-grid {
            grid-template-columns: 1fr;
        }

        .guard-info-item:nth-child(odd) {
            border-right: 0;
        }

        .guard-lifecycle {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .guard-lifecycle-step::before {
            display: none;
        }
    }

    .incident-actions {
        padding: 20px;
    }

    .incident-action-button {
        min-height: 38px;
        padding: 0 18px;
        border: 1px solid #4589ff;
        border-radius: 2px;
        background: #0f62fe;
        color: #ffffff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .incident-action-button:hover {
        background: #0353e9;
    }

    .incident-resolve-form {
        max-width: 720px;
    }

    .incident-textarea {
        display: block;
        width: 100%;
        margin-top: 8px;
        margin-bottom: 12px;
        padding: 12px;
        border: 1px solid #525252;
        background: #161616;
        color: #f4f4f4;
        font: inherit;
        resize: vertical;
        box-sizing: border-box;
    }

    .incident-textarea:focus {
        outline: 2px solid #0f62fe;
        outline-offset: -2px;
    }

    .incident-field-error {
        margin: -4px 0 12px;
        color: #ff8389;
        font-size: 13px;
    }

    .incident-terminal-state {
        padding: 14px;
        border-left: 3px solid #42be65;
        background: rgba(66, 190, 101, 0.08);
        color: #c6c6c6;
    }

    .incident-flash {
        margin-bottom: 16px;
        padding: 12px 16px;
        border-left: 3px solid;
    }

    .incident-flash-success {
        border-color: #42be65;
        background: rgba(66, 190, 101, 0.08);
        color: #a7f0ba;
    }

    .incident-flash-error {
        border-color: #fa4d56;
        background: rgba(250, 77, 86, 0.08);
        color: #ffb3b8;
    }

    .incident-history {
        padding: 4px 20px 20px;
    }

    .incident-history-item {
        display: flex;
        gap: 14px;
        min-height: 90px;
    }

    .incident-history-marker {
        display: flex;
        width: 14px;
        flex-direction: column;
        align-items: center;
    }

    .incident-history-dot {
        width: 10px;
        height: 10px;
        margin-top: 7px;
        border: 2px solid #78a9ff;
        border-radius: 50%;
        background: #161616;
    }

    .incident-history-line {
        width: 1px;
        flex: 1;
        margin-top: 4px;
        background: #393939;
    }

    .incident-history-item:last-child
    .incident-history-line {
        display: none;
    }

    .incident-history-content {
        flex: 1;
        padding-bottom: 22px;
    }

    .incident-history-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        color: #f4f4f4;
    }

    .incident-history-header span {
        color: #8d8d8d;
        font-size: 12px;
    }

    .incident-history-transition {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
    }

    .incident-history-arrow {
        color: #8d8d8d;
    }

    .incident-history-meta {
        margin-top: 10px;
        color: #8d8d8d;
        font-size: 13px;
    }

    .incident-history-meta strong {
        color: #c6c6c6;
    }

    .incident-history-note {
        margin-top: 10px;
        padding: 10px 12px;
        border-left: 2px solid #525252;
        background: #1f1f1f;
        color: #c6c6c6;
        white-space: pre-wrap;
    }

    @media (max-width: 768px) {
        .incident-history-header {
            flex-direction: column;
            gap: 4px;
        }
    }

    .incident-ownership-controls {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #393939;
    }

    .incident-assignment-form {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .incident-assignment-field {
        width: min(420px, 100%);
    }

    .incident-select {
        display: block;
        width: 100%;
        height: 40px;
        margin-top: 8px;
        padding: 0 40px 0 12px;
        border: 1px solid #525252;
        border-radius: 0;
        background: #161616;
        color: #f4f4f4;
        font: inherit;
    }

    .incident-select:focus {
        outline: 2px solid #0f62fe;
        outline-offset: -2px;
    }

    .incident-assignment-actions {
        display: flex;
        align-items: center;
    }

    .incident-unassign-form {
        margin-top: 12px;
    }

    .incident-secondary-button {
        min-height: 38px;
        padding: 0 18px;
        border: 1px solid #8d8d8d;
        background: transparent;
        color: #f4f4f4;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .incident-secondary-button:hover {
        background: #393939;
    }

    .incident-assignment-empty {
        padding: 12px 14px;
        border-left: 3px solid #f1c21b;
        background: rgba(241, 194, 27, 0.08);
        color: #c6c6c6;
    }

    @media (max-width: 768px) {
        .incident-assignment-form {
            align-items: stretch;
            flex-direction: column;
        }

        .incident-assignment-actions,
        .incident-assignment-actions
            .incident-action-button {
            width: 100%;
        }
    }

    .incident-investigation-description {
        margin: 0 0 18px;
        color: #a8a8a8;
        font-size: 14px;
        line-height: 1.55;
    }

    .incident-investigation-form {
        width: 100%;
    }

    .incident-investigation-field {
        width: 100%;
    }

    .incident-investigation-textarea {
        display: block;
        width: 100%;
        min-height: 130px;
        margin-top: 8px;
        padding: 12px 14px;
        resize: vertical;
        border: 1px solid #525252;
        border-radius: 0;
        background: #161616;
        color: #f4f4f4;
        font: inherit;
        line-height: 1.5;
    }

    .incident-investigation-textarea::placeholder {
        color: #6f6f6f;
    }

    .incident-investigation-textarea:focus {
        outline: 2px solid #0f62fe;
        outline-offset: -2px;
        border-color: transparent;
    }

    .incident-investigation-meta {
        margin-top: 7px;
        color: #8d8d8d;
        font-size: 12px;
        line-height: 1.4;
    }

    .incident-investigation-actions {
        display: flex;
        justify-content: flex-start;
        margin-top: 14px;
    }

    .incident-field-error {
        margin-top: 8px;
        color: #ff8389;
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .incident-investigation-actions,
        .incident-investigation-actions
            .incident-action-button {
            width: 100%;
        }
    }

    .guard-filter-card {
        margin-bottom: 18px;
        padding: 18px;
    }

    .guard-filter-form {
        display: grid;
        grid-template-columns:
            minmax(260px, 2fr)
            minmax(150px, 1fr)
            minmax(150px, 1fr)
            minmax(180px, 1fr)
            auto;
        gap: 14px;
        align-items: end;
    }

    .guard-filter-search,
    .guard-filter-field {
        min-width: 0;
    }

    .guard-filter-search label,
    .guard-filter-field label {
        display: block;
        margin-bottom: 7px;
        color: var(--g-muted);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .guard-filter-search input,
    .guard-filter-field select {
        width: 100%;
        min-height: 42px;
        padding: 9px 11px;
        border: 1px solid var(--g-border) !important;
        border-radius: 4px;
        background: #0b1320 !important;
        color: var(--g-text) !important;
        font: inherit;
        outline: none;
        box-shadow: none !important;
    }

    .guard-filter-search input:focus,
    .guard-filter-field select:focus {
        border-color: var(--g-cyan) !important;
        outline: 2px solid rgba(51, 177, 255, .18) !important;
        box-shadow: none !important;
    }

    .guard-filter-search input::placeholder {
        color: #7889a0 !important;
    }

    .guard-filter-field select option {
        background: #0b1320;
        color: var(--g-text);
    }

    .guard-filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
    }

    .guard-filter-actions .guard-btn,
    .guard-page-header .guard-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0 16px;
        border: 1px solid transparent;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        text-decoration: none !important;
        white-space: nowrap;
        cursor: pointer;
        transition:
            background-color .15s ease,
            border-color .15s ease,
            color .15s ease;
    }

    .guard-filter-actions .guard-btn-primary,
    .guard-page-header .guard-btn-primary {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    .guard-filter-actions .guard-btn-primary:hover,
    .guard-page-header .guard-btn-primary:hover {
        border-color: var(--g-cyan) !important;
        background: #0353e9 !important;
        color: #fff !important;
    }

    .guard-filter-actions .guard-btn-primary:focus,
    .guard-page-header .guard-btn-primary:focus {
        outline: 2px solid var(--g-cyan);
        outline-offset: 2px;
    }

    .guard-filter-actions .guard-btn-secondary,
    .guard-page-header .guard-btn-secondary {
        border-color: var(--g-border) !important;
        background: var(--g-surface-raised) !important;
        color: var(--g-text) !important;
    }

    .guard-filter-actions .guard-btn-secondary:hover,
    .guard-page-header .guard-btn-secondary:hover {
        border-color: var(--g-cyan) !important;
        background: var(--g-surface-soft) !important;
        color: #fff !important;
    }

    .guard-filter-actions .guard-btn-secondary:focus,
    .guard-page-header .guard-btn-secondary:focus {
        outline: 2px solid var(--g-cyan);
        outline-offset: 2px;
    }

    @media (max-width: 1180px) {
        .guard-filter-form {
            grid-template-columns:
                minmax(240px, 2fr)
                repeat(3, minmax(140px, 1fr));
        }

        .guard-filter-actions {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 760px) {
        .guard-filter-form {
            grid-template-columns: 1fr;
        }

        .guard-filter-actions {
            grid-column: auto;
            flex-wrap: wrap;
        }
    }

    .guard-incident-metrics {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .guard-incident-metric {
        position: relative;
        min-width: 0;
        min-height: 118px;
        padding: 16px 18px;
        overflow: hidden;
        border: 1px solid var(--g-border);
        border-radius: 4px;
        background: var(--g-surface);
    }

    .guard-incident-metric::before {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 2px;
        background: var(--g-blue);
        content: "";
    }

    .guard-incident-metric-label {
        margin-bottom: 8px;
        color: var(--g-muted);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .guard-incident-metric-value {
        color: var(--g-text);
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.02em;
        line-height: 1;
    }

    .guard-incident-metric-description {
        margin-top: 10px;
        color: var(--g-muted);
        font-size: 12px;
        line-height: 1.4;
    }

    .guard-incident-metric-risk::before {
        background: #fa4d56;
    }

    .guard-incident-metric-risk .guard-incident-metric-value {
        color: #ff8389;
    }

    .guard-incident-metric-warning::before {
        background: #f1c21b;
    }

    .guard-incident-metric-warning .guard-incident-metric-value {
        color: #f1c21b;
    }

    @media (max-width: 1200px) {
        .guard-incident-metrics {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 820px) {
        .guard-incident-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .guard-incident-metrics {
            grid-template-columns: 1fr;
        }
    }

    .guard-incident-age {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 70px;
    }

    .guard-incident-age strong {
        color: var(--g-text);
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
        white-space: nowrap;
    }

    .guard-incident-age span {
        color: var(--g-muted);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    .guard-incident-sla {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 4px 8px;
        border: 1px solid var(--g-border);
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .05em;
        line-height: 1;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .guard-incident-sla-on_track {
        color: #42be65;
        border-color: rgba(66, 190, 101, .45);
        background: rgba(66, 190, 101, .08);
    }

    .guard-incident-sla-due_soon {
        color: #f1c21b;
        border-color: rgba(241, 194, 27, .45);
        background: rgba(241, 194, 27, .08);
    }

    .guard-incident-sla-breached {
        color: #ff8389;
        border-color: rgba(250, 77, 86, .5);
        background: rgba(250, 77, 86, .1);
    }

    .guard-incident-sla-met {
        color: #78a9ff;
        border-color: rgba(120, 169, 255, .45);
        background: rgba(120, 169, 255, .08);
    }

    .guard-incident-sla-unknown {
        color: var(--g-muted);
    }

    .guard-incident-sla-card {
    margin-bottom: 18px;
    }

    .guard-incident-sla-detail-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1px;
        overflow: hidden;
        border: 1px solid var(--g-border-soft);
        border-radius: 4px;
        background: var(--g-border-soft);
    }

    .guard-incident-sla-detail {
        min-width: 0;
        padding: 14px 16px;
        background: var(--g-surface);
    }

    .guard-incident-sla-detail span {
        display: block;
        margin-bottom: 6px;
        color: var(--g-muted);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .guard-incident-sla-detail strong {
        display: block;
        overflow: hidden;
        color: var(--g-text);
        font-size: 13px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 900px) {
        .guard-incident-sla-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        .guard-incident-sla-detail-grid {
            grid-template-columns: 1fr;
        }
    }

    .guard-incident-sla-summary {
        display: flex;
        align-items: center;
        gap: 1px;
        margin-top: -6px;
        margin-bottom: 18px;
        overflow: hidden;
        border: 1px solid var(--g-border);
        border-radius: 4px;
        background: var(--g-border);
    }

    .guard-incident-sla-summary-item {
        display: flex;
        flex: 1;
        align-items: center;
        justify-content: space-between;
        min-width: 0;
        padding: 11px 14px;
        background: var(--g-surface);
    }

    .guard-incident-sla-summary-item span {
        color: var(--g-muted);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .guard-incident-sla-summary-item strong {
        font-size: 18px;
        font-weight: 800;
    }

    .guard-incident-sla-summary-breached {
        color: #ff8389;
    }

    .guard-incident-sla-summary-due {
        color: #f1c21b;
    }

    @media (max-width: 620px) {
        .guard-incident-sla-summary {
            flex-direction: column;
            align-items: stretch;
        }
    }

    .guard-incident-resolution-analytics {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }

    .guard-incident-resolution-card {
        min-width: 0;
        padding: 14px 16px;
        border: 1px solid var(--g-border);
        border-radius: 4px;
        background: var(--g-surface);
    }

    .guard-incident-resolution-label {
        margin-bottom: 7px;
        color: var(--g-muted);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .guard-incident-resolution-value {
        color: var(--g-text);
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
    }

    .guard-incident-resolution-description {
        margin-top: 9px;
        color: var(--g-muted);
        font-size: 11px;
        line-height: 1.4;
    }

    .guard-incident-resolution-success {
        color: #42be65;
    }

    .guard-incident-resolution-danger {
        color: #ff8389;
    }

    @media (max-width: 1100px) {
        .guard-incident-resolution-analytics {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .guard-incident-resolution-analytics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .guard-incident-resolution-analytics {
            grid-template-columns: 1fr;
        }
    }

    .incident-activity-heading {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .incident-activity-heading strong {
        min-width: 0;
    }

    .incident-activity-category {
        display: inline-flex;
        align-items: center;
        min-height: 20px;
        padding: 3px 7px;
        border: 1px solid var(--g-border);
        border-radius: 999px;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        line-height: 1;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .incident-activity-category-lifecycle {
        color: #78a9ff;
        border-color: rgba(120, 169, 255, .4);
        background: rgba(120, 169, 255, .08);
    }

    .incident-activity-category-ownership {
        color: #f1c21b;
        border-color: rgba(241, 194, 27, .4);
        background: rgba(241, 194, 27, .08);
    }

    .incident-activity-category-investigation {
        color: #42be65;
        border-color: rgba(66, 190, 101, .4);
        background: rgba(66, 190, 101, .08);
    }

    .incident-activity-category-activity {
        color: var(--g-muted);
    }

    .incident-priority {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .incident-priority strong {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        min-height: 22px;
        padding: 3px 6px;
        border: 1px solid var(--g-border);
        border-radius: 3px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .04em;
        line-height: 1;
    }

    .incident-priority > span {
        color: var(--g-muted);
        font-size: 11px;
        font-weight: 700;
    }

    .incident-priority-p1 strong {
        color: #ff8389;
        border-color: rgba(250, 77, 86, .55);
        background: rgba(250, 77, 86, .12);
    }

    .incident-priority-p2 strong {
        color: #f1c21b;
        border-color: rgba(241, 194, 27, .5);
        background: rgba(241, 194, 27, .10);
    }

    .incident-priority-p3 strong {
        color: #78a9ff;
        border-color: rgba(120, 169, 255, .45);
        background: rgba(120, 169, 255, .10);
    }

    .incident-priority-p4 strong {
        color: #42be65;
        border-color: rgba(66, 190, 101, .4);
        background: rgba(66, 190, 101, .08);
    }

    .incident-priority-none strong {
        color: var(--g-muted);
        border-color: var(--g-border);
        background: var(--g-surface-soft);
    }

    .guard-page-actions,
    .guard-filter-actions,
    .guard-report-audit-summary {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .guard-report-range {
        margin: 12px 0 18px;
        color: var(--g-muted);
        font-size: 13px;
    }

    .guard-report-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin: 16px 0;
    }

    .guard-report-panel,
    .guard-report-section {
        overflow: hidden;
    }

    .guard-report-section {
        margin-top: 16px;
    }

    .guard-report-breakdown > div {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        padding: 10px 14px;
        border-top: 1px solid var(--g-border-soft);
    }

    .guard-report-breakdown span,
    .guard-report-audit-summary span {
        color: var(--g-muted);
    }

    .guard-report-audit-summary {
        padding: 12px 16px;
        border-bottom: 1px solid var(--g-border-soft);
    }

    .guard-report-audit-summary span {
        padding-right: 14px;
    }

    .guard-report-category {
        display: inline-block;
        padding: 4px 7px;
        border: 1px solid var(--g-border);
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .04em;
    }

    .guard-report-error {
        margin-top: 10px;
        color: var(--g-danger);
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .guard-report-grid {
            grid-template-columns: 1fr;
        }
    }

    .security-dashboard .incident-intelligence-stat span,
    .security-dashboard .incident-intelligence-stat small {
        color: var(--g-muted) !important;
    }

    .security-dashboard .incident-intelligence-stat strong {
        color: var(--g-text) !important;
    }

    .security-dashboard .incident-p1 span,
    .security-dashboard .incident-p1 strong,
    .security-dashboard .incident-sla-breached span,
    .security-dashboard .incident-sla-breached strong {
        color: #ffb3b8 !important;
    }

    .security-dashboard .incident-p2 span,
    .security-dashboard .incident-p2 strong,
    .security-dashboard .incident-sla-due span,
    .security-dashboard .incident-sla-due strong {
        color: #fddc69 !important;
    }

</style>
@endonce
