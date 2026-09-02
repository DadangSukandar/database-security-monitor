@extends('app')

@section('content')
<div class="guard-page">

    {{-- ============================================================
        PAGE HEADER
    ============================================================ --}}
    <div class="guard-page-header">
        <div>
            <h1>Security Incidents</h1>

            <p>
                Incident management and response cases escalated
                from security alerts.
            </p>
        </div>

        <div class="guard-count">
            {{ $incidents->total() }}
            {{ $incidents->total() === 1 ? 'Incident' : 'Incidents' }}
        </div>
    </div>

    {{-- ============================================================
        INCIDENT OPERATIONAL SUMMARY
    ============================================================ --}}
    <div class="guard-incident-metrics">

        <div class="guard-incident-metric">
            <div class="guard-incident-metric-label">
                Active
            </div>

            <div class="guard-incident-metric-value">
                {{ number_format($incidentMetrics['active']) }}
            </div>

            <div class="guard-incident-metric-description">
                Incidents requiring response
            </div>
        </div>

        <div class="guard-incident-metric">
            <div class="guard-incident-metric-label">
                Open
            </div>

            <div class="guard-incident-metric-value">
                {{ number_format($incidentMetrics['open']) }}
            </div>

            <div class="guard-incident-metric-description">
                Newly opened incidents
            </div>
        </div>

        <div class="guard-incident-metric">
            <div class="guard-incident-metric-label">
                Investigating
            </div>

            <div class="guard-incident-metric-value">
                {{ number_format($incidentMetrics['investigating']) }}
            </div>

            <div class="guard-incident-metric-description">
                Under active investigation
            </div>
        </div>

        <div class="guard-incident-metric guard-incident-metric-risk">
            <div class="guard-incident-metric-label">
                Critical / High
            </div>

            <div class="guard-incident-metric-value">
                {{ number_format($incidentMetrics['critical_high']) }}
            </div>

            <div class="guard-incident-metric-description">
                Active high-risk incidents
            </div>
        </div>

        <div class="guard-incident-metric">
            <div class="guard-incident-metric-label">
                Oldest Active
            </div>

            <div class="guard-incident-metric-value">
                {{ $incidentAgingMetrics['oldest_active'] ?? '—' }}
            </div>

            <div class="guard-incident-metric-description">
                @if ($incidentAgingMetrics['oldest_active'])
                    Longest-running active incident
                @else
                    No active incidents
                @endif
            </div>
        </div>

        <div class="guard-incident-metric guard-incident-metric-warning">
            <div class="guard-incident-metric-label">
                Unassigned
            </div>

            <div class="guard-incident-metric-value">
                {{ number_format($incidentMetrics['unassigned']) }}
            </div>

            <div class="guard-incident-metric-description">
                Active incidents without PIC
            </div>
        </div>

    </div>

    <div class="guard-incident-sla-summary">
        <div class="guard-incident-sla-summary-item">
            <span>Response SLA Breached</span>

            <strong class="guard-incident-sla-summary-breached">
                {{ number_format($incidentSlaMetrics['breached']) }}
            </strong>
        </div>

        <div class="guard-incident-sla-summary-item">
            <span>Response SLA Due Soon</span>

            <strong class="guard-incident-sla-summary-due">
                {{ number_format($incidentSlaMetrics['due_soon']) }}
            </strong>
        </div>
    </div>

    <div class="guard-incident-resolution-analytics">
        <div class="guard-incident-resolution-card">
            <div class="guard-incident-resolution-label">
                Avg Acknowledgement
            </div>

            <div class="guard-incident-resolution-value">
                @if ($incidentResolutionMetrics['average_acknowledgement_minutes'] !== null)
                    {{ number_format($incidentResolutionMetrics['average_acknowledgement_minutes'], 1) }} min
                @else
                    —
                @endif
            </div>

            <div class="guard-incident-resolution-description">
                Average time from opened to acknowledged
            </div>
        </div>

        <div class="guard-incident-resolution-card">
            <div class="guard-incident-resolution-label">
                Avg Resolution
            </div>

            <div class="guard-incident-resolution-value">
                @if ($incidentResolutionMetrics['average_resolution_minutes'] !== null)
                    {{ number_format($incidentResolutionMetrics['average_resolution_minutes'], 1) }} min
                @else
                    —
                @endif
            </div>

            <div class="guard-incident-resolution-description">
                Average time from opened to resolved
            </div>
        </div>

        <div class="guard-incident-resolution-card">
            <div class="guard-incident-resolution-label">
                Ack SLA Met
            </div>

            <div class="guard-incident-resolution-value guard-incident-resolution-success">
                {{ number_format($incidentResolutionMetrics['acknowledgement_sla_met']) }}
            </div>

            <div class="guard-incident-resolution-description">
                Historical acknowledged incidents meeting SLA
            </div>
        </div>

        <div class="guard-incident-resolution-card">
            <div class="guard-incident-resolution-label">
                Ack SLA Breached
            </div>

            <div class="guard-incident-resolution-value guard-incident-resolution-danger">
                {{ number_format($incidentResolutionMetrics['acknowledgement_sla_breached']) }}
            </div>

            <div class="guard-incident-resolution-description">
                Historical acknowledged incidents missing SLA
            </div>
        </div>

        <div class="guard-incident-resolution-card">
            <div class="guard-incident-resolution-label">
                Ack SLA Met Rate
            </div>

            <div class="guard-incident-resolution-value">
                @if ($incidentResolutionMetrics['acknowledgement_sla_met_rate'] !== null)
                    {{ number_format($incidentResolutionMetrics['acknowledgement_sla_met_rate'], 1) }}%
                @else
                    —
                @endif
            </div>

            <div class="guard-incident-resolution-description">
                SLA compliance among acknowledged incidents
            </div>
        </div>
    </div>

    {{-- ============================================================
        INCIDENT FILTERS
    ============================================================ --}}
    <div class="guard-card guard-filter-card">
        <form
            method="GET"
            action="{{ route('security-incidents.index') }}"
            class="guard-filter-form"
        >

            {{-- Search --}}
            <div class="guard-filter-search">
                <label for="search">
                    Search
                </label>

                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Incident number or title..."
                    autocomplete="off"
                >
            </div>


            {{-- Status --}}
            <div class="guard-filter-field">
                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >
                    <option value="">
                        All Statuses
                    </option>

                    @foreach([
                        'OPEN',
                        'ACKNOWLEDGED',
                        'INVESTIGATING',
                        'CONTAINED',
                        'RESOLVED',
                        'CLOSED',
                    ] as $filterStatus)

                        <option
                            value="{{ $filterStatus }}"
                            @selected(
                                request('status') === $filterStatus
                            )
                        >
                            {{ str_replace(
                                '_',
                                ' ',
                                $filterStatus
                            ) }}
                        </option>

                    @endforeach
                </select>
            </div>


            {{-- Severity --}}
            <div class="guard-filter-field">
                <label for="severity">
                    Severity
                </label>

                <select
                    id="severity"
                    name="severity"
                >
                    <option value="">
                        All Severities
                    </option>

                    @foreach([
                        'CRITICAL',
                        'HIGH',
                        'MEDIUM',
                        'LOW',
                    ] as $filterSeverity)

                        <option
                            value="{{ $filterSeverity }}"
                            @selected(
                                request('severity') === $filterSeverity
                            )
                        >
                            {{ $filterSeverity }}
                        </option>

                    @endforeach
                </select>
            </div>


            {{-- PIC --}}
            <div class="guard-filter-field">
                <label for="pic">
                    PIC
                </label>

                <select
                    id="pic"
                    name="pic"
                >
                    <option value="">
                        All PICs
                    </option>

                    <option
                        value="unassigned"
                        @selected(
                            request('pic') === 'unassigned'
                        )
                    >
                        Unassigned
                    </option>

                    @foreach($teamMembers as $member)

                        <option
                            value="{{ $member->id }}"
                            @selected(
                                (string) request('pic') ===
                                (string) $member->id
                            )
                        >
                            {{ $member->name }}
                        </option>

                    @endforeach
                </select>
            </div>


            {{-- Actions --}}
            <div class="guard-filter-actions">

                <button
                    type="submit"
                    class="guard-btn guard-btn-primary"
                >
                    Apply Filters
                </button>

                @if(
                    request()->filled('search') ||
                    request()->filled('status') ||
                    request()->filled('severity') ||
                    request()->filled('pic')
                )

                    <a
                        href="{{ route(
                            'security-incidents.index'
                        ) }}"
                        class="guard-btn guard-btn-secondary"
                    >
                        Reset
                    </a>

                @endif
            </div>

        </form>
    </div>


    {{-- ============================================================
        INCIDENT QUEUE
    ============================================================ --}}
    <div class="guard-card">

        <div class="guard-card-header">
            <div>
                <h2>
                    Incident Queue
                </h2>

                <p>
                    Security incidents requiring investigation
                    and response.
                </p>
            </div>
        </div>


        {{-- ========================================================
            TABLE
        ======================================================== --}}
        <div class="guard-table-wrap">

            <table class="guard-table">

                <thead>
                    <tr>
                        <th>
                            Incident
                        </th>

                        <th>
                            Title
                        </th>

                        <th>
                            Severity
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            PIC
                        </th>

                        <th>
                            Source Alert
                        </th>

                         <th>
                            Age
                        </th>

                        <th>
                            SLA
                        </th>

                        <th>
                            Opened
                        </th>
                    </tr>
                </thead>


                <tbody>

                    @forelse($incidents as $incident)

                        @php
                            $severity = strtoupper(
                                $incident->severity ?? 'UNKNOWN'
                            );

                            $status = strtoupper(
                                $incident->status ?? 'OPEN'
                            );
                        @endphp


                        <tr>

                            {{-- Incident Number --}}
                            <td>
                                <a
                                    class="
                                        guard-link
                                        guard-incident-number
                                    "
                                    href="{{ route(
                                        'security-incidents.show',
                                        $incident
                                    ) }}"
                                >
                                    {{ $incident->incident_number }}
                                </a>
                            </td>


                            {{-- Title --}}
                            <td>
                                <div class="guard-primary">
                                    {{ $incident->title }}
                                </div>
                            </td>


                            {{-- Severity --}}
                            <td>
                                <span
                                    class="
                                        guard-badge
                                        severity-{{ strtolower(
                                            $severity
                                        ) }}
                                    "
                                >
                                    {{ $severity }}
                                </span>
                            </td>


                            {{-- Status --}}
                            <td>
                                <span
                                    class="
                                        guard-badge
                                        status-{{ strtolower(
                                            $status
                                        ) }}
                                    "
                                >
                                    {{ str_replace(
                                        '_',
                                        ' ',
                                        $status
                                    ) }}
                                </span>
                            </td>


                            {{-- PIC --}}
                            <td>
                                @if($incident->assignedTo)

                                    <span class="guard-primary">
                                        {{ $incident->assignedTo->name }}
                                    </span>

                                @else

                                    <span class="guard-muted">
                                        Unassigned
                                    </span>

                                @endif
                            </td>


                            {{-- Source Alert --}}
                            <td>

                                @if($incident->securityAlert)

                                    <a
                                        class="guard-link"
                                        href="{{ route(
                                            'security-alerts.show',
                                            $incident->securityAlert
                                        ) }}"
                                    >
                                        Alert #{{ $incident->security_alert_id }}
                                    </a>

                                @else

                                    <span class="guard-muted">
                                        Alert #{{ $incident->security_alert_id }}
                                    </span>

                                @endif

                            </td>

                            {{-- Age At --}}
                            <td>
                                <div class="guard-incident-age">
                                    <strong>{{ $incident->ageLabel() }}</strong>

                                    @if ($incident->status === 'CLOSED')
                                        <span>Closed</span>
                                    @else
                                        <span>Active</span>
                                    @endif
                                </div>
                            </td>

                            @php
                                $slaStatus = $incident->responseSlaStatus();
                            @endphp

                            {{-- Sla --}}
                            <td>
                                <div class="guard-incident-sla guard-incident-sla-{{ strtolower($slaStatus) }}">
                                    {{ str_replace('_', ' ', $slaStatus) }}
                                </div>
                            </td>

                            {{-- Opened At --}}
                            <td>
                                {{ $incident->opened_at?->format(
                                    'd M Y H:i:s'
                                ) ?? '-' }}
                            </td>

                        </tr>


                    @empty

                        <tr>
                            <td
                                colspan="7"
                                class="guard-empty"
                            >

                                @if(
                                    request()->filled('search') ||
                                    request()->filled('status') ||
                                    request()->filled('severity') ||
                                    request()->filled('pic')
                                )

                                    No security incidents match
                                    the selected filters.

                                @else

                                    No security incidents found.

                                @endif

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ========================================================
            PAGINATION
        ======================================================== --}}
        @if($incidents->hasPages())

            <div class="guard-pagination">
                {{ $incidents->links() }}
            </div>

        @endif

    </div>

</div>
@endsection