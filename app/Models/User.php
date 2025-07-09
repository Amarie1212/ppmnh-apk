<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public $timestamps = false; // Benar, jika tidak ingin created_at/updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',      // GANTI dari 'name' ke 'full_name'
        'email',
        'password',
        'jenis_kelamin',
        'no_hp',
        'no_hp_ortu',
        'tempat_lahir',
        'tanggal_lahir',
        'asal_daerah',
        'asal_desa',
        'asal_kelompok',
        'kelas',
        'role',
        "boleh_tambah_absen",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime', // Optional, jika tidak dipakai bisa dihapus
            // 'password' => 'hashed', // Ini khusus Laravel 10+, boleh dihapus jika error
            'boleh_tambah_absen' => 'boolean',
        ];
    }
}
