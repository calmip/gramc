<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('commentaireexpert_index', new Route(
    '/',
    array('_controller' => 'AppBundle:CommentaireExpert:index'),
    array(),
    array(),
    '',
    array(),
    array('GET')
));

$collection->add('commentaireexpert_show', new Route(
    '/{id}/show',
    array('_controller' => 'AppBundle:CommentaireExpert:show'),
    array(),
    array(),
    '',
    array(),
    array('GET')
));

$collection->add('commentaireexpert_new', new Route(
    '/new',
    array('_controller' => 'AppBundle:CommentaireExpert:new'),
    array(),
    array(),
    '',
    array(),
    array('GET', 'POST')
));

$collection->add('commentaireexpert_edit', new Route(
    '/{id}/edit',
    array('_controller' => 'AppBundle:CommentaireExpert:edit'),
    array(),
    array(),
    '',
    array(),
    array('GET', 'POST')
));

$collection->add('commentaireexpert_delete', new Route(
    '/{id}/delete',
    array('_controller' => 'AppBundle:CommentaireExpert:delete'),
    array(),
    array(),
    '',
    array(),
    array('DELETE')
));

return $collection;
