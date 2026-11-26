<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\FreelancerProfile;
use App\Models\Lead;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $freelancers = [
            ['Farhan Freelancer', 'freelancer@fams.test', 'Full-stack Laravel developer', 25, 'available', ['Laravel', 'PHP', 'MySQL', 'Vue']],
            ['Sadia Designer', 'sadia@fams.test', 'UI/UX designer', 18, 'busy', ['Figma', 'UI Design', 'Tailwind']],
            ['Rafi Rahman', 'rafi@fams.test', 'Python and data engineer', 30, 'unavailable', ['Python', 'Django', 'Data Analysis']],
            ['Nila Chowdhury', 'nila@fams.test', 'Frontend developer', 35, 'available', ['Vue', 'React', 'Tailwind', 'Laravel']],
        ];

        foreach ($freelancers as [$name, $email, $headline, $rate, $availability, $skills]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $user->syncRoles(['freelancer']);

            $profile = FreelancerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'headline' => $headline,
                    'bio' => "{$name} works on modern web projects for agency clients.",
                    'hourly_rate' => $rate,
                    'availability' => $availability,
                ]
            );

            $profile->skills()->sync(
                collect($skills)->map(fn ($s) => Skill::firstOrCreate(['name' => $s])->id)->all()
            );
        }

        Client::firstOrCreate(['email' => 'client@fams.test'], [
            'company_name' => 'Bright Studio',
            'contact_name' => 'Imran Hossain',
            'phone' => '+8801700000000',
            'website' => 'https://brightstudio.test',
            'billing_address' => 'House 12, Road 5, Dhanmondi',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'status' => 'active',
        ]);

        $manager = User::where('email', 'manager@fams.test')->first();

        $leads = [
            ['Rahim Uddin', 'rahim@example.com', 'Rahim Traders', 'Website redesign', 'new'],
            ['Nusrat Jahan', 'nusrat@example.com', 'Nusrat Fashion', 'Online store', 'contacted'],
            ['David Lee', 'david@example.com', 'Lee Logistics', 'Custom CRM', 'qualified'],
            ['Anika Rahman', 'anika@example.com', 'Anika Foods', 'Mobile-friendly web app', 'proposal'],
            ['Tariq Aziz', 'tariq@example.com', 'Aziz Tech', 'Landing page', 'won'],
            ['Mila Khan', 'mila@example.com', null, 'SEO work', 'lost'],
        ];

        foreach ($leads as [$name, $email, $company, $service, $stage]) {
            Lead::firstOrCreate(['email' => $email], [
                'name' => $name,
                'company' => $company,
                'service' => $service,
                'message' => "Hello, we need help with: {$service}.",
                'source' => 'website',
                'stage' => $stage,
                'assigned_to' => $stage === 'new' ? null : $manager?->id,
            ]);
        }
    }
}