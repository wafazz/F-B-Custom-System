<?php

namespace App\Models;

use App\Services\Mail\BrevoMailer;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'date_of_birth',
        'gender',
        'photo',
        'address_line',
        'city',
        'postcode',
        'state',
        'referral_code',
        'referred_by',
        'preferred_branch_id',
        'marketing_consent',
        'whatsapp_consent',
        'push_consent',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'marketing_consent' => 'boolean',
            'whatsapp_consent' => 'boolean',
            'push_consent' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! $user->referral_code) {
                $user->referral_code = static::generateReferralCode();
            }
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_staff')
            ->using(BranchStaff::class)
            ->withPivot(['pin', 'employment_type', 'hired_at', 'ended_at', 'is_active', 'is_primary'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Product, $this> */
    public function favouriteProducts(): BelongsToMany
    {
        // Pivot has only created_at (no updated_at), so withTimestamps()
        // would write a column that doesn't exist. We let MySQL default
        // the created_at via useCurrent() on insert.
        return $this->belongsToMany(Product::class, 'user_favourites')
            ->withPivot('created_at')
            ->orderByDesc('user_favourites.created_at');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'hq_admin',
            'ops_manager',
            'mkt_manager',
            'branch_manager',
            'cashier',
            'barista',
        ]);
    }

    /**
     * Send the password reset link via Brevo (HTTP API) instead of the default
     * Laravel Notification mail driver. Falls back to logging if Brevo isn't
     * configured so the flow doesn't crash in dev.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        $mailer = app(BrevoMailer::class);
        if (! $mailer->isConfigured()) {
            Log::warning('Password reset email not sent — Brevo not configured.', [
                'user_id' => $this->getKey(),
                'url' => $url,
            ]);

            return;
        }

        $minutes = (int) config('auth.passwords.users.expire', 60);
        $html = view('emails.password-reset', [
            'name' => $this->name,
            'url' => $url,
            'minutes' => $minutes,
        ])->render();

        $mailer->send(
            $this->getEmailForPasswordReset(),
            'Reset your Star Coffee password',
            $html,
            "Reset your password: {$url}\n\nThis link expires in {$minutes} minutes.",
        );
    }
}
