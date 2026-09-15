<?php

use Laravel\Fortify\Features;

test('landing page does not show theme toggle', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('data-test="theme-toggle"', false);
});

test('landing page loads flux appearance script', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('flux.appearance', false);
});

test('login page does not show theme toggle', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('data-test="theme-toggle"', false);
});

test('registration page does not show theme toggle', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('data-test="theme-toggle"', false);
});

test('forgot password page does not show theme toggle', function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    $this->get(route('password.request'))
        ->assertOk()
        ->assertDontSee('data-test="theme-toggle"', false);
});

test('guest pages do not force dark mode on the html element', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('class="dark"', false);
    $this->get(route('login'))->assertOk()->assertDontSee('class="dark"', false);
});

test('guest pages default to light theme when no preference is stored', function () {
    $this->get(route('home'))->assertOk()->assertSee('id="theme-default"', false);
    $this->get(route('login'))->assertOk()->assertSee('id="theme-default"', false);
});
