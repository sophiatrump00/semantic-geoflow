<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminWeb;
use Illuminate\View\View;

class CommerceOpsController extends Controller
{
    public function index(): View
    {
        return view('admin.commerce-ops.index', [
            'adminSiteName' => AdminWeb::siteName(),
            'pageTitle' => __('admin.commerce_ops.page_title'),
            'activeMenu' => 'commerce_ops',
        ]);
    }
}
