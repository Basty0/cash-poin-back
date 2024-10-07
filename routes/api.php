<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\OperateurController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('operateurs', [OperateurController::class, 'index']);
    Route::get('transactions', [TransactionController::class, 'getTransactionsByDateAndUser']);
    Route::post('transactions', [TransactionController::class, 'createTransaction']);
    Route::put('transactions/{id}', [TransactionController::class, 'updateTransaction']);
    Route::get('transactions/grouped-by-operator', [TransactionController::class, 'getTransactionsGroupedByOperator']);
    Route::get('transactions/summary-by-type', [TransactionController::class, 'getTransactionsSummaryByType']);
    Route::get('transactions/general-summary', [TransactionController::class, 'getGeneralTransactionSummary']);
    Route::get('transactions/operator-report', [TransactionController::class, 'getOperatorTransactionReport']);
    Route::get('transactions/by-user-type-date', [TransactionController::class, 'getTransactionsByUserTypeAndDate']);
    Route::get('transactions/recapitulatif', [TransactionController::class, 'recapitulatifParOperateur']);
    Route::get('transactions/search-by-tel', [TransactionController::class, 'searchByTel']);
    Route::get('user/me', [ApiAuthController::class, 'getAuthenticatedUser']);

    Route::get('complete/trasactions', [TransactionController::class, 'getCompleteTransactions']);

    Route::get('dashboard/summary', [TransactionController::class, 'getDashboardSummary']);
    Route::get('dashboard/details', [TransactionController::class, 'getTransactionDetails']);


});

Route::get('transactions/daily', [ApiAuthController::class, 'getDailyTransactions']);

Route::get('/transactions/monthly/{year}', [TransactionController::class, 'getMonthlyTransactions']);


Route::get('/transactions/monthl/{month}/{year}', [TransactionController::class, 'getTransactionsByMonthAndYear']);

// routes/api.php
Route::get('/export-transactions', [TransactionController::class, 'exportTransactions']);






// Routes pour l'authentification
Route::post('register', [ApiAuthController::class, 'register']);
Route::post('login', [ApiAuthController::class, 'login']);
Route::post('logout', [ApiAuthController::class, 'logout'])->middleware('auth:sanctum');


