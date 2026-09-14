<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    /**
     * 勤怠レポートを表示する。
     *
     * @param  Request  $request  ログインユーザーを取得
     * @param  ReportService  $reportService  勤怠レポート集計サービス
     * @return View 勤怠レポート画面
     */
    public function index(Request $request, ReportService $reportService): View
    {
        $data = $reportService->getReport(
            $request->user()
        );

        return view('reports.index', $data);
    }
}
