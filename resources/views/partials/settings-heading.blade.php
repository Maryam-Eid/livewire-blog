<div class="relative mb-6 w-full">
    <flux:heading size="xl" level="1">{{ __('My account') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        {{ auth()->user()->canSubscribeToPremium()
            ? __('Manage your profile, security, and Premium membership')
            : __('Manage your profile and security settings') }}
    </flux:subheading>
    <flux:separator variant="subtle" />
</div>
