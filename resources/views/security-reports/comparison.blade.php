@extends('app')

@section('content')

<div
    style="
        max-width:1400px;
        margin:0 auto;
        padding:30px;
    "
>

    {{-- HEADER --}}

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:25px;
        "
    >

        <div>

            <div
                style="
                    font-size:13px;
                    color:#6c757d;
                    margin-bottom:5px;
                "
            >
                SECURITY REPORT
            </div>

            <h1
                style="
                    margin:0;
                    font-size:28px;
                    font-weight:700;
                "
            >
                Assessment Comparison
            </h1>

            <div
                style="
                    margin-top:6px;
                    color:#6c757d;
                "
            >
                {{ $assessment->database_name }}
            </div>

        </div>


        <div
            style="
                display:flex;
                gap:10px;
            "
        >

            <a
                href="{{ route(
                    'security-reports.show',
                    $assessment
                ) }}"
                style="
                    padding:10px 16px;
                    border:1px solid #dee2e6;
                    border-radius:7px;
                    background:#ffffff;
                    color:#212529;
                    text-decoration:none;
                    font-weight:600;
                "
            >
                ← Back
            </a>

        </div>

    </div>


    {{-- NO PREVIOUS ASSESSMENT --}}

    @if (!$previousAssessment)

        <div
            style="
                background:#ffffff;
                border:1px solid #dee2e6;
                border-radius:12px;
                padding:50px;
                text-align:center;
            "
        >

            <div
                style="
                    font-size:45px;
                    margin-bottom:15px;
                "
            >
                📊
            </div>

            <h2
                style="
                    margin:0 0 10px;
                "
            >
                No Previous Assessment
            </h2>

            <p
                style="
                    margin:0;
                    color:#6c757d;
                "
            >
                Belum ada assessment sebelumnya untuk
                database ini.
            </p>

        </div>

    @else


        {{-- SCORE COMPARISON --}}

        <div
            style="
                display:grid;
                grid-template-columns:
                    1fr 1fr 1fr;
                gap:18px;
                margin-bottom:25px;
            "
        >

            {{-- PREVIOUS --}}

            <div
                style="
                    background:#ffffff;
                    border:1px solid #dee2e6;
                    border-radius:12px;
                    padding:25px;
                "
            >

                <div
                    style="
                        font-size:12px;
                        font-weight:700;
                        color:#6c757d;
                        margin-bottom:10px;
                    "
                >
                    PREVIOUS SCORE
                </div>

                <div
                    style="
                        font-size:48px;
                        font-weight:800;
                    "
                >
                    {{ $previousAssessment->score }}
                </div>

                <div
                    style="
                        color:#6c757d;
                        margin-top:5px;
                    "
                >
                    Assessment #{{ $previousAssessment->id }}
                </div>

            </div>


            {{-- CURRENT --}}

            <div
                style="
                    background:#ffffff;
                    border:1px solid #dee2e6;
                    border-radius:12px;
                    padding:25px;
                "
            >

                <div
                    style="
                        font-size:12px;
                        font-weight:700;
                        color:#6c757d;
                        margin-bottom:10px;
                    "
                >
                    CURRENT SCORE
                </div>

                <div
                    style="
                        font-size:48px;
                        font-weight:800;
                    "
                >
                    {{ $assessment->score }}
                </div>

                <div
                    style="
                        color:#6c757d;
                        margin-top:5px;
                    "
                >
                    Assessment #{{ $assessment->id }}
                </div>

            </div>


            {{-- CHANGE --}}

            <div
                style="
                    background:#ffffff;
                    border:1px solid #dee2e6;
                    border-radius:12px;
                    padding:25px;
                "
            >

                <div
                    style="
                        font-size:12px;
                        font-weight:700;
                        color:#6c757d;
                        margin-bottom:10px;
                    "
                >
                    SCORE CHANGE
                </div>

                <div
                    style="
                        font-size:48px;
                        font-weight:800;
                    "
                >

                    @if ($scoreChange > 0)

                        +{{ $scoreChange }}

                    @elseif ($scoreChange < 0)

                        {{ $scoreChange }}

                    @else

                        0

                    @endif

                </div>

                <div
                    style="
                        color:#6c757d;
                        margin-top:5px;
                    "
                >

                    @if ($scoreChange > 0)

                        Security improved

                    @elseif ($scoreChange < 0)

                        Security decreased

                    @else

                        No change

                    @endif

                </div>

            </div>

        </div>


        {{-- FINDING COMPARISON --}}

        <div
            style="
                background:#ffffff;
                border:1px solid #dee2e6;
                border-radius:12px;
                overflow:hidden;
                margin-bottom:25px;
            "
        >

            <div
                style="
                    padding:20px 22px;
                    border-bottom:1px solid #dee2e6;
                "
            >

                <h2
                    style="
                        margin:0;
                        font-size:18px;
                    "
                >
                    Finding Comparison
                </h2>

            </div>


            <table
                style="
                    width:100%;
                    border-collapse:collapse;
                "
            >

                <thead>

                    <tr
                        style="
                            background:#f8f9fa;
                        "
                    >

                        <th
                            style="
                                text-align:left;
                                padding:13px 20px;
                                font-size:12px;
                            "
                        >
                            SEVERITY
                        </th>

                        <th
                            style="
                                text-align:center;
                                padding:13px;
                                font-size:12px;
                            "
                        >
                            PREVIOUS
                        </th>

                        <th
                            style="
                                text-align:center;
                                padding:13px;
                                font-size:12px;
                            "
                        >
                            CURRENT
                        </th>

                        <th
                            style="
                                text-align:center;
                                padding:13px;
                                font-size:12px;
                            "
                        >
                            CHANGE
                        </th>

                    </tr>

                </thead>


                <tbody>

                    {{-- CRITICAL --}}

                    <tr>

                        <td
                            style="
                                padding:16px 20px;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            Critical
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $previousAssessment->critical_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $assessment->critical_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            {{ $criticalChange > 0 ? '+' : '' }}{{ $criticalChange }}
                        </td>

                    </tr>


                    {{-- HIGH --}}

                    <tr>

                        <td
                            style="
                                padding:16px 20px;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            High
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $previousAssessment->high_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $assessment->high_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            {{ $highChange > 0 ? '+' : '' }}{{ $highChange }}
                        </td>

                    </tr>


                    {{-- MEDIUM --}}

                    <tr>

                        <td
                            style="
                                padding:16px 20px;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            Medium
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $previousAssessment->medium_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $assessment->medium_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            {{ $mediumChange > 0 ? '+' : '' }}{{ $mediumChange }}
                        </td>

                    </tr>


                    {{-- LOW --}}

                    <tr>

                        <td
                            style="
                                padding:16px 20px;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            Low
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $previousAssessment->low_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                            "
                        >
                            {{ $assessment->low_count }}
                        </td>

                        <td
                            style="
                                text-align:center;
                                border-top:1px solid #eee;
                                font-weight:700;
                            "
                        >
                            {{ $lowChange > 0 ? '+' : '' }}{{ $lowChange }}
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>


        {{-- TOTAL FINDINGS --}}

        <div
            style="
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:18px;
            "
        >

            <div
                style="
                    background:#ffffff;
                    border:1px solid #dee2e6;
                    border-radius:12px;
                    padding:22px;
                "
            >

                <div
                    style="
                        font-size:12px;
                        color:#6c757d;
                        font-weight:700;
                    "
                >
                    PREVIOUS TOTAL FINDINGS
                </div>

                <div
                    style="
                        font-size:32px;
                        font-weight:800;
                        margin-top:8px;
                    "
                >
                    {{ $previousTotal }}
                </div>

            </div>


            <div
                style="
                    background:#ffffff;
                    border:1px solid #dee2e6;
                    border-radius:12px;
                    padding:22px;
                "
            >

                <div
                    style="
                        font-size:12px;
                        color:#6c757d;
                        font-weight:700;
                    "
                >
                    CURRENT TOTAL FINDINGS
                </div>

                <div
                    style="
                        font-size:32px;
                        font-weight:800;
                        margin-top:8px;
                    "
                >
                    {{ $currentTotal }}
                </div>

            </div>

        </div>

    @endif

</div>


<style>

@media (max-width: 900px) {

    div[style*="grid-template-columns: 1fr 1fr 1fr"] {
        grid-template-columns:1fr !important;
    }

}

</style>

@endsection