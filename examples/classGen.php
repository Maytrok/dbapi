<?php

use dbapi\db\Database;
use dbapi\tools\ClassGenerator;
use dbapi\tools\ClassGeneratorDB;
use dbapi\tools\EnvReader;

include __DIR__ . "../bin/basic.php";

// DB Init
Database::open(new EnvReader(__DIR__));

// Alternative open with credentials
// Database::openConnection("myUser", "5up3r53cr37");


// Possible Config Options
//abstract => Generate an abstract class in an subfolder called "basic"
// path => the path where the Classes should be generatet - if set to false the whole class will be printed on the screen
// getter => automatic generate getters for the new Class
// setter =>  automatic generate setters for the new Class
// interface => an interface to definet which propertie are needed for an save
// namespace => defines the namespace of the class
$config = ["abstract" => true, "path" => "./php/klassen/", "getter" => true, "setter" => true, "interface" => true, "namespace" => "app"];

//should be called from the root directory - See Path option
// To Generate an class an ID field with Primary option is required

// Generate a Single Class
new ClassGenerator("testTable", "testDb");

new ClassGenerator("anotherTestTable", "testDb", $config);


// Generate a Class for each table in the given db
new ClassGeneratorDB("testDb", $config);
