{%extends "app/base.view.php"%}



{%block content%}

	<div class="container d-flex flex-column">
		<div class="row">
			<div class="col">
				<h1>{{content.heading??"Select your resources"}}</h1>
				<h5>{{content.short_intro??"From the choices below, select the resource materials you would like to take with you. When you have
					made your selection, simply provide us with your email address and we will email them to you."}}</h5>
			</div>
		</div>
		<div class="row">
			<div class="col d-flex justify-content-end">
				<a href="/basket" class="btn btn-secondary">Go to basket <i class="fa fa-arrow-right"></i></a>
			</div>
		</div>
		<div class="resource-block row d-flex mt-3 ccs-scroll">
            {%for resource in resources%}
				{%set r = resource.resourceable%}
				<div class="col-md-6 col-lg-4 d-flex">
					<div class="resource-item card shadow p-3 mb-4 rounded" data-id="{{resource.guid}}">
						<div class="card-body">
							{{_self.resource_badge(r.file_extension, r.link)}}
							<h5 class="card-title">{{r.title}}</h5>
							<p class="card-text">{{r.short_description}}</p>
							<div class="resource-footer">
								<button class="card-link btn btn-primary basket-btn" data-id="{{resource.guid}}">Add to basket <i class="fa fa-shopping-cart"></i></button>
								<button type="button" class="btn btn-danger remove-btn d-none" data-id="{{resource.guid}}">Remove <i class="fa fa-times"></i></button>
								<button class="card-link btn btn-secondary" data-toggle="modal" data-target="#resource-{{resource.guid}}">More</button>
							</div>

						</div>
					</div>
				</div>
			{%else%}
				<p>No results</p>
			{%endfor%}
		</div>

	</div>


{%endblock%}


{%block modals%}
	{%for resource in resources%}
	{%set r = resource.resourceable%}
		<div class="modal fade" id="resource-{{resource.guid}}" tabindex="-1" aria-labelledby="" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">{{r.title}}</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<p>Resource type: {{r.file_extension?r.file_extension|upper:"Link"}}</p>
						{{r.description|raw}}
						{%if r.tags%}<p><small>Tags: {{r.tags}}</small></p>{%endif%}
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary basket-btn" data-id="{{resource.guid}}">Add to basket <i class="fa fa-shopping-cart"></i></button>
						<button type="button" class="btn btn-danger remove-btn d-none" data-id="{{resource.guid}}">Remove <i class="fa fa-times"></i></button>
						<button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Close</button>

					</div>
				</div>
			</div>
		</div>

	{%endfor%}

{%endblock%}


{%block extraJS%}
{%endblock%}


{%macro resource_badge(type, link)%}
	{%set icon = "fa-file"%}

	{%if type == "pdf"%}
		{%set icon = "fa-file-pdf"%}
	{%elseif type in ["png,jpg,jpeg,gif"]%}
		{%set icon = "fa-image"%}
	{%elseif link %}
		{%set icon = "fa-link"%}
	{%endif%}

	<span class="badge badge-secondary"><i class="fa {{icon}} p-1"></i></span>
{%endmacro%}
