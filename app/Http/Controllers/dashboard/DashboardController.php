<?php

namespace App\Http\Controllers\dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDataAgents()
    {
        $activeAgent = DB::table('customers')
            ->where('type', 'agent')
            ->where('status', true)
            ->where('deleted_at', null)
            ->count();

        $inactiveAgent = DB::table('customers')
            ->where('type', 'agent')
            ->where('status', false)
            ->where('deleted_at', '!=', null)
            ->count();

        return ResponseHelper::success('Active agents retrieved successfully.', ['active_agents' => $activeAgent, 'inactive_agents' => $inactiveAgent], 200);
    }

    public function getDataVendors()
    {
        $activeVendor = DB::table('vendors')
            ->where('status', true)
            ->where('deleted_at', null)
            ->count();
        $inactiveVendor = DB::table('vendors')
            ->where('status', false)
            ->where('deleted_at', '!=', null)
            ->count();

        return ResponseHelper::success('Active vendors retrieved successfully.', ['active_vendors' => $activeVendor, 'inactive_vendors' => $inactiveVendor], 200);
    }

    public function getDataAirlines()
    {
        $activeAirline = DB::table('airlines')
            ->where('status', true)
            ->where('deleted_at', null)
            ->count();
        $inactiveAirline = DB::table('airlines')
            ->where('status', false)
            ->where('deleted_at', '!=', null)
            ->count();

        return ResponseHelper::success('Active airlines retrieved successfully.', ['active_airlines' => $activeAirline, 'inactive_airlines' => $inactiveAirline], 200);
    }

    public function getDataUsers()
    {
        $activeUsers = DB::table('users')
            ->where('status', true)
            ->where('deleted_at', null)
            ->count();
            $inactiveUsers = DB::table('users')
            ->where('status', false)
            ->where('deleted_at', '!=', null)
            ->count();

        return ResponseHelper::success('Active users retrieved successfully.', ['active_users' => $activeUsers, 'inactive_users' => $inactiveUsers], 200);
    }

    public function getDataAirports()
    {
        $activeAirport = DB::table('airports')
            ->where('status', true)
            ->where('deleted_at', null)
            ->count();
            $inactiveAirport = DB::table('airports')
            ->where('status', false)
            ->where('deleted_at', '!=', null)
            ->count();

        return ResponseHelper::success('Active airports retrieved successfully.', ['active_airports' => $activeAirport, 'inactive_airports' => $inactiveAirport], 200);
    }

    public function getTopDest(Request $request)
    {
        $limit = $request->input('limit', 10);
        $date_from = $request->input('date_from', null);
        $date_to = $request->input('date_to', null);


        $topDest = DB::table('awb')
            ->join('airport', 'awb.pol', '=', 'airport.id_airport')
            ->select('airport.name_airport as destination_name', DB::raw('COUNT(awb.id_awb) as total_orders'))
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                return $query->whereBetween('awb.created_at', [$date_from, $date_to]);
            })
            ->groupBy('awb.pol')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get();

        return ResponseHelper::success('Top destinations retrieved successfully.', ['top_destinations' => $topDest], 200);
    }

    public function getTotalTonnage(Request $request)
    {
        $date_from = $request->input('date_from', null);
        $date_to = $request->input('date_to', null);

        $monthlyTonnage = DB::table('awb')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(chargeable_weight) as total_tonnage')
            )
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                return $query->whereBetween('awb.created_at', [$date_from, $date_to]);
            })
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return ResponseHelper::success('Monthly tonnage retrieved successfully.', ['monthly_tonnage' => $monthlyTonnage], 200);
    }

    //top 10 agent berdasarkan total shipment,tonase,sales,revenue
    public function getTopAgentsByShipment(Request $request)
    {
        $limit = $request->input('limit', 10);
        $date_from = $request->input('date_from', null);
        $date_to = $request->input('date_to', null);


        $topAgents = DB::table('awb')
            ->join('customers', 'awb.agent', '=', 'customers.id_customer')
            ->select('customers.name as agent_name', DB::raw('COUNT(awb.id_awb) as total_shipments'))
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                return $query->whereBetween('awb.created_at', [$date_from, $date_to]);
            })
            ->where('awb.status', '!=', 'awb_deleted')
            ->groupBy('awb.agent', 'customers.name')
            ->orderByDesc('total_shipments')
            ->limit($limit)
            ->get();
        return ResponseHelper::success('Top agents retrieved successfully.', ['top_agents' => $topAgents], 200);
    }

    public function getTopAgentsByTonnage(Request $request)
    {
        $limit = $request->input('limit', 10);
        $date_from = $request->input('date_from', null);
        $date_to = $request->input('date_to', null);

        $topAgents = DB::table('awb')
            ->join('customers', 'awb.agent', '=', 'customers.id_customer')
            ->select('customers.name as agent_name', DB::raw('SUM(awb.chargeable_weight) as total_tonnage'))
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                return $query->whereBetween('awb.created_at', [$date_from, $date_to]);
            })
            ->where('awb.status', '!=', 'awb_deleted')
            ->groupBy('awb.agent', 'customers.name')
            ->orderByDesc('total_tonnage')
            ->limit($limit)
            ->get();

        return ResponseHelper::success('Top agents retrieved successfully.', ['top_agents' => $topAgents], 200);
    }

    public function getTopAgentsBySales(Request $request)
    {
        $limit = $request->input('limit', 10);
        $date_from = $request->input('date_from', null);
        $date_to = $request->input('date_to', null);

        $topAgents = DB::table('selling_salesorder')
            ->join('salesorder', 'selling_salesorder.id_salesorder', '=', 'salesorder.id_salesorder')
            ->join('customers', 'salesorder.id_customer', '=', 'customers.id_customer')
            ->join('awb', 'salesorder.id_awb', '=', 'awb.id_awb')
            ->select(
                'customers.name as agent_name',
                DB::raw('SUM(CASE WHEN selling_salesorder.charge_by = "chargeable_weight" THEN selling_salesorder.selling_value * awb.chargeable_weight ELSEIF selling_salesorder.charge_by = "gross_weight" THEN selling_salesorder.selling_value * awb.gross_weight ELSE selling_salesorder.selling_value END) as total_sales')
            )
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                return $query->whereBetween('salesorder.created_at', [$date_from, $date_to]);
            })
            ->where('salesorder.status', '!=', 'salesorder_deleted')
            ->groupBy('salesorder.id_customer', 'customers.name')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        return ResponseHelper::success('Top agents retrieved successfully.', ['top_agents' => $topAgents], 200);
    }

    public function getTopAgentsByRevenue(Request $request)
    {

        $limit = $request->input('limit', 10);
        $date_from = $request->input('date_from', null);
        $date_to = $request->input('date_to', null);
        $rate = $request->input('rate', 16000); // Example conversion rate, you can adjust this as needed
        $topAgents = DB::table('cost_jobsheet')
            ->join('salesorder', 'selling_salesorder.id_salesorder', '=', 'salesorder.id_salesorder')
            ->join('customers', 'salesorder.id_customer', '=', 'customers.id_customer')
            ->join('awb', 'salesorder.id_awb', '=', 'awb.id_awb')

            ->select(
                'customers.name as agent_name',
                DB::raw('SUM(CASE WHEN selling_salesorder.charge_by = "chargeable_weight" 
                THEN selling_salesorder.selling_value * awb.chargeable_weight 
                ELSEIF selling_salesorder.charge_by = "gross_weight" 
                THEN selling_salesorder.selling_value * awb.gross_weight
                ELSE selling_salesorder.selling_value END) - SUM(CASE WHEN cost_jobsheet.charge_by = "chargeable_weight" THEN cost_jobsheet.cost_value * awb.chargeable_weight ELSEIF cost_jobsheet.charge_by = "gross_weight" THEN cost_jobsheet.cost_value * awb.gross_weight ELSE cost_jobsheet.cost_value END) as total_revenue')
            )
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                return $query->whereBetween('salesorder.created_at', [$date_from, $date_to]);
            })
            ->where('salesorder.status', '!=', 'salesorder_deleted')
            ->groupBy('salesorder.id_customer', 'customers.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }
}
