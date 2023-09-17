<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index(){
        if (Auth::guard('admin')->check()){
            return redirect('admin');
        }
        return view('Admin.auth.login');
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'email'   =>'required|exists:admins',
            'password'=>'required'
        ]);
        if (Auth::guard('admin')->attempt($data)){
            return response()->json(200);
        }
        return response()->json(405);
    }

    public function logout(){
        Auth::guard('admin')->logout();
        toastr()->info('logged out successfully');
        return redirect('admin/login');
    }
}
