import { CalendarDays, Cake, Coffee, Crown, Package, Sparkles, Tag, Ticket } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';

const MONTH_NAMES = [
    '',
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

export interface VoucherDetail {
    id: number;
    code: string;
    name: string;
    description: string | null;
    banner_image: string | null;
    discount_type: 'percentage' | 'fixed' | 'buy_x_get_y';
    discount_value: number;
    min_subtotal: number;
    max_discount: number | null;
    valid_from: string | null;
    valid_until: string | null;
    max_uses_per_user: number;
    tier_names: string[];
    birthday_months: number[] | null;
    product_names: string[];
    combo_names: string[];
    new_users_only: boolean;
    points_cost: number | null;
}

interface Props {
    voucher: VoucherDetail | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    actionLabel?: string;
    onAction?: () => void;
    actionDisabled?: boolean;
}

export function VoucherDetailsSheet({
    voucher,
    open,
    onOpenChange,
    actionLabel,
    onAction,
    actionDisabled,
}: Props) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="max-h-[92vh] overflow-y-auto bg-white text-neutral-900 sm:mx-auto sm:max-w-md sm:rounded-xl dark:bg-neutral-950 dark:text-neutral-50"
            >
                {voucher && (
                    <Body
                        voucher={voucher}
                        actionLabel={actionLabel}
                        onAction={onAction}
                        actionDisabled={actionDisabled}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}

function Body({
    voucher,
    actionLabel,
    onAction,
    actionDisabled,
}: {
    voucher: VoucherDetail;
    actionLabel?: string;
    onAction?: () => void;
    actionDisabled?: boolean;
}) {
    const discountLabel =
        voucher.discount_type === 'percentage'
            ? `${voucher.discount_value.toFixed(0)}% off`
            : `RM${voucher.discount_value.toFixed(2)} off`;

    return (
        <div>
            <SheetTitle className="sr-only">{voucher.name}</SheetTitle>

            {voucher.banner_image && (
                <div className="-mx-6 -mt-6 mb-4 overflow-hidden bg-amber-100">
                    <div className="aspect-[16/9] w-full">
                        <img
                            src={`/storage/${voucher.banner_image}`}
                            alt=""
                            aria-hidden
                            className="size-full object-cover"
                        />
                    </div>
                </div>
            )}

            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="text-lg leading-tight font-bold">{voucher.name}</p>
                    {voucher.description && (
                        <p className="text-muted-foreground mt-1 text-sm leading-snug whitespace-pre-line">
                            {voucher.description}
                        </p>
                    )}
                </div>
                <span className="flex shrink-0 items-center gap-1 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-700">
                    <Tag className="size-3.5" /> {discountLabel}
                </span>
            </div>

            <div className="border-border my-4 rounded-xl border bg-amber-50/40 p-3 font-mono text-sm font-bold tracking-wider text-amber-900">
                <Ticket className="mr-1.5 inline size-4 -translate-y-0.5" />
                {voucher.code}
            </div>

            <dl className="space-y-2.5 text-xs">
                {voucher.min_subtotal > 0 && (
                    <Row label="Minimum order" value={`RM${voucher.min_subtotal.toFixed(2)}`} />
                )}
                {voucher.max_discount !== null && (
                    <Row label="Maximum discount" value={`RM${voucher.max_discount.toFixed(2)}`} />
                )}
                <Row label="Uses per customer" value={String(voucher.max_uses_per_user)} />
                {(voucher.valid_from || voucher.valid_until) && (
                    <Row
                        icon={<CalendarDays className="size-3.5" />}
                        label="Valid"
                        value={validityRange(voucher.valid_from, voucher.valid_until)}
                    />
                )}
                {voucher.tier_names.length > 0 && (
                    <Row
                        icon={<Crown className="size-3.5" />}
                        label="Member tiers"
                        value={voucher.tier_names.join(', ')}
                    />
                )}
                {voucher.birthday_months && voucher.birthday_months.length > 0 && (
                    <Row
                        icon={<Cake className="size-3.5" />}
                        label="Birthday months"
                        value={voucher.birthday_months
                            .map((m) => MONTH_NAMES[m] ?? '')
                            .filter(Boolean)
                            .join(', ')}
                    />
                )}
                {voucher.product_names.length > 0 && (
                    <Row
                        icon={<Coffee className="size-3.5" />}
                        label="Eligible items"
                        value={voucher.product_names.join(', ')}
                    />
                )}
                {voucher.combo_names.length > 0 && (
                    <Row
                        icon={<Package className="size-3.5" />}
                        label="Eligible combos"
                        value={voucher.combo_names.join(', ')}
                    />
                )}
                {voucher.new_users_only && (
                    <Row
                        icon={<Sparkles className="size-3.5" />}
                        label="Customer type"
                        value="New customers only"
                    />
                )}
            </dl>

            {actionLabel && onAction && (
                <Button onClick={onAction} disabled={actionDisabled} className="mt-5 w-full">
                    {actionLabel}
                </Button>
            )}
        </div>
    );
}

function Row({ label, value, icon }: { label: string; value: string; icon?: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-3">
            <dt className="text-muted-foreground flex items-center gap-1.5">
                {icon}
                {label}
            </dt>
            <dd className="text-card-foreground max-w-[60%] text-right font-medium">{value}</dd>
        </div>
    );
}

function validityRange(from: string | null, until: string | null): string {
    const fromTxt = from ? new Date(from).toLocaleDateString() : null;
    const untilTxt = until ? new Date(until).toLocaleDateString() : null;
    if (fromTxt && untilTxt) return `${fromTxt} — ${untilTxt}`;
    if (untilTxt) return `Until ${untilTxt}`;
    if (fromTxt) return `From ${fromTxt}`;
    return 'No end date';
}
