<?php


namespace gem\classes\ccs;


use gem\exceptions\emailService\emailException;
use gem\exceptions\fileZipper\fileZipperException;
use gem\helpers\guid\guid;
use gem\interfaces\services\email\emailInterface;
use gem\models\eloquent\ccs\contactFormModel;
use gem\repositories\ccs\contactFormRepository;
use gem\repositories\ccs\contactResourcesRepository;
use gem\repositories\ccs\eventsRepository;
use gem\repositories\ccs\eventsResourcesRepository;
use gem\repositories\ccs\resourcesRepository;
use gem\services\fileZipper\ccs\resourcesZipper;
use gem\services\fileZipper\fileZipper;
use Twig\Environment;
use Twig\Error\Error;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class app
{

	/**
	 * @var eventsRepository
	 */
	private $eventsRepository;
	/**
	 * @var eventsResourcesRepository
	 */
	private $eventsResourcesRepository;

	private $event;
	/**
	 * @var contactFormRepository
	 */
	private $contactFormRepository;
	/**
	 * @var contactResourcesRepository
	 */
	private $contactResourcesRepository;
	/**
	 * @var emailInterface
	 */
	private $emailer;
	/**
	 * @var Environment
	 */
	private $twig;
	/**
	 * @var resourcesRepository
	 */
	private $resourcesRepository;
	private $logger;



	/**
	 * app constructor.
	 */
	public function __construct( eventsRepository $eventsRepository, eventsResourcesRepository $eventsResourcesRepository, contactFormRepository $contactFormRepository, contactResourcesRepository $contactResourcesRepository, emailInterface $emailer, Environment $twig, resourcesRepository $resourcesRepository, $logger)
	{

		$this->eventsRepository          = $eventsRepository;
		$this->eventsResourcesRepository = $eventsResourcesRepository;
		$this->event                     = $_SESSION[ 'ccs_event' ] ?? null;
		$this->contactFormRepository = $contactFormRepository;
		$this->contactResourcesRepository = $contactResourcesRepository;
		$this->emailer = $emailer;
		$this->twig = $twig;
		$this->resourcesRepository       = $resourcesRepository;
		$this->logger                    = $logger->withName("ccs_app_class");
	}



	public function getResources(string $contentType)
	{

		return $this->eventsResourcesRepository->allByEventId( $this->event[ 'id' ], $contentType )->toArray();
	}



	public function validateBasketRequest( $post )
	{

		if( ! isset( $post[ '_current' ] ) ) {
			return false;
		}

		foreach( $post[ '_current' ] as &$item ) {
			if( ! guid::check( $item ) ) {
				return false;
			}

			$item = htmlspecialchars( $item );
		}

		return $post[ '_current' ];
	}



	public function getApiBasket( array $arr )
	{

		$resources = $this->eventsResourcesRepository->findByGuids( $arr, $this->event[ 'id' ] );
//		$resources = $this->resourcesRepository->findByGuidsForEvent($arr, $this->event[ 'id' ]);

		$filtered = array_map( function ( $item ) {

			$r = $item[ 'resourceable' ];
			unset(
				$r[ 'id' ],
				$r[ 'is_default' ],
				$r[ 'created' ],
				$r[ 'last_edited' ],
				$r[ 'user_id' ],
				$r[ 'record_status' ],
			);

			// other guid needed is for reference
			$r[ 'guid' ] = $item[ 'guid' ];

			return $r;
		}, $resources->toArray() );

		$_SESSION[ 'ccs_basket' ] = $filtered;

		return $filtered;
	}



	public function getCurrentBasket()
	{

		return $_SESSION[ 'ccs_basket' ] ?? [];

	}



	public function processContactForm($data)
	{
		$result = $this->saveContactForm($data);

		// save resources
		$this->contactFormRepository->saveResources(
			$result->id,
			$this->event['id'],
			$this->getCurrentBasket()
		);


		return $result;
	}



	public function saveContactForm($data): object
	{
		if(!$result = $this->contactFormRepository->save($data, $this->event['id']))
			throw new \Exception("save error");

		return $result;
	}



	public function sendResources(contactFormModel $contact)
	{
		// get contact resources from model
		$contactResources = $contact->fresh(['contactResources', 'event'])->contactresources;

		$eventResources = array_map(function($item){
			return $item['events_resources_id'];
		}, $contactResources->toArray());

		$resources = $this->eventsResourcesRepository->findByIds($eventResources);

		// just want the resourceable, aka actual resources
		// separate files and links
		$filteredFiles = [];
		foreach($resources->toArray() as $r) {
			if(isset($r['resourceable']['file_extension']))
				$filteredFiles[] =  $r['resourceable'];

		}

		$filteredLinks = [];
		foreach($resources->toArray() as $r) {
			if(isset($r['resourceable']['link']))
				$filteredLinks[] =  $r['resourceable'];

		}

/*var_dump($filteredFiles);
		dd($filteredLinks);*/

		$zipArray = [];

		try {
			$zipper = new resourcesZipper();
			$zipped = $zipper
				->setFiles($filteredFiles)
				->setZipName(uniqid("your-ccs-resources_"))
				->create();

			// save zip resource
			$zipModel = $this->contactFormRepository->saveZip($contact->id, $zipped['name']);

			$zipArray = $zipModel->toArray();

		} catch( fileZipperException $f) {
			$this->logger->error($f);
		}

		// email
		$this->emailResources($contact->toArray(), $contact->event->toArray(), $filteredLinks,  $zipArray);

	}



	public function emailResources(array $contact, array $event, $links = [], $zipped = [])
	{
/*dd([
	'contact' => $contact,
	'event' => $event,
	'links' => $links,
	'zipped' => $zipped
]);*/
		// render view
		try{
			$view = $this->twig->load("app/emails/resources.php");
			$html = $this->twig->render($view, [
				'contact' => $contact,
				'event' => $event,
				'links' => $links,
				'zipped' => $zipped,
				'host' => $_SERVER['SERVER_NAME']
			]);
		} catch(Error $t){
			$this->logger->error($t);
			return false;
		}

		$this->emailer->setBody($html);

/*		// add zip
		if($zipped) {
			$this->emailer->setAttachment(
				"zip",
				$zipped['name'],
				$zipped['location']
			);
		}*/

		$result = $this->emailer
			->setIndividualTo(
			$contact['email'],
			"{$contact['first']} {$contact['last']}"
		)
			->setMailFrom("tomh+ccs_admin@glasgows.co.uk")
			->setMailFromName("CCS Resource App")
			->setSubject("Your resources have arrived")
			->setBody($html)
			->send();


		// save results
		if(isset($result[0])) {
			$this->contactFormRepository->saveEmail(
				$contact['id'],
				$result[0]->_id,
				$result[0]->email,
				$result[0]->status,
				$result[0]->reject_reason,
			);
		}
	}



	public function searchResources($term)
	{
		$term = filter_var($term);

		return $this->eventsResourcesRepository->searchText($term, $this->event['id']);

	}



	public function searchLinks($term)
	{
		$term = filter_var($term);

		return $this->eventsResourcesRepository->searchLinks($term, $this->event['id']);

	}



	public function getEventLogo($logo)
	{
		$event = $this->eventsRepository->find($this->event['id']);

		if($event->meta_array['logo'] == $logo) {
			$file = "../files/ccs/logos/{$logo}";
			if( file_exists( $file ) ) {
				return file_get_contents($file);
			}
		}

		return false;
	}



	public function trackZipDownload($id)
	{
		$this->contactFormRepository->trackZipDownload(
			$id, $this->event['id'], $_SERVER['REMOTE_ADDR']
		);
	}

}