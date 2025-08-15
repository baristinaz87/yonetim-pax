<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'api:token {email} {token_name?}';
    protected $description = 'Create a new API token for a user';

    public function handle()
    {
        $email = $this->argument('email');
        $tokenName = $this->argument('token_name') ?? 'api-token';

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found!');
            return 1;
        }

        $token = $user->createToken($tokenName);

        $this->info('API Token created successfully!');
        $this->info('Token: ' . $token->plainTextToken);
        
        return 0;
    }
}
