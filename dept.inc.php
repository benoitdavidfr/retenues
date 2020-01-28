<?php
// Classe pour calculer le numéro de dept à partir du nom

require_once __DIR__.'/vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

class Depts {
  static $deptmetro = null;
  
  static function idFromName(string $name): ?string {
    if (!self::$deptmetro) {
      self::$deptmetro = Yaml::parse(file_get_contents(__DIR__.'/deptmetro.yaml'));
    }
    foreach (self::$deptmetro['data'] as $cinsee => $dept) {
      if ($dept['title'] == $name)
        return $cinsee;
    }
    return null;
  }
};

//echo Depts::idFromName('Haute-Garonne');