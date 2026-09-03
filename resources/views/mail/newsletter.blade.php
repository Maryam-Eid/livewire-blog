<x-mail::message>
<div style="text-align: center; padding: 8px 0 28px;">
    <a href="{{ route('blog.index') }}" style="display: inline-block; text-decoration: none;">
        <img
            src="{{ $message->embed(public_path('newsletter-logo.jpg')) }}"
            alt="{{ config('app.name') }}"
            width="150"
            style="display: block; width: 150px; height: auto; margin: 0 auto 12px;"
        >
    </a>
    <p style="margin: 8px 0 0; color: #6b7280; font-size: 14px;">
        Stories, ideas, and updates selected for you.
    </p>
</div>

<div style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; background: #ffffff; color: #1f2937; line-height: 1.7;">
    {!! $formattedContent !!}
</div>

@if ($unsubscribeUrl !== null)
<div style="margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 24px; text-align: center;">
    <p style="margin: 0 0 16px; color: #6b7280; font-size: 13px; line-height: 1.6;">
        You received this email because you subscribed to {{ config('app.name') }}.
    </p>
</div>

<x-mail::button :url="$unsubscribeUrl" color="error">
Unsubscribe
</x-mail::button>

<p style="margin: 16px 0 0; color: #9ca3af; font-size: 12px; line-height: 1.6; text-align: center;">Or copy this link: <a href="{{ $unsubscribeUrl }}" style="color: #6d28d9; word-break: break-all;">{{ $unsubscribeUrl }}</a></p>
@endif
</x-mail::message>
