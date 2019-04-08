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
        // 数据验证。当发生错误产生错误消息$errors变量存储在session中，$errors可以在视图调用。
        // 并且，如果验证后产生数据错误，页面依然跳转回到users.create路由页面。
        // 因为validate()验证到错误，会返回到填充提交的旧页面。
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        // User::create()方法创建成功后并返回一个用户对象，并包含新注册用户的所有信息。
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // 建立success键的内容，通过session()->get('success')获取
        session()->flash('success', '欢迎，您将在这里开启一段新的旅程');

        // 注册成功跳转页面，users.show用户展示页面。
        // 通过redirect()->route()的路由函数绑定内容到视图。那么，redirect()->route()方法就有两个作用。
        // 1. 建立跳转路由；2. 把数据绑定到用户展示视图。
        return redirect()->route('users.show', [$user]);
    }
}
