<dl class="option option-{{{ $option_name }}}">
	<dt class="option-term">
		{!! Form::label(
			$option_name,
			trans("config.option.{$option_name}"),
			[
				'class' => "field-label",
		]) !!}
	</dt>
	<dd class="option-definition">
		{!! Form::textarea(
			$option_name,
			$option_value,
			[
				'id'        => $option_name,
				'class'     => "field-control",
		]) !!}
	</dd>
</dl>
