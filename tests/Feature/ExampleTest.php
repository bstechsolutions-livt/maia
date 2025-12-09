<?php

use function Pest\Laravel\get;

test('home redirects to login', function () {
    get(route('home'))->assertRedirect(route('login'));
});