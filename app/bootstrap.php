<?php

require_once __DIR__.'/../vendor/autoload.php';

/** Using Composer package **/
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\SessionServiceProvider;
/** Tolkien Namespace **/
use Tolkien\Facades\Tolkien as TolkienFacade;
use Tolkien\Init;
/** End of Composer Package List **/

/* Initialiaze */
$app = new  Silex\Application();

/* Service & Provider */
$app->register(new TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/views',
));

$app->register(new TranslationServiceProvider(), array(
    'locale_fallbacks' => array('en'),
));
$app->register(new ValidatorServiceProvider());
$app->register(new SecurityServiceProvider());
$app->register(new UrlGeneratorServiceProvider());
$app->register(new SessionServiceProvider());
/** End Of Service Provide **/

/** Basic Configuration **/
$app['dir_blog'] = realpath(dirname(__DIR__)) .  '/public/blog';

$authors = array();
$authors_parse = array();
$dumper = new Dumper();
/** End of Configuration **/

/* Data & callback */
$app['data'] = array(
	'title' => 'Elf-Pen - Default Admin Panel for Tolkien',
	'footer' => 'Copyright © 2013, KodeTalk ®'
);

$config_url = '';
$author_url = $app['dir_blog'] . '/author.yml';

if(file_exists($app['dir_blog'])) 
{
	$config_url = $app['dir_blog'] . '/config.yml';
	$app['config'] = Yaml::parse(file_get_contents($config_url));

	$authors = Yaml::parse(file_get_contents($app['dir_blog'] . '/author.yml'));
	$authors_parse = array();
	foreach ($authors as $key => $value) {
		$authors_parse[$key] = array($value['role'], $value['password']);
	}
}
/** end of Data **/

/** Authentication & Authorization **/
$app['security.encoder.digest'] = $app->share(function ($app) {
		return new MessageDigestPasswordEncoder('sha1', false, 1);
});

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

$app->match('/install', function(Request $request) use($app, $dumper, $author_url) {

	if(file_exists($app['dir_blog'])) {
		return $app->redirect('/login');
	}

	$app['form'] = array(
		'name' => '',
		'email' => '',
		'username' => '',
		'password' => '',
		'password_confirmation' => ''
		);

	if('POST' == $request->getMethod()) {
		$app['form'] = array(
			'name' => $request->request->get('name'),
			'email' => $request->request->get('email'),
			'username' => $request->request->get('username'),
			'password' => $request->request->get('password'),
			'password_confirmation' => $request->request->get('password_confirmation')
		);

		$constraint = new Assert\Collection(array(
			'name' => new Assert\NotBlank(),
			'email' => array(new Assert\NotBlank(), new Assert\Email()),
			'username' => new Assert\NotBlank(),
			'password' => new Assert\NotBlank(),
			'password_confirmation' => new Assert\NotBlank()
		));

		$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
		if(count($app['errors']) > 0) {
			return $app['twig']->render('install.twig', $app['data']);	
		}
		else if($app['form']['password'] != $app['form']['password_confirmation']) {
			$app['error_confirm'] = "Password and Confirmed Password doesn't match";
			return $app['twig']->render('install.twig', $app['data']);
		}

		$init = new Init('blog');
		$init->create();

		$administrator = array(
			$app['form']['username'] => array(
				'name' => $app['form']['name'],
				'email' => $app['form']['email'],
				'username' => $app['form']['username'],
				'password' => sha1($app['form']['password']),
				'signature' => 'Your signature',
				'facebook' => 'Your facebook account',
				'twitter' => 'Your twitter account',
				'github' => 'Your github account',
				'role' => 'author',
				)
			);

		file_put_contents($author_url , $dumper->dump($administrator, 2));
		return $app->redirect('/admin/setting/edit');
	}
	return $app['twig']->render('install.twig', $app['data']);
});

/** Login **/
$app->get('/login', function(Request $request) use($app) {
	if(!file_exists($app['dir_blog'])) 
		return $app->redirect('/install');

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

/** POST MANAGER **/

$app->get('/admin/posts', function(Request $request) use($app) {
	$app['posts'] = TolkienFacade::build($app['dir_blog'], 'post');
	return $app['twig']->render('posts.twig', $app['data']);
});

$app->get('/admin/posts/new', function(Request $request) use($app) {
	$app['form_title'] = 'New Post';
	$app['categories'] = TolkienFacade::build($app['dir_blog'], 'site_category');
	$app['post_categories'] = array();
	$app['form'] = array(
		'title' => '',
		'other_categories' => '',
		'body' => '',
		'file' => ''
		);
	return $app['twig']->render('post_form.twig', $app['data']);
});

$app->post('/admin/posts', function(Request $request) use($app) {
	$app['form_title'] = $request->request->get('form_title');
	$app['categories'] = TolkienFacade::build($app['dir_blog'], 'site_category');
	$app['post_categories'] = array();

	$app['form'] = array(
		'title' => $request->request->get('title'),
		'categories' => $request->request->get('categories'),
		'other_categories' => $request->request->get('other_categories'),
		'body' => $request->request->get('body'),
		'file' => $request->request->get('file')
		);

	$constraint = new Assert\Collection(array(
		'title' => new Assert\NotBlank(),
		'body' => new Assert\NotBlank(),
		'categories' => new Assert\NotBlank(),
		'other_categories' => array(),
		'file' => array()
		));

	$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
	if(count($app['errors']) > 0) {
		return $app['twig']->render('post_form.twig', $app['data']);	
	}

	$author = $app['session']->get('author');
	$categories = array_filter( explode(',', implode(',', $app['form']['categories']) . ',' . $app['form']['other_categories']) );

	// indicates that form is from edit form
	if($app['form']['file'] != '') {
		unlink($app['config']['dir']['post'] . '/' . $app['form']['file']);
	}

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
	$app['form_title'] = 'Update Post';	

	foreach ($posts as $post) {
		if($post->getFileName() == $id) {
			$markdown = new \HTML_To_Markdown($post->getBody());
			$post_categories = array();

			$app['form'] = array(
				'title' => $post->getTitle(),
				'body' => $markdown->output(),
				'other_categories' => '',
				'file' => $post->getFile()
				);
			foreach ($post->getCategories() as $category) {
				$post_categories[] = $category->getName();
			}
			$app['post_categories'] = $post_categories;
			break;
		}		
	}
	return $app['twig']->render('post_form.twig', $app['data']);
});

$app->get('/admin/post/{file}/delete', function(Request $request, $file) use($app) {
	if(unlink($app['config']['dir']['post'] . '/' . $file)) 
		$app['notification'] = "A post has been succesfully deleted";
	else 
		$app['notification'] = "Error when deleted a post";

	return $app->redirect('/admin/posts');
});

/** END OF POST MANAGER **/


/** PAGE MANAGER **/

$app->get('/admin/pages', function(Request $request) use($app) {
	$app['pages'] = TolkienFacade::build($app['dir_blog'], 'page');
	return $app['twig']->render('pages.twig', $app['data']);
});

$app->get('/admin/pages/new', function(Request $request) use($app) {
	$app['form_title'] = 'New Page';
	$app['form'] = array(
		'title' => '',
		'body' => '',
		'file' => ''
		);
	return $app['twig']->render('page_form.twig', $app['data']);
});

$app->post('/admin/pages', function(Request $request) use($app) {
	$app['form'] = array(
		'title' => $request->request->get('title'),
		'body' => $request->request->get('body'),
		'file' => $request->request->get('file')
		);

	$constraint = new Assert\Collection(array(
		'title' => new Assert\NotBlank(),
		'body' => new Assert\NotBlank(),
		'file' => array()
		));

	$app['errors'] = $app['validator']->validateValue($app['form'], $constraint);
	if(count($app['errors']) > 0) {
		return $app['twig']->render('page_form.twig', $app['data']);	
	}

	if($app['form']['file'] != '') {
		unlink($app['config']['dir']['page'] . '/' . $app['form']['file']);
	}

	TolkienFacade::generate($app['dir_blog'], array(
		'type' => 'page',
		'layout' => 'page',
		'title' => $app['form']['title'],
		'body' => $app['form']['body']
		));

	return $app->redirect('/admin/pages');
});

$app->get('/admin/page/{file}/edit', function(Request $request, $file) use($app) {
	$app['form_title'] = 'Update Page';
	$pages = TolkienFacade::build($app['dir_blog'], 'page');
	foreach ($pages as $page) {
		if($page->getFileName() == $file) {
			$markdown = new \HTML_To_Markdown($page->getBody());
			$app['form'] = array(
				'title' => $page->getTitle(),
				'body' => $markdown->output(),
				'file' => $page->getFile()
				);
			break;
		}
	}
	return $app['twig']->render('page_form.twig', $app['data']);
});

$app->get('/admin/page/{file}/delete', function(Request $request, $file) use($app) {
	if(unlink($app['config']['dir']['page'] . '/' . $file)) 
		$app['notification'] = "A page has been succesfully deleted";
	else 
		$app['notification'] = "Error when deleted a page";

	return $app->redirect('/admin/pages');
});

/** END OF PAGE MANAGER **/


/** AUTHOR MANAGER **/

$app->get('/admin/authors', function(Request $request) use($app, $authors) {
	$app['authors'] = $authors;
	return $app['twig']->render('authors.twig', $app['data']);
});

$app->get('/admin/authors/new', function(Request $request) use($app) {
	$app['form_title'] = "New Author";
	$app['form'] = array(
		'name' => '',
		'email' => '',
		'role' => 'author',
		'signature' => '',
		'facebook' => '',
		'twitter' => '',
		'github' => '',
		'username' => ''
		);
	return $app['twig']->render('author_form.twig', $app['data']);
});

$app->post('/admin/authors', function(Request $request) use($app, $authors, $dumper, $author_url) {
	$app['form_title'] = $request->request->get('form_title');
	$app['form'] = array(
		'name' => $request->request->get('name'),
		'email' => $request->request->get('email'),
		'role' => $request->request->get('role'),
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
		'role' => new Assert\NotBlank(),
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
		unset($app['errors']);
		$app['error_confirm'] = "Password and Confirmed Password doesn't match";
		return $app['twig']->render('author_form.twig', $app['data']);		
	}

	// process here
	$authors[$app['form']['username']] = array(
		'name' => $app['form']['name'],
		'email' => $app['form']['email'],
		'role' => $app['form']['role'],
		'signature' => $app['form']['signature'],
		'facebook' => $app['form']['facebook'],
		'twitter' => $app['form']['twitter'],
		'github' => $app['form']['github'],
		'username' => $app['form']['username']
		);

	if($app['form_title'] == 'New Author')
		$authors[$app['form']['username']]['password'] = sha1($app['form']['password']);
	else
		$authors[$app['form']['username']]['password'] = $app['form']['password'];

	file_put_contents($author_url, $dumper->dump($authors, 2));
	return $app->redirect('/admin/authors');
});

$app->get('/admin/author/{username}/edit', function(Request $request, $username) use($app, $authors) {
	$app['form_title'] = "Update Author";
	$author = $authors[$username];
	$app['form'] = array(
		'name' => $author['name'],
		'email' => $author['email'],
		'role' => $author['role'],
		'signature' => $author['signature'],
		'facebook' => $author['facebook'],
		'twitter' => $author['twitter'],
		'github' => $author['github'],
		'username' => $username,
		'password' => $author['password']
		);
	return $app['twig']->render('author_form.twig', $app['data']);
});

$app->get('/admin/author/{username}/delete', function(Request $request, $username) use($app, $authors, $author_url, $dumper) {
	unset($authors[$username]);
	file_put_contents($author_url, $dumper->dump($authors, 2));
	return $app->redirect('/admin/authors');
});

/** END OF AUTHOR MANAGER **/


/** UPDATE SETTING **/

$app->match('/admin/setting/edit', function(Request $request) use($app, $dumper, $config_url) {
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
		else {
			unset($app['errors']);
			$app['success'] = 'Setting has been succesfully updated';
		}
		
		$config = array(
			'config' => array(
				'title' => $app['form']['title'],
				'tagline' => $app['form']['tagline'],
				'pagination' => $app['form']['pagination']
				),
			'dir' => $app['config']['dir']
			);
		file_put_contents($config_url, $dumper->dump($config, 2));
	}
	return $app['twig']->render('setting.twig', $app['data']);
});

/** END OF SETTING **/

/** ASSET Manager **/
$app->get('/admin/assets', function(Request $request) use($app) {
	$assets = TolkienFacade::build($app['dir_blog'], 'asset');
	$arrAssets = array();
	foreach ($assets as $asset) {
		$arrAssets[] = array(
			'path' => $asset->getPath(),
			'ext' => array_pop(explode('.', basename($asset->getPath())))
			);
	}

	$app['ext_editable'] = array('css', 'js');
	$app['assets'] = $arrAssets;
	return $app['twig']->render('assets.twig', $app['data']);
});

$app->get('/admin/assets/new', function(Request $request) use($app) {
	$app['form_title'] = "Upload Asset";
	$app['form'] = array(
		'asset' => ''
		);
	return $app['twig']->render('asset_form.twig', $app['data']);
});

$app->post('/admin/assets', function(Request $request) use($app) {
	$app['form_title'] = 'Upload Asset';
	$app['form'] = array(
		'asset' => $request->request->get('asset')
		);
	
	$files = $request->files->get('asset');
	$path = $app['dir_blog'] . '/_assets/';

	if(isset($files))
	{
		$filename = $files->getClientOriginalName();
		switch ( array_pop(explode('.', $filename)) ) {
			case 'js':
				$path .= 'js/';
				break;
			case 'css':
				$path .= 'css/';
				break;
			case 'jpg':
				$path .= 'images/';
				break;
			case 'jpeg':
				$path .= 'images/';
				break;
			case 'png':
				$path .= 'images/';
				break;
			case 'gif':
				$path .= 'images/';
				break;			
			default:
				$path .= 'other/';
				break;
		}

		if (!is_dir($path))
			mkdir($path);

		$files->move($path, $filename);
		return $app->redirect('/admin/assets');
	}
	else {
		$app['errors'] = array(
			'error' => array(
				'propertyPath' => 'Asset',
				'message' => 'Upload Failed',
				)
			);
		return $app['twig']->render('asset_form.twig', $app['data']);
	}
});

$app->get('/admin/asset/{path}/edit', function(Request $request, $path) use($app) {
	$file = str_replace('&', '/', $path);
	$app['form'] = array(
		'content' => file_get_contents($file),
		'asset' => $file
		);
	$app['form_title'] = "Edit Asset";
	$app['path'] = $path;
	$app['file'] = '/' . $file;
	$app['editable'] = false;
	if( in_array( array_pop(explode('.', basename($file))), array('css', 'js') ) )
		$app['editable'] = true;

	return $app['twig']->render('asset_edit_form.twig', $app['data']);
});

$app->post('/admin/asset/{path}', function(Request $request, $path) use($app) {
	$file = str_replace('&', '/', $path);
	file_put_contents($file, $request->request->get('content'));
	return $app->redirect('/admin/asset/' . $path . '/edit' );
});

$app->get('/admin/asset/{path}/delete', function(Request $request, $path) use($app) {
	$path = str_replace('&', '/', $path);
	unlink($path);
	return $app->redirect('/admin/assets');
});

$app->get('/admin/site', function(Request $request) use($app) {

});

$app->get('/admin/site/compile', function(Request $request) use($app) {

});

$app->run();