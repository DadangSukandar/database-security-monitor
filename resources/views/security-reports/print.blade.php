<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Security Report #{{ $assessment->id }}
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            padding: 0;
            background: #f5f6f8;
            color: #212529;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }


        .container {
            width: 100%;
            max-width: 1100px;
            margin: 30px auto;
            background: #ffffff;
            padding: 40px;
        }


        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #212529;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }


        .title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }


        .subtitle {
            margin-top: 5px;
            color: #6c757d;
        }


        .report-number {
            text-align: right;
            color: #6c757d;
        }


        .report-number strong {
            display: block;
            color: #212529;
            font-size: 15px;
        }


        .score-section {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }


        .score-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
        }


        .score-label {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 5px;
        }


        .score {
            font-size: 70px;
            line-height: 1;
            font-weight: 700;
        }


        .score-good {
            color: #198754;
        }


        .score-warning {
            color: #fd7e14;
        }


        .score-danger {
            color: #dc3545;
        }


        .score-status {
            margin-top: 10px;
            font-weight: 700;
            font-size: 15px;
        }


        .information-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }


        .section-title {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 15px 0;
        }


        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
        }


        .info-item label {
            display: block;
            color: #6c757d;
            font-size: 11px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }


        .info-item strong {
            font-size: 14px;
        }


        .summary {
            margin-bottom: 25px;
        }


        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);
            gap: 12px;
        }


        .summary-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 18px;
        }


        .summary-card .label {
            color: #6c757d;
            font-size: 12px;
        }


        .summary-card .number {
            font-size: 30px;
            font-weight: 700;
            margin-top: 5px;
        }


        .critical {
            color: #dc3545;
        }


        .high {
            color: #fd7e14;
        }


        .medium {
            color: #0dcaf0;
        }


        .low {
            color: #198754;
        }


        .findings {
            margin-top: 30px;
        }


        .finding {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 20px;
            page-break-inside: avoid;
        }


        .finding-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 12px;
        }


        .finding-title {
            font-size: 16px;
            font-weight: 700;
        }


        .badge {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }


        .badge-critical {
            background: #f8d7da;
            color: #842029;
        }


        .badge-high {
            background: #ffe5d0;
            color: #984c0c;
        }


        .badge-medium {
            background: #cff4fc;
            color: #055160;
        }


        .badge-low {
            background: #d1e7dd;
            color: #0f5132;
        }


        .badge-open {
            background: #f8d7da;
            color: #842029;
        }


        .badge-resolved {
            background: #d1e7dd;
            color: #0f5132;
        }


        .rule {
            margin-bottom: 10px;
            color: #6c757d;
        }


        code {
            background: #f1f3f5;
            border-radius: 3px;
            padding: 2px 5px;
            font-family:
                Consolas,
                monospace;
        }


        .finding-block {
            margin-top: 12px;
        }


        .finding-block-title {
            font-weight: 700;
            margin-bottom: 4px;
        }


        .finding-description {
            color: #495057;
        }


        .evidence {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            font-family:
                Consolas,
                monospace;
            font-size: 11px;
            white-space: pre-wrap;
            word-break: break-word;
        }


        .recommendation {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 5px;
            padding: 12px;
        }


        .footer {
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 11px;
        }


        .no-findings {
            text-align: center;
            padding: 40px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }


        .no-findings-title {
            font-size: 18px;
            font-weight: 700;
            color: #198754;
        }


        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 18px;
            border: 0;
            border-radius: 5px;
            background: #212529;
            color: #ffffff;
            cursor: pointer;
            font-weight: 600;
        }


        @media print {

            body {
                background: #ffffff;
            }


            .container {
                max-width: none;
                margin: 0;
                padding: 20px;
            }


            .print-button {
                display: none;
            }


            .score-section {
                grid-template-columns:
                    220px 1fr;
            }


            .finding {
                page-break-inside: avoid;
            }


            @page {
                size: A4;
                margin: 12mm;
            }

        }


        @media (max-width: 768px) {

            .container {
                padding: 20px;
                margin: 0;
            }


            .score-section {
                grid-template-columns: 1fr;
            }


            .summary-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .info-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<button
    class="print-button"
    onclick="window.print()"
>
    Print Report
</button>


@php

    $score = (int) (
        $assessment->score ?? 0
    );


    if ($score >= 80) {

        $scoreClass = 'score-good';

        $scoreStatus = 'GOOD';

    } elseif ($score >= 60) {

        $scoreClass = 'score-warning';

        $scoreStatus = 'NEEDS IMPROVEMENT';

    } else {

        $scoreClass = 'score-danger';

        $scoreStatus = 'CRITICAL';

    }

@endphp


<div class="container">


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="header">

        <div>

            <h1 class="title">
                Security Assessment Report
            </h1>

            <div class="subtitle">
                Database Security & Vulnerability Assessment
            </div>

        </div>


        <div class="report-number">

            Report

            <strong>
                #{{ $assessment->id }}
            </strong>

            Generated:

            <strong>
                {{ now()->format('d F Y H:i') }}
            </strong>

        </div>

    </div>


    {{-- =====================================================
         SCORE + INFORMATION
    ====================================================== --}}

    <div class="score-section">


        <div class="score-card">

            <div class="score-label">
                SECURITY SCORE
            </div>


            <div class="score {{ $scoreClass }}">
                {{ $score }}
            </div>


            <div>
                / 100
            </div>


            <div class="score-status {{ $scoreClass }}">
                {{ $scoreStatus }}
            </div>

        </div>


        <div class="information-card">

            <h2 class="section-title">
                Assessment Information
            </h2>


            <div class="info-grid">


                <div class="info-item">

                    <label>
                        Database Connection
                    </label>

                    <strong>
                        {{ $assessment->databaseConnection->name ?? '-' }}
                    </strong>

                </div>


                <div class="info-item">

                    <label>
                        Driver
                    </label>

                    <strong>
                        {{
                            strtoupper(
                                $assessment
                                    ->databaseConnection
                                    ->driver
                                ?? '-'
                            )
                        }}
                    </strong>

                </div>


                <div class="info-item">

                    <label>
                        Database
                    </label>

                    <strong>
                        {{ $assessment->database_name ?? '-' }}
                    </strong>

                </div>


                <div class="info-item">

                    <label>
                        Status
                    </label>

                    <strong>
                        {{ $assessment->status ?? '-' }}
                    </strong>

                </div>


                <div class="info-item">

                    <label>
                        Scan Date
                    </label>

                    <strong>

                        @if($assessment->scanned_at)

                            {{ $assessment->scanned_at->format(
                                'd F Y H:i:s'
                            ) }}

                        @else

                            -

                        @endif

                    </strong>

                </div>


                <div class="info-item">

                    <label>
                        Assessment ID
                    </label>

                    <strong>
                        #{{ $assessment->id }}
                    </strong>

                </div>


            </div>

        </div>

    </div>


    {{-- =====================================================
         SUMMARY
    ====================================================== --}}

    <div class="summary">

        <h2 class="section-title">
            Vulnerability Summary
        </h2>


        <div class="summary-grid">


            <div class="summary-card">

                <div class="label">
                    CRITICAL
                </div>

                <div class="number critical">
                    {{ $critical }}
                </div>

            </div>


            <div class="summary-card">

                <div class="label">
                    HIGH
                </div>

                <div class="number high">
                    {{ $high }}
                </div>

            </div>


            <div class="summary-card">

                <div class="label">
                    MEDIUM
                </div>

                <div class="number medium">
                    {{ $medium }}
                </div>

            </div>


            <div class="summary-card">

                <div class="label">
                    LOW
                </div>

                <div class="number low">
                    {{ $low }}
                </div>

            </div>


        </div>

    </div>


    {{-- =====================================================
         FINDINGS
    ====================================================== --}}

    <div class="findings">

        <h2 class="section-title">
            Security Findings
        </h2>


        @forelse(
            $assessment->findings
                ->sortByDesc(function ($finding) {

                    return match (
                        strtoupper(
                            $finding->severity ?? 'LOW'
                        )
                    ) {

                        'CRITICAL' => 4,

                        'HIGH' => 3,

                        'MEDIUM' => 2,

                        default => 1,

                    };

                })
            as $finding
        )


            @php

                $severity =
                    strtoupper(
                        $finding->severity ?? 'LOW'
                    );


                $badgeClass = match ($severity) {

                    'CRITICAL' =>
                        'badge-critical',

                    'HIGH' =>
                        'badge-high',

                    'MEDIUM' =>
                        'badge-medium',

                    default =>
                        'badge-low',

                };

            @endphp


            <div class="finding">


                <div class="finding-header">


                    <div>

                        <div class="finding-title">

                            {{ $finding->title ?? 'Security Finding' }}

                        </div>


                        @if($finding->rule_code)

                            <div class="rule">

                                Rule:

                                <code>
                                    {{ $finding->rule_code }}
                                </code>

                            </div>

                        @endif

                    </div>


                    <div>

                        <span class="badge {{ $badgeClass }}">
                            {{ $severity }}
                        </span>


                        @if($finding->resolved)

                            <span class="badge badge-resolved">
                                RESOLVED
                            </span>

                        @else

                            <span class="badge badge-open">
                                OPEN
                            </span>

                        @endif

                    </div>


                </div>


                @if($finding->category)

                    <div class="finding-block">

                        <div class="finding-block-title">
                            Category
                        </div>

                        <div>
                            {{ $finding->category }}
                        </div>

                    </div>

                @endif


                @if($finding->description)

                    <div class="finding-block">

                        <div class="finding-block-title">
                            Description
                        </div>

                        <div class="finding-description">

                            {{ $finding->description }}

                        </div>

                    </div>

                @endif


                @if(
                    $finding->database_name ||
                    $finding->username ||
                    $finding->host
                )

                    <div class="finding-block">

                        <div class="finding-block-title">
                            Account / Database
                        </div>


                        @if($finding->database_name)

                            <div>

                                Database:

                                <code>
                                    {{ $finding->database_name }}
                                </code>

                            </div>

                        @endif


                        @if($finding->username)

                            <div>

                                Username:

                                <code>
                                    {{ $finding->username }}
                                </code>

                            </div>

                        @endif


                        @if($finding->host)

                            <div>

                                Host:

                                <code>
                                    {{ $finding->host }}
                                </code>

                            </div>

                        @endif

                    </div>

                @endif


                @if($finding->evidence)

                    <div class="finding-block">

                        <div class="finding-block-title">
                            Evidence
                        </div>

                        <div class="evidence">

                            {{ $finding->evidence }}

                        </div>

                    </div>

                @endif


                @if($finding->recommendation)

                    <div class="finding-block">

                        <div class="finding-block-title">
                            Recommendation
                        </div>

                        <div class="recommendation">

                            {{ $finding->recommendation }}

                        </div>

                    </div>

                @endif


            </div>


        @empty


            <div class="no-findings">

                <div class="no-findings-title">
                    No Vulnerabilities Found
                </div>

                <div>
                    Tidak ditemukan security finding
                    berdasarkan rule assessment yang dijalankan.
                </div>

            </div>


        @endforelse


    </div>


    {{-- =====================================================
         FOOTER
    ====================================================== --}}

    <div class="footer">

        <div>
            Database Security Assessment
        </div>


        <div>
            Assessment #{{ $assessment->id }}
        </div>

    </div>


</div>


</body>

</html>