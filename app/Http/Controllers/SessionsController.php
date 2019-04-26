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
    public function __construct()
    {
        // 只让未登录用户访问注册页面
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store']
        ]);

        // 只让未登录用户访问登录页面
        $this->middleware('guest', [
            'only' => ['create'];
        ]);
    }

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
        // $request->has('remember') 是响应name为remember的input的form
        if (Auth::attempt($credentials, $request->has('remember'))) {
            // 登录成功后的相关操作
            session()->flash('success', '欢迎回来');
            $fallback = route('users.show', Auth::user());
            //return redirect()->route('users.show', [Auth::user()]);
            //intended方法，该方法可将页面重定向到上一次请求尝试访问的页面上，并接收一个默认跳转地址参数，
            //当上一次请求记录为空时，跳转到默认地址上。
            //$fallback是默认地址
            return redirect()->intended($fallback);
        } else {
            // 登录失败后的相关操作
            session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
            return redirect()->back()->withInput();
        }

        return;
    }

    public function detroy()
    {
        Auth::logout();
        session()->flash('success', '您已成功退出');
        return redirect(login);
    }
}
