<nav class="cp-top">
	<section class="cp-linklists">
		@if (!$user->isAnonymous())
		<ul class="cp-linkgroups">
			<li class="cp-linkgroup">
				<a class="linkgroup-name linkgroup-home" href="{!! url('cp') !!}">Home</a>
			</li>
			
			@if ($user->canCreateBoard() || $user->canEditAnyConfig())
			<li class="cp-linkgroup">
				<a class="linkgroup-name linkgroup-home" href="{!! url('cp/boards') !!}">Boards</a>
			</li>
			@endif
			
			@if ($user->canAdminConfig())
			<li class="cp-linkgroup">
				<a class="linkgroup-name linkgroup-home" href="{!! url('cp/site') !!}">Site</a>
			</li>
			@endif
			
			@if ($user->canAdminUsers() || $user->canAdminPermissions())
			<li class="cp-linkgroup">
				<a class="linkgroup-name linkgroup-home" href="{!! url('cp/users') !!}">Users</a>
			</li>
			@endif
		</ul>
		@endif
		
		<ul class="cp-linkgroups linkgroups-user">
			@if (!$user->isAnonymous())
			<li class="cp-linkgroup">
				Signed in as {{ $user->username }}
			</li>
			<li class="cp-linkgroup">
				<a class="linkgroup-name" href="{!! url('cp/auth/logout') !!}">Logout</a>
			</li>
			@else
			<li class="cp-linkgroup">
				<a class="linkgroup-name" href="{!! url('cp/auth/register') !!}">Register</a>
			</li>
			<li class="cp-linkgroup">
				<a class="linkgroup-name" href="{!! url('cp/auth/login') !!}">Login</a>
			</li>
			@endif
		</ul>
	</section>
</nav>