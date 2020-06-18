<?php
use Jgauthi\Component\Database\Db;

// In this example, the vendor folder is located in "example/"
require_once __DIR__.'/../vendor/autoload.php';

//-- Configuration (edit here) ------------------------
$dbhost ??= 'localhost';
$dbuser ??= 'root';
$dbpass ??= 'root';
$dbname ??= 'dbname';
$dbport ??= 3306;
//-----------------------------------------------------

function d(string $value, string $title = ''): void
{
    echo '<h3>'.$title.'</h3>';
    var_dump($value);
}

// Creates the instance
$db = new db($dbhost, $dbuser, $dbpass, $dbname, $dbport);
