@extends('layouts.tabler')

@section('content')
<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        {{ __('Purchases: Daily Report') }}
                    </h3>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered card-table table-vcenter text-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" class="text-center">{{ __('No.') }}</th>
                            <th scope="col" class="text-center">{{ __('Purchase') }}</th>
                            <th scope="col" class="text-center">{{ __('Supplier') }}</th>
                            <th scope="col" class="text-center">{{ __('Date') }}</th>
                            <th scope="col" class="text-center">{{ __('Total') }}</th>
                            <th scope="col" class="text-center">{{ __('Status') }}</th>
                            <th scope="col" class="text-center">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center">{{ $purchase->purchase_no }}</td>
                            <td class="text-center">{{ $purchase->supplier->name }}</td>
                            <td class="text-center">{{ $purchase->date->format('d-m-Y') }}</td>
                            <td class="text-center">{{ Number::currency($purchase->total_amount, 'EUR') }}</td>
                            <td class="text-center">
                                <span class="btn btn-{{ $purchase->status->value === 0 ? 'warning' : 'success' }} btn-sm text-uppercase">
                                    {{ $purchase->status->label() }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-icon btn-outline-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">{{ __('No purchases found for today.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
