<?php


namespace gem\controllers\admin\ccs;


use gem\models\eloquent\ccs\eventsModel;
use gem\models\eloquent\ccs\eventsResourcesModel;
use gem\repositories\ccs\contentRepository;
use gem\repositories\ccs\eventsRepository;
use gem\services\validation\validations\ccs\content\landingContentValidation;
use gem\services\validation\validations\ccs\content\mainMenuContentValidation;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ccsContentController
{

	/**
	 * @var contentRepository
	 */
	private $contentRepository;
	private $eventsRepository;
	private $view;
	private $flash;



	/**
	 * ccsContentController constructor.
	 * @param                   $view
	 * @param                   $flash
	 * @param contentRepository $contentRepository
	 */
	public function __construct( $view, $flash, contentRepository $contentRepository, eventsRepository $eventsRepository)
	{
		$this->view = $view;
		$this->flash = $flash;
		$this->contentRepository = $contentRepository;
		$this->eventsRepository = $eventsRepository;
	}



	public function getIndex(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findByGuid($args['event']);
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			//'content' => $data['event']['content'],
		];

		$this->view->render( $response, '/ccs/content/content.view.php', $data );

		return $response;
	}



	// magic get function (ish)
	public function getContentFromPage(Request $request, Response $response, $args): Response
	{
		$pages = [
			"landing" => "getLanding",
			"main-menu" => "getMainMenu",
			"resources" => "getResources",
			"contact-form" => "getContactForm",
			"contact-form-thanks" => "getContactFormThanks",
			"basket" => "getBasket",
			"checkout" => "getCheckout",
			"checkout-additional" => "getAdditional",
			"checkout-thanks" => "getCheckoutThanks",
			"app-email" => "getAppEmail",
		];

		if(array_key_exists($args['page'], $pages)) {
			return $this->{$pages[$args['page']]}($request, $response, $args);
		}


		return $this->redirect("/admin/ccs/events/list", $response);
	}

	public function setContentFromPage(Request $request, Response $response, $args): Response
	{
		$pages = [
			"landing" => "setLanding",
			"main-menu" => "setMainMenu",
			"resources" => "setResources",
			"contact-form" => "setContactForm",
			"contact-form-thanks" => "setContactFormThanks",
			"basket" => "setBasket",
			"checkout" => "setCheckout",
			"checkout-additional" => "setAdditional",
			"checkout-thanks" => "setCheckoutThanks",
			"app-email" => "setAppEmail",
		];

		if(array_key_exists($args['page'], $pages)) {
			return $this->{$pages[$args['page']]}($request, $response, $args);
		}


		return $this->redirect("/admin/ccs/events/list", $response);
	}





	public function getMainMenu(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "main_menu");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

//		dd($event->toArray());
		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['main_menu'] ?? null
		];

		$this->view->render( $response, '/ccs/content/main-menu.view.php', $data );

		return $response;
	}



	public function setMainMenu(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();


		try {

			$validation = new mainMenuContentValidation();
			if( ! $validation->assert( $body ) ) {
				// return errors
				$this->flash->addMessage( "error", "We found some errors, see below." );
				$this->flash->addMessage( "form_errors", $validation->errors() );
				throw new \Exception( "validation error" );
			}


			$result = $this->contentRepository->saveMainMenu( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Main Menu content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/main-menu", $response);

	}



	public function getLanding(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "landing");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['landing'] ?? null
		];

		$this->view->render( $response, '/ccs/content/landing.view.php', $data );

		return $response;
	}



	public function setLanding(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			$validation = new landingContentValidation();
			if( ! $validation->assert( $body ) ) {
				// return errors
				$this->flash->addMessage( "error", "We found some errors, see below." );
				$this->flash->addMessage( "form_errors", $validation->errors() );
				throw new \Exception( "validation error" );
			}


			$result = $this->contentRepository->saveLanding( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Landing content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/landing", $response);

	}



	public function getResources(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "resources");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['resources'],
		];

		$this->view->render( $response, '/ccs/content/resources.view.php', $data );

		return $response;
	}



	public function setResources(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			// same as main menu val
			$validation = new mainMenuContentValidation();
			if( ! $validation->assert( $body ) ) {
				// return errors
				$this->flash->addMessage( "error", "We found some errors, see below." );
				$this->flash->addMessage( "form_errors", $validation->errors() );
				throw new \Exception( "validation error" );
			}


			$result = $this->contentRepository->saveResources( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Resources content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/resources", $response);

	}



	public function getContactForm(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "contact_form");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['contact_form'] ?? null
		];

		$this->view->render( $response, '/ccs/content/contact-form.view.php', $data );

		return $response;
	}



	public function setContactForm(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			$result = $this->contentRepository->saveContactForm( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Contact Form content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/contact-form", $response);

	}



	public function getContactFormThanks(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "contact_form_thanks");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['contact_form_thanks'] ?? null,
		];

		$this->view->render( $response, '/ccs/content/contact-form-thanks.view.php', $data );

		return $response;
	}



	public function setContactFormThanks(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			$result = $this->contentRepository->saveContactFormThanks( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Contact Form Thank You content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/contact-form-thanks", $response);

	}



	public function getCheckout(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "checkout");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['checkout'] ?? null,
		];

		$this->view->render( $response, '/ccs/content/checkout.view.php', $data );

		return $response;
	}



	public function setCheckout(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {
			$result = $this->contentRepository->saveCheckout( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Checkout (page 1) content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/checkout", $response);

	}



	public function getAdditional(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "checkout_additional");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['checkout_additional'],
		];

		$this->view->render( $response, '/ccs/content/checkout-additional.view.php', $data );

		return $response;
	}



	public function setAdditional(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

/*			// same as main menu val
			$validation = new mainMenuContentValidation();
			if( ! $validation->assert( $body ) ) {
				// return errors
				$this->flash->addMessage( "error", "We found some errors, see below." );
				$this->flash->addMessage( "form_errors", $validation->errors() );
				throw new \Exception( "validation error" );
			}*/


			$result = $this->contentRepository->saveCheckoutAdditional( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Checkout (page 2) content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/checkout-additional", $response);

	}



	public function getCheckoutThanks(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "checkout_thanks");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['checkout_thanks'] ?? null,
		];

		$this->view->render( $response, '/ccs/content/checkout-thanks.view.php', $data );

		return $response;
	}



	public function setCheckoutThanks(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			$result = $this->contentRepository->saveCheckoutThanks( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Checkout Thank You content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/checkout-thanks", $response);

	}



	public function getAppEmail(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "app_email");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['app_email'] ?? null,
		];

		$this->view->render( $response, '/ccs/content/email.view.php', $data );

		return $response;
	}



	public function setAppEmail(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			$result = $this->contentRepository->saveAppEmail( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The App Email content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/app-email", $response);

	}



	public function getBasket(Request $request, Response $response, $args): Response
	{
		$event = $this->eventsRepository->findEventWithContent($args['event'], "basket");
		if(!$event)
			return $this->redirect("admin/ccs/events/list", $response);

		$data = [
			'menu'   => $request->getAttribute( 'menu' ), //stupid
			'event' => $event->toArray(),
			'content' => $event->contentArray['basket'] ?? null,
		];

		$this->view->render( $response, '/ccs/content/basket.view.php', $data );

		return $response;
	}



	public function setBasket(Request $request, Response $response, $args): Response
	{

		$body = $request->getParsedBody();

		try {

			$result = $this->contentRepository->saveBasket( $args[ 'event' ], $body );
			if( ! $result ) {
				$this->flash->addMessage( "error", "There was a problem saving, try again" );
				throw new \Exception( "Saving issue" );
			}

			// success
			$this->flash->addMessage( "success", "The Basket content has been updated" );

			return $this->redirect( "/admin/ccs/content/{$args['event']}", $response);

		} catch( \Exception $e ) {
		}

		return $this->redirect( "/admin/ccs/content/{$args['event']}/basket", $response);

	}



	/**
	 * @param string   $route
	 * @param Response $response
	 * @return Response
	 */
	public function redirect( string $route, Response $response ): Response
	{

		return $response
			->withHeader( 'Location', $route )
			->withStatus( 302 );
	}


}



