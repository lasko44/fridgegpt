<?php

namespace App\Http\Requests;

use AllowDynamicProperties;
use App\Facades\ModelSlugger;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{

    /**
     * @throws Exception
     */
    protected function prepareForValidation(): void
    {
        // If the name has changed, generate a new username slug
        if($this->nameChanged()){
            $this->merge([
                'username' => ModelSlugger::slug(User::class, $this->input('name'), 'username'),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->user()->id ?? null;

        return [
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'. $userId,
            'email' => 'sometimes|nullable|string|email|max:255|unique:users,email,'. $userId,
        ];
    }


    private function nameChanged():bool
    {
        $model = $this->route('user') ?? $this->user();
        $currentName = $model ? $model->name : null;

        return $this->filled('name') && $this->input('name') !== $currentName;
    }
}
