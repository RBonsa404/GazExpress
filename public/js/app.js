/* =====================================================================
   GazExpress — comportements communs (panier, menus, notifications AJAX)
   ===================================================================== */
(function () {
    'use strict';

    const BASE = document.body.dataset.base || '';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    window.GazExpress = { BASE, CSRF };

    /* ---------- Tiroir du panier ---------- */
    const tiroir = $('#tiroir-panier');
    const voile = $('#voile-panier');
    function ouvrirPanier() {
        if (!tiroir) return;
        tiroir.classList.add('ouvert');
        voile.classList.add('ouvert');
        tiroir.setAttribute('aria-hidden', 'false');
        document.body.classList.add('panier-ouvert');
    }
    function fermerPanier() {
        if (!tiroir) return;
        tiroir.classList.remove('ouvert');
        voile.classList.remove('ouvert');
        tiroir.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('panier-ouvert');
        if (location.hash === '#panier') history.replaceState(null, '', location.pathname + location.search);
    }
    $$('[data-ouvrir-panier]').forEach(b => b.addEventListener('click', ouvrirPanier));
    $$('[data-fermer-panier]').forEach(b => b.addEventListener('click', fermerPanier));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fermerPanier(); });
    if (location.hash === '#panier') ouvrirPanier();

    /* Recalcul des frais selon le mode de récupération et le quartier */
    const form = $('#form-commande');
    if (form) {
        const sousTotal = parseInt(form.dataset.sousTotal, 10) || 0;
        const fmt = n => n.toLocaleString('fr-FR').replace(/\s/g, ' ') + ' FCFA';
        const maj = () => {
            const livraison = $('#mode-livraison').checked;
            const opt = $('#quartier').selectedOptions[0];
            const fraisQuartier = opt ? parseInt(opt.dataset.frais, 10) : 500;
            const frais = livraison ? fraisQuartier : 0;
            $('[data-frais]', form).textContent = fmt(frais);
            $('[data-total]', form).textContent = fmt(sousTotal + frais);
            $('[data-frais-libelle]', form).textContent = fraisQuartier;
            $('[data-bloc-livraison]', form).style.display = livraison ? '' : 'none';
            // Paiement en espèces : libellé adapté au mode
            const esp = $('#paiement option[value="especes"]');
            if (esp) esp.textContent = livraison ? 'Espèces à la livraison' : 'Espèces au retrait';
        };
        $$('input[name="mode"], #quartier', form).forEach(el => el.addEventListener('change', maj));
        maj();
    }

    /* ---------- Menus déroulants & navigation mobile ---------- */
    function basculer(bouton, panneau, onOpen) {
        if (!bouton || !panneau) return;
        bouton.addEventListener('click', e => {
            e.stopPropagation();
            const ouvert = panneau.classList.toggle('ouvert');
            $$('.panneau-deroulant.ouvert').forEach(p => { if (p !== panneau) p.classList.remove('ouvert'); });
            if (ouvert && onOpen) onOpen();
        });
    }
    document.addEventListener('click', e => {
        $$('.panneau-deroulant.ouvert').forEach(p => { if (!p.contains(e.target)) p.classList.remove('ouvert'); });
    });
    basculer($('#btn-compte'), $('#panneau-compte'));
    $('#burger')?.addEventListener('click', () => $('#nav').classList.toggle('ouvert'));

    /* ---------- Notifications (rafraîchies en AJAX) ---------- */
    const pastilleNotif = $('#nb-notifs');
    const listeNotifs = $('#liste-notifs');
    const echapper = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function chargerNotifications() {
        try {
            const r = await fetch(BASE + '/api/notifications', { headers: { 'Accept': 'application/json' } });
            if (!r.ok) return;
            const d = await r.json();
            pastilleNotif.textContent = d.nonLues > 9 ? '9+' : d.nonLues;
            pastilleNotif.classList.toggle('visible', d.nonLues > 0);
            listeNotifs.innerHTML = d.items.length
                ? d.items.map(n => `<a class="notif ${n.lue ? '' : 'non-lue'}" href="${echapper(n.lien || '#')}">${echapper(n.message)}<small>${echapper(n.date)}</small></a>`).join('')
                : '<div class="notif-vide">Aucune notification pour le moment.</div>';
        } catch (e) { /* réseau indisponible : on réessaiera */ }
    }
    if (pastilleNotif) {
        chargerNotifications();
        setInterval(chargerNotifications, 20000);
        basculer($('#btn-notifs'), $('#panneau-notifs'), () => {
            if (!pastilleNotif.classList.contains('visible')) return;
            fetch(BASE + '/api/notifications/lues', { method: 'POST', headers: { 'X-CSRF-Token': CSRF } })
                .then(() => pastilleNotif.classList.remove('visible'));
        });
    }

    /* ---------- Messages flash : disparition automatique ---------- */
    $$('.toast').forEach((t, i) => setTimeout(() => {
        t.style.transition = 'opacity .4s'; t.style.opacity = '0';
        setTimeout(() => t.remove(), 400);
    }, 6000 + i * 800));

    /* ---------- Filtres par puces (catalogue) ----------
       Chaque groupe [data-filtre-groupe] filtre les éléments ciblés sur l'attribut
       data-<data-filtre-attr> ; un élément reste visible s'il satisfait tous les groupes. */
    const groupes = $$('[data-filtre-groupe]');
    function appliquerFiltres() {
        if (!groupes.length) return;
        const cible = groupes[0].dataset.filtreGroupe;
        const actifs = groupes.map(g => [g.dataset.filtreAttr, $('.puce.actif', g)?.dataset.valeur || '']);
        let visibles = 0;
        $$(cible).forEach(el => {
            const ok = actifs.every(([attr, val]) => !val || el.dataset[attr] === val);
            el.hidden = !ok;
            if (ok) visibles++;
        });
        const compteur = $('[data-compteur-produits]');
        if (compteur) compteur.textContent = visibles + ' bouteille' + (visibles > 1 ? 's' : '');
        const vide = $('[data-aucun-produit]');
        if (vide) vide.hidden = visibles > 0;
    }
    groupes.forEach(groupe => $$('.puce', groupe).forEach(p => p.addEventListener('click', () => {
        $$('.puce', groupe).forEach(x => { x.classList.remove('actif'); x.setAttribute('aria-pressed', 'false'); });
        p.classList.add('actif');
        p.setAttribute('aria-pressed', 'true');
        appliquerFiltres();
    })));

    /* ---------- Burger : état accessible ---------- */
    $('#burger')?.addEventListener('click', e => e.currentTarget.setAttribute('aria-expanded', $('#nav').classList.contains('ouvert')));

    /* ---------- Confirmation des actions sensibles ---------- */
    $$('form[data-confirmer]').forEach(f => f.addEventListener('submit', e => {
        if (!confirm(f.dataset.confirmer)) e.preventDefault();
    }));
})();
