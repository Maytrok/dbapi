<?php

namespace dbapi\tools;

use dbapi\db\Database;
use PDO;
use Exception;

class ClassGenerator
{

  private $tbname, $db, $body = "";

  private $config = ["abstract" => true, "path" => "./php/klassen/", "getter" => true, "setter" => true, "interface" => false];


  public function __construct($tablename, $db, $config = [])
  {

    $this->config = array_merge($this->config, $config);

    $this->tbname = $tablename;
    $this->db = $db;

    $pdo = Database::getPDO();

    $sth = $pdo->prepare("DESCRIBE " . $db . "." . $tablename);
    $sth->execute();

    $fields = $this->exctractId($sth->fetchAll(PDO::FETCH_COLUMN));
    if ($this->config['abstract']) {
      $this->body = "<?php
            
namespace php\klassen\basic;
      
use dbapi\model\ModelBasic;";
    } else {
      $this->body = "<?php
            
namespace php\klassen;
      
use dbapi\model\ModelBasic;";
    }



    if ($this->config['interface']) {
      $this->body .= "
use dbapi\interfaces\ModelProps;";
    }

    $this->body .= "

";

    if ($this->config['abstract']) {
      $this->body .= "abstract ";
    }
    $this->body .= "class " . ucfirst($tablename);

    if ($this->config['abstract']) {
      $this->body .= "Basic";
    }

    $this->body .= " extends ModelBasic ";
    if ($this->config['interface']) {
      $this->body .= "implements ModelProps ";
    }

    $this->body .= "
{
    
";

    foreach ($fields as $value) {
      if ($value == "id") continue;

      $this->body .= "  protected $" . $value . ";
";
    }

    $this->body .= "
        
        
        ";

    if ($this->config['interface']) {
      $this->requiredProps($fields);
    }

    if ($this->config['getter']) {

      $this->getter($fields);
    }
    if ($this->config['setter']) {
      $this->setter($fields);
    }

    $this->abstractMethods($fields);



    $this->body .= "
}";

    if ($this->config['path'] === false) {
      echo substr($this->body, 1);
    } else {

      $path = $this->config['abstract'] ? $this->config['path'] . "basic/" . ucfirst($this->tbname) . "Basic" : $this->config['path'] . ucfirst($this->tbname);

      $this->createPath($path);

      $handle = fopen($path . ".php", $this->config['abstract'] ? 'w' : 'x');
      fwrite($handle, $this->body);
      fclose($handle);
    }
  }

  private function createPath()
  {
    if (!is_dir($this->config['path'])) {
      if (!mkdir($this->config['path'])) {
        throw new Exception("The Path " . $this->config['path'] . " could not be created . Are write rights available?");
      }
    }
  }

  private function exctractId($fields)
  {

    $found = false;

    foreach ($fields as $key => $value) {
      if ($value == "id") {
        unset($fields[$key]);
        $found = true;
      }
    }

    if (!$found) {
      throw new Exception("ID key is required");
    }
    return $fields;
  }


  private function requiredProps($fields)
  {

    $this->body .= "
  public function requiredProps()
  {
    return [\"" . implode("\",\"", $fields) . "\"];

  }";
  }


  private function abstractMethods($fields)
  {
    $this->body .= "
        
  public static function getTableName()
  {
    return \"" . $this->tbname . "\";
  }";
    $this->body .= "
  public static function getDB()
  {
    return \"" . $this->db . "\";
  }
  ";

    $this->body .= "
        
  public function getProperties()
  {
    return [\"" . implode("\",\"", $fields) . "\"];
  }";
  }

  private function getter($fields)
  {

    foreach ($fields as $value) {
      $this->body .= "
  public function get" . ucfirst($value) . "()
  {
    return \$this->$value;
  }";
    }
  }


  private function setter($fields)
  {

    foreach ($fields as $value) {
      $this->body .= "
  public function set" . ucfirst($value) . "(\$value)
  {
    \$this->$value = \$value;
  }";
    }
  }
}
