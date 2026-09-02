@extends('app')

@section('content')
@php
    $severity = strtoupper($incident->severity ?? 'UNKNOWN');
    $status = strtoupper($incident->status ?? 'OPEN');
@endphp

<div class="guard-page">

    @if(session('success'))
        <div class="incident-flash incident-flash-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('incident'))
        <div class="incident-flash incident-flash-error">
            {{ $errors->first('incident') }}
        </div>
    @endif

    <div class="guard-page-header">
        <div>
            <div class="guard-breadcrumb">
                <a href="{{ route('security-incidents.index') }}">
                    Security Incidents
                </a>

                <span>/</span>

                <span>
                    {{ $incident->incident_number }}
                </span>
            </div>

            <h1>
                {{ $incident->incident_number }}
            </h1>

            <p>
                {{ $incident->title }}
            </p>
        </div>

        <div class="guard-header-badges">
            <span class="guard-badge severity-{{ strtolower($severity) }}">
                {{ $severity }}
            </span>

            <span class="guard-badge status-{{ strtolower($status) }}">
                {{ str_replace('_', ' ', $status) }}
            </span>
        </div>
    </div>

    <div class="guard-grid">

        {{-- INCIDENT INFORMATION --}}
        <div class="guard-card">
            <div class="guard-card-header">
                <div>
                    <h2>Incident Information</h2>
                    <p>
                        Core information for this security incident.
                    </p>
                </div>
            </div>

            <div class="guard-info-grid">

                <div class="guard-info-item">
                    <span>Incident Number</span>
                    <strong>
                        {{ $incident->incident_number }}
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Status</span>
                    <strong>
                        {{ str_replace('_', ' ', $status) }}
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Severity</span>
                    <strong>{{ $severity }}</strong>
                </div>

                <div class="guard-info-item">
                    <span>Opened At</span>
                    <strong>
                        {{ $incident->opened_at?->format(
                            'd M Y H:i:s'
                        ) ?? '-' }}
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Created By</span>
                    <strong>
                        {{ $incident->createdBy?->name ?? 'System' }}
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Assigned PIC</span>
                    <strong>
                        {{ $incident->assignedTo?->name ?? 'Unassigned' }}
                    </strong>
                </div>

            </div>
        </div>

        {{-- SOURCE ALERT --}}
        <div class="guard-card">
            <div class="guard-card-header">
                <div>
                    <h2>Source Alert</h2>
                    <p>
                        Detection evidence that originated this incident.
                    </p>
                </div>
            </div>

            <div class="guard-info-grid">

                <div class="guard-info-item">
                    <span>Alert ID</span>

                    <strong>
                        <a
                            class="guard-link"
                            href="{{ route(
                                'security-alerts.show',
                                $incident->securityAlert
                            ) }}"
                        >
                            Alert #{{ $incident->security_alert_id }}
                        </a>
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Alert Status</span>

                    <strong>
                        {{ $incident->securityAlert?->status ?? '-' }}
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Occurrences</span>

                    <strong>
                        {{ $incident->securityAlert?->occurrence_count ?? 0 }}
                    </strong>
                </div>

                <div class="guard-info-item">
                    <span>Last Seen</span>

                    <strong>
                        {{ $incident->securityAlert?->last_seen_at?->format(
                            'd M Y H:i:s'
                        ) ?? '-' }}
                    </strong>
                </div>

            </div>
        </div>

    </div>

    {{-- DESCRIPTION --}}
    <div class="guard-card guard-section">
        <div class="guard-card-header">
            <div>
                <h2>Incident Description</h2>
                <p>
                    Context captured when the alert was escalated.
                </p>
            </div>
        </div>

        <div class="guard-description">
            {{ $incident->description ?: 'No description provided.' }}
        </div>
    </div>

    {{-- OWNERSHIP --}}
    <div class="guard-card guard-section">
        <div class="guard-card-header">
            <div>
                <h2>Incident Ownership</h2>
                <p>
                    Current person responsible for incident response.
                </p>
            </div>
        </div>

    <div class="guard-card incident-investigation-card">
        <div class="guard-card-header">
            <div>
                <div class="guard-card-eyebrow">
                    Investigation
                </div>

                <h2 class="guard-card-title">
                    Incident Investigation Notes
                </h2>
            </div>
        </div>

        <div class="guard-card-body">
            <p class="incident-investigation-description">
                Add an immutable analyst note to document
                investigation findings, verification,
                remediation activity, or post-incident review.
            </p>

            <form
                method="POST"
                action="{{ route(
                    'security-incidents.investigation-notes.store',
                    $incident
                ) }}"
                class="incident-investigation-form"
            >
                @csrf

                <div class="incident-investigation-field">
                    <label
                        for="investigation_note"
                        class="guard-label"
                    >
                        Investigation Note
                    </label>

                    <textarea
                        id="investigation_note"
                        name="note"
                        rows="5"
                        maxlength="5000"
                        class="incident-investigation-textarea"
                        placeholder="Document investigation findings, verification, remediation activity, or post-incident observations..."
                        required
                    >{{ old('note') }}</textarea>

                    <div class="incident-investigation-meta">
                        Maximum 5,000 characters.
                        This note will be stored in the
                        immutable incident history.
                    </div>

                    @error('note')
                        <div class="incident-field-error">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="incident-investigation-actions">
                    <button
                        type="submit"
                        class="incident-action-button"
                    >
                        Add Investigation Note
                    </button>
                </div>
            </form>
        </div>
    </div>

        <div class="guard-card guard-section">
            <div class="guard-card-header">
                <div>
                    <h2>Incident Response Actions</h2>
                    <p>
                        Progress this incident through the
                        security response lifecycle.
                    </p>
                </div>
            </div>

            <div class="incident-actions">

                @if($status === 'OPEN')
                    <form
                        method="POST"
                        action="{{ route(
                            'security-incidents.acknowledge',
                            $incident
                        ) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="incident-action-button"
                            onclick="return confirm(
                                'Acknowledge this incident?'
                            )"
                        >
                            Acknowledge Incident
                        </button>
                    </form>
                @elseif($status === 'ACKNOWLEDGED')
                    <form
                        method="POST"
                        action="{{ route(
                            'security-incidents.investigate',
                            $incident
                        ) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="incident-action-button"
                            onclick="return confirm(
                                'Start investigation for this incident?'
                            )"
                        >
                            Start Investigation
                        </button>
                    </form>
                @elseif($status === 'INVESTIGATING')
                    <form
                        method="POST"
                        action="{{ route(
                            'security-incidents.contain',
                            $incident
                        ) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="incident-action-button"
                            onclick="return confirm(
                                'Mark this incident as contained?'
                            )"
                        >
                            Contain Incident
                        </button>
                    </form>
                @elseif($status === 'CONTAINED')
                    <form
                        method="POST"
                        action="{{ route(
                            'security-incidents.resolve',
                            $incident
                        ) }}"
                        class="incident-resolve-form"
                    >
                        @csrf

                        <label
                            for="resolution_note"
                            class="guard-label"
                        >
                            Resolution Note
                        </label>

                        <textarea
                            id="resolution_note"
                            name="resolution_note"
                            rows="5"
                            maxlength="5000"
                            required
                            class="incident-textarea"
                            placeholder="Describe the remediation and resolution..."
                        >{{ old('resolution_note') }}</textarea>

                        @error('resolution_note')
                            <div class="incident-field-error">
                                {{ $message }}
                            </div>
                        @enderror

                        <button
                            type="submit"
                            class="incident-action-button"
                            onclick="return confirm(
                                'Resolve this incident?'
                            )"
                        >
                            Resolve Incident
                        </button>
                    </form>
                @elseif($status === 'RESOLVED')
                    <form
                        method="POST"
                        action="{{ route(
                            'security-incidents.close',
                            $incident
                        ) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="incident-action-button"
                            onclick="return confirm(
                                'Close this incident?'
                            )"
                        >
                            Close Incident
                        </button>
                    </form>
                @elseif($status === 'CLOSED')
                    <div class="incident-terminal-state">
                        This incident is closed.
                        No further lifecycle actions are available.
                    </div>
                @endif

            </div>
        </div>

        <div class="guard-ownership">
            <div>
                <span class="guard-label">
                    Current PIC
                </span>

                <div class="guard-owner-name">
                    {{ $incident->assignedTo?->name ?? 'Unassigned' }}
                </div>

                @if($incident->assigned_at)
                    <div class="guard-muted">
                        Assigned
                        {{ $incident->assigned_at->format(
                            'd M Y H:i:s'
                        ) }}
                    </div>
                @endif
            </div>

            <div class="incident-ownership-controls">

            @if($teamMembers->isNotEmpty())
                <form
                    method="POST"
                    action="{{ route(
                        'security-incidents.assign',
                        $incident
                    ) }}"
                    class="incident-assignment-form"
                >
                    @csrf

                    <div class="incident-assignment-field">
                        <label
                            for="assigned_to_user_id"
                            class="guard-label"
                        >
                            Incident PIC
                        </label>

                        <select
                            id="assigned_to_user_id"
                            name="assigned_to_user_id"
                            class="incident-select"
                            required
                        >
                            <option value="">
                                Select team member
                            </option>

                            @foreach($teamMembers as $member)
                                <option
                                    value="{{ $member->id }}"
                                    @selected(
                                        old(
                                            'assigned_to_user_id',
                                            $incident
                                                ->assigned_to_user_id
                                        ) == $member->id
                                    )
                                >
                                    {{ $member->name }}
                                    @if(
                                        $member->id ===
                                        auth()->id()
                                    )
                                        (You)
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('assigned_to_user_id')
                            <div class="incident-field-error">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="incident-assignment-actions">
                        <button
                            type="submit"
                            class="incident-action-button"
                        >
                            {{
                                $incident->assigned_to_user_id
                                    ? 'Reassign PIC'
                                    : 'Assign PIC'
                            }}
                        </button>
                    </div>
                </form>

                @if($incident->assigned_to_user_id)
                    <form
                        method="POST"
                        action="{{ route(
                            'security-incidents.unassign',
                            $incident
                        ) }}"
                        class="incident-unassign-form"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="incident-secondary-button"
                            onclick="return confirm(
                                'Remove the current PIC from this incident?'
                            )"
                        >
                            Unassign PIC
                        </button>
                    </form>
                @endif
            @else
                <div class="incident-assignment-empty">
                    No members are available in your current team.
                </div>
            @endif

        </div>
        </div>
    </div>

    {{-- LIFECYCLE --}}
    <div class="guard-card guard-section">
        <div class="guard-card-header">
            <div>
                <h2>Incident Lifecycle</h2>
                <p>
                    Current response stage and lifecycle timestamps.
                </p>
            </div>
        </div>

        <div class="guard-card guard-section">
            <div class="guard-card-header">
                <div>
                    <h2>Incident History</h2>
                    <p>
                        Immutable audit trail for incident
                        lifecycle activity.
                    </p>
                </div>

                <div class="guard-count">
                    {{ $incident->histories->count() }}
                    Events
                </div>
            </div>

            <div class="incident-history">

                @forelse(
                    $incident->histories
                        ->sortByDesc('created_at')
                    as $history
                )
                    <div class="incident-history-item">

                        <div class="incident-history-marker">
                            <div class="incident-history-dot"></div>
                            <div class="incident-history-line"></div>
                        </div>

                        <div class="incident-history-content">

                            <div class="incident-history-header">
                                <strong>
                                    {{ str_replace(
                                        '_',
                                        ' ',
                                        $history->action
                                    ) }}
                                </strong>

                                <span>
                                    {{ $history->created_at
                                        ?->format('d M Y H:i:s') }}
                                </span>
                            </div>

                            @if(
                                $history->old_status ||
                                $history->new_status
                            )
                                <div class="incident-history-transition">

                                    <span class="guard-badge status-{{
                                        strtolower(
                                            $history->old_status
                                            ?? 'open'
                                        )
                                    }}">
                                        {{
                                            str_replace(
                                                '_',
                                                ' ',
                                                $history->old_status
                                                ?? '-'
                                            )
                                        }}
                                    </span>

                                    <span class="incident-history-arrow">
                                        →
                                    </span>

                                    <span class="guard-badge status-{{
                                        strtolower(
                                            $history->new_status
                                            ?? 'open'
                                        )
                                    }}">
                                        {{
                                            str_replace(
                                                '_',
                                                ' ',
                                                $history->new_status
                                                ?? '-'
                                            )
                                        }}
                                    </span>

                                </div>
                            @endif

                            <div class="incident-history-meta">
                                Actor:
                                <strong>
                                    {{ $history->user?->name ?? 'System' }}
                                </strong>
                            </div>

                            @if($history->notes)
                                <div class="incident-history-note">
                                    {{ $history->notes }}
                                </div>
                            @endif

                        </div>
                    </div>
                @empty
                    <div class="guard-empty">
                        No incident lifecycle history recorded yet.
                    </div>
                @endforelse

            </div>
        </div>

        <div class="guard-lifecycle">

            @foreach([
                'Opened' => $incident->opened_at,
                'Acknowledged' => $incident->acknowledged_at,
                'Investigation Started' =>
                    $incident->investigation_started_at,
                'Contained' => $incident->contained_at,
                'Resolved' => $incident->resolved_at,
                'Closed' => $incident->closed_at,
            ] as $label => $timestamp)

                <div class="guard-lifecycle-step {{ $timestamp ? 'complete' : '' }}">
                    <div class="guard-lifecycle-dot"></div>

                    <div>
                        <strong>{{ $label }}</strong>

                        <span>
                            {{ $timestamp?->format(
                                'd M Y H:i:s'
                            ) ?? 'Pending' }}
                        </span>
                    </div>
                </div>

            @endforeach

        </div>
    </div>

</div>
@endsection