<x-mail::message>
@if ($imageUrl)
<img src="{{ $imageUrl }}" alt="" style="max-width: 100%; border-radius: 12px; margin-bottom: 24px;">
@endif

{!! nl2br(e($message)) !!}

<x-mail::button :url="config('app.frontend_url')">
Voir la boutique
</x-mail::button>

Cordialement,
L'équipe GOLDSHOP

<x-mail::subcopy>
Vous recevez cet e-mail parce que vous êtes inscrit à la newsletter GOLDSHOP.
[Se désabonner en un clic]({{ $lienDesabonnement }})
</x-mail::subcopy>
</x-mail::message>
