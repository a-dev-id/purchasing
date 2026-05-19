@if ($showSelectedVendorColumns)
<tr class="bg-gray-100">
    <td colspan="{{ $canGmApproveItems ? 7 : 6 }}" class="border border-gray-400 px-3 py-3 text-right font-bold text-gray-900">
        Grand Total
    </td>

    <td class="border border-gray-400 px-3 py-3"></td>

    <td class="border border-gray-400 px-3 py-3 text-right font-bold text-gray-900">
        -
    </td>

    <td class="border border-gray-400 px-3 py-3 text-right font-bold text-gray-900">
        Rp {{ number_format($grandTotal, 0, ',', '.') }}
    </td>

    <td colspan="{{ $showBidColumns ? 4 : 1 }}" class="border border-gray-400 px-3 py-3"></td>
</tr>
@endif