<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogStatus extends Model
{
    protected $table = 'log_statuses';

    protected $fillable = ['nota_id', 'diubah_oleh', 'status_lama', 'status_baru', 'catatan'];

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }

    public function pengubah()
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
