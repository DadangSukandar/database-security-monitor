@extends('app')

@section('content')

<div class="security-audit-page" style="padding: 30px;">

<div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:25px;
    ">

        <div>
            <h1 style="margin:0;">
                Security Audit
            </h1>

            <p style="color:#64748b;">
                Audit dan monitoring keamanan database.
            </p>
        </div>


        <form
            method="POST"
            action="{{ route('security-audit.scan') }}"
            style="
                display:flex;
                gap:10px;
                align-items:center;
            "
        >

            @csrf

            <select
                name="database_connection_id"
                required
                style="
                    padding:10px;
                    border:1px solid #ccc;
                    border-radius:6px;
                "
            >

                <option value="">
                    Select Database
                </option>

                @foreach($connections as $connection)

                    <option value="{{ $connection->id }}">
                        {{ $connection->name }}
                        -
                        {{ strtoupper($connection->driver) }}
                    </option>

                @endforeach

            </select>


            <button
                type="submit"
                style="
                    background:#2563eb;
                    color:white;
                    border:0;
                    padding:10px 18px;
                    border-radius:6px;
                    cursor:pointer;
                "
            >
                🔍 Scan Security
            </button>

        </form>

    </div>


    {{-- SUCCESS --}}

    @if(session('success'))

        <div style="
            background:#d1fae5;
            border:1px solid #a7f3d0;
            padding:15px;
            border-radius:8px;
            margin-bottom:20px;
        ">
            {{ session('success') }}
        </div>

    @endif

    @if($errors->any())

        <div style="
            background:#fee2e2;
            border:1px solid #fecaca;
            color:#991b1b;
            padding:15px;
            border-radius:8px;
            margin-bottom:20px;
        ">

            @foreach($errors->all() as $error)

                <div>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- STATISTICS --}}

    <div style="
        display:grid;
        grid-template-columns:repeat(6, 1fr);
        gap:15px;
        margin-bottom:25px;
    ">

        <div class="audit-card">
            <span>TOTAL</span>
            <strong>{{ $total }}</strong>
        </div>

        <div class="audit-card">
            <span>CRITICAL</span>
            <strong style="color:#dc2626;">
                {{ $critical }}
            </strong>
        </div>

        <div class="audit-card">
            <span>HIGH</span>
            <strong style="color:#dc2626;">
                {{ $high }}
            </strong>
        </div>

        <div class="audit-card">
            <span>MEDIUM</span>
            <strong style="color:#f59e0b;">
                {{ $medium }}
            </strong>
        </div>

        <div class="audit-card">
            <span>LOW</span>
            <strong>
                {{ $low }}
            </strong>
        </div>

        <div class="audit-card">
            <span>SECURITY SCORE</span>
            <strong
                style="
                color:
                {{ $score >= 80
                    ? '#16a34a'
                    : ($score >= 60
                        ? '#f59e0b'
                        : '#dc2626') }};
                "
            >
                {{ $score }}/100
            </strong>
        </div>

    </div>


    {{-- FILTER --}}

    <form method="GET" style="
        background:white;
        padding:20px;
        border:1px solid #ddd;
        border-radius:8px;
        margin-bottom:20px;
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    ">

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search finding, database, user..."
            style="padding:10px; flex:1; min-width:220px;"
        >

        <select
            name="severity"
            style="padding:10px;"
        >
            <option value="">
                All Severity
            </option>

            @foreach([
                'CRITICAL',
                'HIGH',
                'MEDIUM',
                'LOW'
            ] as $level)

                <option
                    value="{{ $level }}"
                    @selected(request('severity') === $level)
                >
                    {{ $level }}
                </option>

            @endforeach

        </select>


        <select
            name="status"
            style="padding:10px;"
        >

            <option value="">
                All Status
            </option>

            <option
                value="OPEN"
                @selected(request('status') === 'OPEN')
            >
                OPEN
            </option>

            <option
                value="RESOLVED"
                @selected(request('status') === 'RESOLVED')
            >
                RESOLVED
            </option>

            <option
                value="IGNORED"
                @selected(request('status') === 'IGNORED')
            >
                IGNORED
            </option>

        </select>


        <select
            name="database_connection_id"
            style="padding:10px;"
        >

            <option value="">
                All Connections
            </option>

            @foreach($connections as $connection)

                <option
                    value="{{ $connection->id }}"
                    @selected(
                        request('database_connection_id')
                        == $connection->id
                    )
                >
                    {{ $connection->name }}
                </option>

            @endforeach

        </select>


        <button
            type="submit"
            style="
                background:#2563eb;
                color:white;
                border:0;
                padding:10px 20px;
                border-radius:6px;
            "
        >
            Filter
        </button>


        <a
            href="{{ route('security-audit.index') }}"
            style="
                padding:10px 20px;
                border:1px solid #aaa;
                border-radius:6px;
                text-decoration:none;
                color:#333;
            "
        >
            Reset
        </a>

    </form>


    {{-- FINDINGS --}}

    <div style="
        background:white;
        border:1px solid #ddd;
        border-radius:8px;
        overflow:hidden;
    ">

        <div style="
            padding:15px;
            font-weight:bold;
            border-bottom:1px solid #ddd;
        ">
            Security Findings
        </div>


        @forelse($findings as $finding)

            <div style="
                padding:18px;
                border-bottom:1px solid #eee;
            ">

                <div style="
                    display:flex;
                    justify-content:space-between;
                    gap:20px;
                ">

                    <div style="flex:1;">

                        <div style="
                            display:flex;
                            gap:10px;
                            align-items:center;
                            margin-bottom:8px;
                        ">

                            <strong>
                                {{ $finding->title }}
                            </strong>

                            <span class="
                                severity
                                severity-{{ strtolower($finding->severity) }}
                            ">
                                {{ $finding->severity }}
                            </span>

                            <span class="status">
                                {{ $finding->status }}
                            </span>

                        </div>


                        <div style="
                            color:#64748b;
                            margin-bottom:8px;
                        ">

                            {{ $finding->description }}

                        </div>


                        <div style="
                            font-size:13px;
                            color:#475569;
                        ">

                            Database:
                            <strong>
                                {{ $finding->database_name ?? '-' }}
                            </strong>

                            &nbsp; | &nbsp;

                            User:
                            <strong>
                                {{ $finding->username ?? '-' }}
                            </strong>

                            &nbsp; | &nbsp;

                            Object:
                            <strong>
                                {{ $finding->object_name ?? '-' }}
                            </strong>

                        </div>

                    </div>


                    <div>

                        <a
                            href="{{ route(
                                'security-audit.show',
                                $finding
                            ) }}"
                            style="
                                background:#2563eb;
                                color:white;
                                padding:8px 14px;
                                border-radius:6px;
                                text-decoration:none;
                            "
                        >
                            Detail
                        </a>

                    </div>

                </div>

            </div>

        @empty

            <div style="
                padding:50px;
                text-align:center;
                color:#64748b;
            ">

                Belum ada security finding.

            </div>

        @endforelse

    </div>


    <div style="margin-top:20px;">
        {{ $findings->links() }}
    </div>

</div>


<style>

.audit-card {
    background:white;
    border:1px solid #ddd;
    border-radius:8px;
    padding:20px;
}

.audit-card span {
    display:block;
    color:#64748b;
    font-size:13px;
}

.audit-card strong {
    display:block;
    font-size:30px;
    margin-top:8px;
}

.severity {
    padding:4px 8px;
    border-radius:5px;
    font-size:11px;
    font-weight:bold;
}

.severity-critical {
    background:#7f1d1d;
    color:white;
}

.severity-high {
    background:#ef4444;
    color:white;
}

.severity-medium {
    background:#f59e0b;
    color:#111;
}

.severity-low {
    background:#16a34a;
    color:white;
}

.status {
    padding:4px 8px;
    background:#e2e8f0;
    border-radius:5px;
    font-size:11px;
}

</style>

@endsection
