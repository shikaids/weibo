<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 生成用户激活令牌
     *
     * boot 方法会在用户模型类完成初始化之后进行加载，因此我们对事件的监听需要放在该方法中。
     * parent::boot() 继承父类的boot方法。
     * creating 事件
     *
     * 功能：用户的激活令牌需要在用户创建（注册）之前就先生成好，
     * 这样当用户注册成功之后我们才可以将令牌附带到注册链接上，并通过邮件的形式发送给用户。
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->activation_token = Str::random(10);
        });
    }

    /**
     * Gratatar头像
     * @param  string $value 默认尺寸值100
     * @return string        头像链接
     *
     * $this->attributes是模型属性的数组。
     * 逻辑应该是读取users数据表的记录赋值给$this->attributes数组，然后通过读取数组记录来获取user属性值。
     * 那么$this->attributes是在哪里实现的？
     */
    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email'])));
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }

    // 指明一个用户拥有多条微博
    public function statuses()
    {
        return $this->hasMany(Status::class);
    }

    /**
     * 将当前用户发布过的所有微博从数据库中取出，并根据创建时间来倒序排序
     * @return array 按时间倒序排序的微博列表
     *
     * 为什么在模型类内创建？而不是在用户控制器类创建？
     * 因为可以被其他控制器调用，例如首页控制器。
     * 如果是UsersController写feed()方法，那么就要以UsersController注入StaticPagesController，
     * 但是UsersController是控制器，所以这样不合适
     *
     * 最后的方法就是在User模型建立要使用的数据，然后被其他控制器调用
     */
    public function feed()
    {
        // 这是简单返回当前用户所发布的微博
        //return $this->statuses()->orderBy('created_at', 'desc');

        // 显示所有关注用户的、自己的微博
        //
        // 获取关注用户的数据
        //
        // 通过 followings 方法取出所有关注用户的信息，再借助 pluck 方法将 id 进行分离并赋值给 user_ids
        $user_ids = $this->followings->pluck('id')->toArray();
        // 将当前用户的 id 加入到 user_ids 数组中
        array_push($user_ids, $this->id);
        // 使用 Laravel 提供的 查询构造器 whereIn 方法取出所有用户的微博动态并进行倒序排序。
        //  Eloquent 关联的 预加载 with 方法，预加载避免了 N+1 查找的问题，
        //  大大提高了查询效率。N+1 问题 的例子可以阅读此文档 Eloquent 模型关系预加载。
        return Status::whereIn('user_id', $user_ids)->with('user')->orderBy('created_at', 'desc');
    }


    /**
     * 以下代码之所以难以理解，是因为用户模型与用户模型的多对多关系，也就是user_user。
     * 所以把建立belongsToMany关联都写在一个模型中了，而关联表则是followers。
     *
     * 如果以用户模型users与用户角色模型roles建立多对多关系统，那么关联表就是role_user
     * 具体实现情况官方文档关于Eloquent关联里有说明，比较容易理解。
     *
     * 但是user_user的关联，必须把belongsToMany写在同一个模型中，所以如果不理解多对多的实现，
     * 就很难理解为什么这样写。
     *
     * 我认为，这个教程最大败笔是把user_user的关联表命名为followers。
     * 因为本身就是用户关注用户的，本模型的外键名是user_id, 另一个模型的外键名是follower_id，关注者id；
     * 所以表名应该尽量区别两个模型，有助于理解模型users，与user_follower表的区别，直接用follower，
     * 让人以为这是两个一个模型的表，而不是关联表，而且在文案写的是粉丝关系表，表述有问题，关联表就是关联表，
     * 用“粉丝关系表”来表述，让人就是一个模型表。
     *
     */

    /**
     * 一个用户拥有多个粉丝。获取粉丝列表，也就是被关注的列表。
     *
     * 1 在 Laravel 中会默认将两个关联模型的名称进行合并，并按照字母排序，因此我们生成的关联关系表名称会是 user_user。
     * 2 我们也可以自定义生成的名称，把关联表名改为 followers。
     * 所以，belongsToMany的第二个参数就是自定义关联表的名称。
     *
     * 除了自定义合并数据表的名称，我们也可以通过传递额外参数至 belongsToMany 方法来自定义数据表里的字段名称。
     * 1 第三个参数是定义此关联的模型在连接表里的外键名
     * 2 第四个参数是另一个模型在连接表里的外键名
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    /**
     * 一个用户关注多个人。获取用户关注人的列表。
     *
     * 建立关联，获取所有关注人列表，返回一个关注人列表的数据库构建起，
     * 可以通过get(), paginate()等方法获取数据
     */
    public function followings()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    /**
     * Eloquent 模型为多对多提供的一系列简便的方法。
     *
     * attach 方法或 sync 方法在中间表上创建一个多对多记录；
     * 使用 detach 方法在中间表上移除一个记录；
     * 创建和移除操作并不会影响到两个模型各自的数据，所有的数据变动都在 中间表 上进行。
     * attach, sync, detach 这几个方法都允许传入 id 数组参数。
     * allRelatedIds 是 Eloquent 关联关系提供的 API，用来获取关联模型的 ID 集合
     */
    /**
     * sync 方法会接收两个参数，第一个参数为要进行添加的 id，
     * 第二个参数则指明是否要移除其它不包含在关联的 id 数组中的 id，true 表示移除，false 表示不移除，
     * 默认值为 true。
     * 由于我们在关注一个新用户的时候，仍然要保持之前已关注用户的关注关系，因此不能对其进行移除，
     * 所以在这里我们选用 false.
     */
    public function follow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->sync($user_ids, false);
    }

    public function unfollow($user_ids)
    {
        if (!is_array($user_ids)) {
            $user_ids = compact('user_ids');
        }
        $this->followings()->detach($user_ids);
    }

    /**
     * 我们还需要一个方法用于判断当前登录的用户 A 是否关注了用户 B，
     * 代码实现逻辑很简单，我们只需判断用户 B 是否包含在用户 A 的关注人列表上即可。
     *
     * $this->followings是Laravel中动态属性的应用。
     * 这是动态属性最简单的应用。
     *
     * $this->followings的写法实质就是调用类的属性。
     * 但是在User类中没有followings属性，也就是调用的属性不存在，
     * 但存在同名的方法时，则会调用同名的方法，返回的类型是collection类型（Eloquent的集合）。
     *
     * 而$this->followings()一个Relations，也就是数据库请求构建器，所以可以使用：
     * $this->followings()->get()来获取数据集合。
     * $this->followings == $this->followings()-get()
     *
     * 单理解为 followings 返回的是数据集合，而 followings() 返回的是数据库查询语句。
     *
     * 扩展理解，基本上Laravel关于模型Eloquent都可以这样理解。知识点如下：
     * 1 动态属性知识点；
     * 2 动态属性返回的类型collection类型（Eloquent的集合）；
     * 3 建立模型关联的方法返回的是一个Relations，也就是数据库请求构建器；
     *
     *
     */
    public function isFollowing($user_id)
    {
        return $this->followings->contains($user_id);
    }
}

/**
 * 模型建立关联的函数：hasOne()，belongsTo(), hasMany(), belongsToMany()等建立关联的函数，
 * 会读取所有关联的数据，返回一个关联数据的数据库构建器，通过数据库操作方法对数据进行操作处理。
 *
 * Auth::user()->followings 的用法。我们在 User 模型里定义了关联方法 followings()，
 * 关联关系定义好后，我们就可以通过访问 followings 属性直接获取到关注用户的 集合。
 * 这是 Laravel Eloquent 提供的「动态属性」属性功能，我们可以像在访问模型中定义的属性一样，来访问所有的关联方法。
 *
 * 还有一点需要注意的是 $user->followings 与 $user->followings() 调用时返回的数据是不一样的，
 * $user->followings 返回的是 Eloquent：集合 。而 $user->followings() 返回的是 数据库请求构建器 。
 *
 */
