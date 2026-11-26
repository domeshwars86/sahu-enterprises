<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnquiryNote extends Model
{
    protected $fillable = ['loan_enquiry_id', 'user_id', 'body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted'];
    }

    /** @return BelongsTo<LoanEnquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(LoanEnquiry::class, 'loan_enquiry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
