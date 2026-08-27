<span class="enrolment-status-field-label">
    <span>Status</span>
    <span class="required">*</span>
    <span class="enrolment-status-guide-tooltip" x-data="tooltip(`Open status explanation`)">
        {!! $statusGuide !!}
    </span>
</span>

<style>
    .form-label:has(> .enrolment-status-field-label) > .required {
        display: none;
    }

    .enrolment-status-field-label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .enrolment-status-field-label .enrolment-status-guide-trigger {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 18px !important;
        height: 18px !important;
        flex: 0 0 18px !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        color: currentColor !important;
        line-height: 1;
        text-decoration: none;
    }

    .enrolment-status-field-label .enrolment-status-guide-trigger .icon-wrapper,
    .enrolment-status-field-label .enrolment-status-guide-trigger svg {
        width: 16px !important;
        height: 16px !important;
    }
</style>
