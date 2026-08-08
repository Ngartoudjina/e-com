<?php

namespace App\Http\Controllers;

use App\Http\Resources\AffiliateRequestResource;
use App\Http\Resources\AffiliateResource;
use App\Models\Affiliate;
use App\Models\AffiliateRequest;
use App\Models\Referral;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Programme d'affiliation. Porté depuis back/src/controllers/affiliate.controller.js.
 *
 * Le contrôleur Node visait un schéma qui n'existe pas : il manipulait
 * `affiliates.userId/.email/.name/.code/.commission/.status` et
 * `referrals.affiliateName/.userId/.productId/.clickedAt`, absents de
 * schema.js. Il ne pouvait donc pas s'exécuter. Le portage suit le schéma
 * versionné, qui est aussi celui qu'attend AffiliateView.vue.
 */
class AffiliateController extends Controller
{
    private const TAUX_COMMISSION = 0.05;

    public function __construct(private readonly MediaService $medias) {}

    // ---------------------------------------------------------------- côté membre

    public function submitRequest(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            // Le formulaire impose 50 caractères minimum côté client : on le
            // rejoue ici, un client n'étant jamais une garantie.
            'reason' => ['required', 'string', 'min:50', 'max:2000'],
            'identityCard' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $uid = $request->user()->getKey();

        if (Affiliate::where('uid', $uid)->exists()) {
            return response()->json(['error' => 'Vous êtes déjà affilié'], 400);
        }

        if (AffiliateRequest::where('uid', $uid)->where('status', 'pending')->exists()) {
            return response()->json(['error' => 'Une demande est déjà en attente'], 400);
        }

        $media = $this->medias->envoyerImage($request->file('identityCard'), 'affiliates');

        $demande = AffiliateRequest::create([
            'uid' => $uid,
            'reason' => $donnees['reason'],
            'identity_card_url' => $media['url'],
            'identity_card_public_id' => $media['publicId'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'request' => (new AffiliateRequestResource($demande))->resolve(),
            'message' => 'Demande soumise, en attente de validation',
        ], 201);
    }

    public function status(Request $request): JsonResponse
    {
        $uid = $request->user()->getKey();

        $affilie = Affiliate::where('uid', $uid)->first();

        if ($affilie) {
            return response()->json([
                'isAffiliate' => true,
                'affiliateData' => (new AffiliateResource($affilie))->resolve(),
                'requestStatus' => 'approved',
                'hasPendingRequest' => false,
            ]);
        }

        $demande = AffiliateRequest::where('uid', $uid)->latest('created_at')->first();

        return response()->json([
            'isAffiliate' => false,
            'requestStatus' => $demande?->status,
            'hasPendingRequest' => $demande?->status === 'pending',
            'rejectionReason' => $demande?->status === 'rejected' ? $demande->reason : null,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $affilie = Affiliate::where('uid', $request->user()->getKey())->first();

        if (! $affilie) {
            return response()->json(['error' => 'Compte affilié non trouvé'], 403);
        }

        $parrainages = Referral::where('affiliate_id', $affilie->getKey())->get();

        $commandes = 0;
        $chiffre = 0.0;

        foreach ($parrainages as $parrainage) {
            foreach ((array) ($parrainage->orders ?? []) as $commande) {
                $commandes++;
                $chiffre += (float) ($commande['total'] ?? $commande['amount'] ?? 0);
            }
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'code' => $affilie->affiliate_code,
                'totalClicks' => $parrainages->count(),
                'totalOrders' => $commandes,
                'totalRevenue' => $chiffre,
                'commissionRate' => (float) $affilie->commission_rate,
                'totalEarnings' => round($chiffre * (float) $affilie->commission_rate, 2),
            ],
            'recentReferrals' => $parrainages->take(20)->map(fn (Referral $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'firstClickAt' => $r->first_click_at?->toIso8601String(),
                'lastClickAt' => $r->last_click_at?->toIso8601String(),
                'orders' => $r->orders ?? [],
            ])->values()->all(),
        ]);
    }

    /** Suivi d'un clic sur un lien de parrainage. Endpoint public. */
    public function trackClick(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'ref' => ['required', 'string', 'max:64'],
        ]);

        $affilie = Affiliate::where('affiliate_code', $donnees['ref'])->where('is_active', true)->first();

        // Réponse identique qu'il existe ou non : un code invalide ne doit pas
        // permettre de deviner les codes valides.
        if (! $affilie) {
            return response()->json(['success' => true, 'tracked' => false]);
        }

        $visiteur = $request->user()?->getKey();

        // Un même visiteur identifié ne crée qu'une ligne : on met à jour la
        // date du dernier clic plutôt que d'empiler les doublons.
        $parrainage = $visiteur
            ? Referral::where('affiliate_id', $affilie->getKey())->where('referred_user_id', $visiteur)->first()
            : null;

        if ($parrainage) {
            $parrainage->forceFill(['last_click_at' => now()])->save();
        } else {
            Referral::create([
                'affiliate_id' => $affilie->getKey(),
                'referred_user_id' => $visiteur ?? 'anonyme:'.Str::uuid(),
                'affiliate_code' => $affilie->affiliate_code,
                'first_click_at' => now(),
                'last_click_at' => now(),
                'status' => 'pending',
                'orders' => [],
            ]);

            $affilie->increment('referral_count');
        }

        return response()->json(['success' => true, 'tracked' => true]);
    }

    // ---------------------------------------------------------------- côté admin

    public function listRequests(string $tab): JsonResponse
    {
        $statut = in_array($tab, ['pending', 'approved', 'rejected'], true) ? $tab : 'pending';

        $demandes = AffiliateRequest::with('user')
            ->where('status', $statut)
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'requests' => AffiliateRequestResource::collection($demandes)->resolve(),
            'count' => $demandes->count(),
        ]);
    }

    public function approve(string $id): JsonResponse
    {
        $demande = AffiliateRequest::find($id);

        if (! $demande) {
            return response()->json(['error' => 'Demande non trouvée'], 404);
        }

        if ($demande->status !== 'pending') {
            return response()->json(['error' => 'Cette demande a déjà été traitée'], 400);
        }

        $affilie = DB::transaction(function () use ($demande) {
            $code = $this->genererCodeUnique();

            $affilie = Affiliate::create([
                'uid' => $demande->uid,
                'affiliate_code' => $code,
                'referral_link' => rtrim((string) config('app.frontend_url'), '/').'/?ref='.$code,
                'identity_card_url' => $demande->identity_card_url,
                'commission_rate' => self::TAUX_COMMISSION,
                'is_active' => true,
            ]);

            $demande->forceFill(['status' => 'approved'])->save();
            User::whereKey($demande->uid)->update(['is_affiliate' => true]);

            return $affilie;
        });

        return response()->json([
            'success' => true,
            'affiliate' => (new AffiliateResource($affilie))->resolve(),
            'message' => 'Demande approuvée',
        ]);
    }

    public function reject(string $id): JsonResponse
    {
        $demande = AffiliateRequest::find($id);

        if (! $demande) {
            return response()->json(['error' => 'Demande non trouvée'], 404);
        }

        $demande->forceFill(['status' => 'rejected'])->save();

        return response()->json([
            'success' => true,
            'request' => (new AffiliateRequestResource($demande))->resolve(),
            'message' => 'Demande rejetée',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $demande = AffiliateRequest::find($id);

        if (! $demande) {
            return response()->json(['error' => 'Demande non trouvée'], 404);
        }

        $publicId = $demande->identity_card_public_id;
        $demande->delete();

        // La pièce d'identité est une donnée personnelle : elle ne doit pas
        // survivre à la demande qui la justifiait.
        $this->medias->supprimer($publicId);

        return response()->json(['success' => true, 'message' => 'Demande supprimée']);
    }

    /**
     * Code d'affiliation unique.
     *
     * La version Node bouclait sans borne sur un tirage aléatoire ; la contrainte
     * d'unicité en base reste ici le garde-fou final.
     */
    private function genererCodeUnique(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $code = 'AFF-'.random_int(100000, 999999);

            if (! Affiliate::where('affiliate_code', $code)->exists()) {
                return $code;
            }
        }

        return 'AFF-'.strtoupper(Str::random(10));
    }
}
