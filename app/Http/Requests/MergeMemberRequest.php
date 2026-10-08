<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('members.manage');
    }

    public function rules(): array
    {
        return [
            'canonical_user_id' => ['required', 'integer', 'different:merged_user_id', 'exists:users,id'],
            'merged_user_id' => ['required', 'integer', 'different:canonical_user_id', 'exists:users,id'],
            'email_source_user_id' => ['required', 'integer', Rule::in(array_filter([
                $this->integer('canonical_user_id'),
                $this->integer('merged_user_id'),
            ]))],
            'mobile_source_user_id' => ['required', 'integer', Rule::in(array_filter([
                $this->integer('canonical_user_id'),
                $this->integer('merged_user_id'),
            ]))],
            'primary_membership_id' => ['required', 'integer', 'exists:member_branch_memberships,id'],
            'confirmation' => ['accepted'],
        ];
    }
}
