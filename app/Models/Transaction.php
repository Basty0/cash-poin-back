<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'operateur_id',
        'tel',
        'type',
        'montant',
        'commission',
        'date_transaction',
        'statut'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operateur()
    {
        return $this->belongsTo(Operateur::class);
    }
}
