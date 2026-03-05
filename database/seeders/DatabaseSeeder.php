<?php

namespace Database\Seeders;

use App\Domain\Job\Models\Job;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tradie\Models\Tradie;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Ace Plumbing Co.',
            'slug' => 'ace-plumbing',
        ]);

        // Create an admin tradie for this tenant
        $admin = Tradie::factory()->forTenant($tenant)->withBusinessNumber()->admin()->create([
            'name'           => 'Admin User',
            'email'          => 'admin@acepiping.com',
            'personal_phone' => '+61412000001',
        ]);

        // Create some regular tradies
        $tradies = Tradie::factory()->forTenant($tenant)->withBusinessNumber()->count(3)->create();

        // Seed some jobs for each tradie
        foreach ($tradies as $tradie) {
            Job::factory()->assigned($tradie)->count(2)->create();
            Job::factory()->completed($tradie)->count(3)->create();
        }

        // A few unassigned AI-created jobs waiting for dispatcher attention
        Job::factory()->aiCreated()->count(4)->create(['tenant_id' => $tenant->id]);

        $this->command->info("Seeded tenant: {$tenant->name}");
        $this->command->info("Admin login: admin@acepiping.com / password");
        $this->command->info("Tradies seeded: {$tradies->count()}");
    }
}
