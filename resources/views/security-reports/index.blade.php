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
        gap:20px;
        margin-bottom:28px;
    ">

        <div>
            <div style="
                font-size:12px;
                color:#6c757d;
                font-weight:700;
                letter-spacing:.5px;
                margin-bottom:6px;
            ">
                SECURITY CENTER
            </div>

            <h1 style="
                margin:0;
                font-size:30px;
                font-weight:800;
            ">
                Security Reports
            </h1>

            <div style="
                margin-top:7px;
                color:#6c757d;
                font-size:14px;
            ">
                Database security assessment and vulnerability monitoring
            </div>
        </div>

        @if(isset($latestAssessment) && $latestAssessment)

            <div style="
                display:flex;
                gap:10px;
                flex-wrap:wrap;
            ">

                <a
                    href="{{ route('security-reports.show', $latestAssessment) }}"
                    style="
                        display:inline-flex;
                        align-items:center;
                        padding:10px 16px;
                        border-radius:7px;
                        background:#212529;
                        color:#fff;
                        text-decoration:none;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    View Latest Report
                </a>

                <a
                    href="{{ route('security-reports.print', $latestAssessment) }}"
                    target="_blank"
                    style="
                        display:inline-flex;
                        align-items:center;
                        padding:10px 16px;
                        border-radius:7px;
                        border:1px solid #dee2e6;
                        background:#fff;
                        color:#212529;
                        text-decoration:none;
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Print
                </a>

            </div>

        @endif

    </div>


    {{-- SUCCESS --}}
    @if(session('success'))

        <div style="
            margin-bottom:20px;
            padding:13px 16px;
            border-radius:8px;
            background:#d1e7dd;
            color:#0f5132;
            border:1px solid #badbcc;
            font-size:14px;
        ">
            {{ session('success') }}
        </div>

    @endif


    {{-- ERRORS --}}
    @if($errors->any())

        <div style="
            margin-bottom:20px;
            padding:13px 16px;
            border-radius:8px;
            background:#f8d7da;
            color:#842029;
            border:1px solid #f5c2c7;
            font-size:14px;
        ">

            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach

        </div>

    @endif


    {{-- TOP SUMMARY --}}
    <div style="
        display:grid;
        grid-template-columns:
            repeat(4, 1fr);
        gap:18px;
        margin-bottom:25px;
    ">


        {{-- ASSESSMENTS --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:22px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
                letter-spacing:.4px;
            ">
                TOTAL ASSESSMENTS
            </div>

            <div style="
                margin-top:8px;
                font-size:34px;
                font-weight:800;
            ">
                {{ $totalAssessments ?? 0 }}
            </div>

            <div style="
                margin-top:5px;
                color:#6c757d;
                font-size:12px;
            ">
                Security scans performed
            </div>

        </div>


        {{-- SCORE --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:22px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
                letter-spacing:.4px;
            ">
                LATEST SCORE
            </div>

            @php
                $latestScore = $latestAssessment->score ?? null;

                if ($latestScore === null) {
                    $scoreLabel = 'N/A';
                    $scoreDescription = 'No assessment yet';
                } elseif ($latestScore >= 80) {
                    $scoreLabel = 'Good';
                    $scoreDescription = 'Security posture is good';
                } elseif ($latestScore >= 60) {
                    $scoreLabel = 'Fair';
                    $scoreDescription = 'Security improvements recommended';
                } elseif ($latestScore >= 40) {
                    $scoreLabel = 'Poor';
                    $scoreDescription = 'Security attention required';
                } else {
                    $scoreLabel = 'Critical';
                    $scoreDescription = 'Immediate attention required';
                }
            @endphp

            <div style="
                margin-top:8px;
                font-size:34px;
                font-weight:800;
            ">
                {{ $latestScore !== null ? $latestScore . '/100' : 'N/A' }}
            </div>

            <div style="
                margin-top:5px;
                color:#6c757d;
                font-size:12px;
            ">
                {{ $scoreDescription }}
            </div>

        </div>


        {{-- CRITICAL --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:22px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
                letter-spacing:.4px;
            ">
                CRITICAL FINDINGS
            </div>

            <div style="
                margin-top:8px;
                font-size:34px;
                font-weight:800;
            ">
                {{ $critical ?? 0 }}
            </div>

            <div style="
                margin-top:5px;
                color:#842029;
                font-size:12px;
                font-weight:600;
            ">
                Critical vulnerabilities
            </div>

        </div>


        {{-- HIGH --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:22px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
                letter-spacing:.4px;
            ">
                HIGH FINDINGS
            </div>

            <div style="
                margin-top:8px;
                font-size:34px;
                font-weight:800;
            ">
                {{ $high ?? 0 }}
            </div>

            <div style="
                margin-top:5px;
                color:#842029;
                font-size:12px;
                font-weight:600;
            ">
                High-risk vulnerabilities
            </div>

        </div>

    </div>


    {{-- SECOND SUMMARY --}}
    <div style="
        display:grid;
        grid-template-columns:
            repeat(4, 1fr);
        gap:18px;
        margin-bottom:25px;
    ">


        {{-- MEDIUM --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:20px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
            ">
                MEDIUM FINDINGS
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                margin-top:7px;
            ">
                {{ $medium ?? 0 }}
            </div>

        </div>


        {{-- LOW --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:20px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
            ">
                LOW FINDINGS
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                margin-top:7px;
            ">
                {{ $low ?? 0 }}
            </div>

        </div>


        {{-- TOTAL FINDINGS --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:20px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
            ">
                TOTAL FINDINGS
            </div>

            <div style="
                font-size:30px;
                font-weight:800;
                margin-top:7px;
            ">
                {{
                    ($critical ?? 0)
                    + ($high ?? 0)
                    + ($medium ?? 0)
                    + ($low ?? 0)
                }}
            </div>

        </div>


        {{-- RISK LEVEL --}}
        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:20px;
        ">

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:800;
            ">
                CURRENT RISK
            </div>

            <div style="
                font-size:24px;
                font-weight:800;
                margin-top:10px;
            ">

                @if($latestScore === null)

                    N/A

                @elseif($latestScore >= 80)

                    LOW

                @elseif($latestScore >= 60)

                    MEDIUM

                @elseif($latestScore >= 40)

                    HIGH

                @else

                    CRITICAL

                @endif

            </div>

        </div>

    </div>


    {{-- LATEST ASSESSMENT --}}
    @if($latestAssessment)

        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:24px;
            margin-bottom:25px;
        ">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:flex-start;
                gap:20px;
                margin-bottom:20px;
            ">

                <div>

                    <div style="
                        font-size:11px;
                        color:#6c757d;
                        font-weight:800;
                        letter-spacing:.4px;
                    ">
                        LATEST ASSESSMENT
                    </div>

                    <h2 style="
                        margin:6px 0 4px;
                        font-size:20px;
                    ">
                        Assessment #{{ $latestAssessment->id }}
                    </h2>

                    <div style="
                        color:#6c757d;
                        font-size:13px;
                    ">
                        {{ $latestAssessment->database_name ?? '-' }}
                    </div>

                </div>


                <div style="
                    text-align:right;
                ">

                    <div style="
                        font-size:32px;
                        font-weight:800;
                    ">
                        {{ $latestAssessment->score ?? 0 }}/100
                    </div>

                    <div style="
                        font-size:11px;
                        color:#6c757d;
                    ">
                        Security Score
                    </div>

                </div>

            </div>


            <div style="
                display:grid;
                grid-template-columns:
                    repeat(5, 1fr);
                gap:12px;
            ">

                <div style="
                    background:#f8f9fa;
                    border-radius:8px;
                    padding:14px;
                ">
                    <div style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    ">
                        CRITICAL
                    </div>

                    <div style="
                        margin-top:5px;
                        font-size:22px;
                        font-weight:800;
                    ">
                        {{ $latestAssessment->critical_count ?? 0 }}
                    </div>
                </div>


                <div style="
                    background:#f8f9fa;
                    border-radius:8px;
                    padding:14px;
                ">
                    <div style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    ">
                        HIGH
                    </div>

                    <div style="
                        margin-top:5px;
                        font-size:22px;
                        font-weight:800;
                    ">
                        {{ $latestAssessment->high_count ?? 0 }}
                    </div>
                </div>


                <div style="
                    background:#f8f9fa;
                    border-radius:8px;
                    padding:14px;
                ">
                    <div style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    ">
                        MEDIUM
                    </div>

                    <div style="
                        margin-top:5px;
                        font-size:22px;
                        font-weight:800;
                    ">
                        {{ $latestAssessment->medium_count ?? 0 }}
                    </div>
                </div>


                <div style="
                    background:#f8f9fa;
                    border-radius:8px;
                    padding:14px;
                ">
                    <div style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    ">
                        LOW
                    </div>

                    <div style="
                        margin-top:5px;
                        font-size:22px;
                        font-weight:800;
                    ">
                        {{ $latestAssessment->low_count ?? 0 }}
                    </div>
                </div>


                <div style="
                    background:#f8f9fa;
                    border-radius:8px;
                    padding:14px;
                ">
                    <div style="
                        font-size:10px;
                        color:#6c757d;
                        font-weight:700;
                    ">
                        STATUS
                    </div>

                    <div style="
                        margin-top:5px;
                        font-size:14px;
                        font-weight:800;
                    ">
                        {{ strtoupper($latestAssessment->status ?? '-') }}
                    </div>
                </div>

            </div>


            <div style="
                margin-top:20px;
                display:flex;
                gap:10px;
                flex-wrap:wrap;
            ">

                <a
                    href="{{ route('security-reports.show', $latestAssessment) }}"
                    style="
                        padding:9px 14px;
                        border-radius:7px;
                        background:#212529;
                        color:#fff;
                        text-decoration:none;
                        font-size:12px;
                        font-weight:700;
                    "
                >
                    View Report
                </a>

                <a
                    href="{{ route('security-reports.print', $latestAssessment) }}"
                    target="_blank"
                    style="
                        padding:9px 14px;
                        border-radius:7px;
                        border:1px solid #dee2e6;
                        background:#fff;
                        color:#212529;
                        text-decoration:none;
                        font-size:12px;
                        font-weight:700;
                    "
                >
                    Print Report
                </a>

                <a
                    href="{{ route('security-reports.comparison', $latestAssessment) }}"
                    style="
                        padding:9px 14px;
                        border-radius:7px;
                        border:1px solid #dee2e6;
                        background:#fff;
                        color:#212529;
                        text-decoration:none;
                        font-size:12px;
                        font-weight:700;
                    "
                >
                    Compare
                </a>

            </div>

        </div>

    @else

        <div style="
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:12px;
            padding:55px 30px;
            text-align:center;
            margin-bottom:25px;
        ">

            <div style="
                font-size:42px;
                margin-bottom:12px;
            ">
                📊
            </div>

            <h2 style="
                margin:0 0 8px;
                font-size:21px;
            ">
                No Security Assessment Yet
            </h2>

            <p style="
                margin:0;
                color:#6c757d;
                font-size:14px;
            ">
                Jalankan vulnerability assessment terlebih dahulu
                untuk menghasilkan security report.
            </p>

        </div>

    @endif


    {{-- ASSESSMENT HISTORY --}}
    <div style="
        background:#fff;
        border:1px solid #dee2e6;
        border-radius:12px;
        overflow:hidden;
    ">

        <div style="
            padding:20px 22px;
            border-bottom:1px solid #dee2e6;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:15px;
        ">

            <div>

                <h2 style="
                    margin:0;
                    font-size:18px;
                ">
                    Assessment History
                </h2>

                <div style="
                    margin-top:4px;
                    color:#6c757d;
                    font-size:12px;
                ">
                    Riwayat security assessment database
                </div>

            </div>

        </div>


        @if(isset($assessments) && $assessments->count())

            <div style="
                overflow-x:auto;
            ">

                <table style="
                    width:100%;
                    border-collapse:collapse;
                    min-width:850px;
                ">

                    <thead>

                        <tr style="
                            background:#f8f9fa;
                        ">

                            <th style="
                                padding:13px 18px;
                                text-align:left;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                ID
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:left;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                DATABASE
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:center;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                SCORE
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:center;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                CRITICAL
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:center;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                HIGH
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:center;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                MEDIUM
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:center;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                LOW
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:left;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                DATE
                            </th>

                            <th style="
                                padding:13px 18px;
                                text-align:right;
                                font-size:11px;
                                color:#6c757d;
                            ">
                                ACTION
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($assessments as $assessment)

                            <tr>

                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    font-weight:700;
                                ">
                                    #{{ $assessment->id }}
                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                ">

                                    <div style="
                                        font-weight:700;
                                    ">
                                        {{ $assessment->database_name ?? '-' }}
                                    </div>

                                    @if($assessment->databaseConnection)

                                        <div style="
                                            margin-top:3px;
                                            font-size:11px;
                                            color:#6c757d;
                                        ">
                                            {{ $assessment->databaseConnection->name }}
                                        </div>

                                    @endif

                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    text-align:center;
                                ">

                                    <span style="
                                        font-weight:800;
                                    ">
                                        {{ $assessment->score ?? 0 }}
                                    </span>

                                    <span style="
                                        color:#6c757d;
                                        font-size:11px;
                                    ">
                                        /100
                                    </span>

                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    text-align:center;
                                    font-weight:700;
                                ">
                                    {{ $assessment->critical_count ?? 0 }}
                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    text-align:center;
                                    font-weight:700;
                                ">
                                    {{ $assessment->high_count ?? 0 }}
                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    text-align:center;
                                    font-weight:700;
                                ">
                                    {{ $assessment->medium_count ?? 0 }}
                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    text-align:center;
                                    font-weight:700;
                                ">
                                    {{ $assessment->low_count ?? 0 }}
                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    color:#6c757d;
                                    font-size:12px;
                                ">

                                    @if($assessment->scanned_at)

                                        {{ $assessment->scanned_at->format('d M Y H:i') }}

                                    @else

                                        -

                                    @endif

                                </td>


                                <td style="
                                    padding:15px 18px;
                                    border-top:1px solid #eee;
                                    text-align:right;
                                ">

                                    <div style="
                                        display:flex;
                                        justify-content:flex-end;
                                        gap:7px;
                                    ">

                                        <a
                                            href="{{ route(
                                                'security-reports.show',
                                                $assessment
                                            ) }}"
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


                                        <a
                                            href="{{ route(
                                                'security-reports.comparison',
                                                $assessment
                                            ) }}"
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
                                            Compare
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- PAGINATION --}}

            @if(method_exists($assessments, 'links'))

                <div style="
                    padding:18px 22px;
                    border-top:1px solid #dee2e6;
                ">
                    {{ $assessments->links() }}
                </div>

            @endif

        @else

            <div style="
                padding:45px 25px;
                text-align:center;
                color:#6c757d;
                font-size:14px;
            ">
                Belum ada security assessment.
            </div>

        @endif

    </div>

</div>


<style>

@media (max-width:1100px) {

    div[style*="repeat(4, 1fr)"] {
        grid-template-columns:repeat(2, 1fr) !important;
    }

    div[style*="repeat(5, 1fr)"] {
        grid-template-columns:repeat(3, 1fr) !important;
    }

}


@media (max-width:700px) {

    div[style*="repeat(4, 1fr)"] {
        grid-template-columns:1fr !important;
    }

    div[style*="repeat(5, 1fr)"] {
        grid-template-columns:1fr 1fr !important;
    }

}

</style>

@endsection