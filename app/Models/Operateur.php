<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operateur extends Model
{
    use HasFactory;
    protected $fillable = ['nom', 'code', 'image'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
