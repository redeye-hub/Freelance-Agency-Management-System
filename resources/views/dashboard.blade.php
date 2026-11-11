{{-- resources/views/dashboard.blade.php (replace the whole file) --}}
@php
    $me = auth()->user();
    $stageColors = ['new' => '#6366f1', 'contacted' => '#0ea5e9', 'qualified' => '#8b5cf6', 'proposal' => '#f59e0b', 'won' => '#10b981', 'lost' => '#f43f5e'];
    $max = max(1, (int) ($stageCounts->max() ?? 0));
    $tiles = [];
    if ($stats) {
        foreach (['total' => 'Total users', 'active' => 'Active', 'suspended' => 'Suspended', 'archived' => 'Archived', 'freelancers' => 'Freelancers', 'clients' => 'Clients', 'managers' => 'Project managers', 'finance' => 'Finance'] as $k => $label) {
            $tiles[] = [$label, $stats[$k]];
        }
    }
    $crmTiles = [];
    if ($crm) {
        foreach (['leads_total' => 'Leads', 'leads_open' => 'Open leads', 'leads_won' => 'Won', 'clients' => 'Active clients', 'available' => 'Freelancers available'] as $k => $label) {
            $crmTiles[] = [$label, $crm[$k]];
        }
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <style>
        .d3-hero { position: relative; overflow: hidden; }
        .d3-hero canvas { position: absolute; right: 0; top: 0; width: 55%; height: 100%; opacity: 0; animation: d3-in 1.4s .2s ease forwards; }
        @keyframes d3-in { to { opacity: 1; } }
        .d3-num { font-weight: 800; font-size: 2.4rem; line-height: 1; letter-spacing: -.02em;
            background: linear-gradient(135deg, #4f46e5, #0ea5e9); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .d3-dot { width: 11px; height: 11px; border-radius: 50%; display: inline-block;
            background: radial-gradient(circle at 30% 30%, #fff, var(--c, #6366f1)); box-shadow: 0 3px 6px rgba(49, 46, 129, .3); }

        /* 3D pipeline columns */
        .d3-stage { perspective: 900px; display: flex; align-items: flex-end; justify-content: space-around; gap: 8px; height: 270px; padding: 0 8px; }
        .d3-col { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
        .d3-box { position: relative; width: 44px; height: var(--h); transform-style: preserve-3d;
            transform: rotateX(-24deg) rotateY(-32deg); transform-origin: bottom center;
            animation: d3-rise 1.1s cubic-bezier(.2, .8, .2, 1) both; animation-delay: calc(var(--i) * 90ms + 300ms); }
        .d3-box i { position: absolute; display: block; background: var(--c); }
        .d3-box .f { inset: 0; transform: translateZ(22px); background: linear-gradient(180deg, color-mix(in srgb, var(--c) 80%, #fff), var(--c)); }
        .d3-box .r { left: 44px; top: 0; width: 44px; height: 100%; transform-origin: left; transform: translateZ(22px) rotateY(90deg); filter: brightness(.72); }
        .d3-box .t { left: 0; top: 0; width: 44px; height: 44px; transform-origin: top; transform: translateZ(22px) rotateX(-90deg); filter: brightness(1.3); }
        @keyframes d3-rise { from { transform: rotateX(-24deg) rotateY(-32deg) scaleY(.02); } }
        .d3-count { font-weight: 700; font-size: .95rem; color: #1e293b; margin-bottom: 14px; }
        .d3-label { font-size: .75rem; color: #475569; margin-top: 12px; text-transform: capitalize; }
        .d3-avatar { width: 38px; height: 38px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; color: #fff;
            background: radial-gradient(circle at 30% 25%, #a5b4fc, #4f46e5 70%); box-shadow: 0 5px 10px rgba(79, 70, 229, .35), inset 0 -4px 8px rgba(0, 0, 0, .2); }
        @media (prefers-reduced-motion: reduce) { .d3-box { animation: none; } .d3-hero canvas { animation: none; opacity: 1; } }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Hero with live 3D scene --}}
            <div class="bg-white d3-hero sm:rounded-lg">
                <canvas id="hero3d"></canvas>
                <div class="relative p-8 sm:p-10 min-h-[260px] flex flex-col justify-center sm:w-3/5">
                    <p class="text-sm text-gray-500">Signed in as {{ $me->getRoleNames()->first() ?? 'user' }}</p>
                    <h3 class="mt-1 text-3xl sm:text-4xl font-extrabold text-gray-800 tracking-tight">Welcome back, {{ $me->name }}</h3>
                    <p class="mt-3 text-gray-600 max-w-md">Everything for your agency in one place: leads, clients, freelancers and your team.</p>
                    <div class="mt-6 flex flex-wrap gap-4">
                        @if($me->hasAnyRole(['super-admin', 'project-manager']))
                            <a href="{{ route('leads.index') }}" class="bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open pipeline</a>
                        @endif
                        @if($me->hasRole('super-admin'))
                            <a href="{{ route('admin.users.index') }}" class="bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Manage users</a>
                        @endif
                        @if($me->hasRole('freelancer'))
                            <a href="{{ route('freelancer.profile.edit') }}" class="bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit my profile</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- User stats (admin) --}}
            @if($tiles)
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($tiles as [$label, $value])
                        <div class="bg-white p-5 sm:rounded-lg">
                            <span class="d3-dot"></span>
                            <p class="mt-3 text-sm text-gray-500">{{ $label }}</p>
                            <p class="d3-num mt-1" data-count="{{ $value }}">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- CRM stats + 3D pipeline --}}
            @if($crmTiles)
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-5">
                    @foreach($crmTiles as [$label, $value])
                        <div class="bg-white p-5 sm:rounded-lg">
                            <p class="text-sm text-gray-500">{{ $label }}</p>
                            <p class="d3-num mt-2" data-count="{{ $value }}">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white p-6 sm:rounded-lg">
                    <h3 class="font-semibold text-gray-800">Lead pipeline</h3>
                    <p class="text-sm text-gray-500 mb-4">Leads in each stage right now.</p>
                    <div class="d3-stage">
                        @foreach($stages as $i => $stage)
                            @php $n = (int) $stageCounts->get($stage, 0); $h = 34 + round(($n / $max) * 150); @endphp
                            <div class="d3-col">
                                <span class="d3-count">{{ $n }}</span>
                                <div class="d3-box" style="--h: {{ $h }}px; --c: {{ $stageColors[$stage] ?? '#6366f1' }}; --i: {{ $i }}">
                                    <i class="f"></i><i class="r"></i><i class="t"></i>
                                </div>
                                <span class="d3-label">{{ $stage }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Newest users (admin) --}}
            @if($recent->count())
                <div class="bg-white p-6 sm:rounded-lg">
                    <h3 class="font-semibold text-gray-800 mb-4">Newest users</h3>
                    <ul class="divide-y">
                        @foreach($recent as $u)
                            <li class="flex items-center gap-4 py-3">
                                <span class="d3-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-800 truncate">{{ $u->name }}</p>
                                    <p class="text-sm text-gray-500 truncate">{{ $u->email }}</p>
                                </div>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{{ $u->roles->pluck('name')->join(', ') ?: 'no role' }}</span>
                                <span class="text-xs px-2.5 py-0.5 rounded-full {{ ($u->status ?? 'active') === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $u->status ?? 'active' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Freelancer card --}}
            @if($profile)
                <div class="bg-white p-6 sm:rounded-lg">
                    <h3 class="font-semibold text-gray-800">{{ $profile->headline ?: 'Add a headline to your profile' }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $profile->hourly_rate ? '$' . $profile->hourly_rate . ' per hour' : 'No rate set' }} · {{ $profile->availability }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse($profile->skills as $s)
                            <span class="text-xs px-2.5 py-1 rounded-full bg-sky-100 text-sky-700">{{ $s->name }}</span>
                        @empty
                            <span class="text-sm text-gray-500">No skills yet. Add some in your profile.</span>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
    (function () {
        const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Count-up numbers
        document.querySelectorAll('[data-count]').forEach(n => {
            const to = +n.dataset.count;
            if (reduce || !to) return;
            let s = null;
            const step = ts => {
                s = s ?? ts;
                const p = Math.min((ts - s) / 900, 1);
                n.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(step);
            };
            n.textContent = 0;
            requestAnimationFrame(step);
        });

        // 3D hero: glass crystal with orbiting spheres
        const el = document.getElementById('hero3d');
        if (!el || !window.THREE) return;
        const scene = new THREE.Scene();
        const cam = new THREE.PerspectiveCamera(45, 1, .1, 100);
        cam.position.z = 7.5;
        const r = new THREE.WebGLRenderer({ canvas: el, alpha: true, antialias: true });
        r.setPixelRatio(Math.min(devicePixelRatio, 2));
        scene.add(new THREE.AmbientLight(0xffffff, .75));
        const l1 = new THREE.PointLight(0x8b9cff, 1.7, 40); l1.position.set(5, 5, 6); scene.add(l1);
        const l2 = new THREE.PointLight(0xff7ac8, 1.3, 40); l2.position.set(-6, -3, 4); scene.add(l2);

        const group = new THREE.Group();
        group.add(new THREE.Mesh(new THREE.IcosahedronGeometry(1.6, 1),
            new THREE.MeshPhongMaterial({ color: 0x7c8cff, shininess: 120, transparent: true, opacity: .74, flatShading: true })));
        group.add(new THREE.Mesh(new THREE.IcosahedronGeometry(2.0, 1),
            new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true, transparent: true, opacity: .45 })));
        scene.add(group);

        const sats = [0x38bdf8, 0xf472b6, 0x34d399, 0xfbbf24, 0xa78bfa].map((c, i) => {
            const m = new THREE.Mesh(new THREE.SphereGeometry(.22 + i * .03, 32, 32), new THREE.MeshPhongMaterial({ color: c, shininess: 150 }));
            scene.add(m);
            return { m, a: i * 1.25, s: .35 + i * .09, rad: 2.8 + i * .28, h: .6 + i * .25 };
        });

        let mx = 0, my = 0;
        addEventListener('pointermove', e => { mx = e.clientX / innerWidth - .5; my = e.clientY / innerHeight - .5; }, { passive: true });
        function size() {
            const w = el.clientWidth, h = el.clientHeight;
            r.setSize(w, h, false); cam.aspect = w / h; cam.updateProjectionMatrix();
        }
        addEventListener('resize', size); size();

        function tick(ms) {
            const t = ms * .001;
            group.rotation.y = t * .35 + mx * 1.2;
            group.rotation.x = t * .2 + my * .8;
            group.scale.setScalar(1 + Math.sin(t * 1.4) * .04);
            sats.forEach(o => {
                const a = o.a + t * o.s;
                o.m.position.set(Math.cos(a) * o.rad, Math.sin(a * 1.3) * o.h, Math.sin(a) * o.rad * .6);
            });
            r.render(scene, cam);
            if (!reduce) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    })();
    </script>
</x-app-layout>
