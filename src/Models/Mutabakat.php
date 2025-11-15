<?php

namespace Visiosoft\Mutabakat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mutabakat extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'mutabakat';

    protected $fillable = [
        'park_id',
        'row_hash',
        'provision_date',
        'company',
        'parking_name',
        'transaction_name',
        'transaction_count',
        'total_amount',
        'commission_amount',
        'net_transfer_amount',
        'payment_date',
        'status',
    ];

    protected $casts = [
        'provision_date' => 'date',
        'payment_date' => 'date',
        'transaction_count' => 'integer',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_transfer_amount' => 'decimal:2',
    ];
}
