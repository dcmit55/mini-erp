<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Production\StageType;
use App\Models\Production\Stage;

class StageTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'FRP/STATUE' => ['Design and prototyping', 'Printing', 'Assembly and Joining', 'Grinding and rough sanding', 'Epoxy base and putty', 'Sanding', 'Epoxy finishing before painting', 'QC check before painting', 'Base color application', 'Main color application', 'QC check before clear coat', 'Cleaning and clear coat application', 'Final QC and packing'],
            'INFLATABLE' => ['Design and prototyping', 'Draft and print 3d part', 'Make structure and join all part', 'Take photo structure and impose', 'Structure adjustments', 'Structure approval', 'Wrapping and join all part', 'Take photo after wrap and impose', 'Wrapping adjustments', 'Wrapping approval', 'Finishing and cleaning', 'Final QC and packing'],
            'COMPRESSED FOAM' => ['Design and prototyping', 'Draft and print 3d part', 'Make structure and join all part', 'Take photo structure and join all part', 'Structure adjustments', 'Structure approval', 'Wrapping and join all part', 'Take photo after wrap and impose', 'Wrapping adjustments', 'Wrapping approval', 'Finishing and cleaning', 'Final QC and packing'],
        ];

        foreach ($data as $typeName => $stages) {
            $stageType = StageType::firstOrCreate(['name' => $typeName]);

            foreach ($stages as $seq => $stageName) {
                Stage::firstOrCreate(['stage_type_id' => $stageType->id, 'name' => $stageName], ['sequence' => $seq + 1, 'is_active' => true]);
            }
        }
    }
}
