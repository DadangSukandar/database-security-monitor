@extends('app')

@section('content')

<style>
    .risk-page {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px;
    }

    .risk-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 28px;
    }

    .risk-title {
        margin: 0;
        font-size: 30px;
        font-weight: 700;
        color: #212529;
    }

    .risk-subtitle {
        margin-top: 7px;
        color: #6c757d;
        font-size: 14px;
    }

    .risk-header-actions {
        display: flex;
        gap: 10px;
    }

    .risk-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 15px;
        border-radius: 7px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #343a40;
    }

    .risk-btn:hover {
        background: #f8f9fa;
    }

    .risk-btn-primary {
        background: #212529;
        color: #fff;
        border-color: #212529;
    }

    .risk-btn-primary:hover {
        background: #343a40;
        color: #fff;
    }

    .risk-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    .risk-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
    }

    .risk-card-label {
        color: #6c757d;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .risk-card-value {
        margin-top: 8px;
        font-size: 28px;
        font-weight: 700;
        color: #212529;
    }

    .risk-card-description {
        margin-top: 5px;
        color: #6c757d;
        font-size: 12px;
    }

    .score-layout {
        display: grid;
        grid-template-columns: 330px 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .score-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 25px;
    }

    .section-title {
        margin: 0 0 18px;
        font-size: 16px;
        font-weight: 700;
        color: #212529;
    }

    .score-circle {
        width: 190px;
        height: 190px;
        border-radius: 50%;
        margin: 10px auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            conic-gradient(
                #212529 {{ max(0, min(100, $securityScore)) }}%,
                #e9ecef 0
            );
        position: relative;
    }

    .score-circle::after {
        content: "";
        position: absolute;
        width: 145px;
        height: 145px;
        background: #fff;
        border-radius: 50%;
    }

    .score-number {
        position: relative;
        z-index: 2;
        text-align: center;
    }

    .score-number strong {
        display: block;
        font-size: 40px;
        line-height: 1;
        color: #212529;
    }

    .score-number span {
        display: block;
        margin-top: 5px;
        font-size: 11px;
        color: #6c757d;
        font-weight: 700;
    }

    .score-label {
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #495057;
    }

    .risk-level {
        margin-top: 8px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .05em;
    }

    .risk-level-critical {
        color: #842029;
    }

    .risk-level-high {
        color: #a61e2a;
    }

    .risk-level-medium {
        color: #856404;
    }

    .risk-level-low {
        color: #0f5132;
    }

    .risk-level-secure {
        color: #198754;
    }

    .assessment-info {
        margin-top: 20px;
        padding-top: 18px;
        border-top: 1px solid #dee2e6;
        font-size: 12px;
        color: #6c757d;
        line-height: 1.7;
    }

    .severity-list {
        display: flex;
        flex-direction: column;
        gap: 17px;
    }

    .severity-row {
        display: grid;
        grid-template-columns: 100px 1fr 55px;
        gap: 12px;
        align-items: center;
    }

    .severity-name {
        font-size: 12px;
        font-weight: 700;
    }

    .severity-bar {
        height: 10px;
        background: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
    }

    .severity-fill {
        height: 100%;
        border-radius: 10px;
    }

    .severity-fill-critical {
        background: #dc3545;
    }

    .severity-fill-high {
        background: #fd7e14;
    }

    .severity-fill-medium {
        background: #ffc107;
    }

    .severity-fill-low {
        background: #198754;
    }

    .severity-count {
        text-align: right;
        font-size: 12px;
        font-weight: 700;
    }

    .two-column {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .panel {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        overflow: hidden;
    }

    .panel-header {
        padding: 18px 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .panel-header h2 {
        margin: 0;
        font-size: 16px;
    }

    .panel-header span {
        font-size: 11px;
        color: #6c757d;
    }

    .panel-body {
        padding: 20px;
    }

    .risk-item {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        padding: 14px 0;
        border-bottom: 1px solid #f1f3f5;
    }

    .risk-item:last-child {
        border-bottom: 0;
    }

    .risk-item-title {
        font-size: 13px;
        font-weight: 700;
        color: #212529;
    }

    .risk-item-meta {
        margin-top: 5px;
        font-size: 11px;
        color: #6c757d;
    }

    .severity-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 8px;
        border-radius: 5px;
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
        color: #9a3412;
    }

    .badge-medium {
        background: #fff3cd;
        color: #664d03;
    }

    .badge-low {
        background: #d1e7dd;
        color: #0f5132;
    }

    .empty-state {
        padding: 35px 15px;
        text-align: center;
        color: #6c757d;
        font-size: 13px;
    }

    .category-item {
        margin-bottom: 17px;
    }

    .category-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 7px;
        font-size: 12px;
    }

    .category-name {
        font-weight: 700;
    }

    .category-count {
        color: #6c757d;
    }

    .category-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 8px;
        overflow: hidden;
    }

    .category-fill {
        height: 100%;
        background: #495057;
        border-radius: 8px;
    }

    .database-table {
        width: 100%;
        border-collapse: collapse;
    }

    .database-table th {
        text-align: left;
        padding: 11px 0;
        border-bottom: 1px solid #dee2e6;
        font-size: 10px;
        color: #6c757d;
        text-transform: uppercase;
    }

    .database-table td {
        padding: 13px 0;
        border-bottom: 1px solid #f1f3f5;
        font-size: 12px;
    }

    .database-table tr:last-child td {
        border-bottom: 0;
    }

    .database-risk-count {
        text-align: right;
        font-weight: 700;
    }

    .recent-table-wrapper {
        overflow-x: auto;
    }

    .recent-table {
        width: 100%;
        border-collapse: collapse;
    }

    .recent-table th {
        padding: 12px 15px;
        text-align: left;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        color: #6c757d;
        font-size: 10px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .recent-table td {
        padding: 14px 15px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 12px;
        vertical-align: middle;
    }

    .recent-table tr:last-child td {
        border-bottom: 0;
    }

    .finding-link {
        color: #212529;
        text-decoration: none;
        font-weight: 700;
    }

    .finding-link:hover {
        text-decoration: underline;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 5px;
        font-size: 9px;
        font-weight: 700;
    }

    .status-open {
        background: #f8d7da;
        color: #842029;
    }

    .status-resolved {
        background: #d1e7dd;
        color: #0f5132;
    }

    .status-ignored {
        background: #e9ecef;
        color: #495057;
    }

    .trend-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .trend-item {
        display: grid;
        grid-template-columns: 100px 1fr 50px;
        gap: 12px;
        align-items: center;
    }

    .trend-date {
        font-size: 11px;
        color: #6c757d;
    }

    .trend-bar {
        height: 9px;
        background: #e9ecef;
        border-radius: 9px;
        overflow: hidden;
    }

    .trend-fill {
        height: 100%;
        background: #212529;
        border-radius: 9px;
    }

    .trend-score {
        text-align: right;
        font-size: 11px;
        font-weight: 700;
    }

    @media (max-width: 1000px) {
        .risk-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .score-layout,
        .two-column {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 650px) {
        .risk-page {
            padding: 18px;
        }

        .risk-header {
            flex-direction: column;
        }

        .risk-grid {
            grid-template-columns: 1fr;
        }

        .risk-title {
            font-size: 24px;
        }
    }
</style>

<style>
    .risk-page {
        color: var(--g-text);
    }

    .risk-title,
    .risk-card-value,
    .section-title,
    .score-number strong,
    .score-label,
    .risk-item-title,
    .finding-link,
    .severity-name,
    .severity-count,
    .category-name,
    .trend-score {
        color: var(--g-text) !important;
    }

    .risk-subtitle,
    .risk-card-label,
    .risk-card-description,
    .score-number span,
    .assessment-info,
    .panel-header span,
    .risk-item-meta,
    .empty-state,
    .category-count,
    .trend-date,
    .database-table th,
    .recent-table th {
        color: var(--g-muted) !important;
    }

    .risk-card,
    .score-card,
    .panel {
        border-color: var(--g-border) !important;
        background: linear-gradient(145deg, rgba(26, 39, 59, .98), rgba(15, 24, 38, .98)) !important;
        color: var(--g-text) !important;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .17);
    }

    .risk-btn {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
    }

    .risk-btn:hover {
        border-color: var(--g-cyan) !important;
        background: #263a56 !important;
    }

    .risk-btn-primary,
    .risk-btn-primary:hover {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    .score-circle {
        background: conic-gradient(
            var(--g-cyan) {{ max(0, min(100, $securityScore)) }}%,
            #2b3950 0
        ) !important;
        box-shadow: 0 0 34px rgba(51, 177, 255, .12);
    }

    .score-circle::after {
        border: 1px solid var(--g-border-soft);
        background: var(--g-surface) !important;
    }

    .assessment-info,
    .panel-header,
    .risk-item,
    .database-table th,
    .database-table td,
    .recent-table th,
    .recent-table td {
        border-color: var(--g-border-soft) !important;
    }

    .severity-bar,
    .category-bar,
    .trend-bar {
        background: #2b3950 !important;
    }

    .category-fill,
    .trend-fill {
        background: var(--g-cyan) !important;
    }

    .recent-table th {
        background: #25344a !important;
    }

    .recent-table tbody tr:hover td,
    .database-table tbody tr:hover td {
        background: #1b2b42 !important;
    }

    .risk-level-critical,
    .risk-level-high {
        color: #ff8389 !important;
    }

    .risk-level-medium {
        color: #fddc69 !important;
    }

    .risk-level-low,
    .risk-level-secure {
        color: #a7f0ba !important;
    }
</style>


<div class="risk-page">

    {{-- HEADER --}}

    <div class="risk-header">

        <div>

            <div style="
                font-size:11px;
                color:#6c757d;
                font-weight:700;
                text-transform:uppercase;
                letter-spacing:.05em;
                margin-bottom:7px;
            ">
                Security Center
            </div>

            <h1 class="risk-title">
                Security Risk Intelligence
            </h1>

            <div class="risk-subtitle">
                Centralized view of database security posture and active risks.
            </div>

        </div>


        <div class="risk-header-actions">

            <a
                href="{{ route('vulnerability-assessments.index') }}"
                class="risk-btn"
            >
                Vulnerability Assessment
            </a>

            <a
                href="{{ route('security-findings.index') }}"
                class="risk-btn risk-btn-primary"
            >
                View All Findings
            </a>

        </div>

    </div>


    {{-- SUMMARY CARDS --}}

    <div class="risk-grid">

        <div class="risk-card">

            <div class="risk-card-label">
                Security Score
            </div>

            <div class="risk-card-value">
                {{ $securityScore }}/100
            </div>

            <div class="risk-card-description">
                {{ $scoreLabel }}
            </div>

        </div>


        <div class="risk-card">

            <div class="risk-card-label">
                Active Risks
            </div>

            <div class="risk-card-value">
                {{ $openFindings }}
            </div>

            <div class="risk-card-description">
                Findings requiring attention
            </div>

        </div>


        <div class="risk-card">

            <div class="risk-card-label">
                Critical + High
            </div>

            <div class="risk-card-value">
                {{ $openCritical + $openHigh }}
            </div>

            <div class="risk-card-description">
                Highest priority active risks
            </div>

        </div>


        <div class="risk-card">

            <div class="risk-card-label">
                Risk Points
            </div>

            <div class="risk-card-value">
                {{ $riskPoints }}
            </div>

            <div class="risk-card-description">
                {{ $riskStatus }}
            </div>

        </div>

    </div>


    {{-- SCORE + SEVERITY --}}

    <div class="score-layout">

        {{-- SCORE --}}

        <div class="score-card">

            <h2 class="section-title">
                Security Posture
            </h2>


            <div class="score-circle">

                <div class="score-number">

                    <strong>
                        {{ $securityScore }}
                    </strong>

                    <span>
                        OUT OF 100
                    </span>

                </div>

            </div>


            <div class="score-label">
                {{ $scoreLabel }}
            </div>


            <div class="
                risk-level
                @if($riskLevel === 'CRITICAL')
                    risk-level-critical
                @elseif($riskLevel === 'HIGH')
                    risk-level-high
                @elseif($riskLevel === 'MEDIUM')
                    risk-level-medium
                @elseif($riskLevel === 'LOW')
                    risk-level-low
                @else
                    risk-level-secure
                @endif
            ">

                {{ $riskLevel }}

            </div>


            @if($latestAssessment)

                <div class="assessment-info">

                    <strong>
                        Latest Assessment
                    </strong>

                    <br>

                    Assessment #{{ $latestAssessment->id }}

                    <br>

                    @if($latestAssessment->databaseConnection)

                        Database:

                        {{ $latestAssessment->databaseConnection->name }}

                        <br>

                    @endif

                    Scanned:

                    {{ $latestAssessment->scanned_at?->format('d M Y H:i') ?? '-' }}

                </div>

            @endif

        </div>


        {{-- SEVERITY --}}

        <div class="score-card">

            <h2 class="section-title">
                Active Risk Distribution
            </h2>


            @php

                $maxSeverity =
                    max(
                        $openCritical,
                        $openHigh,
                        $openMedium,
                        $openLow,
                        1
                    );

            @endphp


            <div class="severity-list">

                <div class="severity-row">

                    <div class="severity-name">
                        Critical
                    </div>

                    <div class="severity-bar">

                        <div
                            class="severity-fill severity-fill-critical"
                            style="
                                width:
                                {{ ($openCritical / $maxSeverity) * 100 }}%;
                            "
                        ></div>

                    </div>

                    <div class="severity-count">
                        {{ $openCritical }}
                    </div>

                </div>


                <div class="severity-row">

                    <div class="severity-name">
                        High
                    </div>

                    <div class="severity-bar">

                        <div
                            class="severity-fill severity-fill-high"
                            style="
                                width:
                                {{ ($openHigh / $maxSeverity) * 100 }}%;
                            "
                        ></div>

                    </div>

                    <div class="severity-count">
                        {{ $openHigh }}
                    </div>

                </div>


                <div class="severity-row">

                    <div class="severity-name">
                        Medium
                    </div>

                    <div class="severity-bar">

                        <div
                            class="severity-fill severity-fill-medium"
                            style="
                                width:
                                {{ ($openMedium / $maxSeverity) * 100 }}%;
                            "
                        ></div>

                    </div>

                    <div class="severity-count">
                        {{ $openMedium }}
                    </div>

                </div>


                <div class="severity-row">

                    <div class="severity-name">
                        Low
                    </div>

                    <div class="severity-bar">

                        <div
                            class="severity-fill severity-fill-low"
                            style="
                                width:
                                {{ ($openLow / $maxSeverity) * 100 }}%;
                            "
                        ></div>

                    </div>

                    <div class="severity-count">
                        {{ $openLow }}
                    </div>

                </div>

            </div>


            <div style="
                margin-top:28px;
                padding-top:18px;
                border-top:1px solid #dee2e6;
                display:grid;
                grid-template-columns:repeat(3,1fr);
                gap:10px;
            ">

                <div>

                    <div style="
                        font-size:10px;
                        color:#6c757d;
                    ">
                        TOTAL
                    </div>

                    <strong>
                        {{ $totalFindings }}
                    </strong>

                </div>


                <div>

                    <div style="
                        font-size:10px;
                        color:#6c757d;
                    ">
                        RESOLVED
                    </div>

                    <strong>
                        {{ $resolvedFindings }}
                    </strong>

                </div>


                <div>

                    <div style="
                        font-size:10px;
                        color:#6c757d;
                    ">
                        IGNORED
                    </div>

                    <strong>
                        {{ $ignoredFindings }}
                    </strong>

                </div>

            </div>

        </div>

    </div>


    {{-- TOP RISKS + DATABASE RISK --}}

    <div class="two-column">

        {{-- TOP RISKS --}}

        <div class="panel">

            <div class="panel-header">

                <h2>
                    Top Active Risks
                </h2>

                <span>
                    Highest priority
                </span>

            </div>


            <div class="panel-body">

                @forelse($topRisks as $risk)

                    @php

                        $severityValue =
                            strtoupper(
                                $risk->severity ?? 'LOW'
                            );

                    @endphp


                    <div class="risk-item">

                        <div>

                            <a
                                href="{{ route(
                                    'security-findings.show',
                                    $risk
                                ) }}"
                                class="finding-link"
                            >
                                {{ $risk->title }}
                            </a>

                            <div class="risk-item-meta">

                                {{ $risk->rule_code }}

                                @if($risk->database_name)
                                    · {{ $risk->database_name }}
                                @endif

                            </div>

                        </div>


                        <div>

                            <span class="
                                severity-badge
                                @if($severityValue === 'CRITICAL')
                                    badge-critical
                                @elseif($severityValue === 'HIGH')
                                    badge-high
                                @elseif($severityValue === 'MEDIUM')
                                    badge-medium
                                @else
                                    badge-low
                                @endif
                            ">

                                {{ $severityValue }}

                            </span>

                        </div>

                    </div>

                @empty

                    <div class="empty-state">

                        No active security risks detected.

                    </div>

                @endforelse

            </div>

        </div>


        {{-- DATABASE RISK --}}

        <div class="panel">

            <div class="panel-header">

                <h2>
                    Database Risk
                </h2>

                <span>
                    Active findings
                </span>

            </div>


            <div class="panel-body">

                @if($databaseRisk->count() > 0)

                    <table class="database-table">

                        <thead>

                            <tr>

                                <th>
                                    Database
                                </th>

                                <th style="text-align:right;">
                                    Active Risks
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($databaseRisk as $database)

                                <tr>

                                    <td>
                                        {{ $database->database_name }}
                                    </td>

                                    <td class="database-risk-count">
                                        {{ $database->total }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                @else

                    <div class="empty-state">

                        No database risks detected.

                    </div>

                @endif

            </div>

        </div>

    </div>


    {{-- CATEGORY + TREND --}}

    <div class="two-column">

        {{-- CATEGORY --}}

        <div class="panel">

            <div class="panel-header">

                <h2>
                    Risk Categories
                </h2>

                <span>
                    All findings
                </span>

            </div>


            <div class="panel-body">

                @php

                    $maxCategory =
                        max(
                            $categoryDistribution->max('total') ?? 0,
                            1
                        );

                @endphp


                @forelse(
                    $categoryDistribution
                    as $category
                )

                    <div class="category-item">

                        <div class="category-header">

                            <span class="category-name">
                                {{ $category->category }}
                            </span>

                            <span class="category-count">
                                {{ $category->total }}
                            </span>

                        </div>


                        <div class="category-bar">

                            <div
                                class="category-fill"
                                style="
                                    width:
                                    {{ ($category->total / $maxCategory) * 100 }}%;
                                "
                            ></div>

                        </div>

                    </div>

                @empty

                    <div class="empty-state">

                        No category data available.

                    </div>

                @endforelse

            </div>

        </div>


        {{-- TREND --}}

        <div class="panel">

            <div class="panel-header">

                <h2>
                    Security Score Trend
                </h2>

                <span>
                    Recent assessments
                </span>

            </div>


            <div class="panel-body">

                @forelse($riskTrend as $trend)

                    <div class="trend-item">

                        <div class="trend-date">

                            {{ $trend->scanned_at?->format('d M Y') ?? '-' }}

                        </div>


                        <div class="trend-bar">

                            <div
                                class="trend-fill"
                                style="
                                    width:
                                    {{ max(
                                        0,
                                        min(
                                            100,
                                            (int) $trend->score
                                        )
                                    ) }}%;
                                "
                            ></div>

                        </div>


                        <div class="trend-score">

                            {{ $trend->score }}/100

                        </div>

                    </div>

                @empty

                    <div class="empty-state">

                        No assessment history available.

                    </div>

                @endforelse

            </div>

        </div>

    </div>


    {{-- RECENT FINDINGS --}}

    <div class="panel">

        <div class="panel-header">

            <h2>
                Recent Security Findings
            </h2>

            <a
                href="{{ route('security-findings.index') }}"
                style="
                    color:#495057;
                    text-decoration:none;
                    font-size:11px;
                    font-weight:700;
                "
            >
                View all →
            </a>

        </div>


        <div class="recent-table-wrapper">

            @if($recentFindings->count() > 0)

                <table class="recent-table">

                    <thead>

                        <tr>

                            <th>
                                Finding
                            </th>

                            <th>
                                Rule
                            </th>

                            <th>
                                Severity
                            </th>

                            <th>
                                Database
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Detected
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($recentFindings as $finding)

                            @php

                                $severityValue =
                                    strtoupper(
                                        $finding->severity ?? 'LOW'
                                    );

                                $statusValue =
                                    strtoupper(
                                        $finding->status ?? (
                                            $finding->resolved
                                            ? 'RESOLVED'
                                            : 'OPEN'
                                        )
                                    );

                            @endphp


                            <tr>

                                <td>

                                    <a
                                        href="{{ route(
                                            'security-findings.show',
                                            $finding
                                        ) }}"
                                        class="finding-link"
                                    >
                                        {{ $finding->title }}
                                    </a>

                                </td>


                                <td>

                                    <code>
                                        {{ $finding->rule_code }}
                                    </code>

                                </td>


                                <td>

                                    <span class="
                                        severity-badge
                                        @if($severityValue === 'CRITICAL')
                                            badge-critical
                                        @elseif($severityValue === 'HIGH')
                                            badge-high
                                        @elseif($severityValue === 'MEDIUM')
                                            badge-medium
                                        @else
                                            badge-low
                                        @endif
                                    ">

                                        {{ $severityValue }}

                                    </span>

                                </td>


                                <td>

                                    {{ $finding->database_name ?? '-' }}

                                </td>


                                <td>

                                    <span class="
                                        status-badge
                                        @if($statusValue === 'RESOLVED')
                                            status-resolved
                                        @elseif($statusValue === 'IGNORED')
                                            status-ignored
                                        @else
                                            status-open
                                        @endif
                                    ">

                                        {{ $statusValue }}

                                    </span>

                                </td>


                                <td>

                                    {{ $finding->created_at?->format(
                                        'd M Y H:i'
                                    ) ?? '-' }}

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            @else

                <div class="empty-state">

                    No security findings available.

                </div>

            @endif

        </div>

    </div>

</div>

@endsection
