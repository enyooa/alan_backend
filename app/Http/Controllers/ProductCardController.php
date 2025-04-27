<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCard;
use App\Models\Reference;
use App\Models\ReferenceItem;
use App\Services\ProductCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductCardController extends Controller
{
    protected $productCardService;

    public function __construct(ProductCardService $productCardService)
    {
        $this->productCardService = $productCardService;
    }

    /**
     * Store a new ProductCard
     */

public function store(Request $request): JsonResponse
{
    Log::info('ProductCard store endpoint hit.', ['request' => $request->all()]);

    /* ───── 1. Валидируем «плоские» поля (title, items-строка, файл) ───── */
    $firstPass = $request->validate([
        'title' => ['required','string','max:255'],
        'items' => ['required','string'],                    // <─ СТРОКА (JSON)
        'photo' => ['nullable','file','mimes:jpg,png,jpeg,gif'],
    ]);

    /* ───── 2. Превращаем items-строку в массив ───── */
    try {
        $items = json_decode($firstPass['items'], true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $e) {
        return response()->json(
            ['error' => 'Поле "items" должно быть корректным JSON-массивом'],
            422
        );
    }

    if (!is_array($items) || empty($items)) {
        return response()->json(
            ['error' => '"items" — пустой массив, ничего сохранять'],
            422
        );
    }

    /* ───── 3. Валидируем каждую под-карточку ───── */
    foreach ($items as $idx => $row) {
        $rowValidator = Validator::make(
            $row,
            [
                'name'        => ['required','string','max:255'],
                'description' => ['nullable','string'],
                'country'     => ['nullable','string','max:255'],
                'value'       => ['nullable','numeric'],
                'type'        => ['nullable','string','max:255'],
                'card_id'     => ['nullable','integer','exists:reference_items,id'],
            ],
            [],              // messages
            ["items.$idx"]   // attribute «prefix» в ошибках
        );

        if ($rowValidator->fails()) {
            throw new ValidationException($rowValidator);
        }

        // заменяем на «очищенную» версию
        $items[$idx] = $rowValidator->validated();
    }

    /* ───── 4. Фото ───── */
    $photoPath = null;
    if ($request->hasFile('photo')) {
        $photoPath = $request->file('photo')->store('products','public');
    }

    /* ───── 5. Транзакция: создаём карточку + под-карточки ───── */
    DB::beginTransaction();
    try {
        // 5-A. Header (Reference)
        $card = Reference::create([
            'title' => $firstPass['title'],
        ]);

        // 5-B. Items (ReferenceItem)
        foreach ($items as $row) {
            ReferenceItem::create(
                Arr::collapse([
                    $row,
                    [
                        'reference_id'  => $card->id,
                        'photo' => $photoPath,
                    ],
                ])
            );
        }

        DB::commit();

        return response()->json([
            'message' => 'Карточка товара успешно создана!',
            'data'    => $card->load('items'),
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Error creating product card.', ['error'=>$e->getMessage()]);
        return response()->json(['error'=>'Failed to create product card.'], 500);
    }
}


    /**
     * Get all ProductCards
     */
    // old version
    public function getCardProducts(): JsonResponse
    {
        try {
            /*  title       – заголовок карточки
             *  items[*]    – под-карточки
             *                (name, description, …, photo_url)
             */
            $cards = Reference::with('items')          // eager-load под-карточки
                      ->orderByDesc('created_at')
                      ->get();

            return response()->json($cards, 200);

        } catch (\Throwable $e) {
            return response()->json(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    // new version get product cards новая ветка для карточек товара
    public function index()
    {
        $cards = Reference::where('title', 'Карточка товара')
            ->with('items')                   // тяним reference_items
            ->orderBy('id')
            ->get()
            ->map(fn ($card) => $this->formatCard($card));

        return response()->json($cards, 200);
    }

    /* ----------------------------------------------------------
     | 2)  GET  /api/card‑products/{id}
     |     одна конкретная карточка  (аналог   /getCardProduct/3 )
     * --------------------------------------------------------*/
    public function show(int $id)
    {
        $card = Reference::where('title', 'Карточка товара')
            ->where('id', $id)
            ->with('items')
            ->firstOrFail();

        return response()->json($this->formatCard($card), 200);
    }

    /* ----------------------------------------------------------
     |  private helper ─ приводит Reference к прежнему виду
     * --------------------------------------------------------*/
    private function formatCard(Reference $card): array
    {
        return [
            'card' => [
                'id'          => $card->id,
                'title'       => $card->title,           // ≈ name_of_products
                'description' => $card->description,     // если поле есть
                'country'     => $card->country,         // если поле есть
                'type'        => $card->type,            // если поле есть
                'photo_product_url' => $card->photo_product
                        ? url('storage/' . $card->photo_product)
                        : null,
            ],

            // «под‑товары»  ─ то, что раньше было product_sub_cards
            'subcards' => $card->items->map(fn ($itm) => [
                'id'               => $itm->id,
                'reference_id'     => $itm->reference_id,  // FK
                'name'             => $itm->name,
                'description'      => $itm->description,
                'value'            => $itm->value,
                'type'             => $itm->type,
                'country'          => $itm->country,
            ])->values(),
        ];
    }

    /**
     * Update an existing ProductCard (HEAD version)
     */
    public function updateProductCard(Request $request, $id)
    {
        $productCard = ProductCard::findOrFail($id);

        $validated = $request->validate([
            'name_of_products' => 'nullable|string',
            'description'      => 'nullable|string',
            'country'          => 'nullable|string',
            'type'             => 'nullable|string',
            'photo_product'    => 'nullable|string',
        ]);

        $productCard->update($validated);

        return response()->json([
            'message' => 'Product Card updated successfully'
        ], 200);
    }

    /**
     * Delete a ProductCard
     */
    public function destroy($id)
    {
        ProductCard::destroy($id);
        return response()->json([
            'message' => 'Product Card deleted successfully'
        ], 200);
    }
}
