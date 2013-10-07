<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\SessionServiceProvider;

/* Initialiaze */
$app = new  Silex\Application();

/* Service & Provider */
$app->register(new TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/views',
));
$app->register(new FormServiceProvider());
$app->register(new TranslationServiceProvider(), array(
    'locale_fallbacks' => array('en'),
));
$app->register(new ValidatorServiceProvider());
$app->register(new SecurityServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new SessionServiceProvider());


/* Data & callback */
$app['data'] = array(
	'title' => 'Elf-Pen Default Admin Panel for Tolkien Static Web Generator',
	'footer' => 'Copyright © 2013, Glend Maatita ®'
);


$app['security.firewalls'] = array(
		'admin' => array(
			'pattern' => '^/admin',
			'form' => array(
				'login_path' => '/login', 
				'check_path' => '/admin/login_check'
				),
			'logout' => array('logout_path' => '/admin/logout'),
			'users' => array(
				'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg==')
				)
			)
	);

	
$app['debug'] = true;

/**
 * Application Routes
 */

$app->get('/', function(Request $request) use($app) {
	return $app->redirect('/admin/events/grab');
});

/** Login **/
$app->get('/login', function(Request $request) use($app) {
	return $app['twig']->render('admin/login.tpl', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->run();