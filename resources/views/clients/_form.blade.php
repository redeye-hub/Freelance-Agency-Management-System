@php
    $fields = [
        ['company_name', 'Company name', 'text', true],
        ['contact_name', 'Contact person', 'text', true],
        ['email', 'Email', 'email', true],
        ['phone', 'Phone', 'text', false],
        ['website', 'Website', 'text', false],
        ['city', 'City', 'text', false],
        ['country', 'Country', 'text', false],
        ['tax_id', 'Tax / VAT ID', 'text', false],
    ];
@endphp

<div class="grid sm:grid-cols-2 gap-4">
    @foreach ($fields as [$name, $label, $type, $required])
        <div>
            <x-input-label :for="$name" :value="$label . ($required ? ' *' : '')" />
            <x-text-input :id="$name" :name="$name" :type="$type" class="mt-1 block w-full"
                          :value="old($name, $client->$name ?? '')" :required="$required" />
            <x-input-error class="mt-1" :messages="$errors->get($name)" />
        </div>
    @endforeach
</div>

<div>
    <x-input-label for="billing_address" value="Billing address" />
    <textarea id="billing_address" name="billing_address" rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('billing_address', $client->billing_address ?? '') }}</textarea>
    <x-input-error class="mt-1" :messages="$errors->get('billing_address')" />
</div>

<div>
    <x-input-label for="notes" value="Notes" />
    <textarea id="notes" name="notes" rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $client->notes ?? '') }}</textarea>
    <x-input-error class="mt-1" :messages="$errors->get('notes')" />
</div>

@isset($client)
    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" class="mt-1 block w-full sm:w-60 rounded-md border-gray-300 shadow-sm">
            <option value="active" @selected(old('status', $client->status) === 'active')>Active</option>
            <option value="archived" @selected(old('status', $client->status) === 'archived')>Archived</option>
        </select>
    </div>
@endisset