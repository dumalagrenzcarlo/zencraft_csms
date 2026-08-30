<div class="table-wrap">
    <table class="table">
        <thead><tr><th>School</th><th>Workspace path</th><th>Plan</th><th>Status</th><th>Provisioned</th></tr></thead>
        <tbody>
        @forelse ($schools as $school)
            <tr>
                <td><a href="{{ route('platform.schools.show', $school) }}"><strong>{{ $school->name }}</strong></a></td>
                <td><a href="{{ url($school->slug) }}" target="_blank" rel="noreferrer">/{{ $school->slug }} ↗</a></td>
                <td>{{ $school->currentPlan?->name ?? 'Unassigned' }}</td>
                <td><span class="badge {{ $school->status }}">{{ $school->status }}</span></td>
                <td>{{ $school->provisioned_at?->diffForHumans() ?? 'Provisioning' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">No school workspaces have been provisioned yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
