import { Download, Share, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { isIOS, isStandalone } from '@/lib/platform';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const DISMISS_KEY = 'star-coffee:install-dismissed';

export function InstallPrompt() {
    const [evt, setEvt] = useState<BeforeInstallPromptEvent | null>(null);
    const [open, setOpen] = useState(false);
    const [iosHint, setIosHint] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        if (window.localStorage.getItem(DISMISS_KEY)) return;

        // iOS Safari never fires beforeinstallprompt — show a manual hint
        // unless the user already launched from Home Screen.
        if (isIOS() && !isStandalone()) {
            setIosHint(true);
            setOpen(true);
            return;
        }

        const handler = (e: Event) => {
            e.preventDefault();
            setEvt(e as BeforeInstallPromptEvent);
            setOpen(true);
        };
        window.addEventListener('beforeinstallprompt', handler);
        return () => window.removeEventListener('beforeinstallprompt', handler);
    }, []);

    async function install() {
        if (!evt) return;
        await evt.prompt();
        await evt.userChoice;
        setOpen(false);
        setEvt(null);
    }

    function dismiss() {
        window.localStorage.setItem(DISMISS_KEY, '1');
        setOpen(false);
    }

    if (!open) return null;

    if (iosHint) {
        return (
            <div className="fixed bottom-20 left-1/2 z-40 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-xl border border-amber-200 bg-white p-4 shadow-2xl">
                <div className="flex items-start gap-3">
                    <div className="flex size-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <Share className="size-5" />
                    </div>
                    <div className="flex-1">
                        <p className="text-sm font-semibold">Install Star Coffee on iPhone</p>
                        <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                            Tap the <span className="font-semibold">Share</span> icon{' '}
                            <span className="inline-block translate-y-0.5">
                                <Share className="inline size-3" />
                            </span>{' '}
                            in Safari, then choose{' '}
                            <span className="font-semibold">Add to Home Screen</span>. Open Star
                            Coffee from your Home Screen to receive order-ready notifications.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={dismiss}
                        className="text-muted-foreground hover:text-foreground"
                        aria-label="Dismiss"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="fixed bottom-20 left-1/2 z-40 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 rounded-xl border border-amber-200 bg-white p-4 shadow-2xl">
            <div className="flex items-start gap-3">
                <div className="flex size-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <Download className="size-5" />
                </div>
                <div className="flex-1">
                    <p className="text-sm font-semibold">Install Star Coffee</p>
                    <p className="text-muted-foreground text-xs">
                        Add to home screen for instant access.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={dismiss}
                    className="text-muted-foreground hover:text-foreground"
                    aria-label="Dismiss"
                >
                    <X className="size-4" />
                </button>
            </div>
            <Button onClick={install} className="mt-3 w-full">
                Install app
            </Button>
        </div>
    );
}
