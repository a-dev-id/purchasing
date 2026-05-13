@extends('layouts.purchasing-lite')

@section('title', ($purchaseRequest->request_number ?? 'Purchase Request') . ' - Nandini Purchasing Lite')

@section('content')
@include('purchasing.v2.requests.partials.flash')

@include('purchasing.v2.requests.partials.header')

@include('purchasing.v2.requests.partials.summary-card')

@include('purchasing.v2.requests.partials.requested-items-table')

@include('purchasing.v2.requests.partials.vendor-search-script')

@include('purchasing.v2.requests.partials.actions')
@endsection