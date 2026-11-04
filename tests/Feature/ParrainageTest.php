<?php

use App\Listeners\EnregistrerCommission;
use App\Models\CenterCommission;
use App\Models\LanguageCenter;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Center\Parrainage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use App\Services\Center\FactureDuPaiement;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Socialite\Facades\Socialite;

/**
 * Lot D : parrainage par un centre (/r/{code}) et commission de 25 % sur les
 * factures Stripe payees par les comptes qu'il a amenes.
 */
beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
});

function parrCentre(string $nom = 'Centre Parrain', bool $actif = true): LanguageCenter
{
    return LanguageCenter::create([
        'name' => $nom, 'slug' => \Illuminate\Support\Str::slug($nom).'-'.uniqid(),
        'owner_email' => 'owner-'.uniqid().'@exemple.test', 'is_active' => $actif,
    ]);
}

function parrMembre(LanguageCenter $centre, string $role): User
{
    $u = User::factory()->create();
    UserProfile::factory()->for($u)->create(['onboarding_completed_at' => now()]);
    $centre->members()->attach($u->id, ['role' => $role, 'joined_at' => now()]);

    return $u;
}

function parrInscription(array $extra = []): array
{
    return array_merge([
        'name' => 'Nouvel Eleve',
        'email' => 'nouveau-'.uniqid().'@exemple.test',
        'phone' => '+237 600000000',
        'password' => 'password-solide-123',
        'password_confirmation' => 'password-solide-123',
    ], $extra);
}

function parrCompteParraine(LanguageCenter $centre, string $stripeId = 'cus_parr1'): User
{
    $u = User::factory()->create(['stripe_id' => $stripeId]);
    $u->forceFill(['referred_by_center_id' => $centre->id, 'referred_at' => now()])->save();

    return $u;
}

/**
 * Facture payee, forme de l'API basil qu'utilise Cashier (2025-08-27.basil) :
 * ni « charge » ni « subscription » a la racine, l'abonnement est sous parent.
 */
function parrFacture(string $client, string $facture = 'in_001', int $montant = 1000, array $extra = []): WebhookReceived
{
    return new WebhookReceived(parrFacturePayload($client, $facture, $montant, $extra));
}

function parrFacturePayload(string $client, string $facture = 'in_001', int $montant = 1000, array $extra = []): array
{
    return [
        'id' => 'evt_'.$facture,
        'type' => 'invoice.payment_succeeded',
        'data' => ['object' => array_merge([
            'id' => $facture,
            'object' => 'invoice',
            'customer' => $client,
            'amount_paid' => $montant,
            'currency' => 'eur',
            'parent' => ['type' => 'subscription_details', 'subscription_details' => ['subscription' => 'sub_001', 'metadata' => []]],
            'created' => now()->timestamp,
        ], $extra)],
    ];
}

/**
 * Remboursement, forme basil : le paiement n'a plus de champ « invoice »,
 * seulement son payment_intent. Avec $facture, l'ancienne forme.
 */
function parrRemboursement(string $client, string $charge, int $cumul, ?string $facture = null, ?string $intention = null): WebhookReceived
{
    return new WebhookReceived(parrRemboursementPayload($client, $charge, $cumul, $facture, $intention));
}

function parrRemboursementPayload(string $client, string $charge, int $cumul, ?string $facture = null, ?string $intention = null): array
{
    $objet = [
        'id' => $charge,
        'object' => 'charge',
        'customer' => $client,
        'amount_refunded' => $cumul,
        'currency' => 'eur',
        'payment_intent' => $intention ?? 'pi_'.$charge,
        'created' => now()->timestamp,
    ];
    if ($facture) {
        $objet['invoice'] = $facture;
    }

    return ['id' => 'evt_remb_'.$charge.'_'.$cumul, 'type' => 'charge.refunded', 'data' => ['object' => $objet]];
}

/**
 * Remplace l'appel Stripe qui relie un PaymentIntent a sa facture, et compte
 * les appels. Sans correspondance, le PaymentIntent ne regle aucune facture.
 */
function parrStripeFactures(array $correspondances = []): object
{
    $faux = new class($correspondances) extends FactureDuPaiement
    {
        public array $appels = [];

        public function __construct(private array $correspondances) {}

        public function pourPaymentIntent(string $paymentIntent): ?string
        {
            $this->appels[] = $paymentIntent;

            return $this->correspondances[$paymentIntent] ?? null;
        }
    };
    app()->instance(FactureDuPaiement::class, $faux);

    return $faux;
}

function parrFakeGoogle(string $email, string $googleId): void
{
    $g = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $g->shouldReceive('getId')->andReturn($googleId);
    $g->shouldReceive('getEmail')->andReturn($email);
    $g->shouldReceive('getName')->andReturn('Compte Google');
    $g->shouldReceive('getAvatar')->andReturn(null);
    Socialite::shouldReceive('driver->user')->andReturn($g);
}

// ── Code du centre ───────────────────────────────────────────────────────────

test('chaque centre recoit a sa creation un code aleatoire de 8 caracteres non ambigus', function () {
    $a = parrCentre('A');
    $b = parrCentre('B');

    expect($a->referral_code)->toMatch('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/')
        ->and($b->referral_code)->not->toBe($a->referral_code)
        ->and($a->referral_code)->not->toContain($a->slug);
});

test('les colonnes de rattachement ne sont pas remplissables en masse', function () {
    $u = new User;
    expect($u->isFillable('referred_by_center_id'))->toBeFalse()
        ->and($u->isFillable('referred_at'))->toBeFalse();
});

test('la migration donne un code aux centres qui existaient avant elle', function () {
    // Un centre ancien, sans code : la migration rejouee le lui donne.
    $c = parrCentre('Ancien');
    \Illuminate\Support\Facades\DB::table('language_centers')->where('id', $c->id)->update(['referral_code' => null]);

    $migration = require database_path('migrations/2026_11_04_400001_add_referral_code_to_language_centers.php');
    $migration->down();
    expect(Schema::hasColumn('language_centers', 'referral_code'))->toBeFalse();
    $migration->up();

    expect(LanguageCenter::find($c->id)->referral_code)->toMatch('/^[A-Z2-9]{8}$/');
});

// ── Lien /r/{code} ───────────────────────────────────────────────────────────

test('le lien range le code en session et en cookie puis mene a l\'inscription', function () {
    $c = parrCentre();

    $this->get('/r/'.strtolower($c->referral_code))
        ->assertRedirect(route('register'))
        ->assertSessionHas(Parrainage::CLE, $c->referral_code)
        ->assertCookie(Parrainage::CLE, $c->referral_code);
});

test('un code mal forme n\'est pas range, et la reponse ne trahit rien', function () {
    $this->get('/r/pas-un-code')
        ->assertRedirect(route('register'))
        ->assertSessionMissing(Parrainage::CLE);
});

// ── Rattachement a l'inscription ─────────────────────────────────────────────

test('le code est applique a l\'inscription classique', function () {
    $c = parrCentre();

    $this->get('/r/'.$c->referral_code);
    $this->post('/register', parrInscription(['email' => 'eleve@exemple.test']))->assertRedirect();

    $u = User::where('email', 'eleve@exemple.test')->first();
    expect($u->referred_by_center_id)->toBe($c->id)
        ->and($u->referred_at)->not->toBeNull();
    // Le code est consomme : il ne sert pas une seconde fois.
    expect(session(Parrainage::CLE))->toBeNull();
});

test('le cookie seul suffit quand la session a expire', function () {
    $c = parrCentre();

    $this->withCookie(Parrainage::CLE, $c->referral_code)
        ->post('/register', parrInscription(['email' => 'cookie@exemple.test']));

    expect(User::where('email', 'cookie@exemple.test')->value('referred_by_center_id'))->toBe($c->id);
});

test('un champ referred_by_center_id glisse dans le formulaire est ignore', function () {
    $c = parrCentre();

    $this->post('/register', parrInscription(['email' => 'malin@exemple.test', 'referred_by_center_id' => $c->id]));

    expect(User::where('email', 'malin@exemple.test')->value('referred_by_center_id'))->toBeNull();
});

test('le code est applique a une inscription par Google', function () {
    $c = parrCentre();
    parrFakeGoogle('google-neuf@exemple.test', 'g-neuf');

    $this->get('/r/'.$c->referral_code);
    $this->get(route('auth.google.callback'));

    expect(User::where('email', 'google-neuf@exemple.test')->value('referred_by_center_id'))->toBe($c->id);
});

test('un compte existant qui se connecte par Google n\'est pas rattache', function () {
    $c = parrCentre();
    $ancien = User::factory()->create(['email' => 'ancien@exemple.test', 'google_id' => 'g-ancien']);
    parrFakeGoogle('ancien@exemple.test', 'g-ancien');

    $this->get('/r/'.$c->referral_code);
    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($ancien->fresh());
    expect($ancien->fresh()->referred_by_center_id)->toBeNull();
});

test('un compte existant lie a Google par son adresse n\'est pas rattache non plus', function () {
    $c = parrCentre();
    $ancien = User::factory()->create(['email' => 'verifie@exemple.test']);
    parrFakeGoogle('verifie@exemple.test', 'g-verifie');

    $this->get('/r/'.$c->referral_code);
    $this->get(route('auth.google.callback'));

    expect($ancien->fresh()->referred_by_center_id)->toBeNull();
});

test('un code inconnu est ignore', function () {
    parrCentre();

    $this->get('/r/ZZZZZZZZ');
    $this->post('/register', parrInscription(['email' => 'inconnu@exemple.test']))->assertRedirect();

    expect(User::where('email', 'inconnu@exemple.test')->value('referred_by_center_id'))->toBeNull();
});

test('le code d\'un centre desactive est ignore', function () {
    $c = parrCentre('Ferme', false);

    $this->get('/r/'.$c->referral_code);
    $this->post('/register', parrInscription(['email' => 'ferme@exemple.test']));

    expect(User::where('email', 'ferme@exemple.test')->value('referred_by_center_id'))->toBeNull();
});

test('le personnel du centre ne peut pas etre parraine par son propre centre', function () {
    $c = parrCentre();
    // Le responsable (adresse du centre) qui se cree un second compte.
    $this->get('/r/'.$c->referral_code);
    $this->post('/register', parrInscription(['email' => $c->owner_email]));
    expect(User::where('email', $c->owner_email)->value('referred_by_center_id'))->toBeNull();

    // Un enseignant invite par ce centre, qui s'inscrit avec le lien.
    TeacherInvitation::issue(['name' => 'Prof', 'email' => 'Prof@Exemple.test', 'center_id' => $c->id, 'role' => 'teacher', 'invited_by' => User::factory()->create()->id]);
    $this->get('/r/'.$c->referral_code);
    $this->post('/register', parrInscription(['email' => 'prof@exemple.test']));
    expect(User::where('email', 'prof@exemple.test')->value('referred_by_center_id'))->toBeNull();

    // Un membre deja enregistre au personnel.
    $prof = parrMembre($c, 'teacher');
    $req = \Illuminate\Http\Request::create('/');
    $req->setLaravelSession(app('session')->driver());
    $req->session()->put(Parrainage::CLE, $c->referral_code);
    expect(Parrainage::rattacher($prof, $req))->toBeNull()
        ->and($prof->fresh()->referred_by_center_id)->toBeNull();
});

// ── Commission ───────────────────────────────────────────────────────────────

test('un paiement d\'un compte parraine cree une commission de 25 %', function () {
    $c = parrCentre();
    $u = parrCompteParraine($c);

    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_001', 1999));

    $l = CenterCommission::sole();
    expect($l->center_id)->toBe($c->id)
        ->and($l->user_id)->toBe($u->id)
        ->and($l->stripe_invoice_id)->toBe('in_001')
        ->and($l->stripe_subscription_id)->toBe('sub_001')
        ->and($l->amount_paid)->toBe(1999)
        ->and($l->currency)->toBe('EUR')
        ->and($l->rate)->toBe(25)
        ->and($l->commission)->toBe(500)
        ->and($l->type)->toBe('charge')
        ->and($l->periode)->toBe(now('Africa/Douala')->format('Y-m'));
});

test('la periode suit l\'heure de Douala', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    // 31 octobre 23 h 30 UTC = 1er novembre 0 h 30 a Douala.
    $paye = \Illuminate\Support\Carbon::parse('2026-10-31 23:30:00', 'UTC')->timestamp;

    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_douala', 1000, ['status_transitions' => ['paid_at' => $paye]]));

    expect(CenterCommission::sole()->periode)->toBe('2026-11');
});

test('l\'ancienne forme de la facture Stripe (subscription, charge) reste lue', function () {
    $c = parrCentre();
    parrCompteParraine($c);

    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_002', 1000, [
        'parent' => null,
        'subscription' => 'sub_ancien',
        'charge' => 'ch_ancien',
    ]));

    $l = CenterCommission::sole();
    expect($l->stripe_subscription_id)->toBe('sub_ancien')
        ->and($l->stripe_charge_id)->toBe('ch_ancien');
});

test('le meme evenement rejoue ne cree qu\'une ligne', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    $evt = parrFacture('cus_parr1', 'in_003', 1000);

    (new EnregistrerCommission)->handle($evt);
    (new EnregistrerCommission)->handle($evt);
    // Et par le vrai bus d'evenements : le listener n'y est branche qu'une fois.
    event($evt);

    expect(CenterCommission::count())->toBe(1);
});

test('chaque facture payee de l\'abonnement rapporte sa commission', function () {
    $c = parrCentre();
    parrCompteParraine($c);

    event(parrFacture('cus_parr1', 'in_m1', 1000));
    event(parrFacture('cus_parr1', 'in_m2', 1000));

    expect(CenterCommission::count())->toBe(2)
        ->and((int) CenterCommission::sum('commission'))->toBe(500);
});

test('rien pour une facture a zero, un compte non parraine ou un client inconnu', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    User::factory()->create(['stripe_id' => 'cus_libre']);

    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_zero', 0));
    (new EnregistrerCommission)->handle(parrFacture('cus_libre', 'in_libre', 1000));
    (new EnregistrerCommission)->handle(parrFacture('cus_inconnu', 'in_inconnu', 1000));
    (new EnregistrerCommission)->handle(new WebhookReceived(['type' => 'customer.subscription.updated', 'data' => ['object' => []]]));

    expect(CenterCommission::count())->toBe(0);
});

test('un compte parraine devenu membre du personnel du centre ne rapporte plus rien', function () {
    $c = parrCentre();
    $u = parrCompteParraine($c);
    $c->members()->attach($u->id, ['role' => 'teacher', 'joined_at' => now()]);

    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_perso', 1000));

    expect(CenterCommission::count())->toBe(0);
});

test('un remboursement (forme basil) cree une ligne negative, une seule meme rejouee', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    $stripe = parrStripeFactures(['pi_r' => 'in_r']);
    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_r', 2000));
    // La facture basil ne donne pas son paiement : rien a rapprocher directement.
    expect(CenterCommission::sole()->stripe_charge_id)->toBeNull();

    $remb = parrRemboursement('cus_parr1', 'ch_r', 2000, null, 'pi_r');
    (new EnregistrerCommission)->handle($remb);
    (new EnregistrerCommission)->handle($remb);

    $neg = CenterCommission::where('type', 'remboursement')->sole();
    expect($neg->amount_paid)->toBe(-2000)
        ->and($neg->commission)->toBe(-500)
        ->and($neg->center_id)->toBe($c->id)
        ->and($neg->stripe_charge_id)->toBe('ch_r')
        ->and($neg->stripe_subscription_id)->toBe('sub_001')
        ->and((int) CenterCommission::sum('commission'))->toBe(0)
        ->and($stripe->appels)->toBe(['pi_r', 'pi_r']);
});

test('un remboursement a l\'ancienne forme (champ invoice) est aussi repris, sans appel Stripe', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    $stripe = parrStripeFactures();
    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_old', 2000));

    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_old', 2000, 'in_old'));

    expect(CenterCommission::where('type', 'remboursement')->sole()->commission)->toBe(-500)
        ->and($stripe->appels)->toBe([]);
});

test('deux remboursements partiels font deux lignes, sans depasser la facture', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    parrStripeFactures(['pi_p' => 'in_p']);
    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_p', 2000));

    // Stripe envoie le CUMUL rembourse : 800, puis 2000.
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_p', 800, null, 'pi_p'));
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_p', 2000, null, 'pi_p'));
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_p', 800, null, 'pi_p'));

    $negs = CenterCommission::where('type', 'remboursement')->orderBy('id')->pluck('amount_paid')->all();
    expect($negs)->toBe([-800, -1200])
        ->and((int) CenterCommission::sum('commission'))->toBe(0);
});

test('un remboursement sans commission d\'origine n\'ecrit rien', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    parrStripeFactures(['pi_autre' => 'in_autre']);
    (new EnregistrerCommission)->handle(parrFacture('cus_parr1', 'in_compte', 1000));

    // Paiements de factures jamais comptees, ancienne et nouvelle forme.
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_inconnu', 1000, 'in_inconnu'));
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_autre', 1000, null, 'pi_autre'));
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_rien', 1000, null, 'pi_rien'));

    expect(CenterCommission::where('type', 'remboursement')->count())->toBe(0);
});

test('un remboursement d\'un compte sans commission n\'appelle pas Stripe', function () {
    $c = parrCentre();
    parrCompteParraine($c);
    User::factory()->create(['stripe_id' => 'cus_libre']);
    $stripe = parrStripeFactures(['pi_x' => 'in_x']);

    (new EnregistrerCommission)->handle(parrRemboursement('cus_libre', 'ch_x', 1000, null, 'pi_x'));
    (new EnregistrerCommission)->handle(parrRemboursement('cus_parr1', 'ch_x', 1000, null, 'pi_x'));

    expect(CenterCommission::count())->toBe(0)
        ->and($stripe->appels)->toBe([]);
});

test('un remboursement ne reprend jamais la commission d\'un autre compte', function () {
    $a = parrCentre('Centre A');
    $b = parrCentre('Centre B');
    parrCompteParraine($a, 'cus_a');
    parrCompteParraine($b, 'cus_b');
    // Le PaymentIntent pointerait vers la facture du compte de A, mais le
    // remboursement vient du client de B : rien n'est repris chez A.
    parrStripeFactures(['pi_b' => 'in_a']);
    (new EnregistrerCommission)->handle(parrFacture('cus_a', 'in_a', 2000));
    (new EnregistrerCommission)->handle(parrFacture('cus_b', 'in_b', 2000));

    (new EnregistrerCommission)->handle(parrRemboursement('cus_b', 'ch_b', 2000, null, 'pi_b'));

    expect(CenterCommission::where('type', 'remboursement')->count())->toBe(0);
});

// ── Par la vraie route stripe/webhook de Cashier ────────────────────────────

test('la route stripe/webhook enregistre le paiement puis le remboursement', function () {
    // Sans secret, Cashier ne verifie pas la signature.
    config(['cashier.webhook.secret' => null]);
    $c = parrCentre();
    parrCompteParraine($c);
    parrStripeFactures(['pi_route' => 'in_route']);

    $this->postJson('/stripe/webhook', parrFacturePayload('cus_parr1', 'in_route', 4000))->assertOk();
    $this->postJson('/stripe/webhook', parrFacturePayload('cus_parr1', 'in_route', 4000))->assertOk();
    expect(CenterCommission::where('type', 'charge')->sole()->commission)->toBe(1000);

    // charge.refunded : Cashier ne le traite pas lui-meme (aucun WebhookHandled).
    $remb = parrRemboursementPayload('cus_parr1', 'ch_route', 4000, null, 'pi_route');
    $this->postJson('/stripe/webhook', $remb)->assertOk();
    $this->postJson('/stripe/webhook', $remb)->assertOk();

    expect(CenterCommission::where('type', 'remboursement')->sole()->commission)->toBe(-1000)
        ->and((int) CenterCommission::sum('commission'))->toBe(0);
});

test('une signature invalide n\'ecrit aucune commission', function () {
    config(['cashier.webhook.secret' => 'whsec_test']);
    $c = parrCentre();
    parrCompteParraine($c);

    $this->postJson('/stripe/webhook', parrFacturePayload('cus_parr1', 'in_faux', 4000), ['Stripe-Signature' => 't=1,v1=faux'])
        ->assertForbidden();

    expect(CenterCommission::count())->toBe(0);
});

// ── Pages ────────────────────────────────────────────────────────────────────

test('le responsable voit son lien, ses comptes parraines et ses commissions du mois', function () {
    $c = parrCentre();
    $resp = parrMembre($c, 'center_admin');
    parrCompteParraine($c);
    event(parrFacture('cus_parr1', 'in_page', 4000));

    $this->actingAs($resp)->get(route('center.parrainage.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('center/parrainage')
            ->where('lien', url('/r/'.$c->referral_code))
            ->where('comptesParraines', 1)
            ->where('totaux.0.commission', 1000)
            ->has('lignes', 1)
            ->missing('lignes.0.user_id')
        );
});

test('un enseignant n\'a pas acces au parrainage du centre', function () {
    $c = parrCentre();
    $prof = parrMembre($c, 'teacher');

    $this->actingAs($prof)->get(route('center.parrainage.index'))->assertForbidden();
});

test('un eleve ou un visiteur n\'y a pas acces', function () {
    $c = parrCentre();
    $eleve = parrMembre($c, 'student');

    $this->actingAs($eleve)->get(route('center.parrainage.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('center.parrainage.index'))->assertRedirect(route('login'));
});

test('un centre ne voit jamais les comptes ni les commissions d\'un autre', function () {
    $a = parrCentre('Centre A');
    $b = parrCentre('Centre B');
    $respB = parrMembre($b, 'center_admin');
    parrCompteParraine($a, 'cus_de_a');
    event(parrFacture('cus_de_a', 'in_de_a', 4000));

    $this->actingAs($respB)->get(route('center.parrainage.index').'?center_id='.$a->id)
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->where('centre.id', $b->id)
            ->where('code', $b->referral_code)
            ->where('comptesParraines', 0)
            ->has('lignes', 0)
            ->has('totaux', 0)
        );
});

test('le super administrateur voit les commissions par centre et par mois', function () {
    $a = parrCentre('Centre A');
    $b = parrCentre('Centre B');
    parrCompteParraine($a, 'cus_a');
    parrCompteParraine($b, 'cus_b');
    event(parrFacture('cus_a', 'in_a', 2000));
    event(parrFacture('cus_b', 'in_b', 4000));
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin)->get(route('admin.commissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p
            ->component('admin/commissions')
            ->has('parCentre', 2)
            ->where('parCentre.0.centre', 'Centre A')
            ->where('parCentre.0.commission', 500)
            ->where('parCentre.1.commission', 1000)
        );

    // Un autre mois : rien.
    $this->actingAs($admin)->get(route('admin.commissions.index', ['periode' => '2020-01']))
        ->assertInertia(fn (Assert $p) => $p->where('periode', '2020-01')->has('parCentre', 0));
});

test('un responsable de centre n\'accede pas aux commissions de tous les centres', function () {
    $c = parrCentre();
    $resp = parrMembre($c, 'center_admin');

    $this->actingAs($resp)->get(route('admin.commissions.index'))->assertForbidden();
});

test('le menu du responsable propose le parrainage', function () {
    $c = parrCentre();
    $resp = parrMembre($c, 'center_admin');

    $this->actingAs($resp)->get(route('center.dashboard'))
        ->assertInertia(fn (Assert $p) => $p->where('menus.parrainage', true));
});
