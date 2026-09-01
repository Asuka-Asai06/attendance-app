<?php

namespace App\Http\Controllers\User;

use App\Actions\AttendanceAction;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceAction $attendanceAction
    ) {}

    /**
     * 勤怠登録画面を表示
     */
    public function index(): View
    {
        $user = Auth::user();

        $now = Carbon::now();

        $formattedDate = $now->format('Y年n月j日');

        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 勤怠の操作を処理する。
     */
    public function store(Request $request): RedirectResponse
    {
        $action = $request->input('action');

        $user = $request->user();

        match ($action) {
            'clock_in' => $this->attendanceAction->clockIn($user),
            'clock_out' => $this->attendanceAction->clockOut($user),
            'break_in' => $this->attendanceAction->breakIn($user),
            'break_out' => $this->attendanceAction->breakOut($user),
            default => abort(400),
        };

        return redirect()->route('attendance.index');
    }
}
