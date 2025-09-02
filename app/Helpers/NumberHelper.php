<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NumberHelper
{

    public static function generateShippingInstructionNumber()
    {
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('y');

        // Ambil nomor terakhir bulan & tahun ini
        $lastOrder = DB::table('shippinginstruction')
            ->whereRaw('MONTH(created_at) = ?', [$month])
            ->whereRaw('YEAR(created_at) = ?', [Carbon::now()->format('Y')])
            ->orderBy('id_shippinginstruction', 'desc')
            ->first();

        if ($lastOrder && isset($lastOrder->no_shippinginstruction)) {
            // Pecah nomor terakhir
            $parts = explode('/', $lastOrder->no_shippinginstruction);
            $lastNumber = intval(end($parts));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = "0001";
        }

        return "{$month}/{$year}/{$nextNumber}";
    }

    public static function generateJobNumber()
    {
        $month = Carbon::now()->format('m');
        $year = Carbon::now()->format('y');

        // Ambil nomor terakhir bulan & tahun ini
        $lastOrder = DB::table('job')
            ->whereRaw('MONTH(created_at) = ?', [$month])
            ->whereRaw('YEAR(created_at) = ?', [Carbon::now()->format('Y')])
            ->orderBy('id_job', 'desc')
            ->first();

        if ($lastOrder && isset($lastOrder->no_job)) {
            // Pecah nomor terakhir
            $parts = explode('/', $lastOrder->no_job);
            $lastNumber = intval(end($parts));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = "0001";
        }

        return "{$month}/{$year}/{$nextNumber}";
    }


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

        if ($lastOrder && isset($lastOrder->no_salesorder)) {
            // Pecah nomor terakhir
            $parts = explode('/', $lastOrder->no_salesorder);
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

        if ($lastOrder && isset($lastOrder->no_jobsheet)) {
            // Pecah nomor terakhir
            $parts = explode('/', $lastOrder->no_jobsheet);
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

        if ($lastInvoice && isset($lastInvoice->no_invoice)) {
            $lastNumber = intval(substr($lastInvoice->no_invoice, -5));
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = "00001"; // reset tiap tahun
        }

        return "{$year}{$month}{$nextNumber}";
    }


    public static function generateAccountpayablenumber($type): string
    {
        // Find the last record with the same type
        $lastRecord = DB::table('accountpayable')
            ->where('type', $type)
            ->orderBy('id_accountpayable', 'desc')
            ->first();

        if ($lastRecord && isset($lastRecord->no_accountpayable)) {
            // Extract the number part after the hyphen
            $parts = explode('-', $lastRecord->no_accountpayable);
            $lastNumber = intval(end($parts));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1; // Start with 1 if no previous record
        }

        return "{$type}-{$nextNumber}";
    }
}
