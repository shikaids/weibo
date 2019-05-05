<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
    public function __construct()
    {
        // $this->middleware()方法是使用中间件
        // 如果在中间件系统中写中间件，是否就是使用middleware来使用中间件
        // index 是允许游客访问
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);
    }

    public function index()
    {
        // User::all 获取用户模型的全部记录
        // compact函数建立一个数组，包括变量名和它们的值
        //
        // index 方法中，我们使用 Eloquent
        // 用户模型将所有用户的数据一下子完全取出来了，
        // 这么做会影响应用的性能，后面我们再来对该代码进行优化，通过分页的方式来读取用户数据。
        //
        // 在将用户数据取出之后，与 index 视图进行绑定，这样便可以在视图中使用 $users 来访问所有用户实例
        //$users = User::all();
        //
        //
        /**
         * 默认状况下，页面的当前页数由 HTTP 请求所带的 page 参数决定，
         * 当你访问 http://weibo.test/users?page=2链接时，
         * 获取的是第二页的用户列表信息，
         * Laravel 会自动检测到 page 的值并插入由分页器生成的链接中。
         */
        // paginate方法指定每页生成的数据数量为 10 条，即当我们有 50 个用户时，用户列表将被分为五页进行展示
        $users = User::paginate(10);
        /**
         * paginate方法与render方法一般是配合使用。
         * 调用 paginate 方法获取用户列表之后，然后通过 {!! $users->render() !!} 在用户列表页上渲染分页链接。
         * {!! $users->render() !!} 是用在视图模板的。
         *
         * 由 render 方法生成的 HTML 代码默认会使用 Bootstrap 框架的样式，
         * 渲染出来的视图链接也都统一会带上 ?page 参数来设置指定页数的链接。
         * 另外还需要注意的一点是，渲染分页视图的代码必须使用 {!! !!} 语法，
         * 而不是 {{　}}，这样生成 HTML 链接才不会被转义。
         */
        return view('users.index', compact('users'));
    }

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
        // User::create()方法的源代码在哪里？
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        /**
         * 没有邮件验证功能的代码
         */
        // 用户注册后自动登录
        //Auth::login($user);

        // 建立success键的内容，通过session()->get('success')获取
        //session()->flash('success', '欢迎，您将在这里开启一段新的旅程');

        // 注册成功跳转页面，users.show用户展示页面。
        // 通过redirect()->route()的路由函数绑定内容到视图。那么，redirect()->route()方法就有两个作用。
        // 1. 建立跳转路由；2. 把数据绑定到用户展示视图。
        //return redirect()->route('users.show', [$user]);

        /**
         * 邮件验证功能的代码
         */
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);

        $data = [];
        $date['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        //这个update方法在哪里定义？
        $user->update($data);

        session()->flash('success', '个人资料更新成功');

        return redirect()->route('users.show', $user->id);
    }

    // 首先会根据路由发送过来的用户 id 进行数据查找
    // 查找到指定用户之后再调用 Eloquent 模型提供的 delete 方法对用户资源进行删除，
    // 成功删除后在页面顶部进行消息提示。
    // 最后将用户重定向到上一次进行删除操作的页面，即用户列表页。
    public function destroy(User $user)
    {
        // authorize 方法来对删除操作进行授权验证
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户');
        return back();
    }

    public function confirmEmail($token) {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功');
        //为什么使用数组？[$user]
        return redirect()->route('users.show', [$user]);
    }

    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = 'summer@example.com';
        $name = 'summer';
        $to   = $user->email;
        $subject = "感谢注册 Weibo 应用！ 请确认你的邮箱";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }
}
