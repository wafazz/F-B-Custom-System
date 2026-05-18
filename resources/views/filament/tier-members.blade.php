@php
    /** @var \App\Models\MembershipTier $tier */
    $rows = \App\Models\CustomerTier::query()
        ->where('membership_tier_id', $tier->id)
        ->with('user')
        ->orderByDesc('lifetime_spend')
        ->limit(100)
        ->get();
@endphp

@if ($rows->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">No customers in this tier yet.</p>
@else
    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
        Showing top {{ $rows->count() }} member{{ $rows->count() === 1 ? '' : 's' }} by lifetime spend.
    </p>
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Customer</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Email</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Lifetime spend</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Achieved</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-700 dark:bg-gray-900">
                @foreach ($rows as $row)
                    @php $user = $row->user; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-2">
                            @if ($user)
                                <a href="{{ \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $user->id]) }}"
                                   class="font-medium text-amber-700 hover:underline dark:text-amber-400">
                                    {{ $user->name }}
                                </a>
                            @else
                                <span class="text-gray-400">— deleted —</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                            {{ $user?->email ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-right font-mono text-xs">
                            RM{{ number_format((float) $row->lifetime_spend, 2) }}
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ $row->achieved_at?->diffForHumans() ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
