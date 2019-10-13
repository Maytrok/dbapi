<?php

namespace php\klassen\basic;

use dbapi\interfaces\ModelProps;
use dbapi\model\JWTAuthenticate;

abstract class UsersBasic extends JWTAuthenticate implements ModelProps
{

  protected $name;
  protected $created_at;
  protected $jwt;
  protected $passwort;




  public function requiredProps()
  {
    return ["name", "jwt", "passwort"];
  }
  public function getName()
  {
    return $this->name;
  }
  public function getCreated_at()
  {
    return $this->created_at;
  }
  public function getJwt()
  {
    return $this->jwt;
  }
  public function getPasswort()
  {
    return $this->passwort;
  }
  public function setName($value)
  {
    $this->name = $value;
  }
  public function setCreated_at($value)
  {
    $this->created_at = $value;
  }
  public function setJwt($value)
  {
    $this->jwt = $value;
  }
  public function setPasswort($value)
  {
    $this->passwort = $value;
  }

  public static function getTableName()
  {
    return "users";
  }
  public static function getDB()
  {
    return "jwt";
  }


  public function getProperties()
  {
    return ["name", "created_at", "jwt", "passwort"];
  }
}
