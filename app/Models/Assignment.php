<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'created_by',
        'title',
        'instructions',
        'due_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssignmentItem::class)->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isOverdue(): bool
    {
        return self::depassee($this->due_at);
    }

    /**
     * L'echeance est-elle depassee ? L'enseignant la saisit avec un champ date
     * (center/assignments/create.tsx) : elle arrive a minuit pile, et le devoir
     * passait « en retard » des le matin du jour de remise. Une echeance a
     * 00:00 vaut donc pour toute la journee ; une heure precise reste precise.
     */
    public static function depassee(?\Carbon\CarbonInterface $echeance): bool
    {
        if ($echeance === null) {
            return false;
        }

        $limite = $echeance->format('H:i:s') === '00:00:00' ? $echeance->copy()->endOfDay() : $echeance;

        return $limite->isPast();
    }
}
