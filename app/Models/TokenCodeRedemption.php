<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenCodeRedemption extends Model
{
    protected $fillable = ['token_code_id', 'user_id', 'tokens_received'];

    protected function casts(): array
    {
        return [
            'tokens_received' => 'integer',
        ];
    }

    public function tokenCode(): BelongsTo
    {
        return $this->belongsTo(TokenCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
