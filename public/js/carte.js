/* =====================================================================
   GazExpress — carte interactive des vendeurs (Leaflet + OpenStreetMap)
   ===================================================================== */
(function () {
    'use strict';

    const conteneur = document.getElementById('carte');
    if (!conteneur || typeof L === 'undefined') return;

    const vendeurs = JSON.parse(conteneur.dataset.vendeurs || '[]');
    const OUAGA = [12.3600, -1.5200];

    const carte = L.map(conteneur, { scrollWheelZoom: false }).setView(OUAGA, 13);

    // Fonds de carte OpenStreetMap : serveur officiel, puis serveurs de secours sans clé
    // si le premier refuse les tuiles (politique d'usage OSM, réseau filtré…).
    const OSM = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
    const fonds = [
        ['https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: OSM }],
        ['https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', { maxZoom: 19, subdomains: 'abc', attribution: OSM + ' &copy; OSM France' }],
        ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: 'Tiles &copy; Esri' }],
    ];
    let indexFond = 0, erreurs = 0, couche = null;
    function poserFond() {
        if (couche) carte.removeLayer(couche);
        erreurs = 0;
        couche = L.tileLayer(fonds[indexFond][0], fonds[indexFond][1]).addTo(carte);
        couche.on('tileerror', () => {
            if (++erreurs === 3 && indexFond < fonds.length - 1) { indexFond++; poserFond(); }
        });
    }
    poserFond();

    const echapper = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const couleurs = { disponible: '#0F8A5F', limite: '#D97706', indisponible: '#B91C1C' };
    const SPRITE = document.body.dataset.icones || '';
    const ico = nom => `<svg class="ic" aria-hidden="true"><use href="${SPRITE}#${nom}"></use></svg>`;

    const marqueurs = {};
    const groupe = L.featureGroup().addTo(carte);

    vendeurs.forEach(v => {
        const icone = L.divIcon({
            className: '',
            html: `<div class="marqueur-gaz ${v.etat}">${ico('bouteille')}</div>`,
            iconSize: [30, 30], iconAnchor: [15, 30], popupAnchor: [0, -30]
        });
        const stocks = v.stocks.map(s =>
            `<li><span><span class="p-etat" style="background:${couleurs[s.etat]}"></span>${echapper(s.produit)}</span><b>${echapper(s.prix)}</b></li>`
        ).join('');
        const popup = `
            <div class="popup">
                <h4>${echapper(v.nom)}</h4>
                <p>${echapper(v.quartier)} · ${echapper(v.adresse)}</p>
                <p>Ouvert ${echapper(v.horaires)} ${v.ouvert ? '' : '· <b style="color:#B91C1C">Fermé</b>'}</p>
                <p>Tél. ${echapper(v.telephone)}</p>
                <ul>${stocks || '<li>Aucun produit renseigné</li>'}</ul>
                <a class="btn btn-primaire btn-petit" href="${echapper(v.url)}">Voir le stock et commander</a>
            </div>`;
        const m = L.marker([v.lat, v.lon], { icon: icone, title: v.nom }).bindPopup(popup);
        m.on('click', () => surligner(v.id));
        m.addTo(groupe);
        marqueurs[v.id] = { marqueur: m, donnees: v };
    });

    if (vendeurs.length) carte.fitBounds(groupe.getBounds().pad(0.25));

    /* Lien carte <-> liste */
    const cartes = Array.from(document.querySelectorAll('.carte-vendeur'));
    function surligner(id) {
        cartes.forEach(c => c.classList.toggle('surligne', c.dataset.id == id));
        const el = cartes.find(c => c.dataset.id == id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    cartes.forEach(c => c.addEventListener('click', e => {
        if (e.target.closest('a')) return;
        const m = marqueurs[c.dataset.id];
        if (!m) return;
        carte.setView(m.marqueur.getLatLng(), 15);
        m.marqueur.openPopup();
        surligner(c.dataset.id);
    }));

    /* Recherche texte + filtres par quartier */
    const champ = document.getElementById('recherche-vendeur');
    const puces = Array.from(document.querySelectorAll('#puces-quartiers .puce'));
    let quartier = '';

    function filtrer() {
        const q = (champ.value || '').trim().toLowerCase();
        let visibles = 0;
        groupe.clearLayers();
        cartes.forEach(c => {
            const ok = (!quartier || c.dataset.quartier === quartier) && (!q || c.dataset.recherche.includes(q));
            c.style.display = ok ? '' : 'none';
            if (ok) {
                visibles++;
                marqueurs[c.dataset.id]?.marqueur.addTo(groupe);
            }
        });
        document.getElementById('nb-vendeurs').textContent = visibles;
        document.getElementById('aucun-vendeur').style.display = visibles ? 'none' : '';
        if (visibles) carte.fitBounds(groupe.getBounds().pad(0.3), { maxZoom: 15 });
    }
    champ?.addEventListener('input', filtrer);
    puces.forEach(p => p.addEventListener('click', () => {
        puces.forEach(x => x.classList.remove('actif'));
        p.classList.add('actif');
        quartier = p.dataset.quartier;
        filtrer();
    }));

    /* Géolocalisation du navigateur : position du client + vendeur le plus proche */
    document.getElementById('btn-localiser')?.addEventListener('click', () => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(pos => {
            const ici = [pos.coords.latitude, pos.coords.longitude];
            // On ne recentre que si l'utilisateur se trouve dans l'agglomération de Ouagadougou
            if (carte.distance(ici, OUAGA) > 25000) return;
            L.circleMarker(ici, { radius: 9, color: '#fff', weight: 3, fillColor: '#2563EB', fillOpacity: 1 })
                .addTo(carte).bindPopup('Vous êtes ici').openPopup();
            let proche = null, min = Infinity;
            Object.values(marqueurs).forEach(({ marqueur, donnees }) => {
                const d = carte.distance(ici, marqueur.getLatLng());
                if (d < min && donnees.etat !== 'indisponible') { min = d; proche = donnees.id; }
            });
            carte.setView(ici, 14);
            if (proche) surligner(proche);
        }, () => { /* refus : la carte reste centrée sur Ouagadougou */ }, { timeout: 8000 });
    });

    /* Recherche du bandeau d'accueil : filtre la liste et fait défiler jusqu'à la carte */
    function chercher(texte) {
        if (!champ) return;
        champ.value = texte;
        filtrer();
        document.getElementById('vendeurs')?.scrollIntoView({ behavior: 'smooth' });
    }
    document.getElementById('form-intro')?.addEventListener('submit', e => {
        e.preventDefault();
        chercher(e.target.q.value.trim());
    });
    const qInitial = new URLSearchParams(location.search).get('q');
    if (qInitial) chercher(qInitial);
})();
