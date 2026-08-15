<?php

/*
 * L'ancienne chaîne Neon ouvre-t-elle encore la base ?
 *
 * Connexion directe via PDO, sans passer par la configuration de
 * l'application, qui vise désormais la nouvelle base.
 */

$chemin = 'C:/Users/ADMIN/Documents/e-com/api/.env.avant-rotation';

if (! is_file($chemin)) {
    exit("Ancienne configuration introuvable : la référence est perdue.\n");
}

$ligne = null;
foreach (file($chemin) as $l) {
    if (str_starts_with($l, 'DB_URL=')) {
        $ligne = trim(substr($l, 7));
    }
}

if (! $ligne) {
    exit("Aucune DB_URL dans l'ancienne configuration.\n");
}

$parties = parse_url($ligne);
parse_str($parties['query'] ?? '', $options);

echo "Point d'accès : {$parties['host']}\n";

$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
    $parties['host'],
    $parties['port'] ?? 5432,
    ltrim($parties['path'], '/'),
    $options['sslmode'] ?? 'require'
);

try {
    $pdo = new PDO($dsn, $parties['user'], urldecode($parties['pass']), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
    ]);

    $n = $pdo->query('select count(*) from users')->fetchColumn();

    echo "État         : ENCORE ACCESSIBLE — {$n} utilisateur(s) lisibles\n";
    echo "               la chaîne exposée reste une clé valide.\n";
    exit(1);
} catch (Throwable $e) {
    $message = substr($e->getMessage(), 0, 180);
    echo "État         : refusée\n";
    echo "               {$message}\n";
}
