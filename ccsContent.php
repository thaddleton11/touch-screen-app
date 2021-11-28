<?php


$container = $app->getContainer();
$container->set('ccsContentController', function (\Psr\Container\ContainerInterface $c) {
	return new \gem\controllers\admin\ccs\ccsContentController(
		$c->get('adminView'),
		$c->get('flash'),
		$c->get('ccsContentRepository'),
		$c->get('ccsEventsRepository')
	);
});