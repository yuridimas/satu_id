<?php

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Language settings')] class extends Component {
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = session('locale', app()->getLocale());
    }

    public function updatedLocale(string $value): void
    {
        if (! in_array($value, ['en', 'id'], true)) {
            return;
        }

        session(['locale' => $value]);
        app()->setLocale($value);

        Flux::toast(text: __('Language updated.'), variant: 'success');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Language settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Language')" :subheading="__('Choose your preferred language')">
        <flux:radio.group wire:model.live="locale" variant="segmented">
            <flux:radio value="en" icon="globe-alt">English</flux:radio>
            <flux:radio value="id" icon="globe-alt">Indonesia</flux:radio>
        </flux:radio.group>
        <flux:text variant="subtle" class="mt-3 text-xs">{{ __('Language will be applied on your next navigation. If some texts still appear in the previous language, please refresh the page.') }}</flux:text>
    </x-pages::settings.layout>
</section>
