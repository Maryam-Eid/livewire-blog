<x-mail::message>
# Confirm your subscription

Thanks for subscribing to {{ config('app.name') }}. Confirm your email address to start receiving new posts and newsletters.

<x-mail::button :url="$verificationUrl">
Confirm subscription
</x-mail::button>

This confirmation link expires in 24 hours. If you did not request this subscription, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
