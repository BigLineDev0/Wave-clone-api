<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Wallet;
use App\Models\Favorite;
use App\Models\scheduledTransfers;
use App\Enums\UserRole;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'merchant_code',
        'email',
        'role',
        'password',

    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // definir le nom complet de l'utilisateur en combinant le prénom et le nom
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }


    // un utilisateur a un  portefeuille
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Crée un portefeuille automatiquement pour l'utilisateur après sa création
    protected static function booted()
    {
        static::created(function ($user) {

            $user->wallet()->create([
                'balance' => 0,
                'plafond' => 500000,
                'status' => 'active'
            ]);

            if ($user->role === UserRole::MERCHANT) {
                $user->update(['merchant_code' => 'MCH-' . str_pad(
                        $user->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                ]);
            }

        });
    }

    /**
    * Un utilisateur possède plusieurs favoris.
    */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
    * Un utilisateur peut planifier plusieurs transfers.
    */
    public function scheduledTransfers()
    {
        return $this->hasMany(ScheduledTransfer::class);
    }
}
