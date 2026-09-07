<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧を表示
     *
     * @return View|Factory
     */
    public function index(): View
    {
        $user = User::all();

        $now = Carbon::now();

        $formattedDate = $now->format('Y年n月j日');

        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }
}
