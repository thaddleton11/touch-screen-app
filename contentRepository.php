<?php


namespace gem\repositories\ccs;


use gem\models\eloquent\ccs\contentModel;
use gem\models\eloquent\ccs\eventsModel;
use gem\helpers\guid\guid;

class contentRepository
{

	/**
	 * @var contentModel
	 */
	private $model;
	private $session;
	private $eventsModel;
	private $event;
	private $allowed;



	/**
	 * contentRepository constructor.
	 * @param              $session
	 * @param contentModel $contentModel
	 * @param eventsModel  $eventsModel
	 */
	public function __construct($session, contentModel $contentModel, eventsModel $eventsModel)
	{
		$this->session = $session;
		$this->model = $contentModel;
		$this->eventsModel = $eventsModel;
	}


	public function findContentByEventGuid($guid)
	{
		return $this->model
			->with(['event' => function ($query) use ($guid) {
				$query->where('guid', $guid);
			}])
			->get();
	}



	public function saveMainMenu($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"short_intro",
			"heading"
		];

		return $this->saveContent($params, "main_menu");

	}



	public function saveLanding($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"short_intro",
		];

		return $this->saveContent($params, "landing");

	}



	public function saveResources($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"short_intro",
			"heading"
		];

		return $this->saveContent($params, "resources");

	}



	public function saveContactForm($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"short_description",
			"heading"
		];

		return $this->saveContent($params, "contact_form");

	}



	public function saveContactFormThanks($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"description",
			"heading"
		];

		return $this->saveContent($params, "contact_form_thanks");

	}



	public function saveCheckout($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"short_description",
			"heading"
		];

		return $this->saveContent($params, "checkout");

	}



	public function saveCheckoutAdditional($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"short_description",
			"heading",
			"addition_intro"
		];

		return $this->saveContent($params, "checkout_additional");

	}



	public function saveCheckoutThanks($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"description",
			"heading",
			"sub_heading",
		];

		return $this->saveContent($params, "checkout_thanks");

	}



	public function saveBasket($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"description",
			"heading"
		];

		return $this->saveContent($params, "basket");

	}



	public function saveAppEmail($eventGuid, $params)
	{
		$this->event = $this->eventsModel
			->where('guid', $eventGuid)
			->first();

		if(!$this->event)
			return false;

		// input keys allowed
		$this->allowed = [
			"body",
		];

		return $this->saveContent($params, "app_email");

	}



	/**
	 * Master method
	 * @param array  $params
	 * @param string $page
	 * @return bool
	 */
	public function saveContent(array $params, string $page)
	{
		foreach($params as $pKey=>$param) {
			if(!in_array($pKey, $this->allowed))
				continue;

			$this->event->content()->updateOrCreate([
				'content_page' => $page,
				'content_key' => $pKey
			],
				[
					'content_value' => $param,
					'guid' => guid::create(),
					'user_id' => $this->session->getSession()->id,
					'record_status' => 1
				]);

		}

		return true;
	}
}