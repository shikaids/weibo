<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function create()
    {
        return view('users.create');
    }

    // compace 建立一个数字，包括变量名和它们的值
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    // 1. validator 由 App\Http\Controllers\Controller类中的ValidatesRequests进行定义，
    //    因此我们可以在所有的控制器中使用 validate 方法来进行数据验证。注意：是所有的控制器。
    // 2. validate 接收两个参数，第一个参数为用户的输入数据；第二个参数为该数据的验证规则。
    // 3. required 要验证数据是否为空。
    // 4. min|max  限定最小长度和最大长度。
    // 5、email     邮件格式
    // 6、unique    唯一性验证
    // 7、confirmed  密码匹配验证
    public function store(Request $request)
    {
        //数据验证。当发生错误，$errors可以在视图调用
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        //
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        //
        session()->flash('success', '欢迎，您将在这里开启一段新的旅程');
        return redirect()->route('users.show', [$user]);
    }
}
