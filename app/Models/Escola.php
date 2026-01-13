<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Escola extends Model
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $table = 'escolas';

    protected $fillable = [
        'codigo',
        'nome',
        'telefone',
        'email',
    ];

    public function casts(): array
    {
        return [
            'codigo' => 'string',
            'nome' => 'string',
            'telefone' => 'string',
            'email' => 'string',
        ];
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_escola');
    }
}
