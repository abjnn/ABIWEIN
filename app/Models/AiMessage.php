<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Représente un seul échange privé avec l'assistant ABIWEIN. */
#[Fillable(['user_id', 'role', 'content', 'provider'])]
class AiMessage extends Model
{
    /** Retourne le compte auquel appartient exclusivement le message. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
