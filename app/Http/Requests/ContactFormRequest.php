<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::sharedRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::sharedMessages();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function sharedRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sharedMessages(): array
    {
        return [
            'name.required' => __('Please tell us your name.'),
            'email.required' => __('Please provide an email address we can reply to.'),
            'email.email' => __('Please provide a valid email address.'),
            'subject.required' => __('Please include a subject so we know what your message is about.'),
            'message.required' => __('Please write a message.'),
            'message.min' => __('Your message is a bit short — please add at least :min characters.'),
        ];
    }
}
