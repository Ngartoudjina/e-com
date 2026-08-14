<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Création d'une commande à partir d'un panier.
 *
 * Règle centrale : **rien de ce qui touche à l'argent ne vient du client**.
 * La requête n'apporte que des identifiants de produit, des quantités et des
 * variantes. Les prix, le sous-total, les frais de port et le total sont
 * recalculés ici depuis la base. Faire confiance au panier transmis
 * permettrait de commander n'importe quoi à n'importe quel prix.
 */
class OrderService
{
    public function __construct(
        private readonly PromoService $promos,
        private readonly SettingsService $reglages,
    ) {}

    /**
     * @param  array<int, array{productId: string, quantity: int, size?: string|null, color?: string|null}>  $lignes
     */
    public function creer(array $lignes, array $client, ?string $uid = null): Order
    {
        if (! $lignes) {
            throw ValidationException::withMessages(['items' => 'Le panier est vide.']);
        }

        // Une seule requête pour toutes les références demandées.
        $identifiants = array_values(array_unique(array_column($lignes, 'productId')));
        $produits = Product::whereIn('id', $identifiants)->get()->keyBy('id');

        $manquants = array_diff($identifiants, $produits->keys()->all());
        if ($manquants) {
            throw ValidationException::withMessages([
                'items' => 'Une pièce du panier n’existe plus : '.implode(', ', $manquants),
            ]);
        }

        // Les quantités sont regroupées par variante avant tout contrôle de
        // stock : deux lignes de la même taille comptent pour leur somme.
        $regroupees = [];
        foreach ($lignes as $ligne) {
            $quantite = max(1, (int) ($ligne['quantity'] ?? 1));
            $cle = $ligne['productId'].'|'.($ligne['size'] ?? '').'|'.($ligne['color'] ?? '');

            $regroupees[$cle] ??= [
                'produit' => $produits[$ligne['productId']],
                'size' => $ligne['size'] ?? null,
                'color' => $ligne['color'] ?? null,
                'quantite' => 0,
            ];
            $regroupees[$cle]['quantite'] += $quantite;
        }

        // Contrôle de stock par référence, toutes variantes confondues :
        // le stock n'est pas ventilé par taille dans le schéma actuel.
        $demandeParProduit = [];
        foreach ($regroupees as $ligne) {
            $id = $ligne['produit']->getKey();
            $demandeParProduit[$id] = ($demandeParProduit[$id] ?? 0) + $ligne['quantite'];
        }

        foreach ($demandeParProduit as $id => $demande) {
            $produit = $produits[$id];
            if ($produit->stock < $demande) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        '« %s » : %d en stock, %d demandé%s.',
                        $produit->name,
                        $produit->stock,
                        $demande,
                        $demande > 1 ? 's' : ''
                    ),
                ]);
            }
        }

        $sousTotal = 0.0;
        foreach ($regroupees as $ligne) {
            $sousTotal += (float) $ligne['produit']->price * $ligne['quantite'];
        }

        $remise = $this->promos->remisePour($client['promoCode'] ?? null, $sousTotal);

        $mode = $client['shippingMethod'] ?? 'standard';
        $port = $this->fraisDePort($mode, $sousTotal - $remise);

        $total = round(max(0, $sousTotal - $remise + $port), 2);

        return DB::transaction(function () use ($regroupees, $client, $uid, $sousTotal, $remise, $port, $total, $mode) {
            $commande = Order::create([
                'reference' => Order::genererReference(),
                'uid' => $uid,
                'email' => $client['email'],
                'phone' => $client['phone'] ?? null,
                'status' => 'pending',
                'subtotal' => round($sousTotal, 2),
                'discount' => round($remise, 2),
                'shipping' => round($port, 2),
                'total' => $total,
                'currency' => config('boutique.devise'),
                'promo_code' => $remise > 0 ? strtoupper((string) ($client['promoCode'] ?? '')) : null,
                'shipping_method' => $mode,
                'shipping_name' => $client['name'] ?? null,
                'shipping_address' => $client['address'] ?? null,
                'shipping_postal_code' => $client['postalCode'] ?? null,
                'shipping_city' => $client['city'] ?? null,
                'shipping_country' => $client['country'] ?? 'France',
                'placed_at' => now(),
            ]);

            foreach ($regroupees as $ligne) {
                $produit = $ligne['produit'];
                $prix = (float) $produit->price;

                OrderItem::create([
                    'order_id' => $commande->getKey(),
                    'product_id' => $produit->getKey(),
                    // Copies figées : la ligne ne doit pas suivre le produit.
                    'name' => $produit->name,
                    'reference' => 'GS-'.strtoupper(substr($produit->name, 0, 3)).'-'.strtoupper($ligne['size'] ?? 'U'),
                    'color' => $ligne['color'],
                    'size' => $ligne['size'],
                    'media_url' => $produit->media_url,
                    'unit_price' => $prix,
                    'quantity' => $ligne['quantite'],
                    'line_total' => round($prix * $ligne['quantite'], 2),
                ]);

                // Le stock est décrémenté en base, pas en mémoire : deux
                // commandes simultanées ne peuvent pas passer sous zéro.
                Product::whereKey($produit->getKey())->decrement('stock', $ligne['quantite']);
                Product::whereKey($produit->getKey())->increment('sold_count', $ligne['quantite']);
            }

            return $commande->load('items');
        });
    }

    /**
     * Le franco s'apprécie sur le montant après remise.
     * Les valeurs viennent des réglages, modifiables depuis l'administration.
     */
    public function fraisDePort(string $mode, float $montant): float
    {
        if ($montant >= (float) $this->reglages->get('freeShippingThreshold')) {
            return 0.0;
        }

        return (float) $this->reglages->get(
            $mode === 'express' ? 'shippingExpress' : 'shippingStandard'
        );
    }
}
