@csrf
<div class="grid sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Project name *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $project->name)" required />
        <x-input-error class="mt-1" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="client_id" value="Client *" />
        <select id="client_id" name="client_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">Select a client</option>
            @foreach ($clients as $c)
                <option value="{{ $c->id }}" @selected((int) old('client_id', $project->client_id) === $c->id)>{{ $c->company_name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('client_id')" />
    </div>
    <div>
        <x-input-label for="manager_id" value="Project manager" />
        <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">Unassigned</option>
            @foreach ($managers as $m)
                <option value="{{ $m->id }}" @selected((int) old('manager_id', $project->manager_id) === $m->id)>{{ $m->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('manager_id')" />
    </div>
    <div>
        <x-input-label for="budget" value="Budget (USD) *" />
        <x-text-input id="budget" name="budget" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('budget', $project->budget)" required />
        <x-input-error class="mt-1" :messages="$errors->get('budget')" />
    </div>
    <div>
        <x-input-label for="deadline" value="Deadline" />
        <x-text-input id="deadline" name="deadline" type="date" class="mt-1 block w-full" :value="old('deadline', $project->deadline?->format('Y-m-d'))" />
        <x-input-error class="mt-1" :messages="$errors->get('deadline')" />
    </div>
    <div>
        <x-input-label for="status" value="Status *" />
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected(old('status', $project->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-1" :messages="$errors->get('status')" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('description', $project->description) }}</textarea>
        <x-input-error class="mt-1" :messages="$errors->get('description')" />
    </div>
</div>
