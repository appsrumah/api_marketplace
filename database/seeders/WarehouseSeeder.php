<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Seed satu warehouse default sebagai contoh.
     * Warehouse tambahan dibuat oleh user via UI.
     */
    public function run(): void
    {
        Warehouse::updateOrCreate(
            ['code' => 'GDG-UTAMA'],
            [
                'name'          => 'Gudang Utama',
                'code'          => 'GDG-UTAMA',
                'pos_outlet_id' => null,
                'address'       => null,
                'city'          => null,
                'province'      => null,
                'is_active'     => true,
                'is_default'    => true,
                'notes'         => 'Gudang default sistem. Diisi saat setup awal.',
            ]
        );

        $this->command->info('✅  Default warehouse berhasil di-seed.');
    }
}
