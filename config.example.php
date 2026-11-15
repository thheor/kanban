<?php
declare(strict_types=1);
/**
 * Copy this file to config.php and adjust it for your machine.
 * config.php is gitignored — each developer keeps their own.
 *
 *   Linux (Docker):   port 3307, user kanban, pass kanban
 *   Windows (XAMPP):  port 3306, user root,   pass ''
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3307,
        'name' => 'kanban',
        'user' => 'kanban',
        'pass' => 'kanban',
    ],
];
