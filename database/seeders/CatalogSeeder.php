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
        $catJajanan = Category::create(['name' => 'Jajanan', 'type' => 'food']);
        $catSweets = Category::create(['name' => 'Sweets', 'type' => 'food']);
        $catCepatSaji = Category::create(['name' => 'Cepat saji', 'type' => 'food']);
        $catSembako = Category::create(['name' => 'Sembako', 'type' => 'mart']);
        $catMinuman = Category::create(['name' => 'Minuman', 'type' => 'mart']);

        // 2. Merchants
        $mcd = Merchant::create([
            'name' => 'McDonald\'s',
            'description' => 'I\'m lovin it',
            'address' => 'Jl. MT Haryono No. 1, Malang',
            'type' => 'food',
        ]);

        $belikopi = Merchant::create([
            'name' => 'Belikopi',
            'description' => 'Kopi susu gula aren andalan',
            'address' => 'Jl. Soekarno Hatta, Malang',
            'type' => 'food',
        ]);

        $indomaret = Merchant::create([
            'name' => 'Indomaret Point',
            'description' => 'Minimarket terdekat',
            'address' => 'Jl. Veteran No 8, Malang',
            'type' => 'mart',
        ]);

        // 3. Products
        Product::create([
            'merchant_id' => $belikopi->id,
            'category_id' => $catMinuman->id,
            'name' => 'Paket Kopi Susu Nusantara',
            'description' => 'Kopi susu dengan gula aren khas nusantara',
            'estimated_price' => 16875,
            'is_flash_sale' => true,
        ]);

        Product::create([
            'merchant_id' => $mcd->id,
            'category_id' => $catCepatSaji->id,
            'name' => 'Paket Super Hemat 2',
            'description' => 'Kebab Turki Baba Rafi + Nasi Ayam',
            'estimated_price' => 59900,
            'is_flash_sale' => true,
        ]);

        Product::create([
            'merchant_id' => $indomaret->id,
            'category_id' => $catSembako->id,
            'name' => 'Mie Sedaap Soto',
            'description' => 'Mie instan kuah rasa soto',
            'estimated_price' => 3000,
        ]);

        Product::create([
            'merchant_id' => $indomaret->id,
            'category_id' => $catMinuman->id,
            'name' => 'Ultra Milk UHT Stroberi 125ml',
            'description' => 'Susu UHT rasa stroberi segar',
            'estimated_price' => 4400,
        ]);
        
        Product::create([
            'merchant_id' => $indomaret->id,
            'category_id' => $catSembako->id,
            'name' => 'Prochiz Keju Cheddar',
            'description' => 'Keju cheddar olahan 160gram',
            'estimated_price' => 15300,
        ]);
    }
}
