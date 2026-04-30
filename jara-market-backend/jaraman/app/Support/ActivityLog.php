<?php

namespace App\Support;

use App\Exceptions\GeneralException;
use App\Models\PaymentLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ActivityLog
{
    public function log($causedBy, $performedOn, $message, $withProperties = [])
    {
        // activity()->by($causedBy)->performedOn($performedOn)->withProperties($withProperties)->log($message);
    }

    public function logTransaction(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $transaction = PaymentLog::create($data);
                if ($user = auth()?->user()) {
                    $this->log($user, $transaction, "{$user->first_name} initiated a transaction");
                }

                return $transaction;
            });
        } catch (QueryException $e) {
            report($e);
            throw new GeneralException('Error creating transaction log', 500);
        }
    }
}
