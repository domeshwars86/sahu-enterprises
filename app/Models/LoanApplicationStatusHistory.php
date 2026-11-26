<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApplicationStatusHistory extends Model
{
    protected $fillable = ['loan_application_id', 'changed_by', 'old_stage', 'new_stage', 'reason'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted'];
    }

    /** @return BelongsTo<LoanApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }
}
