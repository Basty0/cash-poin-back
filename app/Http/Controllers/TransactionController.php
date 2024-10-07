<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{


    public function getMonthlyTransactions($year)
    {
        $monthlyTransactions = Transaction::whereYear('created_at', $year)
            ->with('operateur')
            ->selectRaw('MONTHNAME(created_at) as month,MONTH(created_at) as month_number,SUM(montant) as total_montant,SUM(commission) as total_commission,COUNT(*) as total_transactions,SUM(CASE WHEN type = "dépôt" THEN montant ELSE 0 END) as total_depot,SUM(CASE WHEN type = "retrait" THEN montant ELSE 0 END) as total_retrait,SUM(CASE WHEN type = "dépôt" THEN commission ELSE 0 END) as total_commission_depot,SUM(CASE WHEN type = "retrait" THEN commission ELSE 0 END) as total_commission_retrait,COUNT(CASE WHEN type = "dépôt" THEN 1 END) as total_depot_count,COUNT(CASE WHEN type = "retrait" THEN 1 END) as total_retrait_count')
            ->groupBy('month', 'month_number')
            ->get();

        return response()->json($monthlyTransactions);
    }



    public function getTransactionsByMonthAndYear($month, $year)
    {
        $transactions = Transaction::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->with('operateur')
            ->get();

        return response()->json($transactions);
    }

    public function exportTransactions(Request $request)
    {    // Validate the request parameters
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|digits:4',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');

        return Excel::download(new TransactionsExport($month, $year), 'transactions.xlsx');
    }


    public function getTransactionsByDateAndUser(Request $request)
    {
        $userId = auth()->user()->id; // Récupérer l'ID de l'utilisateur authentifié
        $date = $request->input('date');
        $operateur = $request->input('selectedOperateur'); // Récupérer l'opérateur envoyé
        $type = $request->input('selectedType'); // Récupérer le type de transaction envoyé



        // 1. Si les deux paramètres 'operateur' et 'type' sont fournis
        if ($operateur && $type) {
            $transactions = Transaction::whereDate('created_at', $date)
                ->where('user_id', $userId)
                ->where('operateur_id', $operateur)
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->with('operateur')
                ->get();
        }
        // 2. Si seul 'operateur' est fourni
        elseif ($operateur && !$type) {
            $transactions = Transaction::whereDate('created_at', $date)
                ->where('user_id', $userId)
                ->where('operateur_id', $operateur)
                ->orderBy('created_at', 'desc')
                ->with('operateur')
                ->get();
        }
        // 3. Si seul 'type' est fourni
        elseif ($type && !$operateur) {
            $transactions = Transaction::whereDate('created_at', $date)
                ->where('user_id', $userId)
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->with('operateur')
                ->get();
        }
        // 4. Si aucun des deux paramètres n'est fourni
        else {
            $transactions = Transaction::whereDate('created_at', $date)
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->with('operateur')
                ->get();
        }

        return response()->json($transactions);
    }




    public function createTransaction(Request $request)
    {
        $request->validate([
            'operateur_id' => 'required|exists:operateurs,id',
            'type' => 'required|in:dépôt,retrait',
            'montant' => 'required|numeric',
            'commission' => 'nullable|numeric',
            'tel' => 'required',
        ]);

        $userId = auth()->user()->id; // Get the authenticated user's ID

        // Create a new Transaction instance using the 'new' keyword
        $transaction = new Transaction([
            'user_id' => $userId, // Set user_id to the authenticated user's ID
            'operateur_id' => $request->operateur_id,
            'type' => $request->type,
            'montant' => $request->montant,
            'commission' => $request->commission,
            'tel' => $request->tel,
        ]);

        // Save the new transaction to the database
        $transaction->save();
        return response()->json($transaction, 201);
    }

    public function updateTransaction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'operateur_id' => 'sometimes|exists:operateurs,id',
            'type' => 'sometimes|in:dépôt,retrait',
            'montant' => 'sometimes|numeric',
            'commission' => 'nullable|numeric',
            'tel' => 'nullable',
            'statut' => 'sometimes|in:réussi,échoué,en attente',
        ]);

        $userId = auth()->user()->id; // Get the authenticated user's ID

        // Check if the user is authorized to update this transaction
        if ($transaction->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction->update($request->all());

        return response()->json($transaction);
    }


    public function getTransactionsGroupedByOperator(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);
        $userId = auth()->user()->id; // Get the authenticated user's ID

        $transactions = Transaction::whereDate('created_at', $request->date)
            ->where('user_id', $userId) // Filter by user ID
            ->with('operateur')
            ->get()
            ->groupBy('operateur_id');

        return response()->json($transactions);
    }

    public function getTransactionsSummaryByType(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);
        $userId = auth()->user()->id; // Get the authenticated user's ID

        $summary = Transaction::whereDate('created_at', $request->date)
            ->where('user_id', $userId) // Filter by user ID
            ->selectRaw('type, SUM(montant) as total_montant, SUM(commission) as total_commission, COUNT(*) as total_transactions')
            ->groupBy('type')
            ->get();

        return response()->json($summary);
    }

    public function getGeneralTransactionSummary(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);
        $userId = auth()->user()->id; // Get the authenticated user's ID
        $summary = Transaction::whereDate('created_at', $request->date)
            ->where('user_id', $userId) // Filter by user ID
            ->selectRaw('type, COUNT(*) as total_transactions, SUM(montant) as total_montant')
            ->groupBy('type')
            ->get();

        return response()->json($summary);
    }


    public function getOperatorTransactionReport(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);
        $date = $request->input('date');
        $userId = auth()->user()->id; // Get the authenticated user's ID
        $report = Transaction::whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->with('operateur')
            ->selectRaw('operateur_id, COUNT(*) as total_transactions, SUM(montant) as total_montant')
            ->groupBy('operateur_id')
            ->get();
        // $trans = Transaction::whereDate('created_at', $date)->where('user_id', $userId)->get();


        return response()->json($report);
    }


    public function getTransactionsByUserTypeAndDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:dépôt,retrait',
        ]);
        $userId = auth()->user()->id; // Get the authenticated user's ID
        $transactions = Transaction::whereDate('created_at', $request->date)
            ->where('user_id', $userId) // Filter by user ID
            ->where('type', $request->type)
            ->get();

        return response()->json($transactions);
    }


    public function recapitulatifParOperateur(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $userId = auth()->user()->id; // Get the authenticated user's ID
        $date = $request->input('date');

        $recapitulatif = DB::table('transactions')
            ->select(
                'operateurs.nom as operateur_nom',
                DB::raw('COUNT(transactions.id) as nombre_transactions'),
                DB::raw('SUM(CASE WHEN transactions.type = "dépôt" THEN transactions.montant ELSE 0 END) as somme_depot'),
                DB::raw('SUM(CASE WHEN transactions.type = "retrait" THEN transactions.montant ELSE 0 END) as somme_retrait'),
                DB::raw('SUM(CASE WHEN transactions.type = "dépôt" THEN transactions.commission ELSE 0 END) as commission_depot'),
                DB::raw('SUM(CASE WHEN transactions.type = "retrait" THEN transactions.commission ELSE 0 END) as commission_retrait'),
                DB::raw('COUNT(CASE WHEN transactions.type = "dépôt" THEN 1 END) as nombre_depot'),
                DB::raw('COUNT(CASE WHEN transactions.type = "retrait" THEN 1 END) as nombre_retrait'),
                DB::raw('SUM(transactions.montant) as somme_totale')
            )
            ->join('operateurs', 'transactions.operateur_id', '=', 'operateurs.id')
            ->where('transactions.user_id', $userId) // Filter by user ID
            ->whereDate('transactions.created_at', $date)
            ->groupBy('transactions.operateur_id')
            ->get();

        return response()->json($recapitulatif);
    }

    public function searchByTel(Request $request)
    {
        $request->validate([
            'tel' => 'required|string',
        ]);

        $tel = $request->input('tel');
        $userId = auth()->user()->id; // Get the authenticated user's ID

        $transaction = Transaction::where('tel', $tel)
            ->where('user_id', $userId) // Filter by user ID
            ->with('operateur')->get();

        if ($transaction) {
            return response()->json($transaction, 200);
        } else {
            return response()->json(['message' => 'Transaction non trouvée'], 404);
        }
    }









    public function getDashboardSummary(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);
        $date = $request->input('date');
        $userId = auth()->user()->id; // Get the authenticated user's ID

        // Use the whereDate method to filter by the date part of the created_at column
        $totalTransactions = Transaction::whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->count();
        $totalAmount = Transaction::whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->sum('montant');
        $totalCommission = Transaction::whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->sum('commission');

        return response()->json([
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
            'total_commission' => $totalCommission,
        ], 200);
    }


    public function getTransactionDetails(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);
        $date = $request->input('date');
        $userId = auth()->user()->id; // Get the authenticated user's ID

        // Calculate details for deposits
        $depot = Transaction::where('type', 'dépôt')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->select(
                'operateur_id',
                DB::raw('COUNT(*) as number_of_transactions'),
                DB::raw('SUM(montant) as total_amount'),
                DB::raw('SUM(commission) as total_commission')
            )
            ->with('operateur')
            ->groupBy('operateur_id')
            ->get();

        // Calculate details for withdrawals
        $retrait = Transaction::where('type', 'retrait')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->select(
                'operateur_id',
                DB::raw('COUNT(*) as number_of_transactions'),
                DB::raw('SUM(montant) as total_amount'),
                DB::raw('SUM(commission) as total_commission')
            )->with('operateur')
            ->groupBy('operateur_id')
            ->get();

        // Calculate total deposit and withdrawal amounts
        $totalDepotAmount = Transaction::where('type', 'dépôt')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->sum('montant');
        $totalRetraitAmount = Transaction::where('type', 'retrait')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->sum('montant');

        // Calculate total deposit and withdrawal commissions
        $totalDepotCommission = Transaction::where('type', 'dépôt')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->sum('commission');
        $totalRetraitCommission = Transaction::where('type', 'retrait')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->sum('commission');

        // Calculate total deposit and withdrawal counts
        $totalDepotCount = Transaction::where('type', 'dépôt')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->count();
        $totalRetraitCount = Transaction::where('type', 'retrait')
            ->whereDate('created_at', $date)
            ->where('user_id', $userId) // Filter by user ID
            ->count();

        return response()->json([
            'depots' => $depot,
            'retraits' => $retrait,
            'total_depot_amount' => $totalDepotAmount,
            'total_retrait_amount' => $totalRetraitAmount,
            'total_depot_commission' => $totalDepotCommission,
            'total_retrait_commission' => $totalRetraitCommission,
            'total_depot_count' => $totalDepotCount,
            'total_retrait_count' => $totalRetraitCount,
        ], 200);
    }


    public function getCompleteTransactions()
    {
        $userId = auth()->user()->id; // Get the authenticated user's ID

        // Get transactions for the specified date, grouping by the date part only
        $transactions = Transaction::where('user_id', $userId) // Filter by user ID
            ->with('operateur')
            ->orderBy('created_at', 'desc') // Order by created_at in descending order
            ->get()
            ->groupBy(function ($transaction) {
                // Extract the date part from the created_at timestamp
                return $transaction->created_at->format('Y-m-d');
            });

        return response()->json($transactions, 200);
    }


}
