import { Head, Link, router } from '@inertiajs/react';
import L, { type Map as LeafletMap, type Marker as LeafletMarker } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowLeft, Coffee, Crosshair, Navigation } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useBranchStore } from '@/stores/branch-store';
import { useCartStore } from '@/stores/cart-store';
import { cn } from '@/lib/utils';
import type { BranchSummary } from '@/types/menu';

interface Props {
    branches: BranchSummary[];
}

// Centre of Peninsular Malaysia as a fallback when geolocation is denied.
const FALLBACK_CENTER: [number, number] = [3.139, 101.6869];

function haversineKm(a: [number, number], b: [number, number]): number {
    const R = 6371;
    const dLat = ((b[0] - a[0]) * Math.PI) / 180;
    const dLng = ((b[1] - a[1]) * Math.PI) / 180;
    const lat1 = (a[0] * Math.PI) / 180;
    const lat2 = (b[0] * Math.PI) / 180;
    const x =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(x));
}

function escapeHtml(s: string): string {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function pinIcon(
    name: string,
    image: string | null,
    active: boolean,
): L.DivIcon {
    const ring = active ? '#92400e' : '#b45309';
    const shadow = active
        ? '0 8px 20px rgba(124,74,30,0.45)'
        : '0 4px 12px rgba(0,0,0,0.22)';
    const size = active ? 56 : 44;
    const labelMaxW = active ? 140 : 110;
    const safeName = escapeHtml(name.replace(/^star coffee[\s—-]*/i, ''));
    const safeImage = image ? escapeHtml(image) : null;
    const imageHtml = safeImage
        ? `<img src="/storage/${safeImage}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;" />`
        : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;font-weight:700;font-size:14px;">★</div>`;

    return L.divIcon({
        className: 'star-coffee-pin',
        html: `
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:none;">
                <div style="
                    width:${size}px;height:${size}px;
                    border-radius:50%;
                    border:3px solid ${ring};
                    background:white;
                    overflow:hidden;
                    box-shadow:${shadow};
                    pointer-events:auto;
                    transition:all .2s ease-out;
                ">${imageHtml}</div>
                <div style="
                    background:${active ? ring : 'rgba(255,255,255,0.95)'};
                    color:${active ? '#fff8eb' : '#1f1716'};
                    padding:2px 8px;
                    border-radius:999px;
                    font-size:10px;
                    font-weight:700;
                    max-width:${labelMaxW}px;
                    white-space:nowrap;
                    overflow:hidden;
                    text-overflow:ellipsis;
                    box-shadow:0 2px 6px rgba(0,0,0,0.18);
                    pointer-events:auto;
                ">${safeName}</div>
            </div>
        `,
        iconSize: [Math.max(labelMaxW, size), size + 22],
        iconAnchor: [Math.max(labelMaxW, size) / 2, size + 22],
    });
}

export default function BranchSelect({ branches }: Props) {
    const setBranch = useBranchStore((s) => s.setBranch);
    const rebindBranch = useCartStore((s) => s.rebindBranch);

    const mapDiv = useRef<HTMLDivElement | null>(null);
    const mapRef = useRef<LeafletMap | null>(null);
    const markersRef = useRef<Map<number, LeafletMarker>>(new Map());
    const carouselRef = useRef<HTMLDivElement | null>(null);
    const cardRefs = useRef<Map<number, HTMLDivElement>>(new Map());
    const skipSyncRef = useRef(false);

    const [userLoc, setUserLoc] = useState<[number, number] | null>(null);
    const [activeId, setActiveId] = useState<number | null>(
        branches.find((b) => b.latitude && b.longitude)?.id ?? branches[0]?.id ?? null,
    );

    const branchesWithDistance = useMemo(() => {
        return branches.map((b) => {
            const km =
                userLoc && b.latitude && b.longitude
                    ? haversineKm(userLoc, [b.latitude, b.longitude])
                    : null;
            return { ...b, distance_km: km };
        });
    }, [branches, userLoc]);

    const sortedBranches = useMemo(() => {
        if (!userLoc) return branchesWithDistance;
        return [...branchesWithDistance].sort((a, b) => {
            if (a.distance_km === null && b.distance_km === null) return 0;
            if (a.distance_km === null) return 1;
            if (b.distance_km === null) return -1;
            return a.distance_km - b.distance_km;
        });
    }, [branchesWithDistance, userLoc]);

    // Initialise Leaflet map once.
    useEffect(() => {
        if (!mapDiv.current || mapRef.current) return;

        const firstWithCoords = sortedBranches.find((b) => b.latitude && b.longitude);
        const initialCenter: [number, number] = firstWithCoords?.latitude
            ? [firstWithCoords.latitude!, firstWithCoords.longitude!]
            : FALLBACK_CENTER;

        const map = L.map(mapDiv.current, {
            center: initialCenter,
            zoom: 14,
            zoomControl: false,
        });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19,
        }).addTo(map);

        L.control.zoom({ position: 'bottomleft' }).addTo(map);
        mapRef.current = map;

        const markers = markersRef.current;
        return () => {
            map.remove();
            mapRef.current = null;
            markers.clear();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Place / refresh pins whenever branches list (or active id) changes.
    useEffect(() => {
        const map = mapRef.current;
        if (!map) return;

        markersRef.current.forEach((m) => m.remove());
        markersRef.current.clear();

        sortedBranches.forEach((branch) => {
            if (!branch.latitude || !branch.longitude) return;
            const thumb = branch.logo ?? branch.cover_image ?? null;
            const marker = L.marker([branch.latitude, branch.longitude], {
                icon: pinIcon(branch.name, thumb, branch.id === activeId),
                zIndexOffset: branch.id === activeId ? 1000 : 0,
            }).addTo(map);
            marker.on('click', () => {
                skipSyncRef.current = true;
                setActiveId(branch.id);
                cardRefs.current.get(branch.id)?.scrollIntoView({
                    behavior: 'smooth',
                    inline: 'center',
                    block: 'nearest',
                });
            });
            markersRef.current.set(branch.id, marker);
        });
    }, [sortedBranches, activeId]);

    // Pan map when active branch changes.
    useEffect(() => {
        const map = mapRef.current;
        if (!map || activeId === null) return;
        const branch = branchesWithDistance.find((b) => b.id === activeId);
        if (!branch?.latitude || !branch?.longitude) return;
        map.flyTo([branch.latitude, branch.longitude], Math.max(map.getZoom(), 15), {
            duration: 0.5,
        });
    }, [activeId, branchesWithDistance]);

    // Try geolocation.
    function requestLocate() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const here: [number, number] = [pos.coords.latitude, pos.coords.longitude];
                setUserLoc(here);
                mapRef.current?.flyTo(here, 14, { duration: 0.6 });
            },
            () => {
                // user denied — silently keep fallback
            },
            { enableHighAccuracy: true, timeout: 8000 },
        );
    }

    useEffect(() => {
        requestLocate();
    }, []);

    // Scroll-snap → update active branch on swipe.
    useEffect(() => {
        if (skipSyncRef.current) {
            skipSyncRef.current = false;
            return;
        }
        const root = carouselRef.current;
        if (!root) return;

        const obs = new IntersectionObserver(
            (entries) => {
                if (skipSyncRef.current) return;
                const visible = entries
                    .filter((e) => e.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                if (visible) {
                    const id = Number(visible.target.getAttribute('data-branch-id'));
                    if (Number.isFinite(id) && id !== activeId) setActiveId(id);
                }
            },
            { root, threshold: [0.55, 0.75] },
        );

        cardRefs.current.forEach((el) => obs.observe(el));
        return () => obs.disconnect();
    }, [sortedBranches, activeId]);

    function handleSelect(branch: BranchSummary) {
        const cleared = rebindBranch(branch.id);
        if (cleared) {
            window.alert('Switching branch cleared your existing cart.');
        }
        setBranch(branch);
        router.visit(`/branches/${branch.id}`);
    }

    return (
        <div className="bg-background fixed inset-0 overflow-hidden">
            <Head title="Select Your Outlet" />

            {/* Full-bleed map fills the entire viewport */}
            <div ref={mapDiv} className="absolute inset-0 z-0" />

            {/* Floating header */}
            <div className="bg-card/90 absolute inset-x-0 top-0 z-30 flex items-center justify-between gap-3 border-b border-amber-100/50 px-3 py-3 backdrop-blur-md">
                <Link
                    href="/"
                    className="bg-card hover:bg-amber-50 flex size-9 items-center justify-center rounded-full shadow-sm"
                    aria-label="Back"
                >
                    <ArrowLeft className="size-4" />
                </Link>
                <h1 className="text-sm font-semibold">Select Your Outlet</h1>
                <div className="size-9" />
            </div>

            {/* Floating hint chip below the header */}
            <div className="bg-amber-100/95 text-amber-900 absolute left-1/2 top-[60px] z-30 max-w-[280px] -translate-x-1/2 rounded-full px-3 py-1.5 text-[10px] font-medium shadow-md">
                We always choose the outlet closest to you. Select another one if you'd like.
            </div>

            {/* Floating locate-me button */}
            <button
                type="button"
                onClick={requestLocate}
                className="bg-card text-card-foreground absolute right-3 top-[60px] z-30 flex size-10 items-center justify-center rounded-full shadow-md hover:bg-amber-50"
                aria-label="Locate me"
            >
                <Crosshair className="size-4" />
            </button>

            {/* Branch carousel floats above the map at the bottom */}
            <div
                ref={carouselRef}
                className="absolute inset-x-0 bottom-0 z-20 flex snap-x snap-mandatory gap-3 overflow-x-auto px-3 py-4"
                style={{ scrollbarWidth: 'none' }}
            >
                {sortedBranches.length === 0 && (
                    <p className="text-muted-foreground w-full text-center text-sm">
                        No branches available.
                    </p>
                )}
                {sortedBranches.map((branch) => {
                    const cover = branch.cover_image
                        ? `/storage/${branch.cover_image}`
                        : branch.logo
                          ? `/storage/${branch.logo}`
                          : '/images/logo.jpg';
                    const isActive = branch.id === activeId;
                    return (
                        <div
                            key={branch.id}
                            ref={(el) => {
                                if (el) cardRefs.current.set(branch.id, el);
                            }}
                            data-branch-id={branch.id}
                            className={cn(
                                'group relative flex w-[min(86vw,380px)] shrink-0 snap-center flex-col overflow-hidden rounded-2xl bg-white/95 backdrop-blur-xl transition-all duration-300 dark:bg-neutral-900/95',
                                isActive
                                    ? 'ring-1 ring-amber-300/60 shadow-[0_20px_40px_-12px_rgba(124,74,30,0.35)] -translate-y-1'
                                    : 'shadow-[0_8px_24px_-8px_rgba(0,0,0,0.2)]',
                            )}
                        >
                            {/* Top accent line — appears on the active card */}
                            <div
                                className={cn(
                                    'h-1 w-full transition-all',
                                    isActive
                                        ? 'bg-gradient-to-r from-amber-300 via-amber-500 to-amber-700'
                                        : 'bg-transparent',
                                )}
                            />

                            <div className="flex gap-3 p-3.5">
                                <div className="relative bg-amber-50 dark:bg-amber-950/40 aspect-square size-[88px] shrink-0 overflow-hidden rounded-xl ring-1 ring-amber-100/80 dark:ring-amber-900/40">
                                    {branch.cover_image || branch.logo ? (
                                        <img
                                            src={cover}
                                            alt={branch.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center">
                                            <Coffee className="text-amber-700/60 size-8" />
                                        </div>
                                    )}
                                    {branch.distance_km !== null && (
                                        <span className="absolute bottom-1 left-1 rounded-full bg-black/70 px-1.5 py-0.5 text-[9px] font-bold text-white">
                                            {branch.distance_km.toFixed(1)}km
                                        </span>
                                    )}
                                </div>
                                <div className="flex min-w-0 flex-1 flex-col">
                                    <div className="flex items-start justify-between gap-2">
                                        <p className="text-amber-700 text-[10px] font-bold uppercase tracking-[0.18em]">
                                            Star Coffee
                                        </p>
                                        <span
                                            title={
                                                !branch.is_open_now && branch.closed_reason
                                                    ? branch.closed_reason
                                                    : undefined
                                            }
                                            className={cn(
                                                'flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider',
                                                branch.is_open_now
                                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                                                    : 'bg-red-50 text-red-600 ring-1 ring-red-200',
                                            )}
                                        >
                                            <span
                                                className={cn(
                                                    'size-1.5 rounded-full',
                                                    branch.is_open_now
                                                        ? 'bg-emerald-500'
                                                        : 'bg-red-500',
                                                )}
                                            />
                                            {branch.is_open_now
                                                ? 'Open'
                                                : (branch.closed_reason ?? 'Closed')}
                                        </span>
                                    </div>
                                    <h3 className="text-card-foreground mt-0.5 truncate text-base font-bold leading-tight">
                                        {branch.name.replace(/^star coffee[\s—-]*/i, '')}
                                    </h3>
                                    <p className="text-muted-foreground mt-1 line-clamp-1 text-[11px] leading-snug">
                                        {branch.address}
                                        {branch.city && `, ${branch.city}`}
                                    </p>
                                    {branch.latitude && branch.longitude && (
                                        <a
                                            href={`https://www.google.com/maps/dir/?api=1&destination=${branch.latitude},${branch.longitude}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-amber-700 hover:text-amber-800 mt-auto inline-flex w-fit items-center gap-1 text-[11px] font-semibold hover:underline"
                                        >
                                            <Navigation className="size-3" /> Get directions
                                        </a>
                                    )}
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => handleSelect(branch)}
                                className={cn(
                                    'group/btn relative mx-3.5 mb-3.5 overflow-hidden rounded-xl py-3 text-xs font-bold uppercase tracking-[0.2em] shadow-[0_6px_18px_-6px_rgba(124,74,30,0.5)] transition-all',
                                    isActive
                                        ? 'bg-gradient-to-r from-amber-800 via-amber-700 to-amber-900 text-amber-50 hover:from-amber-700 hover:to-amber-800'
                                        : 'bg-neutral-900 text-amber-50 hover:bg-neutral-800 dark:bg-amber-50 dark:text-neutral-900',
                                )}
                            >
                                <span className="relative z-10">Select this store</span>
                                <span className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full" />
                            </button>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
