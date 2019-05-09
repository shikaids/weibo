<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class FollowersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 获取全部用户
        $users = User::all();
        // 获取第一个用户
        $user  = $users->first();
        // 第一个用的id
        $user_id = $user->id;

        /**获取去除掉 ID 为 1 的所有用户 ID 数组*/
        // 获取从第2个元素起到结束的数组。slice的第一个参数表明是从哪里起。
        $followers = $users->slice(1);
        $follower_ids = $followers->pluck('id')->toArray();

        // 关注除了1号用户以外的用户
        $user->follow($follower_ids);

        // 除了 1 号用户以外的所有用户都来关注 1 号用户
        foreach ($followers as $follower) {
            $follower->follow($user_id);
        }
    }
}
