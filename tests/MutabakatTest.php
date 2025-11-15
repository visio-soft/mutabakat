<?php

use Visiosoft\Mutabakat\Models\Mutabakat;

it('can create a mutabakat record', function () {
    $mutabakat = Mutabakat::create([
        'park_id' => 1,
        'row_hash' => 'test-hash-123',
        'company' => 'Test Company',
        'parking_name' => 'Test Parking',
        'transaction_name' => 'Daily Settlement',
        'transaction_count' => 100,
        'total_amount' => 10000.50,
        'commission_amount' => 500.25,
        'net_transfer_amount' => 9500.25,
        'status' => 'pending',
    ]);

    expect($mutabakat)->toBeInstanceOf(Mutabakat::class)
        ->and($mutabakat->company)->toBe('Test Company')
        ->and($mutabakat->transaction_count)->toBe(100)
        ->and($mutabakat->total_amount)->toBe('10000.50');
});

it('can update a mutabakat record', function () {
    $mutabakat = Mutabakat::create([
        'company' => 'Test Company',
        'status' => 'pending',
    ]);

    $mutabakat->update(['status' => 'completed']);

    expect($mutabakat->fresh()->status)->toBe('completed');
});

it('can soft delete a mutabakat record', function () {
    $mutabakat = Mutabakat::create([
        'company' => 'Test Company',
    ]);

    $mutabakat->delete();

    expect($mutabakat->trashed())->toBeTrue()
        ->and(Mutabakat::withTrashed()->find($mutabakat->id))->not->toBeNull();
});

it('can restore a soft deleted mutabakat record', function () {
    $mutabakat = Mutabakat::create([
        'company' => 'Test Company',
    ]);

    $mutabakat->delete();
    $mutabakat->restore();

    expect($mutabakat->trashed())->toBeFalse();
});

it('casts decimal fields correctly', function () {
    $mutabakat = Mutabakat::create([
        'total_amount' => 1234.56,
        'commission_amount' => 123.45,
        'net_transfer_amount' => 1111.11,
    ]);

    expect($mutabakat->total_amount)->toBe('1234.56')
        ->and($mutabakat->commission_amount)->toBe('123.45')
        ->and($mutabakat->net_transfer_amount)->toBe('1111.11');
});

it('casts date fields correctly', function () {
    $mutabakat = Mutabakat::create([
        'provision_date' => '2024-01-15',
        'payment_date' => '2024-01-20',
    ]);

    expect($mutabakat->provision_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($mutabakat->payment_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
