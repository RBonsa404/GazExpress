<?php
/**
 * Génère les illustrations vectorielles des bouteilles de gaz (public/img/produits).
 * Usage : php docs/sources/bouteilles.php public/img/produits
 */
$sortie = rtrim($argv[1], '/\\');
@mkdir($sortie, 0777, true);

$marques = [
    'sodigaz' => ['nom' => 'SODIGAZ', 'c' => '#1F4E8C', 'f' => '#163A6A', 'h' => '#3D72B8'],
    'total'   => ['nom' => 'TOTAL',   'c' => '#B3261E', 'f' => '#861C16', 'h' => '#D4483F'],
    'oryx'    => ['nom' => 'ORYX',    'c' => '#D27A1F', 'f' => '#A65E14', 'h' => '#EE9A43'],
];

// Géométrie (viewBox 200×260, sol à y=250)
$formats = [
    '6'  => ['x1' => 52, 'x2' => 148, 'haut' => 128, 'bas' => 238, 'epaule' => 30, 'kg' => '6 kg'],
    '12' => ['x1' => 50, 'x2' => 150, 'haut' => 64,  'bas' => 238, 'epaule' => 32, 'kg' => '12,5 kg'],
];

foreach ($marques as $cle => $m) {
    foreach ($formats as $fmt => $g) {
        $x1 = $g['x1']; $x2 = $g['x2']; $h = $g['haut']; $b = $g['bas']; $r = $g['epaule'];
        $w = $x2 - $x1; $cx = ($x1 + $x2) / 2;
        $id = "$cle$fmt";

        // Corps : épaules arrondies en haut, fond légèrement arrondi
        $corps = "M$x1 " . ($h + $r) . " Q$x1 $h " . ($x1 + $r) . " $h H" . ($x2 - $r) . " Q$x2 $h $x2 " . ($h + $r)
               . " V" . ($b - 10) . " Q$x2 $b " . ($x2 - 10) . " $b H" . ($x1 + 10) . " Q$x1 $b $x1 " . ($b - 10) . " Z";

        // Collerette de protection (avec poignée) au-dessus du corps
        $cw = $fmt === '6' ? 52 : 56;
        $chT = $h - 40; $chB = $h + 6;
        $cxl = $cx - $cw / 2; $cxr = $cx + $cw / 2;
        $collerette = "M$cxl $chB V" . ($chT + 6) . " Q$cxl $chT " . ($cxl + 6) . " $chT H" . ($cxr - 6) . " Q$cxr $chT $cxr " . ($chT + 6) . " V$chB Z";
        $poignee = sprintf('<rect x="%s" y="%s" width="%s" height="9" rx="4.5" fill="#0F172A" opacity=".55"/>', $cx - 15, $chT + 7, 30);

        // Robinet
        $robinet = sprintf(
            '<rect x="%s" y="%s" width="14" height="14" rx="2" fill="url(#laiton)"/><rect x="%s" y="%s" width="22" height="5" rx="2" fill="#8A6A2A"/><rect x="%s" y="%s" width="10" height="6" rx="1.5" fill="#6B7280"/>',
            $cx - 7, $chT + 20, $cx - 11, $chT + 17, $cx + 7, $chT + 25
        );

        // Pied (embase) ajouré
        $pied = sprintf(
            '<path d="M%s %s H%s L%s 250 H%s Z" fill="#374151"/><rect x="%s" y="%s" width="16" height="6" rx="3" fill="#1F2937"/><rect x="%s" y="%s" width="16" height="6" rx="3" fill="#1F2937"/>',
            $x1 + 10, $b - 2, $x2 - 10, $x2 - 6, $x1 + 6,
            $cx - 34, $b + 3, $cx + 18, $b + 3
        );

        // Cordon de soudure + bandeau d'étiquette
        $milieu = $fmt === '6' ? $h + 52 : $h + 92;
        $bandeauH = $fmt === '6' ? 38 : 46;
        $bandeauY = $milieu - $bandeauH / 2;
        $soudure = sprintf('<path d="M%s %s H%s" stroke="%s" stroke-width="2" opacity=".7"/>', $x1, $fmt === '6' ? $h + 20 : $h + 40, $x2, $m['f']);
        $tailleNom = $fmt === '6' ? 15 : 17;
        $bandeau = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="#F8FAFC"/>'
            . '<rect x="%s" y="%s" width="%s" height="3" fill="%s"/>'
            . '<text x="%s" y="%s" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="800" font-size="%s" letter-spacing=".5" fill="%s">%s</text>'
            . '<text x="%s" y="%s" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="600" font-size="%s" fill="#334155">%s · Butane</text>',
            $x1, $bandeauY, $w, $bandeauH,
            $x1, $bandeauY + $bandeauH - 3, $w, $m['c'],
            $cx, $bandeauY + ($fmt === '6' ? 17 : 21), $tailleNom, $m['f'], $m['nom'],
            $cx, $bandeauY + ($fmt === '6' ? 30 : 36), $fmt === '6' ? 9.5 : 10.5, $g['kg']
        );

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 260" role="img" aria-label="Bouteille de gaz {$m['nom']} {$g['kg']}">
  <title>Bouteille de gaz {$m['nom']} {$g['kg']}</title>
  <defs>
    <linearGradient id="c$id" x1="0" x2="1" y1="0" y2="0">
      <stop offset="0" stop-color="{$m['f']}"/>
      <stop offset=".32" stop-color="{$m['h']}"/>
      <stop offset=".55" stop-color="{$m['c']}"/>
      <stop offset="1" stop-color="{$m['f']}"/>
    </linearGradient>
    <linearGradient id="laiton" x1="0" x2="1">
      <stop offset="0" stop-color="#9C7A32"/><stop offset=".45" stop-color="#D8B25A"/><stop offset="1" stop-color="#8A6A2A"/>
    </linearGradient>
    <clipPath id="k$id"><path d="$corps"/></clipPath>
  </defs>
  <ellipse cx="$cx" cy="251" rx="{$w}" ry="6" fill="#0F172A" opacity=".08"/>
  $pied
  <path d="$collerette" fill="url(#c$id)"/>
  $poignee
  $robinet
  <path d="$corps" fill="url(#c$id)"/>
  <g clip-path="url(#k$id)">
    $soudure
    $bandeau
    <rect x="{$x1}" y="$h" width="8" height="200" fill="#fff" opacity=".10" transform="translate(18 0)"/>
  </g>
</svg>
SVG;
        file_put_contents("$sortie/$cle-$fmt.svg", $svg);
        echo "$cle-$fmt.svg\n";
    }
}

// Visuel générique (produit d'une marque inconnue)
$gen = file_get_contents("$sortie/sodigaz-12.svg");
$gen = str_replace(['#1F4E8C', '#163A6A', '#3D72B8', 'SODIGAZ'], ['#64748B', '#475569', '#94A3B8', 'GAZ'], $gen);
file_put_contents("$sortie/generique.svg", $gen);
echo "generique.svg\n";
