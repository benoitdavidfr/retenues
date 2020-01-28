<?php
/*PhpDoc:
name: index.php
title: index.php - script exécuté pour l'API
doc: |
  Ce script correspond à la définition https://app.swaggerhub.com/apis/benoitdavidfr/retenue/0.1
  l'URL http://retenues.geoapi.fr est redirigée vers /prod/geoapi/retenues/index.php/
journal: |
  28/1/2020:
    création
*/
if (0) { // Mise hors service 
  header("HTTP/1.1 500 Internal Server Error");
  header('Content-type: text/plain; charset="utf8"');
  die("API indisponible\n");
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/dept.inc.php';

use Symfony\Component\Yaml\Yaml;

if (0) {
  echo "index.php<br>\n";
  if (isset($_SERVER['PATH_INFO']))
    echo "_SERVER[PATH_INFO]=$_SERVER[PATH_INFO]<br>\n";
  else
    echo "_SERVER[PATH_INFO] indéfini<br>\n";
  echo "<pre>_SERVER="; print_r($_SERVER); echo "</pre>\n";
}

// identifie le type MIME demandé parmi ceux transmis
function http_accept(array $mimeTypes): ?string {
  if (!isset($_SERVER['HTTP_ACCEPT']))
    return null;
  $http_accepts = explode(',',$_SERVER['HTTP_ACCEPT']);
  //echo "<pre>http_accepts="; print_r($http_accepts); echo "</pre>\n"; die();
  foreach ($http_accepts as $http_accept)
    if (in_array($http_accept, $mimeTypes))
      return $http_accept;
  return null;
}

function ahref(string $url): string {
  return "<a href='$url'>$url</a>";
}


// /
// affichage de la spec Swagger en JSON
if (!isset($_SERVER['PATH_INFO']) || ($_SERVER['PATH_INFO']=='/')
    || ($_SERVER['PATH_INFO']=='/v1') || ($_SERVER['PATH_INFO']=='/v1/')) {
  header('Content-type: application/json; charset="utf8"');
  $apidef = Yaml::parse(file_get_contents(__DIR__.'/apidef.yaml'));
  die(json_encode($apidef, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}


// /terms
if (preg_match('!^/(v1/)?terms$!', $_SERVER['PATH_INFO'])) {
  header('Content-type: text/html; charset="utf8"');
  echo file_get_contents(__DIR__.'/terms.html');
  die();
}


// /retenues
if (preg_match('!^/(v1/)?retenues$!', $_SERVER['PATH_INFO'])) {
  $departement = (isset($_GET['departement']) ? $_GET['departement'] : null);
  
  $features = [];

  if (!($file = fopen(__DIR__."/data/retenues-20200121-Occitanie.csv",'r')))
    die("Erreur ouverture du fichier retenues-20200121-Occitanie.csv");

  $header = fgetcsv($file, 1024, ';', '"');
  while ($record = fgetcsv($file, 1024, ';', '"')) {
    foreach ($header as $i => $k)
      $rec[$k] = $record[$i];
    $coord = [floatval(str_replace(',','.',$rec['Lon'])), floatval(str_replace(',','.',$rec['Lat']))];
    //print_r($rec);
    //echo $rec['Date construction'];
    $usages = explode('/', $rec['Usages']);
    foreach ($usages as $i => $usage)
      $usages[$i] = trim($usage);
    $features[] = [
      'type'=> 'Feature',
      'properties'=> [
        'num' => intval($rec['Num']),
        'nom' => $rec['Nom'],
        'nature' => $rec['Nature'],
        'nomRegion' => $rec['Région'],
        'departement' => Depts::idFromName($rec['Département']),
        'nomDepartement' => $rec['Département'],
        'nomCommune' => $rec['Commune'],
        'riviere' => $rec['Riviere'],
        'annee_construction' => $rec['Date construction'],
        'volume(Mm3)' => floatval(str_replace(',','.',$rec['Volume (Mm3)'])),
        'superficie(ha)' => floatval(str_replace(',','.',$rec['superficie (ha)'])),
        'altitude(mNGF)' => floatval(str_replace(',','.',$rec['altitude (mNGF)'])),
        'usages' => $usages,
      ],
      'geometry'=> [
        'type'=> 'Point',
        'coordinates'=> $coord,
      ],
    ];
  }
  header('Content-Type: application/json');
  echo json_encode(['type'=>'FeatureCollection', 'features'=>$features],
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /retenues/{num}
if (preg_match('!^/(v1/)?retenues/(\d+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $num = $matches[1];
  
  /*
  if (!($file = fopen(__DIR__."/data/retenues-20200121-Occitanie.csv",'r')))
    die("Erreur ouverture du fichier retenues-20200121-Occitanie.csv");
  */

  $nom = 'Astarac';

  if (!($file = fopen(__DIR__."/data/retenue-$nom.csv",'r')))
    die("Erreur ouverture du fichier retenue-$nom.csv");

  fgetcsv($file, 1024, ';', '"'); // ligne avec le nom du barrage
  $header = fgetcsv($file, 1024, ';', '"');

  $mesures = []; // [ date (jj/mm/aaaa) => [côte (m), volume (Mm3), surface (ha)]]
  while ($record = fgetcsv($file, 1024, ';', '"')) {
    preg_match('!^(\d+)/(\d+)/(\d+)$!', $record[0], $matches);
    $mesures[] = [
      'date'=> "$matches[3]-$matches[2]-$matches[1]",
      'cote_bassin(m)'=> floatval(str_replace(',','.',$record[1])),
      'volume(Mm3)'=> floatval(str_replace(',','.',$record[2])),
      'surface(ha)'=> floatval(str_replace(',','.',$record[3])),
    ];
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode(['num'=> 21, 'mesures'=> $mesures],
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}


header('HTTP/1.1 400 Bad Request');
echo "Unknown query\n";
die();
