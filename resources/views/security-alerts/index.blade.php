@extends('app')

@section('content')

<div class="security-alerts-page" style="
    max-width:1400px;
    margin:0 auto;
    padding:30px;
">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:25px;
    ">

        <div>
            <div style="
                font-size:12px;
                color:#6c757d;
                font-weight:700;
                margin-bottom:5px;
            ">
                SECURITY OPERATIONS
            </div>

            <h1 style="
                margin:0;
                font-size:28px;
                font-weight:800;
            ">
                Security Alerts
            </h1>

            <div style="
                margin-top:6px;
                color:#6c757d;
                font-size:14px;
            ">
                Monitor, acknowledge, and resolve database security alerts.
            </div>
        </div>

        @if(Route::has('security-dashboard'))
            <a
                href="{{ route('security-dashboard') }}"
                style="
                    padding:10px 15px;
                    border:1px solid #dee2e6;
                    border-radius:7px;
                    background:#fff;
                    color:#212529;
                    text-decoration:none;
                    font-weight:600;
                    font-size:13px;
                "
            >
                ← Security Dashboard
            </a>
        @endif

    </div>


    @if(session('success'))
        <div style="
            background:#d1e7dd;
            color:#0f5132;
            border:1px solid #badbcc;
            padding:13px 15px;
            border-radius:7px;
            margin-bottom:20px;
        ">
            {{ session('success') }}
        </div>
    @endif


    @if($errors->any())
        <div style="
            background:#f8d7da;
            color:#842029;
            border:1px solid #f5c2c7;
            padding:13px 15px;
            border-radius:7px;
            margin-bottom:20px;
        ">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif


    {{-- STATS --}}

    <div style="
        display:grid;
        grid-template-columns:repeat(6,1fr);
        gap:14px;
        margin-bottom:20px;
    ">

        @php
            $statCards = [
                ['label' => 'TOTAL', 'value' => $totalAlerts ?? 0, 'color' => '#212529'],
                ['label' => 'OPEN', 'value' => $openAlerts ?? 0, 'color' => '#842029'],
                ['label' => 'ACKNOWLEDGED', 'value' => $acknowledgedAlerts ?? 0, 'color' => '#664d03'],
                ['label' => 'RESOLVED', 'value' => $resolvedAlerts ?? 0, 'color' => '#0f5132'],
                ['label' => 'CRITICAL', 'value' => $criticalAlerts ?? 0, 'color' => '#842029'],
                ['label' => 'HIGH', 'value' => $highAlerts ?? 0, 'color' => '#984c0c'],
            ];
        @endphp

        @foreach($statCards as $card)
            <div style="
                background:#fff;
                border:1px solid #dee2e6;
                border-radius:10px;
                padding:18px;
            ">
                <div style="
                    font-size:10px;
                    color:#6c757d;
                    font-weight:800;
                ">
                    {{ $card['label'] }}
                </div>

                <div style="
                    font-size:28px;
                    font-weight:800;
                    margin-top:6px;
                    color:{{ $card['color'] }};
                ">
                    {{ $card['value'] }}
                </div>
            </div>
        @endforeach

    </div>


    {{-- FILTER --}}

    <div style="
        background:#fff;
        border:1px solid #dee2e6;
        border-radius:10px;
        padding:18px;
        margin-bottom:20px;
    ">

        <form
            method="GET"
            action="{{ route('security-alerts.index') }}"
            style="
                display:grid;
                grid-template-columns:2fr repeat(4,1fr) auto;
                gap:10px;
                align-items:end;
            "
        >

            <div>
                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:800;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    SEARCH
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Title, database, username, IP..."
                    style="
                        width:100%;
                        box-sizing:border-box;
                        padding:10px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                    "
                >
            </div>


            <div>
                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:800;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    SEVERITY
                </label>

                <select
                    name="severity"
                    style="
                        width:100%;
                        padding:10px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >
                    <option value="">All</option>
                    @foreach(['CRITICAL','HIGH','MEDIUM','LOW'] as $severity)
                        <option
                            value="{{ $severity }}"
                            @selected(request('severity') === $severity)
                        >
                            {{ $severity }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div>
                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:800;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    STATUS
                </label>

                <select
                    name="status"
                    style="
                        width:100%;
                        padding:10px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >
                    <option value="">All</option>
                    @foreach(['OPEN','ACKNOWLEDGED','RESOLVED'] as $status)
                        <option
                            value="{{ $status }}"
                            @selected(request('status') === $status)
                        >
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div>
                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:800;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    DATABASE
                </label>

                <select
                    name="database"
                    style="
                        width:100%;
                        padding:10px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >
                    <option value="">All</option>

                    @foreach($databases ?? [] as $database)
                        <option
                            value="{{ $database }}"
                            @selected(request('database') === $database)
                        >
                            {{ $database }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div>
                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:800;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    TYPE
                </label>

                <select
                    name="alert_type"
                    style="
                        width:100%;
                        padding:10px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >
                    <option value="">All</option>

                    @foreach($alertTypes ?? [] as $type)
                        <option
                            value="{{ $type }}"
                            @selected(request('alert_type') === $type)
                        >
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div style="display:flex; gap:7px;">
                <button
                    type="submit"
                    style="
                        padding:10px 14px;
                        border:0;
                        border-radius:6px;
                        background:#212529;
                        color:#fff;
                        cursor:pointer;
                        font-weight:700;
                    "
                >
                    Filter
                </button>

                <a
                    href="{{ route('security-alerts.index') }}"
                    style="
                        padding:10px 12px;
                        border:1px solid #dee2e6;
                        border-radius:6px;
                        background:#fff;
                        color:#212529;
                        text-decoration:none;
                        font-weight:600;
                    "
                >
                    Reset
                </a>
            </div>

        </form>

    </div>


    {{-- TABLE --}}

    <div style="
        background:#fff;
        border:1px solid #dee2e6;
        border-radius:10px;
        overflow:hidden;
    ">

        <div style="
            padding:18px 20px;
            border-bottom:1px solid #dee2e6;
            font-weight:800;
        ">
            Alert List
        </div>

        @if(($alerts ?? collect())->count())

            <div style="overflow-x:auto;">

                <table style="
                    width:100%;
                    border-collapse:collapse;
                    min-width:1000px;
                ">

                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">SEVERITY</th>
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">TITLE</th>
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">DATABASE</th>
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">TYPE</th>
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">STATUS</th>
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">RESPONSE SLA</th>
                            <th style="padding:12px 15px;text-align:left;font-size:10px;">DETECTED</th>
                            <th style="padding:12px 15px;text-align:right;font-size:10px;">ACTION</th>
                        </tr>
                    </thead>

                    <tbody>

                    @foreach($alerts as $alert)

                        @php
                            $severity = strtoupper($alert->severity ?? 'LOW');
                            $status = strtoupper($alert->status ?? 'OPEN');
                            $slaStatus = $alert->responseSlaStatus();

                            $slaColor = match($slaStatus) {
                                'BREACHED' => '#842029',
                                'DUE_SOON' => '#664d03',
                                'MET' => '#0f5132',
                                default => '#055160',
                            };

                            $slaBackground = match($slaStatus) {
                                'BREACHED' => '#f8d7da',
                                'DUE_SOON' => '#fff3cd',
                                'MET' => '#d1e7dd',
                                default => '#cff4fc',
                            };

                            $severityBg = match($severity) {
                                'CRITICAL' => '#f8d7da',
                                'HIGH' => '#ffe5d0',
                                'MEDIUM' => '#fff3cd',
                                default => '#d1e7dd',
                            };

                            $severityColor = match($severity) {
                                'CRITICAL' => '#842029',
                                'HIGH' => '#984c0c',
                                'MEDIUM' => '#664d03',
                                default => '#0f5132',
                            };
                        @endphp

                        <tr>

                            <td style="padding:14px 15px;border-top:1px solid #eee;">
                                <span class="severity-{{ strtolower($severity) }}" style="
                                    display:inline-block;
                                    background:{{ $severityBg }};
                                    color:{{ $severityColor }};
                                    padding:5px 8px;
                                    border-radius:15px;
                                    font-size:10px;
                                    font-weight:800;
                                ">
                                    {{ $severity }}
                                </span>
                            </td>


                            <td style="padding:14px 15px;border-top:1px solid #eee;">
                                <a
                                    href="{{ route('security-alerts.show', $alert) }}"
                                    style="
                                        color:#212529;
                                        text-decoration:none;
                                        font-weight:700;
                                    "
                                >
                                    {{ $alert->title }}
                                </a>

                                @if($alert->username)
                                    <div style="
                                        margin-top:4px;
                                        color:#6c757d;
                                        font-size:11px;
                                    ">
                                        User: {{ $alert->username }}
                                    </div>
                                @endif
                            </td>


                            <td style="padding:14px 15px;border-top:1px solid #eee;">
                                {{ $alert->database_name ?? '-' }}
                            </td>


                            <td style="padding:14px 15px;border-top:1px solid #eee;">
                                {{ $alert->alert_type ?? '-' }}
                            </td>


                            <td style="padding:14px 15px;border-top:1px solid #eee;">
                                <strong style="
                                    font-size:10px;
                                    color:
                                        {{ $status === 'RESOLVED'
                                            ? '#0f5132'
                                            : ($status === 'ACKNOWLEDGED'
                                                ? '#664d03'
                                                : '#842029')
                                        }};
                                ">
                                    {{ $status }}
                                </strong>
                            </td>


                            <td style="padding:14px 15px;border-top:1px solid #eee;white-space:nowrap;">
                                <strong class="sla-{{ strtolower($slaStatus) }}" style="display:inline-block;padding:5px 8px;border-radius:15px;background:{{ $slaBackground }};color:{{ $slaColor }};font-size:9px;">
                                    {{ str_replace('_', ' ', $slaStatus) }}
                                </strong>
                                @if($alert->responseSlaDeadline())
                                    <div style="margin-top:4px;color:#6c757d;font-size:10px;">
                                        Due {{ $alert->responseSlaDeadline()->format('d M H:i') }}
                                    </div>
                                @endif
                            </td>


                            <td style="
                                padding:14px 15px;
                                border-top:1px solid #eee;
                                color:#6c757d;
                                font-size:11px;
                                white-space:nowrap;
                            ">
                                {{ $alert->detected_at?->format('d M Y H:i') ?? '-' }}
                            </td>


                            <td style="
                                padding:14px 15px;
                                border-top:1px solid #eee;
                                text-align:right;
                            ">
                                <a
                                    href="{{ route('security-alerts.show', $alert) }}"
                                    style="
                                        padding:7px 10px;
                                        border:1px solid #dee2e6;
                                        border-radius:6px;
                                        background:#fff;
                                        color:#212529;
                                        text-decoration:none;
                                        font-size:11px;
                                        font-weight:700;
                                    "
                                >
                                    View
                                </a>
                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>

            @if(method_exists($alerts, 'links'))
                <div style="
                    padding:18px 20px;
                    border-top:1px solid #eee;
                ">
                    {{ $alerts->links() }}
                </div>
            @endif

        @else

            <div style="
                padding:50px;
                text-align:center;
                color:#6c757d;
            ">
                Tidak ada security alert.
            </div>

        @endif

    </div>

</div>


<style>
@media(max-width:1100px){
    div[style*="repeat(6,1fr)"]{
        grid-template-columns:repeat(3,1fr)!important;
    }

    form[style*="grid-template-columns:2fr repeat(4,1fr) auto"]{
        grid-template-columns:1fr 1fr!important;
    }
}

@media(max-width:650px){
    div[style*="repeat(6,1fr)"]{
        grid-template-columns:1fr 1fr!important;
    }

    form[style*="grid-template-columns:2fr repeat(4,1fr) auto"]{
        grid-template-columns:1fr!important;
    }
}
</style>

@endsection
