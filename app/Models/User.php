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

    // 获取用户发布过的所有微博，并按创建时间的倒序排序。
    public function feed()
    {
        return $this->statuses()->orderBy('created_at', 'desc');
    }
}
