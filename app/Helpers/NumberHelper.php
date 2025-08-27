<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NumberHelper
{
    public static function generateSalesOrderNumber()
    {
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('y');

        // Ambil nomor terakhir bulan & tahun ini
        $lastOrder = DB::table('salesorder')
            ->whereRaw('MONTH(created_at) = ?', [$month])
            ->whereRaw('YEAR(created_at) = ?', [Carbon::now()->format('Y')])
            ->orderBy('id_salesorder', 'desc')
            ->first();

        if ($lastOrder && isset($lastOrder->sales_order_no)) {
            // Pecah nomor terakhir
            $parts = explode('/', $lastOrder->sales_order_no);
            $lastNumber = intval(end($parts));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = "0001";
        }

        return "{$month}/{$year}/{$nextNumber}";
    }

     public static function generateJobsheetNumber()
    {
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('y');

        // Ambil nomor terakhir bulan & tahun ini
        $lastOrder = DB::table('jobsheet')
            ->whereRaw('MONTH(created_at) = ?', [$month])
            ->whereRaw('YEAR(created_at) = ?', [Carbon::now()->format('Y')])
            ->orderBy('id_jobsheet', 'desc')
            ->first();

        if ($lastOrder && isset($lastOrder->jobsheet_no)) {
            // Pecah nomor terakhir
            $parts = explode('/', $lastOrder->jobsheet_no);
            $lastNumber = intval(end($parts));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = "0001";
        }

        return "{$month}/{$year}/{$nextNumber}";
    }

    public static function generateInvoiceNumber(): string
    {
        $year = Carbon::now()->format('y');   // contoh: 25
        $month = Carbon::now()->format('m');  // contoh: 08

        $lastInvoice = DB::table('invoice')
            ->whereYear('created_at', Carbon::now()->year)
            ->orderBy('id_invoice', 'desc')
            ->first();

        if ($lastInvoice && isset($lastInvoice->invoice_no)) {
            $lastNumber = intval(substr($lastInvoice->invoice_no, -5));
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = "00001"; // reset tiap tahun
        }

        return "{$year}{$month}{$nextNumber}";
    }
}
