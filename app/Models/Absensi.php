<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi'; // Pastikan sama dengan nama table di DB

    protected $fillable = [
        'user_id',
        'tanggal',
        'kelas',
        'kegiatan',
        'status',
        'keterangan',
         'kategori_absensi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
