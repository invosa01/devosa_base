<?php
// Example 1
// ---------
$books = [];
$books[] = ['title' => 'Robot Dreams', 'author' => 'Isaac Asimov', 'price' => 7.99, 'stock' => 11];
$books[] = ['title'  => 'The Hitch Hiker\'s Guide to the Galaxy',
			'author' => 'Douglas Adams',
			'price'  => 22.17,
			'stock'  => 5
];
$books[] = ['title' => 'A.I: Artificial Intelligence', 'author' => '', 'price' => 20.23, 'stock' => 1];
$books[] = ['title' => 'The Da Vinci Code', 'author' => 'Dan Brown', 'price' => 23.12, 'stock' => 0];
$books[] = ['title' => '1634: The Ram Rebellion', 'author' => 'Eric Flint', 'price' => 15.75, 'stock' => 7];
$books[] = ['title'  => 'The Possibility of an Island',
			'author' => 'Michel Houellebecq',
			'price'  => 16.47,
			'stock'  => 10
];
$books[] = ['title' => 'Among the Free', 'author' => 'Margaret Peterson Haddix', 'price' => 11.02, 'stock' => 15];
$books[] = ['title' => 'Pushing Ice', 'author' => 'Alastair Reynolds', 'price' => 16.35, 'stock' => 8];
$books[] = ['title' => 'Dragon\'s Fire', 'author' => 'Anne McCaffrey', 'price' => 16.47, 'stock' => 2];
$books[] = ['title' => 'The Last Siege, The Final Truth', 'author' => 'John Ostrander', 'price' => 11.67, 'stock' => 5];
// Example 2
// ---------
$tasks = [];
$tasks[] = ['t_id' => 1, 'title' => 'Screen Main'];
$tasks[] = ['t_id' => 2, 'title' => 'Screen Search'];
$tasks[] = ['t_id' => 3, 'title' => 'Screen Convert'];
$tasks[] = ['t_id' => 4, 'title' => 'Export Module'];
$tasks[] = ['t_id' => 5, 'title' => 'Import Module'];
$tasks[] = ['t_id' => 6, 'title' => 'Admin Module'];
$tasks[] = ['t_id' => 7, 'title' => 'Archive Module'];
$tasks[] = ['t_id' => 8, 'title' => 'Mac OSX compatibility'];
$tasks[] = ['t_id' => 9, 'title' => 'Debugging'];
$tasks[] = ['t_id' => 10, 'title' => 'New queries'];
$employees = [];
$employees[] = ['e_id' => 1, 'fname' => 'Boby', 'lname' => 'Green'];
$employees[] = ['e_id' => 2, 'fname' => 'Julie', 'lname' => 'Robinet'];
$employees[] = ['e_id' => 3, 'fname' => 'Marc', 'lname' => 'Plonckt'];
$employees[] = ['e_id' => 4, 'fname' => 'Steeve', 'lname' => 'Mac King'];
$employees[] = ['e_id' => 5, 'fname' => 'John', 'lname' => 'Travalto'];
$employees[] = ['e_id' => 6, 'fname' => 'Mary', 'lname' => 'Douglas'];
$times = [];
$times[] = ['t_id' => 10, 'e_id' => 1, 'hour' => 0.5];
$times[] = ['t_id' => 2, 'e_id' => 1, 'hour' => 1.0];
$times[] = ['t_id' => 7, 'e_id' => 1, 'hour' => 2.0];
$times[] = ['t_id' => 5, 'e_id' => 2, 'hour' => 1.5];
$times[] = ['t_id' => 8, 'e_id' => 2, 'hour' => 1.5];
$times[] = ['t_id' => 2, 'e_id' => 2, 'hour' => 2.0];
$times[] = ['t_id' => 7, 'e_id' => 3, 'hour' => 1.5];
$times[] = ['t_id' => 8, 'e_id' => 3, 'hour' => 0.5];
$times[] = ['t_id' => 6, 'e_id' => 3, 'hour' => 1.5];
$times[] = ['t_id' => 9, 'e_id' => 4, 'hour' => 0.5];
$times[] = ['t_id' => 10, 'e_id' => 4, 'hour' => 2.0];
$times[] = ['t_id' => 4, 'e_id' => 4, 'hour' => 1.5];
$times[] = ['t_id' => 6, 'e_id' => 5, 'hour' => 1.0];
$times[] = ['t_id' => 3, 'e_id' => 5, 'hour' => 1.5];
$times[] = ['t_id' => 1, 'e_id' => 5, 'hour' => 2.0];
$times[] = ['t_id' => 1, 'e_id' => 6, 'hour' => 1.0];
$times[] = ['t_id' => 9, 'e_id' => 6, 'hour' => 1.5];
$times[] = ['t_id' => 3, 'e_id' => 6, 'hour' => 0.5];
$times[] = ['t_id' => 5, 'e_id' => 6, 'hour' => 0.5];
// Rearange times data
$times2 = [];
foreach ($times as $rec) {
	$times2[$rec['t_id']][$rec['e_id']] = $rec['hour'];
}
?>