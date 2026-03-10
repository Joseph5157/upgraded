<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;

class ClientUserSeeder extends Seeder
{
    public function run()
    {
        $client = Client::first() ?? Client::create(['name' => 'Default Client']);

        User::create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'client_id' => $client->id,
        ]);
    }
}
