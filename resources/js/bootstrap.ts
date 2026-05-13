import axios from 'axios';

declare global {
    interface Window {
        axios: typeof axios;
    }
}

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function detectChannel(): 'pwa' | 'web' {
    if (typeof window === 'undefined') return 'web';
    const standalone =
        window.matchMedia?.('(display-mode: standalone)').matches ||
        (window.navigator as Navigator & { standalone?: boolean }).standalone === true;
    return standalone ? 'pwa' : 'web';
}

window.axios.defaults.headers.common['X-Channel'] = detectChannel();
