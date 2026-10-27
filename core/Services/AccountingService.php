<?php
namespace Core\Services;

use Core\Database\Database;
use Exception;

class AccountingService {
    /**
     * Post a balanced journal entry
     */
    public static function postJournal(array $entryData, array $lines): int {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $totalDebit += (float)($line['debit'] ?? 0);
            $totalCredit += (float)($line['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception("Unbalanced journal entry! Total Debit (₹{$totalDebit}) must equal Total Credit (₹{$totalCredit}).");
        }

        $journalNo = Database::getNextDocumentNumber('journal_entry', $entryData['company_id'] ?? 1);

        $journalId = Database::insert('journal_entries', [
            'company_id'   => $entryData['company_id'] ?? 1,
            'journal_no'   => $journalNo,
            'entry_date'   => $entryData['entry_date'] ?? date('Y-m-d'),
            'source_type'  => $entryData['source_type'] ?? null,
            'source_id'    => $entryData['source_id'] ?? null,
            'narration'    => $entryData['narration'] ?? '',
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'status'       => 'posted',
            'posted_by'    => $entryData['posted_by'] ?? 1,
            'posted_at'    => date('Y-m-d H:i:s')
        ]);

        foreach ($lines as $line) {
            Database::insert('journal_lines', [
                'journal_entry_id' => $journalId,
                'account_id'       => $line['account_id'],
                'debit'            => $line['debit'] ?? 0.00,
                'credit'           => $line['credit'] ?? 0.00,
                'line_narration'   => $line['line_narration'] ?? null
            ]);

            // Update account balance
            $netChange = ($line['debit'] ?? 0.00) - ($line['credit'] ?? 0.00);
            Database::getConnection()->prepare(
                "UPDATE chart_of_accounts SET current_balance = current_balance + :change WHERE id = :id"
            )->execute([
                'change' => $netChange,
                'id'     => $line['account_id']
            ]);
        }

        return $journalId;
    }
}
