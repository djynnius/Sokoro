Sokoro
======

PHP Object Relational Mapper - also doubles as an educational too to introduce newbies to Object Relational Mapping in PHP

Just include in your file and use! And dont forget to set your config parameters in cxn.cnf

Setup
=====
Enter your database params in cxn.cnf

	{
	    "adapter": "mysql",
	    "host": "localhost",
	    "user": "root",
	    "pass": "", 
	    "db": "sokoro"
	}


*Currently supported adapters are "mysql", "postgre" and "sqlite" and should be entered as such in the config.

include sokoro without an extension to your php file

	include "sokoro";

if you dont have a config file in your root you may explicitly specify the path to one using the config method:

	Sokoro::config("/path/to/config.file");

next stop to create a table:

	Sokoro::create("table_name", ['col_name'=>"varchar(30)"]);

*note when creating tables Sokoro automaticaly creates the following:

id - auto incrementing pk
created_at timestamp that records time of record creation
created_by integer possibly for link as FK
updated_at timestamp that updates on each update
updated_by int possibly for FK

the id column as PK which auto increments is essential to Sokoro functioning as expected

set the table pointer to the table you intend to work with:

	Sokoro::table("table_name");

To insert a new record:
	
	Sokoro::add(['col_name'=>"value"...]); //alias is Sokoro::insert($records);

this will scafold the rest ie create auto incrementing id, set created on value

To update an existing record:
	
	Sokoro::update('id', ['col_name'=>"value"...]);

this will update the record with the id given. Alternatively:

	Sokoro::update('col', 'val', ['col_name'=>"value"...]);

this will update the table with the new params for columnname with the value val.

deleting a record:

	Sokoro::remove(1);

this will delete record on the set table with id=1

alternatiely:

	Sokoro::remove('col_name', 'value');

will delete all records with the value val on col_name


THE READ VARIANTS
===================

To read all recods in the set table:

	Sokoro::rows(); //alias Sokoro::read();

this returns as array of objects;

It may take an argument which is an sql statement:

	Sokoro::rows("SELECT * FROM another_table LIMIT 14"); //alias Sokoro::read($sql);

to do a search on a column inclusing partial matches 

	Sokoro::find('col_name', 'partial_match');

this returns a row of objects


THE ORMRecord Object
=====================

To get a single record as object:

	$row = Sokoro::row(1); //alias is record()

this will return record with id=1 as object

alternatively:

	$row = Sokoro::row('col_name', 'value');

this will return the most recent record that matches the critera as object

the following actions may be performed on an ORMRecord object:

to see the value of a column on the record

	print $row->col_name;

to set a new value:

	print $row->col_name = "new value";
	$row->commit(); //this writes to DB

to remove the entire record from DB:

	$row->destroy(); //alias $row->delete();

Other ways of accessing single records:

	Sokoro::firstRecord(); //matches first record in the set table

	Sokoro::firstRecord('col_name', 'val'); //matches first record in the set table that matches the criteria

	Sokoro::lastRecord(); //as in firstRecord

	Sokoro::lastRecord('col_name', 'val'); //as in firstRecord

	Sokoro::findFirst('col_name', 'val'); //find first match on the table for the critera

	Sokoro::findLast('col_name', 'val'); //find last match on the table for the critera

	Sokoro::previous(10); //this returns record with id preceeding 10

	Sokoro::next(10); //this returns record with id after 10


Others
======

	Sokoro::truncate(); //truncates the set table
	
	Sokoro::exists(1); //chcks if record with id=1 exists in the DB and returns a boolean

	Sokoro::exists('col_name', 'value'); //checks if record with col_name=val exists in the DB and returns a boolean

	Sokoro::exists(['col_name'=>'value'...]); //checks if record with array pairs as col_name=val exists in the DB and returns a boolean

	Sokoro::count() or Sokoro::length() will return the number of records in the index table


You may change the DB accessed by pointing to a new cnf file:

	Sokoro::config("/path/to/new_config"); //without the .cnf extension

*Sokoro was designed deliberately not to be singleton.