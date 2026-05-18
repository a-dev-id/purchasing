<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ItemMasterController extends Controller
{
    public function index(Request $request): View
    {
        $query = Item::query()
            ->with('photos')
            ->latest('updated_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $items = $query
            ->paginate(25)
            ->withQueryString();

        return view('purchasing.v2.items.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('purchasing.v2.items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:191'],
            'category' => ['nullable', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'default_unit' => ['nullable', 'string', 'max:191'],
            'default_specification' => ['nullable', 'string'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'in:IDR'],
            'is_active' => ['required', 'boolean'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $validated['currency'] = $validated['currency'] ?: 'IDR';
        $validated['default_unit'] = $validated['default_unit'] ?: 'pcs';

        DB::transaction(function () use ($request, $validated) {
            unset($validated['photos']);

            $item = Item::create($validated);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    if (! $photo) {
                        continue;
                    }

                    $path = $photo->store('item-photos', 'public');

                    ItemPhoto::create([
                        'item_id' => $item->id,
                        'file_path' => $path,
                        'file_name' => $photo->getClientOriginalName(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('purchasing.v2.items.index')
            ->with('success', 'Item has been added successfully.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:191'],
            'category' => ['nullable', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'default_unit' => ['nullable', 'string', 'max:191'],
            'default_specification' => ['nullable', 'string'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'in:IDR'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $item = DB::transaction(function () use ($request, $validated) {
            $item = Item::create([
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?? null,
                'category' => $validated['category'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'default_unit' => ($validated['default_unit'] ?? null) ?: 'pcs',
                'default_specification' => $validated['default_specification'] ?? null,
                'last_price' => $validated['last_price'] ?? 0,
                'currency' => ($validated['currency'] ?? null) ?: 'IDR',
                'is_active' => $request->boolean('is_active', true),
            ]);

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $path = $photo->store('item-photos', 'public');

                ItemPhoto::create([
                    'item_id' => $item->id,
                    'file_path' => $path,
                    'file_name' => $photo->getClientOriginalName(),
                ]);
            }

            return $item->fresh('photos');
        });

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'default_unit' => $item->default_unit ?: 'pcs',
                'default_specification' => $item->default_specification,
                'last_price' => (float) ($item->last_price ?? 0),
                'currency' => $item->currency ?: 'IDR',
                'photos' => $item->photos
                    ->map(fn(ItemPhoto $photo) => [
                        'url' => asset('storage/' . $photo->file_path),
                        'file_path' => $photo->file_path,
                        'file_name' => $photo->file_name,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function edit(Item $item): View
    {
        return view('purchasing.v2.items.edit', [
            'item' => $item,
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:191'],
            'category' => ['nullable', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'default_unit' => ['nullable', 'string', 'max:191'],
            'default_specification' => ['nullable', 'string'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'in:IDR'],
            'is_active' => ['required', 'boolean'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $validated['currency'] = $validated['currency'] ?: 'IDR';
        $validated['default_unit'] = $validated['default_unit'] ?: 'pcs';

        unset($validated['photos']);

        $item->update($validated);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (! $photo) {
                    continue;
                }

                $path = $photo->store('item-photos', 'public');

                ItemPhoto::create([
                    'item_id' => $item->id,
                    'file_path' => $path,
                    'file_name' => $photo->getClientOriginalName(),
                ]);
            }
        }

        return redirect()
            ->route('purchasing.v2.items.edit', $item)
            ->with('success', 'Item has been updated successfully.');
    }

    public function destroyPhoto(ItemPhoto $photo)
    {
        $item = $photo->item;

        if ($photo->file_path && Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
        }

        $photo->delete();

        return redirect()
            ->route('purchasing.v2.items.edit', $item)
            ->with('success', 'Photo has been deleted successfully.');
    }
}
