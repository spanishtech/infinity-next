@if (!$reply_to || !$reply_to->isLocked() || $board->canPostInLockedThreads($user))
@if (isset($post))
{!! Form::model($post, [
	'url'    => Request::url(),
	'method' => "PATCH",
	'files'  => true,
	'id'     => "mod-form",
	'class'  => "form-mod smooth-box",
]) !!}
@else
{!! Form::open([
	'url'    => url($board->board_uri . '/thread/' . ($reply_to ? $reply_to->board_id: "")),
	'files'  => true,
	'method' => "PUT",
	'id'     => "post-form",
	'class'  => "form-post",
	'data-widget' => "postbox",
]) !!}
@endif
	@if (!isset($post))
	<ul class="post-menu">
		<li class="menu-icon menu-icon-minimize">
			<span class="menu-icon-button"></span>
			<span class="menu-icon-text">Minimize</span>
		</li>
		<li class="menu-icon menu-icon-maximize">
			<span class="menu-icon-button"></span>
			<span class="menu-icon-text">Expand</span>
		</li>
		<li class="menu-icon menu-icon-close">
			<span class="menu-icon-button"></span>
			<span class="menu-icon-text">Close</span>
		</li>
	</ul>
	@endif
	
	<fieldset class="form-fields">
		<legend class="form-legend">{{ trans("board.legend." . implode($actions, "+")) }}</legend>
		
		@include('widgets.messages')
		
		<div class="field row-subject label-inline">
			{!! Form::text(
				'subject',
				old('subject'),
				[
					'id'        => "subject",
					'class'     => "field-control",
					'maxlength' => 255,
			]) !!}
			{!! Form::label(
				"subject",
				trans('board.field.subject'),
				[
					'class' => "field-label",
			]) !!}
		</div>
		
		<div class="field row-author label-inline">
			{!! Form::text(
				'author',
				isset($post) ? $post->author . ($post->capcode_id ? " ## {$post->capcode->capcode}" : "") : old('author'),
				[
					'id'          => "author",
					'class'       => "field-control",
					'maxlength'   => 255,
					'placeholder' => $board->getConfig('defaultName'),
					
					isset($post) && $post->capcode_id ? "disabled" : "data-enabled",
			]) !!}
			{!! Form::label(
				"author",
				trans('board.field.author'),
				[
					'class' => "field-label",
			]) !!}
		</div>
		
		<div class="field row-email label-inline">
			{!! Form::text(
				'email',
				old('email'),
				[
					'id'        => "email",
					'class'     => "field-control",
					'maxlength' => 254,
			]) !!}
			{!! Form::label(
				"email",
				trans('board.field.email'),
				[
					'class' => "field-label",
			]) !!}
		</div>
		
		<div class="field row-post">
			{!! Form::textarea(
				'body',
				old('body'),
				[
					'id'           => "body",
					'class'        => "field-control",
					'autocomplete' => "off",
			]) !!}
		</div>
		
		@if ($board->canAttach($user) && !isset($post))
		<div class="field row-file">
			<div class="dz-container">
				<span class="dz-instructions"><span class="dz-instructions-text">@lang('board.field.file-dz')</span></span>
				<div class="fallback">
					<input class="field-control" id="file" name="files[]" type="file" multiple />
					<div class="field-control">
						<label class="dz-spoiler"><input name="spoilers" type="checkbox" value="1" />&nbsp;@lang('board.field.spoilers')</label>
					</div>
				</div>
			</div>
		</div>
		@endif
		
		<div class="field row-captcha" style="display:@if ($board->canPostWithoutCaptcha($user)) none @else block @endif;">
			<label class="field-label" for="captcha">
				@if (!$board->canPostWithoutCaptcha($user))
					{!! captcha() !!}
				@else
					<img src="" class="captcha">
					<input type="hidden" name="captcha_hash" value="" />
				@endif
			</label>
			
			{!! Form::text(
				'captcha',
				"",
				[
					'id'           => "captcha",
					'class'        => "field-control",
					'placeholder'  => "Security Code",
					'autocomplete' => "off",
			]) !!}
		</div>
		
		<div class="field row-submit">
			{!! Form::button(
				trans("board.submit." . implode($actions, "+")),
				[
					'type'      => "submit",
					'id'        => "submit-post",
					'class'     => "field-submit",
			]) !!}
			
			@if (!$user->isAnonymous() && !isset($post))
			@if ($user->getCapcodes($board))
				<select id="capcode" class="field-control field-capcode" name="capcode">
					<option value="" selected>Capcode</option>
					
					@foreach ($user->getCapcodes($board) as $role)
						<option value="{!! $role->role_id !!}">{{{ $role->getCapcodeName() }}}</option>
					@endforeach
				</select>
			@endif
			@endif
		</div>
	</fieldset>
	
@if (!isset($form) || $form)
</form>
@endif
@endif