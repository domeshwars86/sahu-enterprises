<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    protected $fillable = ['loan_enquiry_id', 'assigned_user_id', 'created_by', 'title', 'description', 'due_at', 'priority', 'status', 'completed_at', 'reminder_sent_at'];

    protected function casts(): array
    {
        return ['description' => 'encrypted', 'due_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'reminder_sent_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<LoanEnquiry, $this> */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(LoanEnquiry::class, 'loan_enquiry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
