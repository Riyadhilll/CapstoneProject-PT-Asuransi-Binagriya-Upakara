<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('layanan', timestamps: false)]
#[Fillable([
    'konsumen_id', 'kanal_id', 'kategori_id', 'jenis', 'bentuk', 'uraian',
    'diterima_pada', 'tgl_dokumen_lengkap', 'selesai_saat_diterima',
    'status_kode', 'divisi_tujuan_id', 'pic_id', 'dicatat_oleh',
    'hasil', 'metode_penyampaian',
])]
class Layanan extends Model
{

    protected function casts(): array
    {
        return [
            'diterima_pada' => 'datetime',
            'tgl_dokumen_lengkap' => 'date',
            'mulai_hitung' => 'date',
            'tenggat' => 'date',
            'selesai_pada' => 'datetime',
            'selesai_saat_diterima' => 'boolean',
        ];
    }

    public function konsumen(): BelongsTo { return $this->belongsTo(Konsumen::class); }
    public function kanal(): BelongsTo { return $this->belongsTo(Kanal::class); }
    public function kategori(): BelongsTo { return $this->belongsTo(Kategori::class); }
    public function status(): BelongsTo { return $this->belongsTo(Status::class, 'status_kode', 'kode'); }
    public function divisiTujuan(): BelongsTo { return $this->belongsTo(Divisi::class, 'divisi_tujuan_id'); }
    public function pic(): BelongsTo { return $this->belongsTo(Pengguna::class, 'pic_id'); }
    public function pencatat(): BelongsTo { return $this->belongsTo(Pengguna::class, 'dicatat_oleh'); }
    public function riwayat(): HasMany { return $this->hasMany(RiwayatLayanan::class)->orderBy('pada'); }
    public function perpanjangan(): HasMany { return $this->hasMany(Perpanjangan::class); }

    public function tepatWaktu(): ?bool
    {
        return match (true) {
            $this->status_kode !== 'SELESAI' => null,
            $this->selesai_saat_diterima => true,
            $this->tenggat === null => null,
            default => $this->selesai_pada->toDateString() <= $this->tenggat->toDateString(),
        };
    }
}
