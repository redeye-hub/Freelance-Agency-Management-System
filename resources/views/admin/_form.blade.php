<div>
    <x-input-label for="name" value="Full name *" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name ?? '')" required />
    <x-input-error class="mt-1" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="email" value="Email *" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email ?? '')" required />
    <x-input-error class="mt-1" :messages="$errors->get('email')" />
</div>

<div>
    <x-input-label for="phone" value="Phone" />
    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone ?? '')" />
    <x-input-error class="mt-1" :messages="$errors->get('phone')" />
</div>

<div>
    <x-input-label for="role" value="Role *" />
    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        @foreach ($roles as $r)
            <option value="{{ $r }}" @selected(old('role', isset($user) ? $user->getRoleNames()->first() : 'freelancer') === $r)>{{ $r }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-1" :messages="$errors->get('role')" />
</div>