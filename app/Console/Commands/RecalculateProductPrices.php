<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Product\App\Services\ProductPriceCalculator;

class RecalculateProductPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:recalculate-prices {--product-id=* : Specific product IDs to recalculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate min_price and max_price for products based on their variants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productIds = $this->option('product-id');

        if (!empty($productIds)) {
            // Recalculate specific products
            ProductPriceCalculator::recalculateMultipleProducts($productIds);
            $this->info('Recalculated prices for ' . count($productIds) . ' products.');
        } else {
            // Recalculate all products
            $this->info('Recalculating prices for all products...');
            $updated = ProductPriceCalculator::recalculateAllProducts();
            $this->info("Successfully updated prices for {$updated} products.");
        }
    }
}
