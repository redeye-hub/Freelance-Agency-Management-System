{{-- resources/views/components/glass-bg.blade.php --}}
<div class="glass-scene" aria-hidden="true">
    <span class="glass-orb" data-depth="1.0" style="--s:380px;--c:#818cf8;--c2:#4338ca;--t:19s;top:-110px;left:-90px"></span>
    <span class="glass-orb" data-depth="0.5" style="--s:240px;--c:#38bdf8;--c2:#0369a1;--t:15s;--b:2px;top:18%;right:-60px"></span>
    <span class="glass-orb" data-depth="1.6" style="--s:170px;--c:#f9a8d4;--c2:#be185d;--t:13s;bottom:10%;left:6%"></span>
    <span class="glass-orb" data-depth="0.3" style="--s:120px;--c:#5eead4;--c2:#0f766e;--t:17s;--b:3px;bottom:-30px;right:30%"></span>
    <span class="glass-ring"></span>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    // Orb parallax follows the pointer
    const orbs = [...document.querySelectorAll('.glass-scene [data-depth]')];
    addEventListener('pointermove', e => {
        const x = e.clientX / innerWidth - .5, y = e.clientY / innerHeight - .5;
        orbs.forEach(o => {
            const d = +o.dataset.depth;
            o.style.setProperty('--px', (-x * d * 60) + 'px');
            o.style.setProperty('--py', (-y * d * 60) + 'px');
        });
    }, { passive: true });

    // Tilt on small display cards only (not forms, tables, nav, dropdowns)
    document.querySelectorAll('body .bg-white').forEach(el => {
        if (el.closest('nav, header') || el.matches('button, a, input, select, textarea')) return;
        if (el.querySelector('input, select, textarea, table')) return;
        if (el.offsetWidth < 180 || el.offsetWidth > 760 || el.offsetHeight < 60) return;
        el.classList.add('glass-tilt');
        el.addEventListener('pointermove', e => {
            const r = el.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
            el.style.transform = `perspective(900px) rotateX(${(.5 - py) * 6}deg) rotateY(${(px - .5) * 8}deg)`;
            el.style.setProperty('--mx', px * 100 + '%');
            el.style.setProperty('--my', py * 100 + '%');
        });
        el.addEventListener('pointerleave', () => { el.style.transform = ''; });
    });
});
</script>
