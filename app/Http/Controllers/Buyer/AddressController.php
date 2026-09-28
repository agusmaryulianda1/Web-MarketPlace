<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\StoreAddressRequest;
use App\Http\Requests\Buyer\UpdateAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use App\Support\IndonesiaRegions;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addressService) {}

    public function index(): Response { return Inertia::render('Buyer/Addresses/Index', ['addresses' => request()->user()->addresses()->latest()->get(), 'regions' => IndonesiaRegions::all()]); }

    public function store(StoreAddressRequest $request)
    {
        $this->addressService->create($request->user(), $request->validated());
        return redirect()->route('buyer.addresses.index');
    }

    public function update(UpdateAddressRequest $request, Address $address)
    {
        $this->addressService->update($request->user(), $address, $request->validated());
        return redirect()->route('buyer.addresses.index');
    }

    public function default(Address $address)
    {
        $this->addressService->setDefault(request()->user(), $address);
        return redirect()->route('buyer.addresses.index');
    }
}