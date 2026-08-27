@extends('platform.layouts.app')
@section('title', 'Audit log')
@section('content')
<div class="page-head"><div><span class="eyebrow">Governance</span><h1>Platform audit log</h1></div></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Time</th><th>Actor</th><th>School</th><th>Event</th><th>IP</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at?->format('M j, Y g:i A') }}</td><td>{{ $log->user?->email ?? 'System' }}</td><td>{{ $log->tenant?->name ?? '—' }}</td><td>{{ $log->event }}</td><td>{{ $log->ip_address ?? '—' }}</td></tr>@empty<tr><td colspan="5" class="empty">No platform events recorded.</td></tr>@endforelse</tbody></table></div>{{ $logs->links() }}
@endsection
