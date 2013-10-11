<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;
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
//$app->register(new FormServiceProvider());
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
	'title' => 'Elf-Pen - Default Admin Panel for Tolkien',
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
 * | POST                     | /admin/posts                 | Save new post               |
 * | GET                      | /admin/post/{id}/edit        | Edit a post                 |
 * | POST                     | /admin/post/{id}/edit        | Update post                 |
 * | DELETE                   | /admin/post/{id}/delete      | Delete a post               |
 * | GET                      | /admin/pages/new             | Add New page                |
 * | POST                     | /admin/pages                 | Save new page               |
 * | GET                      | /admin/page/{id}/edit        | Edit a page                 |
 * | POST                     | /admin/page/{id}/edit        | Update page                 |
 * | DELETE                   | /admin/page/{id}/delete      | Delete a page               |
 * | GET                      | /admin/authors               | List of authors             |
 * | GET                      | /admin/authors/new           | Add new author              |
 * | POST                     | /admin/authors               | Save author                 |
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

$app->match('/admin/check_session', function(Request $request) use($app, $authors) {
	$username = $app['security']->getToken()->getUser()->getUsername();

	foreach ($authors as $key => $value) {
		if( $key == $username ) {
			$app['session']->set('author', array(
				'username' => $key,
				'name' => $value['name'],
				'email' => $value['email'],
				'signature' => $value['signature'],				
				'facebook' => $value['facebook'],
				'twitter' => $value['twitter'],
				'github' => $value['github'],
				'role' => $value['role'],
				));
			break;
		}
	}
	return $app->redirect('/admin/posts');
});

$app->get('/admin/posts', function(Request $request) use($app) {
	$app['posts'] = TolkienFacade::build($app['dir_blog'], 'post');
	return $app['twig']->render('posts.twig', $app['data']);
});

$app->get('/admin/posts/new', function(Request $request) use($app) {
	$app['categories'] = TolkienFacade::build($app['dir_blog'], 'site_category');
	$app['post_categories'] = array();
	$app['form'] = array(
		'title' => '',
		'other_categories' => '',
		'body' => ''
		);
	return $app['twig']->render('post_form.twig', $app['data']);
});

$app->post('/admin/posts', function(Request $request) use($app) {
	$app['categories'] = TolkienFacade::build($app['dir_blog'], 'site_category');
	$app['post_categories'] = array();

	$app['form'] = array(
		'title' => $request->request->get('title'),
		'categories' => $request->request->get('categories'),
		'other_categories' => $request->request->get('other_categories'),
		'body' => $request->request->get('body')
		);

	$constraint = new Assert\Collection(array(
		'title' => new Assert\NotBlank(),
		'body' => new Assert\NotBlank(),
		'categories' => new Assert\NotBlank(),
		'other_categories' => array()
		));

	$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
	if(count($app['errors']) > 0) {
		return $app['twig']->render('post_form.twig', $app['data']);	
	}

	$author = $app['session']->get('author');
	$categories = explode(',', implode(',', $app['form']['categories']) . ',' . $app['form']['other_categories']);

	TolkienFacade::generate($app['dir_blog'], array(
		'type' => 'post',
		'layout' => 'post',
		'title' => $app['form']['title'],
		'author' => array(
			'name' => $author['name'],
			'email' => $author['email'],
			'facebook' => $author['facebook'],
			'twitter' => $author['twitter'],
			'github' => $author['github'],
			'signature' => $author['signature']
			),
		'categories' => $categories,
		'body' => $app['form']['body']
		));

	return $app->redirect('/admin/posts');
});

$app->get('/admin/post/{id}/edit', function(Request $request, $id) use($app) {
	// id is filename, unique property of Post
	$posts = TolkienFacade::build($app['dir_blog'], 'post');
	$app['categories'] = TolkienFacade::build($app['dir_blog'], 'site_category');

	foreach ($posts as $post) {
		if($post->getFileName() == $id) {
			$app['form'] = array(
				'title' => $post->getTitle(),
				'body' => $post->getBody(),
				'other_categories' => ''
				);
			$app['post_categories'] = explode(',', $post->getCategories());
			break;
		}		
	}
	return $app['twig']->render('post_form.twig', $app['data']);
});

$app->post('/admin/post/{id}', function(Request $request, $id) use($app) {
	
});

$app->get('/admin/post/{file}/delete', function(Request $request, $file) use($app) {
	if(unlink($file)) 
		$app['notification'] = "A post has been succesfully deleted";
	else 
		$app['notification'] = "Error when deleted a post";

	return $app->redirect('/admin/posts');
});

$app->get('/admin/pages', function(Request $request) use($app) {
	$app['pages'] = TolkienFacade::build($app['dir_blog'], 'page');
	return $app['twig']->render('pages.twig', $app['data']);
});

$app->get('/admin/pages/new', function(Request $request) use($app) {
	$app['form'] = array(
		'title' => '',
		'body' => ''
		);
	return $app['twig']->render('page_form.twig', $app['data']);
});

$app->post('/admin/pages', function(Request $request) use($app) {
	$app['form'] = array(
		'title' => $request->request->get('title'),
		'body' => $request->request->get('body')
		);

	$constraint = new Assert\Collection(array(
		'title' => new Assert\NotBlank(),
		'body' => new Assert\NotBlank()
		));

	$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
	if(count($app['errors']) > 0) {
		return $app['twig']->render('page_form.twig', $app['data']);	
	}

	TolkienFacade::generate($app['dir_blog'], array(
		'type' => 'page',
		'layout' => 'page',
		'title' => $app['form']['title'],
		'body' => $app['form']['body']
		));

	return $app->redirect('/admin/pages');
});

$app->get('/admin/page/{id}/edit', function(Request $request, $id) use($app) {
	$pages = TolkienFacade::build($app['dir_blog'], 'page');
	foreach ($pages as $page) {
		if($page->getFileName() == $id) {
			$app['form'] = array(
				'title' => $page->getTitle(),
				'body' => $page->getBody()
				);
			break;
		}
	}
	return $app['twig']->render('page_form.twig', $app['data']);
});

$app->post('admin/page/{file}', function(Request $request, $file) use($app) {
	if(unlink($file)) 
		$app['notification'] = "A page has been succesfully deleted";
	else 
		$app['notification'] = "Error when deleted a page";

	return $app->redirect('/admin/pages');
});

$app->get('/admin/page/{id}/delete', function(Request $request, $id) use($app) {

});

$app->get('/admin/authors', function(Request $request) use($app, $authors) {
	$app['authors'] = $authors;
	return $app['twig']->render('authors.twig', $app['data']);
});

$app->get('/admin/authors/new', function(Request $request) use($app) {
	$app['form'] = array(
		'name' => '',
		'email' => '',
		'signature' => '',
		'facebook' => '',
		'twitter' => '',
		'github' => '',
		'username' => ''
		);
	return $app['twig']->render('author_form.twig', $app['data']);
});

$app->post('/admin/authors', function(Request $request) use($app) {
	$app['form'] = array(
		'name' => $request->request->get('name'),
		'email' => $request->request->get('email'),
		'signature' => $request->request->get('signature'),
		'facebook' => $request->request->get('facebook'),
		'twitter' => $request->request->get('twitter'),
		'github' => $request->request->get('github'),
		'username' => $request->request->get('username'),
		'password' => $request->request->get('password'),
		'password_confirmation' => $request->request->get('password_confirmation')
		);

	$constraint = new Assert\Collection(array(
		'name' => new Assert\NotBlank(),
		'email' => new Assert\Email(),
		'signature' => array(),
		'facebook' => array(),
		'twitter' => array(),
		'github' => array(),
		'username' => new Assert\NotBlank(),
		'password' => new Assert\NotBlank(),
		'password_confirmation' => new Assert\NotBlank()
		));

	$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
	if(count($app['errors']) > 0) {
		return $app['twig']->render('author_form.twig', $app['data']);	
	}
	// dammit, cant find simple validation for this
	else if($app['form']['password'] != $app['form']['password_confirmation']) {
		$app['error_confirm'] = "Password and Confirmed Password doesn't match";
		return $app['twig']->render('author_form.twig', $app['data']);		
	}
	return $app->redirect('/admin/authors');
});

$app->match('/admin/author/{id}/edit', function(Request $request, $id) use($app) {

});

$app->get('/admin/author/{id}/delete', function(Request $request, $id) use($app) {

});

$app->match('/admin/setting/edit', function(Request $request) use($app) {
	$app['paginations'] = array('5', '10', '15', '20');
	$app['form'] = array(
		'title' => $app['config']['config']['title'],
		'tagline' => $app['config']['config']['tagline'],
		'pagination' => $app['config']['config']['pagination']
		);

	if('POST' == $request->getMethod()) {
		$app['form'] = array(
			'title' => $request->request->get('title'),
			'tagline' => $request->request->get('tagline'),
			'pagination' => $request->request->get('pagination')
			);

		$constraint = new Assert\Collection(array(
			'title' => new Assert\NotBlank(),
			'tagline' => new Assert\NotBlank(),
			'pagination' => new Assert\NotBlank()
			));

		$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
		if(count($app['errors']) > 0) {
			return $app['twig']->render('setting.twig', $app['data']);	
		}
	}
	return $app['twig']->render('setting.twig', $app['data']);
});

$app->run();