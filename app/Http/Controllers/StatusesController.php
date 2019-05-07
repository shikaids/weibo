<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatusesController extends Controller
{
    //
    public function __contruct()
    {
        $this->middleware('auth');
    }

    public function store()
    {
        $this->validate($request, [
            'content' => 'required|max:140'
        ]);

        Auth::user()->statuses()->create([
            'content' => $request['content']
        ]);

        session()->flash('success', '发布成功！');
        return redirect()->back();
    }
}
