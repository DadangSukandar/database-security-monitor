@extends('app')

@section('content')

<div class="container-fluid py-4">

    {{-- =========================================================
         HEADER
    ========================================================== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <div class="d-flex align-items-center gap-2 mb-1">

                <a
                    href="{{ route('security-reports.index') }}"
                    class="text-decoration-none"
                >
                    Security Reports
                </a>

                <span class="text-muted">
                    /
                </span>

                <span class="text-muted">
                    Assessment #{{ $assessment->id }}
                </span>

            </div>

            <h1 class="h3 mb-1">
                Security Assessment Report
            </h1>

            <p class="text-muted mb-0">
                Detail hasil vulnerability assessment database.
            </p>

        </div>


        <div class="top-left">

            <a
                href="{{ route('security-reports.index') }}"
                class="btn"
            >
                ← Back to Reports
            </a>


            <form
                method="POST"
                action="{{ route(
                    'security-reports.rerun',
                    $assessment
                ) }}"
                style="display:inline;"
                onsubmit="
                    return confirm(
                        'Jalankan assessment ulang untuk database ini?'
                    );
                "
            >

                @csrf

                <button
                    type="submit"
                    class="btn"
                >
                    ↻ Re-run Assessment
                </button>

            </form>

            <a
                href="{{ route(
                    'security-reports.comparison',
                    $assessment
                ) }}"
                class="btn"
            >
                📊 Compare
            </a>

            <a
                href="{{ route(
                    'security-reports.print',
                    $assessment
                ) }}"
                target="_blank"
                class="btn btn-primary"
            >
                <i class="bi bi-printer"></i>
                Print Report
            </a>

        </div>

    </div>


    {{-- =========================================================
         SECURITY SCORE
    ========================================================== --}}

    @php

        $score = (int) (
            $assessment->score ?? 0
        );


        if ($score >= 80) {

            $scoreClass = 'success';

            $scoreLabel = 'Good';

        } elseif ($score >= 60) {

            $scoreClass = 'warning';

            $scoreLabel = 'Needs Improvement';

        } else {

            $scoreClass = 'danger';

            $scoreLabel = 'Critical';

        }

    @endphp


    <div class="row g-4 mb-4">

        {{-- SCORE --}}
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body text-center py-4">

                    <div class="text-muted mb-2">
                        Security Score
                    </div>


                    <div
                        class="display-1 fw-bold text-{{ $scoreClass }}"
                    >
                        {{ $score }}
                    </div>


                    <div class="text-muted mb-3">
                        out of 100
                    </div>


                    <span
                        class="badge bg-{{ $scoreClass }} fs-6 px-3 py-2"
                    >
                        {{ $scoreLabel }}
                    </span>


                    <div class="progress mt-4" style="height: 10px;">

                        <div
                            class="progress-bar bg-{{ $scoreClass }}"
                            role="progressbar"
                            style="width: {{ max(0, min(100, $score)) }}%;"
                            aria-valuenow="{{ $score }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        ></div>

                    </div>

                </div>

            </div>

        </div>


        {{-- DATABASE INFORMATION --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white">

                    <h5 class="mb-0">
                        Assessment Information
                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-4">

                        <div class="col-md-6">

                            <div class="text-muted small">
                                Database Connection
                            </div>

                            <div class="fw-semibold">

                                {{ $assessment->databaseConnection->name ?? '-' }}

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted small">
                                Driver
                            </div>

                            <div class="fw-semibold">

                                {{ strtoupper(
                                    $assessment->databaseConnection->driver ?? '-'
                                ) }}

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted small">
                                Database Name
                            </div>

                            <div class="fw-semibold">

                                {{ $assessment->database_name ?? '-' }}

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted small">
                                Status
                            </div>

                            <div>

                                @php

                                    $status = strtoupper(
                                        $assessment->status ?? ''
                                    );

                                @endphp


                                @if($status === 'COMPLETED')

                                    <span class="badge bg-success">
                                        Completed
                                    </span>

                                @elseif($status === 'SCANNING')

                                    <span class="badge bg-warning text-dark">
                                        Scanning
                                    </span>

                                @elseif($status === 'FAILED')

                                    <span class="badge bg-danger">
                                        Failed
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        {{ $assessment->status ?? '-' }}
                                    </span>

                                @endif

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted small">
                                Scan Date
                            </div>

                            <div class="fw-semibold">

                                @if($assessment->scanned_at)

                                    {{ $assessment->scanned_at->format(
                                        'd F Y H:i:s'
                                    ) }}

                                @else

                                    -

                                @endif

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="text-muted small">
                                Assessment ID
                            </div>

                            <div class="fw-semibold">

                                #{{ $assessment->id }}

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         SEVERITY SUMMARY
    ========================================================== --}}

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Vulnerability Summary
            </h5>

        </div>


        <div class="card-body">

            <div class="row g-3">

                {{-- Critical --}}
                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="text-muted small">
                                    Critical
                                </div>

                                <div class="fs-3 fw-bold text-danger">

                                    {{ $assessment->critical_count ?? 0 }}

                                </div>

                            </div>

                            <div class="fs-2 text-danger">

                                <i class="bi bi-exclamation-octagon"></i>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- High --}}
                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="text-muted small">
                                    High
                                </div>

                                <div class="fs-3 fw-bold text-warning">

                                    {{ $assessment->high_count ?? 0 }}

                                </div>

                            </div>

                            <div class="fs-2 text-warning">

                                <i class="bi bi-exclamation-triangle"></i>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Medium --}}
                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="text-muted small">
                                    Medium
                                </div>

                                <div class="fs-3 fw-bold text-info">

                                    {{ $assessment->medium_count ?? 0 }}

                                </div>

                            </div>

                            <div class="fs-2 text-info">

                                <i class="bi bi-info-circle"></i>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- Low --}}
                <div class="col-md-3">

                    <div class="border rounded p-3">

                        <div class="d-flex justify-content-between">

                            <div>

                                <div class="text-muted small">
                                    Low
                                </div>

                                <div class="fs-3 fw-bold text-success">

                                    {{ $assessment->low_count ?? 0 }}

                                </div>

                            </div>

                            <div class="fs-2 text-success">

                                <i class="bi bi-check-circle"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FINDINGS
    ========================================================== --}}

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-1">
                        Security Findings
                    </h5>

                    <small class="text-muted">
                        Temuan vulnerability dari assessment ini.
                    </small>

                </div>


                <span class="badge bg-dark">

                    {{ $assessment->findings->count() }}
                    Findings

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            @forelse(
                $assessment->findings->sortByDesc(function ($finding) {

                    $priority = [
                        'CRITICAL' => 4,
                        'HIGH' => 3,
                        'MEDIUM' => 2,
                        'LOW' => 1,
                    ];

                    return $priority[
                        strtoupper($finding->severity ?? 'LOW')
                    ] ?? 0;

                })
                as $finding
            )

                @php

                    $severity =
                        strtoupper(
                            $finding->severity ?? 'LOW'
                        );


                    $severityClass = match ($severity) {

                        'CRITICAL' => 'danger',

                        'HIGH' => 'warning',

                        'MEDIUM' => 'info',

                        default => 'success',

                    };


                    $severityIcon = match ($severity) {

                        'CRITICAL' =>
                            'bi-exclamation-octagon-fill',

                        'HIGH' =>
                            'bi-exclamation-triangle-fill',

                        'MEDIUM' =>
                            'bi-info-circle-fill',

                        default =>
                            'bi-check-circle-fill',

                    };

                @endphp


                <div class="border-bottom p-4">

                    <div class="row">

                        {{-- Main --}}
                        <div class="col-lg-9">

                            <div class="d-flex align-items-start gap-3">

                                <div
                                    class="text-{{ $severityClass }} fs-3"
                                >

                                    <i
                                        class="bi {{ $severityIcon }}"
                                    ></i>

                                </div>


                                <div class="flex-grow-1">

                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">

                                        <h5 class="mb-0">

                                            {{ $finding->title ?? 'Security Finding' }}

                                        </h5>


                                        <span
                                            class="badge bg-{{ $severityClass }}"
                                        >

                                            {{ $severity }}

                                        </span>


                                        @if($finding->resolved)

                                            <span class="badge bg-success">

                                                Resolved

                                            </span>

                                        @else

                                            <span class="badge bg-secondary">

                                                Open

                                            </span>

                                        @endif

                                    </div>


                                    {{-- Rule Code --}}
                                    @if($finding->rule_code)

                                        <div class="mb-3">

                                            <span class="text-muted small">
                                                Rule:
                                            </span>

                                            <code>
                                                {{ $finding->rule_code }}
                                            </code>

                                        </div>

                                    @endif


                                    {{-- Category --}}
                                    @if($finding->category)

                                        <div class="mb-3">

                                            <span class="badge bg-light text-dark border">

                                                {{ $finding->category }}

                                            </span>

                                        </div>

                                    @endif


                                    {{-- Description --}}
                                    @if($finding->description)

                                        <div class="mb-3">

                                            <div class="fw-semibold mb-1">
                                                Description
                                            </div>

                                            <div class="text-muted">

                                                {{ $finding->description }}

                                            </div>

                                        </div>

                                    @endif


                                    {{-- Evidence --}}
                                    @if($finding->evidence)

                                        <div class="mb-3">

                                            <div class="fw-semibold mb-1">
                                                Evidence
                                            </div>

                                            <pre class="bg-light border rounded p-3 mb-0 small"
                                                style="white-space: pre-wrap; word-break: break-word;">{{ $finding->evidence }}</pre>

                                        </div>

                                    @endif


                                    {{-- Account --}}
                                    @if(
                                        $finding->username ||
                                        $finding->host
                                    )

                                        <div class="mb-3">

                                            <div class="fw-semibold mb-1">
                                                Account
                                            </div>

                                            <div>

                                                @if($finding->username)

                                                    <span class="me-3">

                                                        <span class="text-muted">
                                                            User:
                                                        </span>

                                                        <code>
                                                            {{ $finding->username }}
                                                        </code>

                                                    </span>

                                                @endif


                                                @if($finding->host)

                                                    <span>

                                                        <span class="text-muted">
                                                            Host:
                                                        </span>

                                                        <code>
                                                            {{ $finding->host }}
                                                        </code>

                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    @endif


                                    {{-- Recommendation --}}
                                    @if($finding->recommendation)

                                        <div>

                                            <div class="fw-semibold mb-1">
                                                Recommendation
                                            </div>

                                            <div class="alert alert-light border mb-0">

                                                <i class="bi bi-lightbulb me-1"></i>

                                                {{ $finding->recommendation }}

                                            </div>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>


                        {{-- Side information --}}
                        <div class="col-lg-3 mt-3 mt-lg-0">

                            <div class="border rounded p-3 bg-light">

                                <div class="text-muted small mb-1">
                                    Database
                                </div>

                                <div class="fw-semibold mb-3">

                                    {{ $finding->database_name ?? '-' }}

                                </div>


                                @if($finding->username)

                                    <div class="text-muted small mb-1">
                                        Username
                                    </div>

                                    <div class="fw-semibold mb-3">

                                        {{ $finding->username }}

                                    </div>

                                @endif


                                @if($finding->host)

                                    <div class="text-muted small mb-1">
                                        Host
                                    </div>

                                    <div class="fw-semibold mb-3">

                                        {{ $finding->host }}

                                    </div>

                                @endif


                                <div class="text-muted small mb-1">
                                    Status
                                </div>

                                @if($finding->resolved)

                                    <span class="badge bg-success">
                                        Resolved
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Open
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center py-5">

                    <div class="fs-1 text-success mb-3">

                        <i class="bi bi-shield-check"></i>

                    </div>

                    <h5>
                        Tidak ada vulnerability ditemukan
                    </h5>

                    <p class="text-muted mb-0">

                        Database tidak memiliki security finding
                        berdasarkan rule assessment yang dijalankan.

                    </p>

                </div>

            @endforelse

        </div>

    </div>


    {{-- =========================================================
         FOOTER
    ========================================================== --}}

    <div class="d-flex justify-content-between align-items-center mt-4">

        <div class="text-muted small">

            Assessment #{{ $assessment->id }}

        </div>


        <div>

            <a
                href="{{ route(
                    'security-reports.print',
                    $assessment
                ) }}"
                target="_blank"
                class="btn btn-primary"
            >

                <i class="bi bi-printer"></i>

                Print Security Report

            </a>

        </div>

    </div>

</div>

@endsection