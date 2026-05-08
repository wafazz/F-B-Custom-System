{{-- Star Coffee House — admin theme overrides (loaded after Filament's CSS so these win on specificity). --}}
<style>
    /* ─── Login canvas: pure black with amber corner glows ─── */
    .fi-simple-layout {
        background:
            radial-gradient(circle at top left, rgba(245, 158, 11, 0.12), transparent 50%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.08), transparent 55%),
            #000000 !important;
    }

    /* ─── Sign-in card ─── */
    .fi-simple-main {
        background-color: #0c0c0c !important;
        border: 1px solid rgba(245, 158, 11, 0.25) !important;
        box-shadow:
            0 0 0 1px rgba(245, 158, 11, 0.05),
            0 25px 50px -12px rgba(0, 0, 0, 0.85) !important;
        padding: 3rem 2.5rem !important;
    }

    /* ─── Brand logo: large circular crop with amber ring ─── */
    .fi-simple-main .fi-logo {
        display: flex !important;
        justify-content: center !important;
        margin-bottom: 1.5rem !important;
    }

    .fi-simple-main .fi-logo img {
        height: 7.5rem !important;
        width: 7.5rem !important;
        max-width: 7.5rem !important;
        border-radius: 9999px !important;
        object-fit: cover !important;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.40), 0 0 30px rgba(245, 158, 11, 0.25) !important;
    }

    /* ─── Headings ─── */
    .fi-simple-main .fi-simple-header,
    .fi-simple-main .fi-simple-header-heading {
        text-align: center !important;
        color: #ffffff !important;
        font-size: 1.625rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
    }

    .fi-simple-main .fi-simple-header-subheading {
        text-align: center !important;
        color: rgba(255, 255, 255, 0.55) !important;
    }

    /* ─── Form labels ─── */
    .fi-simple-main label,
    .fi-simple-main .fi-fo-field-wrp-label,
    .fi-simple-main .fi-fo-field-wrp-label > * {
        color: #f5f5f5 !important;
        font-weight: 500 !important;
    }

    /* ─── Inputs: dark fill, white text, amber focus ─── */
    .fi-simple-main input[type='email'],
    .fi-simple-main input[type='password'],
    .fi-simple-main input[type='text'],
    .fi-simple-main .fi-input {
        background-color: #1a1a1a !important;
        border-color: rgba(245, 158, 11, 0.20) !important;
        color: #ffffff !important;
    }

    .fi-simple-main input::placeholder,
    .fi-simple-main .fi-input::placeholder {
        color: rgba(255, 255, 255, 0.35) !important;
    }

    .fi-simple-main input:focus,
    .fi-simple-main .fi-input:focus {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.20) !important;
        outline: none !important;
    }

    /* "Show password" toggle button */
    .fi-simple-main .fi-input-wrp button {
        color: rgba(255, 255, 255, 0.5) !important;
    }
    .fi-simple-main .fi-input-wrp button:hover {
        color: #f59e0b !important;
    }

    /* ─── Submit button: solid amber + bold black text (matches logo accent) ─── */
    .fi-simple-main .fi-btn-color-primary,
    .fi-simple-main button[type='submit'].fi-btn {
        background-color: #f59e0b !important;
        background-image: none !important;
        color: #000000 !important;
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        letter-spacing: 0.025em !important;
        border: none !important;
        padding: 0.75rem 1rem !important;
        box-shadow: 0 4px 16px rgba(245, 158, 11, 0.35) !important;
        transition: all 0.18s ease !important;
    }

    .fi-simple-main .fi-btn-color-primary:hover,
    .fi-simple-main button[type='submit'].fi-btn:hover {
        background-color: #fbbf24 !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 22px rgba(245, 158, 11, 0.50) !important;
    }

    .fi-simple-main .fi-btn-color-primary:active {
        transform: translateY(0);
    }

    /* "Remember me" checkbox label */
    .fi-simple-main .fi-fo-checkbox label,
    .fi-simple-main .fi-fo-checkbox-input + label,
    .fi-simple-main [role='checkbox'] + * {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    /* Validation errors */
    .fi-simple-main .fi-fo-field-wrp-error-message {
        color: #fca5a5 !important;
    }
</style>
