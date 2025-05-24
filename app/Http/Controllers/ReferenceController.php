<?php

namespace App\Http\Controllers;

use App\Models\ProductCard;
use App\Models\ProductSubCard;
use App\Models\Provider;
use App\Models\Unit_measurement;
use App\Models\Address;
use App\Models\AdminCashes;
use App\Models\Expense;
use App\Models\FinancialElement;
use App\Models\User;

use App\Models\ExpenseName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;


class ReferenceController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $term = trim($request->query('search', ''));
        $has  = $term !== '';

        /* cards + subCards */
        $cards = collect();
        if ($this->can(1114)) {
            $cards = ProductCard::with(['subCards:id,product_card_id,name'])
                ->when($has, fn ($q) =>
                    $q->where('name_of_products', 'like', "%{$term}%")
                      ->orWhere('description',      'like', "%{$term}%")
                )
                ->orderBy('name_of_products')
                ->get()
                ->map(fn ($c) => [
                    'id'          => $c->id,
                    'name'        => $c->name_of_products,
                    'description' => $c->description,
                    'country'     => $c->country,
                    'type'        => $c->type,
                    'photo_url'   => $c->photo_product ? URL::to('storage/'.$c->photo_product) : null,
                    'sub_cards'   => $c->subCards
                        ->when($has, fn ($col) => $col->where('name', 'like', "%{$term}%"))
                        ->map(fn ($s) => [
                            'id'   => $s->id,
                            'name' => $s->name,
                        ]),
                ]);
        }

        $subcards = $this->can(1115)
            ? ProductSubCard::select('id','product_card_id','name')
                  ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                  ->orderBy('name')
                  ->get()
            : collect();

        $units = $this->can(1116)
            ? Unit_measurement::select('id','name','tare')
                  ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                  ->orderBy('name')
                  ->get()
            : collect();

        $providers = $this->can(1117)
            ? Provider::select('id','name')
                  ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                  ->orderBy('name')
                  ->get()
            : collect();

        $addresses = $this->can(1201)
            ? Address::select('id','name')
                  ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                  ->orderBy('name')
                  ->get()
            : collect();

        /* ---- новые: expense_names ---- */
        $expenses = $this->can(1119)
            ? ExpenseName::select('id','name')
                  ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                  ->orderBy('name')
                  ->get()
            : collect();

        $financial_elements = ($this->can(1202) || $this->can(1203))
            ? FinancialElement::when($has, fn ($q) => $q->where('name','like',"%{$term}%"))->get()
            : collect();

        return response()->json(compact(
            'cards','subcards','units','providers','addresses','expenses','financial_elements'
        ));
    }

    /**
 * Есть ли у пользователя permission с данным code?
 */
private function can(int $code): bool
    {
        $u = auth()->user();
        return $u->allPermissions()->contains(fn ($p) => (int)$p->code === $code);
    }



    public function cashbox(): JsonResponse
    {
        /* ① product cards + nested sub-cards (eager-loaded) */
        $financial_element = FinancialElement::all();

        return response()->json(compact('financial_element',));
    }

    public function getReferencesByType(Request $request, string $type): JsonResponse
    {
        $term = trim($request->query('search', $request->query('q', '')));
        $has  = $term !== '';

        switch ($type) {
            case 'productCard':
                return response()->json(
                    ProductCard::with('subCards:id,product_card_id,name')
                        ->when($has, fn ($q) =>
                            $q->where('name_of_products','like',"%{$term}%")
                              ->orWhere('description','like',"%{$term}%")
                        )
                        ->orderBy('name_of_products')
                        ->get()
                        ->map(fn ($c) => [
                            'id'          => $c->id,
                            'name'        => $c->name_of_products,
                            'description' => $c->description,
                            'country'     => $c->country,
                            'type'        => $c->type,
                            'photo_url'   => $c->photo_product ? URL::to('storage/'.$c->photo_product) : null,
                            'sub_cards'   => $c->subCards
                                                ->when($has, fn ($col) => $col->where('name','like',"%{$term}%"))
                                                ->map(fn ($s) => ['id'=>$s->id,'name'=>$s->name]),
                        ])
                );

            case 'subproductCard':
                return response()->json(
                    ProductSubCard::select('id','product_card_id','name')
                        ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                        ->orderBy('name')
                        ->get()
                );

            case 'unit':
                return response()->json(
                    Unit_measurement::select('id','name','tare')
                        ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                        ->orderBy('name')
                        ->get()
                );

            case 'provider':
                return response()->json(
                    Provider::select('id','name')
                        ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                        ->orderBy('name')
                        ->get()
                );

            case 'address':
                return response()->json(
                    Address::select('id','name','city','street','house','additional')
                        ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                        ->orderBy('name')
                        ->get()
                );

            case 'expense':
                return response()->json(
                    ExpenseName::select('id','name')
                        ->when($has, fn ($q) => $q->where('name','like',"%{$term}%"))
                        ->orderBy('name')
                        ->get()
                );

            default:
                return response()->json(['error'=>"Unknown reference type '{$type}'"],400);
        }
    }
    public function store(Request $request, string $type): JsonResponse
    {
        DB::beginTransaction();
        try {
            switch ($type) {
                case 'productCard':
                    $data = $request->validate([
                        'name_of_products' => 'required|string',
                        'description'      => 'nullable|string',
                        'country'          => 'nullable|string',
                        'type'             => 'nullable|string',
                        'photo_product'    => 'nullable|image|max:2048',
                    ]);
                    if ($request->hasFile('photo_product')) {
                        $data['photo_product'] = $request->file('photo_product')->store('products','public');
                    }
                    $model = ProductCard::create($data);
                    break;

                case 'subproductCard':
                    $model = ProductSubCard::create(
                        $request->validate([
                            'product_card_id' => 'required|uuid|exists:product_cards,id',
                            'name'            => 'required|string',
                        ])
                    );
                    break;

                case 'provider':
                    $model = Provider::create($request->validate(['name'=>'required|string']));
                    break;

                case 'unit':
                    $model = Unit_measurement::create($request->validate([
                        'name' => 'required|string|unique:unit_measurements,name',
                        'tare' => 'nullable|numeric',
                    ]));
                    break;

                case 'address':
                    $model = Address::create($request->validate([
                        'name'       => 'required|string',
                        'city'       => 'nullable|string',
                        'street'     => 'nullable|string',
                        'house'      => 'nullable|string',
                        'additional' => 'nullable|string',
                    ]));
                    break;

                case 'expense':
                    $model = ExpenseName::create($request->validate([
                        'name' => 'required|string|unique:expense_names,name',
                    ]));
                    break;

                default:
                    return response()->json(['error'=>'Invalid reference type.'],400);
            }
            DB::commit();
            return response()->json($model,201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }


    public function bulkStore(Request $request): JsonResponse
{
    // Log::info($request->all());                // shows raw inputs

    /* ──────────────────────────────────────────────────────────────
     | 1.  Mobile app sends: payload = '[{…}]'
     |     Decode and replace the request body **before** validation
     *────────────────────────────────────────────────────────────── */
    if ($request->filled('payload')) {
        $decoded = json_decode($request->input('payload'), true);

        if (!is_array($decoded)) {
            return response()->json([
                'success' => false,
                'error'   => 'Malformed JSON in payload field'
            ], 422);
        }

        // attach uploaded image to first productCard (if any)
        if ($request->hasFile('photo_product')) {
            foreach ($decoded as &$row) {
                if (($row['type'] ?? '') === 'productCard') {
                    $row['data']['photo_product'] = $request->file('photo_product');
                    break;
                }
            }
            unset($row);
        }

        /*  Replace the internal parameter bag so ->validate() below
            will see exactly the array we need.                       */
        $request->replace($decoded);
    }

    /* ──────────────────────────────────────────────────────────────
     | 2.  Validate the (now correct) array
     *────────────────────────────────────────────────────────────── */
    $payload = $request->validate([
        '*.type'               => ['required', Rule::in([
            'productCard','subproductCard','provider','unit','address','expense'
        ])],
        '*.data'               => 'required|array',

        // BEFORE (too strict for the string "null")
        // '*.data.photo_product' => 'sometimes|file|image|max:2048',

        // AFTER  – accept anything, we’ll verify inside case 'productCard'
        '*.data.photo_product' => 'sometimes',
    ]);


    /* 3–the rest of your original code … -------------------------- */
    $orgId   = $request->user()->organization_id ?? null;
    $created = [];

    DB::beginTransaction();
    try {
        foreach ($payload as $entry) {
            $type = $entry['type'];
            $data = $entry['data'] + ['organization_id' => $orgId];

            switch ($type) {
                case 'productCard':
                    /* attach “photo_product” fix for string "null" */
                    if (($data['photo_product'] ?? null) === 'null') {
                        unset($data['photo_product']);
                    }

                    $validated = Validator::make($data, [
                        'name_of_products' => 'required|string',
                        'description'      => 'nullable|string',
                        'country'          => 'nullable|string',
                        'type'             => 'nullable|string',
                        'photo_product'    => 'nullable|file|image|max:2048',
                        'organization_id'  => 'nullable|uuid',
                    ])->validate();

                    if (isset($validated['photo_product']) &&
                        $validated['photo_product'] instanceof UploadedFile) {

                        $validated['photo_product'] =
                            $validated['photo_product']->store('products','public');
                    }

                    $created[] = ProductCard::create($validated);
                    break;

                /*  ---- other cases stay unchanged ---- */
                case 'subproductCard':
                    $created[] = ProductSubCard::create(Validator::validate($data, [
                        'product_card_id' => 'required|uuid|exists:product_cards,id',
                        'name'            => 'required|string',
                        'organization_id' => 'nullable|uuid',
                    ]));
                    break;

                case 'provider':
                    $created[] = Provider::create(Validator::validate($data, [
                        'name'            => 'required|string',
                        'organization_id' => 'nullable|uuid',
                    ]));
                    break;

                case 'unit':
                    $created[] = Unit_measurement::create(Validator::validate($data, [
                        'name'            => 'required|string|unique:unit_measurements,name',
                        'tare'            => 'nullable|numeric',
                        'organization_id' => 'nullable|uuid',
                    ]));
                    break;

                case 'address':
                    $created[] = Address::create(Validator::validate($data, [
                        'name'        => 'required|string',
                        'city'        => 'nullable|string',
                        'street'      => 'nullable|string',
                        'house'       => 'nullable|string',
                        'additional'  => 'nullable|string',
                        'organization_id' => 'nullable|uuid',
                    ]));
                    break;

                case 'expense':
                    $created[] = Expense::create(Validator::validate($data, [
                        'name'            => 'required|string',
                        'amount'          => 'nullable|numeric',
                        'organization_id' => 'nullable|uuid',
                    ]));
                    break;
            }
        }

        DB::commit();
        return response()->json($created, 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('bulkStore error', ['msg'=>$e->getMessage()]);
        return response()->json(['error'=>$e->getMessage()], 500);
    }
}


public function bulkUpdate(Request $request): JsonResponse
{
    /* ---------- 0. Нормализация входа ---------- */
    $decoded = null;
    $file    = null;

    if ($request->filled('payload')) {                               // multipart + payload
        $decoded = json_decode($request->input('payload'), true);
        $file    = $request->file('photo_product');

    } elseif ($request->isJson() && is_array($request->json('payload'))) {
        $decoded = $request->json('payload');                        // raw JSON {payload:[…]}

    } elseif ($request->isJson()) {
        $decoded = json_decode($request->getContent(), true);        // raw JSON […]

    } elseif ($request->has('data.formData._parts')) {               // React-Native
        foreach ($request->input('data.formData._parts') as [$k, $v]) {
            if ($k === 'payload') $decoded = json_decode($v, true);
            if ($k === 'photo_product')
                return response()->json(['error'=>'photo_product must be multipart file'],422);
        }
    }

    if (!is_array($decoded))
        return response()->json(['error'=>'Payload missing / malformed'],422);

    if ($file) {                                                     // вставляем UploadedFile
        foreach ($decoded as &$row) {
            if (($row['type'] ?? '') === 'productCard') {
                $row['data']['photo_product'] = $file;
                break;
            }
        } unset($row);
    }

    $request->replace($decoded);   // чтобы validator видел наш массив

    /* ---------- 1. Верхний уровень ---------- */
    $payload = $request->validate([
        '*.id'                 => ['required','uuid'],
        '*.type'               => ['required','string'],
        '*.data'               => ['required','array'],
        '*.data.photo_product' => ['sometimes'],
    ]);

    /* ---------- 2. Карта моделей ---------- */
    $map = [
        'productCard'    => ProductCard::class,
        'subproductCard' => ProductSubCard::class,
        'provider'       => Provider::class,
        'unit'           => Unit_measurement::class,
        'address'        => Address::class,
        'expense'        => Expense::class,
    ];

    $orgId   = $request->user()->organization_id ?? null;
    $updated = [];

    /* ---------- 3. Обновление ---------- */
    DB::beginTransaction();
    try {
        foreach ($payload as $row) {

            $type = $row['type'];
            if (!isset($map[$type]))
                throw new \RuntimeException("Unknown type: {$type}");

            /** @var \Illuminate\Database\Eloquent\Model $Model */
            $Model = $map[$type];

            $q = $Model::query();
            if ($orgId && Schema::hasColumn($Model::make()->getTable(),'organization_id')) {
                $q->where(function ($q) use ($orgId) {
                    $q->whereNull('organization_id')
                      ->orWhere('organization_id', $orgId);
                });
            }

            $model = $q->findOrFail($row['id']);
            $data  = $row['data'];

            /* ----- правила валидации (PHP-7 — только switch) ----- */
            switch ($type) {
                case 'productCard':
                    $rules = [
                        'name_of_products' => 'sometimes|string|max:255',
                        'description'      => 'sometimes|nullable|string|max:255',
                        'country'          => 'sometimes|nullable|string|max:255',
                        'type'             => 'sometimes|nullable|string|max:255',
                        'photo_product'    => 'sometimes|file|image|max:2048',
                    ];
                    break;

                case 'subproductCard':
                    $rules = [
                        'product_card_id' => [
                            'sometimes','uuid',
                            Rule::exists('product_cards','id')
                                ->where(function ($q) use ($orgId) {
                                    if ($orgId) $q->where('organization_id',$orgId);
                                }),
                        ],
                        'name' => [
                            'sometimes','string','max:255',
                            Rule::unique('product_sub_cards','name')
                                ->where(function ($q) use ($orgId) {
                                    if ($orgId) $q->where('organization_id',$orgId);
                                })->ignore($model->id),
                        ],
                    ];
                    break;

                case 'provider':
                case 'address':
                    $rules = [
                        'name' => 'sometimes|string|max:255',
                    ];
                    break;

                case 'unit':
                    $rules = [
                        'name' => [
                            'sometimes','string','max:255',
                            Rule::unique('unit_measurements','name')->ignore($model->id),
                        ],
                        'tare' => 'sometimes|nullable|numeric|min:0',
                    ];
                    break;

                default:            // expense
                    $rules = [
                        'name'   => 'sometimes|string|max:255',
                        'amount' => 'sometimes|nullable|numeric|min:0',
                    ];
            }

            $v = validator($data, $rules)->validate();

            if ($type === 'productCard' && isset($v['photo_product'])) {
                /** @var \Illuminate\Http\UploadedFile $img */
                $img = $v['photo_product'];
                if ($model->photo_product)
                    Storage::disk('public')->delete($model->photo_product);

                $v['photo_product'] = $img->store('products','public');
            }

            if ($v) $model->update($v);
            $updated[] = $model->fresh();
        }

        DB::commit();
        return response()->json(['updated'=>$updated],200);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('bulkUpdate', ['msg'=>$e->getMessage()]);
        return response()->json(['error'=>$e->getMessage()],500);
    }
}




public function bulkDestroy(Request $request): JsonResponse
{
    /**
     * DELETE /api/reference
     * Body (raw → JSON):
     * [
     *   { "id": 15, "type": "productCard"    },
     *   { "id": 48, "type": "subproductCard" },
     *   { "id":  7, "type": "provider"       }
     * ]
     */
    $payload = $request->validate([
        '*.id'   => 'required|uuid|min:1',
        '*.type' => 'required|string',
    ]);

    $classMap = [
        'productCard'     => \App\Models\ProductCard::class,
        'subproductCard'  => \App\Models\ProductSubCard::class,
        'provider'        => \App\Models\Provider::class,
        'unit'            => \App\Models\Unit_measurement::class,
        'address'         => \App\Models\Address::class,
        'expense'         => \App\Models\Expense::class,
    ];

    $orgId      = $request->user()->organization_id ?? null;
    $deletedIds = [];

    DB::beginTransaction();
    try {
        foreach ($payload as $entry) {
            $id   = $entry['id'];
            $type = $entry['type'];

            if (! isset($classMap[$type])) {
                throw new \RuntimeException("Unknown reference type: {$type}");
            }

            /** @var \Illuminate\Database\Eloquent\Model $modelClass */
            $modelClass = $classMap[$type];
            $builder    = $modelClass::query();

            // apply organization filter only if the table actually has the column
            if ($orgId !== null &&
                Schema::hasColumn($modelClass::make()->getTable(), 'organization_id')) {

                $builder->where('organization_id', $orgId);
            }

            $model = $builder->findOrFail($id);
            $model->delete();

            $deletedIds[] = ['id' => $id, 'type' => $type];
        }

        DB::commit();
        return response()->json(['deleted' => $deletedIds], 200);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('bulkDestroy', ['msg' => $e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

// app/Http/Controllers/ReferenceController.php
public function destroyOne(Request $request, string $type, string $id): JsonResponse
{
    $classMap = [
        'productCard'     => \App\Models\ProductCard::class,
        'subproductCard'  => \App\Models\ProductSubCard::class,
        'provider'        => \App\Models\Provider::class,
        'unit'            => \App\Models\Unit_measurement::class,
        'address'         => \App\Models\Address::class,
        'expense'         => \App\Models\Expense::class,
    ];

    if (!isset($classMap[$type])) {
        return response()->json(['error' => 'Unknown reference type'], 400);
    }

    $orgId      = $request->user()->organization_id ?? null;
    $modelClass = $classMap[$type];

    $query = $modelClass::query();
    if ($orgId !== null &&
        Schema::hasColumn($modelClass::make()->getTable(), 'organization_id')) {
        $query->where('organization_id', $orgId);
    }

    try {
        $query->findOrFail($id)->delete();
        return response()->json(['success' => true], 200);

    } catch (\Throwable $e) {
        Log::error('destroyOne', ['msg'=>$e->getMessage()]);
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // 1) Fetch data by type
    // старая ветка
    public function fetch(Request $request, string $type)
{
    try {
        /** @var \App\Models\User $user */
        $user = $request->user();                   // авторизованный
        $org  = $user->organization_id;           // UUID или null

        // маленький хелпер-колбек
        $scope = fn ($q) => $q->whereNull('organization_id')
                              ->orWhere('organization_id', $org);

        switch ($type) {

            /* ------ карточка товара ------ */
            case 'cash':
                case 'cashbox':
                    return response()->json(
                        AdminCashes::where('admin_id', $user->id)->get()
                        //  если нужно видеть ВСЕ счета организации:
                        // AdminCash::where($scope)->get()
                    );
            case 'productCard':
                $products = ProductCard::where($scope)->get()
                    ->map(function ($p) {
                        $p->photo_url = $p->photo_product
                            ? url('storage/' . $p->photo_product)
                            : null;
                        return $p;
                    });
                return response()->json($products);

            /* ------ под-карточка ------ */
            case 'subproductCard':
                $subs = ProductSubCard::where($scope)->get();
                return response()->json($subs);

            /* ------ поставщик ------ */
            case 'provider':
                return response()->json(
                    Provider::where($scope)->get()
                );

            /* ------ ед. измерения ------ */
            case 'unit':
                return response()->json(
                    Unit_measurement::where($scope)->get()
                );

            /* ------ адрес ------ */
            case 'address':
                return response()->json(
                    Address::where($scope)->get()
                );

            /* ------ доп. расход ------ */
            case 'expense':
                return response()->json(
                    Expense::where($scope)->get()
                );

            /* ------ неверный тип ------ */
            default:
                return response()->json(
                    ['error' => 'Invalid reference type.'],
                    400
                );
        }

    } catch (\Throwable $e) {
        report($e);
        return response()->json(
            ['error' => $e->getMessage()],
            500
        );
    }
}

    // старая ветка


    public function update(Request $request, $type, $id)
    {
        try {
            switch ($type) {
                case 'productCard':
                    $model = ProductCard::findOrFail($id);
                    $validatedData = $request->validate([
                        'name_of_products' => 'required|string',
                        'description'      => 'nullable|string',
                        'country'          => 'nullable|string',
                        'type'             => 'nullable|string',
                        'photo_product'    => 'nullable'
                    ]);

                    if ($request->hasFile('photo_product')) {
                        $path = $request->file('photo_product')->store('products', 'public');
                        $validatedData['photo_product'] = $path;
                    }

                    $model->update($validatedData);
                    return response()->json($model, 200);

                    case 'subproductCard':
                        $model = ProductSubCard::findOrFail($id);

                        $orgId = $request->user()->organization_id;

                        $validatedData = $request->validate([
                            'product_card_id' => [
                                'required',
                                'uuid',
                                Rule::exists('product_cards','id')
                                    ->where('organization_id', $orgId),
                            ],
                            'name' => [
                                'required',
                                'string',
                                'max:255',
                                Rule::unique('product_sub_cards','name')
                                    ->where('organization_id', $orgId)
                                    ->ignore($id),
                            ],
                            /*  добавляем числовые поля, если нужно их хранить  */
                            'brutto' => ['nullable','numeric','min:0'],
                            'netto'  => ['nullable','numeric','min:0'],
                        ], [
                            'name.unique' => 'Подкарточка с таким именем уже существует.',
                        ]);
                        break;

                    case 'provider':
                    $model = Provider::findOrFail($id);
                    $validatedData = $request->validate([
                        'name' => 'required|string',
                    ]);
                    break;

                case 'unit':
                    // HERE is the ONLY change: we added unique validation.
                    $model = Unit_measurement::findOrFail($id);

                    $validatedData = $request->validate([
                        'name' => [
                            'required',
                            'string',
                            "unique:unit_measurements,name,{$id},id"
                        ],
                        'tare' => 'nullable|numeric',
                    ], [
                        'name.unique' => 'Единица измерения с таким наименованием уже существует.',
                    ]);
                    break;

                case 'address':
                    $model = Address::findOrFail($id);
                    $validatedData = $request->validate([
                        'name' => 'required|string',
                    ]);
                    break;

                case 'expense':
                    $model = Expense::findOrFail($id);
                    $validatedData = $request->validate([
                        'name'   => 'required|string',
                        'amount' => 'nullable|numeric',
                    ]);
                    break;
                case 'cash':
                    case 'cashbox':
                        $model = AdminCashes::findOrFail($id);

                        // только владелец или суперадмин может менять счёт
                        $user = $request->user();
                        if ($model->admin_id !== $user->id && !$user->hasRole('superadmin')) {
                            return response()->json(['message' => 'Forbidden'], 403);
                        }

                        $validatedData = $request->validate([
                            'name' => 'required|string|max:255',
                        ]);
                        // IBAN не меняем здесь
                        break;
                default:
                    return response()->json(['error' => 'Invalid reference type.'], 400);
            }

            // Update the found model with validated data
            $model->update($validatedData);
            return response()->json($model, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fetchOne($type, $id)
{

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

    // app/Http/Controllers/ReferenceController.php

   public function counterparties(Request $request): JsonResponse
    {
        $onlyOwn = $request->query('scope') === 'own';          // ?scope=own
        $orgId   = optional($request->user())->organization_id; // может быть null (guest)

        /* --------------------------------------------------------------
         * helper-замыкание: если             scope=own   И   в таблице
         * есть колонка organization_id  → добавляем where().
         * ------------------------------------------------------------ */
        $scoped = static function (string $table) use ($onlyOwn, $orgId) {
            $q = DB::table($table);

            if ($onlyOwn && $orgId && Schema::hasColumn($table, 'organization_id')) {
                $q->where("$table.organization_id", $orgId);
            }

            return $q;
        };

        /* ─────────── 1. Клиенты (роль client) ─────────── */
        $clients = User::selectRaw("
                        users.id,
                        CONCAT(users.first_name,' ',users.last_name) AS name,
                        'client' AS type
                    ")
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->join('roles',     'roles.id',          '=', 'role_user.role_id')
                    ->where('roles.name', 'client')
                    ->when($onlyOwn && $orgId,
                           fn ($q) => $q->where('users.organization_id', $orgId));

        /* ─────────── 2. Поставщики ────────────────────── */
        $providers = $scoped('providers')
            ->selectRaw("providers.id, providers.name, 'provider' AS type");

        /* ─────────── 3. Организации ───────────────────── */
        $orgs = $scoped('organizations')
            ->selectRaw("organizations.id, organizations.name, 'organization' AS type");

        /* ─── 4. Один SQL с UNION ALL и сортировкой по name ─── */
        $union = $clients
                    ->unionAll($providers)
                    ->unionAll($orgs);

        $result = DB::query()
                    ->fromSub($union, 't')
                    ->orderBy('name')
                    ->get();

        return response()->json($result);
    }
    // new Expense version
public function listExpenseNames(Request $request)
{
    $org = $request->user()->organization_id;

    return ExpenseName::whereNull('organization_id')
                      ->orWhere('organization_id', $org)
                      ->orderBy('name')
                      ->get();
}

// ---------- создать ----------
public function storeExpenseName(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $data['organization_id'] = $request->user()->organization_id;

    return ExpenseName::create($data);
}

// ---------- обновить ----------
public function updateExpenseName(Request $request, $id)
{
    $row = ExpenseName::findOrFail($id);

    $row->update(
        $request->validate(['name' => 'required|string|max:255'])
    );

    return $row;
}

// ---------- удалить ----------
public function destroyExpenseName($id)
{
    ExpenseName::findOrFail($id)->delete();

    return response()->noContent();
}
}