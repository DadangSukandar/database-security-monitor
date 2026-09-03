@extends('app')

@section('content')
<div class="guard-page">
    <div class="guard-page-header">
        <div>
            <h1>Incident Reporting & Audit</h1>
            <p>Operational, SLA, trend, and immutable case activity reporting.</p>
        </div>
        <a class="guard-btn guard-btn-secondary" href="{{ route('security-incidents.index') }}">
            Back to Incident Queue
        </a>
    </div>

    <div class="guard-card guard-filter-card guard-report-filter">
        <form method="GET" action="{{ route('security-incidents.reports.index') }}" class="guard-filter-form">
            <div class="guard-filter-field">
                <label for="start_date">Start Date</label>
                <input id="start_date" type="date" name="start_date" value="{{ $startDate->toDateString() }}">
            </div>
            <div class="guard-filter-field">
                <label for="end_date">End Date</label>
                <input id="end_date" type="date" name="end_date" value="{{ $endDate->toDateString() }}">
            </div>
            <div class="guard-filter-actions">
                <button type="submit" class="guard-btn guard-btn-primary">Apply Range</button>
                <a href="{{ route('security-incidents.reports.index') }}" class="guard-btn guard-btn-secondary">Last 30 Days</a>
            </div>
        </form>
        @error('start_date')<div class="guard-report-error">{{ $message }}</div>@enderror
        @error('end_date')<div class="guard-report-error">{{ $message }}</div>@enderror
    </div>

    <div class="guard-report-range">
        Reporting period: <strong>{{ $startDate->format('d M Y') }}</strong> – <strong>{{ $endDate->format('d M Y') }}</strong>
    </div>

    <div class="guard-incident-metrics">
        @foreach([
            ['Total Incidents', $report['summary']['total'], 'Opened in reporting period'],
            ['Active', $report['summary']['active'], 'Currently not closed'],
            ['Closed', $report['summary']['closed'], 'Currently closed'],
            ['Unassigned', $report['summary']['unassigned'], 'Without current PIC'],
        ] as [$label, $value, $description])
            <div class="guard-incident-metric">
                <div class="guard-incident-metric-label">{{ $label }}</div>
                <div class="guard-incident-metric-value">{{ number_format($value) }}</div>
                <div class="guard-incident-metric-description">{{ $description }}</div>
            </div>
        @endforeach
    </div>

    <div class="guard-report-grid">
        <div class="guard-card guard-report-panel">
            <div class="guard-card-header"><div><h2>Status Breakdown</h2><p>Current state of incidents opened in range.</p></div></div>
            <div class="guard-report-breakdown">
                @foreach($report['status_breakdown'] as $label => $count)
                    <div><span>{{ str_replace('_', ' ', $label) }}</span><strong>{{ number_format($count) }}</strong></div>
                @endforeach
            </div>
        </div>

        <div class="guard-card guard-report-panel">
            <div class="guard-card-header"><div><h2>Severity Breakdown</h2><p>Risk distribution for the selected cohort.</p></div></div>
            <div class="guard-report-breakdown">
                @foreach($report['severity_breakdown'] as $label => $count)
                    <div><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong></div>
                @endforeach
            </div>
        </div>

        <div class="guard-card guard-report-panel">
            <div class="guard-card-header"><div><h2>Priority Breakdown</h2><p>Current operational triage priority.</p></div></div>
            <div class="guard-report-breakdown">
                @foreach($report['priority_breakdown'] as $label => $count)
                    <div><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong></div>
                @endforeach
            </div>
        </div>

        <div class="guard-card guard-report-panel">
            <div class="guard-card-header"><div><h2>Assignment / PIC</h2><p>Current ownership of incidents opened in range.</p></div></div>
            <div class="guard-report-breakdown">
                <div><span>Assigned</span><strong>{{ number_format($report['assignment_breakdown']['assigned']) }}</strong></div>
                <div><span>Unassigned</span><strong>{{ number_format($report['assignment_breakdown']['unassigned']) }}</strong></div>
                @foreach($report['assignment_breakdown']['by_pic'] as $pic)
                    <div><span>{{ $pic['name'] }}</span><strong>{{ number_format($pic['count']) }}</strong></div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="guard-card guard-report-section">
        <div class="guard-card-header"><div><h2>Response SLA Performance</h2><p>Acknowledgement performance for incidents opened in the reporting period.</p></div></div>
        <div class="guard-incident-resolution-analytics">
            @php($slaCards = [
                ['Acknowledged', number_format($report['sla']['acknowledged'])],
                ['SLA Met', number_format($report['sla']['met'])],
                ['SLA Breached', number_format($report['sla']['breached'])],
                ['SLA Met Rate', $report['sla']['met_rate'] !== null ? number_format($report['sla']['met_rate'], 1).'%' : '—'],
                ['Avg Acknowledgement', $report['sla']['average_acknowledgement_minutes'] !== null ? number_format($report['sla']['average_acknowledgement_minutes'], 1).' min' : '—'],
                ['Avg Resolution', $report['sla']['average_resolution_minutes'] !== null ? number_format($report['sla']['average_resolution_minutes'], 1).' min' : '—'],
            ])
            @foreach($slaCards as [$label, $value])
                <div class="guard-incident-resolution-card">
                    <div class="guard-incident-resolution-label">{{ $label }}</div>
                    <div class="guard-incident-resolution-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="guard-card guard-report-section">
        <div class="guard-card-header"><div><h2>Incident Trend</h2><p>Opened, resolved, and closed case events by day.</p></div></div>
        <div class="guard-table-wrap">
            <table class="guard-table">
                <thead><tr><th>Date</th><th>Opened</th><th>Resolved</th><th>Closed</th></tr></thead>
                <tbody>
                    @foreach($report['trends'] as $trend)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($trend['date'])->format('d M Y') }}</td>
                            <td>{{ number_format($trend['opened']) }}</td>
                            <td>{{ number_format($trend['resolved']) }}</td>
                            <td>{{ number_format($trend['closed']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="guard-card guard-report-section">
        <div class="guard-card-header">
            <div><h2>Audit Activity</h2><p>Immutable incident lifecycle, ownership, investigation, and case activity in the selected period.</p></div>
            <div class="guard-count">{{ number_format($report['audit_summary']['total']) }} Events</div>
        </div>
        <div class="guard-report-audit-summary">
            @foreach(['lifecycle' => 'Lifecycle', 'ownership' => 'Ownership', 'investigation' => 'Investigation', 'activity' => 'Other Activity'] as $key => $label)
                <span>{{ $label }} <strong>{{ number_format($report['audit_summary'][$key]) }}</strong></span>
            @endforeach
        </div>
        <div class="guard-table-wrap">
            <table class="guard-table">
                <thead><tr><th>Timestamp</th><th>Incident</th><th>Category</th><th>Activity</th><th>Actor</th><th>Transition / Notes</th></tr></thead>
                <tbody>
                    @forelse($auditActivities as $activity)
                        <tr>
                            <td>{{ $activity->created_at?->format('d M Y H:i:s') ?? '-' }}</td>
                            <td>
                                @if($activity->incident)
                                    <a class="guard-link" href="{{ route('security-incidents.show', $activity->incident) }}">{{ $activity->incident->incident_number }}</a>
                                @else
                                    <span class="guard-muted">Deleted incident</span>
                                @endif
                            </td>
                            <td><span class="guard-report-category">{{ $activity->activityCategory() }}</span></td>
                            <td>{{ $activity->activityLabel() }}</td>
                            <td>{{ $activity->user?->name ?? 'System' }}</td>
                            <td>
                                @if($activity->isStatusTransition())
                                    <strong>{{ $activity->old_status }} &rarr; {{ $activity->new_status }}</strong>
                                @endif
                                @if($activity->notes)
                                    <div class="guard-muted">{{ $activity->notes }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="guard-empty">No incident audit activity in this reporting period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($auditActivities->hasPages())
            <div class="guard-pagination">{{ $auditActivities->links() }}</div>
        @endif
    </div>
</div>
@endsection
