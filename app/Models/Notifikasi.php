<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $fillable = ['user_id', 'nota_id', 'pesan', 'dibaca'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function tandaiDibaca(): void
    {
        $this->update(['dibaca' => true]);
    }
}
