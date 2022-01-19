<?php

$handler = 'lmdb';
$db_filename = __DIR__ . '/../data/phpindex/test0.mdb';

$lmdb_h = dba_open($db_filename, 'c', $handler, 0644, 5 * 1048576);
for ($i = 0; $i < 50000; $i++) {
    dba_insert('key' . $i, 'value ' . $i, $lmdb_h);
}
dba_sync($lmdb_h);
dba_close($lmdb_h);
unlink($db_filename . '-lock');

$lmdb_h = dba_open($db_filename, 'rd', $handler, 0644, 5 * 1048576);
for ($i = 0; $i < 50000; $i++) {
    print_r(dba_fetch('key' . $i, $lmdb_h));
}

dba_close($lmdb_h);
unlink($db_filename . '-lock');

echo "\ndone\n";
