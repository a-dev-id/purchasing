@php
use App\Models\Item;
use Illuminate\Support\Facades\Storage;

$itemId = $get('item_id');
$item = filled($itemId) ? Item::with('photos')->find($itemId) : null;
@endphp

<div>
    <div style="font-weight: 600; margin-bottom: 8px;">
        Existing Item Photos
    </div>

    @if (blank($itemId))
    <div style="color: #6b7280;">
        No item selected.
    </div>
    @elseif (! $item || $item->photos->isEmpty())
    <div style="color: #6b7280;">
        This item has no saved images.
    </div>
    @else
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        @foreach ($item->photos as $photo)
        @php
        $path = trim((string) ($photo->file_path ?? ''));
        @endphp

        @if ($path !== '')
        <a href="{{ Storage::disk('public')->url($path) }}" target="_blank" style="display: inline-block;">
            <img src="{{ Storage::disk('public')->url($path) }}" alt="{{ $photo->file_name ?: 'Item Image' }}" style="width: 72px; height: 72px; object-fit: cover; border: 1px solid #d1d5db; border-radius: 8px; background: #fff;">
        </a>
        @endif
        @endforeach
    </div>
    @endif
</div>