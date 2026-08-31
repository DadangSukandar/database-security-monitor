@extends('app')

@section('content')

<div
    style="
        max-width:1100px;
        margin:0 auto;
        padding:30px;
    "
>

    {{-- HEADER --}}

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            margin-bottom:25px;
        "
    >

        <div>

            <a
                href="{{ route('security-findings.index') }}"
                style="
                    color:#6c757d;
                    text-decoration:none;
                    font-size:13px;
                "
            >
                ← Back to Findings
            </a>

            <h1
                style="
                    margin:12px 0 5px;
                    font-size:28px;
                    font-weight:700;
                "
            >
                {{ $finding->title }}
            </h1>

            @if($finding->finding_type)

                <div
                    style="
                        color:#6c757d;
                        font-size:12px;
                    "
                >
                    Finding type:

                    <code>
                        {{ $finding->finding_type }}
                    </code>
                </div>

            @endif

        </div>


        {{-- STATUS --}}

        @php
            $status = strtoupper(
                $finding->status ?? 'OPEN'
            );

            if ($status === 'RESOLVED') {
                $statusBackground = '#d1e7dd';
                $statusColor = '#0f5132';
            } elseif ($status === 'IGNORED') {
                $statusBackground = '#e2e3e5';
                $statusColor = '#41464b';
            } else {
                $statusBackground = '#f8d7da';
                $statusColor = '#842029';
            }
        @endphp


        <span
            style="
                background:{{ $statusBackground }};
                color:{{ $statusColor }};
                padding:7px 12px;
                border-radius:20px;
                font-size:11px;
                font-weight:700;
                white-space:nowrap;
            "
        >
            {{ $status }}
        </span>

    </div>


    {{-- SUCCESS MESSAGE --}}

    @if(session('success'))

        <div
            style="
                background:#d1e7dd;
                color:#0f5132;
                padding:12px 15px;
                border-radius:7px;
                margin-bottom:20px;
            "
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ERROR MESSAGE --}}

    @if($errors->any())

        <div
            style="
                background:#f8d7da;
                color:#842029;
                padding:12px 15px;
                border-radius:7px;
                margin-bottom:20px;
            "
        >

            @foreach($errors->all() as $error)

                <div>
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    {{-- MAIN CARD --}}

    <div
        style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:10px;
            padding:25px;
        "
    >


        {{-- META --}}

        <div
            style="
                display:grid;
                grid-template-columns:repeat(4,1fr);
                gap:12px;
                margin-bottom:25px;
            "
        >

            {{-- SEVERITY --}}

            <div>

                <div
                    style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    "
                >
                    SEVERITY
                </div>

                <div
                    style="
                        margin-top:5px;
                        font-weight:700;
                    "
                >
                    {{ strtoupper($finding->severity ?? 'LOW') }}
                </div>

            </div>


            {{-- CATEGORY --}}

            <div>

                <div
                    style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    "
                >
                    CATEGORY
                </div>

                <div
                    style="
                        margin-top:5px;
                        font-weight:700;
                    "
                >
                    {{ $finding->category ?? '-' }}
                </div>

            </div>


            {{-- DATABASE --}}

            <div>

                <div
                    style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    "
                >
                    DATABASE
                </div>

                <div
                    style="
                        margin-top:5px;
                        font-weight:700;
                    "
                >
                    {{ $finding->database_name ?? '-' }}
                </div>

            </div>


            {{-- USER --}}

            <div>

                <div
                    style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    "
                >
                    USER
                </div>

                <div
                    style="
                        margin-top:5px;
                        font-weight:700;
                    "
                >

                    {{ $finding->username ?? '-' }}

                    @if($finding->object_name)

                        &middot; {{ $finding->object_name }}

                    @endif

                </div>

            </div>

        </div>


        {{-- DESCRIPTION --}}

        @if($finding->description)

            <div
                style="
                    margin-bottom:25px;
                "
            >

                <h2
                    style="
                        font-size:17px;
                        margin-bottom:10px;
                    "
                >
                    Description
                </h2>

                <div
                    style="
                        color:#495057;
                        line-height:1.7;
                    "
                >
                    {{ $finding->description }}
                </div>

            </div>

        @endif


        {{-- RECOMMENDATION --}}

        @if($finding->recommendation)

            <div
                style="
                    margin-bottom:25px;
                "
            >

                <h2
                    style="
                        font-size:17px;
                        margin-bottom:10px;
                    "
                >
                    Recommendation
                </h2>

                <div
                    style="
                        background:#fff3cd;
                        border:1px solid #ffecb5;
                        color:#664d03;
                        padding:15px;
                        border-radius:7px;
                        line-height:1.6;
                    "
                >
                    {{ $finding->recommendation }}
                </div>

            </div>

        @endif


        {{-- FINDING HISTORY --}}

        @php
            $histories = $finding->histories ?? collect();
        @endphp

        <div
            style="
                border-top:1px solid #dee2e6;
                margin-top:25px;
                padding-top:25px;
            "
        >

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    margin-bottom:18px;
                "
            >

                <div>

                    <h2
                        style="
                            font-size:18px;
                            margin:0;
                        "
                    >
                        Finding History
                    </h2>

                    <div
                        style="
                            color:#6c757d;
                            font-size:12px;
                            margin-top:4px;
                        "
                    >
                        Riwayat perubahan finding
                    </div>

                </div>


                <div
                    style="
                        background:#f8f9fa;
                        border:1px solid #dee2e6;
                        border-radius:20px;
                        padding:5px 10px;
                        font-size:11px;
                        font-weight:700;
                        color:#495057;
                        white-space:nowrap;
                    "
                >
                    {{ $histories->count() }}
                    Events
                </div>

            </div>


            @if($histories->count() > 0)

                <div
                    style="
                        position:relative;
                        padding-left:30px;
                    "
                >

                    {{-- TIMELINE LINE --}}

                    <div
                        style="
                            position:absolute;
                            left:6px;
                            top:5px;
                            bottom:5px;
                            width:2px;
                            background:#dee2e6;
                        "
                    ></div>


                    @foreach($histories as $history)

                        @php

                            $historyAction = strtoupper(
                                (string) (
                                    $history->action
                                    ?? 'STATUS CHANGE'
                                )
                            );

                            $newStatus = strtoupper(
                                (string) (
                                    $history->new_status
                                    ?? ''
                                )
                            );


                            if ($newStatus === 'RESOLVED') {

                                $dotColor = '#198754';

                            } elseif ($newStatus === 'IGNORED') {

                                $dotColor = '#6c757d';

                            } elseif ($newStatus === 'OPEN') {

                                $dotColor = '#dc3545';

                            } else {

                                $dotColor = '#0d6efd';

                            }

                        @endphp


                        <div
                            style="
                                position:relative;
                                margin-bottom:22px;
                            "
                        >

                            {{-- TIMELINE DOT --}}

                            <div
                                style="
                                    position:absolute;
                                    left:-30px;
                                    top:5px;
                                    width:14px;
                                    height:14px;
                                    border-radius:50%;
                                    background:{{ $dotColor }};
                                    border:3px solid #ffffff;
                                    box-shadow:0 0 0 1px #dee2e6;
                                    box-sizing:border-box;
                                "
                            ></div>


                            {{-- HISTORY CARD --}}

                            <div
                                style="
                                    background:#f8f9fa;
                                    border:1px solid #dee2e6;
                                    border-radius:9px;
                                    padding:15px;
                                "
                            >

                                <div
                                    style="
                                        display:flex;
                                        justify-content:space-between;
                                        align-items:flex-start;
                                        gap:15px;
                                    "
                                >

                                    <div>

                                        <div
                                            style="
                                                font-weight:700;
                                                font-size:14px;
                                                color:#212529;
                                            "
                                        >
                                            {{ ucwords(
                                                str_replace(
                                                    ['_', '-'],
                                                    ' ',
                                                    strtolower($historyAction)
                                                )
                                            ) }}
                                        </div>


                                        <div
                                            style="
                                                color:#6c757d;
                                                font-size:11px;
                                                margin-top:4px;
                                            "
                                        >

                                            @if($history->created_at)

                                                {{ $history->created_at->format('d M Y H:i:s') }}

                                            @else

                                                -

                                            @endif

                                        </div>

                                    </div>


                                    {{-- STATUS --}}

                                    <div
                                        style="
                                            display:flex;
                                            align-items:center;
                                            justify-content:flex-end;
                                            gap:7px;
                                            font-size:11px;
                                            font-weight:700;
                                            flex-wrap:wrap;
                                        "
                                    >

                                        @if($history->old_status)

                                            <span
                                                style="
                                                    background:#ffffff;
                                                    border:1px solid #dee2e6;
                                                    border-radius:5px;
                                                    padding:4px 7px;
                                                "
                                            >
                                                {{ strtoupper($history->old_status) }}
                                            </span>

                                            <span
                                                style="
                                                    color:#6c757d;
                                                "
                                            >
                                                →
                                            </span>

                                        @endif


                                        @if($history->new_status)

                                            @php

                                                $historyStatus =
                                                    strtoupper(
                                                        $history->new_status
                                                    );


                                                if (
                                                    $historyStatus === 'RESOLVED'
                                                ) {

                                                    $historyBackground =
                                                        '#d1e7dd';

                                                    $historyColor =
                                                        '#0f5132';

                                                } elseif (
                                                    $historyStatus === 'IGNORED'
                                                ) {

                                                    $historyBackground =
                                                        '#e2e3e5';

                                                    $historyColor =
                                                        '#41464b';

                                                } elseif (
                                                    $historyStatus === 'OPEN'
                                                ) {

                                                    $historyBackground =
                                                        '#f8d7da';

                                                    $historyColor =
                                                        '#842029';

                                                } else {

                                                    $historyBackground =
                                                        '#cff4fc';

                                                    $historyColor =
                                                        '#055160';

                                                }

                                            @endphp


                                            <span
                                                style="
                                                    background:{{ $historyBackground }};
                                                    color:{{ $historyColor }};
                                                    border-radius:5px;
                                                    padding:4px 7px;
                                                "
                                            >
                                                {{ $historyStatus }}
                                            </span>

                                        @endif

                                    </div>

                                </div>


                                {{-- NOTES --}}

                                @if($history->notes)

                                    <div
                                        style="
                                            margin-top:12px;
                                            padding-top:10px;
                                            border-top:1px solid #dee2e6;
                                            color:#495057;
                                            font-size:13px;
                                            line-height:1.6;
                                        "
                                    >
                                        {{ $history->notes }}
                                    </div>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>


            @else

                <div
                    style="
                        background:#f8f9fa;
                        border:1px dashed #ced4da;
                        border-radius:9px;
                        padding:25px;
                        text-align:center;
                        color:#6c757d;
                    "
                >

                    <div
                        style="
                            font-size:25px;
                            margin-bottom:7px;
                        "
                    >
                        🕒
                    </div>

                    <div
                        style="
                            font-weight:600;
                            color:#495057;
                            margin-bottom:5px;
                        "
                    >
                        Belum ada riwayat
                    </div>

                    <div
                        style="
                            font-size:12px;
                        "
                    >
                        Belum ada perubahan status
                        untuk finding ini.
                    </div>

                </div>

            @endif

        </div>


        {{-- ACTIONS --}}

        <div
            style="
                border-top:1px solid #dee2e6;
                margin-top:25px;
                padding-top:20px;
                display:flex;
                gap:10px;
                flex-wrap:wrap;
            "
        >

            {{-- RESOLVED --}}

            @if($status === 'RESOLVED')

                <form
                    method="POST"
                    action="{{ route(
                        'security-findings.reopen',
                        $finding
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
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        Reopen Finding
                    </button>

                </form>


            {{-- IGNORED --}}

            @elseif($status === 'IGNORED')

                <form
                    method="POST"
                    action="{{ route(
                        'security-findings.reopen',
                        $finding
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
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        Reopen Finding
                    </button>

                </form>


            {{-- OPEN --}}

            @else

                <form
                    method="POST"
                    action="{{ route(
                        'security-findings.resolve',
                        $finding
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Mark this finding as resolved?'
                        );
                    "
                >

                    @csrf

                    <button
                        type="submit"
                        style="
                            padding:9px 15px;
                            border:0;
                            border-radius:6px;
                            background:#198754;
                            color:#fff;
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        ✓ Mark as Resolved
                    </button>

                </form>


                <form
                    method="POST"
                    action="{{ route(
                        'security-findings.ignore',
                        $finding
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Ignore this finding?'
                        );
                    "
                >

                    @csrf

                    <button
                        type="submit"
                        style="
                            padding:9px 15px;
                            border:0;
                            border-radius:6px;
                            background:#6c757d;
                            color:#fff;
                            cursor:pointer;
                            font-weight:600;
                        "
                    >
                        Ignore
                    </button>

                </form>

            @endif

        </div>

    </div>

</div>


{{-- RESPONSIVE --}}

<style>

@media (max-width: 900px) {

    div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns:1fr 1fr !important;
    }

}

@media (max-width: 600px) {

    div[style*="grid-template-columns:repeat(4,1fr)"] {
        grid-template-columns:1fr !important;
    }

}

</style>

@endsection
