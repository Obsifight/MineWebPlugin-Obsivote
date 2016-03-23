<?php
Router::connect('/vote', array('controller' => 'voter', 'action' => 'index', 'plugin' => 'obsivote'));
Router::connect('/vote/*', array('controller' => 'voter', 'action' => 'index', 'plugin' => 'obsivote'));
Router::connect('/voter', array('controller' => 'voter', 'action' => 'index', 'plugin' => 'obsivote'));
Router::connect('/voter/*', array('controller' => 'voter', 'action' => 'index', 'plugin' => 'obsivote'));

Router::connect('/admin/Obsivote/*', array('controller' => 'voter', 'action' => 'index', 'plugin' => 'obsivote', 'prefix' => 'admin'));
