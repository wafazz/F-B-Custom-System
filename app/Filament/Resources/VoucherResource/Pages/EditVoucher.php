<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Resources\VoucherResource;
use App\Models\Voucher;
use App\Services\Push\PushService;
use App\Services\Vouchers\VoucherService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVoucher extends EditRecord
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('notifyEligible')
                ->label('Notify eligible members')
                ->icon('heroicon-o-megaphone')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Send announcement?')
                ->modalDescription('Every customer matching this voucher\'s tier + birthday-month rules gets an inbox notification and a Web Push (if subscribed). Existing subscriptions to this same voucher are not re-notified at the push layer — duplicates are tagged.')
                ->modalSubmitActionLabel('Send announcement')
                ->action(function (Voucher $record, VoucherService $service, PushService $push): void {
                    $count = $service->notifyEligibleMembers($record, $push);

                    Notification::make()
                        ->title($count > 0 ? "Sent to {$count} eligible member(s)" : 'No eligible members matched')
                        ->body($count > 0
                            ? 'Inbox rows are visible immediately; push delivery is best-effort.'
                            : 'Check the eligibility rules — no customers fit them right now.')
                        ->{$count > 0 ? 'success' : 'warning'}()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
