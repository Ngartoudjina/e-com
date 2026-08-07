<x-mail::message>
# Réinitialisation de mot de passe

Bonjour {{ $prenom }},

Vous avez demandé à réinitialiser votre mot de passe. Ce lien est valable **une heure**.

<x-mail::button :url="$lien">
Choisir un nouveau mot de passe
</x-mail::button>

Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe actuel reste inchangé.

Cordialement,
L'équipe GOLDSHOP

<x-mail::subcopy>
Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
{{ $lien }}
</x-mail::subcopy>
</x-mail::message>
