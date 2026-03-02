<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\models\ApiToken;

class ApiTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
     {
        // Hapus token lama jika ada (opsional, untuk development)
        // ApiToken::truncate(); // Uncomment jika mau reset semua token

        // Create token untuk BotTime Application
        $token = ApiToken::createToken(
            name: 'BotTime Application',
            allowedIps: null // Bisa diisi: ['192.168.1.100', '10.0.0.50']
        );

        // Output ke console (saat run seeder)
        $this->command->info('API Token Created Successfully!');
        $this->command->newLine();
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line('📋 Token Details:');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line('Name     : ' . $token->name);
        $this->command->line('Token    : ' . $token->token);
        $this->command->line('Status   : ' . ($token->is_active ? 'Active ✓' : 'Inactive'));
        $this->command->line('IPs      : ' . ($token->allowed_ips ?? 'All IPs allowed'));
        $this->command->line('Created  : ' . $token->created_at->format('d M Y H:i:s'));
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        $this->command->warn('⚠️  IMPORTANT: Keep this token secure!');
        $this->command->warn('⚠️  he token cannot be viewed again after this.');
        $this->command->newLine();

        // Create multiple tokens (contoh untuk multiple apps)
        // ApiToken::createToken('Mobile App', ['103.127.96.10']);
        // ApiToken::createToken('Webhook Service', null);
    }
}