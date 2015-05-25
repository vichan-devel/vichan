<?php
$theme = array(
    'name'        => 'rules',
    'description' => 'rules 27chan',
    'version'     => '1.0',

    'config' => array(
        array('title'   => 'Page title',
              'name'    => 'title',
              'type'    => 'text'),

        array('title'   => 'Slogan',
              'name'    => 'subtitle',
              'type'    => 'text',
              'comment' => '(optional)'),

        array('title'   => 'File',
              'name'    => 'file',
              'type'    => 'text',
              'default' => 'rules.html')),

    'build_function'   => 'rules_build');
?>
