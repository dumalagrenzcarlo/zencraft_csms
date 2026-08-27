<div class="rfid-checker-card">
    <div class="rfid-checker-heading">
        <h2>Check an RFID Card</h2>
        <p>Tap a card to find the student or teacher currently assigned to it.</p>
    </div>

    <form method="GET" action="{{ request()->url() }}" class="rfid-checker-form">
        <label for="rfid-checker-input">RFID Card UID</label>
        <div class="rfid-checker-controls">
            <input
                id="rfid-checker-input"
                name="rfid_card_uid"
                type="text"
                value=""
                autocomplete="off"
                inputmode="text"
                data-rfid-checker-input
                autofocus
                required
            >
            <button type="submit" class="btn btn-primary">Check Card</button>
        </div>
        <p class="rfid-checker-hint">The checker submits automatically after the reader finishes.</p>
    </form>

    @if ($error)
        <div class="rfid-checker-result is-error" role="alert">
            <strong>Unable to check this card</strong>
            <span>{{ $error }}</span>
        </div>
    @elseif ($searched && $assignment)
        <div class="rfid-checker-result is-assigned" role="status">
            <strong>Card assigned</strong>
            <dl>
                <div><dt>RFID UID</dt><dd>{{ $uid }}</dd></div>
                <div><dt>Assigned to</dt><dd>{{ $assignment['type'] }}</dd></div>
                <div><dt>Record ID</dt><dd>{{ $assignment['record_id'] }}</dd></div>
                <div><dt>{{ $assignment['identifier_label'] }}</dt><dd>{{ $assignment['identifier'] }}</dd></div>
                <div><dt>Name</dt><dd>{{ $assignment['name'] }}</dd></div>
            </dl>
            <a href="{{ $assignment['record_url'] }}" class="btn btn-primary rfid-checker-record-link">
                View {{ $assignment['type'] }} Record
            </a>
        </div>
    @elseif ($searched)
        <div class="rfid-checker-result is-unassigned" role="status">
            <strong>Unassigned card</strong>
            <span>No student or teacher is assigned to RFID UID {{ $uid }}.</span>
        </div>
    @endif
</div>
