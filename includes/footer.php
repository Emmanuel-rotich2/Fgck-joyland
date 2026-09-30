
<footer class="text-center py-3">

    <p class="mb-1">
        © 2026 FGCK Joyland. All rights reserved.
    </p>

    <p class="mb-0">
        Powered by
        <a
            href="/fgck_joyland/staff/dashboard.php#"
            title="MylesHub Technologies"
            style="font-weight: 700; text-decoration: none;"
        >
            @MylesHubTechnologies
        </a>
    </p>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<div class="sidebar-overlay" data-sidebar-overlay></div>
<script>
(function(){
    const sidebar=document.getElementById('sidebar');
    const overlay=document.querySelector('[data-sidebar-overlay]');
    const openers=document.querySelectorAll('[data-sidebar-toggle]');
    function openSidebar(){sidebar?.classList.add('show');overlay?.classList.add('show');document.body.classList.add('sidebar-open');}
    function closeSidebar(){sidebar?.classList.remove('show');overlay?.classList.remove('show');document.body.classList.remove('sidebar-open');}
    openers.forEach(b=>b.addEventListener('click',()=>sidebar?.classList.contains('show')?closeSidebar():openSidebar()));
    overlay?.addEventListener('click',closeSidebar);
    sidebar?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{if(window.innerWidth<=900)closeSidebar();}));
    window.addEventListener('resize',()=>{if(window.innerWidth>900)closeSidebar();});
})();
</script>
