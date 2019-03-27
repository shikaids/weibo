<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App/Models/User;

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
}
