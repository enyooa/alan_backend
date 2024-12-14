<?php

namespace App\Services;

use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Sales;

class ProductCardService
{
    // Create a new product
    public function createProduct(array $data): ProductCard
    {
        return ProductCard::create([
            'name_of_products' => $data['name_of_products'],
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? null,
            'type' => $data['type'] ?? null,
            'brutto' => $data['brutto'] ?? null,
            'netto' => $data['netto'] ?? null,
            'photo_product' => $data['photo_product'] ?? null, // This should now have the file path
        ]);
    }

    // Create a new sales entry linked to a product
    public function createProductSubCard(array $data): ProductSubCard
    {
        return ProductSubCard::create([
            'product_id' => $data['product_id'],
            'client_id' => $data['client_id'] ?? null,
            'quantity_sold' => $data['quantity_sold'],
            'price_at_sale' => $data['price_at_sale'],
        ]);
    }
}
