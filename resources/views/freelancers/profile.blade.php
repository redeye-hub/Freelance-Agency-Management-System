<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Skills &amp; Rates</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="POST" action="{{ route('freelancer.profile.update') }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="headline" value="Headline" />
                    <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full"
                                  placeholder="e.g. Full-stack Laravel developer" :value="old('headline', $profile->headline)" />
                    <x-input-error class="mt-1" :messages="$errors->get('headline')" />
                </div>

                <div>
                    <x-input-label for="bio" value="About me" />
                    <textarea id="bio" name="bio" rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('bio', $profile->bio) }}</textarea>
                    <x-input-error class="mt-1" :messages="$errors->get('bio')" />
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="hourly_rate" value="Hourly rate (USD)" />
                        <x-text-input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                      :value="old('hourly_rate', $profile->hourly_rate)" />
                        <x-input-error class="mt-1" :messages="$errors->get('hourly_rate')" />
                    </div>
                    <div>
                        <x-input-label for="availability" value="Availability" />
                        <select id="availability" name="availability" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach (\App\Models\FreelancerProfile::AVAILABILITY as $a)
                                <option value="{{ $a }}" @selected(old('availability', $profile->availability) === $a)>{{ ucfirst($a) }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-1" :messages="$errors->get('availability')" />
                    </div>
                </div>

                <div>
                    <x-input-label for="skills" value="Skills (separate with commas)" />
                    <x-text-input id="skills" name="skills" type="text" class="mt-1 block w-full"
                                  placeholder="Laravel, Vue, Figma, MySQL"
                                  :value="old('skills', $profile->skills->pluck('name')->implode(', '))" />
                    <x-input-error class="mt-1" :messages="$errors->get('skills')" />
                </div>

                <button class="px-5 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Save profile</button>
            </form>
        </div>
    </div>
</x-app-layout>