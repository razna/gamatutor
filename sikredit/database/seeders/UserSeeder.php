<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const PASSWORD = 'password123';

    public function run(): void
    {
        $make = fn (string $name, string $email, Role $role, ?int $branch = null) => User::query()->create([
            'name' => $name, 'email' => $email, 'phone' => '0812'.random_int(10000000, 99999999),
            'password' => self::PASSWORD, 'role' => $role, 'branch_id' => $branch, 'email_verified_at' => now(),
        ]);

        $make('Administrator Sistem', 'admin@sikredit.test', Role::Admin);
        $make('Ir. Hendra Wijaya, M.M.', 'komite@sikredit.test', Role::Komite);

        $people = [
            'JKT01' => ['jkt', ['Rina Marlina', 'Bagus Prasetyo', 'Dewi Kartika, S.E.', 'Andi Saputra, S.H.', 'Sinta Maharani', 'Yoga Pratama']],
            'BDG01' => ['bdg', ['Asep Sunandar', 'Nina Herlina', 'Dadang Hermawan, S.E.', 'Lilis Suryani, S.H.', 'Rudi Kurniawan', 'Teguh Santoso']],
            'SBY01' => ['sby', ['Fitri Ayu', 'Eko Wahyudi', 'Hadi Susanto, S.E.', 'Ratna Sari, S.H.', 'Wawan Setiawan', 'Joko Purnomo']],
            'YOG01' => ['yog', ['Sri Lestari', 'Bayu Aji', 'Agus Riyanto, S.E.', 'Wulan Dari, S.H.', 'Putri Ayuningtyas', 'Gilang Ramadhan']],
        ];
        $roles = [Role::CustomerService, Role::Analis, Role::KepalaCabang, Role::Legal, Role::Teller, Role::Collection];
        $slugs = ['cs', 'analis', 'kacab', 'legal', 'teller', 'collection'];

        foreach ($people as $code => [$prefix, $names]) {
            $branch = Branch::query()->where('code', $code)->value('id');
            foreach ($roles as $i => $role) {
                $make($names[$i], "{$slugs[$i]}.{$prefix}@sikredit.test", $role, $branch);
            }
        }
    }
}
