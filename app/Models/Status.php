<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    /**
     * 不用写代码关联数据表的吗？
     *
     * 除非明确地指定了其它名称，否则将使用类的复数形式「蛇形命名」来作为表名。
     * 例如 User模型,表名成就是Users。
     *
     * 但是，Status的复数是Statuses，不是单纯加s为复数。
     * 所以模型名称是Status，那么表名称是Statuss还是Statuses
     * 如果是Statuss,那么在教程文档建立Statuses表是对的吗？
     * 还是系统会判断s结尾，所以是使用Statuses命名的表
     */

    // 指明一条微博属于一个用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
