<?php

namespace Database\Seeders;

use App\Models\MarketplaceChannel;
use Illuminate\Database\Seeder;

class MarketplaceChannelSeeder extends Seeder
{
    /**
     * Master data channel marketplace yang didukung platform ini.
     * Data ini adalah referensi tetap; tambah channel baru lewat seeder ini.
     */
    public function run(): void
    {
        $channels = [
            [
                'name'          => 'TikTok Shop',
                'slug'          => 'tiktok',
                'color'         => '#fe2c55',
                'bg_color'      => '#fff0f1',
                'text_color'    => '#cc0000',
                'api_base_url'  => 'https://open-api.tiktokglobalshop.com',
                'auth_type'     => 'oauth2',
                'country_codes' => 'ID,SG,MY,TH,PH,VN,GB,US',
                'is_active'     => true,
                'sort_order'    => 1,
                'notes'         => 'Integrasi via TikTok Open Platform. Token expire 24 jam.',
            ],
            [
                'name'          => 'Shopee',
                'slug'          => 'shopee',
                'color'         => '#ee4d2d',
                'bg_color'      => '#fff3f0',
                'text_color'    => '#c1361a',
                'api_base_url'  => 'https://partner.shopeemobile.com',
                'auth_type'     => 'oauth2',
                'country_codes' => 'ID,SG,MY,TH,PH,VN,TW',
                'is_active'     => true,
                'sort_order'    => 2,
                'notes'         => 'Shopee Open Platform. Partner ID & Secret Key diperlukan.',
            ],
            [
                'name'          => 'Tokopedia',
                'slug'          => 'tokopedia',
                'color'         => '#42b549',
                'bg_color'      => '#f0fff1',
                'text_color'    => '#2d7a31',
                'api_base_url'  => 'https://fs.tokopedia.net',
                'auth_type'     => 'oauth2',
                'country_codes' => 'ID',
                'is_active'     => true,
                'sort_order'    => 3,
                'notes'         => 'Tokopedia Open API. Client ID & Secret diperlukan.',
            ],
            [
                'name'          => 'Lazada',
                'slug'          => 'lazada',
                'color'         => '#0f1035',
                'bg_color'      => '#f0f0ff',
                'text_color'    => '#0f1035',
                'api_base_url'  => 'https://api.lazada.co.id/rest',
                'auth_type'     => 'oauth2',
                'country_codes' => 'ID,SG,MY,TH,PH,VN',
                'is_active'     => true,
                'sort_order'    => 4,
                'notes'         => 'Lazada Open Platform (LOP). App Key & App Secret diperlukan.',
            ],
            [
                'name'          => 'Blibli',
                'slug'          => 'blibli',
                'color'         => '#0095da',
                'bg_color'      => '#f0f8ff',
                'text_color'    => '#006fa3',
                'api_base_url'  => 'https://api.blibli.com',
                'auth_type'     => 'api_key',
                'country_codes' => 'ID',
                'is_active'     => true,
                'sort_order'    => 5,
                'notes'         => 'Blibli Seller API. Menggunakan API Key + Seller ID.',
            ],
            [
                'name'          => 'Bukalapak',
                'slug'          => 'bukalapak',
                'color'         => '#e31924',
                'bg_color'      => '#fff0f0',
                'text_color'    => '#b01218',
                'api_base_url'  => 'https://api.bukalapak.com',
                'auth_type'     => 'oauth2',
                'country_codes' => 'ID',
                'is_active'     => true,
                'sort_order'    => 6,
                'notes'         => 'Bukalapak Open API.',
            ],
            [
                'name'          => 'Zalora',
                'slug'          => 'zalora',
                'color'         => '#1a1a1a',
                'bg_color'      => '#f5f5f5',
                'text_color'    => '#1a1a1a',
                'api_base_url'  => null,
                'auth_type'     => 'api_key',
                'country_codes' => 'ID,SG,MY,TH,PH',
                'is_active'     => false,
                'sort_order'    => 7,
                'notes'         => 'Belum aktif. Integrasi melalui Zalora Seller Center API.',
            ],
            [
                'name'          => 'Shopify',
                'slug'          => 'shopify',
                'color'         => '#96bf48',
                'bg_color'      => '#f5fff0',
                'text_color'    => '#5a7a1a',
                'api_base_url'  => 'https://{shop}.myshopify.com/admin/api',
                'auth_type'     => 'oauth2',
                'country_codes' => 'GLOBAL',
                'is_active'     => false,
                'sort_order'    => 8,
                'notes'         => 'Toko online mandiri via Shopify Admin API.',
            ],
        ];

        foreach ($channels as $data) {
            MarketplaceChannel::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('✅  Marketplace channels berhasil di-seed (' . count($channels) . ' channels).');
    }
}
