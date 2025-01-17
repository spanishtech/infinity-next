<nav class="boardlist">
	<div class="boardlist-row row-pages">
		<ul class="boardlist-categories">
			<li class="boardlist-category">
				<ul class="boardlist-items">
					<!-- Site Index -->
					<li class="boardlist-item"><a href="{!! url("/") !!}" class="boardlist-link">Home</a></li>
					
					<!-- Fundraiser Page -->
					<li class="boardlist-item"><a href="{!! url("cp") !!}" class="boardlist-link">Control Panel</a></li>
					
					@if (isset($user) && $user->canCreateBoard())
					<!-- Create a Board -->
					<li class="boardlist-item"><a href="{!! url("cp/boards/create") !!}" class="boardlist-link">Create Board</a></li>
					@endif
					
					@if (env('CONTRIB_ENABLED', false))
					<!-- Fundraiser Page -->
					<li class="boardlist-item"><a href="{!! url("contribute") !!}" class="boardlist-link">Contribute</a></li>
					
					<!-- Donation Page -->
					<li class="boardlist-item"><a href="{!! secure_url("cp/donate") !!}" class="boardlist-link">Fund us</a></li>
					@endif
					
					@if (isset($c) && $c->option('adventureEnabled'))
					<!-- Adventure! -->
					<li class="boardlist-item"><a href="{!! url("cp/adventure") !!}" class="boardlist-link">Adventure</a></li>
					@endif
				</ul>
			</li>
		</ul>
	</div>
	
	@if(isset($boardbar))
	<div class="boardlist-row row-boards" {{isset($board) ? "data-instant" : ""}}>
		<ul class="boardlist-categories">
		@foreach ($boardbar as $boards)
			<li class="boardlist-category">
				<ul class="boardlist-items">
					@foreach ($boards as $board)
					<li class="boardlist-item"><a href="{!! url($board->board_uri) !!}" class="boardlist-link">{!! $board->board_uri !!}</a></li>
					@endforeach
				</ul>
			</li>
		@endforeach
		</ul>
	</div>
	@endif
</nav>
