<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        // Fetch transactions based on month and year
        return Transaction::whereMonth('created_at', $this->month)
            ->whereYear('created_at', $this->year)
            ->with('operateur', 'user') // Ensure user relationship if needed
            ->get();
    }

    public function headings(): array
    {
        return [
            'Index',
            'ID Transaction',
            'Nom Opérateur',
            'Nom Utilisateur',
            'Montant',
            'Commission',
            'Type',
            'Téléphone Client',
            'Date de Transaction',
            'Heure de Transaction',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id, // Index
            $transaction->id, // Transaction ID
            $transaction->operateur_id, // Operator Name
            $transaction->user->name, // User Name (Make sure user has a name field)
            $transaction->montant, // Amount
            $transaction->commission, // Commission
            $transaction->type, // Type
            $transaction->tel, // Client Phone
            $transaction->created_at->format('Y-m-d'), // Transaction Date
            $transaction->created_at->format('H:i:s'), // Transaction Time
        ];
    }

    public function title(): string
    {
        return 'Transactions';
    }
}
