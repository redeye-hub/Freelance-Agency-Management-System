@if (session('success'))
    <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">{{ $errors->first() }}</div>
@endif