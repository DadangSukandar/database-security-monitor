@extends('app')

@section('content')

<div style="
    max-width:1400px;
    margin:0 auto;
    padding:30px;
">


    {{-- HEADER --}}

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        margin-bottom:25px;
    ">

        <div>

            <div style="
                font-size:12px;
                color:#6c757d;
                font-weight:700;
                letter-spacing:.5px;
            ">
                SECURITY CENTER
            </div>

            <h1 style="
                margin:5px 0 0;
                font-size:28px;
            ">
                Security Findings
            </h1>

            <p style="
                margin:7px 0 0;
                color:#6c757d;
            ">
                Manage and review detected database security findings.
            </p>

        </div>

    </div>


    {{-- SUCCESS MESSAGE --}}

    @if(session('success'))

        <div style="
            background:#d1e7dd;
            color:#0f5132;
            border:1px solid #badbcc;
            padding:12px 15px;
            border-radius:7px;
            margin-bottom:20px;
        ">

            {{ session('success') }}

        </div>

    @endif


    {{-- ERROR MESSAGE --}}

    @if($errors->any())

        <div style="
            background:#f8d7da;
            color:#842029;
            border:1px solid #f5c2c7;
            padding:12px 15px;
            border-radius:7px;
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
        grid-template-columns:
            repeat(6, 1fr);
        gap:12px;
        margin-bottom:22px;
    ">


        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:700;
            ">
                TOTAL
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                margin-top:5px;
            ">
                {{ $totalFindings }}
            </div>

        </div>


        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#842029;
                font-weight:700;
            ">
                CRITICAL
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                color:#dc3545;
                margin-top:5px;
            ">
                {{ $critical }}
            </div>

        </div>


        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#984c0c;
                font-weight:700;
            ">
                HIGH
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                color:#fd7e14;
                margin-top:5px;
            ">
                {{ $high }}
            </div>

        </div>


        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#055160;
                font-weight:700;
            ">
                MEDIUM
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                color:#0dcaf0;
                margin-top:5px;
            ">
                {{ $medium }}
            </div>

        </div>


        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#0f5132;
                font-weight:700;
            ">
                OPEN
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                color:#dc3545;
                margin-top:5px;
            ">
                {{ $openFindings }}
            </div>

        </div>


        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#0f5132;
                font-weight:700;
            ">
                RESOLVED
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                color:#198754;
                margin-top:5px;
            ">
                {{ $resolvedFindings }}
            </div>

        </div>

        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:9px;
            padding:18px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:700;
            ">
                IGNORED
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                color:#6c757d;
                margin-top:5px;
            ">
                {{ $ignoredFindings }}
            </div>

        </div>


    </div>


    {{-- FILTER --}}

    <form
        method="GET"
        action="{{ route('security-findings.index') }}"
        style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:10px;
            padding:18px;
            margin-bottom:20px;
        "
    >

        <div style="
            display:grid;
            grid-template-columns:
                2fr 1fr 1fr 1fr auto auto;
            gap:10px;
            align-items:end;
        ">


            <div>

                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:700;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    SEARCH
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Title, rule, user, database..."
                    style="
                        width:100%;
                        padding:9px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                    "
                >

            </div>


            <div>

                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:700;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    SEVERITY
                </label>

                <select
                    name="severity"
                    style="
                        width:100%;
                        padding:9px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >

                    <option value="">
                        All
                    </option>

                    @foreach([
                        'CRITICAL',
                        'HIGH',
                        'MEDIUM',
                        'LOW'
                    ] as $item)

                        <option
                            value="{{ $item }}"
                            @selected($severity === $item)
                        >
                            {{ $item }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:700;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    CATEGORY
                </label>

                <select
                    name="category"
                    style="
                        width:100%;
                        padding:9px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >

                    <option value="">
                        All
                    </option>

                    @foreach($categories as $item)

                        <option
                            value="{{ $item }}"
                            @selected($category === $item)
                        >
                            {{ $item }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <label style="
                    display:block;
                    font-size:10px;
                    font-weight:700;
                    color:#6c757d;
                    margin-bottom:5px;
                ">
                    STATUS
                </label>

                <select
                    name="status"
                    style="
                        width:100%;
                        padding:9px;
                        border:1px solid #ced4da;
                        border-radius:6px;
                        background:#fff;
                    "
                >

                    <option value="">
                        All
                    </option>

                    <option
                        value="OPEN"
                        @selected($status === 'OPEN')
                    >
                        Open
                    </option>

                    <option
                        value="RESOLVED"
                        @selected($status === 'RESOLVED')
                    >
                        Resolved
                    </option>

                    <option
                        value="IGNORED"
                        @selected($status === 'IGNORED')
                    >
                        Ignored
                    </option>

                </select>

            </div>


            <button
                type="submit"
                style="
                    padding:9px 16px;
                    border:0;
                    border-radius:6px;
                    background:#212529;
                    color:#fff;
                    font-weight:600;
                    cursor:pointer;
                "
            >
                Filter
            </button>


            <a
                href="{{ route('security-findings.index') }}"
                style="
                    padding:9px 16px;
                    border:1px solid #dee2e6;
                    border-radius:6px;
                    background:#fff;
                    color:#212529;
                    text-decoration:none;
                    font-weight:600;
                    text-align:center;
                "
            >
                Reset
            </a>

        </div>

    </form>


    {{-- TABLE --}}

    <div style="
        background:#fff;
        border:1px solid #dee2e6;
        border-radius:10px;
        overflow:hidden;
    ">

        <table style="
            width:100%;
            border-collapse:collapse;
        ">

            <thead>

                <tr style="
                    background:#f8f9fa;
                ">

                    <th style="
                        text-align:left;
                        padding:13px 16px;
                        font-size:11px;
                    ">
                        FINDING
                    </th>

                    <th style="
                        text-align:left;
                        padding:13px;
                        font-size:11px;
                    ">
                        DATABASE
                    </th>

                    <th style="
                        text-align:center;
                        padding:13px;
                        font-size:11px;
                    ">
                        SEVERITY
                    </th>

                    <th style="
                        text-align:center;
                        padding:13px;
                        font-size:11px;
                    ">
                        STATUS
                    </th>

                    <th style="
                        text-align:right;
                        padding:13px 16px;
                        font-size:11px;
                    ">
                        ACTION
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($findings as $finding)


                    @php

                        $severityName =
                            strtoupper(
                                $finding->severity ?? 'LOW'
                            );

                        $severityStyle = match(
                            $severityName
                        ) {

                            'CRITICAL' =>
                                'background:#f8d7da;color:#842029;',

                            'HIGH' =>
                                'background:#ffe5d0;color:#984c0c;',

                            'MEDIUM' =>
                                'background:#cff4fc;color:#055160;',

                            default =>
                                'background:#d1e7dd;color:#0f5132;',

                        };

                    @endphp


                    <tr>


                        <td style="
                            padding:16px;
                            border-top:1px solid #eee;
                        ">

                            <div style="
                                font-weight:700;
                                margin-bottom:3px;
                            ">

                                {{ $finding->title }}

                            </div>


                            @if($finding->finding_type)

                                <div style="
                                    color:#6c757d;
                                    font-size:11px;
                                ">

                                    {{ $finding->finding_type }}

                                </div>

                            @endif

                        </td>


                        <td style="
                            padding:13px;
                            border-top:1px solid #eee;
                        ">

                            <div>

                                {{ $finding->database_name ?? '-' }}

                            </div>


                            @if($finding->username)

                                <div style="
                                    color:#6c757d;
                                    font-size:11px;
                                ">

                                    {{ $finding->username }}

                                    @if($finding->object_name)

                                        &middot; {{ $finding->object_name }}

                                    @endif

                                </div>

                            @endif

                        </td>


                        <td style="
                            text-align:center;
                            border-top:1px solid #eee;
                        ">

                            <span style="
                                display:inline-block;
                                padding:5px 9px;
                                border-radius:4px;
                                font-size:10px;
                                font-weight:700;
                                {{ $severityStyle }}
                            ">

                                {{ $severityName }}

                            </span>

                        </td>


                        <td style="
                            text-align:center;
                            border-top:1px solid #eee;
                        ">

                            @php

                                $findingStatus = strtoupper(
                                    (string) ($finding->status ?? 'OPEN')
                                );

                            @endphp


                            @if($findingStatus === 'RESOLVED')

                                <span style="
                                    display:inline-block;
                                    padding:5px 9px;
                                    border-radius:4px;
                                    font-size:10px;
                                    font-weight:700;
                                    background:#d1e7dd;
                                    color:#0f5132;
                                ">
                                    RESOLVED
                                </span>

                            @elseif($findingStatus === 'IGNORED')

                                <span style="
                                    display:inline-block;
                                    padding:5px 9px;
                                    border-radius:4px;
                                    font-size:10px;
                                    font-weight:700;
                                    background:#e2e3e5;
                                    color:#41464b;
                                ">
                                    IGNORED
                                </span>

                            @else

                                <span style="
                                    display:inline-block;
                                    padding:5px 9px;
                                    border-radius:4px;
                                    font-size:10px;
                                    font-weight:700;
                                    background:#f8d7da;
                                    color:#842029;
                                ">
                                    OPEN
                                </span>

                            @endif

                        </td>


                        <td style="
                            text-align:right;
                            padding:13px 16px;
                            border-top:1px solid #eee;
                        ">

                            <a
                                href="{{ route(
                                    'security-findings.show',
                                    $finding
                                ) }}"
                                style="
                                    display:inline-block;
                                    padding:7px 11px;
                                    border:1px solid #dee2e6;
                                    border-radius:5px;
                                    color:#212529;
                                    text-decoration:none;
                                    font-size:11px;
                                    font-weight:600;
                                "
                            >
                                View
                            </a>

                        </td>


                    </tr>


                @empty


                    <tr>

                        <td
                            colspan="5"
                            style="
                                padding:50px;
                                text-align:center;
                                color:#6c757d;
                            "
                        >

                            No security findings found.

                        </td>

                    </tr>


                @endforelse

            </tbody>

        </table>

    </div>


    {{-- PAGINATION --}}

    <div style="
        margin-top:20px;
    ">

        {{ $findings->links() }}

    </div>


</div>


<style>

@media (max-width: 1000px) {

    table {
        min-width:900px;
    }

    .security-findings-table-wrapper {
        overflow-x:auto;
    }

}

</style>

@endsection
