<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginToken;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view("auth.login");
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            "email" => "required|email",
        ]);
        $user = User::whereEmail($data["email"])->first();
        if (!$user) {
            $user = User::create([
                "email" => $data["email"],
                "name" => explode("@", $data["email"])[0],
            ]);
        }
        $user->sendLoginLink();
        session()->flash("success", true);
        return redirect()->back();
    }

    public function verifyLogin(Request $request, $token)
    {
        $token = LoginToken::whereToken(hash("sha256", $token))->firstOrFail();
        abort_unless($request->hasValidSignature() && $token->isValid(), 401);
        $token->consume();
        Auth::login($token->user);
        return redirect("/");
    }

    public function logout()
    {
        logger("Logging out");
        Auth::guard("user")->logout();
        session()->invalidate();
        return redirect(route("login"));
    }
}
