<?php

namespace App\Http\Controllers\Qc;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QcViewController extends Controller
{
    public function mascot(Request $request)
    {
        $user = $request->user();

        return view('qc.mascot', [
            'authUser' => [
                'id'   => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function costume(Request $request)
    {
        $user = $request->user();

        return view('qc.costume', [
            'authUser' => [
                'id'   => $user->id,
                'name' => $user->name,
            ],
        ]);
    }
}
