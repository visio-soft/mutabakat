<?php

namespace Visio\mutabakat\Services;

use App\Models\ParkSession;
use App\Models\Payment;
use Visio\mutabakat\Models\Mutabakat;
use Visio\mutabakat\Models\HgsParkTransaction;
use Carbon\Carbon;

class MutabakatWidgetService
{
   public static function getStatsData(): array
   {
       $today = Carbon::today();
       $weekAgo = $today->copy()->subWeek();

       $todayReconciliation = Mutabakat::dateSum('provision_date', $today,'total_amount');
       $todayPayments = Payment::dateSum('created_at',$today ,'amount');
       $todaySessions = ParkSession::dateSum('entry_at',$today, 'amount');
       $todayDifference = $todayPayments - $todayReconciliation;

       $weekReconciliation = Mutabakat::betweenDateSum('provision_date', $weekAgo, $today, 'total_amount');
       $weekPayments = Payment::betweenDateSum('created_at', $weekAgo, $today, 'amount');
       $weekSessions = ParkSession::betweenDateSum('entry_at', $weekAgo, $today, 'amount');
       $weekDifference = $weekPayments - $weekReconciliation;

       $totalRecords = HgsParkTransaction::count();
       $matchingRecords = 0;
       if ($totalRecords > 0) {
           $reconciliations = Mutabakat::select('provision_date','park_id','total_amount')->get();
           if ($reconciliations->isNotEmpty()) {
               $parkIds = $reconciliations->pluck('park_id')->unique()->values()->toArray();
               $dates = $reconciliations->pluck('provision_date')->map(fn($d)=>$d->format('Y-m-d'))->unique()->values();
               $minDate = $dates->min();
               $maxDate = $dates->max();
               $payments = Mutabakat::getPaymentsForReconciliation($parkIds, $minDate, $maxDate);
               $paymentGrouped = $payments->groupBy(function($p) {
                   return $p->park_id.'|'.$p->created_at->toDateString();
               })->map(fn($group)=>$group->sum('amount'));
               foreach ($reconciliations as $reconciliation) {
                   $key = $reconciliation->park_id.'|'.$reconciliation->provision_date->format('Y-m-d');
                   $paymentAmount = $paymentGrouped[$key] ?? 0;
                   if (abs($paymentAmount - $reconciliation->total_amount) < 1) {
                       $matchingRecords++;
                   }
               }
           }
       }
       $matchingPercentage = $totalRecords > 0 ? round(($matchingRecords / $totalRecords) * 100, 1) : 0;
       $sessionPaymentDifference = $todaySessions - $todayPayments;

       return [
           'today' => [
               'reconciliation' => $todayReconciliation,
               'payments' => $todayPayments,
               'sessions' => $todaySessions,
               'difference' => $todayDifference,
           ],
           'week' => [
               'reconciliation' => $weekReconciliation,
               'payments' => $weekPayments,
               'sessions' => $weekSessions,
               'difference' => $weekDifference,
           ],
           'matching' => [
               'matched' => $matchingRecords,
               'total' => $totalRecords,
               'percentage' => $matchingPercentage,
           ],
           'session_payment_difference' => $sessionPaymentDifference,
       ];
   }
}
