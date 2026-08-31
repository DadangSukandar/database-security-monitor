@extends('app')

@section('content')

<div style="padding: 20px;">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:20px;
    ">

        <div>
            <h1 style="margin:0;">
                Query History
            </h1>

            <p style="
                margin-top:5px;
                color:#64748b;
            ">
                Riwayat seluruh aktivitas query database.
            </p>
        </div>

    </div>


    {{-- STATISTICS --}}

    <div style="
        display:grid;
        grid-template-columns:repeat(5,1fr);
        gap:15px;
        margin-bottom:20px;
    ">

        <div class="stat-card">
            <div class="stat-title">
                TOTAL
            </div>

            <div class="stat-value">
                {{ number_format($total) }}
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-title">
                SUCCESS
            </div>

            <div class="stat-value success">
                {{ number_format($success) }}
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-title">
                FAILED
            </div>

            <div class="stat-value danger">
                {{ number_format($failed) }}
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-title">
                SELECT
            </div>

            <div class="stat-value">
                {{ number_format($select) }}
            </div>
        </div>


        <div class="stat-card">
            <div class="stat-title">
                AVG EXECUTION
            </div>

            <div class="stat-value">
                {{ $averageExecution !== null
                    ? number_format($averageExecution, 2) . ' ms'
                    : '0 ms'
                }}
            </div>
        </div>

    </div>


    {{-- FILTER --}}

    <div class="card">

        <form
            method="GET"
            action="{{ route('query-history.index') }}"
        >

            <div class="filter-grid">

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search query, table, database, user..."
                >


                <select name="connection_id">

                    <option value="">
                        All Connections
                    </option>

                    @foreach($connections as $connection)

                        <option
                            value="{{ $connection->id }}"
                            @selected(
                                request('connection_id')
                                == $connection->id
                            )
                        >
                            {{ $connection->name }}
                        </option>

                    @endforeach

                </select>


                <select name="action">

                    <option value="">
                        All Actions
                    </option>

                    <option
                        value="SELECT"
                        @selected(request('action') === 'SELECT')
                    >
                        SELECT
                    </option>

                    <option
                        value="INSERT"
                        @selected(request('action') === 'INSERT')
                    >
                        INSERT
                    </option>

                    <option
                        value="UPDATE"
                        @selected(request('action') === 'UPDATE')
                    >
                        UPDATE
                    </option>

                    <option
                        value="DELETE"
                        @selected(request('action') === 'DELETE')
                    >
                        DELETE
                    </option>

                </select>


                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="success"
                        @selected(request('status') === 'success')
                    >
                        SUCCESS
                    </option>

                    <option
                        value="failed"
                        @selected(request('status') === 'failed')
                    >
                        FAILED
                    </option>

                </select>


                <input
                    type="date"
                    name="date_from"
                    value="{{ request('date_from') }}"
                >


                <input
                    type="date"
                    name="date_to"
                    value="{{ request('date_to') }}"
                >


                <button type="submit">
                    🔍 Filter
                </button>


                <a
                    href="{{ route('query-history.index') }}"
                    class="reset-button"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- TABLE --}}

    <div class="card" style="margin-top:20px;">

        <div class="card-title">
            Query Activity
        </div>


        <div style="overflow-x:auto;">

            <table>

                <thead>

                    <tr>

                        <th>Time</th>

                        <th>User</th>

                        <th>Database</th>

                        <th>Action</th>

                        <th>Table</th>

                        <th>Query</th>

                        <th>Execution</th>

                        <th>Status</th>

                        <th></th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($activities as $activity)

                        <tr>

                            <td>
                                {{ optional($activity->executed_at)
                                    ->format('Y-m-d H:i:s')
                                }}
                            </td>


                            <td>
                                {{ $activity->username ?: '-' }}
                            </td>


                            <td>
                                {{ $activity->database_name ?: '-' }}
                            </td>


                            <td>

                                <span class="action-badge action-{{ strtolower($activity->action) }}">
                                    {{ $activity->action }}
                                </span>

                            </td>


                            <td>
                                {{ $activity->table_name ?: '-' }}
                            </td>


                            <td>

                                <code class="query-text">
                                    {{ $activity->query }}
                                </code>

                            </td>


                            <td>
                                {{ $activity->execution_time_ms ?? 0 }}
                                ms
                            </td>


                            <td>

                                @if($activity->status === 'success')

                                    <span class="status-success">
                                        SUCCESS
                                    </span>

                                @else

                                    <span class="status-failed">
                                        FAILED
                                    </span>

                                @endif

                            </td>


                            <td>

                                <a
                                    href="{{ route(
                                        'query-history.show',
                                        $activity
                                    ) }}"
                                    class="detail-button"
                                >
                                    Detail
                                </a>

                            </td>

                        </tr>


                        @if($activity->status === 'failed')

                            <tr class="error-row">

                                <td colspan="9">

                                    <strong>
                                        Error:
                                    </strong>

                                    {{ $activity->error_message }}

                                </td>

                            </tr>

                        @endif


                    @empty

                        <tr>

                            <td
                                colspan="9"
                                style="
                                    text-align:center;
                                    padding:50px;
                                "
                            >

                                Belum ada query activity.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}

        <div style="margin-top:20px;">

            {{ $activities->links() }}

        </div>

    </div>

</div>


<style>

.stat-card {
    background:white;
    border:1px solid #d1d5db;
    border-radius:8px;
    padding:20px;
    box-shadow:0 1px 2px rgba(0,0,0,.05);
}

.stat-title {
    color:#64748b;
    font-size:13px;
    margin-bottom:8px;
}

.stat-value {
    font-size:28px;
    font-weight:700;
}

.success {
    color:#16a34a;
}

.danger {
    color:#dc2626;
}

.card {
    background:white;
    border:1px solid #d1d5db;
    border-radius:8px;
    padding:16px;
}

.filter-grid {
    display:flex;
    flex-wrap:wrap;
    align-items:stretch;
    gap:8px;
}

.filter-grid input,
.filter-grid select {
    min-width:0;
    flex:1 1 145px;
    box-sizing:border-box;
    height:40px;
    border:1px solid #cbd5e1;
    border-radius:6px;
    padding:0 12px;
    background:white;
}

.filter-grid input[type="text"] {
    flex:2 1 220px;
}

.filter-grid select[name="connection_id"] {
    flex:1.35 1 180px;
}

.filter-grid button {
    flex:0 0 auto;
    min-height:40px;
    border:0;
    border-radius:6px;
    padding:0 18px;
    background:#2563eb;
    color:white;
    cursor:pointer;
}

.reset-button {
    display:flex;
    min-height:40px;
    flex:0 0 auto;
    box-sizing:border-box;
    align-items:center;
    justify-content:center;
    padding:0 15px;
    border:1px solid #94a3b8;
    border-radius:6px;
    text-decoration:none;
    color:#334155;
}

@media (max-width: 720px) {
    .filter-grid input,
    .filter-grid select,
    .filter-grid input[type="text"],
    .filter-grid select[name="connection_id"],
    .filter-grid button,
    .filter-grid .reset-button {
        width:100%;
        flex:1 1 100%;
    }

    .filter-grid button,
    .filter-grid .reset-button {
        justify-content:center;
    }
}

.card-title {
    font-weight:700;
    padding-bottom:15px;
}

table {
    width:100%;
    border-collapse:collapse;
}

th,
td {
    padding:12px;
    border-top:1px solid #e2e8f0;
    text-align:left;
    vertical-align:top;
}

th {
    background:#f8fafc;
    font-weight:700;
}

.query-text {
    color:#e11d68;
    white-space:normal;
    word-break:break-word;
    font-family:monospace;
}

.action-badge {
    display:inline-block;
    padding:4px 8px;
    border-radius:5px;
    color:white;
    font-size:12px;
    font-weight:700;
}

.action-select {
    background:#2563eb;
}

.action-insert {
    background:#16a34a;
}

.action-update {
    background:#f59e0b;
}

.action-delete {
    background:#dc2626;
}

.status-success {
    display:inline-block;
    background:#16a34a;
    color:white;
    padding:4px 8px;
    border-radius:5px;
    font-size:11px;
    font-weight:700;
}

.status-failed {
    display:inline-block;
    background:#dc2626;
    color:white;
    padding:4px 8px;
    border-radius:5px;
    font-size:11px;
    font-weight:700;
}

.detail-button {
    color:#2563eb;
    text-decoration:none;
    font-weight:600;
}

.error-row td {
    background:#fff7f7;
    color:#b91c1c;
}

</style>

@endsection
