<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProjectExport implements FromView
{
    protected $projects;

    public function __construct($projects)
    {
        $this->projects = $projects;
    }

    public function view(): View
    {
        return view('production.projects.export', [
            'projects' => $this->projects
        ]);
    }
}