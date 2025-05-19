<?php

namespace Rais\MomoSuite\Console;

use Illuminate\Console\Command;
use Rais\MomoSuite\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'momo-suite:create-admin {--default : Create a default admin user (admin@momo-suite.com / password)}';

    protected $description = 'Create a new admin user for Momo Suite';

    public function handle()
    {
        if ($this->option('default')) {
            // Check if user already exists
            if (User::where('email', 'admin@momo-suite.com')->exists()) {
                $this->error('A default admin user already exists!');
                return 1;
            }

            User::create([
                'name' => 'Admin',
                'email' => 'admin@momo-suite.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'permissions' => ['*']
            ]);

            $this->info('A default admin user is automatically created:');
            $this->info('Email: admin@momo-suite.com');
            $this->info('Password: password');
            $this->warn('Please change these credentials after first login.');
            return 0;
        }

        $name = $this->ask('What is the admin name?');
        $email = $this->ask('What is the admin email?');
        $password = $this->secret('What is the admin password?');
        $confirmPassword = $this->secret('Confirm password');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmPassword,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error('An admin with this email already exists!');
            return 1;
        }

        // Create the admin user
        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'permissions' => ['*']
        ]);

        $this->info('Admin user created successfully!');
        $this->info("Email: {$email}");

        return 0;
    }
}
