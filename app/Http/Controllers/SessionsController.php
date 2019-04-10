<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * 导入Auth，是Auth的facade服务。
 *
 * 因为config/app.php的设置，
 * use Auth;
 * 等价于
 * use Illuminate\Support\Facades\Auth;
 */
use Auth;

class SessionsController extends Controller
{
    public function create()
    {
        return view('sessions.create');
    }

    public function store(Request $request)
    {
        $credentials =$this->validate($request, [
            'email' => 'required|email|max:225',
            'password' => 'required'
        ]);

        // Auth::attempt(): Attempt to authenticate a user using the given credentials
        // Auth::user(): Get the currently authenticated user
        // 上面两个方面都是SessionGuard的方法
        // Illuminate\Auth\SessionGuard::attempt()
        // Illuminate\Auth\SessionGuard::user()
        if (Auth::attempt($credentials)) {
            // 登录成功后的相关操作
            session()->flash('success', '欢迎回来');
            return redirect()->route('users.show', [Auth::user()]);
        } else {
            // 登录失败后的相关操作
            session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
            return redirect()->back()->withInput();
        }

        return;
    }
}
