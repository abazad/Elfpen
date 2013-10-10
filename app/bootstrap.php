<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\SessionServiceProvider;

/** Tolkien Namespace **/
use Tolkien\Facades\Tolkien as TolkienFacade;

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

/** Basic Configuration **/
$app['dir_blog'] = realpath(dirname(__DIR__) .  '/blog');
define('CONFIG', $app['dir_blog'] . '/config.yml');

/* Data & callback */
$app['data'] = array(
	'title' => 'Elf-Pen Default Admin Panel for Tolkien Static Web Generator',
	'footer' => '&copy; 2013, Glend Maatita 2013'
);

//$app['blog_name'] = 'blog';
$app['config'] = Yaml::parse(file_get_contents(CONFIG));
/** end of data **/

/** Authentication & Authorization **/
$authors = Yaml::parse(file_get_contents($app['dir_blog'] . '/author.yml'));
$authors_parse = array();
foreach ($authors as $key => $value) {
	$authors_parse[$key] = array($value['role'], $value['password']);
}

$app['security.firewalls'] = array(
	'admin' => array(
		'pattern' => '^/admin',
		'form' => array(
			'login_path' => '/login',
			'check_path' => '/admin/login_check'
			),
		'logout' => array('logout_path' => '/admin/logout'),
		'users' => $authors_parse			
		)
	);
/** End of authentication **/
	
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

$app->get('/signup', function(Request $request) use($app) {

});

/** Login **/
$app->get('/login', function(Request $request) use($app) {
	return $app['twig']->render('login.twig', array(
		'error'	=> $app['security.last_error']($request),
		'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/admin/posts', function(Request $request) use($app) {
	$app['posts'] = TolkienFacade::build($app['dir_blog'], 'post');
	return $app['twig']->render('posts.twig', $app['data']);
});

$app->match('/admin/posts/new', function(Request $request) use($app) {
	$form = $app['form.factory']->createBuilder('form') 
		->add('title', 'text', array('constraints' => new Assert\NotBlank()))
		->add('body', 'textarea', array('constraints' => new Assert\NotBlank()))
		->add('categories', 'choice', array(
			'choices' => $arrKota,
			'multiple' => true,
			'expanded' => true
			))->getForm();

	if('POST' == $request->getMethod())
	{

	}

});

$app->match('/admin/post/{id}/edit', function(Request $request, $id) use($app) {

});

$app->get('/admin/post/{id}/delete', function(Request $request, $id) use($app) {

});

$app->get('/admin/pages/', function(Request $request) use($app) {
	$app['pages'] = TolkienFacade::build($app['dir_blog'], 'page');
	return $app['twig']->render('pages.twig', $app['data']);
});

$app->match('/admin/pages/new', function(Request $request) use($app) {

});

$app->match('/admin/page/{id}/edit', function(Request $request, $id) use($app) {

});

$app->get('/admin/page/{id}/delete', function(Request $request, $id) use($app) {

});

$app->get('/admin/authors', function(Request $request) use($app, $authors) {
	$app['authors'] = $authors;
	return $app['twig']->render('authors.twig', $app['data']);
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