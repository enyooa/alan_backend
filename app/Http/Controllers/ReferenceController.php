<?php

namespace App\Http\Controllers;

use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Unit_measurement;
use App\Models\Address;
use App\Models\Expense;
use App\Models\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReferenceController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
{
    /* 1)  тянем items + их parent‑card, чтобы не было N+1   */
    $refs = Reference::with([
                'items:id,reference_id,card_id,name,description,value,type,country',
                'items.card:id,name'                         // ← card‑объект
            ])
            ->get()
            ->map(fn ($ref) => [
                'id'         => $ref->id,
                'title'      => $ref->title,
                'created_at' => $ref->created_at->toDateTimeString(),

                /* ---------- вложенные ReferenceItem‑ы ---------- */
                'RefferenceItem' => $ref->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'reference_id' => $item->reference_id,

                    // 👇 вместо голого card_id — объект‑карточка
                    'card' => $item->card
                         ? [
                               'id'   => $item->card->id,
                               'name' => $item->card->name,
                           ]
                         : null,

                    'name'        => $item->name,
                    'description' => $item->description,
                    'value'       => $item->value,
                    'type'        => $item->type,
                    'country'     => $item->country,
                ]),
            ]);

    return response()->json(['refferences' => $refs], 200);
}

public function storeWithItems(Request $request): \Illuminate\Http\JsonResponse
{
    Log::info($request->all());
    /* 1. Получаем тело запроса */
    $payload = $request->input('refference')            // ← новый фронт
             ?? $request->all();                       // ← старый фронт

    if (!$payload) {
        return response()->json(
            ['error' => 'Missing “refference” wrapper or body is empty.'],
            422
        );
    }

    /* 2. Валидация
     *    – принимаем оба ключа  items  и  RefferenceItem
     *    – value → string  (чистим позже) */
    $validator = Validator::make($payload, [
        'title'   => ['required', 'string'],

        'items'                       => ['array'],
        'items.*.card_id'             => ['sometimes', 'nullable', 'integer'],
        'items.*.name'                => ['required_with:items', 'string'],
        'items.*.description'         => ['nullable', 'string'],
        'items.*.value'               => ['nullable', 'string'],
        'items.*.type'                => ['nullable', 'string'],
        'items.*.country'             => ['nullable', 'string'],

        'RefferenceItem'              => ['array'],
        'RefferenceItem.*.card_id'    => ['sometimes', 'nullable', 'integer'],
        'RefferenceItem.*.name'       => ['required_with:RefferenceItem', 'string'],
        'RefferenceItem.*.description'=> ['nullable', 'string'],
        'RefferenceItem.*.value'      => ['nullable', 'string'],
        'RefferenceItem.*.type'       => ['nullable', 'string'],
        'RefferenceItem.*.country'    => ['nullable', 'string'],
    ]);
    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $data  = $validator->validated();
    $items = $payload['RefferenceItem']
          ?? $payload['items']
          ?? [];

    /* 3. Транзакция */
    $reference = DB::transaction(function () use ($data, $items) {

        /** @var \App\Models\Reference $ref */
        $ref = Reference::create(['title' => $data['title']]);

        foreach ($items as $item) {
            $ref->items()->create($this->cleanItem($item));
        }

        return $ref->fresh('items');
    });

    /* 4. Ответ */
    return response()->json(
        ['refference' => $this->formatReference($reference)],
        201
    );
}

/** Убираем "NaN", пустые строки и т. п. */
private function cleanItem(array $item): array
{
    $value = $item['value'] ?? null;
    if (!is_numeric($value)) {
        $value = null;
    }

    return [
        'card_id'     => $item['card_id']     ?? null,
        'name'        => $item['name'],
        'description' => $item['description'] ?? null,
        'value'       => $value,
        'type'        => $item['type']        ?? null,
        'country'     => $item['country']     ?? null,
    ];
}

    public function updateWithItems(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $ref = Reference::with('items')->findOrFail($id);

        $validated = $request->validate([
            'title'   => ['required','string'],
            'items'                   => ['array'],
            'items.*.id'              => ['sometimes','integer'],
            'items.*.card_id' => ['nullable','integer'],

            'items.*.name'            => ['required_with:items','string'],
            'items.*.description'     => ['nullable','string'],
            'items.*.value'           => ['nullable','numeric'],
            'items.*.type'            => ['nullable','string'],
            'items.*.country'         => ['nullable','string'],

            'deleted_item_ids'        => ['array'],
            'deleted_item_ids.*'      => ['integer'],
        ]);

        DB::transaction(function () use ($ref, $validated) {
            $ref->update([
                'title'   => $validated['title'],
            ]);

            foreach ($validated['items'] ?? [] as $item) {
                $ref->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
                        'card_id'     => $item['card_id'],
                        'name'        => $item['name'],
                        'description' => $item['description'] ?? null,
                        'value'       => $item['value'] ?? null,
                        'type'        => $item['type'] ?? null,
                        'country'     => $item['country'] ?? null,
                    ]
                );
            }

            if (!empty($validated['deleted_item_ids'])) {
                $ref->items()->whereIn('id', $validated['deleted_item_ids'])->delete();
            }
        });

        return response()->json(['refference' => $this->formatReference($ref->fresh('items'))]);
    }
    public function store(Request $request, $type)   // /reference/{type}
    {
        DB::beginTransaction();

        try {
            switch ($type) {

                /* ---------- ProductCard ---------- */
                case 'productCard':
                    $data = $request->validate([
                        'name_of_products' => 'required|string',
                        'description'      => 'nullable|string',
                        'country'          => 'nullable|string',
                        'type'             => 'nullable|string',
                        'photo_product'    => 'nullable|image|max:2048', // 2 MB
                    ]);

                    if ($request->hasFile('photo_product')) {
                        $data['photo_product'] =
                            $request->file('photo_product')
                                    ->store('products', 'public');
                    }

                    $model = ProductCard::create($data);
                    break;

                /* ---------- ProductSubCard ---------- */
                case 'subproductCard':
                    $data = $request->validate([
                        'product_card_id' => 'required|integer|exists:product_cards,id',
                        'name'            => 'required|string',
                    ]);

                    $model = ProductSubCard::create($data);
                    break;

                /* ---------- Provider ---------- */
                case 'provider':
                    $data = $request->validate([
                        'name' => 'required|string',
                    ]);

                    $model = Provider::create($data);
                    break;

                /* ---------- Unit ---------- */
                case 'unit':
                    $data = $request->validate([
                        'name' => 'required|string|unique:unit_measurements,name',
                        'tare' => 'nullable|numeric',
                    ]);

                    $model = Unit_measurement::create($data);
                    break;

                /* ---------- Address ---------- */
                case 'address':
                    $data = $request->validate([
                        'name'       => 'required|string',
                        'city'       => 'nullable|string',
                        'street'     => 'nullable|string',
                        'house'      => 'nullable|string',
                        'additional' => 'nullable|string',
                    ]);

                    $model = Address::create($data);
                    break;

                /* ---------- Expense ---------- */
                case 'expense':
                    $data = $request->validate([
                        'name'   => 'required|string',
                        'amount' => 'nullable|numeric',
                    ]);

                    $model = Expense::create($data);
                    break;

                /* ---------- Unknown ---------- */
                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }

            DB::commit();
            return response()->json($model, 201);   // 201 Created

        } catch (\Throwable $e) {

            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // 1) Fetch data by type
    // старая ветка
    public function fetch($type)
    {
        try {
            switch ($type) {
                case 'productCard':
                    $products = ProductCard::all()->map(function ($product) {
                        $product->photo_url = $product->photo_product
                            ? url('storage/' . $product->photo_product)
                            : null;
                        return $product;
                    });
                    return response()->json($products, 200);

                case 'subproductCard':
                    $subCards = ProductSubCard::all();
                    return response()->json($subCards, 200);

                case 'provider':
                    $providers = Provider::all();
                    return response()->json($providers, 200);

                case 'unit':
                    $units = Unit_measurement::all();
                    return response()->json($units, 200);

                case 'address':
                    $addresses = Address::all();
                    return response()->json($addresses, 200);

                case 'expense':  // ADD THIS
                    $expenses = Expense::all();
                    return response()->json($expenses, 200);

                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // старая ветка

    public function getReferencesByType(string $type): \Illuminate\Http\JsonResponse
{
    /* -------------------------------------------------
     | 1)  Нормализуем «человекочитаемый» type → title
     |     (если нужно, добавьте свои alias’ы)
     * -----------------------------------------------*/
    $map = [
        // url‑параметр       =>  title в таблице references
        'unit'              => 'Единица измерения',
        'provider'          => 'Поставщик',
        'address'           => 'Адрес',
        'expense'           => 'Расход',
        'income'            => 'Приход',
        'product-card'      => 'Карточка товара',
        'product-subcard'   => 'Подкарточка товара',
    ];

    if (!isset($map[$type])) {
        return response()->json(
            ['error' => "Unknown reference type: $type"],
            404
        );
    }

    $title = $map[$type];

    /* -------------------------------------------------
     | 2)  Тянем саму карточку + все строки‑items
     * -----------------------------------------------*/
    $reference = Reference::where('title', $title)
        ->with('items:id,reference_id,card_id,name,description,value,type,country')
        ->orderBy('id')                 // несколько одинаковых карточек — по id
        ->get()
        ->map(fn ($ref) => $this->formatReference($ref));

    /* -------------------------------------------------
     | 3)  Ответ
     * -----------------------------------------------*/
    return response()->json([
        'refferences' => $reference      // ← оставили старое имя ключа
    ]);
}


    public function fetchOne($type, $id)
{
    Log::info($type);
    Log::info($id);
    try {
        switch ($type) {
            case 'productCard':
                $model = ProductCard::findOrFail($id);
                // Optionally add 'photo_url' if you store an image
                $model->photo_url = $model->photo_product
                    ? url('storage/' . $model->photo_product)
                    : null;
                return response()->json($model, 200);

            case 'subproductCard':
                $model = ProductSubCard::findOrFail($id);
                return response()->json($model, 200);

            case 'provider':
                $model = Provider::findOrFail($id);
                return response()->json($model, 200);
            case 'unit':
                $model = Unit_measurement::findOrFail($id);
                return response()->json($model, 200);
            case 'expense':
                $model = Expense::findOrFail($id);
                return response()->json($model, 200);
            // etc. for unit, address, expense
            default:
                return response()->json(['error' => 'Invalid reference type.'], 400);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // 2) Unified update method (PATCH)
    public function update(Request $request, int $id): \Illuminate\Http\JsonResponse
{
    $payload = $request->input('refference');
    if (!$payload) {
        return response()->json(['error' => 'Missing “refference” wrapper'], 422);
    }

    /* ---------- 1.  validate ---------- */
    $validator = Validator::make($payload, [
        'title'   => ['required','string'],

        'RefferenceItem'                  => ['array'],
        'RefferenceItem.*.id'             => ['sometimes','integer'],

        //  ❱❱  mark card_id as sometimes|nullable|integer
        'RefferenceItem.*.card_id'        => ['sometimes','nullable','integer'],

        'RefferenceItem.*.name'           => ['required_with:RefferenceItem','string'],
        'RefferenceItem.*.description'    => ['nullable','string'],
        'RefferenceItem.*.value'       => ['nullable','string'],
        'RefferenceItem.*.type'           => ['nullable','string'],
        'RefferenceItem.*.country'        => ['nullable','string'],

        'deleted_item_ids'                => ['array'],
        'deleted_item_ids.*'              => ['integer'],
    ]);
    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $data        = $validator->validated();
    $items       = $data['RefferenceItem']   ?? [];
    $idsToDelete = $data['deleted_item_ids'] ?? [];

    /* ---------- 2.  write atomically ---------- */
    $reference = DB::transaction(function () use ($id, $data, $items, $idsToDelete) {

        $ref = Reference::with('items')->findOrFail($id);

        /* parent */
        $ref->update([
            'title' => $data['title'],
        ]);

        /* children (upsert) */
        foreach ($items as $item) {
            $cleanValue = is_numeric($item['value'] ?? null)
                ? (float) $item['value']
                : null;

            $ref->items()->updateOrCreate(
                ['id' => $item['id'] ?? null],   // match on id if given
                [
                    'card_id'     => $item['card_id']     ?? null,   // ← safe
                    'name'        => $item['name'],
                    'description' => $item['description'] ?? null,
                    'value'       => $cleanValue       ?? null,
                    'type'        => $item['type']        ?? null,
                    'country'     => $item['country']     ?? null,
                ]
            );
        }

        /* deletes */
        if ($idsToDelete) {
            $ref->items()->whereIn('id', $idsToDelete)->delete();
        }

        return $ref->fresh('items');
    });

    /* ---------- 3.  response ---------- */
    return response()->json([
        'refference' => $this->formatReference($reference)
    ]);
}

    // 3) Destroy method (DELETE)
    public function destroy(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $ref      = Reference::with('items')->findOrFail($id);
        $payload  = $request->input('refference') ?? [];
        $idsToDel = $payload['deleted_item_ids'] ?? [];

        DB::transaction(function () use ($ref, $idsToDel) {
            if ($idsToDel) {
                $ref->items()->whereIn('id', $idsToDel)->delete();
            } else {
                $ref->delete();
            }
        });

        if ($idsToDel) {
            return response()->json(['refference' => $this->formatReference($ref->fresh('items'))]);
        }

        return response()->json(['refference' => ['id' => $id, 'deleted' => true]]);
    }

    private function formatReference(Reference $ref): array
    {
        return [
            'id'         => $ref->id,
            'title'      => $ref->title,
            'created_at' => $ref->created_at->toDateTimeString(),
            'RefferenceItem' => $ref->items->map(fn($item) => [
                'id'            => $item->id,
                'card_id'       => $item->card_id,
                'reference_id'  => $item->reference_id,
                'name'          => $item->name,
                'description'   => $item->description,
                'value'         => $item->value,
                'type'          => $item->type,
                'country'       => $item->country,
            ]),
        ];
    }

}
