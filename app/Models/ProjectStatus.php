<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    protected $fillable = ['name'];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function badgeClass()
    {
        // Daftar kelas warna Bootstrap dan custom (sama seperti InventoryController)
        $colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-dark', 'bg-secondary', 'bg-purple', 'bg-indigo', 'bg-pink', 'bg-orange', 'bg-teal', 'bg-cyan', 'bg-lime', 'bg-amber', 'bg-rose', 'bg-emerald', 'bg-violet', 'bg-sky'];
        $hash = crc32(strtolower(trim($this->name)));
        $colorIndex = abs($hash) % count($colors);
        return $colors[$colorIndex];
    }
}
