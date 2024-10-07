<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Connecter l'utilisateur
        Auth::login($user);

        // Créer un jeton d'authentification
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
    public function getAuthenticatedUser()
    {
        $user = auth()->user();

        if ($user) {
            return response()->json($user, 200);
        } else {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
    }


    public function getDailyTransactions(Request $request)
    {
        // Validation des paramètres (facultatif)


        $year = $request->input('2024');

        // Récupérer les transactions groupées par jour pour chaque mois de l'année spécifiée
        $transactions = Transaction::selectRaw('DATE(created_at) as date, MONTH(created_at) as month, YEAR(created_at) as year, SUM(montant) as total_amount, SUM(commission) as total_commission, COUNT(id) as total_transactions')
            ->whereYear('created_at', $year)
            ->groupBy('date')
            ->orderBy('month')
            ->orderBy('date')
            ->get();

        return response()->json($transactions);
    }

}
