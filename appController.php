<?php


namespace gem\controllers\app;


use gem\classes\ccs\app;
use gem\classes\contact\contact;
use gem\classes\orca\orca;
use gem\exceptions\fileOutputter\fileOutputterException;
use gem\helpers\cookies\ccsEventCookiesHelper;
use gem\helpers\fileOutputter\fileOutputter;
use gem\helpers\guid\guid;
use gem\repositories\ccs\contactFormRepository;
use gem\repositories\ccs\eventsRepository;
use gem\repositories\ccs\eventsResourcesRepository;
use gem\services\validation\validations\ccs\app\checkoutAdditionalValidation;
use gem\services\validation\validations\ccs\app\checkoutValidation;
use gem\services\validation\validations\ccs\app\contactFormValidation;
use gem\traits\helpers\redirect;
use MailchimpMarketing\Api\ReportingApi;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class appController
{

	use redirect;

	private $view;
	private $eventsRepository;
	private $eventsResourcesRepository;
	private $flash;
	private $app;
	private $contactFormRepository;
	private $logger;
	private $orca;



	/**
	 * appController constructor.
	 * @param                           $flash
	 * @param                           $view
	 * @param eventsRepository          $eventsRepository
	 * @param eventsResourcesRepository $eventsResourcesRepository
	 * @param app                       $app
	 * @param contactFormRepository     $contactFormRepository
	 * @param Logger                    $logger
	 */
	public function __construct( $flash, $view, eventsRepository $eventsRepository, eventsResourcesRepository $eventsResourcesRepository, app $app, contactFormRepository $contactFormRepository, Logger $logger, orca $orca)
	{

		$this->flash                     = $flash;
		$this->view                      = $view;
		$this->eventsRepository          = $eventsRepository;
		$this->eventsResourcesRepository = $eventsResourcesRepository;
		$this->app                       = $app;
		$this->contactFormRepository     = $contactFormRepository;
		$this->logger                    = $logger->withName('ccs_app');
		$this->orca                      = $orca;
	}



	public function index( Request $request, Response $response, $args ): Response
	{

		if( ccsEventCookiesHelper::getEvent() ) {
			// we are configured
			$data = [
				'event' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->toArray(),
			];

			$this->view->render( $response, '/index.view.php', $data );
		} else {
			$data = [
				'events' => $this->eventsRepository->all()->toArray(),
			];

			$this->view->render( $response, '/event.view.php', $data );
		}

		return $response;
	}



	public function eventSelect( Request $request, Response $response, $args ): Response
	{

		try {
			guid::checkThrow( $args[ 'guid' ] );

			if( ! $event = $this->eventsRepository->findByGuid( $args[ 'guid' ] ) ) {
				throw new \Exception( "Event not found" );
			}

			// cookie timeout
			ccsEventCookiesHelper::setEventCookie( $event );

			return $this->redirect( "/", $response );


		} catch( \Exception $e ) {
			$this->logger->error($e);
		}

		return $this->redirect( '/event', $response );

	}



	public function mainMenu( Request $request, Response $response, $args ): Response
	{
		$data = [
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['main_menu'] ?? null

		];
		$this->view->render( $response, '/main-menu.view.php', $data );

		return $response;
	}



	public function getResources( Request $request, Response $response, $args ): Response
	{

		$data = [
			'resources' => $this->app->getResources($args['type']),
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['resources'] ?? null

		];

		$this->view->render( $response, '/resources.view.php', $data );

		return $response;
	}



	public function getSearch( Request $request, Response $response, $args ): Response
	{

		$this->view->render( $response, '/search.view.php', [] );

		return $response;
	}



	public function setSearch( Request $request, Response $response, $args ): Response
	{

		$body = $request->getParsedBody();
		// safety first
		foreach( $body as &$b ) {
			$b = htmlspecialchars( trim($b) );
		}

		try {
			if( ! isset( $body[ 'term' ] ) ) {
				throw new \Exception( 'No search query' );
			}

			// logout method
			if( $body['term'] === "logmeout" ) {
				return $this->logout($response);
			} elseif( preg_match('/(?<=^external-link:).+$/m', $body['term'], $match) ) {
				// custom search for links
				$this->flash->addMessage( 'external', $this->app->searchLinks( $match[0] ) );
			} else {
				// expected
				$this->flash->addMessage( 'search', $this->app->searchResources( $body[ 'term' ] ) );
			}

			$this->flash->addMessage( 'old_term', $body[ 'term' ] );

			return $this->redirect( '/search', $response );

		} catch( \Exception $e ) {
			$this->logger->error($e);
		}

		return $this->redirect( '/menu', $response );
	}



	public function logout(Response $response)
	{
		ccsEventCookiesHelper::unsetEventCookie();

		return $this->redirect('/', $response);
	}



	/********
	 * Basket
	 */


	public function getBasketView( Request $request, Response $response, $args ): Response
	{

		$data = [
			'basket' => $this->app->getCurrentBasket() ?? [],
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['basket'] ?? null
		];

		$this->view->render( $response, '/basket.view.php', $data );

		return $response;
	}



	public function updateBasket( Request $request, Response $response, $args ): Response
	{

		$body = $request->getParsedBody();

		// validation
		if( ! $validated = $this->app->validateBasketRequest( $body ) ) {
			$_SESSION[ 'ccs_basket' ] = [];

			return $this->returnErrorJson( "Invalid request", $response );
		}

		$results = $this->app->getApiBasket( $validated );

		return $this->returnJson( $results, $response );
	}



	public function returnJson( $data, Response $response )
	{

		$payload = json_encode([
			'csrf_token' => $this->orca->csrfToken(),
			'data' => $data
		]);

		$response->getBody()->write( $payload );

		return $response
			->withHeader( 'Content-Type', 'application/json' )
			->withStatus( 201 );
	}



	public function returnErrorJson( $msg, Response $response )
	{

		$payload = json_encode( [
			'error'     => true,
			'error_msg' => $msg,
			'csrf_token' => $this->orca->csrfToken()
		] );

		$response->getBody()->write( $payload );

		return $response
			->withHeader( 'Content-Type', 'application/json' )
			->withStatus( 201 );
	}



	/*******&
	 * Checkout
	 */

	public function getCheckout( Request $request, Response $response, $args ): Response
	{
		$data = [
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['checkout'] ?? null
		];

		$this->view->render( $response, '/checkout.view.php', $data );

		return $response;
	}



	public function setCheckout( Request $request, Response $response, $args ): Response
	{

		$body = $request->getParsedBody();
		// safety first
		foreach( $body as &$b ) {
			$b = htmlspecialchars( $b );
		}


		try {
			// validation
			$validate = new contactFormValidation();
			if( ! $validate->assert( $body ) ) {
				$this->flash->addMessage( "error", "There are some errors in the form below." );
				$this->flash->addMessage( "form_errors", $validate->errors() );
				throw new \Exception( "form errors" );
			}

			/*// filter out any junk
			$_SESSION[ 'ccs_checkout' ] = array_filter( $body, function ( $value, $key ) {

				return in_array( $key, [
					"first",
					"last",
					"email",
					"tel",
					"consent"
				] );
			}, ARRAY_FILTER_USE_BOTH );

			return $this->redirect( "/additional", $response );*/

			$result = $this->app->processContactForm($body);

			if(!$result)
				throw new \Exception("Contact form unprocessed");


		} catch( \Exception $e ) {
			$this->logger->error($e);

			$this->flash->addMessage( "old", $body );

			return $this->redirect( "/checkout", $response );
		}

		/**
		 * Begin to zip and send
		 */
		try {
//dd($result);
			$this->app->sendResources( $result );


		} catch( \Exception $e ) {
			$this->logger->error($e);
			$this->flash->addMessage( "error", "Sorry, we have encountered an error sending your email." );
		}

		return $this->redirect( "/complete", $response );



	}



	/**
	 * Checkout without additional fields
	 * @param Request  $request
	 * @param Response $response
	 * @param          $args
	 * @return Response
	 */
	public function getCheckoutFinish( Request $request, Response $response, $args ): Response
	{

		try {

			if( ! isset( $_SESSION[ 'ccs_checkout' ] ) || ! isset( $_SESSION[ 'ccs_basket' ] ) ) {
				$this->flash->addMessage( "error", "Your session has timed out." );

				return $this->redirect( "/", $response );
			}

			$result = $this->app->processContactForm(
				$_SESSION[ 'ccs_checkout' ]
			);


		} catch( \Exception $e ) {
//			edd( $e );
			$this->flash->addMessage( "error", "Sorry but we encountered an error processing your details. Please try again." );

			return $this->redirect( "/checkout", $response );
		}

		/**
		 * Begin to zip and send
		 */
		try {

			$this->app->sendResources( $result );

			return $this->redirect( "/complete", $response );

		} catch( \Exception $e ) {
			$this->logger->error($e);
			$this->flash->addMessage( "error", "Sorry, we have encountered an error sending your email." );
		}

		// errored somewhere
		return $this->redirect( "/main-menu", $response );
	}



	public function getAdditional( Request $request, Response $response, $args ): Response
	{
		$data = [
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['checkout_additional'] ?? null,
			'previous' => $_SESSION['ccs_checkout'] ?? null
		];
		$this->view->render( $response, '/checkout-additional.view.php', $data );

		return $response;
	}



	public function setAdditional( Request $request, Response $response, $args ): Response
	{

		$body = $request->getParsedBody();
		// safety first
		foreach( $body as &$b ) {
			$b = htmlspecialchars( $b );
		}

		try {

			if( ! isset( $_SESSION[ 'ccs_checkout' ] ) || ! isset( $_SESSION[ 'ccs_basket' ] ) ) {
				$this->flash->addMessage( "error", "Your session has timed out." );

				return $this->redirect( "/", $response );
			}

			$validate = new checkoutAdditionalValidation();
			if( ! $validate->assert( $body ) ) {
				$this->flash->addMessage( "error", "There are some errors in the form below." );
				$this->flash->addMessage( "form_errors", $validate->errors() );
				throw new \Exception( "form errors" );
			}

			//dd(array_merge($_SESSION['ccs_checkout'], $body));

			$result = $this->app->processContactForm(
				array_merge( $_SESSION[ 'ccs_checkout' ], $body )
			);


		} catch( \Exception $e ) {
			$this->logger->error($e);
			$this->flash->addMessage( "old", $body );

			return $this->redirect( "/additional", $response );
		}

		/**
		 * Begin to zip and send
		 */
		try {

			$this->app->sendResources( $result );


		} catch( \Exception $e ) {
			$this->logger->error($e);
			$this->flash->addMessage( "error", "Sorry, we have encountered an error sending your email." );
		}

		return $this->redirect( "/complete", $response );
	}



	public function getComplete( Request $request, Response $response, $args ): Response
	{

		/*		if(!isset($_SESSION['ccs_basket']) || !isset($_SESSION['ccs_checkout'])) {
					// don't belong here
					return $this->redirect('/', $response);
				}*/

		unset( $_SESSION[ 'ccs_basket' ], $_SESSION[ 'ccs_checkout' ] );


		$data = [
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['checkout_thanks'] ?? null
		];

		$this->view->render( $response, '/complete.view.php', $data );

		return $response;
	}



	/*********
	 * Contact Form
	 */

	public function getContactForm( Request $request, Response $response, $args ): Response
	{
		$data = [
			'event' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->toArray()
		];
		$this->view->render( $response, '/contact-form.view.php', $data );
//dd($_SESSION);
		return $response;
	}



	public function setContactForm( Request $request, Response $response, $args ): Response
	{

		$body = $request->getParsedBody();
		// safety first
		foreach( $body as &$b ) {
			$b = htmlspecialchars( $b );
		}

		try {

			$validate = new contactFormValidation();
			if( ! $validate->assert( $body ) ) {
				$this->flash->addMessage( "error", "There are some errors in the form below." );
				$this->flash->addMessage( "form_errors", $validate->errors() );
				throw new \Exception( "form errors" );
			}


			if( ! $this->app->saveContactForm( $body ) ) {
				$this->flash->addMessage( "error", "There was a problem saving the form, please try again." );
			}

			return $this->redirect( '/contact-form/complete', $response );

		} catch( \Exception $e ) {
			$this->logger->error($e);

			$this->flash->addMessage( "old", $body );

			return $this->redirect( "/contact-form", $response );
		}
	}



	public function getContactFormComplete( Request $request, Response $response, $args ): Response
	{
		$data = [
			'content' => $this->eventsRepository->findByGuid( ccsEventCookiesHelper::getEvent() )->content_array['contact_form_thanks'] ?? null
		];
		$this->view->render( $response, '/contact-form.complete.view.php', $data );

		return $response;
	}



	/**********
	 * Downloader
	 */

	public function getDownload( Request $request, Response $response, $args ): Response
	{

		if( ! isset( $args[ 'guid' ] ) || ! guid::check( $args[ 'guid' ] ) ) {
			$response->getBody()->write( "Invalid request" );
		} elseif( ! $zip = $this->contactFormRepository->getZipByGuid( $args[ 'guid' ] ) ) {
			$response->getBody()->write( "Download not found" );
		} else {
			try {

				// track download
				$this->app->trackZipDownload( $zip->id );

				$fileOutputter = new fileOutputter();

				return $fileOutputter
					->setSource( "../files/ccs/zips/{$zip->zip_name}" )
					->setContentType( "application/zip" )
					->setFilename( $zip->zip_name )
					->setResponse( $response )
					->output();
			} catch( fileOutputterException $f ) {
				$this->logger->warning($f);
				$response->getBody()->write( "Zip output failed" );

			}
		}

		return $response;
	}



	public function getEventLogo( Request $request, Response $response, $args ): Response
	{

		$logo = $this->app->getEventLogo( $args[ 'filename' ] );

		$response = $response->withHeader( "Content-Type", "image/png" );
		$response->getBody()->write( $logo );

		return $response;

	}




	/********
	 * Kiosk
	 */

	public function getKiosk(Request $request, Response $response, $args): Response
	{
		$this->view->render($response, '/kiosk.view.php');
		return $response;
	}
	public function getCarbonZero(Request $request, Response $response, $args): Response
	{
		$this->view->render($response, '/kiosk/carbon-zero.pdf.view.php');
		return $response;
	}


}