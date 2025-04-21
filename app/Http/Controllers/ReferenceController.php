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
        $refs = Reference::with('items:id,reference_id,name,description,value,type,country')
            ->get()
            ->map(fn($ref) => [
                'id'            => $ref->id,
                'title'         => $ref->title,
                'created_at'    => $ref->created_at->toDateTimeString(),
                'RefferenceItem'=> $ref->items->map(fn($item) => [
                    'id'            => $item->id,
                    'reference_id'  => $item->reference_id,
                    'name'          => $item->name,
                    'description'   => $item->description,
                    'value'         => $item->value,
                    'type'          => $item->type,
                    'country'       => $item->country,
                ]),
            ]);

        return response()->json(['refferences' => $refs]);
    }

    public function storeWithItems(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info($request->all());

        $validated = $request->validate([
            'title'   => ['required','string'],
            'card_id' => ['nullable','integer'],
            'items'               => ['array'],
            'items.*.name'        => ['required_with:items','string'],
            'items.*.description' => ['nullable','string'],
            'items.*.value'       => ['nullable','numeric'],
            'items.*.type'        => ['nullable','string'],
            'items.*.country'     => ['nullable','string'],
        ]);

        $reference = DB::transaction(function () use ($validated) {
            $ref = Reference::create([
                'title'   => $validated['title'],
                'card_id' => $validated['card_id'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                $ref->items()->createMany($validated['items']);
            }

            return $ref->load('items');
        });

        return response()->json(['refference' => $this->formatReference($reference)], 201);
    }
    public function updateWithItems(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $ref = Reference::with('items')->findOrFail($id);

        $validated = $request->validate([
            'title'   => ['required','string'],
            'card_id' => ['nullable','integer'],

            'items'                   => ['array'],
            'items.*.id'              => ['sometimes','integer'],
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
                'card_id' => $validated['card_id'] ?? $ref->card_id,
            ]);

            foreach ($validated['items'] ?? [] as $item) {
                $ref->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
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

        $validator = Validator::make($payload, [
            'title'   => ['required','string'],
            'card_id' => ['nullable','integer'],

            'RefferenceItem'                => ['array'],
            'RefferenceItem.*.id'           => ['sometimes','integer'],
            'RefferenceItem.*.name'         => ['required_with:RefferenceItem','string'],
            'RefferenceItem.*.description'  => ['nullable','string'],
            'RefferenceItem.*.value'        => ['nullable','numeric'],
            'RefferenceItem.*.type'         => ['nullable','string'],
            'RefferenceItem.*.country'      => ['nullable','string'],

            'deleted_item_ids'              => ['array'],
            'deleted_item_ids.*'            => ['integer'],
        ]);
        if ($validator->fails()) return response()->json($validator->errors(),422);

        $data        = $validator->validated();
        $items       = $data['RefferenceItem']   ?? [];
        $idsToDelete = $data['deleted_item_ids'] ?? [];

        $reference = DB::transaction(function () use ($id, $data, $items, $idsToDelete) {
            $ref = Reference::with('items')->findOrFail($id);

            $ref->update([
                'title'   => $data['title'],
                'card_id' => $data['card_id'] ?? $ref->card_id,
            ]);

            foreach ($items as $item) {
                $ref->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [
                        'name'        => $item['name'],
                        'description' => $item['description'] ?? null,
                        'value'       => $item['value'] ?? null,
                        'type'        => $item['type'] ?? null,
                        'country'     => $item['country'] ?? null,
                    ]
                );
            }

            if ($idsToDelete) {
                $ref->items()->whereIn('id', $idsToDelete)->delete();
            }

            return $ref->fresh('items');
        });

        return response()->json(['refference' => $this->formatReference($reference)]);
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
