<x-mail::message>
# Bienvenue sur GOLDSHOP

Bonjour {{ $prenom }},

Merci de vous être inscrit. Il ne reste qu'une étape : confirmer votre adresse e-mail.

<x-mail::button :url="$lien">
Vérifier mon adresse
</x-mail::button>

Ce lien expire dans 24 heures. Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement ce message.

Cordialement,
L'équipe GOLDSHOP

<x-mail::subcopy>
Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
{{ $lien }}
</x-mail::subcopy>
</x-mail::message>
