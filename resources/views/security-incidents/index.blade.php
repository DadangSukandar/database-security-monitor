@extends('app')

@section('content')
<div class="guard-page">

    <div class="guard-page-header">
        <div>
            <h1>Security Incidents</h1>
            <p>
                Incident management and response cases escalated
                from security alerts.
            </p>
        </div>

        <div class="guard-count">
            {{ $incidents->total() }} Incidents
        </div>
    </div>

    <div class="guard-card">
        <div class="guard-card-header">
            <div>
                <h2>Incident Queue</h2>
                <p>
                    Security incidents requiring investigation
                    and response.
                </p>
            </div>
        </div>

        <div class="guard-table-wrap">
            <table class="guard-table">
                <thead>
                    <tr>
                        <th>Incident</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>PIC</th>
                        <th>Source Alert</th>
                        <th>Opened</th>
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
                            <td>
                                <a
                                    class="guard-link guard-incident-number"
                                    href="{{ route(
                                        'security-incidents.show',
                                        $incident
                                    ) }}"
                                >
                                    {{ $incident->incident_number }}
                                </a>
                            </td>

                            <td>
                                <div class="guard-primary">
                                    {{ $incident->title }}
                                </div>
                            </td>

                            <td>
                                <span class="guard-badge severity-{{ strtolower($severity) }}">
                                    {{ $severity }}
                                </span>
                            </td>

                            <td>
                                <span class="guard-badge status-{{ strtolower($status) }}">
                                    {{ str_replace('_', ' ', $status) }}
                                </span>
                            </td>

                            <td>
                                {{ $incident->assignedTo?->name ?? 'Unassigned' }}
                            </td>

                            <td>
                                <a
                                    class="guard-link"
                                    href="{{ route(
                                        'security-alerts.show',
                                        $incident->securityAlert
                                    ) }}"
                                >
                                    Alert #{{ $incident->security_alert_id }}
                                </a>
                            </td>

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
                                No security incidents found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($incidents->hasPages())
            <div class="guard-pagination">
                {{ $incidents->links() }}
            </div>
        @endif
    </div>

</div>
@endsection