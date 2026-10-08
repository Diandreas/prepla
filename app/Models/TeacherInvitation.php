<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Une invitation d'enseignant : un lien à usage unique, valable deux semaines.
 *
 * Le jeton en clair ne vit que dans le lien remis à la personne — la base n'en garde
 * qu'une empreinte. On ne peut donc pas retrouver un lien depuis la base, seulement
 * vérifier celui qu'on présente.
 */
class TeacherInvitation extends Model
{
    protected $fillable = [
        'token_hash', 'name', 'email', 'center_id', 'role',
        'space_name', 'classroom_name', 'level', 'exam_id',
        'invited_by', 'expires_at', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public const LIFETIME_DAYS = 14;

    /**
     * Émet une invitation et rend le jeton en clair — la seule fois où il existe.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(array $attributes): array
    {
        $plain = Str::random(48);

        $invitation = static::create(array_merge($attributes, [
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]));

        return [$invitation, $plain];
    }

    /** L'invitation correspondant à ce jeton, si elle est encore utilisable. */
    public static function findUsable(string $plainToken): ?self
    {
        return static::where('token_hash', hash('sha256', $plainToken))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /** Rejoindre un espace existant comme professeur, plutôt qu'en ouvrir un. */
    public function joinsExistingSpace(): bool
    {
        return $this->center_id !== null;
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(LanguageCenter::class, 'center_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
