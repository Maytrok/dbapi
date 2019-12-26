<?php

namespace dbapi\tools;

use dbapi\db\Database;
use PDO;
use Exception;

class ClassGenerator
{

  private $tbname, $db, $body = "";

  private $config = ["abstract" => true, "path" => "./php/klassen/", "getter" => true, "setter" => true, "interface" => true, "namespace" => "php\klassen"];


  public function __construct($tablename, $db, $config = [])
  {

    $this->config = array_merge($this->config, $config);

    $this->tbname = $tablename;
    $this->db = $db;

    $this->primaryKeyAvailable();
    $pdo = Database::getPDO();

    $sth = $pdo->prepare("DESCRIBE " . $db . "." . $tablename);
    $sth->execute();

    $fields = $this->exctractId($sth->fetchAll(PDO::FETCH_COLUMN));
    if ($this->config['abstract']) {
      $this->body = "<?php
            
namespace " . $this->config['namespace'] . "\basic;
      
use dbapi\model\ModelBasic;";
    } else {
      $this->body = "<?php
            
namespace " . $this->config['namespace'] . ";
      
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

      $this->body .= "  protected $" . strtolower($value) . ";
";
    }

    $this->body .= "
          
        ";

    if ($this->config['interface']) {
      $this->requiredProps();
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

      $this->createPath();

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

    if ($this->config['abstract']) {
      if (!is_dir($this->config['path'] . "basic/")) {
        if (!mkdir($this->config['path'] . "basic/")) {
          throw new Exception("The Path " . $this->config['path'] . " could not be created . Are write rights available?");
        }
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
      throw new Exception("ID key is required on table" . $this->tbname);
    }
    return $fields;
  }


  private function requiredProps()
  {

    $this->body .= "
  public function requiredProps()
  {
";

    $ar = [];
    foreach (Database::getPDO()->query("DESCRIBE " . $this->db . "." . $this->tbname) as $value) {

      if ($value['Default'] == null) {

        if ($value["Field"] == "id") {
          continue;
        }

        if ($value["Null"] == "YES") {
          continue;
        }
        $ar[] = strtolower($value["Field"]);
      }
    }

    $suchmuster = '/[\\-\.\(\)]/';
    if (preg_match($suchmuster, implode("\", \"", $ar)) > 0) {
      throw new Exception("One or more Columns from " . $this->tbname . " did not match the regex Pattern");
    }
    $this->body .= "    return [\"" . implode("\", \"", $ar) . "\"];
    
  }";
  }

  private function primaryKeyAvailable()
  {
    foreach (Database::getPDO()->query("DESCRIBE " . $this->db . "." . $this->tbname) as $value) {

      if ($value['Field'] == "id") {
        if (false === strpos($value['Key'], "PRI")) {
          throw new Exception("The ID key has to be an primary key", 500);
        }
      }
    }
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
    return [\"" . implode("\", \"", $fields) . "\"];
  }";
  }

  private function getter($fields)
  {

    foreach ($fields as $value) {
      $this->body .= "
  public function get" . ucfirst(strtolower($value)) . "()
  {
    return \$this->$value;
  }";
    }
  }


  private function setter($fields)
  {

    foreach ($fields as $value) {
      $this->body .= "
  public function set" . ucfirst(strtolower($value)) . "(\$value)
  {
    \$this->$value = \$value;
  }";
    }
  }
}
