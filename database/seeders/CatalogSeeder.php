<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['AGUA MINERAL 510 ML COM GÁS', 3.00, 'BEBIDA', 1.49, 5, null],
            ['AGUA MINERAL 510 ML SEM GÁS', 2.50, 'BEBIDA', 1.09, 10, null],
            ['AMSTEL PURO MALTE 350 ML', 7.00, 'BEBIDA', 3.99, 5, null],
            ['ANTARTICA ORIGINAL 300 ML', 6.00, 'BEBIDA', 3.39, 5, '7891149000005'],
            ['BRAHMA 300 ML', 5.00, 'BEBIDA', 2.79, 5, '7891149010103'],
            ['BRAHMA 350 ML', 6.00, 'BEBIDA', 3.75, 5, null],
            ['BUDWEISER 300 ML', 6.00, 'BEBIDA', 3.00, 10, null],
            ['COCA COLA 1 LT LS', 8.00, 'BEBIDA', 3.99, 5, null],
            ['COCA COLA 220 ML', 4.00, 'BEBIDA', 2.00, 5, null],
            ['COCA COLA 290 ML KS', 5.00, 'BEBIDA', 2.45, 5, null],
            ['COCA COLA 350 ML', 6.00, 'BEBIDA', 3.65, 5, null],
            ['COCA COLA ZERO 350 ML', 6.50, 'BEBIDA', 3.55, 5, null],
            ['DEL VALLE FRUT LARANJA 450 ML', 4.50, 'BEBIDA', 2.80, 5, null],
            ['DEL VALLE FRUT UVA 1 LT', 9.00, 'BEBIDA', 4.50, 5, null],
            ['FANTA LARANJA 290 ML KS', 5.00, 'BEBIDA', 2.45, 5, null],
            ['FANTA LARANJA 600 ML', 8.00, 'BEBIDA', 3.90, 5, null],
            ['HEINEKEN 350 ML', 8.00, 'BEBIDA', 4.50, 5, null],
            ['SKOL 269 ML', 4.50, 'BEBIDA', 3.00, 5, null],
            ['SKOL 300 ML', 5.00, 'BEBIDA', 2.79, 5, null],
            ['SKOL 350 ML', 6.00, 'BEBIDA', 2.89, 5, null],
            ['ESPETINHO DE CARNE', 8.00, 'ESPETINHO', 4.00, 5, null],
            ['ESPETINHO DE CARNE DE PORCO', 8.00, 'ESPETINHO', 4.00, 5, null],
            ['ESPETINHO DE CORAÇÃO DE FRANGO', 8.00, 'ESPETINHO', 2.53, 10, null],
            ['ESPETINHO DE FRANGO', 8.00, 'ESPETINHO', 3.50, 5, null],
            ['ESPETINHO DE KAFTA', 8.00, 'ESPETINHO', 0.00, 5, null],
            ['ESPETINHO DE LINGUIÇA', 8.00, 'ESPETINHO', 4.50, 5, null],
            ['ESPETINHO DE PÃO DE ALHO', 8.00, 'ESPETINHO', 2.00, 5, null],
            ['ESPETINHO DE QUEIJO COALHO', 8.00, 'ESPETINHO', 3.50, 7, null],
            ['AMENDOIM SALGADO', 3.00, 'OUTROS', 1.50, 5, null],
            ['COXINHA COM REQUEIJÃO', 7.00, 'OUTROS', 4.10, 10, null],
            ['COXINHA DE CALABRESA', 7.00, 'OUTROS', 4.10, 10, null],
            ['COXINHA DE CARNE SECA', 8.00, 'OUTROS', 5.10, 10, null],
            ['COXINHA DE COSTELA', 8.00, 'OUTROS', 5.10, 10, null],
            ['DIVERSOS', 0.00, 'OUTROS', 0.00, 0, null],
            ['DOSE', 0.00, 'OUTROS', 0.00, 10, null],
            ['PEPE LEGAL', 4.00, 'OUTROS', 2.00, 3, null],
            ['PURURUCA', 4.00, 'OUTROS', 2.00, 5, null],
            ['SALGADINHO KELECK', 3.00, 'OUTROS', 1.09, 10, null],
            ['SALGADOS', 6.00, 'OUTROS', 4.00, 2, null],
            ['TORCIDA', 4.00, 'OUTROS', 2.29, 5, null],
        ];

        foreach ($products as [$name, $price, $category, $cost, $minStock, $barcode]) {
            $exists = DB::table('products')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            DB::table('products')->insert([
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'active' => 1,
                'cost_price' => $cost,
                'stock_quantity' => 30,
                'min_stock' => $minStock,
                'barcode' => $barcode,
            ]);
        }

        foreach (['sla_yellow' => '15', 'sla_red' => '20'] as $key => $value) {
            $exists = DB::table('settings')->where('setting_key', $key)->exists();
            if (! $exists) {
                DB::table('settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                ]);
            }
        }
    }
};
