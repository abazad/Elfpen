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
	'footer' => '&copy; 2013, Glend Maatita 2013'
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
 * Routes
 *
 * +--------------------------+------------------------------+-----------------------------+
 * | Method                   | Path                         | Description                 |
 * +--------------------------+------------------------------+-----------------------------+
 * | GET                      | /login                       | Admin panel login           |
 * | GET                      | /admin/logout                | Logout                      |
 * | GET                      | /admin/dashboard             | Dashboard                   |
 * | GET                      | /admin/posts                 | List of posts               |
 * | GET                      | /admin/posts/new             | Add New post                |
 * | POST                     | /admin/posts/new             | Save new post               |
 * | GET                      | /admin/post/{id}/edit        | Edit a post                 |
 * | POST                     | /admin/post/{id}/edit        | Update post                 |
 * | DELETE                   | /admin/post/{id}/delete      | Delete a post               |
 * | GET                      | /admin/pages/new             | Add New page                |
 * | POST                     | /admin/pages/new             | Save new page               |
 * | GET                      | /admin/page/{id}/edit        | Edit a page                 |
 * | POST                     | /admin/page/{id}/edit        | Update page                 |
 * | DELETE                   | /admin/page/{id}/delete      | Delete a page               |
 * | GET                      | /admin/authors               | List of authors             |
 * | GET                      | /admin/authors/new           | Add new author              |
 * | POST                     | /admin/authors/new           | Save author                 |
 * | GET                      | /admin/author/{id}/edit      | Edit a author               |
 * | POST                     | /admin/author/{id}/edit      | Update author               |
 * | DELETE                   | /admin/author/{id}/delete    | Delete a author             |
 * | GET                      | /admin/setting               | Edit Setting                |
 * | POST                     | /admin/setting               | Update Setting              |
 * +--------------------------+------------------------------+-----------------------------+
 */

$app->get('/', function(Request $request) use($app) {

});

/** Login **/
$app->get('/login', function(Request $request) use($app) {
	return $app['twig']->render('admin/login.tpl', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/admin/posts', function(Request $request) use($app) {

});

$app->match('/admin/posts/new', function(Request $request) use($app) {

});

$app->match('/admin/post/{id}/edit', function(Request $request, $id) use($app) {

});

$app->get('/admin/post/{id}/delete', function(Request $request, $id) use($app) {

});

$app->get('/admin/pages/', function(Request $request) use($app) {

});

$app->match('/admin/pages/new', function(Request $request) use($app) {

});

$app->match('/admin/page/{id}/edit', function(Request $request, $id) use($app) {

});

$app->get('/admin/page/{id}/delete', function(Request $request, $id) use($app) {

});

$app->get('/admin/authors', function(Request $request) use($app) {

});

$app->match('/admin/authors/new', function(Request $request) use($app) {

});

$app->match('/admin/author/{id}/edit', function(Request $request, $id) use($app) {

});

$app->get('/admin/author/{id}/delete', function(Request $request, $id) use($app) {

});

$app->match('/admin/setting', function(Request $request, $id) use($app) {

});

$app->run();