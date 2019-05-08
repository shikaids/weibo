<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Status;
use Auth;

class StatusesController extends Controller
{
    //
    public function __contruct()
    {
        $this->middleware('auth');
    }

    /**
     *
     * user的id是怎么传递到user_id的？
     * 因为user模型的users表中id是user id，
     * Statuses表的user_id是存储user id的，
     * 但是怎么传递过去的？
     * 难道是约定俗成的，users的单数就是user，所以其他表关联就是user_id，
     * 如果这个猜想是对的，那么statuses的id关联到其他表的字段就是status_id
     */
    public function store(Request $request)
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

    public function destroy(Status $status)
    {
        $this->authorize('destroy', $status);
        $status->delete();
        session()->flash('success', '微博已被删除');
        return redirect()->back();
    }
}
