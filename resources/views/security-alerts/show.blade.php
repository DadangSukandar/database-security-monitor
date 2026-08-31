@extends('app')

@section('content')

<div class="security-alerts-page" style="
    max-width:1100px;
    margin:0 auto;
    padding:30px;
">

    @php
        $status = strtoupper($alert->status ?? 'OPEN');
        $severity = strtoupper($alert->severity ?? 'LOW');
    @endphp


    <div style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:25px;
    ">

        <div>

            <a
                href="{{ route('security-alerts.index') }}"
                style="
                    color:#6c757d;
                    text-decoration:none;
                    font-size:13px;
                "
            >
                ← Back to Alerts
            </a>

            <h1 style="
                margin:12px 0 5px;
                font-size:28px;
                font-weight:800;
            ">
                {{ $alert->title }}
            </h1>

            <div style="
                color:#6c757d;
                font-size:12px;
            ">
                Alert #{{ $alert->id }}
                · {{ $alert->alert_type ?? '-' }}
            </div>

        </div>


        <div style="
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        ">

            <span class="severity-{{ strtolower($severity) }}" style="
                padding:6px 10px;
                border-radius:20px;
                background:
                    {{ $severity === 'CRITICAL'
                        ? '#f8d7da'
                        : ($severity === 'HIGH'
                            ? '#ffe5d0'
                            : '#fff3cd')
                    }};
                color:
                    {{ $severity === 'CRITICAL'
                        ? '#842029'
                        : ($severity === 'HIGH'
                            ? '#984c0c'
                            : '#664d03')
                    }};
                font-size:10px;
                font-weight:800;
            ">
                {{ $severity }}
            </span>

            <span class="status-{{ strtolower($status) }}" style="
                padding:6px 10px;
                border-radius:20px;
                background:
                    {{ $status === 'RESOLVED'
                        ? '#d1e7dd'
                        : ($status === 'ACKNOWLEDGED'
                            ? '#fff3cd'
                            : '#f8d7da')
                    }};
                color:
                    {{ $status === 'RESOLVED'
                        ? '#0f5132'
                        : ($status === 'ACKNOWLEDGED'
                            ? '#664d03'
                            : '#842029')
                    }};
                font-size:10px;
                font-weight:800;
            ">
                {{ $status }}
            </span>

        </div>

    </div>


    @if(session('success'))
        <div style="
            background:#d1e7dd;
            color:#0f5132;
            padding:12px 15px;
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
            padding:12px 15px;
            border-radius:7px;
            margin-bottom:20px;
        ">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif


    <div style="
        background:#fff;
        border:1px solid #dee2e6;
        border-radius:10px;
        padding:25px;
    ">

        <div style="
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:15px;
            margin-bottom:25px;
        ">

            <div>
                <div style="font-size:10px;color:#6c757d;font-weight:800;">
                    DATABASE
                </div>
                <div style="margin-top:5px;font-weight:700;">
                    {{ $alert->database_name ?? '-' }}
                </div>
            </div>

            <div>
                <div style="font-size:10px;color:#6c757d;font-weight:800;">
                    USER
                </div>
                <div style="margin-top:5px;font-weight:700;">
                    {{ $alert->username ?? '-' }}
                </div>
            </div>

            <div>
                <div style="font-size:10px;color:#6c757d;font-weight:800;">
                    CLIENT IP
                </div>
                <div style="margin-top:5px;font-weight:700;">
                    {{ $alert->client_ip ?? '-' }}
                </div>
            </div>

            <div>
                <div style="font-size:10px;color:#6c757d;font-weight:800;">
                    DETECTED
                </div>
                <div style="margin-top:5px;font-weight:700;">
                    {{ $alert->detected_at?->format('d M Y H:i:s') ?? '-' }}
                </div>
            </div>

        </div>


        @if($alert->acknowledged_at)
            <div style="color:#6c757d;font-size:12px;margin-top:-12px;margin-bottom:25px;">
                Acknowledged at:
                <strong>{{ $alert->acknowledged_at->format('d M Y H:i:s') }}</strong>
            </div>
        @endif

        @php
            $responseSlaStatus = $alert->responseSlaStatus();
        @endphp

        <div style="background:{{ $responseSlaStatus === 'BREACHED' ? '#f8d7da' : ($responseSlaStatus === 'DUE_SOON' ? '#fff3cd' : '#f8f9fa') }};border:1px solid #dee2e6;border-radius:7px;padding:12px 15px;margin-bottom:25px;font-size:12px;">
            <strong>Response SLA: {{ str_replace('_', ' ', $responseSlaStatus) }}</strong>
            <span style="color:#6c757d;margin-left:8px;">
                Target {{ $alert->responseSlaMinutes() }} minutes
                @if($alert->responseSlaDeadline())
                    &middot; Deadline {{ $alert->responseSlaDeadline()->format('d M Y H:i:s') }}
                @endif
            </span>
        </div>


        @if($alert->description)

            <div style="margin-bottom:25px;">

                <h2 style="
                    font-size:17px;
                    margin-bottom:10px;
                ">
                    Description
                </h2>

                <div style="
                    color:#495057;
                    line-height:1.7;
                ">
                    {{ $alert->description }}
                </div>

            </div>

        @endif


        @if($alert->query)

            <div style="margin-bottom:25px;">

                <h2 style="
                    font-size:17px;
                    margin-bottom:10px;
                ">
                    Evidence / Query
                </h2>

                <pre style="
                    margin:0;
                    padding:15px;
                    background:#f8f9fa;
                    border:1px solid #dee2e6;
                    border-radius:7px;
                    white-space:pre-wrap;
                    word-break:break-word;
                    font-size:12px;
                ">{{ $alert->query }}</pre>

            </div>

        @endif


        @if($alert->table_name)

            <div style="
                margin-bottom:25px;
                color:#495057;
            ">
                <strong>Table:</strong>
                {{ $alert->table_name }}
            </div>

        @endif


        @if($alert->databaseConnection)

            <div style="
                border-top:1px solid #dee2e6;
                padding-top:20px;
                margin-top:20px;
            ">

                <h2 style="
                    font-size:17px;
                    margin-bottom:10px;
                ">
                    Database Connection
                </h2>

                <div style="
                    color:#495057;
                    line-height:1.7;
                ">
                    <div>
                        Name:
                        <strong>
                            {{ $alert->databaseConnection->name ?? '-' }}
                        </strong>
                    </div>

                    <div>
                        Driver:
                        <strong>
                            {{ strtoupper($alert->databaseConnection->driver ?? '-') }}
                        </strong>
                    </div>
                </div>

            </div>

        @endif


        @if($status === 'RESOLVED')

            <div style="
                border-top:1px solid #dee2e6;
                margin-top:25px;
                padding-top:20px;
            ">

                <h2 style="
                    font-size:17px;
                    margin-bottom:10px;
                ">
                    Resolution
                </h2>

                <div style="
                    background:#d1e7dd;
                    border:1px solid #badbcc;
                    border-radius:7px;
                    padding:15px;
                    color:#0f5132;
                ">

                    <div>
                        Resolved at:
                        <strong>
                            {{ $alert->resolved_at?->format('d M Y H:i:s') ?? '-' }}
                        </strong>
                    </div>

                    @if($alert->resolution_note)
                        <div style="
                            margin-top:10px;
                            line-height:1.6;
                        ">
                            {{ $alert->resolution_note }}
                        </div>
                    @endif

                </div>

            </div>

        @endif


        {{-- ALERT HISTORY --}}

        <div style="border-top:1px solid #dee2e6;margin-top:25px;padding-top:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <div>
                    <h2 style="font-size:17px;margin:0;">Alert History</h2>
                    <div style="color:#6c757d;font-size:12px;margin-top:4px;">
                        Riwayat perubahan status alert
                    </div>
                </div>
                <span style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:20px;padding:5px 10px;font-size:11px;font-weight:700;">
                    {{ $alert->histories->count() }} Events
                </span>
            </div>

            @forelse($alert->histories as $history)
                @php
                    $historyStatus = strtoupper((string) $history->new_status);
                    $historyColor = match ($historyStatus) {
                        'RESOLVED' => '#198754',
                        'ACKNOWLEDGED' => '#d39e00',
                        'OPEN' => '#dc3545',
                        default => '#0d6efd',
                    };
                @endphp

                <div style="border-left:3px solid {{ $historyColor }};background:#f8f9fa;border-radius:0 7px 7px 0;padding:12px 15px;margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap;">
                        <strong>{{ ucwords(strtolower(str_replace('_', ' ', $history->action))) }}</strong>
                        <span style="color:#6c757d;font-size:11px;">
                            {{ $history->created_at?->format('d M Y H:i:s') ?? '-' }}
                        </span>
                    </div>
                    <div style="font-size:12px;margin-top:6px;">
                        {{ strtoupper($history->old_status ?? '-') }}
                        &rarr;
                        <strong>{{ $historyStatus }}</strong>
                    </div>
                    @if($history->notes)
                        <div style="color:#495057;font-size:13px;margin-top:7px;white-space:pre-wrap;">{{ $history->notes }}</div>
                    @endif
                </div>
            @empty
                <div style="color:#6c757d;background:#f8f9fa;border:1px dashed #ced4da;border-radius:7px;padding:15px;text-align:center;">
                    Belum ada perubahan status yang tercatat.
                </div>
            @endforelse
        </div>

        {{-- ACTIONS --}}

        <div style="
            border-top:1px solid #dee2e6;
            margin-top:25px;
            padding-top:20px;
        ">

            @if($status === 'OPEN')

                <div style="
                    display:flex;
                    gap:10px;
                    flex-wrap:wrap;
                    margin-bottom:20px;
                ">

                    <form
                        method="POST"
                        action="{{ route(
                            'security-alerts.acknowledge',
                            $alert
                        ) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            style="
                                padding:9px 14px;
                                border:0;
                                border-radius:6px;
                                background:#ffc107;
                                color:#212529;
                                font-weight:700;
                                cursor:pointer;
                            "
                        >
                            Acknowledge
                        </button>
                    </form>

                </div>

            @endif


            @if($status !== 'RESOLVED')

                <form
                    method="POST"
                    action="{{ route(
                        'security-alerts.resolve',
                        $alert
                    ) }}"
                >
                    @csrf

                    <label style="
                        display:block;
                        font-size:11px;
                        font-weight:800;
                        margin-bottom:7px;
                    ">
                        RESOLUTION NOTE
                    </label>

                    <textarea
                        name="resolution_note"
                        required
                        rows="4"
                        placeholder="Jelaskan tindakan yang dilakukan untuk menyelesaikan alert..."
                        style="
                            width:100%;
                            box-sizing:border-box;
                            padding:11px;
                            border:1px solid #ced4da;
                            border-radius:7px;
                            resize:vertical;
                            margin-bottom:10px;
                        "
                    >{{ old('resolution_note') }}</textarea>

                    <button
                        type="submit"
                        onclick="
                            return confirm(
                                'Resolve this security alert?'
                            );
                        "
                        style="
                            padding:9px 15px;
                            border:0;
                            border-radius:6px;
                            background:#198754;
                            color:#fff;
                            font-weight:700;
                            cursor:pointer;
                        "
                    >
                        ✓ Resolve Alert
                    </button>

                </form>

            @else

                <form
                    method="POST"
                    action="{{ route(
                        'security-alerts.reopen',
                        $alert
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        style="
                            padding:9px 15px;
                            border:1px solid #dee2e6;
                            border-radius:6px;
                            background:#fff;
                            color:#212529;
                            font-weight:700;
                            cursor:pointer;
                        "
                    >
                        Reopen Alert
                    </button>

                </form>

            @endif

        </div>

    </div>

</div>


<style>
@media(max-width:800px){
    div[style*="grid-template-columns:repeat(4,1fr)"]{
        grid-template-columns:1fr 1fr!important;
    }
}

@media(max-width:500px){
    div[style*="grid-template-columns:repeat(4,1fr)"]{
        grid-template-columns:1fr!important;
    }
}
</style>

@endsection
