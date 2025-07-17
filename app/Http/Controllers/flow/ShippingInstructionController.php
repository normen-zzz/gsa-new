<?php

namespace App\Http\Controllers\flow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ShippingInstructionController extends Controller
{
    public function getShippingInstructions(Request $request)
    {
        $limit = $request->input('limit', 10);
        $search = $request->input('searchKey', '');
        $select = [
            'a.id_shippinginstruction',
            'c.name_customer as agent',
            'c.id_customer as id_agent',
            'd.name_customer as consignee',
            'd.id_customer as id_consignee',
            'a.type',
            'a.date',
            'a.eta',
            'a.etd',
            'e.name_airport as pol',
            'e.id_airport as id_pol',
            'e.code_airport as code_pol',
            'f.name_airport as pod',
            'f.id_airport as id_pod',
            'f.code_airport as code_pod',
            'a.commodity',
            'a.weight',
            'a.pieces',
            'a.dimensions',
            'a.special_instructions',
            'b.name as created_by',
            'a.status'
        ];

        $query = DB::table('shippinginstruction AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('customers AS c', 'a.agent', '=', 'c.id_customer')
            ->leftJoin('customers AS d', 'a.consignee', '=', 'd.id_customer')
            ->leftJoin('airports AS e', 'a.pol', '=', 'e.id_airport')
            ->leftJoin('airports AS f', 'a.pod', '=', 'f.id_airport')
            ->where('c.name_customer', 'like', '%' . $search . '%')
            ->orWhere('d.name_customer', 'like', '%' . $search . '%')
            ->orWhere('e.name_airport', 'like', '%' . $search . '%')
            ->orWhere('f.name_airport', 'like', '%' . $search . '%')
            ->orderBy('a.created_at', 'desc');

            

        $instructions = $query->paginate($limit);
        $instructions->getCollection()->transform(function ($instruction) {
            // json decode dimensions
            if ($instruction->dimensions) {
                $instruction->dimensions = json_decode($instruction->dimensions, true);
            } else {
                $instruction->dimensions = [];
            }
            return $instruction;
        });

        return response()->json([
            'status' => 'success',
            'data' => $instructions,
            'meta_data' => [
                'code' => 200,
                'message' => 'Shipping instructions retrieved successfully.',
            ],
        ]);
    }

    public function getShippingInstructionById(Request $request)
    {
        $id = $request->input('id');

        $select = [
            'a.id_shippinginstruction',
            'c.name_customer as agent',
            'c.id_customer as id_agent',
            'd.name_customer as consignee',
            'd.id_customer as id_consignee',
            'a.type',
            'a.date',
            'a.eta',
            'a.etd',
            'e.name_airport as pol',
            'e.id_airport as id_pol',
            'e.code_airport as code_pol',
            'f.name_airport as pod',
            'f.id_airport as id_pod',
            'f.code_airport as code_pod',
            'a.commodity',
            'a.weight',
            'a.pieces',
            'a.dimensions',
            'a.special_instructions',
            'b.name as created_by',
            'a.status'
        ];
        $instruction = DB::table('shippinginstruction AS a')
            ->select($select)
            ->leftJoin('users AS b', 'a.created_by', '=', 'b.id_user')
            ->leftJoin('customers AS c', 'a.agent', '=', 'c.id_customer')
            ->leftJoin('customers AS d', 'a.consignee', '=', 'd.id_customer')
            ->leftJoin('airports AS e', 'a.pol', '=', 'e.id_airport')
            ->leftJoin('airports AS f', 'a.pod', '=', 'f.id_airport')
            ->where('a.id_shippinginstruction', $id)
            ->orderBy('a.id_shippinginstruction', 'desc')->first();
        
            // json decode dimensions
        if ($instruction && $instruction->dimensions) {
            $instruction->dimensions = json_decode($instruction->dimensions, true);
        } else {
            $instruction->dimensions = [];
        }

        if (!$instruction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Shipping instruction not found.',
                ]
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $instruction,
            'meta_data' => [
                'code' => 200,
                'message' => 'Shipping instruction retrieved successfully.',
            ]
        ], 200);
    }

    public function createShippingInstruction(Request $request)
    {
        $data = $request->validate([
            'agent' => 'required|integer',
            'consignee' => 'required|integer',
            'etd' => 'required|date',
            'eta' => 'required|date',
            'pol' => 'required|integer|exists:airports,id_airport',
            'pod' => 'required|integer|exists:airports,id_airport',
            'commodity' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'special_instructions' => 'nullable|string',
        ]);

        $data['created_by'] = $request->user()->id_user;
        // date y-m-d H:i:s
        $data['date'] = now();

        $dimensions = $request->validate([
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.weight' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $instruction = DB::table('shippinginstruction')->insert($data);
            $id_shippinginstruction = DB::getPdo()->lastInsertId();
            if ($instruction) {
                $addDimensions = true;
                if (isset($request->dimensions) && is_array($request->dimensions) && count($request->dimensions) > 0) {
                    foreach ($request->dimensions as $dimension) {
                        $dimensionInsert = DB::table('dimension_shippinginstruction')->insert([
                            'id_shippinginstruction' => $id_shippinginstruction,
                            'length' => $dimension['length'] ?? null,
                            'width' => $dimension['width'] ?? null,
                            'height' => $dimension['height'] ?? null,
                            'weight' => $dimension['weight'] ?? null,
                            'created_by' => $data['created_by'],
                        ]);
                        if (!$dimensionInsert) {
                            $addDimensions = false;
                            break;
                        }
                    }
                } else {
                    // If no dimensions provided, consider it successful
                    $addDimensions = true;
                }
                if ($addDimensions) {
                    // add dimensions to data 
                    $data['dimensions'] = $request->dimensions ?? [];
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'data' => $data,

                        'meta_data' => [
                            'code' => 201,
                            'message' => 'Shipping instruction created successfully.',
                        ],
                    ], 201);
                } else {
                    throw new Exception('Failed to add dimensions to shipping instruction.');
                }
            } else {
                throw new Exception('Failed to create shipping instruction.');
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => '||Exception|| Failed to create shipping instruction: ' . $e->getMessage(),
                ]
            ], 500);
        }
    }

    public function updateShippingInstruction(Request $request)
    {
        $data = $request->validate([
            'id_shippinginstruction' => 'required|integer|exists:shippinginstruction,id_shippinginstruction',
            'agent' => 'required|integer|exists:customers,id_customer',
            'consignee' => 'required|integer|exists:customers,id_customer',
            'etd' => 'required|date',
            'eta' => 'required|date',
            'pol' => 'required|integer|exists:airports,id_airport',
            'pod' => 'required|integer|exists:airports,id_airport',
            'commodity' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:0',
            'special_instructions' => 'nullable|string',

        ]);

        $data['updated_by'] = $request->user()->id_user;
        $id = $request->input('id_shippinginstruction');




        DB::beginTransaction();
        try {
            $instruction = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $id)
                ->update($data);

            if ($instruction) {
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'meta_data' => [
                        'code' => 200,
                        'message' => 'Shipping instruction updated successfully.',
                    ],
                ], 200);
            } else {
                throw new Exception('Failed to update shipping instruction.');
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to update shipping instruction: ' . $e->getMessage(),
                ]
            ], 500);
        }
    }

    public function deleteShippingInstruction(Request $request, $id)
    {
        $instruction = DB::table('shippinginstruction')->where('id', $id)->first();

        if (!$instruction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Shipping instruction not found.',
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('shippinginstruction')
                ->where('id', $id)
                ->update(['status' => 'deleted', 'deleted_at' => now(), 'deleted_by' => Auth::id()]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'meta_data' => [
                    'code' => 200,
                    'message' => 'Shipping instruction deleted successfully.',
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to delete shipping instruction: ' . $e->getMessage(),
                ]
            ], 500);
        }
    }

    public function receiveShippingInstruction(Request $request)
    {
        $request->validate([
            'id_shippinginstruction' => 'required|integer|exists:shippinginstruction,id_shippinginstruction',
            'awb' => 'required|integer',
            'agent' => 'required|integer|exists:customers,id_customer',
            'consignee' => 'required|integer|exists:customers,id_customer',
            'etd' => 'required|date',
            'eta' => 'required|date',
            'pol' => 'required|integer|exists:airports,id_airport',
            'pod' => 'required|integer|exists:airports,id_airport',
            'commodity' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'pieces' => 'required|integer|min:0',
            'special_instructions' => 'nullable|string',
            'dimensions' => 'nullable|array',
            'dimensions.*.length' => 'required|numeric|min:0',
            'dimensions.*.width' => 'required|numeric|min:0',
            'dimensions.*.height' => 'required|numeric|min:0',
            'dimensions.*.weight' => 'required|numeric|min:0',
            'data_flight' => 'nullable|array',
            'data_flight.*.flight_number' => 'required|string|max:255',
            'data_flight.*.departure' => 'required|date',
            'data_flight.*.arrival' => 'required|date',
        ]);
        DB::beginTransaction();
        try {
            $dataAwb = [
                'awb' => $request->input('awb'),
                'etd' => $request->input('etd'),
                'eta' => $request->input('eta'),
                'pol' => $request->input('pol'),
                'pod' => $request->input('pod'),
                'commodity' => $request->input('commodity'),
                'weight' => $request->input('weight'),
                'pieces' => $request->input('pieces'),
                'dimensions' => json_encode($request->input('dimensions', [])),
                'data_flight' => json_encode($request->input('data_flight', [])),
                'handling_instructions' => $request->input('special_instructions', ''),
                'created_at' => now(),
                'created_by' => $request->user()->id_user,
            ];
            $addAwb = DB::table('awb')->insert($dataAwb);
            if ($addAwb) {
                $id_awb = DB::getPdo()->lastInsertId();
                $dataJob = [
                    'id_shippinginstruction' => $request->input('id_shippinginstruction'),
                    'id_awb' => $id_awb,
                    'agent' => $request->input('agent'),
                    'consignee' => $request->input('consignee'),
                    'etd' => $request->input('etd'),
                    'eta' => $request->input('eta'),
                    'date' => now(),
                    'created_at' => now(),
                    'created_by' => $request->user()->id_user,
                ];
                $insertDataJob = DB::table('job')->insert($dataJob);
                if ($insertDataJob) {
                    $updateStatus = DB::table('shippinginstruction')
                        ->where('id_shippinginstruction', $request->input('id_shippinginstruction'))
                        ->update(['status' => 'received_by_cs', 'updated_by' => $request->user()->id_user, 'updated_at' => now()]);
                    if ($updateStatus) {
                        DB::commit();
                        return response()->json([
                            'status' => 'success',
                            'meta_data' => [
                                'code' => 200,
                                'message' => 'Shipping instruction received successfully.',
                            ]
                        ], 200);
                    } else {
                        throw new Exception('Failed to update shipping instruction status.');
                    }
                }
            }
        } catch (Exception $th) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to receive shipping instruction: ' . $th->getMessage(),
                ]
            ], 500);
        }

        
    }

    public function rejectShippingInstruction(Request $request)
    {
        $id = $request->input('id');
        $instruction = DB::table('shippinginstruction')->where('id', $id)->first();

        if (!$instruction) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'meta_data' => [
                    'code' => 404,
                    'message' => 'Shipping instruction not found.',
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $update = DB::table('shippinginstruction')
                ->where('id_shippinginstruction', $id)
                ->update(['status' => 'rejected_by_cs']);

            if ($update) {
                // change status to rejected_by_cs
                $instruction->status = 'rejected_by_cs';
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'data' => $instruction,
                    'meta_data' => [
                        'code' => 200,
                        'message' => 'Shipping instruction rejected successfully.',
                    ]
                ], 200);
            } else {
                throw new Exception('Failed to update shipping instruction status.');
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'meta_data' => [
                    'code' => 500,
                    'message' => 'Failed to reject shipping instruction: ' . $e->getMessage(),
                ]
            ], 500);
        }
    }
}
