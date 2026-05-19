@if ($canEditVendorOffers)
<div class="mb-4 flex flex-wrap gap-2">
    <button type="submit" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
        Save Bids
    </button>
</div>

</form>
@elseif ($canSelectVendor)
<div class="mb-4 flex flex-wrap gap-2">
    <button type="submit" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
        Save Selected Vendors
    </button>
</div>

</form>
@elseif ($canGmApproveItems)
<div class="mb-4 bg-white border border-gray-300 p-4">
    <div class="mb-3 text-sm font-bold text-gray-900">
        GM Approval
    </div>

    <input type="hidden" name="deferred_until" value="{{ old('deferred_until', $purchaseRequest->date_needed?->toDateString() ?? now()->addMonth()->toDateString()) }}">

    <button type="submit" onclick="return confirm('Approve selected items? Unticked Reason items will be moved to a new child PR. Unticked Canceled items will not be moved.')" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
        Approve Selected Items
    </button>
</div>

</form>
@endif