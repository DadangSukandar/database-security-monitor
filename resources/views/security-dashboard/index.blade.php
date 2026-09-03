@extends('app')

@section('content')

@php
    $totalAssessments = $totalAssessments ?? 0;
    $totalFindings = $totalFindings ?? 0;
    $openFindings = $openFindings ?? 0;
    $resolvedFindings = $resolvedFindings ?? 0;
    $ignoredFindings = $ignoredFindings ?? 0;

    $critical = $critical ?? 0;
    $high = $high ?? 0;
    $medium = $medium ?? 0;
    $low = $low ?? 0;

    $latestAssessment = $latestAssessment ?? null;
    $securityScore = $securityScore ?? ($latestAssessment?->score ?? null);
    $scoreStatus = $scoreStatus ?? 'NO ASSESSMENT';

    $databaseFindings = $databaseFindings ?? collect();
    $categoryFindings = $categoryFindings ?? collect();
    $recentFindings = $recentFindings ?? collect();
    $assessmentHistory = $assessmentHistory ?? collect();

    $totalAlerts = $totalAlerts ?? 0;
    $totalOpenAlerts = $totalOpenAlerts ?? 0;
    $criticalAlerts = $criticalAlerts ?? 0;
    $highAlerts = $highAlerts ?? 0;
    $resolvedAlerts = $resolvedAlerts ?? 0;
    $recentAlerts = $recentAlerts ?? collect();
    $acknowledgedAlerts = $acknowledgedAlerts ?? 0;
    $acknowledgementRate = $acknowledgementRate ?? 0;
    $resolutionRate = $resolutionRate ?? 0;
    $averageAcknowledgementMinutes = $averageAcknowledgementMinutes ?? null;
    $averageResolutionMinutes = $averageResolutionMinutes ?? null;
    $recentAlertActivity = $recentAlertActivity ?? collect();
    $breachedSlaAlerts = $breachedSlaAlerts ?? 0;
    $dueSoonSlaAlerts = $dueSoonSlaAlerts ?? 0;

    $severityTotal = $critical + $high + $medium + $low;

    $scoreColor = match (true) {
        $securityScore === null => '#6c757d',
        $securityScore >= 90 => '#198754',
        $securityScore >= 75 => '#20c997',
        $securityScore >= 50 => '#d39e00',
        $securityScore >= 25 => '#fd7e14',
        default => '#dc3545',
    };

    $severityRows = [
        ['name' => 'Critical', 'value' => $critical, 'color' => '#842029'],
        ['name' => 'High', 'value' => $high, 'color' => '#b02a37'],
        ['name' => 'Medium', 'value' => $medium, 'color' => '#997404'],
        ['name' => 'Low', 'value' => $low, 'color' => '#0f5132'],
    ];
@endphp

<div class="security-dashboard">

    {{-- HEADER --}}
    <div class="dashboard-header">
        <div>
            <div class="eyebrow">SECURITY CENTER</div>
            <h1>Security Dashboard</h1>
            <p>Database security posture, vulnerability assessment, and security alert overview.</p>
        </div>

        <div class="header-actions">
            @if(Route::has('security-findings.index'))
                <a class="btn btn-light" href="{{ route('security-findings.index') }}">
                    Security Findings
                </a>
            @endif

            @if(Route::has('vulnerability-assessments.index'))
                <a class="btn btn-light" href="{{ route('vulnerability-assessments.index') }}">
                    Assessments
                </a>
            @endif

            @if(Route::has('security-alerts.index'))
                <a class="btn btn-alert" href="{{ route('security-alerts.index') }}">
                    Alerts
                    @if($totalOpenAlerts > 0)
                        <span class="button-count">{{ $totalOpenAlerts }}</span>
                    @endif
                </a>
            @endif

            @if(Route::has('security-reports.index'))
                <a class="btn btn-dark" href="{{ route('security-reports.index') }}">
                    Security Reports
                </a>
            @endif
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="grid grid-4 summary-grid">
        <div class="card summary-card">
            <div class="summary-head">
                <span>OPEN FINDINGS</span>
                <span class="icon-box warning">!</span>
            </div>
            <div class="summary-number">{{ $openFindings }}</div>
            <div class="muted">Active vulnerability findings</div>
        </div>

        <div class="card summary-card">
            <div class="summary-head">
                <span>RESOLVED FINDINGS</span>
                <span class="icon-box success">✓</span>
            </div>
            <div class="summary-number">{{ $resolvedFindings }}</div>
            <div class="muted">Remediated findings</div>
        </div>

        <div class="card summary-card">
            <div class="summary-head">
                <span>TOTAL FINDINGS</span>
                <span class="icon-box neutral">#</span>
            </div>
            <div class="summary-number">{{ $totalFindings }}</div>
            <div class="muted">Findings in latest assessment</div>
        </div>

        <div class="card summary-card">
            <div class="summary-head">
                <span>ASSESSMENTS</span>
                <span class="icon-box info">↗</span>
            </div>
            <div class="summary-number">{{ $totalAssessments }}</div>
            <div class="muted">Completed and historical scans</div>
        </div>
    </div>

    {{-- SCORE + SEVERITY --}}
    <div class="grid score-layout">
        <div class="card padded-card">
            <div class="eyebrow">CURRENT SECURITY SCORE</div>

            @if($securityScore !== null)
                <div class="score-value" style="color:{{ $scoreColor }}">
                    {{ $securityScore }}
                    <span>/100</span>
                </div>

                <div class="score-progress">
                    <div style="width:{{ max(0, min(100, (int) $securityScore)) }}%;background:{{ $scoreColor }}"></div>
                </div>

                <div class="score-meta">
                    <strong style="color:{{ $scoreColor }}">{{ $scoreStatus }}</strong>
                    @if($latestAssessment)
                        <span>Assessment #{{ $latestAssessment->id }}</span>
                    @endif
                </div>

                @if($latestAssessment)
                    <div class="assessment-meta">
                        <div><span>Database</span><strong>{{ $latestAssessment->database_name ?? '-' }}</strong></div>
                        <div><span>Status</span><strong>{{ strtoupper($latestAssessment->status ?? '-') }}</strong></div>
                        <div>
                            <span>Scanned</span>
                            <strong>
                                @if($latestAssessment->scanned_at)
                                    {{ \Carbon\Carbon::parse($latestAssessment->scanned_at)->format('d M Y H:i') }}
                                @else
                                    -
                                @endif
                            </strong>
                        </div>
                    </div>
                @endif
            @else
                <div class="empty-inline">Belum ada vulnerability assessment.</div>
            @endif
        </div>

        <div class="card padded-card">
            <div class="section-title-row">
                <div>
                    <div class="eyebrow">ACTIVE RISK</div>
                    <h2>Findings by Severity</h2>
                </div>
                <span class="small-note">OPEN only</span>
            </div>

            @foreach($severityRows as $row)
                @php
                    $percentage = $severityTotal > 0
                        ? round(($row['value'] / $severityTotal) * 100)
                        : 0;
                @endphp

                <div class="severity-row">
                    <div class="severity-name">{{ $row['name'] }}</div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:{{ $percentage }}%;background:{{ $row['color'] }}"></div>
                    </div>
                    <div class="severity-count">{{ $row['value'] }}</div>
                </div>
            @endforeach

            @if($severityTotal === 0)
                <div class="empty-inline">Tidak ada active vulnerability findings.</div>
            @endif
        </div>
    </div>

    {{-- SECURITY ALERTS --}}
    <section class="section-block">
        <div class="section-heading">
            <div>
                <div class="eyebrow">SECURITY OPERATIONS</div>
                <h2>Security Alerts</h2>
                <p>HIGH dan CRITICAL vulnerability alerts yang dihasilkan oleh automated scan.</p>
            </div>

            @if(Route::has('security-alerts.index'))
                <a class="text-link" href="{{ route('security-alerts.index') }}">View Alert Center →</a>
            @endif
        </div>

        <div class="grid grid-5 alert-summary-grid">
            <div class="card alert-stat">
                <span>TOTAL ALERTS</span>
                <strong>{{ $totalAlerts }}</strong>
            </div>
            <div class="card alert-stat">
                <span>OPEN</span>
                <strong>{{ $totalOpenAlerts }}</strong>
            </div>
            <div class="card alert-stat critical-stat">
                <span>CRITICAL</span>
                <strong>{{ $criticalAlerts }}</strong>
            </div>
            <div class="card alert-stat high-stat">
                <span>HIGH</span>
                <strong>{{ $highAlerts }}</strong>
            </div>
            <div class="card alert-stat resolved-stat">
                <span>RESOLVED</span>
                <strong>{{ $resolvedAlerts }}</strong>
            </div>
        </div>

        <div class="grid grid-4 lifecycle-grid">
            <div class="card lifecycle-stat">
                <span>ACKNOWLEDGEMENT RATE</span>
                <strong>{{ number_format($acknowledgementRate, 1) }}%</strong>
                <small>{{ $acknowledgedAlerts }} of {{ $totalAlerts }} alerts</small>
            </div>
            <div class="card lifecycle-stat">
                <span>RESOLUTION RATE</span>
                <strong>{{ number_format($resolutionRate, 1) }}%</strong>
                <small>{{ $resolvedAlerts }} of {{ $totalAlerts }} alerts</small>
            </div>
            <div class="card lifecycle-stat">
                <span>AVG. TIME TO ACKNOWLEDGE</span>
                <strong>{{ $averageAcknowledgementMinutes !== null ? number_format($averageAcknowledgementMinutes, 1).' min' : '-' }}</strong>
                <small>From detection to acknowledgement</small>
            </div>
            <div class="card lifecycle-stat">
                <span>AVG. TIME TO RESOLVE</span>
                <strong>{{ $averageResolutionMinutes !== null ? number_format($averageResolutionMinutes, 1).' min' : '-' }}</strong>
                <small>From detection to resolution</small>
            </div>
        </div>

        <div class="grid grid-2 sla-grid">
            <div class="card sla-stat sla-breached">
                <span>RESPONSE SLA BREACHED</span>
                <strong>{{ $breachedSlaAlerts }}</strong>
                <small>Active alerts requiring immediate escalation</small>
            </div>
            <div class="card sla-stat sla-warning">
                <span>RESPONSE SLA DUE SOON</span>
                <strong>{{ $dueSoonSlaAlerts }}</strong>
                <small>Within the final 25% of response target</small>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-heading">
                <div>
                    <h3>Recent Security Alerts</h3>
                    <p>Latest vulnerability alerts generated from security assessments.</p>
                </div>
            </div>

            @if($recentAlerts->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">✓</div>
                    <strong>No Security Alerts</strong>
                    <span>Tidak ada vulnerability alert yang tersedia.</span>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>SEVERITY</th>
                                <th>ALERT</th>
                                <th>DATABASE</th>
                                <th>USER / HOST</th>
                                <th>STATUS</th>
                                <th class="right">DETECTED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAlerts as $alert)
                                @php
                                    $alertSeverity = strtoupper($alert->severity ?? 'LOW');
                                    $alertStatus = strtoupper($alert->status ?? 'OPEN');
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge severity-{{ strtolower($alertSeverity) }}">
                                            {{ $alertSeverity }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $alert->title }}</div>
                                        <div class="cell-subtitle">{{ $alert->alert_type ?? 'VULNERABILITY' }}</div>
                                    </td>
                                    <td>{{ $alert->database_name ?? '-' }}</td>
                                    <td>
                                        {{ $alert->username ?? '-' }}
                                        @if($alert->client_ip)
                                            <div class="cell-subtitle">{{ $alert->client_ip }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge status-{{ strtolower($alertStatus) }}">
                                            {{ $alertStatus }}
                                        </span>
                                    </td>
                                    <td class="right muted">
                                        @if($alert->detected_at)
                                            {{ \Carbon\Carbon::parse($alert->detected_at)->format('d M Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card activity-card">
            <div class="card-heading">
                <div>
                    <h3>Recent Alert Activity</h3>
                    <p>Latest acknowledgement, resolution, and reopen events.</p>
                </div>
            </div>

            @forelse($recentAlertActivity as $activity)
                <a class="activity-row" href="{{ route('security-alerts.show', $activity->alert) }}">
                    <div>
                        <strong>{{ ucwords(strtolower($activity->action)) }}</strong>
                        <span>{{ $activity->alert->title }}</span>
                    </div>
                    <div class="activity-meta">
                        <span>{{ strtoupper($activity->old_status ?? '-') }} &rarr; {{ strtoupper($activity->new_status) }}</span>
                        <small>{{ $activity->created_at?->format('d M Y H:i') ?? '-' }}</small>
                    </div>
                </a>
            @empty
                <div class="list-empty">Belum ada aktivitas lifecycle alert.</div>
            @endforelse
        </div>
    </section>

    {{-- INCIDENT OPERATIONS --}}
    <section class="section-block">
        <div class="section-heading">
            <div>
                <div class="eyebrow">INCIDENT RESPONSE</div>
                <h2>Incident Operations</h2>
                <p>
                    Operational view of escalated security incidents and current response workload.
                </p>
            </div>

            <div class="header-actions">
                @if(Route::has('security-incidents.reports.index'))
                    <a
                        class="btn btn-light"
                        href="{{ route('security-incidents.reports.index') }}"
                    >
                        Reporting &amp; Audit
                    </a>
                @endif

                @if(Route::has('security-incidents.index'))
                    <a
                        class="btn btn-dark"
                        href="{{ route('security-incidents.index') }}"
                    >
                        Incident Queue
                    </a>
                @endif
            </div>
        </div>

        <div class="grid incident-summary-grid">
            <div class="card incident-stat">
                <span>TOTAL INCIDENTS</span>
                <strong>{{ $totalIncidents }}</strong>
                <small>All escalated incidents</small>
            </div>

            <div class="card incident-stat incident-active">
                <span>ACTIVE</span>
                <strong>{{ $activeIncidents }}</strong>
                <small>Cases requiring response</small>
            </div>

            <a
                class="card incident-stat incident-critical incident-drilldown"
                href="{{ route(
                    'security-incidents.index',
                    ['severity' => 'CRITICAL']
                ) }}"
            >
                <span>CRITICAL</span>
                <strong>{{ $criticalIncidents }}</strong>
                <small>Active critical incidents</small>
            </a>

            <div class="card incident-stat incident-high">
                <span>HIGH</span>
                <strong>{{ $highIncidents }}</strong>
                <small>Active high incidents</small>
            </div>

            <a
                class="card incident-stat incident-unassigned incident-drilldown"
                href="{{ route(
                    'security-incidents.index',
                    ['pic' => 'unassigned']
                ) }}"
            >
                <span>UNASSIGNED</span>
                <strong>{{ $unassignedIncidents }}</strong>
                <small>Active cases without PIC</small>
            </a>

            <div class="card incident-stat incident-closed">
                <span>CLOSED</span>
                <strong>{{ $closedIncidents }}</strong>
                <small>Completed incident cases</small>
            </div>
        </div>

        <div class="grid grid-4 incident-intelligence-grid">
            <a
                class="card incident-intelligence-stat incident-p1 incident-drilldown"
                href="{{ route(
                    'security-incidents.index',
                    ['priority' => 'P1']
                ) }}"
            >
                <span>P1 INCIDENTS</span>
                <strong>{{ $p1Incidents }}</strong>
                <small>Immediate response priority</small>
            </a>

            <a
                class="card incident-intelligence-stat incident-p2 incident-drilldown"
                href="{{ route(
                    'security-incidents.index',
                    ['priority' => 'P2']
                ) }}"
            >
                <span>P2 INCIDENTS</span>
                <strong>{{ $p2Incidents }}</strong>
                <small>High response priority</small>
            </a>

            <a
                class="card incident-intelligence-stat incident-sla-breached incident-drilldown"
                href="{{ route(
                    'security-incidents.index',
                    ['sla' => 'BREACHED']
                ) }}"
            >
                <span>RESPONSE SLA BREACHED</span>
                <strong>{{ $breachedIncidentSla }}</strong>
                <small>Active incidents past response target</small>
            </a>

            <a
                class="card incident-intelligence-stat incident-sla-due incident-drilldown"
                href="{{ route(
                    'security-incidents.index',
                    ['sla' => 'DUE_SOON']
                ) }}"
            >
                <span>RESPONSE SLA DUE SOON</span>
                <strong>{{ $dueSoonIncidentSla }}</strong>
                <small>Approaching response deadline</small>
            </a>
        </div>

        <div class="card table-card incident-recent-card">
            <div class="card-heading split-heading">
                <div>
                    <div class="eyebrow">LATEST CASES</div>
                    <h3>Recent Incidents</h3>
                    <p>Most recently opened security incident cases.</p>
                </div>

                @if(Route::has('security-incidents.index'))
                    <a
                        class="text-link"
                        href="{{ route('security-incidents.index') }}"
                    >
                        View incident queue &rarr;
                    </a>
                @endif
            </div>

            @if($recentIncidents->isEmpty())
                <div class="empty-state">
                    <strong>No Security Incidents</strong>
                    <span>No alerts have been escalated into incident cases.</span>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>INCIDENT</th>
                                <th>SOURCE ALERT</th>
                                <th>SEVERITY</th>
                                <th>STATUS</th>
                                <th>PIC</th>
                                <th>PRIORITY</th>
                                <th class="right">OPENED</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($recentIncidents as $incident)
                                @php
                                    $incidentSeverity = strtoupper($incident->severity ?? 'LOW');
                                    $incidentStatus = strtoupper($incident->status ?? 'OPEN');
                                    $incidentPriority = $incident->triagePriority();
                                @endphp

                                <tr>
                                    <td>
                                        @if(Route::has('security-incidents.show'))
                                            <a
                                                class="table-link incident-number"
                                                href="{{ route('security-incidents.show', $incident) }}"
                                            >
                                                {{ $incident->incident_number }}
                                            </a>
                                        @else
                                            <div class="cell-title">
                                                {{ $incident->incident_number }}
                                            </div>
                                        @endif

                                        <div class="cell-subtitle">
                                            {{ $incident->title }}
                                        </div>
                                    </td>

                                    <td>
                                        @if($incident->securityAlert)
                                            <a
                                                class="table-link"
                                                href="{{ route(
                                                    'security-alerts.show',
                                                    $incident->securityAlert
                                                ) }}"
                                            >
                                                Alert #{{ $incident->securityAlert->id }}
                                            </a>

                                            <div class="cell-subtitle">
                                                {{ $incident->securityAlert->title }}
                                            </div>
                                        @else
                                            <span class="muted">
                                                -
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge severity-{{ strtolower($incidentSeverity) }}">
                                            {{ $incidentSeverity }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge status-{{ strtolower($incidentStatus) }}">
                                            {{ $incidentStatus }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($incident->assignedTo)
                                            <div class="cell-title">
                                                {{ $incident->assignedTo->name }}
                                            </div>
                                        @else
                                            <span class="badge incident-unassigned-badge">
                                                UNASSIGNED
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge priority-{{ strtolower($incidentPriority) }}">
                                            {{ $incidentPriority }}
                                        </span>
                                    </td>

                                    <td class="right muted">
                                        {{ $incident->opened_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    {{-- DATABASE + CATEGORY --}}
    <div class="grid grid-2 section-block">
        <div class="card list-card">
            <div class="card-heading">
                <div>
                    <div class="eyebrow">DATABASES</div>
                    <h3>Findings by Database</h3>
                </div>
            </div>

            @forelse($databaseFindings as $database)
                <div class="list-row">
                    <span>{{ $database->database_name ?? 'Unknown Database' }}</span>
                    <strong>{{ $database->total ?? 0 }}</strong>
                </div>
            @empty
                <div class="list-empty">No database finding data available.</div>
            @endforelse
        </div>

        <div class="card list-card">
            <div class="card-heading">
                <div>
                    <div class="eyebrow">CATEGORIES</div>
                    <h3>Findings by Category</h3>
                </div>
            </div>

            @forelse($categoryFindings as $categoryItem)
                <div class="list-row">
                    <span>{{ $categoryItem->category ?? 'Uncategorized' }}</span>
                    <strong>{{ $categoryItem->total ?? 0 }}</strong>
                </div>
            @empty
                <div class="list-empty">No category finding data available.</div>
            @endforelse
        </div>
    </div>

    {{-- RECENT VULNERABILITY FINDINGS --}}
    <section class="section-block">
        <div class="card table-card">
            <div class="card-heading split-heading">
                <div>
                    <div class="eyebrow">LATEST ASSESSMENT</div>
                    <h3>Recent Vulnerability Findings</h3>
                    <p>Findings dari assessment terbaru, bukan tabel SecurityFinding lama.</p>
                </div>

                @if(Route::has('vulnerability-assessments.index'))
                    <a class="text-link" href="{{ route('vulnerability-assessments.index') }}">View assessments →</a>
                @endif
            </div>

            @if($recentFindings->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">✓</div>
                    <strong>No Vulnerability Findings</strong>
                    <span>Assessment terbaru tidak memiliki finding.</span>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>SEVERITY</th>
                                <th>FINDING</th>
                                <th>CATEGORY</th>
                                <th>DATABASE</th>
                                <th>USER / HOST</th>
                                <th>STATUS</th>
                                <th class="right">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentFindings as $finding)
                                @php
                                    $findingSeverity = strtoupper($finding->severity ?? 'LOW');
                                    $findingStatus = strtoupper($finding->status ?? 'OPEN');
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge severity-{{ strtolower($findingSeverity) }}">
                                            {{ $findingSeverity }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $finding->title }}</div>
                                        @if($finding->rule_code)
                                            <div class="cell-subtitle">{{ $finding->rule_code }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $finding->category ?? '-' }}</td>
                                    <td>{{ $finding->database_name ?? '-' }}</td>
                                    <td>
                                        {{ $finding->username ?? '-' }}
                                        @if($finding->host)
                                            <div class="cell-subtitle">{{ $finding->host }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge status-{{ strtolower($findingStatus) }}">
                                            {{ $findingStatus }}
                                        </span>
                                    </td>
                                    <td class="right">
                                        @if(Route::has('vulnerability-assessments.show') && $finding->vulnerability_assessment_id)
                                            <a class="table-link" href="{{ route('vulnerability-assessments.show', $finding->vulnerability_assessment_id) }}">
                                                Assessment
                                            </a>
                                        @else
                                            <span class="muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    {{-- ASSESSMENT HISTORY --}}
    <section class="section-block">
        <div class="card table-card">
            <div class="card-heading split-heading">
                <div>
                    <div class="eyebrow">ASSESSMENT HISTORY</div>
                    <h3>Recent Security Assessments</h3>
                    <p>Latest vulnerability scan history and score evolution.</p>
                </div>

                @if(Route::has('vulnerability-assessments.index'))
                    <a class="text-link" href="{{ route('vulnerability-assessments.index') }}">View all →</a>
                @endif
            </div>

            @if($assessmentHistory->isEmpty())
                <div class="empty-state">
                    <strong>No Assessment History</strong>
                    <span>Belum ada assessment yang tersimpan.</span>
                </div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DATABASE</th>
                                <th>SCORE</th>
                                <th>CRITICAL</th>
                                <th>HIGH</th>
                                <th>MEDIUM</th>
                                <th>LOW</th>
                                <th>STATUS</th>
                                <th class="right">SCANNED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assessmentHistory as $assessment)
                                <tr>
                                    <td>#{{ $assessment->id }}</td>
                                    <td>
                                        <div class="cell-title">{{ $assessment->database_name ?? '-' }}</div>
                                    </td>
                                    <td><strong>{{ $assessment->score ?? 0 }}</strong>/100</td>
                                    <td>{{ $assessment->critical_count ?? 0 }}</td>
                                    <td>{{ $assessment->high_count ?? 0 }}</td>
                                    <td>{{ $assessment->medium_count ?? 0 }}</td>
                                    <td>{{ $assessment->low_count ?? 0 }}</td>
                                    <td>
                                        <span class="badge status-{{ strtolower($assessment->status ?? 'completed') }}">
                                            {{ strtoupper($assessment->status ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="right muted">
                                        @if($assessment->scanned_at)
                                            {{ \Carbon\Carbon::parse($assessment->scanned_at)->format('d M Y H:i') }}
                                        @elseif($assessment->created_at)
                                            {{ \Carbon\Carbon::parse($assessment->created_at)->format('d M Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

</div>

<style>
    .security-dashboard {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px;
        color: #212529;
    }

    .dashboard-header,
    .section-heading,
    .split-heading,
    .summary-head,
    .section-title-row,
    .score-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }

    .dashboard-header {
        align-items: flex-start;
        margin-bottom: 28px;
    }

    .dashboard-header h1 {
        margin: 0;
        font-size: 30px;
        font-weight: 800;
    }

    .dashboard-header p,
    .section-heading p,
    .card-heading p {
        margin: 6px 0 0;
        color: #6c757d;
        font-size: 13px;
    }

    .eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .07em;
        color: #6c757d;
    }

    .header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #dee2e6;
    }

    .btn-light { background: #fff; color: #212529; }
    .btn-dark { background: #212529; color: #fff; border-color: #212529; }
    .btn-alert { background: #fff8e5; color: #664d03; border-color: #ffe69c; }

    .button-count {
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #842029;
        color: #fff;
        font-size: 10px;
    }

    .grid {
        display: grid;
        gap: 18px;
    }

    .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .score-layout { grid-template-columns: minmax(280px, 1fr) minmax(0, 2fr); margin-bottom: 24px; }

    .card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
    }

    .summary-card { padding: 21px; }
    .summary-grid { margin-bottom: 24px; }
    .summary-head { color: #6c757d; font-size: 10px; font-weight: 800; }
    .summary-number { margin-top: 16px; font-size: 34px; font-weight: 800; line-height: 1; }
    .muted { color: #6c757d; }
    .summary-card .muted { margin-top: 8px; font-size: 11px; }

    .icon-box {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .icon-box.warning { background: #fff3cd; color: #664d03; }
    .icon-box.success { background: #d1e7dd; color: #0f5132; }
    .icon-box.neutral { background: #e9ecef; color: #495057; }
    .icon-box.info { background: #cff4fc; color: #055160; }

    .padded-card { padding: 24px; }
    .padded-card h2, .section-heading h2 { margin: 5px 0 0; font-size: 18px; }

    .score-value {
        margin-top: 18px;
        font-size: 56px;
        line-height: 1;
        font-weight: 900;
    }

    .score-value span { color: #6c757d; font-size: 14px; font-weight: 500; }

    .score-progress {
        height: 9px;
        background: #e9ecef;
        border-radius: 20px;
        overflow: hidden;
        margin-top: 16px;
    }

    .score-progress div { height: 100%; border-radius: 20px; }
    .score-meta { margin-top: 12px; font-size: 11px; color: #6c757d; }

    .assessment-meta {
        margin-top: 18px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        display: grid;
        gap: 8px;
    }

    .assessment-meta div { display: flex; justify-content: space-between; gap: 12px; font-size: 11px; }
    .assessment-meta span { color: #6c757d; }

    .small-note { font-size: 10px; color: #6c757d; }

    .severity-row {
        display: grid;
        grid-template-columns: 80px minmax(0,1fr) 40px;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
    }

    .severity-name, .severity-count { font-size: 11px; font-weight: 700; }
    .severity-count { text-align: right; }
    .bar-track { height: 8px; border-radius: 20px; background: #edf0f2; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 20px; }

    .section-block { margin-top: 24px; }
    .section-heading { align-items: flex-end; margin-bottom: 14px; }
    .text-link, .table-link { color: #0d6efd; text-decoration: none; font-size: 11px; font-weight: 700; }

    .alert-stat { padding: 18px; }
    .alert-stat span { font-size: 10px; color: #6c757d; font-weight: 800; }
    .alert-stat strong { display: block; margin-top: 7px; font-size: 30px; }
    .critical-stat { background: #fff5f5; border-color: #f5c2c7; }
    .critical-stat span, .critical-stat strong { color: #842029; }
    .high-stat { background: #fff8e5; border-color: #ffe69c; }
    .high-stat span, .high-stat strong { color: #664d03; }
    .resolved-stat { background: #f0fff4; border-color: #badbcc; }
    .resolved-stat span, .resolved-stat strong { color: #0f5132; }

    .incident-summary-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
        margin-bottom: 18px;
    }

    .incident-stat {
        padding: 18px;
    }

    .incident-stat span {
        display: block;
        color: #6c757d;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .04em;
    }

    .incident-stat strong {
        display: block;
        margin-top: 8px;
        font-size: 28px;
    }

    .incident-stat small {
        display: block;
        margin-top: 6px;
        color: #6c757d;
        font-size: 10px;
    }

    .incident-critical {
        background: #fff5f5;
        border-color: #f5c2c7;
    }

    .incident-critical span,
    .incident-critical strong {
        color: #842029;
    }

    .incident-high,
    .incident-unassigned {
        background: #fff8e5;
        border-color: #ffe69c;
    }

    .incident-high span,
    .incident-high strong,
    .incident-unassigned span,
    .incident-unassigned strong {
        color: #664d03;
    }

    .incident-closed {
        background: #f0fff4;
        border-color: #badbcc;
    }

    .incident-closed span,
    .incident-closed strong {
        color: #0f5132;
    }

    .incident-recent-card {
        margin-top: 18px;
    }

    .incident-number {
        font-size: 11px;
    }

    .incident-unassigned-badge {
        background: #fff3cd;
        color: #664d03;
    }

    .priority-p1 {
        background: #f8d7da;
        color: #842029;
    }

    .priority-p2 {
        background: #ffe5d0;
        color: #984c0c;
    }

    .priority-p3 {
        background: #fff3cd;
        color: #664d03;
    }

    .priority-p4 {
        background: #cff4fc;
        color: #055160;
    }

    .priority-none {
        background: #e2e3e5;
        color: #41464b;
    }

    .incident-intelligence-grid {
    margin-bottom: 18px;
}

    .incident-intelligence-stat {
        padding: 18px;
    }

    .incident-intelligence-stat span {
        display: block;
        color: #6c757d;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .04em;
    }

    .incident-intelligence-stat strong {
        display: block;
        margin-top: 8px;
        font-size: 28px;
    }

    .incident-intelligence-stat small {
        display: block;
        margin-top: 6px;
        color: #6c757d;
        font-size: 10px;
    }

    .incident-drilldown {
        display: block;
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        transition:
            transform .15s ease,
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .incident-drilldown:hover {
        color: inherit;
        text-decoration: none;
        transform: translateY(-2px);
        border-color: var(--g-blue);
        box-shadow: 0 6px 18px rgba(0, 0, 0, .18);
    }

    .incident-drilldown:focus-visible {
        outline: 2px solid var(--g-cyan);
        outline-offset: 2px;
    }

    .incident-p1,
    .incident-sla-breached {
        background: #fff5f5;
        border-color: #f5c2c7;
    }

    .incident-p1 span,
    .incident-p1 strong,
    .incident-sla-breached span,
    .incident-sla-breached strong {
        color: #842029;
    }

    .incident-p2,
    .incident-sla-due {
        background: #fff8e5;
        border-color: #ffe69c;
    }

    .incident-p2 span,
    .incident-p2 strong,
    .incident-sla-due span,
    .incident-sla-due strong {
        color: #664d03;
    }
    .lifecycle-grid { margin: 18px 0; }
    .lifecycle-stat { padding: 18px; }
    .lifecycle-stat span { color: #6c757d; font-size: 9px; font-weight: 800; }
    .lifecycle-stat strong { display: block; margin-top: 9px; font-size: 24px; }
    .lifecycle-stat small { display: block; margin-top: 6px; color: #6c757d; font-size: 10px; }

    .sla-grid { margin-bottom: 18px; }
    .sla-stat { padding: 18px; }
    .sla-stat span { font-size: 9px; font-weight: 800; }
    .sla-stat strong { display: block; margin-top: 8px; font-size: 28px; }
    .sla-stat small { display: block; margin-top: 5px; font-size: 10px; }
    .sla-breached { background: #fff5f5; border-color: #f5c2c7; color: #842029; }
    .sla-warning { background: #fff8e5; border-color: #ffe69c; color: #664d03; }

    .activity-card { margin-top: 18px; overflow: hidden; }
    .activity-row { display: flex; justify-content: space-between; gap: 18px; padding: 14px 20px; border-top: 1px solid #eee; color: #212529; text-decoration: none; }
    .activity-row:hover { background: #f8f9fa; }
    .activity-row strong, .activity-row span, .activity-row small { display: block; }
    .activity-row span, .activity-row small { margin-top: 3px; color: #6c757d; font-size: 10px; }
    .activity-meta { text-align: right; white-space: nowrap; }

    .table-card { overflow: hidden; }
    .card-heading { padding: 18px 20px; border-bottom: 1px solid #dee2e6; }
    .card-heading h3 { margin: 0; font-size: 16px; }
    .table-wrap { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; min-width: 760px; }
    thead tr { background: #f8f9fa; }
    th { padding: 11px 14px; text-align: left; color: #6c757d; font-size: 9px; letter-spacing: .04em; }
    td { padding: 13px 14px; border-top: 1px solid #eee; font-size: 11px; vertical-align: middle; }
    .right { text-align: right; }
    .cell-title { font-weight: 700; color: #212529; }
    .cell-subtitle { margin-top: 3px; color: #6c757d; font-size: 10px; }

    .badge {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 14px;
        font-size: 9px;
        font-weight: 800;
        white-space: nowrap;
    }

    .severity-critical { background: #f8d7da; color: #842029; }
    .severity-high { background: #ffe5d0; color: #984c0c; }
    .severity-medium { background: #fff3cd; color: #664d03; }
    .severity-low { background: #d1e7dd; color: #0f5132; }

    .status-open { background: #f8d7da; color: #842029; }
    .status-resolved, .status-completed { background: #d1e7dd; color: #0f5132; }
    .status-ignored { background: #e2e3e5; color: #41464b; }
    .status-scanning { background: #cff4fc; color: #055160; }

    .list-card { overflow: hidden; }
    .list-row {
        padding: 14px 20px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        font-size: 11px;
    }
    .list-row strong {
        min-width: 34px;
        text-align: center;
        padding: 5px 8px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    .list-empty, .empty-inline { padding: 18px; color: #6c757d; font-size: 11px; }

    .empty-state {
        padding: 42px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 6px;
        color: #6c757d;
        font-size: 11px;
    }
    .empty-state strong { color: #212529; font-size: 14px; }
    .empty-icon { font-size: 28px; color: #198754; }

    .security-dashboard {
        color: var(--g-text) !important;
    }

    .security-dashboard .card {
        border-color: var(--g-border) !important;
        background: linear-gradient(145deg, rgba(26, 39, 59, .98), rgba(15, 24, 38, .98)) !important;
        color: var(--g-text) !important;
    }

    .security-dashboard .dashboard-header p,
    .security-dashboard .section-heading p,
    .security-dashboard .card-heading p,
    .security-dashboard .summary-head,
    .security-dashboard .muted,
    .security-dashboard .score-value span,
    .security-dashboard .score-meta,
    .security-dashboard .assessment-meta span,
    .security-dashboard .small-note,
    .security-dashboard .alert-stat span,
    .security-dashboard .lifecycle-stat span,
    .security-dashboard .lifecycle-stat small,
    .security-dashboard .activity-row span,
    .security-dashboard .activity-row small,
    .security-dashboard .cell-subtitle,
    .security-dashboard .list-empty,
    .security-dashboard .empty-inline,
    .security-dashboard .empty-state,
    .security-dashboard th {
        color: var(--g-muted) !important;
    }

    .security-dashboard .summary-number,
    .security-dashboard .severity-name,
    .security-dashboard .severity-count,
    .security-dashboard .lifecycle-stat strong,
    .security-dashboard .activity-row,
    .security-dashboard .activity-row strong,
    .security-dashboard .cell-title,
    .security-dashboard .list-row,
    .security-dashboard .empty-state strong {
        color: var(--g-text) !important;
    }

    .security-dashboard .btn-light {
        border-color: var(--g-border) !important;
        background: var(--g-surface-soft) !important;
        color: var(--g-text) !important;
    }

    .security-dashboard .btn-light:hover {
        border-color: var(--g-cyan) !important;
        background: #263a56 !important;
        color: #fff !important;
    }

    .security-dashboard .btn-dark {
        border-color: var(--g-blue) !important;
        background: var(--g-blue) !important;
        color: #fff !important;
    }

    .security-dashboard .btn-alert {
        border-color: rgba(241, 194, 27, .5) !important;
        background: rgba(241, 194, 27, .12) !important;
        color: #fddc69 !important;
    }

    .security-dashboard .critical-stat span,
    .security-dashboard .critical-stat strong,
    .security-dashboard .sla-breached span,
    .security-dashboard .sla-breached strong,
    .security-dashboard .sla-breached small {
        color: #ffb3b8 !important;
    }

    .security-dashboard .high-stat span,
    .security-dashboard .high-stat strong,
    .security-dashboard .sla-warning span,
    .security-dashboard .sla-warning strong,
    .security-dashboard .sla-warning small {
        color: #fddc69 !important;
    }

    .security-dashboard .resolved-stat span,
    .security-dashboard .resolved-stat strong {
        color: #a7f0ba !important;
    }

    .security-dashboard .icon-box.warning {
        background: rgba(241, 194, 27, .14) !important;
        color: #fddc69 !important;
    }

    .security-dashboard .icon-box.success {
        background: rgba(25, 128, 56, .18) !important;
        color: #a7f0ba !important;
    }

    .security-dashboard .icon-box.neutral {
        background: #26364b !important;
        color: #dce5f2 !important;
    }

    .security-dashboard .icon-box.info {
        background: rgba(15, 98, 254, .18) !important;
        color: #a6c8ff !important;
    }

    .security-dashboard .assessment-meta,
    .security-dashboard .activity-row,
    .security-dashboard .card-heading,
    .security-dashboard td,
    .security-dashboard .list-row {
        border-color: var(--g-border-soft) !important;
    }

    .security-dashboard .activity-row:hover,
    .security-dashboard .list-row:hover {
        background: #1b2b42 !important;
    }

    .security-dashboard .list-row strong {
        background: #26364b !important;
        color: var(--g-text) !important;
    }

    .security-dashboard thead tr {
        background: #25344a !important;
    }

    .security-dashboard .incident-stat span,
    .security-dashboard .incident-stat small {
        color: var(--g-muted) !important;
    }

    .security-dashboard .incident-stat strong {
        color: var(--g-text) !important;
    }

    .security-dashboard .incident-critical span,
    .security-dashboard .incident-critical strong {
        color: #ffb3b8 !important;
    }

    .security-dashboard .incident-high span,
    .security-dashboard .incident-high strong,
    .security-dashboard .incident-unassigned span,
    .security-dashboard .incident-unassigned strong {
        color: #fddc69 !important;
    }

    .security-dashboard .incident-closed span,
    .security-dashboard .incident-closed strong {
        color: #a7f0ba !important;
    }

    @media (max-width: 1100px) {
        .grid-4,
        .grid-5,
        .incident-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .score-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 850px) {
        .dashboard-header, .section-heading { flex-direction: column; align-items: stretch; }
        .grid-2 { grid-template-columns: 1fr; }
    }

    @media (max-width: 620px) {
        .security-dashboard {
            padding: 18px;
        }

        .summary-grid {
            margin-bottom: 18px;
        }

        .grid-4,
        .grid-5,
        .incident-summary-grid {
            grid-template-columns: 1fr;
        }

        .header-actions {
            width: 100%;
        }

        .btn {
            justify-content: center;
            flex: 1 1 auto;
        }

        .severity-row {
            grid-template-columns: 65px minmax(0,1fr) 30px;
        }
    }
</style>

@endsection
