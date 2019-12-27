<?php

namespace dbapi\controller;

use dbapi\model\ModelBasic;

abstract class SlimAdapter extends Api
{

    // Slim Container
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
        // Init Model
        parent::__construct($this->getSlimModel());
    }

    protected function output()
    {
        // Prevent Default output
    }

    /**
     * @return ModelBasic
     */
    abstract protected function getSlimModel();

    public function __invoke($request, $response, $args)
    {


        // If id is provided through the URI
        if (isset($args['id'])) {
            $_GET['id'] = $args['id'];
        }
        $this->run();
        $payload = json_encode($this->view->getMainData());
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
