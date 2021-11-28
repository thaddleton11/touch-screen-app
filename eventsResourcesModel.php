<?php


namespace gem\models\eloquent\ccs;


use gem\traits\models\status\recordStatus as recordStatus;

class eventsResourcesModel extends \Illuminate\Database\Eloquent\Model
{
	use recordStatus;

	const CREATED_AT = 'created';
	const UPDATED_AT = 'last_edited';

	protected $table = "ccs_events_resources";

	protected $fillable = [
		"guid",
		"resources_id",
		"events_id",
		"user_id",
		"sort_order",
		"record_status",
		"content_type",
	];


	public function event()
	{
		return $this->belongsTo('\gem\models\eloquent\ccs\eventsModel', 'events_id');
	}

	public function resourceable()
	{
		return $this->morphTo();
	}

	public function contact_resources()
	{
		return $this->hasMany(contactResourcesModel::class, 'events_resources_id');
	}




}