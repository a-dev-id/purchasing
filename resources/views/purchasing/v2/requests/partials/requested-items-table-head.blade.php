<thead>
    <tr class="bg-gray-200">
        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[50px]">
            No
        </th>

        @if ($canGmApproveItems)
        <th class="border border-gray-400 px-3 py-2 text-center whitespace-nowrap w-[230px]">
            Approve
        </th>
        @endif

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">
            Item Name
        </th>

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[150px]">
            Photos
        </th>

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[80px]">
            Qty
        </th>

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[90px]">
            Unit
        </th>

        @if ($showEstimatedPriceColumns)
        <th class="border border-gray-400 px-3 py-2 text-right whitespace-nowrap w-[160px]">
            Est. Unit Price
        </th>
        @endif

        @if ($showEstimatedTotalPriceColumn)
        <th class="border border-gray-400 px-3 py-2 text-right whitespace-nowrap w-[170px]">
            Est. Total Price
        </th>
        @endif

        @if ($showSelectedVendorColumns)
        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[220px]">
            Selected Vendor
        </th>

        <th class="border border-gray-400 px-3 py-2 text-right whitespace-nowrap w-[160px]">
            Price / Unit
        </th>

        <th class="border border-gray-400 px-3 py-2 text-right whitespace-nowrap w-[170px]">
            Total Price
        </th>
        @endif

        @if ($showBidColumns)
        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[260px]">
            Bid 1
        </th>

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[260px]">
            Bid 2
        </th>

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[260px]">
            Bid 3
        </th>
        @endif

        <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">
            Specification
        </th>
    </tr>
</thead>