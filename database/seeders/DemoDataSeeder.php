<?php

namespace Database\Seeders;

use App\Enums\PlaceStatus;
use App\Enums\UserRole;
use App\Models\Market;
use App\Models\PaymentMethod;
use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@akaguriro.bi'],
            [
                'name' => 'Super Admin AKAGURIRO',
                'phone' => '+25770000001',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $market = Market::firstOrCreate(
            ['name' => 'Marché Siyoni de Bujumbura'],
            [
                'city' => 'BUJUMBURA',
                'province' => 'BUJUMBURA',
                'commune' => 'Mukaza',
                'zone' => 'Rohero',
                'colline' => 'Jabe',
                'location' => 'Quartier Jabe, Avenue du Peuple Murundi',
                'description' => 'Le plus grand centre commercial populaire de Bujumbura.',
                'total_places' => 120,
                'occupied_places' => 0,
                'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600',
                'cover_image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200',
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'gestionnaire@akaguriro.bi'],
            [
                'name' => 'Gestionnaire du marché',
                'phone' => '+25771000001',
                'password' => Hash::make('password'),
                'managed_market_id' => $market->id,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole(UserRole::AdminMarche->value);

        $owner = User::firstOrCreate(
            ['email' => 'proprietaire@akaguriro.bi'],
            [
                'name' => 'Propriétaire du marché',
                'phone' => '+25772000001',
                'password' => Hash::make('password'),
                'managed_market_id' => $market->id,
                'email_verified_at' => now(),
            ]
        );
        $owner->assignRole(UserRole::ProprietaireMarche->value);

        PaymentMethod::firstOrCreate(
            ['market_id' => $market->id, 'name' => 'Banque de la République du Burundi'],
            [
                'type' => 'bank',
                'account_number' => 'BRB-0001',
                'account_name' => 'Marché '.$market->name,
                'is_active' => true,
            ]
        );
        PaymentMethod::firstOrCreate(
            ['market_id' => $market->id, 'name' => 'Lumicash'],
            [
                'type' => 'mobile_money',
                'account_number' => '79000001',
                'account_name' => 'Caisse '.$market->city,
                'is_active' => true,
            ]
        );

        for ($i = 1; $i <= 5; $i++) {
            Place::firstOrCreate(
                ['market_id' => $market->id, 'number' => 'A-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                [
                    'status' => PlaceStatus::Available,
                ]
            );
        }

        $merchant = User::firstOrCreate(
            ['email' => 'commercant@akaguriro.bi'],
            [
                'name' => 'Anésie Ndayishimiye',
                'phone' => '+25779384102',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $merchant->assignRole(UserRole::Commercant->value);

        $place = Place::where('market_id', $market->id)->first();
        if ($place) {
            $place->update([
                'status' => PlaceStatus::Occupied,
                'chief_user_id' => $merchant->id,
            ]);
            $market->update(['occupied_places' => 1]);
        }
    }
}
