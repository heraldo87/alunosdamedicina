<?php
// includes/footer.php
?>
    <footer class="mt-auto py-6 px-8 border-t border-slate-800 text-slate-500">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-[11px] font-medium uppercase tracking-widest">
                &copy; <?php echo date('Y'); ?> <span class="text-white">MedInFocus</span> — Todos os direitos reservados.
            </div>
            <div class="flex gap-6 items-center">
                <a href="#" class="text-[10px] hover:text-brand-primary transition-colors uppercase font-bold">Privacidade</a>
                <a href="#" class="text-[10px] hover:text-brand-primary transition-colors uppercase font-bold">Suporte</a>
                <div class="flex items-center gap-2 px-3 py-1 bg-emerald-500/10 text-emerald-500 rounded-full border border-emerald-500/20 text-[9px] font-black uppercase tracking-tighter">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Servidor Online
                </div>
            </div>
        </div>
    </footer>
</body>
</html>