<?php
            
namespace php\klassen\basic;
      
use dbapi\model\ModelBasic;
use dbapi\interfaces\ModelProps;

abstract class ContentBasic extends ModelBasic implements ModelProps 
{
    
  protected $id_user;
  protected $content;
  protected $complete;
  protected $created_at;
  protected $update_at;

        
        
        
  public function requiredProps()
  {return ["id_user","content"];

  }
  public function getId_user()
  {
    return $this->id_user;
  }
  public function getContent()
  {
    return $this->content;
  }
  public function getComplete()
  {
    return $this->complete;
  }
  public function getCreated_at()
  {
    return $this->created_at;
  }
  public function getUpdate_at()
  {
    return $this->update_at;
  }
  public function setId_user($value)
  {
    $this->id_user = $value;
  }
  public function setContent($value)
  {
    $this->content = $value;
  }
  public function setComplete($value)
  {
    $this->complete = $value;
  }
  public function setCreated_at($value)
  {
    $this->created_at = $value;
  }
  public function setUpdate_at($value)
  {
    $this->update_at = $value;
  }
        
  public static function getTableName()
  {
    return "content";
  }
  public static function getDB()
  {
    return "jwt";
  }
  
        
  public function getProperties()
  {
    return ["id_user","content","complete","created_at","update_at"];
  }
}