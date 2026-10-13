<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Categories
        $catJajanan = Category::firstOrCreate(['name' => 'Jajanan', 'type' => 'food']);
        $catSweets = Category::firstOrCreate(['name' => 'Sweets', 'type' => 'food']);
        $catCepatSaji = Category::firstOrCreate(['name' => 'Cepat saji', 'type' => 'food']);
        $catSembako = Category::firstOrCreate(['name' => 'Sembako', 'type' => 'mart']);
        $catMinuman = Category::firstOrCreate(['name' => 'Minuman', 'type' => 'mart']);
        $catMakanan = Category::firstOrCreate(['name' => 'Makanan', 'type' => 'mart']);
        $catRokok = Category::firstOrCreate(['name' => 'Rokok', 'type' => 'mart']);

        // 2. Merchants
        $mcd = Merchant::firstOrCreate(
            ['name' => 'McDonald\'s'],
            [
                'description' => 'I\'m lovin it',
                'address' => 'Jl. MT Haryono No. 1, Malang',
                'type' => 'food',
                'latitude' => -7.9483,
                'longitude' => 112.6138,
            ]
        );

        $belikopi = Merchant::firstOrCreate(
            ['name' => 'Belikopi'],
            [
                'description' => 'Kopi susu gula aren andalan',
                'address' => 'Jl. Soekarno Hatta, Malang',
                'type' => 'food',
                'latitude' => -7.9445,
                'longitude' => 112.6180,
            ]
        );

        $indomaret = Merchant::firstOrCreate(
            ['name' => 'Indomaret Point'],
            [
                'description' => 'Minimarket terdekat',
                'address' => 'Jl. Veteran No 8, Malang',
                'type' => 'mart',
                'latitude' => -7.9540,
                'longitude' => 112.6145,
            ]
        );

        // 3. Products
        Product::firstOrCreate(
            ['merchant_id' => $belikopi->id, 'name' => 'Paket Kopi Susu Nusantara'],
            [
                'category_id' => $catMinuman->id,
                'description' => 'Kopi susu dengan gula aren khas nusantara',
                'estimated_price' => 16875,
                'is_flash_sale' => true,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $mcd->id, 'name' => 'Paket Super Hemat 2'],
            [
                'category_id' => $catCepatSaji->id,
                'description' => 'Kebab Turki Baba Rafi + Nasi Ayam',
                'estimated_price' => 59900,
                'is_flash_sale' => true,
            ]
        );

        // Indomaret - Makanan
        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Onigiri Tuna Mayo'],
            [
                'category_id' => $catMakanan->id,
                'description' => 'Nasi kepal khas Jepang dengan isian tuna mayo gurih',
                'estimated_price' => 12000,
                'discount_price' => 10000,
                'is_flash_sale' => true,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Roti Say Bread Coklat Keju'],
            [
                'category_id' => $catMakanan->id,
                'description' => 'Roti lembut isi coklat dan keju lezat fresh dari oven',
                'estimated_price' => 9500,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Indomie Goreng Spesial'],
            [
                'category_id' => $catMakanan->id,
                'description' => 'Mie instan goreng rasa original spesial',
                'estimated_price' => 3500,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Chitato Sapi Panggang 68g'],
            [
                'category_id' => $catMakanan->id,
                'description' => 'Keripik kentang bergelombang rasa daging sapi panggang',
                'estimated_price' => 11500,
            ]
        );

        // Indomaret - Minuman
        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Ultra Milk UHT Stroberi 125ml'],
            [
                'category_id' => $catMinuman->id,
                'description' => 'Susu UHT rasa stroberi segar',
                'estimated_price' => 4400,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Le Minerale 600ml'],
            [
                'category_id' => $catMinuman->id,
                'description' => 'Air mineral pegunungan segar dengan mineral alami',
                'estimated_price' => 3500,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Nescafe Can Iced Black 220ml'],
            [
                'category_id' => $catMinuman->id,
                'description' => 'Kopi hitam siap minum dingin yang mantap',
                'estimated_price' => 7800,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Teh Pucuk Harum 350ml'],
            [
                'category_id' => $catMinuman->id,
                'description' => 'Minuman teh melati dari pucuk daun teh pilihan',
                'estimated_price' => 4000,
            ]
        );

        // Indomaret - Rokok
        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Sampoerna A Mild 16'],
            [
                'category_id' => $catRokok->id,
                'description' => 'Rokok kretek mild filter isi 16 batang',
                'estimated_price' => 35000,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Marlboro Filter Black 20'],
            [
                'category_id' => $catRokok->id,
                'description' => 'Rokok filter isi 20 batang',
                'estimated_price' => 42000,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Djarum Super 12'],
            [
                'category_id' => $catRokok->id,
                'description' => 'Rokok kretek filter Djarum Super isi 12 batang',
                'estimated_price' => 25000,
            ]
        );

        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Esse Change Juicy 20'],
            [
                'category_id' => $catRokok->id,
                'description' => 'Rokok mild dengan kapsul aroma buah segar isi 20 batang',
                'estimated_price' => 38000,
            ]
        );

        // Indomaret - Sembako
        Product::firstOrCreate(
            ['merchant_id' => $indomaret->id, 'name' => 'Prochiz Keju Cheddar'],
            [
                'category_id' => $catSembako->id,
                'description' => 'Keju cheddar olahan 160gram',
                'estimated_price' => 15300,
            ]
        );
    }
}
