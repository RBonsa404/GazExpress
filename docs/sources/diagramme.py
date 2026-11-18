# Génère docs/diagramme_classes.drawio et docs/diagramme_classes.png à partir d'un même modèle
import sys, html
from PIL import Image, ImageDraw, ImageFont

OUT = sys.argv[1]  # dossier docs

BORDEAUX = '#58111A'; NUIT = '#0F172A'; ROSE = '#FDF2F4'; GRIS = '#64748B'; ENUM = '#1E293B'

W = 330; GAPX = 70; LH = 18; HEAD = 30
def C(i): return 40 + i * (W + GAPX)

classes = {
 'Utilisateur': (['id : int', 'nom : string', 'prenom : string', 'telephone : string', 'email : string', 'motDePasse : string (haché)',
                  'role : Role', 'statut : StatutCompte', 'adresse : string', 'dateCreation : DateTime'],
                 ['seConnecter(identifiant, motDePasse) : bool', 'seDeconnecter() : void', "sInscrire(donnees) : Utilisateur", 'modifierProfil() : void']),
 'Vendeur': (['id : int', 'nomCommerce : string', 'adresse : string', 'telephone : string', 'latitude : float', 'longitude : float',
              'heureOuverture : Time', 'heureFermeture : Time', 'estOuvert : bool', 'numeroAutorisation : string', 'statutValidation : StatutValidation'],
             ['gererStock(produit, quantite, prix) : void', 'basculerOuverture() : void', 'accepterCommande(c) : void', 'refuserCommande(c, motif) : void', 'calculerEtatGlobal() : EtatStock']),
 'Livreur': (['id : int', 'typeEngin : TypeEngin', 'immatriculation : string', 'numeroPiece : string', 'disponible : bool', 'statutValidation : StatutValidation'],
             ['basculerDisponibilite() : void', 'accepterCourse(l) : bool', 'confirmerRetrait() : void', 'confirmerLivraison() : void']),
 'Quartier': (['id : int', 'nom : string', 'latitude : float', 'longitude : float', 'fraisLivraison : int'], ['calculerDistance(lat, lon) : float']),
 'Produit': (['id : int', 'designation : string', 'marque : string', 'capaciteKg : float', 'description : string', 'prixReference : int'], ['ajouter() : void', 'modifier() : void']),
 'Stock': (['id : int', 'quantite : int', 'prix : int', 'dateMiseAJour : DateTime'], ['mettreAJour(quantite, prix) : void', 'estDisponible(quantite) : bool', 'calculerEtat() : EtatStock']),
 'Commande': (['id : int', 'reference : string', 'dateCommande : DateTime', 'montantArticles : int', 'fraisLivraison : int', 'montantTotal : int',
               'statut : StatutCommande', 'modeRecuperation : ModeRecuperation', 'motifAnnulation : string', 'dateCloture : DateTime'],
              ['calculerTotal() : int', 'changerStatut(statut, auteur) : void', 'annuler(motif) : void', 'restituerStock() : void']),
 'DetailCommande': (['id : int', 'quantite : int', 'prixUnitaire : int'], ['calculerSousTotal() : int']),
 'Livraison': (['id : int', 'adresseLivraison : string', 'latitude : float', 'longitude : float', 'distanceKm : float', 'frais : int',
                'statut : StatutLivraison', 'noteLivreur : int', 'datePrise : DateTime', 'dateLivraison : DateTime'],
               ['assignerLivreur(l) : bool', 'mettreAJourStatut(s) : void', 'confirmerRemise() : void']),
 'Paiement': (['id : int', 'montant : int', 'modePaiement : ModePaiement', 'statut : StatutPaiement', 'referenceTransaction : string', 'datePaiement : DateTime'],
              ['valider() : void', 'rembourser() : void']),
 'Avis': (['id : int', 'note : int [1..5]', 'commentaire : string', 'dateAvis : DateTime'], ['publier() : void']),
 'Notification': (['id : int', 'message : string', 'lien : string', 'estLue : bool', 'dateCreation : DateTime'], ['envoyer() : void', 'marquerLue() : void']),
 'HistoriqueStatut': (['id : int', 'ancienStatut : StatutCommande', 'nouveauStatut : StatutCommande', 'dateChangement : DateTime'], ['enregistrer() : void']),
 'Reclamation': (['id : int', 'objet : string', 'message : string', 'statut : StatutReclamation', 'reponse : string', 'dateCreation : DateTime'], ['soumettre() : void', 'traiter(reponse) : void']),
}
enums = {
 'Role': ['client', 'vendeur', 'livreur', 'admin'],
 'StatutCommande': ['en_attente', 'acceptee', 'en_preparation', 'en_livraison', 'livree', 'annulee'],
 'ModeRecuperation': ['livraison', 'retrait'],
 'ModePaiement': ['especes', 'orange_money', 'moov_money'],
 'StatutValidation': ['en_attente', 'valide', 'refuse'],
}

def hauteur(nom):
    if nom in enums:
        return HEAD + 16 + len(enums[nom]) * LH + 8
    a, m = classes[nom]
    return HEAD + 8 + len(a) * LH + 8 + 8 + len(m) * LH + 8

grille = [
    ['Notification', 'Avis', 'Vendeur', 'Stock', 'Produit'],
    ['Livreur', 'Utilisateur', 'Commande', 'DetailCommande', None],
    ['Reclamation', 'HistoriqueStatut', 'Livraison', 'Paiement', None],
    [None, None, 'Quartier', None, None],
]
boites = {}
y = 100; tops = []
for ligne in grille:
    tops.append(y)
    h = max(hauteur(n) for n in ligne if n)
    for i, n in enumerate(ligne):
        if n: boites[n] = [C(i), y, W, hauteur(n)]
    y += h + 110
# énumérations empilées dans la dernière colonne, sous Produit
ye = tops[1]
for n in enums:
    boites[n] = [C(4), ye, W, hauteur(n)]
    ye += hauteur(n) + 26
LARG = C(4) + W + 40
HAUT = max(y, ye) + 20

def pt(n, cote, t):
    x, y, w, h = boites[n]
    return {'h': (x + w * t, y), 'b': (x + w * t, y + h), 'g': (x, y + h * t), 'd': (x + w, y + h * t)}[cote]

def ch(i, k=0):   # couloir horizontal au-dessus de la ligne i
    return tops[i] - 55 + k
def cv(i, k=0):   # couloir vertical à gauche de la colonne i
    return C(i) - 35 + k

# (source, côté, t, cible, côté, t, points intermédiaires, mult source, mult cible, libellé, composition)
def aligne(s, ts, c):  # t côté cible pour une liaison horizontale droite entre deux boîtes de même ligne
    return boites[s][3] * ts / boites[c][3]

def L(s, sc, st, c, cc, ct, via, ms, mc, lib, comp=False):
    a = pt(s, sc, st); b = pt(c, cc, ct)
    pts = [a]
    for v in via:
        pts.append(v(a, b, pts[-1]))
    pts.append(b)
    return dict(s=s, c=c, sc=sc, st=st, cc=cc, ct=ct, pts=pts, ms=ms, mc=mc, lib=lib, comp=comp)

V = lambda yy: (lambda a, b, p: (p[0], yy))      # déplacement vertical vers y
H = lambda xx: (lambda a, b, p: (xx, p[1]))      # déplacement horizontal vers x
VB = lambda a, b, p: (p[0], b[1])                 # vertical jusqu'au niveau de la cible
HB = lambda a, b, p: (b[0], p[1])                 # horizontal jusqu'à l'abscisse de la cible

aretes = [
    L('Utilisateur', 'h', .5, 'Avis', 'b', .5, [], '1', '*', 'rédige'),
    L('Avis', 'd', .35, 'Vendeur', 'g', aligne('Avis', .35, 'Vendeur'), [], '*', '1', 'concerne'),
    L('Avis', 'b', .85, 'Commande', 'h', .15, [V(ch(1, 30)), HB], '0..1', '1', 'évalue'),
    L('Vendeur', 'b', .5, 'Commande', 'h', .5, [], '1', '*', 'reçoit'),
    L('Vendeur', 'd', aligne('Stock', .35, 'Vendeur'), 'Stock', 'g', .35, [], '1', '*', 'détient'),
    L('Stock', 'd', .4, 'Produit', 'g', aligne('Stock', .4, 'Produit'), [], '*', '1', 'porte sur'),
    L('Utilisateur', 'd', .3, 'Commande', 'g', aligne('Utilisateur', .3, 'Commande'), [], '1', '*', 'passe'),
    L('Commande', 'd', aligne('DetailCommande', .4, 'Commande'), 'DetailCommande', 'g', .4, [], '1', '1..*', 'contient', True),
    L('Produit', 'b', .5, 'DetailCommande', 'h', .6, [V(ch(1, 10)), HB], '1', '*', 'référencé'),
    L('Utilisateur', 'h', .85, 'Vendeur', 'g', .85, [V(ch(1, 45)), H(cv(2, -14)), VB], '1', '0..1', 'profil vendeur'),
    L('Utilisateur', 'g', .4, 'Livreur', 'd', aligne('Utilisateur', .4, 'Livreur'), [], '1', '0..1', 'profil livreur'),
    L('Utilisateur', 'h', .15, 'Notification', 'b', .5, [V(ch(1, 20)), HB], '1', '*', 'reçoit'),
    L('Commande', 'b', .5, 'Livraison', 'h', .5, [], '1', '0..1', 'donne lieu à'),
    L('Commande', 'd', .75, 'Paiement', 'g', .2, [H(cv(3, 10)), VB], '1', '1', 'réglée par'),
    L('Commande', 'b', .15, 'HistoriqueStatut', 'h', .6, [V(ch(2, 30)), HB], '1', '1..*', 'trace', True),
    L('Livreur', 'b', .7, 'Livraison', 'g', .25, [V(ch(2, 10)), H(cv(2, -10)), VB], '0..1', '*', 'effectue'),
    L('Livraison', 'b', .5, 'Quartier', 'h', .5, [], '*', '1', 'vers'),
    L('Quartier', 'g', .3, 'Utilisateur', 'g', .85, [H(cv(1, 12)), VB], '1', '*', 'habite'),
    L('Quartier', 'd', .3, 'Vendeur', 'b', .85, [H(cv(4, 8)), V(ch(1, -30)), HB], '1', '*', 'situé dans'),
    L('Reclamation', 'h', .5, 'Commande', 'g', .85, [V(ch(2, 45)), H(cv(2, -20)), VB], '*', '1', 'porte sur'),
    L('Reclamation', 'd', .2, 'Utilisateur', 'g', .15, [H(cv(1, -10)), VB], '*', '1', 'dépose'),
]
# certaines liaisons partent vers le haut du haut de la boîte : correction du couloir de Quartier→Vendeur
# (le couloir ch(1) se trouve entre les lignes A et B)

# ---------------------------------------------------------------- draw.io
cells = []
nid = [2]
def new_id():
    nid[0] += 1
    return f'c{nid[0]}'
ids = {}
def esc(s): return html.escape(s, quote=True)

for n, (x, yb, w, h) in boites.items():
    i = new_id(); ids[n] = i
    if n in enums:
        cells.append(f'<mxCell id="{i}" value="«enumeration»&lt;br&gt;&lt;b&gt;{n}&lt;/b&gt;" style="swimlane;fontStyle=0;html=1;align=center;verticalAlign=top;childLayout=stackLayout;horizontal=1;startSize={HEAD+16};horizontalStack=0;resizeParent=1;resizeParentMax=0;collapsible=0;marginBottom=0;fillColor={ENUM};fontColor=#FFFFFF;strokeColor={ENUM};fontFamily=Inter;fontSize=12;" vertex="1" parent="1"><mxGeometry x="{x}" y="{yb}" width="{w}" height="{h}" as="geometry"/></mxCell>')
        t = new_id()
        cells.append(f'<mxCell id="{t}" value="{esc("<br>".join(enums[n]))}" style="text;html=1;strokeColor=none;fillColor=#F8FAFC;align=left;verticalAlign=top;spacingLeft=8;fontFamily=Inter;fontSize=12;fontColor={NUIT};" vertex="1" parent="{i}"><mxGeometry y="{HEAD+16}" width="{w}" height="{h-HEAD-16}" as="geometry"/></mxCell>')
        continue
    a, m = classes[n]
    cells.append(f'<mxCell id="{i}" value="{n}" style="swimlane;fontStyle=1;align=center;verticalAlign=top;childLayout=stackLayout;horizontal=1;startSize={HEAD};horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=0;marginBottom=0;fillColor={BORDEAUX};fontColor=#FFFFFF;strokeColor={BORDEAUX};fontFamily=Inter;fontSize=14;swimlaneFillColor=#FFFFFF;" vertex="1" parent="1"><mxGeometry x="{x}" y="{yb}" width="{w}" height="{h}" as="geometry"/></mxCell>')
    ha = 8 + len(a) * LH + 8
    t = new_id()
    cells.append(f'<mxCell id="{t}" value="{esc("<br>".join("- " + s for s in a))}" style="text;html=1;strokeColor=none;fillColor=none;align=left;verticalAlign=top;spacingLeft=8;spacingTop=2;fontFamily=Inter;fontSize=12;fontColor={NUIT};" vertex="1" parent="{i}"><mxGeometry y="{HEAD}" width="{w}" height="{ha}" as="geometry"/></mxCell>')
    t = new_id()
    cells.append(f'<mxCell id="{t}" value="" style="line;strokeWidth=1;fillColor=none;align=left;verticalAlign=middle;strokeColor={BORDEAUX};" vertex="1" parent="{i}"><mxGeometry y="{HEAD+ha}" width="{w}" height="8" as="geometry"/></mxCell>')
    t = new_id()
    cells.append(f'<mxCell id="{t}" value="{esc("<br>".join("+ " + s for s in m))}" style="text;html=1;strokeColor=none;fillColor=none;align=left;verticalAlign=top;spacingLeft=8;spacingTop=2;fontFamily=Inter;fontSize=12;fontColor={BORDEAUX};" vertex="1" parent="{i}"><mxGeometry y="{HEAD+ha+8}" width="{w}" height="{h-HEAD-ha-8}" as="geometry"/></mxCell>')

def rel(n, cote, t):
    return {'h': (t, 0), 'b': (t, 1), 'g': (0, t), 'd': (1, t)}[cote]

for e in aretes:
    i = new_id()
    ex, ey = rel(e['s'], e['sc'], e['st']); nx, ny = rel(e['c'], e['cc'], e['ct'])
    deb = 'startArrow=diamondThin;startFill=1;startSize=16;' if e['comp'] else 'startArrow=none;'
    pts = ''.join(f'<mxPoint x="{p[0]:.0f}" y="{p[1]:.0f}"/>' for p in e['pts'][1:-1])
    cells.append(f'<mxCell id="{i}" value="{esc(e["lib"])}" style="endArrow=none;{deb}html=1;rounded=0;edgeStyle=none;strokeColor={GRIS};strokeWidth=1.3;fontFamily=Inter;fontSize=11;fontColor={GRIS};labelBackgroundColor=#FFFFFF;{"verticalAlign=top;" if len(e["pts"])==2 and e["pts"][0][1]==e["pts"][1][1] else ""}exitX={ex};exitY={ey};exitDx=0;exitDy=0;entryX={nx};entryY={ny};entryDx=0;entryDy=0;" edge="1" parent="1" source="{ids[e["s"]]}" target="{ids[e["c"]]}"><mxGeometry relative="1" as="geometry"><Array as="points">{pts}</Array></mxGeometry></mxCell>')
    for val, pos in ((e['ms'], -0.85), (e['mc'], 0.85)):
        j = new_id()
        cells.append(f'<mxCell id="{j}" value="{esc(val)}" style="edgeLabel;html=1;align=center;verticalAlign=middle;resizable=0;points=[];fontFamily=Inter;fontSize=12;fontStyle=1;fontColor={NUIT};labelBackgroundColor=#FFFFFF;" vertex="1" connectable="0" parent="{i}"><mxGeometry x="{pos}" relative="1" as="geometry"><mxPoint y="-10" as="offset"/></mxGeometry></mxCell>')

titre = new_id()
cells.append(f'<mxCell id="{titre}" value="&lt;b style=&quot;font-size:22px&quot;&gt;GazExpress — Diagramme de classes&lt;/b&gt;&lt;br&gt;&lt;span style=&quot;color:{GRIS}&quot;&gt;Plateforme de commande et de livraison de gaz domestique · Ouagadougou · v1.0 — novembre 2026&lt;/span&gt;" style="text;html=1;align=left;verticalAlign=top;fontFamily=Inter;fontSize=13;fontColor={NUIT};" vertex="1" parent="1"><mxGeometry x="40" y="20" width="1100" height="60" as="geometry"/></mxCell>')
leg = new_id()
legende = ('<b>Légende</b><br>Losange plein : composition<br>Trait simple : association<br>1 : un seul · * : plusieurs · 0..1 : facultatif<br>'
           'Le rôle (client, vendeur, livreur, admin)<br>est porté par Utilisateur ; Vendeur et<br>Livreur sont des profils complémentaires.<br>'
           "État du stock calculé depuis la quantité :<br>0 indisponible · 1 à 4 limité · 5+ disponible")
lx, ly = C(0), boites['Quartier'][1]
cells.append(f'<mxCell id="{leg}" value="{esc(legende)}" style="rounded=1;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacing=14;fillColor={ROSE};strokeColor=#F6D5DB;fontFamily=Inter;fontSize=12;fontColor={NUIT};arcSize=6;" vertex="1" parent="1"><mxGeometry x="{lx}" y="{ly}" width="{W}" height="200" as="geometry"/></mxCell>')

xml = (f'<mxfile host="app.diagrams.net" agent="GazExpress" version="24.7.17"><diagram id="classes" name="Diagramme de classes">'
       f'<mxGraphModel dx="1600" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="{LARG}" pageHeight="{HAUT}" math="0" shadow="0">'
       f'<root><mxCell id="0"/><mxCell id="1" parent="0"/>{"".join(cells)}</root></mxGraphModel></diagram></mxfile>')
open(f'{OUT}/diagramme_classes.drawio', 'w', encoding='utf-8').write(xml)

# ---------------------------------------------------------------- PNG
S = 2
img = Image.new('RGB', (LARG * S, HAUT * S), 'white')
d = ImageDraw.Draw(img)
F = 'C:/Windows/Fonts/'
def font(nom, t):
    try: return ImageFont.truetype(F + nom, t * S)
    except OSError: return ImageFont.truetype(F + 'arial.ttf', t * S)
f12 = font('segoeui.ttf', 12); f12b = font('segoeuib.ttf', 12); f14b = font('segoeuib.ttf', 14); f11 = font('segoeui.ttf', 11)
f22b = font('segoeuib.ttf', 22); f13 = font('segoeui.ttf', 13)
def P(p): return (p[0] * S, p[1] * S)

# liaisons dessinées en premier (sous les boîtes)
for e in aretes:
    pts = [P(p) for p in e['pts']]
    d.line(pts, fill=GRIS, width=int(1.4 * S), joint='curve')
for e in aretes:
    pts = e['pts']
    for val, a, b in ((e['ms'], pts[0], pts[1]), (e['mc'], pts[-1], pts[-2])):
        dx, dy = (b[0] > a[0]) - (b[0] < a[0]), (b[1] > a[1]) - (b[1] < a[1])
        px, py = a[0] + dx * 20, a[1] + dy * 20
        ox, oy = (0, -11) if dy == 0 else (12, 0)
        tw = d.textlength(val, font=f12b) / S
        cx, cy = px + ox, py + oy
        d.text(P((cx - (0 if dy else tw / 2), cy - 8)), val, font=f12b, fill=NUIT)
    # libellé au milieu du plus long segment
    seg = max(zip(pts, pts[1:]), key=lambda s: abs(s[0][0] - s[1][0]) + abs(s[0][1] - s[1][1]))
    mx, my = (seg[0][0] + seg[1][0]) / 2, (seg[0][1] + seg[1][1]) / 2
    tw = d.textlength(e['lib'], font=f11) / S
    court = abs(seg[0][0] - seg[1][0]) + abs(seg[0][1] - seg[1][1]) < 120
    if court and seg[0][1] == seg[1][1]:
        my += 14   # liaison courte horizontale : libellé sous le trait
    d.rectangle([P((mx - tw / 2 - 3, my - 8)), P((mx + tw / 2 + 3, my + 8))], fill='white')
    d.text(P((mx - tw / 2, my - 8)), e['lib'], font=f11, fill=GRIS)

for e in aretes:
    pts = e['pts']
    if e['comp']:
        (x0, y0), (x1, y1) = pts[0], pts[1]
        dx, dy = (x1 > x0) - (x1 < x0), (y1 > y0) - (y1 < y0)
        L_, l_ = 16, 6
        poly = [(x0, y0), (x0 + dx * L_ / 2 - dy * l_, y0 + dy * L_ / 2 + dx * l_), (x0 + dx * L_, y0 + dy * L_), (x0 + dx * L_ / 2 + dy * l_, y0 + dy * L_ / 2 - dx * l_)]
        d.polygon([P(p) for p in poly], fill=NUIT)

for n, (x, yb, w, h) in boites.items():
    enum = n in enums
    coul = ENUM if enum else BORDEAUX
    d.rectangle([P((x, yb)), P((x + w, yb + h))], fill='#F8FAFC' if enum else 'white', outline=coul, width=S)
    hh = HEAD + 16 if enum else HEAD
    d.rectangle([P((x, yb)), P((x + w, yb + hh))], fill=coul)
    if enum:
        t1 = '«enumeration»'; tw = d.textlength(t1, font=f11) / S
        d.text(P((x + w / 2 - tw / 2, yb + 5)), t1, font=f11, fill='#CBD5E1')
        tw = d.textlength(n, font=f14b) / S
        d.text(P((x + w / 2 - tw / 2, yb + 21)), n, font=f14b, fill='white')
        for k, v in enumerate(enums[n]):
            d.text(P((x + 10, yb + hh + 8 + k * LH)), v, font=f12, fill=NUIT)
        continue
    tw = d.textlength(n, font=f14b) / S
    d.text(P((x + w / 2 - tw / 2, yb + 5)), n, font=f14b, fill='white')
    a, m = classes[n]
    yy = yb + HEAD + 8
    for s in a:
        d.text(P((x + 10, yy)), '- ' + s, font=f12, fill=NUIT); yy += LH
    yy += 8
    d.line([P((x, yy - 4)), P((x + w, yy - 4))], fill=BORDEAUX, width=S)
    yy += 4
    for s in m:
        d.text(P((x + 10, yy)), '+ ' + s, font=f12, fill=BORDEAUX); yy += LH

d.text(P((40, 20)), 'GazExpress — Diagramme de classes', font=f22b, fill=NUIT)
d.text(P((40, 54)), 'Plateforme de commande et de livraison de gaz domestique · Ouagadougou · v1.0 — novembre 2026', font=f13, fill=GRIS)
d.rounded_rectangle([P((lx, ly)), P((lx + W, ly + 200))], radius=8 * S, fill=ROSE, outline='#F6D5DB', width=S)
yy = ly + 12
for k, ligne in enumerate(legende.replace('<b>', '').replace('</b>', '').split('<br>')):
    d.text(P((lx + 14, yy)), ligne, font=f12b if k == 0 else f12, fill=NUIT); yy += 19
img.save(f'{OUT}/diagramme_classes.png', optimize=True)
print('ok', LARG, HAUT, len(aretes), 'liaisons')
