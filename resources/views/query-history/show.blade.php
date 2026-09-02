@extends('app')

@section('content')

<div class="query-history-show" style="padding:20px;">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:20px;
    ">

        <div>

            <h1 style="margin:0;">
                Query Detail
            </h1>

            <p style="
                color:#64748b;
                margin-top:5px;
            ">
                Detail aktivitas database.
            </p>

        </div>


        <a
            href="{{ route('query-history.index') }}"
            style="
                padding:10px 16px;
                border:1px solid #94a3b8;
                border-radius:6px;
                text-decoration:none;
                color:#334155;
            "
        >
            ← Back
        </a>

    </div>


    <div class="detail-grid">

        <div class="detail-card">

            <div class="label">
                Execution Time
            </div>

            <div class="value">
                {{ $databaseActivity->executed_at
                    ? $databaseActivity->executed_at
                        ->format('Y-m-d H:i:s')
                    : '-'
                }}
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                User
            </div>

            <div class="value">
                {{ $databaseActivity->username ?: '-' }}
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                Database
            </div>

            <div class="value">
                {{ $databaseActivity->database_name ?: '-' }}
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                Schema
            </div>

            <div class="value">
                {{ $databaseActivity->schema_name ?: '-' }}
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                Table
            </div>

            <div class="value">
                {{ $databaseActivity->table_name ?: '-' }}
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                Action
            </div>

            <div class="value">
                {{ $databaseActivity->action }}
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                Execution
            </div>

            <div class="value">
                {{ $databaseActivity->execution_time_ms ?? 0 }}
                ms
            </div>

        </div>


        <div class="detail-card">

            <div class="label">
                Client IP
            </div>

            <div class="value">
                {{ $databaseActivity->client_ip ?: '-' }}
            </div>

        </div>

    </div>


    <div class="card">

        <h3>
            SQL Query
        </h3>

        <pre>{{ $databaseActivity->query }}</pre>

    </div>


    @if($databaseActivity->status === 'failed')

        <div class="error-box">

            <strong>
                Query Error
            </strong>

            <p>
                Database operation failed. Detail teknis tersedia di log aplikasi.
            </p>

        </div>

    @endif

</div>


<style>

.detail-grid {
    display:grid;
    grid-template-columns:
        repeat(4, 1fr);
    gap:15px;
    margin-bottom:20px;
}

.detail-card {
    background:white;
    border:1px solid #d1d5db;
    border-radius:8px;
    padding:18px;
}

.label {
    color:#64748b;
    font-size:13px;
    margin-bottom:8px;
}

.value {
    font-weight:600;
    font-size:16px;
}

.card {
    background:white;
    border:1px solid #d1d5db;
    border-radius:8px;
    padding:20px;
    margin-bottom:20px;
}

pre {
    background:#0f172a;
    color:#e2e8f0;
    padding:20px;
    border-radius:8px;
    overflow:auto;
    white-space:pre-wrap;
    word-break:break-word;
}

.error-box {
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#b91c1c;
    border-radius:8px;
    padding:20px;
}

.query-history-show {
    color:var(--g-text);
}

.query-history-show .detail-card,
.query-history-show .card {
    border-color:var(--g-border) !important;
    background:linear-gradient(145deg, rgba(26, 39, 59, .98), rgba(15, 24, 38, .98)) !important;
    color:var(--g-text) !important;
    box-shadow:0 12px 30px rgba(0, 0, 0, .17);
}

.query-history-show .label {
    color:var(--g-muted) !important;
}

.query-history-show .value {
    color:var(--g-text);
}

.query-history-show > div:first-child > a {
    border-color:var(--g-border) !important;
    background:var(--g-surface-soft) !important;
    color:var(--g-text) !important;
}

.query-history-show > div:first-child > a:hover {
    border-color:var(--g-cyan) !important;
    background:#263a56 !important;
    color:#fff !important;
}

.query-history-show pre {
    border:1px solid var(--g-border);
    background:#070c14 !important;
    color:#d7e7ff !important;
}

.query-history-show .error-box {
    border-color:rgba(250, 77, 86, .5) !important;
    background:rgba(218, 30, 40, .16) !important;
    color:#ffb3b8 !important;
}

.query-history-show .error-box p {
    color:#ffb3b8 !important;
}

@media (max-width: 900px) {
    .query-history-show .detail-grid {
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 520px) {
    .query-history-show .detail-grid {
        grid-template-columns:1fr;
    }
}

</style>

@endsection
