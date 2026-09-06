@push('styles')
<style>
    .form-input {
        width: 100%; padding: 10px 14px; border-radius: 10px;
        background: #fff; border: 1px solid var(--t-border);
        color: var(--t-text); font-size: 13.5px;
    }
    .form-input:focus { outline: none; border-color: var(--t-dark); }
    textarea.form-input { resize: vertical; }
    .section-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.06em; color: var(--t-dark);
        margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--t-border);
    }
    .field-label { display: block; font-size: 12px; font-weight: 600; color: var(--t-muted); margin-bottom: 6px; }
    .field-hint { font-size: 11px; font-weight: 400; color: #A8A89E; }
</style>
@endpush