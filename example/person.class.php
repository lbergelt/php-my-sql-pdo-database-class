<?php

use Jgauthi\Component\Database\AbstractCrud;

class Person extends AbstractCrud
{
    // Your Table name
    protected $table = 'persons';

    // Primary Key of the Table
    protected $pk = 'id';
}
