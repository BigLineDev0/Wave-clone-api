<?php

namespace App\Models;

use App\Enums\ScheduledTransferFrequency;
use App\Enums\ScheduledTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_phone',
        'amount',
        'frequency',
        'next_run_at',
        'status',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'next_run_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'frequency' => ScheduledTransferFrequency::class,
            'status' => ScheduledTransferStatus::class,
        ];
    }

    /**
     * Une planification appartient à un utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
