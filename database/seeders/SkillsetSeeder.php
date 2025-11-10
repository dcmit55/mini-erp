<?php
// filepath: database/seeders/SkillsetSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hr\Skillset;

class SkillsetSeeder extends Seeder
{
    public function run(): void
    {
        $skillsets = [
            // Production Skills
            ['name' => 'Sewing', 'category' => 'Production', 'proficiency_required' => 'intermediate', 'description' => 'Machine and hand sewing techniques'],
            ['name' => 'Airbrushing', 'category' => 'Production', 'proficiency_required' => 'advanced', 'description' => 'Airbrush painting and detailing'],
            ['name' => 'Pattern Making', 'category' => 'Production', 'proficiency_required' => 'advanced', 'description' => 'Creating and adjusting patterns'],
            ['name' => 'Cutting', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Fabric cutting and material preparation'],
            ['name' => 'Assembly', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Product assembly and construction'],

            // Technical Skills
            ['name' => 'Machine Maintenance', 'category' => 'Technical', 'proficiency_required' => 'intermediate', 'description' => 'Sewing machine maintenance and repair'],
            ['name' => 'Equipment Setup', 'category' => 'Technical', 'proficiency_required' => 'basic', 'description' => 'Production equipment setup'],

            // Quality Control
            ['name' => 'Quality Inspection', 'category' => 'Quality Control', 'proficiency_required' => 'intermediate', 'description' => 'Product quality inspection and verification'],
            ['name' => 'Finishing', 'category' => 'Quality Control', 'proficiency_required' => 'basic', 'description' => 'Final product finishing and detailing'],

            // Administrative
            ['name' => 'Inventory Management', 'category' => 'Administrative', 'proficiency_required' => 'basic', 'description' => 'Material and inventory tracking'],
        ];

        foreach ($skillsets as $skillset) {
            Skillset::create($skillset);
        }
    }
}
