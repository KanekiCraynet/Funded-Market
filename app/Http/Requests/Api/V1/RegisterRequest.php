<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
            'phone' => 'nullable|string|max:20',
            'risk_level' => 'nullable|in:LOW,MEDIUM,HIGH',
            'time_horizon' => 'nullable|in:short_term,medium_term,long_term',
            'max_position_size' => 'nullable|numeric|between:1,50',
            'notifications' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'password_confirmation.required' => 'Password confirmation is required',
            'risk_level.in' => 'Risk level must be LOW, MEDIUM, or HIGH',
            'time_horizon.in' => 'Time horizon must be short_term, medium_term, or long_term',
            'max_position_size.between' => 'Maximum position size must be between 1% and 50%',
        ];
    }
}