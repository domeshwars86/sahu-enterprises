<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanProductFaq extends Model
{
    protected $fillable = ['loan_product_id', 'question', 'answer', 'display_order'];

    /** @return BelongsTo<LoanProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }
}
