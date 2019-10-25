<?php

use dbapi\controller\Api;
use dbapi\db\Database;
use dbapi\interfaces\DefaultView;
use php\klassen\Content;
use dbapi\tools\EnvReader;

include __DIR__ . "/../bin/basic.php";

// DB Init
Database::open(new EnvReader(__DIR__));

// Alternative open with credentials
// Database::openConnection("myUser", "5up3r53cr37");


// Should be in an autoloader
include_once './class/basic/ContentBasic.php';
include_once './class/Content.php';


// Init the DB Model
$content = new Content();

/**
 * Init an Api Controller will Provide full CRUD functionality for an DB Table
 * The model has to be inheriate the ModelBasic class
 * GET Request can be to an single or for all Items =>                      https://example.com/?id=10
 *                                                                          https://example.com/
 * To enable Pagination, submit the GET Params per_page and page =>         https://example.com/?per_page=5&page=2
 * to Count the posible results just submit the count Param =>              https://example.com/?count
 * additional parameters can be passed to limit the result =>               https://example.com/?complete=1
 * 
 * For an DELETE OR Patch request, an ID is required =>                     https://example.com/?id=69
 */
$api = new Api($content);


// Disable DELETE method to prevent deleting items
$api->disallowMethod(Api::$DELETE);


// The output hook is called at each output
// To modify the output just return the edited output
$api->hookOutput(function (DefaultView $view, $REQUEST_METHOD) {

    // Adding greeting to each GET Request
    if ($REQUEST_METHOD == "GET") {
        $view->setData(['greetings' => "Hello there"]);
    }

    return $view;
});


$api->run();
