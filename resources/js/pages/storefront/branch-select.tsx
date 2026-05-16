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

function pinIcon(label: string, active: boolean): L.DivIcon {
    const color = active ? '#92400e' : '#b45309';
    const scale = active ? 1.15 : 1;
    return L.divIcon({
        className: 'star-coffee-pin',
        html: `
            <div style="transform:scale(${scale});transform-origin:bottom center;">
                <div style="width:32px;height:42px;display:flex;align-items:center;justify-content:center;background:${color};color:white;border-radius:16px 16px 16px 0;transform:rotate(-45deg);box-shadow:0 4px 10px rgba(0,0,0,.25);font-size:13px;font-weight:700;">
                    <span style="transform:rotate(45deg);">${label}</span>
                </div>
            </div>
        `,
        iconSize: [32, 42],
        iconAnchor: [16, 42],
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

        sortedBranches.forEach((branch, idx) => {
            if (!branch.latitude || !branch.longitude) return;
            const marker = L.marker([branch.latitude, branch.longitude], {
                icon: pinIcon(String(idx + 1), branch.id === activeId),
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
        <div className="bg-background fixed inset-0 flex flex-col">
            <Head title="Select Your Outlet" />

            {/* Header */}
            <div className="bg-card/95 border-border z-30 flex items-center justify-between gap-3 border-b px-3 py-3 backdrop-blur">
                <Link
                    href="/"
                    className="hover:bg-secondary flex size-9 items-center justify-center rounded-full"
                    aria-label="Back"
                >
                    <ArrowLeft className="size-4" />
                </Link>
                <h1 className="text-sm font-semibold">Select Your Outlet</h1>
                <div className="size-9" />
            </div>

            {/* Map */}
            <div className="relative flex-1">
                <div ref={mapDiv} className="absolute inset-0" />
                <button
                    type="button"
                    onClick={requestLocate}
                    className="bg-card text-card-foreground absolute right-3 top-3 z-[1000] flex size-10 items-center justify-center rounded-full shadow-md"
                    aria-label="Locate me"
                >
                    <Crosshair className="size-4" />
                </button>
                <div className="bg-amber-100/95 text-amber-900 absolute left-1/2 top-3 z-[1000] max-w-[260px] -translate-x-1/2 rounded-full px-3 py-1.5 text-[10px] font-medium shadow-sm">
                    We always choose the outlet closest to you. Select another one if you'd like.
                </div>
            </div>

            {/* Bottom carousel */}
            <div
                ref={carouselRef}
                className="bg-white relative z-20 -mt-4 flex shrink-0 snap-x snap-mandatory gap-3 overflow-x-auto rounded-t-2xl px-3 py-4 shadow-[0_-8px_24px_rgba(0,0,0,0.08)] dark:bg-neutral-900"
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
                    return (
                        <div
                            key={branch.id}
                            ref={(el) => {
                                if (el) cardRefs.current.set(branch.id, el);
                            }}
                            data-branch-id={branch.id}
                            className={cn(
                                'border-border bg-card relative flex w-[min(85vw,360px)] shrink-0 snap-center flex-col gap-2 rounded-xl border p-3 shadow-sm transition-all',
                                branch.id === activeId ? 'ring-primary/40 ring-2' : '',
                            )}
                        >
                            <div className="flex gap-3">
                                <div className="bg-secondary aspect-square size-20 shrink-0 overflow-hidden rounded-lg">
                                    {branch.cover_image || branch.logo ? (
                                        <img
                                            src={cover}
                                            alt={branch.name}
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center">
                                            <Coffee className="text-muted-foreground size-7" />
                                        </div>
                                    )}
                                </div>
                                <div className="flex flex-1 flex-col">
                                    <div className="flex items-start justify-between gap-2">
                                        <h3 className="text-sm font-semibold leading-tight">
                                            {branch.name}
                                        </h3>
                                        {branch.distance_km !== null && (
                                            <span className="text-muted-foreground shrink-0 text-[11px] font-medium">
                                                {branch.distance_km.toFixed(2)}km
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-muted-foreground mt-0.5 line-clamp-1 text-[11px]">
                                        {branch.address}
                                        {branch.city && `, ${branch.city}`}
                                    </p>
                                    <div className="mt-1 flex items-center justify-between gap-2">
                                        <span
                                            className={cn(
                                                'text-[11px] font-semibold',
                                                branch.is_open_now
                                                    ? 'text-emerald-600'
                                                    : 'text-red-500',
                                            )}
                                        >
                                            {branch.is_open_now ? 'Open' : 'Closed'}
                                        </span>
                                        {branch.latitude && branch.longitude && (
                                            <a
                                                href={`https://www.google.com/maps/dir/?api=1&destination=${branch.latitude},${branch.longitude}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-primary flex items-center gap-1 text-[11px] font-semibold hover:underline"
                                            >
                                                <Navigation className="size-3" /> Get Direction
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => handleSelect(branch)}
                                className="bg-primary text-primary-foreground hover:bg-primary/90 w-full rounded-lg py-2.5 text-sm font-semibold shadow-sm transition-colors"
                            >
                                Select This Store
                            </button>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
