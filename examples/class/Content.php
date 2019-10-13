<?php

namespace php\klassen;

use php\klassen\basic\ContentBasic;
use dbapi\interfaces\ModelProps;
use dbapi\interfaces\RestrictedView;

class Content extends ContentBasic implements RestrictedView, ModelProps
{

    protected $complete = 0;

    public function restrictedKey()
    {
        return "id_user";
    }

    public function restrictedValue()
    {
        return $this->id_user;
    }


    public function requiredProps()
    {
        return ['content'];
    }
}
