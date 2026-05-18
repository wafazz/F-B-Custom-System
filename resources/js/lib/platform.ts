interface NavigatorWithStandalone extends Navigator {
    standalone?: boolean;
}

export function isIOS(): boolean {
    if (typeof navigator === 'undefined') return false;
    const ua = navigator.userAgent;
    // iPadOS 13+ reports as Mac; sniff for touch points as a tiebreaker.
    if (/iPhone|iPod/.test(ua)) return true;
    if (/iPad/.test(ua)) return true;
    if (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1) return true;
    return false;
}

export function isStandalone(): boolean {
    if (typeof window === 'undefined') return false;
    if (window.matchMedia?.('(display-mode: standalone)').matches) return true;
    return (navigator as NavigatorWithStandalone).standalone === true;
}
