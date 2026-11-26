<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnquiryStatusHistory extends Model
{
    protected $fillable = ['loan_enquiry_id', 'changed_by', 'old_status', 'new_status', 'reason'];

    protected function casts(): array
    {
        return ['reason' => 'encrypted'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
