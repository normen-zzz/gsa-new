<?php

namespace Database\Seeders\master;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class UserMenuAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $csv = Reader::createFromPath(database_path('seeders/data/role_management_final.csv'), 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            try {
                $division = DB::table('divisions')->where('name', trim($record['division']))->first();
                $position = DB::table('positions')->where('name', trim($record['position']))->first();

                if ($division && $position) {
                    DB::table('menu_user')->updateOrInsert(
                        [
                            'id_position' => $position->id_position,
                            'id_division' => $division->id_division,
                            'id_listmenu' => $record['menu_id'],
                        ],
                        [
                            'can_create'  => intval($record['can_create']),
                            'can_read'    => intval($record['can_view']),
                            'can_update'  => intval($record['can_edit']),
                            'can_delete'  => intval($record['can_delete']),
                            'can_approve' => intval($record['can_approve']),
                            'can_reject'  => intval($record['can_reject']),
                            'can_import'  => intval($record['can_import']),
                            'can_export'  => intval($record['can_export']),
                            // 'can_create' => $record['can_create'],
                            // 'can_read' => $record['can_view'],
                            // 'can_update' => $record['can_edit'],
                            // 'can_delete' => $record['can_delete'],
                            // 'can_approve' => $record['can_approve'],
                            // 'can_reject' => $record['can_reject'],
                            // 'can_import' => $record['can_import'],
                            // 'can_export' => $record['can_export'],
                            'status' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                } else {
                    echo "Division or Position not found for: Division={$record['division']}, Position={$record['position']}\n";
                }
            } catch (\Exception $e) {
                echo "Error on record: " . json_encode($record) . "\n";
                echo "Error message: " . $e->getMessage() . "\n";
            }
        }
    }
}