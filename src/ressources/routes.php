<?php 
/**
 * Route ex:
 * /deeds/france/moselle/algrange/birth?source_type=civil                  => display the list of civil deeds birth
 * /deeds/france/moselle/algrange/birth?source_type=parish                 => display the list of parish deed birth
 * /deeds/france/moselle/algrange/1948/birth/127581?source_type=civil      => display one birth of the civil deed n°127581
 * /deeds/france/moselle/algrange/1948/death?source_type=civil                  => display the list of civil deeds death
 * /deeds/france/moselle/algrange/1948/death/15?source_type=civil               => display one death of the civil deed n°15
 * /deeds/france/moselle/algrange/divers                                      => display the list of the miscellaneous deeds
 */
return [
    'home' => [
        'path' => '/', 
        'controller' => 'App\\Controller\\IndexController', 
        'action' => 'index', 
        'params' => []
    ],
    'deed_index' => [
        'path' => '/deeds', 
        'controller' => 'App\\Controller\\DeedController', 
        'action' => 'index', 
        'params' => ['deed_type'], 
    ],
    'deed_list_miscellaneous' => [
        'path' => '/deeds/([a-z]+/[a-z]+/[a-z]+)/miscellaneous', 
        'controller' => 'App\\Controller\\DeedController', 
        'action' => 'list', 
        'params' => ['locality', 'deed_type'], 
        'options' => ['page' => 1]
    ],
    'deed_list' => [
        'path' => '/deeds/([a-z]+/[a-z]+/[a-z]+)/([/d+]{4})/([a-z]+)', 
        'controller' => 'App\\Controller\\DeedController', 
        'action' => 'list', 
        'params' => ['locality', 'year', 'deed_type'], 
        'options' => ['page' => 1]
    ],
    'deed_detail' => [
        'path' => '/deeds/([a-z]+/[a-z]+/[a-z]+)/([/d+]{4})/([a-z]+)/([/d+]{1,}',  
        'controller' => 'App\\Controller\\DeedController', 
        'action' => 'list', 
        'params' => ['locality', 'year', 'deed_type', 'deed_num'], 
    ],
    'admin_dashboard' => [
        'path' => '/admin/dashboard', 
        'controller' => 'App\\Controller\\AdminDashboardController', 
        'action' => 'dashboard', 
        'params' => []
    ],
    'admin_deed' => [
        'path' => '/admin/deeds', 
        'controller' => 'App\\Controller\\AdminDeedController', 
        'action' => 'index'
    ],
    'admin_deed_list' => [
        'path' => '/admin/deeds/([a-z]+)', 
        'controller' => 'App\\Controller\\AdminDeedController', 
        'action' => 'list', 
        'params' => ['locality', 'deed_type'], 
        'options' => ['page' => 1]
    ],
    'admin_deed_add' => [
        'path' => '/admin/deeds/add', 
        'controller' => 'App\\Controller\\AdminDeedController', 
        'action' => 'edit', 
        'params' => []
    ],
    'admin_deed_modify' => [
        'path' => '/admin/deeds/modify_([/d+]+)', 
        'controller' => 'App\\Controller\\AdminDeedController', 
        'action' => 'edit', 
        'params' => ['deed_num']
    ],
    'admin_deed_remove' => [
        'path' => '/admin/deeds/remove_([/d+]+)', 
        'controller' => 'App\\Controller\\AdminDeedController', 
        'action' => 'remove', 
        'params' => ['locality', 'deed_type', 'deed_num']
    ],
    'admin_user_list' => [
        'path' => '/admin/users', 
        'controller' => 'App\\Controller\\AdminUserController', 
        'action' => 'list', 
        'params' => [],
        'options' => ['page' => 1]
    ],

    
];