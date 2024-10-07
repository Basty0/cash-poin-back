<?php

namespace App\Http\Controllers;

use App\Models\Operateur;
use Illuminate\Http\Request;

class OperateurController extends Controller
{
    public function index()
    {
        try {
            $operateurs = Operateur::all(); // Récupérer tous les opérateurs
            return response()->json($operateurs, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des opérateurs'], 500);
        }
    }
}
