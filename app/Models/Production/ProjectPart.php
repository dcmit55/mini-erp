<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use App\Models\Production\Project;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProjectPart extends Model implements AuditableContract
{
    use \OwenIt\Auditing\Auditable;

    protected $fillable = ['project_id', 'part_name'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
