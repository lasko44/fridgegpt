<?php

namespace App\Http\Requests\Subscription;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating subscription cancellation.
 */
class CancelSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        return $user->is_subscribed;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
