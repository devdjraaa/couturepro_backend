<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitrineEvenement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vitrine_evenements';

    protected $fillable = ['atelier_id', 'type'];
}
