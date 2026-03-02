<?php

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
            ['name' => 'Cutting', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Fabric cutting and material preparation'],
            ['name' => 'Airbrushing', 'category' => 'Production', 'proficiency_required' => 'advanced', 'description' => 'Airbrush painting and detailing'],
            ['name' => 'Assembly', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Product assembly and construction'],
            ['name' => 'Finishing', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Final product finishing and detailing'],
            ['name' => 'Packaging', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Final packing and labeling of products'],
            ['name' => 'Ironing / Steaming', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Pressing and removing wrinkles from garments'],
            ['name' => 'Hand Stitching', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Manual hand-stitching for detailed parts'],
            ['name' => 'Molding', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Shaping materials into desired forms'],
            ['name' => 'Wrapping', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Wrapping products for protection'],
            ['name' => 'Structuring', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Building internal structures for products'],
            ['name' => 'Repairing', 'category' => 'Production', 'proficiency_required' => 'basic', 'description' => 'Fixing and mending products'],
            ['name' => 'Hand Carrying', 'category' => 'Logistics', 'proficiency_required' => 'basic', 'description' => 'Transporting goods manually'],
            ['name' => 'Bag Making', 'category' => 'Production', 'proficiency_required' => 'intermediate', 'description' => 'Creating various types of bags'],
            ['name' => 'Quantity Fabrication', 'category' => 'Production', 'proficiency_required' => 'intermediate', 'description' => 'Mass production techniques and processes'],
            ['name' => 'Material Handling', 'category' => 'Production', 'proficiency_required' => 'intermediate', 'description' => 'Safe handling and movement of materials'],

            // Technical Skills
            ['name' => 'Machine Maintenance', 'category' => 'Technical', 'proficiency_required' => 'intermediate', 'description' => 'Sewing machine maintenance and repair'],
            ['name' => 'Electrical Maintenance', 'category' => 'Technical', 'proficiency_required' => 'basic', 'description' => 'Handling minor electrical issues on production equipment'],
            ['name' => '2D Designing', 'category' => 'Technical', 'proficiency_required' => 'basic', 'description' => '2D design and layout for production'],
            ['name' => '3D Designing', 'category' => 'Technical', 'proficiency_required' => 'basic', 'description' => '3D modeling and prototyping'],
            ['name' => 'Fashion Designing', 'category' => 'Technical', 'proficiency_required' => 'intermediate', 'description' => 'Fashion design and trend analysis'],
            ['name' => 'Pattern Making', 'category' => 'Technical', 'proficiency_required' => 'advanced', 'description' => 'Creating and adjusting patterns'],
            ['name' => 'Drafting', 'category' => 'Technical', 'proficiency_required' => 'advanced', 'description' => 'Creating and adjusting drafts for production'],
            ['name' => 'Embroidery Operation', 'category' => 'Technical', 'proficiency_required' => 'intermediate', 'description' => 'Operating embroidery machines for logo and pattern stitching'],
            ['name' => 'Graphic Designing', 'category' => 'Technical', 'proficiency_required' => 'intermediate', 'description' => 'Creating visual content for products'],
            ['name' => 'Warehouse Handling', 'category' => 'Logistics', 'proficiency_required' => 'basic', 'description' => 'Handling and storing materials safely'],
            ['name' => 'Imposing', 'category' => 'Technical', 'proficiency_required' => 'basic', 'description' => 'Preparing designs for production layout'],
            ['name' => 'Sample Making', 'category' => 'Technical', 'proficiency_required' => 'intermediate', 'description' => 'Creating samples for design validation'],
            ['name' => 'Prototyping', 'category' => 'Technical', 'proficiency_required' => 'advanced', 'description' => 'Developing prototypes for testing and evaluation'],
            ['name' => 'Animatronic Electrical', 'category' => 'Technical', 'proficiency_required' => 'advanced', 'description' => 'Developing animatronic electrical systems for prototypes'],
            ['name' => 'Animatronic Mechanical', 'category' => 'Technical', 'proficiency_required' => 'advanced', 'description' => 'Developing animatronic mechanical systems for prototypes'],
            ['name' => 'Animatronic Programming', 'category' => 'Technical', 'proficiency_required' => 'advanced', 'description' => 'Developing animatronic programming systems for prototypes'],
            ['name' => 'Driving', 'category' => 'Logistics', 'proficiency_required' => 'basic', 'description' => 'Operating vehicles for transport'],

            // Quality Control
            ['name' => 'Quality Inspection', 'category' => 'Quality Control', 'proficiency_required' => 'intermediate', 'description' => 'Product quality inspection and verification'],
            ['name' => 'Measurement Checking', 'category' => 'Quality Control', 'proficiency_required' => 'basic', 'description' => 'Measuring and verifying component sizes'],

            // Administrative
            ['name' => 'Shipping Coordination', 'category' => 'Logistics', 'proficiency_required' => 'intermediate', 'description' => 'Coordinating shipping and delivery schedules'],
            ['name' => 'Inventory Management', 'category' => 'Logistics', 'proficiency_required' => 'basic', 'description' => 'Material and inventory tracking'],
            ['name' => 'Costing', 'category' => 'Administrative', 'proficiency_required' => 'basic', 'description' => 'Product costing and budgeting'],
            ['name' => 'Scheduling', 'category' => 'Administrative', 'proficiency_required' => 'intermediate', 'description' => 'Planning work schedules and task timelines'],
            ['name' => 'Communication & Coordination', 'category' => 'Administrative', 'proficiency_required' => 'intermediate', 'description' => 'Inter-department communication and task coordination'],
            ['name' => 'Payroll Management', 'category' => 'Administrative', 'proficiency_required' => 'advanced', 'description' => 'Payroll processing and employee compensation management'],
            ['name' => 'Timing', 'category' => 'Administrative', 'proficiency_required' => 'basic', 'description' => 'Production timing'],
            ['name' => 'Financial Management', 'category' => 'Administrative', 'proficiency_required' => 'intermediate', 'description' => 'Budgeting and financial oversight'],
            ['name' => 'Procurement Coordination', 'category' => 'Administrative', 'proficiency_required' => 'intermediate', 'description' => 'Supplier communication and purchase request handling'],
        ];

        foreach ($skillsets as $skillset) {
            Skillset::create($skillset);
        }
    }
}
