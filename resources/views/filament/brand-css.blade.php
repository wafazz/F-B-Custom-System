{{-- Star Coffee House — admin theme overrides (loads after Filament's CSS so these win on specificity). --}}
<style>
    /* Login canvas: pure black with subtle amber corner glows */
    .fi-simple-layout {
        background:
            radial-gradient(circle at top left, rgba(245, 158, 11, 0.10), transparent 50%),
            radial-gradient(circle at bottom right, rgba(245, 158, 11, 0.06), transparent 55%),
            #000000 !important;
    }

    /* Sign-in card */
    .fi-simple-main {
        background-color: #0a0a0a !important;
        border: 1px solid rgba(245, 158, 11, 0.25) !important;
        box-shadow:
            0 0 0 1px rgba(245, 158, 11, 0.05),
            0 25px 50px -12px rgba(0, 0, 0, 0.8) !important;
    }

    /* Brand logo on login → big circular crop with amber ring */
    .fi-simple-main .fi-logo {
        display: flex !important;
        justify-content: center !important;
        margin-bottom: 1rem !important;
    }

    .fi-simple-main .fi-logo img {
        height: 5rem !important;
        width: 5rem !important;
        border-radius: 9999px !important;
        object-fit: cover !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.30) !important;
    }

    /* Headings centered */
    .fi-simple-main .fi-simple-header,
    .fi-simple-main .fi-simple-header-heading,
    .fi-simple-main .fi-simple-header-subheading {
        text-align: center !important;
    }

    /* Submit button: amber gradient, black text, lift on hover */
    .fi-simple-main .fi-btn-color-primary {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        color: #000000 !important;
        font-weight: 600 !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35) !important;
        transition: transform 0.15s ease, box-shadow 0.15s ease !important;
    }

    .fi-simple-main .fi-btn-color-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45) !important;
    }

    /* Input focus → amber ring */
    .fi-simple-main .fi-input:focus {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.18) !important;
    }
</style>
